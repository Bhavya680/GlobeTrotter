<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$form_data = [
    'first_name' => '', 'last_name' => '', 'email' => '', 
    'phone' => '', 'city' => '', 'country' => '', 'additional_info' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    } else {
        foreach ($form_data as $key => $val) {
            $form_data[$key] = sanitize($_POST[$key] ?? '');
        }
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (strlen($form_data['first_name']) < 2) $errors[] = "First name must be at least 2 characters.";
        if (strlen($form_data['last_name']) < 2) $errors[] = "Last name must be at least 2 characters.";
        if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
        
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must be at least 8 characters, with 1 uppercase and 1 number.";
        }
        if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
        if (!empty($form_data['phone']) && !is_numeric($form_data['phone'])) $errors[] = "Phone must be numeric.";

        // Unique email check
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$form_data['email']]);
        if ($stmt->fetch()) {
            $errors[] = "Email is already registered.";
        }

        if (empty($errors)) {
            $profile_photo = null;
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $profile_photo = uploadFile($_FILES['profile_photo'], 'profiles');
                if (!$profile_photo) {
                    $errors[] = "Failed to upload profile photo. Ensure it is a valid image under 2MB.";
                }
            }
            
            if (empty($errors)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, phone, city, country, profile_photo, additional_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([
                    $form_data['first_name'], $form_data['last_name'], $form_data['email'], 
                    $hash, $form_data['phone'], $form_data['city'], $form_data['country'], 
                    $profile_photo, $form_data['additional_info']
                ])) {
                    login($form_data['email'], $password);
                    setFlash('success', 'Registration successful! Welcome to GlobeTrotter.');
                    redirect('dashboard.php');
                } else {
                    $errors[] = "A database error occurred.";
                }
            }
        }
    }
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>
<div class="container py-5">
    <div class="card shadow mx-auto" style="max-width: 600px; border-radius: 16px;">
        <div class="card-body">
            <h3 class="text-center mb-4"><i class="fa fa-globe text-primary"></i> Create an Account</h3>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach($errors as $err) echo "<li>".htmlspecialchars($err)."</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <div class="text-center mb-4">
                    <div id="photo-preview" style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid #2563EB; background: #e9ecef; margin: 0 auto; background-size: cover; background-position: center;"></div>
                    <label class="btn btn-sm btn-outline-primary mt-2">
                        Upload Photo <input type="file" name="profile_photo" hidden accept="image/*" onchange="document.getElementById('photo-preview').style.backgroundImage = 'url(' + window.URL.createObjectURL(this.files[0]) + ')'">
                    </label>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($form_data['first_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($form_data['last_name']) ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($form_data['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($form_data['phone']) ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($form_data['city']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($form_data['country']) ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Additional Information</label>
                    <textarea name="additional_info" class="form-control" rows="3"><?= htmlspecialchars($form_data['additional_info']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn" onclick="this.disabled=true;this.form.submit();">Register</button>
                
                <div class="text-center">
                    <a href="login.php" class="text-decoration-none">Already have an account? Login</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
