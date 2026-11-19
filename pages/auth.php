<?php
/**
 * SHOPPULSE Authentication & Account Portal
 * Sign In, Sign Up, Session Security, CSRF Tokens, Bcrypt Password Hashing
 */

require_once __DIR__ . '/../includes/db.php';

$pdo = get_db();
$user = $_SESSION['user'] ?? null;
$message = '';
$error = '';

// Handle Direct POST Auth
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean_input($_POST['action'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        if ($action === 'login') {
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $password = (string)($_POST['password'] ?? '');

            if (!$email || empty($password)) {
                $error = 'Please enter a valid email address and password.';
            } else {
                $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $found = $stmt->fetch();

                if ($found && password_verify($password, $found['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id' => (int)$found['id'],
                        'name' => $found['name'],
                        'email' => $found['email'],
                        'role' => $found['role']
                    ];
                    $user = $_SESSION['user'];
                    $message = 'Signed in successfully!';
                } else {
                    $error = 'Invalid email or password. (Demo: customer@shoppulse.com / password123)';
                }
            }
        } elseif ($action === 'register') {
            $name = clean_input($_POST['name'] ?? '');
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $password = (string)($_POST['password'] ?? '');

            if (empty($name) || strlen($name) < 2) {
                $error = 'Please enter your full name.';
            } elseif (!$email) {
                $error = 'Please provide a valid email address.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                if ($stmt->fetch()) {
                    $error = 'This email is already registered. Please sign in instead.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $ins = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, 'customer')");
                    $ins->execute(['name' => $name, 'email' => $email, 'hash' => $hash]);

                    $newId = (int)$pdo->lastInsertId();
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id' => $newId,
                        'name' => $name,
                        'email' => $email,
                        'role' => 'customer'
                    ];
                    $user = $_SESSION['user'];
                    $message = 'Welcome to ShopPulse! Your account is ready.';
                }
            }
        } elseif ($action === 'logout') {
            unset($_SESSION['user']);
            $user = null;
            $message = 'You have been signed out.';
        }
    }
}

