<?php
/**
 * SHOPPULSE Categories & Product Catalog Page
 * Multi-facet filter sidebar (Price, Brand, Rating), Sorting, Grid, Pagination
 */

require_once __DIR__ . '/../includes/db.php';

$pdo = get_db();

// 1. Fetch all categories for sidebar
$catStmt = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.display_order ASC
");
$allCategories = $catStmt->fetchAll();

// 2. Fetch distinct brands
$brandStmt = $pdo->query("SELECT DISTINCT brand FROM products ORDER BY brand ASC");
$allBrands = $brandStmt->fetchAll(PDO::FETCH_COLUMN);

// 3. Process filter & search parameters
$selectedCategorySlug = clean_input($_GET['category'] ?? '');
$searchQuery = clean_input($_GET['search'] ?? '');
$selectedBrand = clean_input($_GET['brand'] ?? '');
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$minRating = isset($_GET['min_rating']) && is_numeric($_GET['min_rating']) ? (float)$_GET['min_rating'] : null;
$sort = clean_input($_GET['sort'] ?? 'featured');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 9;
$offset = ($page - 1) * $limit;

// Build query
$where = ['1=1'];
$params = [];

$activeCategoryName = 'All Products';
if (!empty($selectedCategorySlug)) {
    $where[] = 'c.slug = :category_slug';
    $params['category_slug'] = $selectedCategorySlug;

    foreach ($allCategories as $c) {
        if ($c['slug'] === $selectedCategorySlug) {
            $activeCategoryName = $c['name'];
            break;
        }
    }
}

if (!empty($searchQuery)) {
    $where[] = '(p.name LIKE :search OR p.brand LIKE :search OR p.description LIKE :search)';
    $params['search'] = '%' . $searchQuery . '%';
}

if (!empty($selectedBrand)) {
    $where[] = 'p.brand = :brand';
    $params['brand'] = $selectedBrand;
}

if ($minPrice !== null) {
    $where[] = 'p.price >= :min_price';
    $params['min_price'] = $minPrice;
}

if ($maxPrice !== null) {
    $where[] = 'p.price <= :max_price';
    $params['max_price'] = $maxPrice;
}

if ($minRating !== null) {
    $where[] = 'p.rating >= :min_rating';
    $params['min_rating'] = $minRating;
}

// Total count
$countSql = "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE " . implode(' AND ', $where);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Sorting
$orderBy = match ($sort) {
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'rating' => 'p.rating DESC, p.reviews_count DESC',
    'newest' => 'p.id DESC',
    'discount' => '(p.original_price - p.price) DESC',
    default => 'p.is_featured DESC, p.rating DESC, p.id ASC'
};

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

