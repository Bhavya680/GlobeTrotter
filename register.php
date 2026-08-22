<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: /dashboard.php');
    exit;
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_str($_POST['name'] ?? '');
    $email = strtolower(clean_str($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $missing = missing_fields(['name' => $name, 'email' => $email, 'password' => $password], ['name', 'email', 'password']);
    if ($missing) {
        $errors[] = 'Please fill in all fields.';
    }
    if ($email !== '' && !is_valid_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (!$errors) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                INSERT INTO users (name, email, password_hash)
                VALUES (?, ?, ?)
                RETURNING id
            ');
            $stmt->execute([$name, $email, $passwordHash]);
            $newUserId = (int) $stmt->fetchColumn();

            login_user($newUserId);
            header('Location: /dashboard.php');
            exit;
        } catch (PDOException $e) {
            error_log('[GlobeTrotter] register.php insert failed: ' . $e->getMessage());
            $errors[] = 'An account with that email already exists.';
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
    <div class="container d-flex justify-content-center">
        <div class="auth-card register-card">
            <div class="auth-logo">
                <i class="fa-solid fa-globe"></i> Create Account
            </div>
            
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="registerForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" id="first_name" name="first_name" value="<?= htmlspecialchars($formData['first_name']) ?>" required minlength="2">
                        <?php if (isset($errors['first_name'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['first_name']) ?></div>
                        <?php endif; ?>
                        <div class="invalid-feedback client-error">Please enter at least 2 characters.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" id="last_name" name="last_name" value="<?= htmlspecialchars($formData['last_name']) ?>" required minlength="2">
                        <?php if (isset($errors['last_name'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['last_name']) ?></div>
                        <?php endif; ?>
                        <div class="invalid-feedback client-error">Please enter at least 2 characters.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['email']) ?></div>
                        <?php endif; ?>
                        <div class="invalid-feedback client-error">Please enter a valid email.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" value="<?= htmlspecialchars($formData['phone']) ?>" pattern="^[0-9\+\-\s]+$">
                        <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['phone']) ?></div>
                        <?php endif; ?>
                        <div class="invalid-feedback client-error">Numbers and + - only.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required minlength="8">
                        
                        <!-- Password Strength Indicator -->
                        <div class="password-strength-bar">
                            <div class="password-strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="password-strength-text" id="strengthText"></div>

                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password']) ?></div>
                        <?php endif; ?>
                        <div class="invalid-feedback client-error">Min 8 chars, 1 uppercase, 1 number.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                        <?php endif; ?>
                        <div class="invalid-feedback client-error">Passwords must match.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($formData['city']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country" value="<?= htmlspecialchars($formData['country']) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="additional_info" class="form-label">Additional Information</label>
                    <textarea class="form-control" id="additional_info" name="additional_info" rows="2"><?= htmlspecialchars($formData['additional_info']) ?></textarea>
                </div>

                <div class="mb-4 text-center">
                    <label class="form-label d-block text-start">Profile Photo</label>
                    <div class="avatar-placeholder" id="photoPreviewContainer">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                    <input class="form-control form-control-sm <?= isset($errors['profile_photo']) ? 'is-invalid' : '' ?>" type="file" id="profile_photo" name="profile_photo" accept="image/jpeg, image/png, image/webp">
                    <?php if (isset($errors['profile_photo'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['profile_photo']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100" id="submitBtn">Register</button>
            </form>

            <div class="auth-links mt-4">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const photoInput = document.getElementById('profile_photo');
    const photoPreviewContainer = document.getElementById('photoPreviewContainer');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');

    // Photo Preview
    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreviewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`;
            }
            reader.readAsDataURL(file);
        } else {
            photoPreviewContainer.innerHTML = '<i class="fa-solid fa-camera"></i>';
        }
    });

    // Password Strength
    passwordInput.addEventListener('input', function() {
        const val = this.value;
        let score = 0;
        
        if (val.length > 0) score++;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++; // bonus for special char

        strengthFill.className = 'password-strength-fill'; // reset
        
        if (val.length === 0) {
            strengthFill.style.width = '0';
            strengthText.textContent = '';
        } else if (score < 3 || val.length < 8) {
            strengthFill.classList.add('strength-weak');
            strengthText.textContent = 'Weak';
        } else if (score === 3 || score === 4) {
            strengthFill.classList.add('strength-medium');
            strengthText.textContent = 'Medium';
        } else {
            strengthFill.classList.add('strength-strong');
            strengthText.textContent = 'Strong';
        }
        
        // Custom validity for requirements
        if (val.length < 8 || !/[A-Z]/.test(val) || !/[0-9]/.test(val)) {
            this.setCustomValidity("Min 8 chars, 1 uppercase, 1 number");
        } else {
            this.setCustomValidity("");
        }
        
        // Re-check confirm password
        if (confirmPasswordInput.value) {
            validateConfirmPassword();
        }
    });

    function validateConfirmPassword() {
        if (confirmPasswordInput.value !== passwordInput.value) {
            confirmPasswordInput.setCustomValidity("Passwords do not match");
        } else {
            confirmPasswordInput.setCustomValidity("");
        }
    }

    confirmPasswordInput.addEventListener('input', validateConfirmPassword);

    // Form validation visuals on blur/input
    const inputs = registerForm.querySelectorAll('input[required], input[pattern]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (!this.checkValidity()) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });

    // Submit handler
    registerForm.addEventListener('submit', function(event) {
        validateConfirmPassword();
        
        if (!registerForm.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            inputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                }
            });
        } else {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Registering...';
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
