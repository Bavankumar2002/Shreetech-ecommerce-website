<?php
/**
 * SHOPPULSE Product Detail Page
 * Image gallery with zoom, Interactive Three.js 3D Product Viewer, Variants, Specs, Reviews, Related Products
 */

require_once __DIR__ . '/../includes/db.php';

$pdo = get_db();
$slug = clean_input($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);

if (empty($slug) && $id <= 0) {
    header('Location: categories.php');
    exit;
}

$whereField = !empty($slug) ? 'p.slug = :val' : 'p.id = :val';
$paramVal = !empty($slug) ? $slug : $id;

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE {$whereField} 
    LIMIT 1
");
$stmt->execute(['val' => $paramVal]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: categories.php');
    exit;
}

// Decode gallery & specs
$gallery = !empty($product['gallery']) ? json_decode($product['gallery'], true) : [$product['image_url']];
if (empty($gallery)) $gallery = [$product['image_url']];

$specs = !empty($product['specs']) ? json_decode($product['specs'], true) : [];

// Reviews
$revStmt = $pdo->prepare("SELECT user_name, rating, comment, created_at FROM reviews WHERE product_id = :pid ORDER BY created_at DESC");
$revStmt->execute(['pid' => $product['id']]);
$reviews = $revStmt->fetchAll();

// Related products
$relStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id = :cat_id AND p.id != :cur_id 
    LIMIT 4
");
$relStmt->execute(['cat_id' => $product['category_id'], 'cur_id' => $product['id']]);
$related_products = $relStmt->fetchAll();

