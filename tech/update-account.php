<?php
require_once '../auth_check.php';
require_once '../database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Technician-only
if (($_SESSION['tech_position'] ?? '') === 'Admin') {
    header("Location: ../admin/index.php");
    exit;
}

$tech_id = $_SESSION['tech_id'] ?? 0;
$action  = $_POST['action'] ?? '';

// ── Update Profile ────────────────────────────────────────────────────────────
if ($action === 'update_profile') {
    $firstname     = trim($_POST['firstname']     ?? '');
    $lastname      = trim($_POST['lastname']      ?? '');
    $email         = trim($_POST['email']         ?? '');
    $contact_viber = trim($_POST['contact_viber'] ?? '');
    $branch        = trim($_POST['branch']        ?? '');
    $username      = trim($_POST['username']      ?? '');

    // Basic validation
    if (empty($firstname) || empty($lastname) || empty($email) || empty($username)) {
        header("Location: account-settings.php?error=" . urlencode("First name, last name, email and username are required."));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: account-settings.php?error=" . urlencode("Please enter a valid email address."));
        exit;
    }

    // Check username uniqueness (exclude self)
    $checkUser = $pdo->prepare("SELECT technical_id FROM technical_staff WHERE username = ? AND technical_id != ?");
    $checkUser->execute([$username, $tech_id]);
    if ($checkUser->fetch()) {
        header("Location: account-settings.php?error=" . urlencode("That username is already taken. Please choose another."));
        exit;
    }

    // Check email uniqueness (exclude self)
    $checkEmail = $pdo->prepare("SELECT technical_id FROM technical_staff WHERE email = ? AND technical_id != ?");
    $checkEmail->execute([$email, $tech_id]);
    if ($checkEmail->fetch()) {
        header("Location: account-settings.php?error=" . urlencode("That email address is already in use."));
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE technical_staff
           SET firstname     = ?,
               lastname      = ?,
               email         = ?,
               contact_viber = ?,
               branch        = ?,
               username      = ?
         WHERE technical_id  = ?
    ");

    if ($stmt->execute([$firstname, $lastname, $email, $contact_viber, $branch, $username, $tech_id])) {
        // Refresh session name
        $_SESSION['tech_firstname'] = $firstname;
        $_SESSION['tech_lastname']  = $lastname;
        header("Location: account-settings.php?success=" . urlencode("Profile updated successfully!"));
    } else {
        header("Location: account-settings.php?error=" . urlencode("Database error. Please try again."));
    }
    exit;
}

// ── Change Password ───────────────────────────────────────────────────────────
if ($action === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password']     ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        header("Location: account-settings.php?error=" . urlencode("All password fields are required."));
        exit;
    }

    if (strlen($new_password) < 6) {
        header("Location: account-settings.php?error=" . urlencode("New password must be at least 6 characters long."));
        exit;
    }

    if ($new_password !== $confirm_password) {
        header("Location: account-settings.php?error=" . urlencode("New passwords do not match."));
        exit;
    }

    // Fetch current hashed password
    $fetch = $pdo->prepare("SELECT password FROM technical_staff WHERE technical_id = ?");
    $fetch->execute([$tech_id]);
    $row = $fetch->fetch();

    if (!$row || !password_verify($current_password, $row['password'])) {
        header("Location: account-settings.php?error=" . urlencode("Current password is incorrect."));
        exit;
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE technical_staff SET password = ? WHERE technical_id = ?");

    if ($update->execute([$hashed, $tech_id])) {
        header("Location: account-settings.php?success=" . urlencode("Password changed successfully!"));
    } else {
        header("Location: account-settings.php?error=" . urlencode("Database error. Please try again."));
    }
    exit;
}

// Fallback
header("Location: account-settings.php");
exit;
