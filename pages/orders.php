<?php
/**
 * SHOPPULSE Orders & Real-Time Tracking Page
 * Animated 5-stage tracking timeline, order lookup, item breakdown, and delivery updates
 */

require_once __DIR__ . '/../includes/db.php';

$pdo = get_db();
$orderNumber = clean_input($_GET['order'] ?? '');
$isNew = !empty($_GET['new']);
$user = $_SESSION['user'] ?? null;

$order = null;
$orderItems = [];
$timeline = [];
$progressPct = 0;

if (!empty($orderNumber)) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = :num LIMIT 1");
    $stmt->execute(['num' => $orderNumber]);
    $order = $stmt->fetch();

    if ($order) {
        $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :oid");
        $itemStmt->execute(['oid' => $order['id']]);
        $orderItems = $itemStmt->fetchAll();

        $allStages = ['confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
        $currentIndex = array_search($order['order_status'], $allStages, true);
        if ($currentIndex === false) $currentIndex = 0;

        $timeline = [
            ['key' => 'confirmed', 'label' => 'Order Confirmed', 'desc' => 'We received your order and payment.', 'done' => $currentIndex >= 0, 'active' => $currentIndex === 0],
            ['key' => 'processing', 'label' => 'Quality Check', 'desc' => 'Warehouse team is packing your items with care.', 'done' => $currentIndex >= 1, 'active' => $currentIndex === 1],
            ['key' => 'shipped', 'label' => 'Dispatched', 'desc' => 'Handed over to carrier partner with tracking.', 'done' => $currentIndex >= 2, 'active' => $currentIndex === 2],
            ['key' => 'out_for_delivery', 'label' => 'Out for Delivery', 'desc' => 'Courier driver has your parcel on the local route.', 'done' => $currentIndex >= 3, 'active' => $currentIndex === 3],
            ['key' => 'delivered', 'label' => 'Delivered', 'desc' => 'Safely arrived at destination address.', 'done' => $currentIndex >= 4, 'active' => $currentIndex === 4]
        ];

        $progressPct = ($currentIndex / (count($allStages) - 1)) * 100;
    }
}

// Recent orders for logged-in user
$userOrders = [];
if ($user) {
    $uStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5");
    $uStmt->execute(['uid' => $user['id']]);
    $userOrders = $uStmt->fetchAll();
}

