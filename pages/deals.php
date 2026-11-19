<?php
/**
 * SHOPPULSE Deals & Cashback Hub Page
 * Real-time countdown timers, highest savings filters, verified cashback multipliers
 */

require_once __DIR__ . '/../includes/db.php';

$pdo = get_db();

// Filter deal products
$filter = clean_input($_GET['filter'] ?? 'all');

$where = ['(p.is_deal = 1 OR p.original_price > p.price)'];
if ($filter === 'under100') {
    $where[] = 'p.price < 100';
} elseif ($filter === 'high_cashback') {
    $where[] = 'p.cashback_pct >= 5.0';
}

$sql = "
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           (p.original_price - p.price) AS savings_amount,
           ROUND(((p.original_price - p.price) / p.original_price) * 100) AS discount_pct
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY savings_amount DESC, p.cashback_pct DESC
";

$stmt = $pdo->query($sql);
$dealProducts = $stmt->fetchAll();

$page_title = "Flash Deals & Cashback Events | " . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-block: 2rem 5rem;">
    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" style="font-size:0.875rem; color:var(--text-muted); margin-bottom:1.5rem; display:flex; gap:0.5rem;">
        <a href="../index.php" style="color:var(--text-secondary);">Home</a>
        <span>/</span>
        <span style="color:var(--text-primary); font-weight:600;">Flash Deals & Cashback</span>
    </nav>

    <!-- Supercharged Deals Countdown Hero Banner -->
    <div class="deals-banner-wrap" style="margin-top:0; margin-bottom:3rem;">
        <div class="deals-banner-bg-orb"></div>
        <div class="deals-content-flex">
            <div class="deals-banner-text">
                <span class="deals-tag">⚡ 24-Hour Flash Event</span>
                <h1 class="deals-title">Supercharged Deals + Up to 8% Instant Cashback</h1>
                <p class="deals-desc">
                    Special member-exclusive pricing on flagship noise-canceling audio, titanium wearables, and ultra-short-throw projectors.
                </p>

                <!-- Live Countdown -->
                <div class="countdown-grid deals-countdown">
                    <div class="countdown-box">
                        <span id="countdown-hours" class="countdown-num">23</span>
                        <span class="countdown-unit">Hours</span>
                    </div>
                    <div class="countdown-box">
                        <span id="countdown-minutes" class="countdown-num">44</span>
                        <span class="countdown-unit">Mins</span>
                    </div>
                    <div class="countdown-box">
                        <span id="countdown-seconds" class="countdown-num">18</span>
                        <span class="countdown-unit">Secs</span>
                    </div>
                </div>
            </div>

            <!-- Promo Codes Pill Matrix -->
            <div style="background:rgba(255,255,255,0.1); backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,0.2); border-radius:var(--radius-lg); padding:1.5rem; max-width:340px; width:100%;">
                <h3 style="font-size:1rem; font-weight:800; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem;">
                    <span>🎟️</span> Active Marketplace Coupons
                </h3>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <div style="background:rgba(0,0,0,0.2); padding:0.6rem 0.875rem; border-radius:var(--radius-md); display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="color:#f8fafc; font-size:0.875rem; font-family:monospace;">WELCOME20</strong>
                            <div style="font-size:0.6875rem; color:#cbd5e1;">20% off all orders</div>
                        </div>
                        <button type="button" onclick="navigator.clipboard.writeText('WELCOME20'); window.SHOPPULSE.showToast('Copied code WELCOME20', 'success');" style="background:#fff; color:var(--primary); font-size:0.75rem; font-weight:700; padding:0.25rem 0.6rem; border-radius:var(--radius-pill);">Copy</button>
                    </div>
                    <div style="background:rgba(0,0,0,0.2); padding:0.6rem 0.875rem; border-radius:var(--radius-md); display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="color:#f8fafc; font-size:0.875rem; font-family:monospace;">FREESHIP</strong>
                            <div style="font-size:0.6875rem; color:#cbd5e1;">Free standard delivery</div>
                        </div>
                        <button type="button" onclick="navigator.clipboard.writeText('FREESHIP'); window.SHOPPULSE.showToast('Copied code FREESHIP', 'success');" style="background:#fff; color:var(--primary); font-size:0.75rem; font-weight:700; padding:0.25rem 0.6rem; border-radius:var(--radius-pill);">Copy</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deal Filter Navigation -->
    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:2.5rem; overflow-x:auto; padding-bottom:0.5rem;">
        <a href="deals.php" class="cat-pill <?= $filter === 'all' ? 'active' : '' ?>">All Deals (<?= count($dealProducts) ?>)</a>
        <a href="deals.php?filter=high_cashback" class="cat-pill <?= $filter === 'high_cashback' ? 'active' : '' ?>">🔥 5%+ High Cashback</a>
        <a href="deals.php?filter=under100" class="cat-pill <?= $filter === 'under100' ? 'active' : '' ?>">Under $100 Steals</a>
    </div>

    <!-- Deals Grid -->
    <div class="product-grid">
        <?php foreach ($dealProducts as $index => $prod): ?>
            <article class="product-card tilt-card reveal-on-scroll stagger-<?= ($index % 3) + 1 ?>">
                <div class="product-card-top">
                    <!-- Badges -->
                    <div class="card-badges">
                        <?php if ($prod['discount_pct']): ?>
                            <span class="badge-tag" style="background:var(--accent);">Save <?= $prod['discount_pct'] ?>%</span>
                        <?php endif; ?>
                        <span class="badge-cashback">+<?= number_format($prod['cashback_pct'], 1) ?>% Cashback</span>
                    </div>

                    <a href="product.php?slug=<?= htmlspecialchars($prod['slug']) ?>">
                        <img src="<?= htmlspecialchars($prod['image_url']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="product-card-img" loading="lazy">
                    </a>
                </div>

                <div class="product-card-body">
                    <span class="product-card-brand"><?= htmlspecialchars($prod['brand']) ?></span>
                    <h3 class="product-card-title">
                        <a href="product.php?slug=<?= htmlspecialchars($prod['slug']) ?>">
                            <?= htmlspecialchars($prod['name']) ?>
                        </a>
                    </h3>

                    <div class="product-rating">
                        <div class="stars">★★★★★</div>
                        <span style="font-weight:700; color:var(--text-primary);"><?= number_format($prod['rating'], 1) ?></span>
                        <span class="rating-count">(<?= $prod['reviews_count'] ?>)</span>
                    </div>

                    <div class="product-price-row">
                        <span class="current-price"><?= format_price($prod['price']) ?></span>
                        <?php if ($prod['original_price']): ?>
                            <span class="original-price"><?= format_price($prod['original_price']) ?></span>
                        <?php endif; ?>
                    </div>

                    <button 
                        type="button" 
                        class="card-add-btn add-to-cart-trigger magnetic-btn" 
                        data-product-id="<?= $prod['id'] ?>" 
                        data-quantity="1"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>Claim Deal</span>
                    </button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
