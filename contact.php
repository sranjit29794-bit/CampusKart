<?php
/**
 * CampusKart - Contact Page & Campus Support Desk
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

$sent = false;
$name = '';
$email = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $message) {
        $sent = true;
        set_flash('success', 'Your inquiry has been logged. The campus representative will get back to you shortly.');
    }
}

$pageTitle = 'Contact Campus Support - CampusKart';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <strong>Contact</strong>
        </nav>
        <h1 class="page-title">Campus Support</h1>
    </div>
</section>

<main class="container" style="padding: 4rem 1.5rem 6rem; max-width: 1000px;">
    <div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:3.5rem; align-items:start;">
        <!-- Contact Form -->
        <div style="background:#fff; border:1px solid var(--border-light); border-radius:var(--radius-sm); padding:2.5rem;">
            <h2 style="font-family:var(--font-display); font-size:1.6rem; margin-bottom:0.5rem; color:#111;">Leave a Message</h2>
            <p style="color:#666; font-size:0.92rem; margin-bottom:2rem;">Have a question about a product, bulk laboratory orders, or hostel delivery timing?</p>

            <?php if ($sent): ?>
                <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:1.25rem; border-radius:var(--radius-sm); margin-bottom:1.5rem;">
                    <strong>Thank you!</strong> Your message has been received by the student support desk.
                </div>
            <?php endif; ?>

            <form action="contact.php" method="POST">
                <?= csrf_field() ?>

                <div class="form-grid" style="margin-bottom:1.25rem;">
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= sanitize($name) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">College Email *</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?= sanitize($email) ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" name="subject" id="subject" class="form-control" value="<?= sanitize($subject) ?>" placeholder="e.g. Order Tracking or Sizing Question" required>
                </div>

                <div class="form-group" style="margin-bottom:1.75rem;">
                    <label for="message" class="form-label">Message *</label>
                    <textarea name="message" id="message" rows="4" class="form-control" required placeholder="Write your query here..."><?= sanitize($message) ?></textarea>
                </div>

                <button type="submit" class="btn-primary" style="padding:0.85rem 2rem;">SEND MESSAGE</button>
            </form>
        </div>

        <!-- Campus Contact Details -->
        <div style="background:var(--bg-subtle); border-radius:var(--radius-sm); padding:2.5rem;">
            <h3 style="font-family:var(--font-display); font-size:1.35rem; margin-bottom:1.25rem; color:#111;">Support Centers</h3>
            
            <div style="margin-bottom:1.75rem;">
                <strong style="display:block; color:#111; margin-bottom:0.25rem;">Main Campus Distribution Hub</strong>
                <p style="color:#666; font-size:0.9rem; line-height:1.6;">
                    Ground Floor, Student Activities Center<br>
                    Gate 2, University Main Campus Road<br>
                    Bangalore, Karnataka 560064
                </p>
            </div>

            <div style="margin-bottom:1.75rem;">
                <strong style="display:block; color:#111; margin-bottom:0.25rem;">Hours of Operation</strong>
                <p style="color:#666; font-size:0.9rem; line-height:1.6;">
                    Monday – Saturday: 8:30 AM – 9:00 PM<br>
                    Sunday: 10:00 AM – 6:00 PM<br>
                    Hostel Express Delivery: 24/7 during Exam Weeks
                </p>
            </div>

            <div style="border-top:1px solid #ddd; padding-top:1.25rem;">
                <strong style="display:block; color:#111; margin-bottom:0.25rem;">Direct Helplines</strong>
                <p style="color:#666; font-size:0.9rem; line-height:1.6;">
                    <strong>Desk Phone:</strong> +91 98765 43210<br>
                    <strong>Email:</strong> helpdesk@campuskart.edu
                </p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
