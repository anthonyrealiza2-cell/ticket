<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database.php';

if (isset($_SESSION['tech_id'])) {
    if (($_SESSION['tech_position'] ?? '') === 'Admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: tech/index.php");
    }
    exit;
}

$step = 1;
$mode = $_POST['mode'] ?? 'new'; // default to 'new' or 'verify'
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'verify') {
        if (isset($_POST['action']) && $_POST['action'] === 'verify') {
            // Step 1: Verify Identity
            $email = trim($_POST['email'] ?? '');
            $lastname = trim($_POST['lastname'] ?? '');

            if (empty($email) || empty($lastname)) {
                $error = "Please enter both Email and Last Name.";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM technical_staff WHERE email = ? AND lastname = ?");
                $stmt->execute([$email, $lastname]);
                $tech = $stmt->fetch();

                if ($tech) {
                    if (!empty($tech['username']) || !empty($tech['password'])) {
                        $error = "User already registered. Please login instead.";
                    } else {
                        $step = 2;
                        $success = "Identity verified! Please create your login credentials.";
                        $_SESSION['register_tech_id'] = $tech['technical_id'];
                    }
                } else {
                    $error = "Account not found. Ensure your Email and Last Name exactly match our records.";
                }
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
            // Step 2: Set credentials
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $tech_id = $_SESSION['register_tech_id'] ?? null;

            if (!$tech_id) {
                $error = "Session expired. Please start over.";
                $step = 1;
            } elseif (empty($username) || empty($password)) {
                $error = "Please fill in all fields.";
                $step = 2;
            } elseif ($password !== $confirm) {
                $error = "Passwords do not match.";
                $step = 2;
            } elseif (strlen($password) < 6) {
                $error = "Password must be at least 6 characters long.";
                $step = 2;
            } else {
                $check = $pdo->prepare("SELECT technical_id FROM technical_staff WHERE username = ?");
                $check->execute([$username]);
                if ($check->fetch()) {
                    $error = "Username is already taken. Please choose another.";
                    $step = 2;
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE technical_staff SET username = ?, password = ?, is_active = 1 WHERE technical_id = ?");
                    
                    if ($update->execute([$username, $hash, $tech_id])) {
                        unset($_SESSION['register_tech_id']);
                        header("Location: index.php?registered=1");
                        exit;
                    } else {
                        $error = "Database error. Please try again.";
                        $step = 2;
                    }
                }
            }
        }
    } elseif ($mode === 'new') {
        // Mode: Register New Staff completely
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $branch = trim($_POST['branch'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        // Handle hybrid position: if 'Other' chosen, use the free-text field
        $positionSelect = trim($_POST['position'] ?? 'Technical');
        $positionCustom = trim($_POST['position_custom'] ?? '');
        $position = ($positionSelect === 'Other' && !empty($positionCustom)) ? $positionCustom : $positionSelect;

        if (empty($firstname) || empty($lastname) || empty($email) || empty($username) || empty($password)) {
            $error = "Please fill in all required fields.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $check = $pdo->prepare("SELECT technical_id FROM technical_staff WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                $error = "Username or Email is already taken. Please choose another.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("
                    INSERT INTO technical_staff (firstname, lastname, email, contact_viber, branch, position, username, password, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
                ");
                
                if ($insert->execute([$firstname, $lastname, $email, $contact, $branch, $position, $username, $hash])) {
                    header("Location: index.php?registered_new=1");
                    exit;
                } else {
                    $error = "Database error. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - TicketFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-card">
        <div class="auth-header">
            <h1>Register</h1>
            <p>Setup your Technical Staff access</p>
        </div>

        <div class="auth-tabs">
            <div class="auth-tab <?= $mode === 'new' ? 'active' : '' ?>" onclick="switchTab('new')">New Account</div>
            <div class="auth-tab <?= $mode === 'verify' ? 'active' : '' ?>" onclick="switchTab('verify')">Bind Existing</div>
        </div>

        <?php if ($error): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- NEW ACCOUNT TAB -->
        <div id="tab-new" class="tab-content <?= $mode === 'new' ? 'active' : '' ?>">
            <form method="POST" action="">
                <input type="hidden" name="mode" value="new">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Contact/Viber</label>
                        <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch *</label>
                        <select name="branch" class="form-control" required>
                            <option value="DAVAO">DAVAO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Role / Position *</label>
                    <select name="position" id="positionSelect" class="form-control" onchange="togglePositionCustom(this)" required>
                        <option value="Technical" <?= ($_POST['position'] ?? '') === 'Technical' ? 'selected' : '' ?>>Technical</option>
                        <option value="Sales" <?= ($_POST['position'] ?? '') === 'Sales' ? 'selected' : '' ?>>Sales</option>
                        <option value="Support" <?= ($_POST['position'] ?? '') === 'Support' ? 'selected' : '' ?>>Support</option>
                        <option value="Engineer" <?= ($_POST['position'] ?? '') === 'Engineer' ? 'selected' : '' ?>>Engineer</option>
                        <option value="Manager" <?= ($_POST['position'] ?? '') === 'Manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="Other" <?= ($_POST['position'] ?? '') === 'Other' ? 'selected' : '' ?>>Other (specify below)</option>
                    </select>
                    <div id="positionCustomWrap" style="margin-top: 8px; display: <?= (($_POST['position'] ?? '') === 'Other') ? 'block' : 'none' ?>;">
                        <input type="text" name="position_custom" id="positionCustom" class="form-control"
                               placeholder="Enter your role/position" value="<?= htmlspecialchars($_POST['position_custom'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" required autocomplete="username">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="newPassword" class="form-control" placeholder="Min 6 chars" required autocomplete="new-password">
                            <button type="button" class="pw-toggle" onclick="togglePw('newPassword', this)" tabindex="-1" aria-label="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password *</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="newConfirmPassword" class="form-control" required autocomplete="new-password">
                            <button type="button" class="pw-toggle" onclick="togglePw('newConfirmPassword', this)" tabindex="-1" aria-label="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-auth"><i class="fas fa-user-plus"></i> Register New Staff</button>
            </form>
        </div>

        <!-- VERIFY/BIND ACCOUNT TAB -->
        <div id="tab-verify" class="tab-content <?= $mode === 'verify' ? 'active' : '' ?>">
            <?php if ($step === 1 || $mode !== 'verify'): ?>
                <form method="POST" action="">
                    <input type="hidden" name="mode" value="verify">
                    <input type="hidden" name="action" value="verify">
                    <div class="form-group">
                        <label class="form-label">Registered Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Registered email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="lastname" class="form-control" placeholder="Exact last name on record" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" required>
                    </div>
                    <button type="submit" class="btn-auth">Verify Identity <i class="fas fa-arrow-right"></i></button>
                </form>
            <?php else: ?>
                <form method="POST" action="">
                    <input type="hidden" name="mode" value="verify">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label class="form-label">New Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Choose a username" required autofocus autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="bindPassword" class="form-control" placeholder="Min. 6 characters" required autocomplete="new-password">
                            <button type="button" class="pw-toggle" onclick="togglePw('bindPassword', this)" tabindex="-1" aria-label="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="bindConfirmPassword" class="form-control" required autocomplete="new-password">
                            <button type="button" class="pw-toggle" onclick="togglePw('bindConfirmPassword', this)" tabindex="-1" aria-label="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn-auth"><i class="fas fa-user-check"></i> Complete Binding</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="auth-footer">
            Already registered? <a href="index.php">Login here</a>
        </div>
    </div>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.auth-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
            
            // Clear alerts on switch if any
            const alerts = document.querySelectorAll('.alert-error, .alert-success');
            alerts.forEach(alert => alert.style.display = 'none');
        }

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

        function togglePositionCustom(select) {
            const wrap = document.getElementById('positionCustomWrap');
            const customInput = document.getElementById('positionCustom');
            if (select.value === 'Other') {
                wrap.style.display = 'block';
                customInput.required = true;
            } else {
                wrap.style.display = 'none';
                customInput.required = false;
                customInput.value = '';
            }
        }
    </script>
</body>
</html>
