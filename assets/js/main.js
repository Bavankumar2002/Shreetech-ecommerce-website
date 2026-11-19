/**
 * SHOPPULSE - Core Front-End Architecture & Animation Engine
 * Live search, fly-to-cart particle arc, drawer mini-cart, 3D card tilt, dark mode
 */

(function () {
    'use strict';

    // Global App State
    const App = {
        csrfToken: '',
        cartCount: 0,
        theme: localStorage.getItem('shoppulse_theme') || 'light'
    };

    /* ====================================================================
       1. THEME INITIALIZATION & TOGGLE (Default strictly to Light Mode)
       ==================================================================== */
    function initTheme() {
        let userExplicitChoice = localStorage.getItem('shoppulse_theme_custom');
        let currentTheme = (userExplicitChoice === 'dark') ? 'dark' : 'light';

        setTheme(currentTheme);

        document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem('shoppulse_theme_custom', next);
                setTheme(next);
            });
        });
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('shoppulse_theme', theme);
        App.theme = theme;
        
        // Update toggle icons
        document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
            btn.setAttribute('aria-label', `Switch to ${theme === 'dark' ? 'light' : 'dark'} mode`);
            btn.innerHTML = theme === 'dark' 
                ? `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>`
                : `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
        });
    }

    /* ====================================================================
       2. ANIMATED TYPING PLACEHOLDER (Search Hero)
       ==================================================================== */
    function initTypingPlaceholder() {
        const input = document.getElementById('hero-search-input');
        if (!input) return;

        const phrases = [
            "Search ANC wireless headphones...",
            "Search titanium smartwatches...",
            "Search 4K laser projectors...",
            "Search rolltop waterproof bags...",
            "Find deals with up to 8% cashback..."
        ];

        let phraseIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        let typingSpeed = 70;

        function type() {
            // Pause if user is currently focused/typing in the input
            if (document.activeElement === input && input.value.length > 0) {
                setTimeout(type, 1500);
                return;
            }

            const currentPhrase = phrases[phraseIndex];

            if (isDeleting) {
                input.placeholder = currentPhrase.substring(0, charIndex - 1);
                charIndex--;
                typingSpeed = 35;
            } else {
                input.placeholder = currentPhrase.substring(0, charIndex + 1);
                charIndex++;
                typingSpeed = 70;
            }

            if (!isDeleting && charIndex === currentPhrase.length) {
                typingSpeed = 2200; // Pause at end of phrase
                isDeleting = true;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                phraseIndex = (phraseIndex + 1) % phrases.length;
                typingSpeed = 500; // Pause before starting new phrase
            }

            setTimeout(type, typingSpeed);
        }

        setTimeout(type, 1000);
    }

    /* ====================================================================
       3. LIVE SEARCH WITH DEBOUNCE & DROPDOWN
       ==================================================================== */
    function initLiveSearch() {
        const searchInputs = document.querySelectorAll('.live-search-input');

        searchInputs.forEach(input => {
            const dropdownId = input.dataset.dropdownTarget;
            const dropdown = document.getElementById(dropdownId);
            if (!dropdown) return;

            let debounceTimer = null;

            input.addEventListener('input', (e) => {
                const query = e.target.value.trim();
                clearTimeout(debounceTimer);

                if (query.length < 2) {
                    dropdown.classList.remove('active');
                    dropdown.innerHTML = '';
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetchSearchResults(query, dropdown);
                }, 280);
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('active');
                }
            });

            // Re-open on focus if query present
            input.addEventListener('focus', () => {
                if (input.value.trim().length >= 2 && dropdown.children.length > 0) {
                    dropdown.classList.add('active');
                }
            });
        });
    }

    async function fetchSearchResults(query, dropdown) {
        try {
            dropdown.innerHTML = `<div style="padding:1rem; text-align:center; color:var(--text-muted); font-size:0.875rem;">Searching catalog for "${query}"...</div>`;
            dropdown.classList.add('active');

            const res = await fetch(`api/products.php?search=${encodeURIComponent(query)}&limit=5`);
            const data = await res.json();

            if (!data.products || data.products.length === 0) {
                dropdown.innerHTML = `<div style="padding:1.25rem; text-align:center; color:var(--text-muted); font-size:0.875rem;">No products found matching "<strong>${query}</strong>"</div>`;
                return;
            }

            let html = '';
            data.products.forEach(p => {
                html += `
                    <a href="pages/product.php?slug=${encodeURIComponent(p.slug)}" class="search-result-item">
                        <img src="${p.image_url}" alt="${p.name}" class="search-result-thumb" loading="lazy">
                        <div class="search-result-info">
                            <div class="search-result-title">${p.name}</div>
                            <div class="search-result-meta">
                                <span>${p.brand}</span>
                                <span>★ ${p.rating}</span>
                                <span class="search-result-price">$${parseFloat(p.price).toFixed(2)}</span>
                            </div>
                        </div>
                    </a>
                `;
            });

            html += `
                <a href="pages/categories.php?search=${encodeURIComponent(query)}" style="display:block; text-align:center; padding:0.75rem 0 0.25rem; font-size:0.8125rem; font-weight:700; color:var(--primary); border-top:1px solid var(--border-color); margin-top:0.5rem;">
                    View all matching results →
                </a>
            `;

            dropdown.innerHTML = html;
        } catch (err) {
            console.error('Search error:', err);
            dropdown.innerHTML = `<div style="padding:1rem; text-align:center; color:var(--accent); font-size:0.875rem;">Error loading search results</div>`;
        }
    }

    /* ====================================================================
       4. SUGGESTED CHIPS CLICK HANDLER
       ==================================================================== */
    function initSearchChips() {
        document.querySelectorAll('.search-chip').forEach(chip => {
            chip.addEventListener('click', (e) => {
                e.preventDefault();
                const term = chip.dataset.term || chip.textContent.trim();
                const heroInput = document.getElementById('hero-search-input');
                if (heroInput) {
                    heroInput.value = term;
                    heroInput.focus();
                    const heroDropdown = document.getElementById('hero-search-dropdown');
                    if (heroDropdown) {
                        fetchSearchResults(term, heroDropdown);
                    }
                } else {
                    window.location.href = `pages/categories.php?search=${encodeURIComponent(term)}`;
                }
            });
        });
    }

    /* ====================================================================
       5. FLY-TO-CART PARABOLIC ANIMATION & BADGE BOUNCE
       ==================================================================== */
    function animateFlyToCart(sourceImgElement) {
        const cartIcon = document.getElementById('header-cart-icon');
        if (!sourceImgElement || !cartIcon) return;

        const imgRect = sourceImgElement.getBoundingClientRect();
        const cartRect = cartIcon.getBoundingClientRect();

        const clone = sourceImgElement.cloneNode(true);
        clone.classList.add('flying-cart-clone');
        clone.style.width = '70px';
        clone.style.height = '70px';
        clone.style.left = `${imgRect.left + (imgRect.width / 2) - 35}px`;
        clone.style.top = `${imgRect.top + (imgRect.height / 2) - 35}px`;
        document.body.appendChild(clone);

        // Force browser reflow
        clone.getBoundingClientRect();

        // Parabolic arc destination
        const destX = cartRect.left + (cartRect.width / 2) - 15;
        const destY = cartRect.top + (cartRect.height / 2) - 15;

        clone.style.transform = `scale(0.35)`;
        clone.style.opacity = '0.4';
        clone.style.left = `${destX}px`;
        clone.style.top = `${destY}px`;

        setTimeout(() => {
            clone.remove();
            triggerBadgeBounce();
        }, 850);
    }

    function triggerBadgeBounce() {
        document.querySelectorAll('.badge-count').forEach(badge => {
            badge.classList.remove('badge-bounce');
            void badge.offsetWidth; // Reflow
            badge.classList.add('badge-bounce');
        });
    }

    /* ====================================================================
       6. CART SYSTEM & MINI-CART DRAWER
       ==================================================================== */
    function initCart() {
        // Toggle cart drawer
        const drawer = document.getElementById('cart-drawer');
        const backdrop = document.getElementById('cart-drawer-backdrop');
        const openBtns = document.querySelectorAll('.open-cart-btn');
        const closeBtns = document.querySelectorAll('.close-cart-btn');

        openBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                openCartDrawer();
            });
        });

        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                closeCartDrawer();
            });
        });

        if (backdrop) {
            backdrop.addEventListener('click', closeCartDrawer);
        }

        // Global Add-to-Cart Delegate
        document.addEventListener('click', (e) => {
            const addBtn = e.target.closest('.add-to-cart-trigger');
            if (addBtn) {
                e.preventDefault();
                const productId = addBtn.dataset.productId;
                const quantity = parseInt(addBtn.dataset.quantity || 1, 10);
                const card = addBtn.closest('.product-card') || document.querySelector('.product-detail-wrap');
                const img = card ? card.querySelector('.product-card-img, .product-main-img') : null;

                if (img) {
                    animateFlyToCart(img);
                }

                addToCart(productId, quantity);
            }
        });

        // Initial cart load
        refreshCart();
    }

    function openCartDrawer() {
        const drawer = document.getElementById('cart-drawer');
        const backdrop = document.getElementById('cart-drawer-backdrop');
        if (drawer && backdrop) {
            drawer.classList.add('active');
            backdrop.classList.add('active');
            document.body.style.overflow = 'hidden';
            refreshCart();
        }
    }

    function closeCartDrawer() {
        const drawer = document.getElementById('cart-drawer');
        const backdrop = document.getElementById('cart-drawer-backdrop');
        if (drawer && backdrop) {
            drawer.classList.remove('active');
            backdrop.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    async function addToCart(productId, quantity = 1) {
        try {
            const res = await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    product_id: productId,
                    quantity: quantity
                })
            });
            const data = await res.json();

            if (data.success) {
                showToast(data.message, 'success');
                updateCartUI(data.cart);
            } else {
                showToast(data.error || 'Could not add to cart', 'error');
            }
        } catch (err) {
            console.error('Add to cart failed:', err);
            showToast('Network error adding to cart', 'error');
        }
    }

    async function updateCartQty(productId, quantity) {
        try {
            const res = await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update',
                    product_id: productId,
                    quantity: quantity
                })
            });
            const data = await res.json();
            if (data.success) {
                updateCartUI(data.cart);
            } else {
                showToast(data.error || 'Failed to update quantity', 'error');
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function removeCartItem(productId) {
        try {
            const res = await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'remove',
                    product_id: productId
                })
            });
            const data = await res.json();
            if (data.success) {
                showToast('Item removed', 'info');
                updateCartUI(data.cart);
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function refreshCart() {
        try {
            const res = await fetch('api/cart.php?action=get');
            const data = await res.json();
            updateCartUI(data);
        } catch (err) {
            console.error('Error fetching cart:', err);
        }
    }

    function updateCartUI(cart) {
        if (!cart) return;

        // Update badge counts
        document.querySelectorAll('.cart-badge-val').forEach(el => {
            el.textContent = cart.total_items;
        });

        // Update drawer body
        const container = document.getElementById('cart-drawer-items');
        if (!container) return;

        if (cart.items.length === 0) {
            container.innerHTML = `
                <div style="text-align:center; padding: 3rem 1rem; color:var(--text-muted);">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 1rem; opacity:0.5;">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    <p style="font-weight:700; color:var(--text-primary); margin-bottom:0.5rem;">Your cart is empty</p>
                    <p style="font-size:0.875rem;">Discover top tech, audio & fashion with cashback rewards.</p>
                </div>
            `;
        } else {
            let html = '';
            cart.items.forEach(item => {
                html += `
                    <div class="cart-item-row" data-id="${item.id}">
                        <img src="${item.image_url}" alt="${item.name}" class="cart-item-img">
                        <div class="cart-item-details">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-price">$${parseFloat(item.price).toFixed(2)}</div>
                            <div class="cart-qty-ctrl">
                                <button type="button" class="qty-btn dec-btn" onclick="window.SHOPPULSE.updateQty(${item.id}, ${item.quantity - 1})">−</button>
                                <span>${item.quantity}</span>
                                <button type="button" class="qty-btn inc-btn" onclick="window.SHOPPULSE.updateQty(${item.id}, ${item.quantity + 1})">+</button>
                            </div>
                        </div>
                        <button type="button" class="remove-item-btn" onclick="window.SHOPPULSE.removeItem(${item.id})" title="Remove item">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        // Update drawer footer totals
        const subtotalEl = document.getElementById('cart-drawer-subtotal');
        if (subtotalEl) subtotalEl.textContent = `$${cart.subtotal.toFixed(2)}`;

        const totalEl = document.getElementById('cart-drawer-total');
        if (totalEl) totalEl.textContent = `$${cart.total.toFixed(2)}`;

        const cashbackEl = document.getElementById('cart-drawer-cashback');
        if (cashbackEl) cashbackEl.textContent = `$${cart.cashback_total.toFixed(2)}`;

        // Free shipping progress bar
        const shipProgressEl = document.getElementById('free-shipping-fill');
        const shipTextEl = document.getElementById('free-shipping-text');
        if (shipProgressEl && shipTextEl) {
            const pct = Math.min(100, (cart.subtotal / cart.free_shipping_threshold) * 100);
            shipProgressEl.style.width = `${pct}%`;
            if (cart.free_shipping_eligible) {
                shipTextEl.innerHTML = `<strong>Free Standard Shipping unlocked!</strong> 🎉`;
            } else {
                shipTextEl.innerHTML = `Add <strong>$${cart.amount_to_free_shipping.toFixed(2)}</strong> more for <strong>Free Shipping</strong>`;
            }
        }
    }

    /* ====================================================================
       7. DEALS COUNTDOWN TIMER
       ==================================================================== */
    function initDealsCountdown() {
        const timerContainer = document.querySelector('.deals-countdown');
        if (!timerContainer) return;

        // Set target date 24 hours from session or fixed interval
        let targetTime = Date.now() + (23 * 3600 * 1000) + (44 * 60 * 1000) + (18 * 1000);

        function updateCountdown() {
            const now = Date.now();
            const diff = Math.max(0, targetTime - now);

            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            const hEl = document.getElementById('countdown-hours');
            const mEl = document.getElementById('countdown-minutes');
            const sEl = document.getElementById('countdown-seconds');

            if (hEl) hEl.textContent = String(hours).padStart(2, '0');
            if (mEl) mEl.textContent = String(minutes).padStart(2, '0');
            if (sEl) sEl.textContent = String(seconds).padStart(2, '0');
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    /* ====================================================================
       8. 3D CARD TILT MICRO-INTERACTION
       ==================================================================== */
    function initCardTilt() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const cards = document.querySelectorAll('.tilt-card');
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const rotateX = ((y - centerY) / centerY) * -6; // Max 6 deg
                const rotateY = ((x - centerX) / centerX) * 6;

                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px)`;
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0px)';
            });
        });
    }

    /* ====================================================================
       9. SCROLL REVEAL (IntersectionObserver)
       ==================================================================== */
    function initScrollReveal() {
        const reveals = document.querySelectorAll('.reveal-on-scroll');
        if (!reveals.length) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        reveals.forEach(el => observer.observe(el));
    }

    /* ====================================================================
       10. TOAST NOTIFICATIONS SYSTEM
       ==================================================================== */
    function showToast(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const iconSvg = type === 'success' 
            ? `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>`
            : `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

        toast.innerHTML = `${iconSvg} <span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3200);
    }

    /* ====================================================================
       11. NEWSLETTER SUBSCRIPTION HANDLER
       ==================================================================== */
    function initNewsletter() {
        const form = document.getElementById('newsletter-form');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const emailInput = form.querySelector('input[type="email"]');
            if (emailInput && emailInput.value) {
                showToast('Thank you for subscribing! Your 10% coupon is in your inbox.', 'success');
                emailInput.value = '';
            }
        });
    }

    /* ====================================================================
       GLOBAL EXPORTS FOR INLINE LISTENERS
       ==================================================================== */
    window.SHOPPULSE = {
        updateQty: updateCartQty,
        removeItem: removeCartItem,
        openCart: openCartDrawer,
        closeCart: closeCartDrawer,
        showToast: showToast
    };

    // DOM Ready execution
    document.addEventListener('DOMContentLoaded', () => {
        initTheme();
        initTypingPlaceholder();
        initLiveSearch();
        initSearchChips();
        initCart();
        initDealsCountdown();
        initCardTilt();
        initScrollReveal();
        initNewsletter();
    });

})();
