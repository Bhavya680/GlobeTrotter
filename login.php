<?php
require_once __DIR__ . '/includes/header.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    }

    if (empty($errors)) {
        if (login($email, $password)) {
            redirect('dashboard.php');
        } else {
            $errors['login'] = 'Invalid email or password.';
        }
    }
}
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

            <form action="login.php" method="POST" id="loginForm" novalidate>
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

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
