<?php
/**
 * SHOPPULSE Database & Core Session Configuration
 * Production-ready PDO connection with multi-port auto-discovery and SQLite fallback
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400 * 7,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Generate CSRF token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize session cart if missing
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

const SITE_NAME = 'SHOPPULSE';
const SITE_TAGLINE = 'Discover, Compare & Earn Cashback';
const DEFAULT_CURRENCY = '$';

/**
 * Get or create PDO Database Connection
 */
function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $db_name = 'ecommerce_db';
    $db_user = 'root';
    $db_pass = '';
    
    // Candidates: port 3305 (standard XAMPP custom), port 3306 (default MySQL)
    $ports = [3305, 3306];
    
    foreach ($ports as $port) {
        try {
            $dsn = "mysql:host=127.0.0.1;port={$port};dbname={$db_name};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            // Try next port
            continue;
        }
    }

    // Fallback: If MySQL is unavailable, use SQLite so the store is 100% operational offline
    try {
        $sqlite_file = __DIR__ . '/../ecommerce.sqlite';
        $needs_init = !file_exists($sqlite_file);
        $pdo = new PDO("sqlite:" . $sqlite_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        if ($needs_init) {
            init_sqlite_db($pdo);
        }
        return $pdo;
    } catch (Exception $e) {
        die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
    }
}

/**
 * SQLite Schema Initializer (Fallback for non-MySQL environments)
 */
function init_sqlite_db(PDO $pdo): void {
    $schema = "
    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        icon TEXT,
        description TEXT,
        image_url TEXT,
        display_order INTEGER DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        brand TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL,
        original_price REAL,
        cashback_pct REAL DEFAULT 2.0,
        rating REAL DEFAULT 4.5,
        reviews_count INTEGER DEFAULT 0,
        stock INTEGER DEFAULT 50,
        badge TEXT,
        is_featured INTEGER DEFAULT 0,
        is_trending INTEGER DEFAULT 0,
        is_deal INTEGER DEFAULT 0,
        deal_ends_at TEXT,
        image_url TEXT NOT NULL,
        gallery TEXT,
        specs TEXT,
        model_shape TEXT DEFAULT 'cube',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT DEFAULT 'customer',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_number TEXT NOT NULL UNIQUE,
        user_id INTEGER,
        customer_name TEXT NOT NULL,
        customer_email TEXT NOT NULL,
        customer_phone TEXT NOT NULL,
        shipping_address TEXT NOT NULL,
        city TEXT NOT NULL,
        state TEXT NOT NULL,
        zip_code TEXT NOT NULL,
        subtotal REAL NOT NULL,
        discount_amount REAL DEFAULT 0,
        shipping_fee REAL DEFAULT 0,
        tax_amount REAL DEFAULT 0,
        total_amount REAL NOT NULL,
        cashback_earned REAL DEFAULT 0,
        payment_method TEXT DEFAULT 'credit_card',
        payment_status TEXT DEFAULT 'paid',
        order_status TEXT DEFAULT 'confirmed',
        tracking_number TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        product_name TEXT NOT NULL,
        price REAL NOT NULL,
        quantity INTEGER NOT NULL,
        image_url TEXT,
        total REAL NOT NULL
    );
    CREATE TABLE IF NOT EXISTS reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        user_name TEXT NOT NULL,
        rating INTEGER NOT NULL,
        comment TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    ";
    $pdo->exec($schema);
}

/**
 * Output standard JSON response
 */
function json_response(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * CSRF Validation Helper
 */
function verify_csrf_token(?string $token): bool {
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get Cart Totals calculation
 */
function get_cart_summary(): array {
    $cart = $_SESSION['cart'] ?? [];
    $subtotal = 0.0;
    $total_items = 0;
    $cashback_total = 0.0;

    foreach ($cart as $item) {
        $subtotal += ($item['price'] * $item['quantity']);
        $total_items += $item['quantity'];
        $cashback_rate = ($item['cashback_pct'] ?? 2.0) / 100;
        $cashback_total += ($item['price'] * $item['quantity']) * $cashback_rate;
    }

    $discount = $_SESSION['coupon_discount'] ?? 0.0;
    $shipping = $subtotal > 99 || $subtotal === 0.0 ? 0.0 : 9.99;
    $tax = round($subtotal * 0.07, 2); // 7% standard sales tax
    $total = max(0, round($subtotal - $discount + $shipping + $tax, 2));

    return [
        'items' => array_values($cart),
        'total_items' => $total_items,
        'subtotal' => round($subtotal, 2),
        'discount' => round($discount, 2),
        'shipping' => round($shipping, 2),
        'tax' => round($tax, 2),
        'total' => $total,
        'cashback_total' => round($cashback_total, 2),
        'free_shipping_eligible' => $subtotal >= 99,
        'free_shipping_threshold' => 99.00,
        'amount_to_free_shipping' => max(0, round(99.00 - $subtotal, 2))
    ];
}

/**
 * Format Currency Helper
 */
function format_price(float|int $price): string {
    return '$' . number_format((float)$price, 2);
}

/**
 * Sanitize User Input
 */
function clean_input(mixed $data): string {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}
