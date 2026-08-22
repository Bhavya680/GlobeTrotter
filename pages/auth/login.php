<?php
require_once __DIR__ . '/../../includes/auth.php';

if (is_logged_in()) {
    header('Location: /dashboard.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(clean_str($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errors['login'] = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors['login'] = 'Invalid email or password.';
        } else {
            login_user((int) $user['id']);

            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $update->execute([$newHash, $user['id']]);
            }

            header('Location: dashboard.php');
            exit;
        }
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="auth-page">
    <div class="container d-flex justify-content-center">
        <div class="auth-card">
            <div class="auth-logo">
                <i class="fa-solid fa-globe"></i> GlobeTrotter
            </div>
            
            <div class="avatar-placeholder">
                <i class="fa-solid fa-user"></i>
            </div>

            <?php if (isset($errors['login'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($errors['login']) ?>
                </div>
            <?php endif; ?>

            <div class="demo-login-box bg-light border rounded p-3 mb-3 text-center">
                <div class="small fw-bold text-muted text-uppercase mb-2"><i class="fa-solid fa-bolt text-warning me-1"></i> Quick Demo Login</div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm flex-fill demo-fill-btn" data-email="admin@globetrotter.dev" data-pass="Admin@123">
                        <i class="fa-solid fa-user-shield me-1"></i> Admin User
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm flex-fill demo-fill-btn" data-email="traveler@globetrotter.dev" data-pass="Traveler@123">
                        <i class="fa-solid fa-plane-departure me-1"></i> Demo Traveler
                    </button>
                </div>
            </div>

            <form action="login.php" method="POST" id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                        <?php endif; ?>
                        <div class="invalid-feedback client-error">Please enter a valid email address.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password']) ?></div>
                        <?php endif; ?>
                        <div class="invalid-feedback client-error">Password is required.</div>
                    </div>
                </div>

                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember">
                        <label class="form-check-label" for="rememberMe">
                            Remember Me
                        </label>
                    </div>
                    <a href="#" onclick="alert('Contact admin to reset password.'); return false;" class="text-decoration-none small">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100" id="submitBtn">Login</button>
            </form>

            <div class="auth-links mt-4">
                <p>Don't have an account? <a href="register.php">Sign Up</a></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');

    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fa-solid fa-eye"></i>' : '<i class="fa-solid fa-eye-slash"></i>';
    });

    // Real-time validation
    const inputs = loginForm.querySelectorAll('input[required]');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                // Don't show error immediately while typing, only if invalid after blur or submit
            }
        });
        
        input.addEventListener('blur', function() {
            if (!this.checkValidity()) {
                this.classList.add('is-invalid');
            }
        });
    });

    // Quick demo credentials fill buttons
    document.querySelectorAll('.demo-fill-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            if (emailInput && passInput) {
                emailInput.value = btn.dataset.email;
                passInput.value = btn.dataset.pass;
                emailInput.classList.remove('is-invalid');
                emailInput.classList.add('is-valid');
                passInput.classList.remove('is-invalid');
                passInput.classList.add('is-valid');
                if (typeof toast === 'function') {
                    toast('Credentials filled for ' + btn.textContent.trim(), 'success');
                }
            }
        });
    });

    // Form submission validation
    loginForm.addEventListener('submit', function(event) {
        if (!loginForm.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            inputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                }
            });
        } else {
            // Disable button to prevent double submit
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
