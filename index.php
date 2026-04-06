<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database.php';

if (isset($_SESSION['tech_id'])) {
    if ($_SESSION['tech_position'] === 'Admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: tech/index.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM technical_staff WHERE username = ?");
        $stmt->execute([$username]);
        $tech = $stmt->fetch();

        if ($tech && password_verify($password, $tech['password'])) {
            if ($tech['is_active'] == 0) {
                $error = "Your account is pending verification and approval from Admin.";
            } else {
                // Success
                $_SESSION['tech_id'] = $tech['technical_id'];
                $_SESSION['tech_firstname'] = $tech['firstname'];
                $_SESSION['tech_position'] = $tech['position'];
                
                // Update last_login
                $update = $pdo->prepare("UPDATE technical_staff SET last_login = NOW() WHERE technical_id = ?");
                $update->execute([$tech['technical_id']]);

                if ($tech['position'] === 'Admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: tech/index.php");
                }
                exit;
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TicketFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-card">
        <div class="auth-header">
            <h1><img src="logo_mssc.png" alt=""></h1>
            <p>Welcome back, Technical Staff</p>
        </div>

        <?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                Registration exact! You may now login.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['registered_new']) && $_GET['registered_new'] == 1): ?>
            <div class="alert-success" style="background: rgba(253, 203, 110, 0.2); color: var(--warning); border-color: var(--warning);">
                <i class="fas fa-clock"></i>
                Account created successfully. Please wait for Admin verification.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="loginPassword" class="form-control" required autocomplete="current-password">
                    <button type="button" class="pw-toggle" onclick="togglePw('loginPassword', this)" tabindex="-1" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-auth"><i class="fas fa-sign-in-alt"></i> Login to Dashboard</button>
        </form>

        <div class="auth-footer">
            No account yet? <a href="signup.php">Register Identity</a>
        </div>
    </div>



    <script>
        function togglePw(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
                btn.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
                btn.setAttribute('aria-label', 'Show password');
            }
        }
    </script>
</body>
</html>
