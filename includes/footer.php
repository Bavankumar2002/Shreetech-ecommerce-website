<?php
/**
 * SHOPPULSE Global Footer
 */
$base_url = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'pages') ? '../' : './';
?>
    </main>

    <!-- Global Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand Info -->
                <div class="footer-brand-col">
                    <div class="brand-logo" style="margin-bottom:0.75rem;">
                        <div class="brand-logo-icon">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                                <polyline points="2 17 12 22 22 17"/>
                                <polyline points="2 12 12 17 22 12"/>
                            </svg>
                        </div>
                        <span><?= SITE_NAME ?></span>
                    </div>
                    <p class="footer-desc">
                        The next-generation e-commerce marketplace powered by WebGL 3D immersion, real-time price intelligence, and automatic cashback on every purchase.
                    </p>
                    <div style="margin-top:1.25rem; display:flex; gap:0.75rem; color:var(--text-muted);">
                        <span style="font-size:0.8125rem;">🔒 256-Bit SSL Encrypted & Verified</span>
                    </div>
                </div>

                <!-- Departments Column -->
                <div>
                    <h3 class="footer-col-title">Departments</h3>
                    <ul class="footer-links">
                        <li><a href="<?= $base_url ?>pages/categories.php?category=electronics">Electronics & Workstations</a></li>
                        <li><a href="<?= $base_url ?>pages/categories.php?category=audio-sound">Hi-Fi Audio & Headphones</a></li>
                        <li><a href="<?= $base_url ?>pages/categories.php?category=wearables">Titanium Smartwatches</a></li>
                        <li><a href="<?= $base_url ?>pages/categories.php?category=fashion-apparel">Designer Streetwear & Eyewear</a></li>
                        <li><a href="<?= $base_url ?>pages/categories.php?category=home-living">Acoustic Smart Home</a></li>
                        <li><a href="<?= $base_url ?>pages/categories.php?category=beauty-wellness">Peptide Serums & Diffusers</a></li>
                    </ul>
                </div>

                <!-- Customer Care Column -->
                <div>
                    <h3 class="footer-col-title">Customer Care</h3>
                    <ul class="footer-links">
                        <li><a href="<?= $base_url ?>pages/orders.php">Track Your Order</a></li>
                        <li><a href="<?= $base_url ?>pages/cart.php">Shopping Bag</a></li>
                        <li><a href="<?= $base_url ?>pages/deals.php">Cashback Rewards Program</a></li>
                        <li><a href="<?= $base_url ?>pages/orders.php">Returns & 30-Day Guarantee</a></li>
                        <li><a href="<?= $base_url ?>pages/auth.php">My Account Portal</a></li>
                        <li><a href="mailto:support@shoppulse.com">24/7 Priority Support</a></li>
                    </ul>
                </div>

                <!-- About & Policies Column -->
                <div>
                    <h3 class="footer-col-title">Marketplace Guarantee</h3>
                    <ul class="footer-links">
                        <li><a href="<?= $base_url ?>pages/deals.php">Price Match Guarantee</a></li>
                        <li><a href="<?= $base_url ?>pages/deals.php">OneCart Unified Shipping</a></li>
                        <li><a href="<?= $base_url ?>pages/orders.php">Carbon Neutral Delivery</a></li>
                        <li><a href="<?= $base_url ?>privacy.php">Privacy & Cookie Policy</a></li>
                        <li><a href="<?= $base_url ?>terms.php">Terms of Service</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="footer-bottom">
                <div>
                    &copy; <?= date('Y') ?> <?= SITE_NAME ?> Inc. All rights reserved. Built with PHP 8, Three.js & Vanilla JS.
                </div>
                <div class="payment-icons">
                    <span class="pay-pill">VISA</span>
                    <span class="pay-pill">MASTERCARD</span>
                    <span class="pay-pill">AMEX</span>
                    <span class="pay-pill">APPLE PAY</span>
                    <span class="pay-pill">GOOGLE PAY</span>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
