<?php
/**
 * Serene Shores Coastal Villa - User & Admin Login
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 * Security: Prepared Statements, password_verify(), session_regenerate_id()
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect based on role
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$pageTitle = 'Sign In | Serene Shores Coastal Villa';
$error = '';
$email = '';
$redirect = (string)($_GET['redirect'] ?? $_POST['redirect'] ?? '');

// Sanitize redirect URL to prevent Open Redirect vulnerabilities
if (!empty($redirect) && (str_starts_with($redirect, 'http://') || str_starts_with($redirect, 'https://') || str_starts_with($redirect, '//'))) {
    $redirect = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email address and password.';
        } else {
            // Prepared statement query
            $stmt = $pdo->prepare('SELECT user_id, name, email, contact_number, password, role FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to mitigate session fixation
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id']        = (int)$user['user_id'];
                $_SESSION['name']           = (string)$user['name'];
                $_SESSION['email']          = (string)$user['email'];
                $_SESSION['contact_number'] = (string)$user['contact_number'];
                $_SESSION['role']           = (string)$user['role'];

                setFlash('success', 'Welcome back, ' . $user['name'] . '!');

                // Role-based routing
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                    exit;
                }

                if (!empty($redirect)) {
                    header('Location: ' . $redirect);
                    exit;
                }

                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid email address or password. Please verify and retry.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper">
        <div class="auth-header">
            <h2>Welcome Back</h2>
            <p>Sign in to manage your bookings or reserve your Mirissa villa</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span class="alert-icon">&#9888;</span>
                <span class="alert-text"><?= e($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" novalidate>
            <?= csrfField(); ?>
            <input type="hidden" name="redirect" value="<?= e($redirect); ?>">

            <div class="form-group" style="margin-bottom: 18px;">
                <label for="email">&#9993; Email Address</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= e($email); ?>" required placeholder="e.g. kasun.perera@gmail.com" autofocus>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label for="password">&#128274; Password</label>
                <div class="password-input-group" style="position: relative; width: 100%;">
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Enter your password" style="padding-right: 42px;">
                    <button type="button" class="password-toggle password-toggle-btn" data-target="password" aria-label="Show password" title="Toggle password visibility" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: transparent; border: none; padding: 0; cursor: pointer; display: flex; align-items: center; color: #64748b;">
                        <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="eye-closed" style="display:none;" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <div class="auth-footer">
            Don't have an account yet? <a href="register.php"><strong>Register here</strong></a>
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
