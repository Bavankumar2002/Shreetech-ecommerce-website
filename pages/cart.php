<?php
/**
 * SHOPPULSE Full Shopping Bag / Cart Page
 * Real-time quantity adjustments, coupon redemption, shipping threshold calculation, cashback summary
 */

require_once __DIR__ . '/../includes/db.php';

$cart = get_cart_summary();
$appliedCoupon = $_SESSION['applied_coupon'] ?? null;

$page_title = "Your Shopping Bag (" . $cart['total_items'] . " items)";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-block: 2.5rem 5rem;">
    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" style="font-size:0.875rem; color:var(--text-muted); margin-bottom:1.5rem; display:flex; gap:0.5rem;">
        <a href="../index.php" style="color:var(--text-secondary);">Home</a>
        <span>/</span>
        <span style="color:var(--text-primary); font-weight:600;">Shopping Bag</span>
    </nav>

    <h1 style="font-size:clamp(1.75rem, 3vw, 2.25rem); font-weight:800; color:var(--text-primary); margin-bottom:2rem; letter-spacing:-0.02em;">
        Shopping Bag <span style="font-size:1.125rem; font-weight:500; color:var(--text-muted);">(<?= $cart['total_items'] ?> items)</span>
    </h1>

    <?php if (empty($cart['items'])): ?>
        <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:5rem 2rem; text-align:center;">
            <svg viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 1.5rem; color:var(--text-muted);">
                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            <h2 style="font-size:1.5rem; font-weight:800; color:var(--text-primary); margin-bottom:0.5rem;">Your bag is currently empty</h2>
            <p style="font-size:1rem; color:var(--text-secondary); max-width:450px; margin:0 auto 2rem;">
                Browse our curated categories to discover industry-leading audio, titanium wearables, and laser cinema tech with instant cashback.
            </p>
            <a href="categories.php" class="card-add-btn" style="display:inline-flex; width:auto; padding:0.85rem 2.25rem; font-size:1rem;">
                Start Exploring Catalog →
            </a>
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns: 1fr 380px; gap:2.5rem; align-items:start;">
            
            <!-- Left Column: Item List & Shipping Status -->
            <div>
                <!-- Free Shipping Progress Tracker -->
                <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.25rem 1.75rem; margin-bottom:1.5rem; box-shadow:var(--shadow-subtle);">
                    <div style="display:flex; justify-content:space-between; font-size:0.875rem; margin-bottom:0.5rem;">
                        <span>
                            <?php if ($cart['free_shipping_eligible']): ?>
                                <strong>🎉 Congratulations! You unlocked Free Standard Shipping</strong>
                            <?php else: ?>
                                Add <strong>$<?= number_format($cart['amount_to_free_shipping'], 2) ?></strong> more to earn <strong>Free Standard Shipping</strong>
                            <?php endif; ?>
                        </span>
                        <strong style="color:var(--primary);"><?= $cart['free_shipping_eligible'] ? '100%' : round(($cart['subtotal'] / 99) * 100) . '%' ?></strong>
                    </div>
                    <div class="free-shipping-bar" style="height:8px;">
                        <div class="free-shipping-fill" style="width:<?= min(100, ($cart['subtotal'] / 99) * 100) ?>%;"></div>
                    </div>
                </div>

                <!-- Items Table / Container -->
                <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.75rem; box-shadow:var(--shadow-subtle);">
                    <?php foreach ($cart['items'] as $item): ?>
                        <div style="display:flex; gap:1.5rem; padding-bottom:1.5rem; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); align-items:center;">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width:90px; height:90px; border-radius:var(--radius-md); object-fit:cover; background:#f1f5f9;">
                            <div style="flex:1;">
                                <span style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--text-muted);"><?= htmlspecialchars($item['brand']) ?></span>
                                <h3 style="font-size:1.0625rem; font-weight:700; color:var(--text-primary); margin-bottom:0.35rem;">
                                    <a href="product.php?slug=<?= htmlspecialchars($item['slug']) ?>"><?= htmlspecialchars($item['name']) ?></a>
                                </h3>
                                <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.75rem;">
                                    <span style="font-size:1.125rem; font-weight:800; color:var(--primary);"><?= format_price($item['price']) ?></span>
                                    <span class="badge-cashback">+<?= number_format($item['cashback_pct'], 1) ?>% Cashback</span>
                                </div>
                                <div style="display:flex; align-items:center; gap:1.5rem;">
                                    <div class="cart-qty-ctrl">
                                        <button type="button" class="qty-btn" onclick="updateAndReload(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>)">−</button>
                                        <span><?= $item['quantity'] ?></span>
                                        <button type="button" class="qty-btn" onclick="updateAndReload(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>)">+</button>
                                    </div>
                                    <button type="button" onclick="removeAndReload(<?= $item['id'] ?>)" style="font-size:0.8125rem; font-weight:600; color:var(--text-muted); text-decoration:underline;">
                                        Remove
                                    </button>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <span style="font-size:1.125rem; font-weight:800; color:var(--text-primary);">
                                    <?= format_price($item['price'] * $item['quantity']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <a href="categories.php" style="font-size:0.875rem; font-weight:700; color:var(--primary); display:inline-flex; align-items:center; gap:0.35rem;">
                            ← Continue Shopping
                        </a>
                        <button type="button" onclick="clearCartAndReload()" style="font-size:0.8125rem; font-weight:600; color:var(--accent);">
                            Empty Bag
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Order Summary & Promo Code -->
            <aside style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2rem; box-shadow:var(--shadow-card); position:sticky; top:100px;">
                <h2 style="font-size:1.25rem; font-weight:800; color:var(--text-primary); margin-bottom:1.5rem;">Order Summary</h2>

                <!-- Promo Code Form -->
                <form id="cart-coupon-form" onsubmit="applyCouponCode(event)" style="margin-bottom:1.5rem;">
                    <label for="coupon-code-input" style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">
                        Have a Promo Code?
                    </label>
                    <div style="display:flex; gap:0.5rem;">
                        <input 
                            type="text" 
                            id="coupon-code-input" 
                            placeholder="e.g. WELCOME20" 
                            value="<?= $appliedCoupon ? htmlspecialchars(explode(' ', $appliedCoupon)[0]) : '' ?>"
                            style="flex:1; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-pill); padding:0.5rem 1rem; font-size:0.875rem; text-transform:uppercase;"
                        >
                        <button type="submit" style="background:var(--primary); color:#fff; font-weight:700; font-size:0.8125rem; padding:0.5rem 1.25rem; border-radius:var(--radius-pill);">
                            Apply
                        </button>
                    </div>
                    <?php if ($appliedCoupon): ?>
                        <div style="font-size:0.75rem; color:var(--cashback); font-weight:700; margin-top:0.4rem;">
                            ✓ Applied: <?= htmlspecialchars($appliedCoupon) ?>
                        </div>
                    <?php endif; ?>
                </form>

                <!-- Cashback Box -->
                <div class="cart-cashback-badge" style="margin-bottom:1.5rem;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                    <span>Estimated Cashback: <strong><?= format_price($cart['cashback_total']) ?></strong></span>
                </div>

                <!-- Lines -->
                <div style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1.5rem;">
                    <div class="cart-summary-line">
                        <span>Items Subtotal</span>
                        <strong><?= format_price($cart['subtotal']) ?></strong>
                    </div>

                    <?php if ($cart['discount'] > 0): ?>
                        <div class="cart-summary-line" style="color:var(--cashback);">
                            <span>Promo Discount</span>
                            <strong>−<?= format_price($cart['discount']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <div class="cart-summary-line">
                        <span>Estimated Shipping</span>
                        <strong><?= $cart['shipping'] == 0 ? '<span style="color:var(--cashback);">FREE</span>' : format_price($cart['shipping']) ?></strong>
                    </div>

                    <div class="cart-summary-line">
                        <span>Estimated Sales Tax</span>
                        <strong><?= format_price($cart['tax']) ?></strong>
                    </div>

                    <div class="cart-summary-line total" style="padding-top:1rem;">
                        <span>Estimated Total</span>
                        <strong style="color:var(--primary); font-size:1.35rem;"><?= format_price($cart['total']) ?></strong>
                    </div>
                </div>

                <a href="checkout.php" class="checkout-btn magnetic-btn" style="padding:1rem; font-size:1.0625rem;">
                    Proceed to Secure Checkout →
                </a>

                <div style="text-align:center; font-size:0.75rem; color:var(--text-muted); margin-top:1rem;">
                    🔒 Guaranteed Safe & Secure 256-Bit SSL Checkout
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>

<script>
async function updateAndReload(id, qty) {
    await fetch('../api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update', product_id: id, quantity: qty })
    });
    window.location.reload();
}

async function removeAndReload(id) {
    await fetch('../api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'remove', product_id: id })
    });
    window.location.reload();
}

async function clearCartAndReload() {
    await fetch('../api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'clear' })
    });
    window.location.reload();
}

async function applyCouponCode(e) {
    e.preventDefault();
    const code = document.getElementById('coupon-code-input').value.trim();
    if (!code) return;

    try {
        const res = await fetch('../api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'coupon', code: code })
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Invalid coupon code');
        }
    } catch (err) {
        console.error(err);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