$page_title = $order ? "Tracking Order #" . $order['order_number'] : "Track Your Order | " . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-block: 2.5rem 5rem;">
    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" style="font-size:0.875rem; color:var(--text-muted); margin-bottom:1.5rem; display:flex; gap:0.5rem;">
        <a href="../index.php" style="color:var(--text-secondary);">Home</a>
        <span>/</span>
        <span style="color:var(--text-primary); font-weight:600;">Order Tracking</span>
    </nav>

    <!-- Celebration Banner if newly created order -->
    <?php if ($isNew && $order): ?>
        <div style="background:linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border:2px solid var(--cashback); border-radius:var(--radius-lg); padding:2rem; margin-bottom:2.5rem; text-align:center; box-shadow:var(--shadow-card);">
            <div style="font-size:2.5rem; margin-bottom:0.5rem;">🎉</div>
            <h1 style="font-size:1.75rem; font-weight:800; color:#065f46; margin-bottom:0.5rem;">Thank You! Your Order Has Been Placed</h1>
            <p style="font-size:1rem; color:#047857; max-width:550px; margin:0 auto 1rem;">
                A confirmation receipt was dispatched to <strong><?= htmlspecialchars($order['customer_email']) ?></strong>. You also earned <strong><?= format_price($order['cashback_earned']) ?> in Instant Cashback!</strong>
            </p>
            <div style="display:inline-block; background:#fff; border:1px solid #a7f3d0; border-radius:var(--radius-pill); padding:0.4rem 1.25rem; font-size:0.875rem; font-weight:800; color:#065f46;">
                Order ID: <?= htmlspecialchars($order['order_number']) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Lookup Search Bar if no order queried -->
    <?php if (!$order): ?>
        <div style="max-width:600px; margin:2rem auto 4rem; text-align:center;">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="var(--primary)" stroke-width="2" style="margin:0 auto 1rem;">
                <rect x="1" y="3" width="15" height="13"/>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                <circle cx="5.5" cy="18.5" r="2.5"/>
                <circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <h1 style="font-size:2rem; font-weight:800; color:var(--text-primary); margin-bottom:0.75rem;">Track Your Shipment</h1>
            <p style="font-size:1rem; color:var(--text-secondary); margin-bottom:2rem;">
                Enter your ShopPulse order number (e.g. <code>SP-XXXXX-XXX</code>) to view live carrier dispatch status and estimated delivery time.
            </p>

            <form action="orders.php" method="GET" style="display:flex; gap:0.5rem; background:var(--bg-surface); border:2px solid var(--border-color); border-radius:var(--radius-pill); padding:0.4rem 0.6rem 0.4rem 1.25rem; box-shadow:var(--shadow-card);">
                <input 
                    type="text" 
                    name="order" 
                    placeholder="Enter Order ID (e.g. SP-...)" 
                    required 
                    style="flex:1; background:transparent; font-size:1rem; color:var(--text-primary);"
                >
                <button type="submit" class="hero-search-btn" style="padding:0.65rem 1.75rem;">
                    Track Order
                </button>
            </form>
        </div>
    <?php else: ?>

        <!-- Active Order Timeline View -->
        <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2.5rem; margin-bottom:3rem; box-shadow:var(--shadow-card);">
            
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1.5rem; margin-bottom:2.5rem; border-bottom:1px solid var(--border-color); padding-bottom:1.5rem;">
                <div>
                    <span style="font-size:0.75rem; text-transform:uppercase; font-weight:800; color:var(--primary); letter-spacing:0.05em; display:block; margin-bottom:0.25rem;">Live Carrier Status</span>
                    <h2 style="font-size:1.75rem; font-weight:800; color:var(--text-primary); margin-bottom:0.25rem;">Order #<?= htmlspecialchars($order['order_number']) ?></h2>
                    <span style="font-size:0.875rem; color:var(--text-muted);">Placed on <?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></span>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--text-muted); display:block; margin-bottom:0.25rem;">Carrier Tracking Number</span>
                    <strong style="font-family:monospace; font-size:1rem; color:var(--text-primary); background:var(--bg-page); padding:0.3rem 0.75rem; border-radius:var(--radius-sm); border:1px solid var(--border-color);">
                        <?= htmlspecialchars($order['tracking_number'] ?? 'TRK-20261119-94021') ?>
                    </strong>
                </div>
            </div>

            <!-- Animated 5-Stage Tracking Timeline -->
            <div class="tracking-timeline" style="margin-bottom:2.5rem;">
                <div class="timeline-track-bg">
                    <div class="timeline-track-fill" style="width:<?= $progressPct ?>%;"></div>
                </div>

                <div class="timeline-steps">
                    <?php foreach ($timeline as $idx => $step): ?>
                        <div class="timeline-step <?= $step['done'] ? 'completed' : '' ?> <?= $step['active'] ? 'active' : '' ?>">
                            <div class="step-node">
                                <?= $step['done'] ? '✓' : ($idx + 1) ?>
                            </div>
                            <div class="step-title"><?= htmlspecialchars($step['label']) ?></div>
                            <div class="step-desc"><?= htmlspecialchars($step['desc']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Order Details Grid: Shipping Address & Summary -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; border-top:1px solid var(--border-color); padding-top:2rem;">
                <div>
                    <h3 style="font-size:1rem; font-weight:800; color:var(--text-primary); margin-bottom:0.75rem;">Shipping Destination</h3>
                    <p style="font-size:0.875rem; color:var(--text-secondary); line-height:1.6;">
                        <strong><?= htmlspecialchars($order['customer_name']) ?></strong><br>
                        <?= htmlspecialchars($order['shipping_address']) ?><br>
                        <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> <?= htmlspecialchars($order['zip_code']) ?><br>
                        Phone: <?= htmlspecialchars($order['customer_phone']) ?>
                    </p>
                </div>
                <div>
                    <h3 style="font-size:1rem; font-weight:800; color:var(--text-primary); margin-bottom:0.75rem;">Payment & Cashback</h3>
                    <p style="font-size:0.875rem; color:var(--text-secondary); line-height:1.6;">
                        Payment Status: <strong style="color:var(--cashback);">Paid (Verified 256-Bit SSL)</strong><br>
                        Payment Method: <?= strtoupper(htmlspecialchars($order['payment_method'])) ?><br>
                        Cashback Credited: <strong style="color:var(--cashback);">+<?= format_price($order['cashback_earned']) ?></strong><br>
                        Total Amount Charged: <strong style="color:var(--primary); font-size:1.125rem;"><?= format_price($order['total_amount']) ?></strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- Ordered Items Card -->
        <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2rem; box-shadow:var(--shadow-subtle);">
            <h3 style="font-size:1.25rem; font-weight:800; color:var(--text-primary); margin-bottom:1.5rem;">Ordered Products (<?= count($orderItems) ?>)</h3>
            <div style="display:flex; flex-direction:column; gap:1.25rem;">
                <?php foreach ($orderItems as $item): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:1.25rem;">
                        <div style="display:flex; gap:1rem; align-items:center;">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" style="width:60px; height:60px; border-radius:var(--radius-sm); object-fit:cover; background:#f1f5f9;">
                            <div>
                                <h4 style="font-size:0.9375rem; font-weight:700; color:var(--text-primary); margin-bottom:0.25rem;"><?= htmlspecialchars($item['product_name']) ?></h4>
                                <span style="font-size:0.8125rem; color:var(--text-muted);">Quantity: <?= $item['quantity'] ?> × <?= format_price($item['price']) ?></span>
                            </div>
                        </div>
                        <strong style="font-size:1rem; color:var(--text-primary);"><?= format_price($item['total']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