$page_title = $product['name'] . " | " . $product['brand'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-block: 2rem 4rem;">
    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" style="font-size:0.875rem; color:var(--text-muted); margin-bottom:2rem; display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
        <a href="../index.php" style="color:var(--text-secondary);">Home</a>
        <span>/</span>
        <a href="categories.php" style="color:var(--text-secondary);">Departments</a>
        <span>/</span>
        <a href="categories.php?category=<?= htmlspecialchars($product['category_slug']) ?>" style="color:var(--text-secondary);"><?= htmlspecialchars($product['category_name']) ?></a>
        <span>/</span>
        <span style="color:var(--text-primary); font-weight:600;"><?= htmlspecialchars($product['name']) ?></span>
    </nav>

    <!-- Product Hero Layout: Gallery / 3D Canvas + Details -->
    <div class="product-detail-wrap" style="display:grid; grid-template-columns: 1fr 1fr; gap:3.5rem; margin-bottom:4rem; align-items:start;">
        
        <!-- Left Column: Gallery & 3D Interactive Viewer -->
        <div>
            <!-- View Mode Switch Tabs (Photo Gallery vs 3D Interactive View) -->
            <div style="display:flex; gap:0.75rem; margin-bottom:1rem;">
                <button type="button" id="tab-photo-btn" class="cat-pill active" onclick="switchMediaTab('photo')">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>High-Res Photos</span>
                </button>
                <button type="button" id="tab-3d-btn" class="cat-pill" onclick="switchMediaTab('3d')">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    <span>Interactive 3D View (WebGL)</span>
                </button>
            </div>

            <!-- 1. Photo Gallery View Container -->
            <div id="media-photo-view" style="display:block;">
                <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); overflow:hidden; position:relative; height:460px; box-shadow:var(--shadow-card); display:flex; align-items:center; justify-content:center;">
                    <img 
                        id="main-product-img" 
                        src="<?= htmlspecialchars($gallery[0]) ?>" 
                        alt="<?= htmlspecialchars($product['name']) ?>" 
                        class="product-main-img"
                        style="width:100%; height:100%; object-fit:cover; transition:transform 0.3s ease; cursor:zoom-in;"
                        onclick="this.style.transform = this.style.transform === 'scale(1.5)' ? 'scale(1)' : 'scale(1.5)';"
                    >
                    <span style="position:absolute; bottom:12px; right:12px; font-size:0.75rem; background:rgba(0,0,0,0.6); color:#fff; padding:0.25rem 0.6rem; border-radius:var(--radius-pill); pointer-events:none;">
                        Click image to zoom
                    </span>
                </div>

                <!-- Thumbnails row -->
                <?php if (count($gallery) > 1): ?>
                    <div style="display:flex; gap:0.75rem; margin-top:1rem; overflow-x:auto;">
                        <?php foreach ($gallery as $idx => $imgUrl): ?>
                            <button 
                                type="button" 
                                onclick="document.getElementById('main-product-img').src = '<?= htmlspecialchars($imgUrl) ?>'; document.querySelectorAll('.thumb-btn').forEach(b => b.style.borderColor = 'var(--border-color)'); this.style.borderColor = 'var(--primary)';" 
                                class="thumb-btn"
                                style="width:75px; height:75px; border-radius:var(--radius-md); overflow:hidden; border:2px solid <?= $idx === 0 ? 'var(--primary)' : 'var(--border-color)' ?>; background:var(--bg-surface); cursor:pointer; flex-shrink:0;"
                            >
                                <img src="<?= htmlspecialchars($imgUrl) ?>" alt="View <?= $idx + 1 ?>" style="width:100%; height:100%; object-fit:cover;">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2. Interactive 3D Model Canvas View Container -->
            <div id="media-3d-view" style="display:none;">
                <div class="product-3d-viewport" id="product-3d-canvas-wrap">
                    <canvas id="product-3d-canvas" style="width:100%; height:100%; display:block; cursor:grab;"></canvas>
                    <div class="product-3d-hint">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                        <span>Click & drag to rotate 360° • Scroll to zoom</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Product Info & Purchase Controls -->
        <div>
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.75rem;">
                <span style="font-size:0.8125rem; font-weight:800; text-transform:uppercase; color:var(--primary); letter-spacing:0.05em;"><?= htmlspecialchars($product['brand']) ?></span>
                <?php if (!empty($product['badge'])): ?>
                    <span class="badge-tag"><?= htmlspecialchars($product['badge']) ?></span>
                <?php endif; ?>
                <span class="badge-cashback">
                    +<?= number_format($product['cashback_pct'], 1) ?>% Cashback
                </span>
            </div>

            <h1 style="font-size:clamp(1.75rem, 2.8vw, 2.35rem); font-weight:800; line-height:1.2; color:var(--text-primary); margin-bottom:1rem;">
                <?= htmlspecialchars($product['name']) ?>
            </h1>

            <!-- Ratings & Reviews Summary -->
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.5rem;">
                <div class="stars" style="font-size:1rem;">★★★★★</div>
                <span style="font-size:0.9375rem; font-weight:700; color:var(--text-primary);"><?= number_format($product['rating'], 1) ?></span>
                <a href="#reviews-section" style="font-size:0.875rem; color:var(--primary); font-weight:600; text-decoration:underline;">
                    <?= count($reviews) ?> Customer Reviews
                </a>
                <span style="color:var(--border-color);">|</span>
                <span style="font-size:0.875rem; color:<?= $product['stock'] > 0 ? 'var(--cashback)' : 'var(--accent)' ?>; font-weight:700;">
                    <?= $product['stock'] > 0 ? "In Stock ({$product['stock']} units)" : 'Out of Stock' ?>
                </span>
            </div>

            <!-- Price Box with Cashback Value -->
            <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem; margin-bottom:2rem; box-shadow:var(--shadow-subtle);">
                <div style="display:flex; align-items:baseline; gap:0.875rem; margin-bottom:0.5rem;">
                    <span style="font-size:2rem; font-weight:800; color:var(--text-primary);"><?= format_price($product['price']) ?></span>
                    <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                        <span style="font-size:1.125rem; color:var(--text-muted); text-decoration:line-through;"><?= format_price($product['original_price']) ?></span>
                        <span style="background:var(--accent); color:#fff; font-size:0.75rem; font-weight:800; padding:0.2rem 0.6rem; border-radius:var(--radius-pill);">
                            Save $<?= number_format($product['original_price'] - $product['price'], 2) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div style="background:var(--cashback-bg); color:var(--cashback); font-size:0.8125rem; font-weight:700; padding:0.5rem 0.875rem; border-radius:var(--radius-sm); display:flex; align-items:center; gap:0.5rem;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><line x1="12" y1="6" x2="12" y2="8"/><line x1="12" y1="16" x2="12" y2="18"/></svg>
                    <span>You will earn <strong>$<?= number_format($product['price'] * ($product['cashback_pct'] / 100), 2) ?> in Cashback</strong> on this order!</span>
                </div>
            </div>

            <!-- Description -->
            <p style="font-size:1rem; color:var(--text-secondary); line-height:1.7; margin-bottom:2rem;">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </p>

            <!-- Color / Finish Selector -->
            <div style="margin-bottom:1.75rem;">
                <label style="font-size:0.875rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.5rem;">
                    Select Finish: <span id="selected-variant-label" style="font-weight:400; color:var(--text-secondary);">Matte Space Titanium</span>
                </label>
                <div style="display:flex; gap:0.75rem;">
                    <button type="button" class="variant-pill active" onclick="selectVariant(this, 'Matte Space Titanium')" style="border:2px solid var(--primary); background:var(--bg-surface); padding:0.4rem 1rem; border-radius:var(--radius-pill); font-size:0.8125rem; font-weight:700; cursor:pointer;">Space Titanium</button>
                    <button type="button" class="variant-pill" onclick="selectVariant(this, 'Obsidian Stealth Black')" style="border:2px solid var(--border-color); background:var(--bg-surface); padding:0.4rem 1rem; border-radius:var(--radius-pill); font-size:0.8125rem; font-weight:700; cursor:pointer;">Obsidian Black</button>
                    <button type="button" class="variant-pill" onclick="selectVariant(this, 'Polar Frost Silver')" style="border:2px solid var(--border-color); background:var(--bg-surface); padding:0.4rem 1rem; border-radius:var(--radius-pill); font-size:0.8125rem; font-weight:700; cursor:pointer;">Polar Silver</button>
                </div>
            </div>

            <!-- Quantity & Add to Cart Action Row -->
            <div style="display:flex; gap:1rem; align-items:center; margin-bottom:2rem;">
                <div style="display:flex; align-items:center; border:2px solid var(--border-color); border-radius:var(--radius-pill); padding:0.4rem 0.75rem; gap:1rem; background:var(--bg-surface);">
                    <button type="button" onclick="const q = document.getElementById('qty-input'); q.value = Math.max(1, parseInt(q.value) - 1);" style="font-size:1.125rem; font-weight:700; color:var(--text-secondary);">−</button>
                    <input type="number" id="qty-input" value="1" min="1" max="<?= $product['stock'] ?>" style="width:36px; text-align:center; font-weight:800; font-size:1rem; background:transparent;">
                    <button type="button" onclick="const q = document.getElementById('qty-input'); q.value = Math.min(<?= $product['stock'] ?>, parseInt(q.value) + 1);" style="font-size:1.125rem; font-weight:700; color:var(--text-secondary);">+</button>
                </div>

                <button 
                    type="button" 
                    class="card-add-btn add-to-cart-trigger magnetic-btn" 
                    data-product-id="<?= $product['id'] ?>" 
                    onclick="this.dataset.quantity = document.getElementById('qty-input').value;"
                    style="flex:1; padding:0.875rem 2rem; font-size:1rem;"
                >
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <span>Add to Shopping Bag</span>
                </button>
            </div>

            <!-- Value Guarantees -->
            <div style="border-top:1px solid var(--border-color); padding-top:1.5rem; display:grid; grid-template-columns:1fr 1fr; gap:1rem; font-size:0.8125rem; color:var(--text-secondary);">
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span>🚀</span> <strong>Free Express Shipping</strong> over $99
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span>🛡️</span> <strong>2-Year Official Warranty</strong>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span>🔄</span> <strong>30-Day Hassle-Free Returns</strong>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span>💰</span> <strong>Price Match Guarantee</strong>
                </div>
            </div>

        </div>
    </div>

    <!-- Technical Specifications Table -->
    <?php if (!empty($specs)): ?>
        <section style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2rem; margin-bottom:4rem;">
            <h3 style="font-size:1.35rem; font-weight:800; color:var(--text-primary); margin-bottom:1.5rem;">Technical Specifications</h3>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:1rem;">
                <?php foreach ($specs as $key => $val): ?>
                    <div style="background:var(--bg-page); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1rem;">
                        <span style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--text-muted); display:block; margin-bottom:0.25rem;"><?= htmlspecialchars($key) ?></span>
                        <strong style="font-size:0.9375rem; color:var(--text-primary);"><?= htmlspecialchars($val) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Customer Reviews Section -->
    <section id="reviews-section" style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2rem; margin-bottom:4rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem;">
            <div>
                <h3 style="font-size:1.35rem; font-weight:800; color:var(--text-primary);">Customer Feedback & Ratings</h3>
                <p style="font-size:0.875rem; color:var(--text-muted);">Verified purchases and community reviews</p>
            </div>
            <button type="button" class="card-add-btn" style="width:auto; padding:0.5rem 1.25rem; font-size:0.8125rem;" onclick="document.getElementById('write-review-box').style.display = 'block'; this.style.display = 'none';">
                Write a Review
            </button>
        </div>

        <!-- Write review expandable box -->
        <div id="write-review-box" style="display:none; background:var(--bg-page); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; margin-bottom:2rem;">
            <h4 style="font-size:1rem; font-weight:700; margin-bottom:1rem;">Share Your Experience</h4>
            <form onsubmit="event.preventDefault(); window.SHOPPULSE.showToast('Thank you! Your review was submitted for approval.', 'success'); document.getElementById('write-review-box').style.display = 'none';">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <input type="text" placeholder="Your Name" required style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:0.5rem; color:var(--text-primary);">
                    <select style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:0.5rem; color:var(--text-primary);">
                        <option value="5">★★★★★ (5/5 Stars - Excellent)</option>
                        <option value="4">★★★★☆ (4/5 Stars - Very Good)</option>
                        <option value="3">★★★☆☆ (3/5 Stars - Average)</option>
                    </select>
                </div>
                <textarea rows="3" placeholder="What did you like or dislike about this product?" required style="width:100%; background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:0.5rem; margin-bottom:1rem; color:var(--text-primary);"></textarea>
                <button type="submit" class="card-add-btn" style="width:auto; padding:0.5rem 1.5rem;">Submit Review</button>
            </form>
        </div>

        <!-- Reviews List -->
        <?php if (empty($reviews)): ?>
            <p style="color:var(--text-muted); font-size:0.9375rem;">No reviews yet. Be the first to share your experience!</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:1.25rem;">
                <?php foreach ($reviews as $rev): ?>
                    <div style="border-bottom:1px solid var(--border-color); padding-bottom:1.25rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
                            <span style="font-weight:700; font-size:0.9375rem; color:var(--text-primary);"><?= htmlspecialchars($rev['user_name']) ?></span>
                            <span style="font-size:0.75rem; color:var(--text-muted);"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
                        </div>
                        <div class="stars" style="font-size:0.875rem; margin-bottom:0.5rem;">
                            <?= str_repeat('★', $rev['rating']) ?><?= str_repeat('☆', 5 - $rev['rating']) ?>
                        </div>
                        <p style="font-size:0.9375rem; color:var(--text-secondary); line-height:1.6;">
                            <?= htmlspecialchars($rev['comment']) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <section>
            <h3 style="font-size:1.5rem; font-weight:800; color:var(--text-primary); margin-bottom:1.5rem;">You Might Also Like</h3>
            <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">
                <?php foreach ($related_products as $rel): ?>
                    <article class="product-card tilt-card">
                        <div class="product-card-top">
                            <span class="badge-cashback" style="position:absolute; top:12px; left:12px;">+<?= number_format($rel['cashback_pct'], 1) ?>% Cashback</span>
                            <a href="product.php?slug=<?= htmlspecialchars($rel['slug']) ?>">
                                <img src="<?= htmlspecialchars($rel['image_url']) ?>" alt="<?= htmlspecialchars($rel['name']) ?>" class="product-card-img" loading="lazy">
                            </a>
                        </div>
                        <div class="product-card-body">
                            <span class="product-card-brand"><?= htmlspecialchars($rel['brand']) ?></span>
                            <h4 class="product-card-title">
                                <a href="product.php?slug=<?= htmlspecialchars($rel['slug']) ?>"><?= htmlspecialchars($rel['name']) ?></a>
                            </h4>
                            <div class="product-price-row">
                                <span class="current-price"><?= format_price($rel['price']) ?></span>
                            </div>
                            <button type="button" class="card-add-btn add-to-cart-trigger magnetic-btn" data-product-id="<?= $rel['id'] ?>" data-quantity="1">
                                Add to Cart
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<!-- ====================================================================
     THREE.JS 3D PRODUCT VIEWER SCRIPT
     ==================================================================== -->
