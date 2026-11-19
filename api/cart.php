<?php
/**
 * SHOPPULSE Cart API
 * Session-based cart with real-time totals, coupon validation & stock verification
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = get_db();
    $method = $_SERVER['REQUEST_METHOD'];

    // Read payload (supports application/json and $_POST)
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $action = $input['action'] ?? ($_GET['action'] ?? 'get');

    switch ($action) {
        case 'get':
            json_response(get_cart_summary());
            break;

        case 'add':
            $productId = (int)($input['product_id'] ?? 0);
            $quantity = max(1, (int)($input['quantity'] ?? 1));

            if ($productId <= 0) {
                json_response(['error' => 'Invalid product ID'], 400);
            }

            // Fetch product
            $stmt = $pdo->prepare("SELECT id, name, slug, brand, price, original_price, cashback_pct, image_url, stock FROM products WHERE id = :id");
            $stmt->execute(['id' => $productId]);
            $product = $stmt->fetch();

            if (!$product) {
                json_response(['error' => 'Product does not exist'], 404);
            }

            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            // Check if already in cart
            if (isset($_SESSION['cart'][$productId])) {
                $newQty = $_SESSION['cart'][$productId]['quantity'] + $quantity;
                if ($newQty > $product['stock']) {
                    json_response(['error' => "Only {$product['stock']} units available in stock"], 400);
                }
                $_SESSION['cart'][$productId]['quantity'] = $newQty;
            } else {
                $_SESSION['cart'][$productId] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'slug' => $product['slug'],
                    'brand' => $product['brand'],
                    'price' => (float)$product['price'],
                    'original_price' => $product['original_price'] ? (float)$product['original_price'] : null,
                    'cashback_pct' => (float)$product['cashback_pct'],
                    'image_url' => $product['image_url'],
                    'quantity' => min($quantity, $product['stock']),
                    'stock' => (int)$product['stock']
                ];
            }

            json_response([
                'success' => true,
                'message' => "Added '{$product['name']}' to cart",
                'cart' => get_cart_summary()
            ]);
            break;

        case 'update':
            $productId = (int)($input['product_id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 1);

            if (!isset($_SESSION['cart'][$productId])) {
                json_response(['error' => 'Item not in cart'], 404);
            }

            if ($quantity <= 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                // Check stock
                $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = :id");
                $stmt->execute(['id' => $productId]);
                $stock = (int)$stmt->fetchColumn();

                if ($quantity > $stock) {
                    json_response(['error' => "Only {$stock} units available"], 400);
                }
                $_SESSION['cart'][$productId]['quantity'] = $quantity;
            }

            json_response([
                'success' => true,
                'cart' => get_cart_summary()
            ]);
            break;

        case 'remove':
            $productId = (int)($input['product_id'] ?? 0);
            if (isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
            }
            json_response([
                'success' => true,
                'message' => 'Item removed from cart',
                'cart' => get_cart_summary()
            ]);
            break;

        case 'coupon':
            $code = strtoupper(trim($input['code'] ?? ''));
            $summary = get_cart_summary();

            if ($code === 'WELCOME20') {
                $_SESSION['coupon_discount'] = round($summary['subtotal'] * 0.20, 2);
                $_SESSION['applied_coupon'] = 'WELCOME20 (20% Off)';
            } elseif ($code === 'SAVE10') {
                $_SESSION['coupon_discount'] = min($summary['subtotal'], 10.00);
                $_SESSION['applied_coupon'] = 'SAVE10 ($10 Off)';
            } elseif ($code === 'FREESHIP') {
                $_SESSION['coupon_discount'] = 9.99;
                $_SESSION['applied_coupon'] = 'FREESHIP (Free Standard Shipping)';
            } else {
                json_response(['error' => 'Invalid promo code. Try WELCOME20 or SAVE10'], 400);
            }

            json_response([
                'success' => true,
                'message' => 'Coupon code applied!',
                'coupon' => $_SESSION['applied_coupon'],
                'cart' => get_cart_summary()
            ]);
            break;

        case 'clear':
            $_SESSION['cart'] = [];
            unset($_SESSION['coupon_discount'], $_SESSION['applied_coupon']);
            json_response([
                'success' => true,
                'cart' => get_cart_summary()
            ]);
            break;

        default:
            json_response(['error' => 'Unknown action'], 400);
    }

} catch (Exception $e) {
    json_response(['error' => 'Cart error: ' . $e->getMessage()], 500);
}
