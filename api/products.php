<?php
/**
 * SHOPPULSE Products API
 * Handles live search, category browsing, deals, trending items, single product details
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = get_db();
    $method = $_SERVER['REQUEST_METHOD'];

    // Single product by slug or id
    if (!empty($_GET['slug']) || !empty($_GET['id'])) {
        $identifier = !empty($_GET['slug']) ? clean_input($_GET['slug']) : (int)$_GET['id'];
        $field = !empty($_GET['slug']) ? 'p.slug' : 'p.id';

        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE {$field} = :val
            LIMIT 1
        ");
        $stmt->execute(['val' => $identifier]);
        $product = $stmt->fetch();

        if (!$product) {
            json_response(['error' => 'Product not found'], 404);
        }

        // Parse JSON fields
        if (!empty($product['gallery'])) {
            $product['gallery'] = json_decode($product['gallery'], true) ?: [$product['image_url']];
        } else {
            $product['gallery'] = [$product['image_url']];
        }

        if (!empty($product['specs'])) {
            $product['specs'] = json_decode($product['specs'], true) ?: [];
        } else {
            $product['specs'] = [];
        }

        // Fetch reviews
        $reviewStmt = $pdo->prepare("
            SELECT user_name, rating, comment, created_at 
            FROM reviews 
            WHERE product_id = :pid 
            ORDER BY created_at DESC
        ");
        $reviewStmt->execute(['pid' => $product['id']]);
        $product['reviews'] = $reviewStmt->fetchAll();

        // Fetch related products in same category
        $relStmt = $pdo->prepare("
            SELECT id, name, slug, brand, price, original_price, cashback_pct, rating, reviews_count, badge, image_url
            FROM products
            WHERE category_id = :cat_id AND id != :current_id
            LIMIT 4
        ");
        $relStmt->execute([
            'cat_id' => $product['category_id'],
            'current_id' => $product['id']
        ]);
        $product['related_products'] = $relStmt->fetchAll();

        json_response(['product' => $product]);
    }

    // List categories query
    if (isset($_GET['categories'])) {
        $stmt = $pdo->query("
            SELECT c.*, COUNT(p.id) AS product_count
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            GROUP BY c.id
            ORDER BY c.display_order ASC
        ");
        json_response(['categories' => $stmt->fetchAll()]);
    }

    // Product search, filtering, and listing
    $where = ['1=1'];
    $params = [];

    // Search query
    if (!empty($_GET['search'])) {
        $searchTerm = '%' . clean_input($_GET['search']) . '%';
        $where[] = '(p.name LIKE :search OR p.brand LIKE :search OR p.description LIKE :search)';
        $params['search'] = $searchTerm;
    }

    // Category filter
    if (!empty($_GET['category'])) {
        $cat = clean_input($_GET['category']);
        if (is_numeric($cat)) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = (int)$cat;
        } else {
            $where[] = 'c.slug = :category_slug';
            $params['category_slug'] = $cat;
        }
    }

    // Brand filter
    if (!empty($_GET['brand'])) {
        $where[] = 'p.brand = :brand';
        $params['brand'] = clean_input($_GET['brand']);
    }

    // Price range
    if (!empty($_GET['min_price'])) {
        $where[] = 'p.price >= :min_price';
        $params['min_price'] = (float)$_GET['min_price'];
    }
    if (!empty($_GET['max_price'])) {
        $where[] = 'p.price <= :max_price';
        $params['max_price'] = (float)$_GET['max_price'];
    }

    // Rating filter
    if (!empty($_GET['min_rating'])) {
        $where[] = 'p.rating >= :min_rating';
        $params['min_rating'] = (float)$_GET['min_rating'];
    }

    // Flags
    if (!empty($_GET['is_trending'])) {
        $where[] = 'p.is_trending = 1';
    }
    if (!empty($_GET['is_deal'])) {
        $where[] = 'p.is_deal = 1';
    }
    if (!empty($_GET['is_featured'])) {
        $where[] = 'p.is_featured = 1';
    }

    // Count total matches
    $countSql = "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE " . implode(' AND ', $where);
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();

    // Sorting
    $sort = $_GET['sort'] ?? 'featured';
    $orderBy = match ($sort) {
        'price_asc' => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'rating' => 'p.rating DESC, p.reviews_count DESC',
        'newest' => 'p.id DESC',
        'discount' => '(p.original_price - p.price) DESC',
        default => 'p.is_featured DESC, p.rating DESC, p.id ASC'
    };

    // Pagination
    $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 12;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $sql = "
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY {$orderBy}
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Format galleries
    foreach ($products as &$prod) {
        if (!empty($prod['gallery'])) {
            $prod['gallery'] = json_decode($prod['gallery'], true) ?: [$prod['image_url']];
        } else {
            $prod['gallery'] = [$prod['image_url']];
        }
    }

    // Brands list for filter sidebar
    $brandsStmt = $pdo->query("SELECT DISTINCT brand FROM products ORDER BY brand ASC");
    $availableBrands = $brandsStmt->fetchAll(PDO::FETCH_COLUMN);

    json_response([
        'products' => $products,
        'pagination' => [
            'total' => $totalCount,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalCount / $limit)
        ],
        'available_brands' => $availableBrands
    ]);

} catch (Exception $e) {
    json_response(['error' => 'Server error: ' . $e->getMessage()], 500);
}
