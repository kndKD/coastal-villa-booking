<?php
/**
 * Serene Shores Coastal Villa - Guest Registration
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 * Security: Prepared Statements, Password Hashing, Session Fixation Protection
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Guest Registration | Serene Shores Coastal Villa';
$errors = [];
$name = '';
$email = '';
$contact = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    }

    $name     = trim((string)($_POST['name'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $contact  = trim((string)($_POST['contact_number'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');

    // Form Validations
    if (empty($name)) {
        $errors[] = 'Please enter your full name.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (empty($contact)) {
        $errors[] = 'Please provide a valid contact phone number.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must contain at least 6 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if email already exists
    if (empty($errors)) {
        $checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
        $checkStmt->execute([':email' => $email]);
        if ($checkStmt->fetch()) {
            $errors[] = 'An account with this email address already exists. Please log in instead.';
        }
    }

    // If valid, register new guest
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = 'guest';

        $insertStmt = $pdo->prepare(
            'INSERT INTO users (name, email, contact_number, password, role) 
             VALUES (:name, :email, :contact, :password, :role)'
        );

        $inserted = $insertStmt->execute([
            ':name'     => $name,
            ':email'    => $email,
            ':contact'  => $contact,
            ':password' => $hashedPassword,
            ':role'     => $role
        ]);

        if ($inserted) {
            $newUserId = (int)$pdo->lastInsertId();

            // Prevent session fixation
            session_regenerate_id(true);

            // Establish authenticated session
            $_SESSION['user_id']        = $newUserId;
            $_SESSION['name']           = $name;
            $_SESSION['email']          = $email;
            $_SESSION['contact_number'] = $contact;
            $_SESSION['role']           = $role;

            setFlash('success', 'Welcome to Serene Shores Villa, ' . $name . '! Your guest account has been successfully created.');
            header('Location: rooms.php');
            exit;
        } else {
            $errors[] = 'An unexpected server error occurred during registration. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper">
        <div class="auth-header">
            <h2>Create Your Account</h2>
            <p>Join Serene Shores Villa for exclusive coastal stay experiences</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <span class="alert-icon">&#9888;</span>
                <div class="alert-text">
                    <ul style="margin: 0; padding-left: 18px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="registerForm" novalidate>
            <?= csrfField(); ?>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="name">&#128100; Full Name</label>
                <input type="text" name="name" id="name" class="form-control" value="<?= e($name); ?>" required placeholder="e.g. Kasun Perera">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="email">&#9993; Email Address</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= e($email); ?>" required placeholder="e.g. kasun@example.com">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="contact_number">&#128222; Contact Phone Number</label>
                <input type="tel" name="contact_number" id="contact_number" class="form-control" value="<?= e($contact); ?>" required placeholder="e.g. +94 77 123 4567">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="password">&#128274; Password</label>
                <div class="password-input-group" style="position: relative; width: 100%;">
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Minimum 6 characters" style="padding-right: 42px;">
                    <button type="button" class="password-toggle password-toggle-btn" data-target="password" aria-label="Show password" title="Toggle password visibility" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: transparent; border: none; padding: 0; cursor: pointer; display: flex; align-items: center; color: #64748b;">
                        <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="eye-closed" style="display:none;" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label for="confirm_password">&#128274; Confirm Password</label>
                <div class="password-input-group" style="position: relative; width: 100%;">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="Re-type password" style="padding-right: 42px;">
                    <button type="button" class="password-toggle password-toggle-btn" data-target="confirm_password" aria-label="Show password" title="Toggle password visibility" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: transparent; border: none; padding: 0; cursor: pointer; display: flex; align-items: center; color: #64748b;">
                        <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="eye-closed" style="display:none;" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Complete Registration</button>
        </form>

        <div class="auth-footer">
            Already registered? <a href="login.php"><strong>Sign in here</strong></a>
        </div>
    </div>
</div>

<script>
(function() {
    function initPasswordToggles() {
        var toggleButtons = document.querySelectorAll('.password-toggle');
        toggleButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var input = button.previousElementSibling || (button.closest('.password-input-group') ? button.closest('.password-input-group').querySelector('input') : null);
                if (!input) return;

                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';

                var eyeOpen = button.querySelector('.eye-open');
                var eyeClosed = button.querySelector('.eye-closed');
                if (eyeOpen && eyeClosed) {
                    eyeOpen.style.display = isPassword ? 'none' : 'block';
                    eyeClosed.style.display = isPassword ? 'block' : 'none';
                }

                var label = isPassword ? 'Hide password' : 'Show password';
                button.setAttribute('aria-label', label);
                button.setAttribute('title', label);
                input.focus();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggles);
    } else {
        initPasswordToggles();
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