$page_title = $user ? "My Account Portal" : "Sign In or Register | " . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-block: 3rem 6rem;">
    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" style="font-size:0.875rem; color:var(--text-muted); margin-bottom:2rem; display:flex; gap:0.5rem;">
        <a href="../index.php" style="color:var(--text-secondary);">Home</a>
        <span>/</span>
        <span style="color:var(--text-primary); font-weight:600;">Account</span>
    </nav>

    <?php if ($message): ?>
        <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:1rem 1.5rem; border-radius:var(--radius-md); margin-bottom:2rem; max-width:540px; margin-inline:auto;">
            ✓ <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:#fff1f2; border:1px solid #fecdd3; color:#9f1239; padding:1rem 1.5rem; border-radius:var(--radius-md); margin-bottom:2rem; max-width:540px; margin-inline:auto;">
            ⚠ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($user): ?>
        <!-- Logged In Account Dashboard -->
        <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:3rem; max-width:680px; margin-inline:auto; box-shadow:var(--shadow-card);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; border-bottom:1px solid var(--border-color); padding-bottom:1.5rem;">
                <div style="display:flex; align-items:center; gap:1.25rem;">
                    <div style="width:60px; height:60px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.5rem; font-weight:800;">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h1 style="font-size:1.5rem; font-weight:800; color:var(--text-primary); margin-bottom:0.25rem;">
                            Hello, <?= htmlspecialchars($user['name']) ?>
                        </h1>
                        <span style="font-size:0.875rem; color:var(--text-muted);"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="logout">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" style="background:var(--bg-page); border:1px solid var(--border-color); color:var(--text-secondary); font-size:0.8125rem; font-weight:700; padding:0.5rem 1rem; border-radius:var(--radius-pill);">
                        Sign Out
                    </button>
                </form>
            </div>

            <!-- Dashboard Quick Links -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                <a href="orders.php" style="background:var(--bg-page); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; display:block;">
                    <div style="font-size:1.5rem; margin-bottom:0.5rem;">📦</div>
                    <strong style="font-size:1.0625rem; color:var(--text-primary); display:block; margin-bottom:0.25rem;">Track Shipments</strong>
                    <span style="font-size:0.8125rem; color:var(--text-muted);">View live delivery milestones</span>
                </a>

                <a href="deals.php" style="background:var(--bg-page); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; display:block;">
                    <div style="font-size:1.5rem; margin-bottom:0.5rem;">💎</div>
                    <strong style="font-size:1.0625rem; color:var(--text-primary); display:block; margin-bottom:0.25rem;">Cashback Hub</strong>
                    <span style="font-size:0.8125rem; color:var(--text-muted);">View rewards & flash deals</span>
                </a>
            </div>

            <a href="categories.php" class="card-add-btn" style="text-align:center; padding:0.85rem;">
                Browse Store Catalog →
            </a>
        </div>

    <?php else: ?>

        <!-- Sign In / Register Tabs Box -->
        <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2.5rem; max-width:480px; margin-inline:auto; box-shadow:var(--shadow-card);">
            
            <div style="display:flex; border-bottom:2px solid var(--border-color); margin-bottom:2rem;">
                <button type="button" id="tab-login" onclick="toggleAuthTab('login')" style="flex:1; padding:0.75rem; font-weight:800; font-size:1rem; color:var(--primary); border-bottom:2px solid var(--primary); margin-bottom:-2px;">
                    Sign In
                </button>
                <button type="button" id="tab-register" onclick="toggleAuthTab('register')" style="flex:1; padding:0.75rem; font-weight:700; font-size:1rem; color:var(--text-muted); margin-bottom:-2px;">
                    Create Account
                </button>
            </div>

            <!-- Login Form -->
            <form id="form-login" method="POST">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div style="margin-bottom:1.25rem;">
                    <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Email Address</label>
                    <input type="email" name="email" required value="customer@shoppulse.com" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; color:var(--text-primary);">
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Password</label>
                    <input type="password" name="password" required value="password123" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; color:var(--text-primary);">
                    <span style="font-size:0.75rem; color:var(--text-muted); margin-top:0.3rem; display:block;">Demo Account: <code>customer@shoppulse.com</code> / <code>password123</code></span>
                </div>

                <button type="submit" class="checkout-btn magnetic-btn" style="padding:0.85rem; font-size:1rem;">
                    Sign In to Account
                </button>
            </form>

            <!-- Register Form -->
            <form id="form-register" method="POST" style="display:none;">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div style="margin-bottom:1.25rem;">
                    <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Full Name</label>
                    <input type="text" name="name" required placeholder="Jane Doe" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; color:var(--text-primary);">
                </div>

                <div style="margin-bottom:1.25rem;">
                    <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Email Address</label>
                    <input type="email" name="email" required placeholder="jane@example.com" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; color:var(--text-primary);">
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); display:block; margin-bottom:0.4rem;">Create Password</label>
                    <input type="password" name="password" required minlength="6" placeholder="At least 6 characters" style="width:100%; background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.75rem; color:var(--text-primary);">
                </div>

                <button type="submit" class="checkout-btn magnetic-btn" style="padding:0.85rem; font-size:1rem;">
                    Create Free Account
                </button>
            </form>

        </div>

    <?php endif; ?>
</div>

<script>
function toggleAuthTab(tab) {
    const loginBtn = document.getElementById('tab-login');
    const registerBtn = document.getElementById('tab-register');
    const loginForm = document.getElementById('form-login');
    const registerForm = document.getElementById('form-register');

    if (tab === 'login') {
        loginBtn.style.color = 'var(--primary)';
        loginBtn.style.borderBottom = '2px solid var(--primary)';
        registerBtn.style.color = 'var(--text-muted)';
        registerBtn.style.borderBottom = 'none';
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
    } else {
        registerBtn.style.color = 'var(--primary)';
        registerBtn.style.borderBottom = '2px solid var(--primary)';
        loginBtn.style.color = 'var(--text-muted)';
        loginBtn.style.borderBottom = 'none';
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
