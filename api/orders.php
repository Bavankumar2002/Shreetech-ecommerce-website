<?php
/**
 * SHOPPULSE Orders & Tracking API
 * Order placement, tracking timeline calculation, and order history
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = get_db();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $action = $input['action'] ?? ($_GET['action'] ?? 'track');

    switch ($action) {
        case 'create':
            $cart = get_cart_summary();
            if (empty($cart['items'])) {
                json_response(['error' => 'Your cart is empty. Cannot place an order.'], 400);
            }

            // Input validation
            $name = clean_input($input['name'] ?? '');
            $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $phone = clean_input($input['phone'] ?? '');
            $address = clean_input($input['address'] ?? '');
            $city = clean_input($input['city'] ?? '');
            $state = clean_input($input['state'] ?? '');
            $zip = clean_input($input['zip'] ?? '');
            $paymentMethod = clean_input($input['payment_method'] ?? 'credit_card');

            if (empty($name) || !$email || empty($address) || empty($city) || empty($zip)) {
                json_response(['error' => 'Please fill in all required shipping address fields.'], 400);
            }

            $orderNumber = 'SP-' . strtoupper(substr(uniqid(), -6)) . '-' . mt_rand(100, 999);
            $trackingNumber = 'TRK-' . date('Ymd') . '-' . mt_rand(100000, 999999);
            $userId = $_SESSION['user']['id'] ?? null;

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    order_number, user_id, customer_name, customer_email, customer_phone,
                    shipping_address, city, state, zip_code, subtotal, discount_amount,
                    shipping_fee, tax_amount, total_amount, cashback_earned,
                    payment_method, payment_status, order_status, tracking_number
                ) VALUES (
                    :order_number, :user_id, :customer_name, :customer_email, :customer_phone,
                    :shipping_address, :city, :state, :zip_code, :subtotal, :discount_amount,
                    :shipping_fee, :tax_amount, :total_amount, :cashback_earned,
                    :payment_method, 'paid', 'confirmed', :tracking_number
                )
            ");

            $stmt->execute([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'shipping_address' => $address,
                'city' => $city,
                'state' => $state,
                'zip_code' => $zip,
                'subtotal' => $cart['subtotal'],
                'discount_amount' => $cart['discount'],
                'shipping_fee' => $cart['shipping'],
                'tax_amount' => $cart['tax'],
                'total_amount' => $cart['total'],
                'cashback_earned' => $cart['cashback_total'],
                'payment_method' => $paymentMethod,
                'tracking_number' => $trackingNumber
            ]);

            $orderId = (int)$pdo->lastInsertId();

            // Insert items
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, price, quantity, image_url, total)
                VALUES (:order_id, :product_id, :product_name, :price, :quantity, :image_url, :total)
            ");

            foreach ($cart['items'] as $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['id'],
                    'product_name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'image_url' => $item['image_url'],
                    'total' => round($item['price'] * $item['quantity'], 2)
                ]);

                // Reduce stock
                $stockStmt = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - :qty) WHERE id = :pid");
                $stockStmt->execute(['qty' => $item['quantity'], 'pid' => $item['id']]);
            }

            $pdo->commit();

            // Clear session cart
            $_SESSION['cart'] = [];
            unset($_SESSION['coupon_discount'], $_SESSION['applied_coupon']);

            json_response([
                'success' => true,
                'order_number' => $orderNumber,
                'tracking_number' => $trackingNumber,
                'total' => $cart['total'],
                'cashback' => $cart['cashback_total'],
                'redirect' => "pages/orders.php?order=" . urlencode($orderNumber)
            ], 201);
            break;

        case 'track':
            $orderNumber = clean_input($input['order_number'] ?? ($_GET['order'] ?? ''));
            if (empty($orderNumber)) {
                json_response(['error' => 'Order number is required'], 400);
            }

            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = :num LIMIT 1");
            $stmt->execute(['num' => $orderNumber]);
            $order = $stmt->fetch();

            if (!$order) {
                json_response(['error' => 'Order not found. Please verify the order number.'], 404);
            }

            // Fetch order items
            $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :oid");
            $itemStmt->execute(['oid' => $order['id']]);
            $order['items'] = $itemStmt->fetchAll();

            // Timeline steps with progress
            $allStages = ['confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
            $currentIndex = array_search($order['order_status'], $allStages, true);
            if ($currentIndex === false) {
                $currentIndex = 0;
            }

            $timeline = [
                ['key' => 'confirmed', 'label' => 'Order Confirmed', 'desc' => 'We received your order and payment.', 'done' => $currentIndex >= 0, 'active' => $currentIndex === 0],
                ['key' => 'processing', 'label' => 'Packaging & Quality Check', 'desc' => 'Warehouse team is packing your items with care.', 'done' => $currentIndex >= 1, 'active' => $currentIndex === 1],
                ['key' => 'shipped', 'label' => 'Dispatched / In Transit', 'desc' => 'Handed over to carrier partner with tracking.', 'done' => $currentIndex >= 2, 'active' => $currentIndex === 2],
                ['key' => 'out_for_delivery', 'label' => 'Out for Delivery', 'desc' => 'Courier driver has your parcel on the local route.', 'done' => $currentIndex >= 3, 'active' => $currentIndex === 3],
                ['key' => 'delivered', 'label' => 'Delivered', 'desc' => 'Safely arrived at your destination address.', 'done' => $currentIndex >= 4, 'active' => $currentIndex === 4]
            ];

            json_response([
                'order' => $order,
                'timeline' => $timeline,
                'progress_pct' => ($currentIndex / (count($allStages) - 1)) * 100
            ]);
            break;

        case 'list':
            $userId = $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                json_response(['orders' => []]);
            }

            $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC");
            $stmt->execute(['uid' => $userId]);
            $orders = $stmt->fetchAll();

            json_response(['orders' => $orders]);
            break;

        default:
            json_response(['error' => 'Invalid action'], 400);
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => 'Order error: ' . $e->getMessage()], 500);
}