$page_title = $activeCategoryName . " | Shop Products";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-block: 2rem 4rem;">
    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" style="font-size:0.875rem; color:var(--text-muted); margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
        <a href="../index.php" style="color:var(--text-secondary);">Home</a>
        <span>/</span>
        <a href="categories.php" style="color:var(--text-secondary);">Categories</a>
        <?php if (!empty($selectedCategorySlug)): ?>
            <span>/</span>
            <span style="color:var(--text-primary); font-weight:600;"><?= htmlspecialchars($activeCategoryName) ?></span>
        <?php endif; ?>
    </nav>

    <!-- Page Title & Results Count Header -->
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:1rem; margin-bottom:2rem; border-bottom:1px solid var(--border-color); padding-bottom:1.25rem;">
        <div>
            <h1 style="font-size: clamp(1.75rem, 3vw, 2.25rem); font-weight:800; color:var(--text-primary); letter-spacing:-0.02em;">
                <?= htmlspecialchars($activeCategoryName) ?>
                <?php if (!empty($searchQuery)): ?>
                    <span style="font-weight:400; font-size:1.25rem; color:var(--text-secondary);">for "<?= htmlspecialchars($searchQuery) ?>"</span>
                <?php endif; ?>
            </h1>
            <p style="font-size:0.875rem; color:var(--text-muted); margin-top:0.25rem;">
                Showing <strong><?= count($products) ?></strong> of <strong><?= $totalProducts ?></strong> items with guaranteed cashback
            </p>
        </div>

        <!-- Sorting dropdown form -->
        <form method="GET" style="display:flex; align-items:center; gap:0.75rem;">
            <?php if (!empty($selectedCategorySlug)): ?><input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategorySlug) ?>"><?php endif; ?>
            <?php if (!empty($searchQuery)): ?><input type="hidden" name="search" value="<?= htmlspecialchars($searchQuery) ?>"><?php endif; ?>
            <?php if (!empty($selectedBrand)): ?><input type="hidden" name="brand" value="<?= htmlspecialchars($selectedBrand) ?>"><?php endif; ?>
            <?php if ($minPrice !== null): ?><input type="hidden" name="min_price" value="<?= $minPrice ?>"><?php endif; ?>
            <?php if ($maxPrice !== null): ?><input type="hidden" name="max_price" value="<?= $maxPrice ?>"><?php endif; ?>
            
            <label for="sort-select" style="font-size:0.875rem; font-weight:600; color:var(--text-secondary); white-space:nowrap;">Sort by:</label>
            <select 
                id="sort-select" 
                name="sort" 
                onchange="this.form.submit()" 
                style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-pill); padding:0.45rem 1.25rem; font-size:0.875rem; font-weight:600; color:var(--text-primary); cursor:pointer;"
            >
                <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>Featured & Best Matches</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
                <option value="discount" <?= $sort === 'discount' ? 'selected' : '' ?>>Biggest Discount</option>
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
            </select>
        </form>
    </div>

    <!-- Active Filter Chips -->
    <?php 
    $hasFilters = !empty($selectedCategorySlug) || !empty($searchQuery) || !empty($selectedBrand) || $minPrice !== null || $maxPrice !== null || $minRating !== null;
    if ($hasFilters): 
    ?>
        <div style="display:flex; align-items:center; flex-wrap:wrap; gap:0.5rem; margin-bottom:1.5rem;">
            <span style="font-size:0.8125rem; font-weight:700; color:var(--text-muted);">Active Filters:</span>
            <?php if (!empty($selectedCategorySlug)): ?>
                <span class="search-chip" style="background:var(--primary-light); color:var(--primary); display:inline-flex; align-items:center; gap:0.4rem;">
                    Category: <?= htmlspecialchars($activeCategoryName) ?>
                    <a href="categories.php?<?= http_build_query(array_merge($_GET, ['category' => ''])) ?>" style="font-weight:800;">×</a>
                </span>
            <?php endif; ?>
            <?php if (!empty($selectedBrand)): ?>
                <span class="search-chip" style="background:var(--primary-light); color:var(--primary); display:inline-flex; align-items:center; gap:0.4rem;">
                    Brand: <?= htmlspecialchars($selectedBrand) ?>
                    <a href="categories.php?<?= http_build_query(array_merge($_GET, ['brand' => ''])) ?>" style="font-weight:800;">×</a>
                </span>
            <?php endif; ?>
            <?php if (!empty($searchQuery)): ?>
                <span class="search-chip" style="background:var(--primary-light); color:var(--primary); display:inline-flex; align-items:center; gap:0.4rem;">
                    Search: <?= htmlspecialchars($searchQuery) ?>
                    <a href="categories.php?<?= http_build_query(array_merge($_GET, ['search' => ''])) ?>" style="font-weight:800;">×</a>
                </span>
            <?php endif; ?>
            <?php if ($minPrice !== null || $maxPrice !== null): ?>
                <span class="search-chip" style="background:var(--primary-light); color:var(--primary); display:inline-flex; align-items:center; gap:0.4rem;">
                    Price: $<?= $minPrice ?? 0 ?> - $<?= $maxPrice ?? 'Any' ?>
                    <a href="categories.php?<?= http_build_query(array_merge($_GET, ['min_price' => '', 'max_price' => ''])) ?>" style="font-weight:800;">×</a>
                </span>
            <?php endif; ?>
            <a href="categories.php" style="font-size:0.8125rem; font-weight:700; color:var(--accent); margin-left:0.5rem;">Reset All</a>
        </div>
    <?php endif; ?>

    <!-- Two-column Layout: Filter Sidebar + Products Grid -->
    <div style="display:grid; grid-template-columns: 260px 1fr; gap:2.5rem; align-items:start;">
        
        <!-- ====================================================================
             FILTER SIDEBAR (Shop.com signature multi-facet navigation)
             ==================================================================== -->
        <aside style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem; box-shadow:var(--shadow-subtle); position:sticky; top:100px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
                <h3 style="font-size:1.0625rem; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:0.5rem;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    Filters
                </h3>
                <?php if ($hasFilters): ?>
                    <a href="categories.php" style="font-size:0.75rem; font-weight:700; color:var(--accent);">Clear All</a>
                <?php endif; ?>
            </div>

            <!-- Categories Filter Group -->
            <div style="margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:1.25rem;">
                <h4 style="font-size:0.875rem; font-weight:700; color:var(--text-primary); margin-bottom:0.75rem; text-transform:uppercase; letter-spacing:0.04em;">Department</h4>
                <ul style="list-style:none; display:flex; flex-direction:column; gap:0.5rem;">
                    <li>
                        <a href="categories.php?<?= http_build_query(array_merge($_GET, ['category' => '', 'page' => 1])) ?>" 
                           style="display:flex; justify-content:space-between; font-size:0.875rem; color:<?= empty($selectedCategorySlug) ? 'var(--primary)' : 'var(--text-secondary)' ?>; font-weight:<?= empty($selectedCategorySlug) ? '700' : '500' ?>;">
                            <span>All Departments</span>
                        </a>
                    </li>
                    <?php foreach ($allCategories as $c): ?>
                        <li>
                            <a href="categories.php?<?= http_build_query(array_merge($_GET, ['category' => $c['slug'], 'page' => 1])) ?>" 
                               style="display:flex; justify-content:space-between; font-size:0.875rem; color:<?= $selectedCategorySlug === $c['slug'] ? 'var(--primary)' : 'var(--text-secondary)' ?>; font-weight:<?= $selectedCategorySlug === $c['slug'] ? '700' : '500' ?>;">
                                <span><?= htmlspecialchars($c['name']) ?></span>
                                <span style="font-size:0.75rem; color:var(--text-muted);">(<?= $c['product_count'] ?>)</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Price Range Filter -->
            <form method="GET" style="margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:1.25rem;">
                <?php if (!empty($selectedCategorySlug)): ?><input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategorySlug) ?>"><?php endif; ?>
                <?php if (!empty($selectedBrand)): ?><input type="hidden" name="brand" value="<?= htmlspecialchars($selectedBrand) ?>"><?php endif; ?>
                <?php if (!empty($searchQuery)): ?><input type="hidden" name="search" value="<?= htmlspecialchars($searchQuery) ?>"><?php endif; ?>
                <?php if (!empty($sort)): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>

                <h4 style="font-size:0.875rem; font-weight:700; color:var(--text-primary); margin-bottom:0.75rem; text-transform:uppercase; letter-spacing:0.04em;">Price Range</h4>
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                    <input 
                        type="number" 
                        name="min_price" 
                        placeholder="$ Min" 
                        value="<?= $minPrice !== null ? htmlspecialchars($minPrice) : '' ?>" 
                        min="0"
                        style="width:50%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:0.4rem 0.6rem; font-size:0.8125rem; color:var(--text-primary);"
                    >
                    <span style="color:var(--text-muted);">-</span>
                    <input 
                        type="number" 
                        name="max_price" 
                        placeholder="$ Max" 
                        value="<?= $maxPrice !== null ? htmlspecialchars($maxPrice) : '' ?>" 
                        min="0"
                        style="width:50%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:0.4rem 0.6rem; font-size:0.8125rem; color:var(--text-primary);"
                    >
                </div>
                <button type="submit" style="width:100%; background:var(--primary-light); color:var(--primary); font-size:0.8125rem; font-weight:700; padding:0.4rem; border-radius:var(--radius-pill);">
                    Apply Price
                </button>
            </form>

            <!-- Brand Filter -->
            <div style="margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:1.25rem;">
                <h4 style="font-size:0.875rem; font-weight:700; color:var(--text-primary); margin-bottom:0.75rem; text-transform:uppercase; letter-spacing:0.04em;">Brand</h4>
                <div style="display:flex; flex-direction:column; gap:0.5rem; max-height:160px; overflow-y:auto;">
                    <?php foreach ($allBrands as $b): ?>
                        <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.875rem; color:var(--text-secondary); cursor:pointer;">
                            <input 
                                type="checkbox" 
                                <?= $selectedBrand === $b ? 'checked' : '' ?>
                                onchange="window.location.href = 'categories.php?' + new URLSearchParams(Object.assign({}, Object.fromEntries(new URLSearchParams(window.location.search)), {brand: this.checked ? '<?= urlencode($b) ?>' : '', page: 1})).toString();"
                                style="accent-color:var(--primary); width:16px; height:16px;"
                            >
                            <span><?= htmlspecialchars($b) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Customer Rating Filter -->
            <div>
                <h4 style="font-size:0.875rem; font-weight:700; color:var(--text-primary); margin-bottom:0.75rem; text-transform:uppercase; letter-spacing:0.04em;">Customer Rating</h4>
                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                    <a href="categories.php?<?= http_build_query(array_merge($_GET, ['min_rating' => 4.5, 'page' => 1])) ?>" 
                       style="display:flex; align-items:center; gap:0.35rem; font-size:0.875rem; color:<?= $minRating == 4.5 ? 'var(--primary)' : 'var(--text-secondary)' ?>; font-weight:<?= $minRating == 4.5 ? '700' : '500' ?>;">
                        <span style="color:var(--warning);">★★★★★</span>
                        <span>4.5 & up</span>
                    </a>
                    <a href="categories.php?<?= http_build_query(array_merge($_GET, ['min_rating' => 4.0, 'page' => 1])) ?>" 
                       style="display:flex; align-items:center; gap:0.35rem; font-size:0.875rem; color:<?= $minRating == 4.0 ? 'var(--primary)' : 'var(--text-secondary)' ?>; font-weight:<?= $minRating == 4.0 ? '700' : '500' ?>;">
                        <span style="color:var(--warning);">★★★★☆</span>
                        <span>4.0 & up</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- ====================================================================
             PRODUCTS GRID & PAGINATION
             ==================================================================== -->
        <main>
            <?php if (empty($products)): ?>
                <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:4rem 2rem; text-align:center;">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 1rem; color:var(--text-muted);">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <h3 style="font-size:1.25rem; font-weight:700; color:var(--text-primary); margin-bottom:0.5rem;">No products found</h3>
                    <p style="font-size:0.9375rem; color:var(--text-secondary); max-width:400px; margin:0 auto 1.5rem;">
                        We couldn't find any products matching your current filters. Try resetting your filters or searching for something else.
                    </p>
                    <a href="categories.php" class="card-add-btn" style="display:inline-flex; width:auto; padding:0.65rem 1.75rem;">
                        Reset Filters
                    </a>
                </div>
            <?php else: ?>
                <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
                    <?php foreach ($products as $index => $prod): ?>
                        <article class="product-card tilt-card reveal-on-scroll stagger-<?= ($index % 3) + 1 ?>">
                            <div class="product-card-top">
                                <!-- Badges -->
                                <div class="card-badges">
                                    <?php if (!empty($prod['badge'])): ?>
                                        <span class="badge-tag"><?= htmlspecialchars($prod['badge']) ?></span>
                                    <?php endif; ?>
                                    <span class="badge-cashback">
                                        <span>+<?= number_format($prod['cashback_pct'], 1) ?>%</span>
                                        <span>Cashback</span>
                                    </span>
                                </div>

                                <!-- Wishlist button -->
                                <button type="button" class="card-wishlist-btn" aria-label="Save to wishlist" onclick="window.SHOPPULSE.showToast('Saved <?= addslashes($prod['name']) ?> to wishlist!', 'success');">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                    </svg>
                                </button>

                                <a href="product.php?slug=<?= htmlspecialchars($prod['slug']) ?>" aria-label="<?= htmlspecialchars($prod['name']) ?>">
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
                                    <?php if ($prod['original_price'] && $prod['original_price'] > $prod['price']): ?>
                                        <span class="original-price"><?= format_price($prod['original_price']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <button 
                                    type="button" 
                                    class="card-add-btn add-to-cart-trigger magnetic-btn" 
                                    data-product-id="<?= $prod['id'] ?>" 
                                    data-quantity="1"
                                >
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <line x1="12" y1="5" x2="12" y2="19"/>
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                    <span>Add to Cart</span>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Catalog Pagination" style="display:flex; justify-content:center; align-items:center; gap:0.5rem; margin-top:3rem;">
                        <?php if ($page > 1): ?>
                            <a href="categories.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                               style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-pill); padding:0.5rem 1rem; font-size:0.875rem; font-weight:700; color:var(--text-primary);">
                                ← Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="categories.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                               style="width:38px; height:38px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-size:0.875rem; font-weight:700; border:1px solid <?= $page === $i ? 'var(--primary)' : 'var(--border-color)' ?>; background:<?= $page === $i ? 'var(--primary)' : 'var(--bg-surface)' ?>; color:<?= $page === $i ? '#fff' : 'var(--text-primary)' ?>;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="categories.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                               style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-pill); padding:0.5rem 1rem; font-size:0.875rem; font-weight:700; color:var(--text-primary);">
                                Next →
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>
        </main>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
