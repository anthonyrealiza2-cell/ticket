<?php
require_once '../auth_check.php';
require_once '../database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Technician-only: redirect admins
if (($_SESSION['tech_position'] ?? '') === 'Admin') {
    header("Location: ../admin/index.php");
    exit;
}

$tech_id = $_SESSION['tech_id'] ?? 0;

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM technical_staff WHERE technical_id = ?");
$stmt->execute([$tech_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: ../index.php");
    exit;
}

// Flash messages passed via query string
$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings – TicketFlow</title>
    <meta name="description" content="Manage your TicketFlow account settings, update your profile and change your password.">

    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="tech-page.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="account-settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Prevent FOUC -->
    <script>
        (function () {
            const t = localStorage.getItem('theme');
            const sys = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', (t === 'dark' || (!t && sys)) ? 'dark' : 'light');
        })();
    </script>
</head>
<body class="technical-page">
<div class="container container-tech">
    <?php include '../navbar.php'; ?>

    <div class="settings-wrapper">

        <!-- ── Page Header ─────────────────────────────────── -->
        <div class="settings-page-header">
            <div class="settings-header-left">
                <div class="settings-avatar">
                    <i class="fas fa-user-circle"></i>
                    <div class="avatar-online-dot"></div>
                </div>
                <div>
                    <h1 class="settings-title">Account Settings</h1>
                    <p class="settings-subtitle">
                        <span class="settings-name-pill">
                            <i class="fas fa-id-badge"></i>
                            <?= htmlspecialchars(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) ?>
                        </span>
                        <span class="settings-role-pill">
                            <i class="fas fa-shield-alt"></i>
                            <?= htmlspecialchars($user['position'] ?? 'Technical') ?>
                        </span>
                    </p>
                </div>
            </div>
            <a href="index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- ── Flash Messages ──────────────────────────────── -->
        <?php if ($success): ?>
            <div class="settings-alert settings-alert-success" id="flashMsg">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
                <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="settings-alert settings-alert-error" id="flashMsg">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
                <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>

        <!-- ── Two-column layout ───────────────────────────── -->
        <div class="settings-grid">

            <!-- LEFT: Profile Information -->
            <div class="settings-card" id="profileCard">
                <div class="settings-card-header">
                    <div class="settings-card-icon profile-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div>
                        <h2>Profile Information</h2>
                        <p>Update your personal details</p>
                    </div>
                </div>

                <form id="profileForm" action="update-account.php" method="POST" autocomplete="off" novalidate>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-row-settings">
                        <div class="form-group-settings">
                            <label class="form-label-settings" for="firstname">
                                <i class="fas fa-user"></i> First Name
                            </label>
                            <input
                                type="text"
                                id="firstname"
                                name="firstname"
                                class="form-control-settings"
                                value="<?= htmlspecialchars($user['firstname'] ?? '') ?>"
                                placeholder="Your first name"
                                required
                                autocomplete="off">
                        </div>
                        <div class="form-group-settings">
                            <label class="form-label-settings" for="lastname">
                                <i class="fas fa-user"></i> Last Name
                            </label>
                            <input
                                type="text"
                                id="lastname"
                                name="lastname"
                                class="form-control-settings"
                                value="<?= htmlspecialchars($user['lastname'] ?? '') ?>"
                                placeholder="Your last name"
                                required
                                autocomplete="off">
                        </div>
                    </div>

                    <div class="form-group-settings">
                        <label class="form-label-settings" for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control-settings"
                            value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                            placeholder="your@email.com"
                            required
                            autocomplete="off">
                    </div>

                    <div class="form-row-settings">
                        <div class="form-group-settings">
                            <label class="form-label-settings" for="contact_viber">
                                <i class="fas fa-phone"></i> Contact / Viber
                            </label>
                            <input
                                type="text"
                                id="contact_viber"
                                name="contact_viber"
                                class="form-control-settings"
                                value="<?= htmlspecialchars($user['contact_viber'] ?? '') ?>"
                                placeholder="+63 9XX XXX XXXX"
                                autocomplete="off">
                        </div>
                        <div class="form-group-settings">
                            <label class="form-label-settings" for="branch">
                                <i class="fas fa-building"></i> Branch
                            </label>
                            <select id="branch" name="branch" class="form-control-settings">
                                <option value="DAVAO"   <?= ($user['branch'] ?? '') === 'DAVAO'   ? 'selected' : '' ?>>DAVAO</option>
                                <option value="CEBU"    <?= ($user['branch'] ?? '') === 'CEBU'    ? 'selected' : '' ?>>CEBU</option>
                                <option value="MANILA"  <?= ($user['branch'] ?? '') === 'MANILA'  ? 'selected' : '' ?>>MANILA</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group-settings">
                        <label class="form-label-settings" for="username">
                            <i class="fas fa-at"></i> Username
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control-settings"
                            value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                            placeholder="Your username"
                            required
                            autocomplete="off">
                        <small class="field-hint-settings"><i class="fas fa-info-circle"></i> Username must be unique across all accounts.</small>
                    </div>

                    <button type="submit" class="btn-settings-primary" id="profileSubmitBtn">
                        <i class="fas fa-save"></i> Save Profile Changes
                    </button>
                </form>
            </div>

            <!-- RIGHT: Change Password -->
            <div class="settings-card" id="passwordCard">
                <div class="settings-card-header">
                    <div class="settings-card-icon password-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div>
                        <h2>Change Password</h2>
                        <p>Keep your account secure</p>
                    </div>
                </div>

                <form id="passwordForm" action="update-account.php" method="POST" autocomplete="new-password" novalidate>
                    <input type="hidden" name="action" value="change_password">

                    <!-- Hidden dummy fields to prevent autofill -->
                    <input type="text"     name="fakeuser" style="display:none;" tabindex="-1" autocomplete="username">
                    <input type="password" name="fakepass" style="display:none;" tabindex="-1" autocomplete="new-password">

                    <div class="form-group-settings">
                        <label class="form-label-settings" for="current_password">
                            <i class="fas fa-key"></i> Current Password
                        </label>
                        <div class="pw-wrapper-settings">
                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                class="form-control-settings"
                                placeholder="Enter your current password"
                                required
                                autocomplete="current-password">
                            <button type="button" class="pw-toggle-settings" onclick="togglePw('current_password', this)" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group-settings">
                        <label class="form-label-settings" for="new_password">
                            <i class="fas fa-lock-open"></i> New Password
                        </label>
                        <div class="pw-wrapper-settings">
                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                class="form-control-settings"
                                placeholder="Min. 6 characters"
                                required
                                autocomplete="new-password"
                                oninput="checkPasswordStrength(this.value)">
                            <button type="button" class="pw-toggle-settings" onclick="togglePw('new_password', this)" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <!-- Password strength meter -->
                        <div class="pw-strength-bar" id="pwStrengthBar">
                            <div class="pw-strength-fill" id="pwStrengthFill"></div>
                        </div>
                        <small class="pw-strength-label" id="pwStrengthLabel"></small>
                    </div>

                    <div class="form-group-settings">
                        <label class="form-label-settings" for="confirm_password">
                            <i class="fas fa-check-double"></i> Confirm New Password
                        </label>
                        <div class="pw-wrapper-settings">
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-control-settings"
                                placeholder="Re-enter new password"
                                required
                                autocomplete="new-password"
                                oninput="checkPasswordMatch()">
                            <button type="button" class="pw-toggle-settings" onclick="togglePw('confirm_password', this)" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="pw-match-label" id="pwMatchLabel"></small>
                    </div>

                    <!-- Password requirements checklist -->
                    <div class="pw-requirements">
                        <p class="pw-req-title"><i class="fas fa-shield-alt"></i> Password Requirements</p>
                        <ul>
                            <li id="req-length"  class="req-item"><i class="fas fa-circle"></i> At least 6 characters</li>
                            <li id="req-upper"   class="req-item"><i class="fas fa-circle"></i> Uppercase letter (A–Z)</li>
                            <li id="req-number"  class="req-item"><i class="fas fa-circle"></i> A number (0–9)</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn-settings-danger" id="pwSubmitBtn">
                        <i class="fas fa-shield-alt"></i> Update Password
                    </button>
                </form>
            </div>

        </div><!-- /.settings-grid -->

        <!-- ── Account Info Panel (read-only metadata) ──────── -->
        <div class="settings-info-panel">
            <div class="info-panel-header">
                <i class="fas fa-info-circle"></i>
                <span>Account Information</span>
            </div>
            <div class="info-panel-grid">
                <div class="info-item">
                    <span class="info-label">Account ID</span>
                    <span class="info-value">#<?= htmlspecialchars($tech_id) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Position</span>
                    <span class="info-value"><?= htmlspecialchars($user['position'] ?? 'Technical') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="info-value status-active"><i class="fas fa-circle"></i> Active</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Branch</span>
                    <span class="info-value"><?= htmlspecialchars($user['branch'] ?? '—') ?></span>
                </div>
            </div>
        </div>

    </div><!-- /.settings-wrapper -->
</div>

<script src="../script.js"></script>
<script>
    /* ── Password toggle ──────────────────────────────── */
    function togglePw(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon  = btn.querySelector('i');
        if (input.type === 'password') {
            input.type  = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type  = 'password';
            icon.className = 'fas fa-eye';
        }
    }

    /* ── Password strength ────────────────────────────── */
    function checkPasswordStrength(val) {
        const fill  = document.getElementById('pwStrengthFill');
        const label = document.getElementById('pwStrengthLabel');
        const reqL  = document.getElementById('req-length');
        const reqU  = document.getElementById('req-upper');
        const reqN  = document.getElementById('req-number');

        let score = 0;
        const hasLength = val.length >= 6;
        const hasUpper  = /[A-Z]/.test(val);
        const hasNumber = /[0-9]/.test(val);
        const hasSpecial = /[^A-Za-z0-9]/.test(val);

        reqL.classList.toggle('req-met', hasLength);
        reqU.classList.toggle('req-met', hasUpper);
        reqN.classList.toggle('req-met', hasNumber);

        if (hasLength)  score++;
        if (hasUpper)   score++;
        if (hasNumber)  score++;
        if (hasSpecial) score++;
        if (val.length >= 12) score++;

        const levels = [
            { label: '',        color: '',           width: '0%'   },
            { label: 'Weak',    color: '#ef4444',    width: '25%'  },
            { label: 'Fair',    color: '#f59e0b',    width: '50%'  },
            { label: 'Good',    color: '#3b82f6',    width: '75%'  },
            { label: 'Strong',  color: '#10b981',    width: '90%'  },
            { label: 'Very Strong', color: '#059669', width: '100%' },
        ];

        const lvl = levels[Math.min(score, 5)];
        fill.style.width           = val.length ? lvl.width : '0%';
        fill.style.backgroundColor = lvl.color;
        label.textContent          = val.length ? lvl.label : '';
        label.style.color          = lvl.color;
    }

    /* ── Password match ───────────────────────────────── */
    function checkPasswordMatch() {
        const np = document.getElementById('new_password').value;
        const cp = document.getElementById('confirm_password').value;
        const lbl = document.getElementById('pwMatchLabel');
        if (!cp) { lbl.textContent = ''; return; }
        if (np === cp) {
            lbl.textContent = '✓ Passwords match';
            lbl.style.color = 'var(--success)';
        } else {
            lbl.textContent = '✗ Passwords do not match';
            lbl.style.color = 'var(--danger)';
        }
    }

    /* ── Client-side form validation ─────────────────── */
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('profileSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';
    });

    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        const np = document.getElementById('new_password').value;
        const cp = document.getElementById('confirm_password').value;
        const cur = document.getElementById('current_password').value;

        if (!cur) {
            e.preventDefault();
            showSettingsAlert('Please enter your current password.', 'error');
            return;
        }
        if (np.length < 6) {
            e.preventDefault();
            showSettingsAlert('New password must be at least 6 characters.', 'error');
            return;
        }
        if (np !== cp) {
            e.preventDefault();
            showSettingsAlert('New passwords do not match.', 'error');
            return;
        }

        const btn = document.getElementById('pwSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating…';
    });

    /* ── Inline alert helper ──────────────────────────── */
    function showSettingsAlert(message, type) {
        const existing = document.getElementById('inlineAlert');
        if (existing) existing.remove();

        const div = document.createElement('div');
        div.id = 'inlineAlert';
        div.className = `settings-alert settings-alert-${type === 'error' ? 'error' : 'success'}`;
        div.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}
            <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;

        const wrapper = document.querySelector('.settings-wrapper');
        const grid    = document.querySelector('.settings-grid');
        wrapper.insertBefore(div, grid);
        div.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /* ── Auto-dismiss flash messages ─────────────────── */
    document.addEventListener('DOMContentLoaded', () => {
        const flash = document.getElementById('flashMsg');
        if (flash) {
            setTimeout(() => {
                flash.style.opacity = '0';
                flash.style.transform = 'translateY(-10px)';
                setTimeout(() => flash.remove(), 400);
            }, 5000);
        }
    });
</script>
</body>
</html>
