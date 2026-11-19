<?php
/**
 * SHOPPULSE Modular Global Header
 * Responsive navigation, live search hero hook, cart drawer and dark mode
 */

require_once __DIR__ . '/db.php';

$base_url = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'pages') ? '../' : './';
$cart_summary = get_cart_summary();
$current_page = basename($_SERVER['SCRIPT_NAME']);
$current_user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' | ' . SITE_NAME : SITE_NAME . ' — ' . SITE_TAGLINE ?></title>
    <meta name="description" content="Shop the best in tech, audio, smart wearables, and designer lifestyle goods with guaranteed cashback and fast delivery.">

    <!-- Ensure default theme is Light Mode -->
    <script>
        (function() {
            var userExplicitChoice = localStorage.getItem('shoppulse_theme_custom');
            var theme = (userExplicitChoice === 'dark') ? 'dark' : 'light';
            localStorage.setItem('shoppulse_theme', theme);
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://images.unsplash.com">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/main.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/animations.css">

    <!-- Three.js (Pinned r128 CDN) & GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js" defer></script>

    <!-- Custom JavaScript Engine -->
    <script src="<?= $base_url ?>assets/js/main.js" defer></script>
</head>
<body>

    <!-- ====================================================================
         LEFT FLOATING SIDEBAR DOCK (Shop.app Signature Component)
         ==================================================================== -->
    <aside class="app-left-sidebar-dock" aria-label="Main Navigation Dock">
        <!-- 1. Top Brand Logo Icon -->
        <a href="<?= $base_url ?>index.php" class="dock-brand-logo" title="<?= SITE_NAME ?> — Home">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="8"/>
                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 14a4 4 0 1 1 4-4 4 4 0 0 1-4 4z"/>
            </svg>
        </a>

        <!-- 2. Middle Navigation Capsule -->
        <nav class="dock-pill-nav" aria-label="Primary destinations">
            <!-- Home -->
            <a href="<?= $base_url ?>index.php" class="dock-item <?= $current_page === 'index.php' ? 'active' : '' ?>" title="Home">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                </svg>
            </a>

            <!-- Departments / Categories (4 Squares Grid) -->
            <a href="<?= $base_url ?>pages/categories.php" class="dock-item <?= $current_page === 'categories.php' ? 'active' : '' ?>" title="Categories & Departments">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <rect x="3" y="3" width="7" height="7" rx="2"/>
                    <rect x="14" y="3" width="7" height="7" rx="2"/>
                    <rect x="14" y="14" width="7" height="7" rx="2"/>
                    <rect x="3" y="14" width="7" height="7" rx="2"/>
                </svg>
            </a>

            <!-- Shopping Cart / Bag with Live Badge -->
            <button type="button" class="dock-item open-cart-btn" id="header-cart-icon" title="Shopping Bag">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
                <span class="badge-count cart-badge-val"><?= $cart_summary['total_items'] ?></span>
            </button>

            <!-- Deals Tag -->
            <a href="<?= $base_url ?>pages/deals.php" class="dock-item <?= $current_page === 'deals.php' ? 'active' : '' ?>" title="Deals & Cashback">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>
                </svg>
            </a>

            <!-- Orders / Tracking Package -->
            <a href="<?= $base_url ?>pages/orders.php" class="dock-item <?= $current_page === 'orders.php' ? 'active' : '' ?>" title="Orders & Tracking">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V7h14v12zm-8-2h2v-4h3l-4-4-4 4h3z"/>
                </svg>
            </a>
        </nav>

        <!-- 3. Bottom User Capsule (Wishlist, Sign In, Theme) -->
        <div class="dock-pill-user">
            <!-- Favorites Heart -->
            <button type="button" class="dock-user-btn" onclick="window.SHOPPULSE.showToast('Your saved favorites', 'info');" title="Favorites">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
            </button>

            <!-- Sign In / Account -->
            <a href="<?= $base_url ?>pages/auth.php" class="dock-user-btn" title="Account Portal">
                <span class="dock-user-label"><?= $current_user ? 'Account' : 'Sign in' ?></span>
            </a>

            <!-- Theme Toggle -->
            <button type="button" class="theme-toggle-btn" aria-label="Toggle theme">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </button>
        </div>
    </aside>

    <!-- ====================================================================
         TOP HEADER: ONLY THE SEARCH BAR (Clean, Centered, Minimal)
         ==================================================================== -->
    <header class="top-search-header">
        <div class="top-search-inner">
            <form action="<?= $base_url ?>pages/categories.php" method="GET" class="top-search-box-pill" role="search">
                <svg class="search-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input 
                    type="search" 
                    name="search" 
                    class="live-search-input" 
                    data-dropdown-target="top-search-dropdown"
                    placeholder="Search products, brands, models..." 
                    autocomplete="off"
                    aria-label="Search catalog"
                >
                <button type="submit" class="search-btn" style="width:38px; height:38px; background:#5c24ff;" aria-label="Submit search">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
            </form>
            <div id="top-search-dropdown" class="search-dropdown"></div>
        </div>
    </header>

    <!-- Slide-in Mini Cart Drawer -->
    <div id="cart-drawer-backdrop" class="cart-drawer-backdrop"></div>
    <aside id="cart-drawer" class="cart-drawer" aria-label="Shopping Cart Drawer">
        <div class="cart-drawer-header">
            <h2 class="cart-drawer-title">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <span>Shopping Cart (<span class="cart-badge-val"><?= $cart_summary['total_items'] ?></span>)</span>
            </h2>
            <button type="button" class="close-drawer-btn close-cart-btn" aria-label="Close cart drawer">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div id="cart-drawer-items" class="cart-drawer-body">
            <!-- Dynamically populated via JS -->
        </div>

        <div class="cart-drawer-footer">
            <div class="free-shipping-progress">
                <div id="free-shipping-text" style="font-size:0.8125rem; color:var(--text-secondary); text-align:center;">
                    Add <strong>$99.00</strong> more for <strong>Free Shipping</strong>
                </div>
                <div class="free-shipping-bar">
                    <div id="free-shipping-fill" class="free-shipping-fill"></div>
                </div>
            </div>

            <div class="cart-cashback-badge">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span>Est. Cashback Earned: <strong id="cart-drawer-cashback">$0.00</strong></span>
            </div>

            <div class="cart-summary-line">
                <span>Subtotal</span>
                <strong id="cart-drawer-subtotal">$0.00</strong>
            </div>
            <div class="cart-summary-line total">
                <span>Estimated Total</span>
                <strong id="cart-drawer-total" style="color:var(--primary);">$0.00</strong>
            </div>

            <div style="margin-top:1.25rem;">
                <a href="<?= $base_url ?>pages/checkout.php" class="checkout-btn">Proceed to Checkout →</a>
                <a href="<?= $base_url ?>pages/cart.php" class="view-cart-link">View Full Shopping Bag</a>
            </div>
        </div>
    </aside>

    <main id="main-content">
