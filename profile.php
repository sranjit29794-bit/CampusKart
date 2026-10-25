<?php
/**
 * CampusKart - Customer Profile & Account Settings
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_auth('profile.php');

$pdo = get_db_connection();
$sessionUser = current_user();
$userId = (int)$sessionUser['id'];

// Fetch latest user details from DB
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    logout_user();
    redirect('login.php');
}

// Fetch order stats for this customer
$statStmt = $pdo->prepare("
    SELECT COUNT(id) AS total_orders, COALESCE(SUM(total_amount), 0) AS total_spend
    FROM orders
    WHERE user_id = ?
");
$statStmt->execute([$userId]);
$stats = $statStmt->fetch();

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($name === '') {
            $errors[] = 'Full name cannot be blank.';
        }

        if (empty($errors)) {
            $upStmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $upStmt->execute([$name, $phone, $userId]);
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['phone'] = $phone;
            set_flash('success', 'Profile information updated successfully.');
            redirect('profile.php');
        }
    }

    if ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPass, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if (strlen($newPass) < 6) {
            $errors[] = 'New password must be at least 6 characters long.';
        }

        if ($newPass !== $confirmPass) {
            $errors[] = 'New password confirmation does not match.';
        }

        if (empty($errors)) {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $passStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $passStmt->execute([$newHash, $userId]);
            set_flash('success', 'Your password has been changed.');
            redirect('profile.php');
        }
    }
}

$pageTitle = 'My Profile - CampusKart';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <strong>Profile</strong>
        </nav>
        <h1 class="page-title">My Account</h1>
    </div>
</section>

<main class="container" style="padding: 3.5rem 1.5rem 5rem;">
    <div style="display:grid; grid-template-columns:300px 1fr; gap:3rem; align-items:start;">
        <!-- Left: Summary Card -->
        <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:2rem; text-align:center;">
            <div style="width:72px; height:72px; background:var(--bg-subtle); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem; font-family:var(--font-display); font-size:1.8rem; font-weight:700; color:var(--text-main);">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <h2 style="font-family:var(--font-display); font-size:1.35rem; margin-bottom:0.25rem;"><?= sanitize($user['name']) ?></h2>
            <p style="color:#666; font-size:0.88rem; margin-bottom:1.5rem;"><?= sanitize($user['email']) ?></p>

            <div style="border-top:1px solid var(--border-light); padding-top:1.25rem; display:grid; grid-template-columns:1fr 1fr; gap:1rem; text-align:center;">
                <div>
                    <span style="font-size:0.75rem; text-transform:uppercase; color:#888; font-weight:700;">Orders</span>
                    <div style="font-family:var(--font-display); font-size:1.4rem; font-weight:700; color:#111;"><?= (int)$stats['total_orders'] ?></div>
                </div>
                <div>
                    <span style="font-size:0.75rem; text-transform:uppercase; color:#888; font-weight:700;">Spent</span>
                    <div style="font-family:var(--font-display); font-size:1.4rem; font-weight:700; color:#111;"><?= format_price((float)$stats['total_spend']) ?></div>
                </div>
            </div>

            <div style="margin-top:2rem; display:flex; flex-direction:column; gap:0.5rem;">
                <a href="orders.php" class="btn-dark" style="padding:0.75rem; font-size:0.85rem; width:100%;">VIEW MY ORDERS</a>
                <?php if (is_admin()): ?>
                    <a href="admin/index.php" class="btn-primary" style="padding:0.75rem; font-size:0.85rem; width:100%;">ADMIN PORTAL</a>
                <?php endif; ?>
                <a href="logout.php" style="color:#e53935; font-size:0.85rem; margin-top:0.5rem; font-weight:600;">Sign Out</a>
            </div>
        </div>

        <!-- Right: Profile Settings Forms -->
        <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:2.5rem;">
            <?php if (!empty($errors)): ?>
                <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:0.85rem 1.25rem; border-radius:var(--radius-sm); margin-bottom:1.75rem; font-size:0.88rem;">
                    <ul style="margin:0; padding-left:1.25rem;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= sanitize($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <h3 style="font-family:var(--font-display); font-size:1.4rem; margin-bottom:1.25rem;">Personal Information</h3>
            <form action="profile.php" method="POST" style="margin-bottom:3rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="form-grid" style="margin-bottom:1.5rem;">
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= sanitize($user['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email (Account ID)</label>
                        <input type="email" id="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled style="background:#f9f9f9; cursor:not-allowed;">
                    </div>
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="<?= sanitize($user['phone']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Role</label>
                        <input type="text" class="form-control" value="<?= ucfirst($user['role']) ?>" disabled style="background:#f9f9f9; text-transform:capitalize;">
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="padding:0.75rem 1.75rem;">SAVE CHANGES</button>
            </form>

            <h3 style="font-family:var(--font-display); font-size:1.4rem; margin-bottom:1.25rem; border-top:1px solid var(--border-light); padding-top:2rem;">Change Password</h3>
            <form action="profile.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="form-group" style="margin-bottom:1.25rem; max-width:400px;">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" required autocomplete="current-password">
                </div>

                <div class="form-grid" style="margin-bottom:1.5rem;">
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password (Min 6 chars)</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" class="btn-dark" style="padding:0.75rem 1.75rem;">UPDATE PASSWORD</button>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
