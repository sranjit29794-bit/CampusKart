<?php
/**
 * CampusKart - User Registration
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (is_logged_in()) {
    redirect('shop.php');
}

$pdo = get_db_connection();
$errors = [];
$nameInput = '';
$emailInput = '';
$phoneInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $nameInput = $name;
    $emailInput = $email;
    $phoneInput = $phone;

    // Validation
    if ($name === '' || strlen($name) < 2) {
        $errors[] = 'Please enter your full name.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($phone === '' || strlen($phone) < 7) {
        $errors[] = 'Please enter a valid phone number.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    // Check duplicate email
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email address already exists. Please sign in instead.';
        }
    }

    // Insert new customer record
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $insertStmt = $pdo->prepare("
            INSERT INTO users (name, email, phone, password_hash, role, created_at)
            VALUES (?, ?, ?, ?, 'customer', NOW())
        ");
        
        try {
            $insertStmt->execute([$name, $email, $phone, $passwordHash]);
            $newUserId = (int)$pdo->lastInsertId();

            // Auto-login newly registered user
            login_user([
                'id'    => $newUserId,
                'name'  => $name,
                'email' => $email,
                'phone' => $phone,
                'role'  => 'customer'
            ]);

            set_flash('success', 'Your CampusKart account has been successfully registered!');
            redirect('shop.php');
        } catch (Exception $e) {
            $errors[] = 'A database error occurred while creating your account. Please try again.';
        }
    }
}

$pageTitle = 'Create Account - CampusKart';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 4rem 1.5rem 6rem; max-width: 520px;">
    <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:2.5rem; box-shadow:0 10px 30px rgba(0,0,0,0.04);">
        <h1 style="font-family:var(--font-display); font-size:2rem; font-weight:700; margin-bottom:0.5rem; text-align:center;">Register Account</h1>
        <p style="text-align:center; color:#666; font-size:0.92rem; margin-bottom:2rem;">Join CampusKart to order campus essentials, enjoy student discounts, and track delivery.</p>

        <?php if (!empty($errors)): ?>
            <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:0.85rem 1.25rem; border-radius:var(--radius-sm); margin-bottom:1.5rem; font-size:0.88rem;">
                <ul style="margin:0; padding-left:1.25rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= sanitize($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <?= csrf_field() ?>

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label for="name" class="form-label">Full Name *</label>
                <input type="text" name="name" id="name" class="form-control" value="<?= sanitize($nameInput) ?>" placeholder="e.g. Rohan Sharma" required>
            </div>

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label for="email" class="form-label">College / Personal Email *</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= sanitize($emailInput) ?>" placeholder="e.g. rohan@college.edu" required>
            </div>

            <div class="form-group" style="margin-bottom:1.25rem;">
                <label for="phone" class="form-label">Phone Number *</label>
                <input type="tel" name="phone" id="phone" class="form-control" value="<?= sanitize($phoneInput) ?>" placeholder="e.g. +91 98123 45678" required>
            </div>

            <div class="form-grid" style="margin-bottom:1.75rem;">
                <div class="form-group">
                    <label for="password" class="form-label">Password *</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Min 6 characters" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat password" required autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width:100%; padding:0.9rem;">CREATE ACCOUNT</button>
        </form>

        <p style="text-align:center; font-size:0.9rem; margin-top:2rem; color:#666;">
            Already registered? <a href="login.php" style="color:var(--accent-red); font-weight:700;">Sign In Instead</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
