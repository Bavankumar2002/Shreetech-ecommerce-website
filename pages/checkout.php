<?php
/**
 * SHOPPULSE Secure Checkout Page
 * Multi-step shipping address, carrier options, demo payment processing, CSRF protection
 */

require_once __DIR__ . '/../includes/db.php';

$cart = get_cart_summary();
$user = $_SESSION['user'] ?? null;

if (empty($cart['items'])) {
    header('Location: cart.php');
    exit;
}

$page_title = "Secure Checkout | " . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-block: 2.5rem 5rem;">
    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" style="font-size:0.875rem; color:var(--text-muted); margin-bottom:1.5rem; display:flex; gap:0.5rem;">
        <a href="../index.php" style="color:var(--text-secondary);">Home</a>
        <span>/</span>
        <a href="cart.php" style="color:var(--text-secondary);">Shopping Bag</a>
        <span>/</span>
        <span style="color:var(--text-primary); font-weight:600;">Checkout</span>
    </nav>

    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:2rem;">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="var(--primary)" stroke-width="2.5">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <h1 style="font-size:clamp(1.75rem, 3vw, 2.25rem); font-weight:800; color:var(--text-primary); letter-spacing:-0.02em;">
            Secure OneCart Checkout
        </h1>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 400px; gap:3rem; align-items:start;">
        
        <!-- Left Column: Checkout Form -->
        <div>
            <form id="checkout-form" onsubmit="handlePlaceOrder(event)">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <!-- Step 1: Customer Contact & Shipping Address -->
                <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2rem; margin-bottom:2rem; box-shadow:var(--shadow-subtle);">
                    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.5rem;">
                        <span style="width:28px; height:28px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.875rem;">1</span>
                        <h2 style="font-size:1.25rem; font-weight:800; color:var(--text-primary);">Shipping & Contact Details</h2>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem; margin-bottom:1.25rem;">
                        <div>
                            <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Full Name *</label>
                            <input type="text" name="name" required value="<?= $user ? htmlspecialchars($user['name']) : 'Alex Mercer' ?>" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; color:var(--text-primary);">
                        </div>
                        <div>
                            <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Email Address *</label>
                            <input type="email" name="email" required value="<?= $user ? htmlspecialchars($user['email']) : 'alex.mercer@example.com' ?>" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; color:var(--text-primary);">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem; margin-bottom:1.25rem;">
                        <div>
                            <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Mobile Phone *</label>
                            <input type="tel" name="phone" required value="+1 (555) 382-9104" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; color:var(--text-primary);">
                        </div>
                        <div>
                            <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Street Address *</label>
                            <input type="text" name="address" required value="742 Evergreen Terrace, Suite 4B" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; color:var(--text-primary);">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:1.25rem;">
                        <div>
                            <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">City *</label>
                            <input type="text" name="city" required value="San Francisco" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; color:var(--text-primary);">
                        </div>
                        <div>
                            <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">State *</label>
                            <input type="text" name="state" required value="CA" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; color:var(--text-primary);">
                        </div>
                        <div>
                            <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Postal / ZIP *</label>
                            <input type="text" name="zip" required value="94102" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; color:var(--text-primary);">
                        </div>
                    </div>
                </div>

                <!-- Step 2: Shipping Method -->
                <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2rem; margin-bottom:2rem; box-shadow:var(--shadow-subtle);">
                    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.5rem;">
                        <span style="width:28px; height:28px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.875rem;">2</span>
                        <h2 style="font-size:1.25rem; font-weight:800; color:var(--text-primary);">Delivery Options</h2>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:1rem;">
                        <label style="display:flex; align-items:center; justify-content:space-between; border:2px solid var(--primary); background:var(--primary-light); padding:1rem 1.25rem; border-radius:var(--radius-md); cursor:pointer;">
                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                <input type="radio" name="shipping_method" value="standard" checked style="accent-color:var(--primary);">
                                <div>
                                    <strong style="color:var(--text-primary); display:block; font-size:0.9375rem;">Standard Carbon-Neutral Delivery</strong>
                                    <span style="font-size:0.8125rem; color:var(--text-secondary);">Estimated arrival in 3-5 business days</span>
                                </div>
                            </div>
                            <strong style="color:var(--primary);"><?= $cart['shipping'] == 0 ? 'FREE' : format_price($cart['shipping']) ?></strong>
                        </label>

                        <label style="display:flex; align-items:center; justify-content:space-between; border:1px solid var(--border-color); background:var(--bg-surface); padding:1rem 1.25rem; border-radius:var(--radius-md); cursor:pointer;">
                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                <input type="radio" name="shipping_method" value="express" style="accent-color:var(--primary);">
                                <div>
                                    <strong style="color:var(--text-primary); display:block; font-size:0.9375rem;">Priority Overnight Air Express</strong>
                                    <span style="font-size:0.8125rem; color:var(--text-secondary);">Guaranteed delivery by 10:30 AM tomorrow</span>
                                </div>
                            </div>
                            <strong style="color:var(--text-primary);">$19.99</strong>
                        </label>
                    </div>
                </div>

                <!-- Step 3: Payment Details (Demo sandbox) -->
                <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2rem; margin-bottom:2rem; box-shadow:var(--shadow-subtle);">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <span style="width:28px; height:28px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.875rem;">3</span>
                            <h2 style="font-size:1.25rem; font-weight:800; color:var(--text-primary);">Payment Information</h2>
                        </div>
                        <span style="font-size:0.75rem; background:var(--primary-light); color:var(--primary); font-weight:700; padding:0.2rem 0.6rem; border-radius:var(--radius-pill);">Demo Sandbox Mode</span>
                    </div>

                    <div style="margin-bottom:1.25rem;">
                        <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Card Number</label>
                        <div style="position:relative;">
                            <input type="text" value="4532 •••• •••• 8912" required style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; font-family:monospace; font-size:1rem; color:var(--text-primary);">
                            <span style="position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:0.75rem; font-weight:800; color:var(--primary);">VISA</span>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem;">
                        <div>
                            <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Expiration Date</label>
                            <input type="text" value="12/28" required style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; text-align:center; color:var(--text-primary);">
                        </div>
                        <div>
                            <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Security CVV</label>
                            <input type="password" value="842" required maxlength="4" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; text-align:center; color:var(--text-primary);">
                        </div>
                    </div>
                </div>

                <button 
                    type="submit" 
                    id="submit-order-btn"
                    class="checkout-btn magnetic-btn" 
                    style="padding:1.15rem; font-size:1.125rem;"
                >
                    Authorize & Complete Order (<?= format_price($cart['total']) ?>)
                </button>
            </form>
        </div>

        <!-- Right Column: Order Review Summary -->
        <aside style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2rem; box-shadow:var(--shadow-card); position:sticky; top:100px;">
            <h2 style="font-size:1.25rem; font-weight:800; color:var(--text-primary); margin-bottom:1.5rem;">Review Items (<?= $cart['total_items'] ?>)</h2>

            <div style="display:flex; flex-direction:column; gap:1rem; max-height:280px; overflow-y:auto; margin-bottom:1.5rem; padding-right:0.5rem;">
                <?php foreach ($cart['items'] as $item): ?>
                    <div style="display:flex; gap:1rem; align-items:center;">
                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width:54px; height:54px; border-radius:var(--radius-sm); object-fit:cover; background:#f1f5f9;">
                        <div style="flex:1;">
                            <div style="font-size:0.875rem; font-weight:700; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:200px;"><?= htmlspecialchars($item['name']) ?></div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Qty: <?= $item['quantity'] ?> × <?= format_price($item['price']) ?></div>
                        </div>
                        <strong style="font-size:0.875rem; color:var(--text-primary);"><?= format_price($item['price'] * $item['quantity']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Cashback callout -->
            <div class="cart-cashback-badge" style="margin-bottom:1.5rem;">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span>Cashback to be credited: <strong><?= format_price($cart['cashback_total']) ?></strong></span>
            </div>

            <div style="display:flex; flex-direction:column; gap:0.75rem; border-top:1px solid var(--border-color); padding-top:1.25rem;">
                <div class="cart-summary-line">
                    <span>Subtotal</span>
                    <strong><?= format_price($cart['subtotal']) ?></strong>
                </div>
                <?php if ($cart['discount'] > 0): ?>
                    <div class="cart-summary-line" style="color:var(--cashback);">
                        <span>Coupon Discount</span>
                        <strong>−<?= format_price($cart['discount']) ?></strong>
                    </div>
                <?php endif; ?>
                <div class="cart-summary-line">
                    <span>Shipping</span>
                    <strong><?= $cart['shipping'] == 0 ? '<span style="color:var(--cashback);">FREE</span>' : format_price($cart['shipping']) ?></strong>
                </div>
                <div class="cart-summary-line">
                    <span>Estimated Sales Tax</span>
                    <strong><?= format_price($cart['tax']) ?></strong>
                </div>
                <div class="cart-summary-line total">
                    <span>Final Amount</span>
                    <strong style="color:var(--primary); font-size:1.35rem;"><?= format_price($cart['total']) ?></strong>
                </div>
            </div>
        </aside>

    </div>
</div>

<script>
async function handlePlaceOrder(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-order-btn');
    btn.disabled = true;
    btn.innerHTML = 'Processing Order & Generating Tracking...';

    const form = document.getElementById('checkout-form');
    const formData = new FormData(form);
    const payload = {
        action: 'create',
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        address: formData.get('address'),
        city: formData.get('city'),
        state: formData.get('state'),
        zip: formData.get('zip'),
        payment_method: 'credit_card'
    };

    try {
        const res = await fetch('../api/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success && data.order_number) {
            window.location.href = `orders.php?order=${encodeURIComponent(data.order_number)}&new=1`;
        } else {
            alert(data.error || 'Failed to place order.');
            btn.disabled = false;
            btn.innerHTML = 'Authorize & Complete Order';
        }
    } catch (err) {
        console.error(err);
        alert('Server communication error.');
        btn.disabled = false;
        btn.innerHTML = 'Authorize & Complete Order';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
