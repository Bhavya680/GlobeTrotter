<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid CSRF token. Please try again.";
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Email and password are required.";
        } else {
            if (login($email, $password)) {
                setFlash('success', 'Welcome back!');
                redirect('dashboard.php');
            } else {
                $error = "Invalid email or password.";
            }
        }
    }
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow" style="max-width: 400px; width: 100%; border-radius: 16px;">
        <div class="text-center mb-4">
            <i class="fa fa-globe text-primary fa-3x mb-2"></i>
            <h2>GlobeTrotter</h2>
            <div class="mt-3">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: #e9ecef; margin: 0 auto; border: 3px solid #2563EB;"></div>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="Email Address" value="<?= htmlspecialchars($email) ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('password').type = document.getElementById('password').type === 'password' ? 'text' : 'password'"><i class="fa fa-eye"></i></button>
                </div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="rememberMe">
                <label class="form-check-label" for="rememberMe">Remember Me</label>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
            <div class="text-center">
                <a href="register.php" class="text-decoration-none">Don't have an account? Sign Up</a><br>
                <a href="#" onclick="alert('Contact admin to reset')" class="text-decoration-none text-muted small">Forgot Password?</a>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
