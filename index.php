<?php
/**
 * SHOPPULSE - Complete Shop.app (Shopify) Inspired Redesign
 * Story Bubbles, Dreamscape 3D Hero, Trending Brands with Dual Thumbs,
 * 2-Row Color Block Categories, Giant Typography Department Ticker,
 * Editorial Spotlight, "Discover something you love", and Floating Search Dock
 */

require_once __DIR__ . '/includes/db.php';

$pdo = get_db();

// 1. Fetch Categories
$catStmt = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.display_order ASC
");
$categories = $catStmt->fetchAll();

// 2. Fetch Products
$allProdStmt = $pdo->query("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.rating DESC, p.id ASC
");
$allProducts = $allProdStmt->fetchAll();

// Helper to find products by category or brand
function getProductsByCategory(array $prods, string $catSlug, int $limit = 6): array {
    $matches = [];
    foreach ($prods as $p) {
        if (($p['category_slug'] ?? '') === $catSlug) {
            $matches[] = $p;
            if (count($matches) >= $limit) break;
        }
    }
    return $matches;
}

function getProductsByBrand(array $prods, string $brand, int $limit = 2): array {
    $matches = [];
    foreach ($prods as $p) {
        if (strcasecmp($p['brand'], $brand) === 0) {
            $matches[] = $p;
            if (count($matches) >= $limit) break;
        }
    }
    return $matches;
}

$page_title = "Shop curated collections from top brands";
require_once __DIR__ . '/includes/header.php';
?>

<!-- ====================================================================
     1. TOP CIRCULAR MERCHANT / BRAND STORY BUBBLES STRIP (Shop.app Signature)
     ==================================================================== -->
<section class="story-bubbles-wrap" aria-label="Featured Merchant Stories">
    <div class="container">
        <div class="story-bubbles-scroll">
            <a href="pages/categories.php?brand=AuraSound" class="story-bubble-item">
                <div class="story-bubble-ring">
                    <div class="story-bubble-inner">
                        <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=150&auto=format&fit=crop&q=80" alt="AuraSound">
                    </div>
                </div>
                <span class="story-bubble-name">AuraSound</span>
            </a>

            <a href="pages/categories.php?brand=ChronosTech" class="story-bubble-item">
                <div class="story-bubble-ring">
                    <div class="story-bubble-inner">
                        <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=150&auto=format&fit=crop&q=80" alt="ChronosTech">
                    </div>
                </div>
                <span class="story-bubble-name">Chronos</span>
            </a>

            <a href="pages/categories.php?brand=PulseTech" class="story-bubble-item">
                <div class="story-bubble-ring">
                    <div class="story-bubble-inner">
                        <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=150&auto=format&fit=crop&q=80" alt="PulseTech">
                    </div>
                </div>
                <span class="story-bubble-name">PulseTech</span>
            </a>

            <a href="pages/categories.php?brand=LumenAudio" class="story-bubble-item">
                <div class="story-bubble-ring">
                    <div class="story-bubble-inner">
                        <img src="https://images.unsplash.com/photo-1545454675-3531b543be5d?w=150&auto=format&fit=crop&q=80" alt="LumenAudio">
                    </div>
                </div>
                <span class="story-bubble-name">Lumen</span>
            </a>

            <a href="pages/categories.php?brand=Kovert" class="story-bubble-item">
                <div class="story-bubble-ring">
                    <div class="story-bubble-inner">
                        <img src="https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=150&auto=format&fit=crop&q=80" alt="Kovert">
                    </div>
                </div>
                <span class="story-bubble-name">Kovert</span>
            </a>

            <a href="pages/categories.php?brand=PureAura" class="story-bubble-item">
                <div class="story-bubble-ring">
                    <div class="story-bubble-inner">
                        <img src="https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=150&auto=format&fit=crop&q=80" alt="PureAura">
                    </div>
                </div>
                <span class="story-bubble-name">PureAura</span>
            </a>

            <a href="pages/categories.php?brand=Solaria" class="story-bubble-item">
                <div class="story-bubble-ring">
                    <div class="story-bubble-inner">
                        <img src="https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=150&auto=format&fit=crop&q=80" alt="Solaria">
                    </div>
                </div>
                <span class="story-bubble-name">Solaria Lux</span>
            </a>

            <a href="pages/deals.php" class="story-bubble-item">
                <div class="story-bubble-ring" style="background:linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);">
                    <div class="story-bubble-inner" style="background:var(--accent); color:#fff; font-size:1.25rem; font-weight:800;">
                        %
                    </div>
                </div>
                <span class="story-bubble-name">Hot Deals</span>
            </a>
        </div>
    </div>
</section>

<!-- ====================================================================
     2. DREAMSCAPE 3D HERO BANNER ("Find the perfect gifts")
     ==================================================================== -->
<section class="shop-hero-container">
    <div class="container">
        <div class="shop-dreamscape-banner reveal-on-scroll">
            <!-- Three.js 3D WebGL Canvas Layer -->
            <div id="hero-canvas-wrap" class="shop-banner-3d-canvas" aria-hidden="true"></div>

            <div class="shop-banner-content">
                <span class="shop-banner-pill-tag">✦ Holiday & Seasonal Edit</span>
                <h1 class="shop-banner-title">Find the perfect gifts</h1>
                <p class="shop-banner-desc">
                    Shop curated gift ideas, studio audio, titanium wearables & lifestyle designs from independent creators.
                </p>

                <div class="shop-banner-btn-group">
                    <a href="pages/categories.php" class="shop-btn-primary magnetic-btn">
                        <span>Shop now</span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                    <a href="pages/deals.php" class="shop-btn-glass">
                        <span>Explore Deals</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====================================================================
     3. "Trending brands" CAROUSEL (WITH DUAL MINI PRODUCT THUMBNAILS)
     ==================================================================== -->
<section class="section" style="padding-top:1.5rem;">
    <div class="container">
        <div class="section-header reveal-on-scroll">
            <h2 class="section-title">Trending brands</h2>
            <a href="pages/categories.php" class="view-all-link">
                <span>View all</span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>

        <div class="trending-brands-grid">
            <!-- Brand 1: AuraSound -->
            <div class="brand-hero-card tilt-card reveal-on-scroll stagger-1">
                <img src="https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=600&auto=format&fit=crop&q=80" alt="AuraSound" class="brand-hero-bg">
                <div class="brand-hero-overlay"></div>
                <div class="brand-card-top-header">
                    <div class="brand-avatar-badge">AS</div>
                </div>
                <h3 class="brand-hero-center-name">AuraSound</h3>
                <div class="brand-mini-thumbs-row">
                    <a href="pages/product.php?slug=aura-anc-wireless-studio-headphones" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=200&auto=format&fit=crop&q=80" alt="Headphones" class="brand-thumb-img">
                        <span class="brand-thumb-title">Aura ANC</span>
                        <span class="brand-thumb-price">$249.99</span>
                    </a>
                    <a href="pages/product.php?slug=nova-soundtrue-true-wireless-earbuds" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=200&auto=format&fit=crop&q=80" alt="Earbuds" class="brand-thumb-img">
                        <span class="brand-thumb-title">Nova Buds</span>
                        <span class="brand-thumb-price">$129.99</span>
                    </a>
                </div>
            </div>

            <!-- Brand 2: ChronosTech -->
            <div class="brand-hero-card tilt-card reveal-on-scroll stagger-2">
                <img src="https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=600&auto=format&fit=crop&q=80" alt="ChronosTech" class="brand-hero-bg">
                <div class="brand-hero-overlay"></div>
                <div class="brand-card-top-header">
                    <div class="brand-avatar-badge">CT</div>
                </div>
                <h3 class="brand-hero-center-name">ChronosTech</h3>
                <div class="brand-mini-thumbs-row">
                    <a href="pages/product.php?slug=chronos-elite-titanium-smartwatch" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=200&auto=format&fit=crop&q=80" alt="Watch" class="brand-thumb-img">
                        <span class="brand-thumb-title">Elite Watch</span>
                        <span class="brand-thumb-price">$379.00</span>
                    </a>
                    <a href="pages/product.php?slug=apex-pro-smart-fitness-and-sleep-ring" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=200&auto=format&fit=crop&q=80" alt="Ring" class="brand-thumb-img">
                        <span class="brand-thumb-title">Apex Ring</span>
                        <span class="brand-thumb-price">$299.00</span>
                    </a>
                </div>
            </div>

            <!-- Brand 3: LumenAudio -->
            <div class="brand-hero-card tilt-card reveal-on-scroll stagger-3">
                <img src="https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=600&auto=format&fit=crop&q=80" alt="LumenAudio" class="brand-hero-bg">
                <div class="brand-hero-overlay"></div>
                <div class="brand-card-top-header">
                    <div class="brand-avatar-badge">LA</div>
                </div>
                <h3 class="brand-hero-center-name">LumenAudio</h3>
                <div class="brand-mini-thumbs-row">
                    <a href="pages/product.php?slug=orbital-smart-ambient-acoustic-speaker" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1545454675-3531b543be5d?w=200&auto=format&fit=crop&q=80" alt="Speaker" class="brand-thumb-img">
                        <span class="brand-thumb-title">Orbital</span>
                        <span class="brand-thumb-price">$189.50</span>
                    </a>
                    <a href="pages/product.php?slug=lumina-4k-uhd-ultra-short-throw-projector" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1593784991095-a205069470b6?w=200&auto=format&fit=crop&q=80" alt="Projector" class="brand-thumb-img">
                        <span class="brand-thumb-title">4K Laser</span>
                        <span class="brand-thumb-price">$1899.00</span>
                    </a>
                </div>
            </div>

            <!-- Brand 4: PureAura & Kovert -->
            <div class="brand-hero-card tilt-card reveal-on-scroll stagger-4">
                <img src="https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&auto=format&fit=crop&q=80" alt="Kovert Labs" class="brand-hero-bg">
                <div class="brand-hero-overlay"></div>
                <div class="brand-card-top-header">
                    <div class="brand-avatar-badge">KL</div>
                </div>
                <h3 class="brand-hero-center-name">Kovert Labs</h3>
                <div class="brand-mini-thumbs-row">
                    <a href="pages/product.php?slug=urban-minimalist-waterproof-rolltop-backpack" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=200&auto=format&fit=crop&q=80" alt="Rolltop" class="brand-thumb-img">
                        <span class="brand-thumb-title">Rolltop Bag</span>
                        <span class="brand-thumb-price">$119.00</span>
                    </a>
                    <a href="pages/product.php?slug=nordic-handcrafted-acetate-polarized-sunglasses" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=200&auto=format&fit=crop&q=80" alt="Solaria" class="brand-thumb-img">
                        <span class="brand-thumb-title">Solaria Lux</span>
                        <span class="brand-thumb-price">$145.00</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====================================================================
     4. "Categories" 2-ROW COLOR BLOCK TILES GRID (Shop.app Signature)
     ==================================================================== -->
<section class="section" style="padding-top:1rem;">
    <div class="container">
        <div class="section-header reveal-on-scroll">
            <h2 class="section-title">Categories</h2>
            <a href="pages/categories.php" class="view-all-link">
                <span>View all</span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>

        <div class="shop-color-categories-grid">
            <!-- Tile 1: Beauty (Rose Pink) -->
            <a href="pages/categories.php?category=beauty-wellness" class="shop-color-tile" style="background:#f43f5e;">
                <span class="color-tile-name">Beauty</span>
                <img src="https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=160&auto=format&fit=crop&q=80" alt="Beauty" class="color-tile-img">
            </a>

            <!-- Tile 2: Women / Style (Crimson Red) -->
            <a href="pages/categories.php?category=fashion-apparel" class="shop-color-tile" style="background:#dc2626;">
                <span class="color-tile-name">Women</span>
                <img src="https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=160&auto=format&fit=crop&q=80" alt="Women" class="color-tile-img">
            </a>

            <!-- Tile 3: Men / Apparel (Deep Violet) -->
            <a href="pages/categories.php?category=fashion-apparel" class="shop-color-tile" style="background:#4c1d95;">
                <span class="color-tile-name">Men</span>
                <img src="https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=160&auto=format&fit=crop&q=80" alt="Men" class="color-tile-img">
            </a>

            <!-- Tile 4: Home (Charcoal Slate) -->
            <a href="pages/categories.php?category=home-living" class="shop-color-tile" style="background:#334155;">
                <span class="color-tile-name">Home</span>
                <img src="https://images.unsplash.com/photo-1545454675-3531b543be5d?w=160&auto=format&fit=crop&q=80" alt="Home" class="color-tile-img">
            </a>

            <!-- Tile 5: Fitness & Wearables (Dark Graphite) -->
            <a href="pages/categories.php?category=wearables" class="shop-color-tile" style="background:#1e293b;">
                <span class="color-tile-name">Fitness</span>
                <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=160&auto=format&fit=crop&q=80" alt="Fitness" class="color-tile-img">
            </a>

            <!-- Row 2 -->
            <!-- Tile 6: Shoes & Sandals (Warm Terracotta) -->
            <a href="pages/categories.php?category=fashion-apparel" class="shop-color-tile" style="background:#c2410c;">
                <span class="color-tile-name">Shoes & sandals</span>
                <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=160&auto=format&fit=crop&q=80" alt="Shoes" class="color-tile-img">
            </a>

            <!-- Tile 7: Sporting Goods (Amber Orange) -->
            <a href="pages/categories.php?category=wearables" class="shop-color-tile" style="background:#ea580c;">
                <span class="color-tile-name">Sporting goods</span>
                <img src="https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=160&auto=format&fit=crop&q=80" alt="Sporting" class="color-tile-img">
            </a>

            <!-- Tile 8: Audio & Sound (Electric Indigo) -->
            <a href="pages/categories.php?category=audio-sound" class="shop-color-tile" style="background:#4338ca;">
                <span class="color-tile-name">Audio & Sound</span>
                <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=160&auto=format&fit=crop&q=80" alt="Audio" class="color-tile-img">
            </a>

            <!-- Tile 9: Electronics (Cobalt Blue) -->
            <a href="pages/categories.php?category=electronics" class="shop-color-tile" style="background:#2563eb;">
                <span class="color-tile-name">Workstations</span>
                <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=160&auto=format&fit=crop&q=80" alt="Laptops" class="color-tile-img">
            </a>

            <!-- Tile 10: Wellness & Living (Teal Green) -->
            <a href="pages/categories.php?category=beauty-wellness" class="shop-color-tile" style="background:#0d9488;">
                <span class="color-tile-name">Wellness</span>
                <img src="https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=160&auto=format&fit=crop&q=80" alt="Wellness" class="color-tile-img">
            </a>
        </div>
    </div>
</section>

<!-- ====================================================================
     5. GIANT TYPOGRAPHY DEPARTMENT TICKER + PRODUCT SHOWCASE
     ==================================================================== -->
<section class="section" style="padding-top:1.5rem; background-color:var(--bg-surface); border-block:1px solid var(--border-color);">
    <div class="container">
        <!-- Giant Ticker -->
        <div class="giant-dept-ticker">
            <span class="giant-dept-item">Audio</span>
            <span class="giant-dept-item active">
                <span>Beauty</span>
                <img src="https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=100&auto=format&fit=crop&q=80" alt="Cutout" class="giant-dept-cutout">
            </span>
            <span class="giant-dept-item">Women</span>
            <span class="giant-dept-item">Men</span>
            <span class="giant-dept-item">Wearables</span>
            <span class="giant-dept-item">Home</span>
        </div>

        <!-- Horizontal Product Cards Grid Under Ticker -->
        <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
            <?php 
            $beautyItems = getProductsByCategory($allProducts, 'beauty-wellness', 4);
            if (empty($beautyItems)) $beautyItems = array_slice($allProducts, 0, 4);
            foreach ($beautyItems as $prod): 
            ?>
                <article class="product-card tilt-card">
                    <div class="product-card-top" style="padding-top:100%; border-radius:20px;">
                        <span class="badge-cashback" style="position:absolute; top:12px; left:12px;">+<?= number_format($prod['cashback_pct'], 1) ?>% Cashback</span>
                        <a href="pages/product.php?slug=<?= htmlspecialchars($prod['slug']) ?>">
                            <img src="<?= htmlspecialchars($prod['image_url']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="product-card-img" loading="lazy">
                        </a>
                    </div>
                    <div class="product-card-body">
                        <span class="product-card-brand"><?= htmlspecialchars($prod['brand']) ?></span>
                        <h4 class="product-card-title">
                            <a href="pages/product.php?slug=<?= htmlspecialchars($prod['slug']) ?>"><?= htmlspecialchars($prod['name']) ?></a>
                        </h4>
                        <div class="product-price-row">
                            <span class="current-price"><?= format_price($prod['price']) ?></span>
                        </div>
                        <button type="button" class="card-add-btn add-to-cart-trigger magnetic-btn" data-product-id="<?= $prod['id'] ?>" data-quantity="1">
                            Add to Cart
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ====================================================================
     6. "Home >" CURATED SHOWCASE (Shop.app Section)
     ==================================================================== -->
<section class="section">
    <div class="container">
        <div class="section-header reveal-on-scroll">
            <h2 class="section-title">Home & Acoustic Design</h2>
            <a href="pages/categories.php?category=home-living" class="view-all-link">
                <span>View all</span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>

        <div class="trending-brands-grid">
            <!-- Card 1 -->
            <div class="brand-hero-card tilt-card">
                <img src="https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=600&auto=format&fit=crop&q=80" alt="Caraway" class="brand-hero-bg">
                <div class="brand-hero-overlay"></div>
                <h3 class="brand-hero-center-name">Lumen Living</h3>
                <div class="brand-mini-thumbs-row">
                    <a href="pages/product.php?slug=orbital-smart-ambient-acoustic-speaker" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1545454675-3531b543be5d?w=200&auto=format&fit=crop&q=80" alt="Orbital" class="brand-thumb-img">
                        <span class="brand-thumb-title">Orbital 360</span>
                        <span class="brand-thumb-price">$189.50</span>
                    </a>
                    <a href="pages/product.php?slug=ceramic-minimalist-ultrasonic-aroma-diffuser" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=200&auto=format&fit=crop&q=80" alt="Diffuser" class="brand-thumb-img">
                        <span class="brand-thumb-title">Aroma Mist</span>
                        <span class="brand-thumb-price">$65.00</span>
                    </a>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="brand-hero-card tilt-card">
                <img src="https://images.unsplash.com/photo-1593784991095-a205069470b6?w=600&auto=format&fit=crop&q=80" alt="Cinema" class="brand-hero-bg">
                <div class="brand-hero-overlay"></div>
                <h3 class="brand-hero-center-name">Laser Cinema</h3>
                <div class="brand-mini-thumbs-row">
                    <a href="pages/product.php?slug=lumina-4k-uhd-ultra-short-throw-projector" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1593784991095-a205069470b6?w=200&auto=format&fit=crop&q=80" alt="Laser" class="brand-thumb-img">
                        <span class="brand-thumb-title">Lumina 4K</span>
                        <span class="brand-thumb-price">$1899.00</span>
                    </a>
                    <a href="pages/product.php?slug=progrip-ergonomic-wireless-precision-mouse" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=200&auto=format&fit=crop&q=80" alt="Mouse" class="brand-thumb-img">
                        <span class="brand-thumb-title">ProGrip</span>
                        <span class="brand-thumb-price">$89.99</span>
                    </a>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="brand-hero-card tilt-card">
                <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&auto=format&fit=crop&q=80" alt="The Citizenry" class="brand-hero-bg">
                <div class="brand-hero-overlay"></div>
                <h3 class="brand-hero-center-name">The Citizenry</h3>
                <div class="brand-mini-thumbs-row">
                    <a href="pages/product.php?slug=urban-minimalist-waterproof-rolltop-backpack" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=200&auto=format&fit=crop&q=80" alt="Bag" class="brand-thumb-img">
                        <span class="brand-thumb-title">Kovert 25L</span>
                        <span class="brand-thumb-price">$119.00</span>
                    </a>
                    <a href="pages/product.php?slug=nordic-handcrafted-acetate-polarized-sunglasses" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=200&auto=format&fit=crop&q=80" alt="Shades" class="brand-thumb-img">
                        <span class="brand-thumb-title">Solaria</span>
                        <span class="brand-thumb-price">$145.00</span>
                    </a>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="brand-hero-card tilt-card">
                <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&auto=format&fit=crop&q=80" alt="Workstation" class="brand-hero-bg">
                <div class="brand-hero-overlay"></div>
                <h3 class="brand-hero-center-name">Pulse Studios</h3>
                <div class="brand-mini-thumbs-row">
                    <a href="pages/product.php?slug=pulsebook-pro-16-ultralight-laptop" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=200&auto=format&fit=crop&q=80" alt="Laptop" class="brand-thumb-img">
                        <span class="brand-thumb-title">PulseBook</span>
                        <span class="brand-thumb-price">$1499.00</span>
                    </a>
                    <a href="pages/product.php?slug=chronos-elite-titanium-smartwatch" class="brand-thumb-card">
                        <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=200&auto=format&fit=crop&q=80" alt="Watch" class="brand-thumb-img">
                        <span class="brand-thumb-title">Chronos</span>
                        <span class="brand-thumb-price">$379.00</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====================================================================
     7. "In the spotlight" EDITORIAL FASHION & TECH GRID
     ==================================================================== -->
<section class="section" style="padding-top:1rem;">
    <div class="container">
        <div class="section-header reveal-on-scroll">
            <h2 class="section-title">In the spotlight</h2>
            <a href="pages/categories.php" class="view-all-link">
                <span>Explore all</span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>

        <div class="editorial-spotlight-row">
            <!-- Hero Card 1 -->
            <div class="editorial-spotlight-hero-card tilt-card">
                <img src="https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=800&auto=format&fit=crop&q=80" alt="Streetstyle in NYC">
                <span class="editorial-pill-title">Streetstyle in NYC</span>
            </div>

            <!-- Adjacent Mosaic Column 1 -->
            <div class="editorial-mosaic-col">
                <a href="pages/product.php?slug=nordic-handcrafted-acetate-polarized-sunglasses" class="editorial-mosaic-card">
                    <img src="https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=300&auto=format&fit=crop&q=80" alt="Acetate Sunglasses">
                    <strong style="font-size:0.875rem; color:var(--text-primary);">Solaria Polarized</strong>
                    <span style="font-size:0.8125rem; color:var(--primary); font-weight:700;">$145.00</span>
                </a>
                <a href="pages/product.php?slug=urban-minimalist-waterproof-rolltop-backpack" class="editorial-mosaic-card">
                    <img src="https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300&auto=format&fit=crop&q=80" alt="Rolltop Bag">
                    <strong style="font-size:0.875rem; color:var(--text-primary);">Cordura Rolltop</strong>
                    <span style="font-size:0.8125rem; color:var(--primary); font-weight:700;">$119.00</span>
                </a>
            </div>

            <!-- Hero Card 2 -->
            <div class="editorial-spotlight-hero-card tilt-card">
                <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&auto=format&fit=crop&q=80" alt="Textural layers">
                <span class="editorial-pill-title">Textural layers</span>
            </div>

            <!-- Adjacent Mosaic Column 2 -->
            <div class="editorial-mosaic-col">
                <a href="pages/product.php?slug=aura-anc-wireless-studio-headphones" class="editorial-mosaic-card">
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300&auto=format&fit=crop&q=80" alt="Aura ANC">
                    <strong style="font-size:0.875rem; color:var(--text-primary);">Aura ANC Headset</strong>
                    <span style="font-size:0.8125rem; color:var(--primary); font-weight:700;">$249.99</span>
                </a>
                <a href="pages/product.php?slug=apex-pro-smart-fitness-and-sleep-ring" class="editorial-mosaic-card">
                    <img src="https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=300&auto=format&fit=crop&q=80" alt="Apex Ring">
                    <strong style="font-size:0.875rem; color:var(--text-primary);">Apex Titanium Ring</strong>
                    <span style="font-size:0.8125rem; color:var(--primary); font-weight:700;">$299.00</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ====================================================================
     8. "Discover something you love" EDITORIAL PORTRAIT CARDS
     ==================================================================== -->
<section class="section" style="padding-top:2.5rem; padding-bottom:5rem;">
    <div class="container">
        <div style="text-align:center; margin-bottom:3rem;">
            <h2 style="font-size:clamp(2.2rem, 4vw, 3.2rem); font-weight:800; color:var(--text-primary); letter-spacing:-0.03em;">
                Discover something you love
            </h2>
        </div>

        <div class="discover-editorial-grid">
            <!-- Card 1: Winter Trend Pops of Purple -->
            <a href="pages/categories.php?category=fashion-apparel" class="discover-portrait-card tilt-card">
                <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&auto=format&fit=crop&q=80" alt="Winter Trend" class="portrait-bg">
                <div class="discover-floating-pill">
                    <img src="https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=100&auto=format&fit=crop&q=80" alt="Thumb" class="discover-pill-thumb">
                    <span class="discover-pill-text">Winter trend: pops of color</span>
                </div>
            </a>

            <!-- Card 2: Titanium & Jewelry Edit -->
            <a href="pages/categories.php?category=wearables" class="discover-portrait-card tilt-card">
                <img src="https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=600&auto=format&fit=crop&q=80" alt="Jewelry Edit" class="portrait-bg">
                <div class="discover-floating-pill">
                    <img src="https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=100&auto=format&fit=crop&q=80" alt="Thumb" class="discover-pill-thumb">
                    <span class="discover-pill-text">Titanium jewelry & rings</span>
                </div>
            </a>

            <!-- Card 3: Gentle Serums & Skincare -->
            <a href="pages/categories.php?category=beauty-wellness" class="discover-portrait-card tilt-card">
                <img src="https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600&auto=format&fit=crop&q=80" alt="Vitamin C Serums" class="portrait-bg">
                <div class="discover-floating-pill">
                    <img src="https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=100&auto=format&fit=crop&q=80" alt="Thumb" class="discover-pill-thumb">
                    <span class="discover-pill-text">Gentle peptide glow serums</span>
                </div>
            </a>

            <!-- Card 4: Acoustic Living -->
            <a href="pages/categories.php?category=home-living" class="discover-portrait-card tilt-card">
                <img src="https://images.unsplash.com/photo-1545454675-3531b543be5d?w=600&auto=format&fit=crop&q=80" alt="Acoustic Living" class="portrait-bg">
                <div class="discover-floating-pill">
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=100&auto=format&fit=crop&q=80" alt="Thumb" class="discover-pill-thumb">
                    <span class="discover-pill-text">Studio audio essentials</span>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- Interactive Three.js 3D Hero Scene Script -->
<script src="assets/js/three-hero.js" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