<script>
function switchMediaTab(mode) {
    const photoTab = document.getElementById('tab-photo-btn');
    const threeTab = document.getElementById('tab-3d-btn');
    const photoView = document.getElementById('media-photo-view');
    const threeView = document.getElementById('media-3d-view');

    if (mode === 'photo') {
        photoTab.classList.add('active');
        threeTab.classList.remove('active');
        photoView.style.display = 'block';
        threeView.style.display = 'none';
    } else {
        threeTab.classList.add('active');
        photoTab.classList.remove('active');
        photoView.style.display = 'none';
        threeView.style.display = 'block';
        initProduct3DViewer();
    }
}

function selectVariant(btn, name) {
    document.querySelectorAll('.variant-pill').forEach(b => {
        b.style.borderColor = 'var(--border-color)';
        b.classList.remove('active');
    });
    btn.style.borderColor = 'var(--primary)';
    btn.classList.add('active');
    document.getElementById('selected-variant-label').textContent = name;
}

// Three.js 3D Viewer Initializer
let viewerInitialized = false;
function initProduct3DViewer() {
    if (viewerInitialized || typeof THREE === 'undefined') return;
    viewerInitialized = true;

    const canvas = document.getElementById('product-3d-canvas');
    const wrap = document.getElementById('product-3d-canvas-wrap');
    if (!canvas || !wrap) return;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, wrap.clientWidth / wrap.clientHeight, 0.1, 100);
    camera.position.set(0, 0, 7);

    const renderer = new THREE.WebGLRenderer({
        canvas: canvas,
        alpha: true,
        antialias: true
    });
    renderer.setSize(wrap.clientWidth, wrap.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    // Studio Lighting
    const hemiLight = new THREE.HemisphereLight(0xffffff, 0x444444, 1.2);
    scene.add(hemiLight);

    const dirLight1 = new THREE.DirectionalLight(0xffffff, 1.5);
    dirLight1.position.set(5, 10, 7);
    scene.add(dirLight1);

    const dirLight2 = new THREE.DirectionalLight(0x0066cc, 0.8);
    dirLight2.position.set(-5, -5, -5);
    scene.add(dirLight2);

    // Dynamic 3D model geometry based on database model_shape
    const modelShape = '<?= htmlspecialchars($product['model_shape'] ?? 'cube') ?>';
    let geometry;

    switch (modelShape) {
        case 'cylinder':
            geometry = new THREE.CylinderGeometry(1.6, 1.6, 2.5, 32);
            break;
        case 'torus':
            geometry = new THREE.TorusGeometry(1.6, 0.5, 30, 60);
            break;
        case 'sphere':
            geometry = new THREE.SphereGeometry(1.8, 32, 32);
            break;
        default:
            geometry = new THREE.BoxGeometry(2.4, 2.4, 2.4);
    }

    // Material with high metallic & roughness realism
    const material = new THREE.MeshPhysicalMaterial({
        color: 0x004b91,
        metalness: 0.6,
        roughness: 0.2,
        clearcoat: 0.8,
        clearcoatRoughness: 0.1
    });

    const mesh = new THREE.Mesh(geometry, material);
    scene.add(mesh);

    // Interactive Drag Orbit & Auto-rotation
    let isDragging = false;
    let prevMousePos = { x: 0, y: 0 };

    canvas.addEventListener('mousedown', (e) => {
        isDragging = true;
        prevMousePos = { x: e.clientX, y: e.clientY };
        canvas.style.cursor = 'grabbing';
    });

    window.addEventListener('mouseup', () => {
        isDragging = false;
        canvas.style.cursor = 'grab';
    });

    canvas.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const deltaX = e.clientX - prevMousePos.x;
        const deltaY = e.clientY - prevMousePos.y;

        mesh.rotation.y += deltaX * 0.01;
        mesh.rotation.x += deltaY * 0.01;

        prevMousePos = { x: e.clientX, y: e.clientY };
    });

    canvas.addEventListener('wheel', (e) => {
        e.preventDefault();
        camera.position.z = Math.min(12, Math.max(3.5, camera.position.z + (e.deltaY * 0.005)));
    }, { passive: false });

    // Render loop
    function animate() {
        requestAnimationFrame(animate);
        if (!isDragging) {
            mesh.rotation.y += 0.006;
            mesh.rotation.x += 0.002;
        }
        renderer.render(scene, camera);
    }
    animate();

    window.addEventListener('resize', () => {
        camera.aspect = wrap.clientWidth / wrap.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(wrap.clientWidth, wrap.clientHeight);
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
