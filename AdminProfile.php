<?php
session_start();
include("db.php");

/* =========================================================
   ADMIN ACCESS PROTECTION
========================================================= */
if (
    empty($_SESSION['is_logged_in']) ||
    empty($_SESSION['is_admin']) ||
    $_SESSION['is_admin'] !== true ||
    empty($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin' ||
    empty($_SESSION['admin_id'])
) {
    $_SESSION['login_notice'] = 'Access denied. Admin login is required.';
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$success_msg = "";
$error_msg   = "";

/* =========================================================
   1. KEMASKINI PROFIL ADMIN (POST HANDLER)
========================================================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name        = trim($_POST['full_name']);
    $email            = trim($_POST['email']);
    $phone            = trim($_POST['phone']);
    $username         = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Ambil data admin semasa
    $stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // --- PROSES MUAT NAIK GAMBAR ---
    $image_path = $admin['image'] ?? ''; 

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp   = $_FILES['profile_image']['tmp_name'];
        $file_name  = $_FILES['profile_image']['name'];
        $file_ext   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = "admin_" . $admin_id . "_" . time() . "." . $file_ext;
            $upload_dir    = "uploads/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $target_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $target_path)) {
                if (!empty($admin['image']) && file_exists($admin['image'])) {
                    unlink($admin['image']);
                }
                $image_path = $target_path;
            } else {
                $error_msg = "Gagal memuat naik gambar profil.";
            }
        } else {
            $error_msg = "Format gambar tidak sah! Hanya JPG, JPEG, PNG, GIF & WEBP dibenarkan.";
        }
    }

    // --- PROSES TUKAR KATA LALUAN ---
    if (!empty($new_password)) {
        if (!empty($current_password)) {
            $is_password_correct = false;
            if (password_verify($current_password, $admin['password']) || $current_password === $admin['password']) {
                $is_password_correct = true;
            }

            if ($is_password_correct) {
                if ($new_password === $confirm_password) {
                    $updated_password = password_hash($new_password, PASSWORD_DEFAULT);
                } else {
                    $error_msg = "Kata laluan baru dan pengesahan kata laluan tidak padan!";
                }
            } else {
                $error_msg = "Kata laluan semasa adalah salah!";
            }
        } else {
            $error_msg = "Sila masukkan kata laluan semasa untuk mengemaskini kata laluan baru!";
        }
    } else {
        $updated_password = $admin['password'];
    }

    // --- KEMASKINI PANGKALAN DATA ---
    if (empty($error_msg)) {
        $update_stmt = $conn->prepare("UPDATE admin SET name=?, email=?, phone=?, username=?, password=?, image=? WHERE id=?");
        $update_stmt->bind_param("ssssssi", $full_name, $email, $phone, $username, $updated_password, $image_path, $admin_id);
        
        if ($update_stmt->execute()) {
            $success_msg = "Profil berjaya dikemaskini!";
        } else {
            $error_msg = "Ralat berlaku semasa kemaskini maklumat.";
        }
        $update_stmt->close();
    }
}

/* =========================================================
   2. DAPATKAN DATA ADMIN TERKINI DARI JADUAL 'admin'
========================================================= */
$stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="ui_polish.css">
<title>Admin Profile | SA Design</title>

<!-- FONT AWESOME -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* =========================================================
   COLOR SYSTEM
========================================================= */
:root {
    --pink: #E98BAA;
    --pink-dark: #D97899;
    --pink-light: #FCE8EF;
    --pink-pale: #FFF5F8;

    --blue: #4F8FA8;
    --blue-dark: #39758D;
    --blue-light: #DFF3FA;
    --blue-pale: #EFF9FC;

    --dark-text: #304A56;
    --blue-text: #39758D;
    --gray-text: #7C929C;

    --light-gray: #EAF6FB;
    --border: #D7EAF0;
    --white: #FFFFFF;

    --success: #78BFA4;
    --warning: #E7B66E;

    --shadow: 0 10px 35px rgba(79, 143, 168, 0.10);
}

/* =========================================================
   GLOBAL
========================================================= */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    background: var(--light-gray);
    color: var(--dark-text);
    min-height: 100vh;
}

a {
    text-decoration: none;
}

button, input {
    font-family: inherit;
}

/* =========================================================
   SCROLLBAR
========================================================= */
::-webkit-scrollbar {
    width: 7px;
}

::-webkit-scrollbar-track {
    background: #EEF8FB;
}

::-webkit-scrollbar-thumb {
    background: #B9DDE8;
    border-radius: 20px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--pink);
}

/* =========================================================
   SIDEBAR
========================================================= */
.sidebar {
    position: fixed;
    top: 22px;
    left: 22px;
    width: 245px;
    height: calc(100vh - 44px);
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 24px;
    display: flex;
    flex-direction: column;
    z-index: 1000;
    overflow: hidden;
    box-shadow: 0 18px 45px rgba(79, 143, 168, 0.14);
}

.sidebar-brand {
    padding: 27px 25px 25px;
    border-bottom: 1px solid var(--border);
}

.brand-main {
    font-size: 25px;
    font-weight: 900;
    letter-spacing: -1.2px;
    color: #EF228B;
}

.brand-main span {
    color: #1C6EF2;
}

.brand-sub {
    margin-top: 5px;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: 1.25px;
    color: #263238;
}

.menu-label {
    padding: 27px 24px 10px;
    color: #9AAEB7;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.4px;
}

.sidebar-menu {
    list-style: none;
    padding: 4px 13px;
    margin: 0;
    flex: 1;
    overflow-y: auto;
}

.sidebar-menu li {
    margin-bottom: 6px;
}

.sidebar-menu li a {
    display: flex;
    align-items: center;
    gap: 13px;
    min-height: 48px;
    padding: 11px 14px;
    color: #718A95;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.25s ease;
}

.sidebar-menu li a i {
    width: 23px;
    text-align: center;
    font-size: 16px;
    color: var(--blue);
}

.sidebar-menu li a:hover {
    background: var(--pink-light);
    color: var(--pink);
    transform: translateX(3px);
}

.sidebar-menu li a:hover i {
    color: var(--pink);
}

.sidebar-menu li.active a {
    background: linear-gradient(135deg, var(--pink-light), var(--blue-pale));
    color: var(--pink);
    box-shadow: 0 7px 20px rgba(233, 139, 170, 0.10);
}

.sidebar-menu li.active a i {
    color: var(--pink);
}

.admin-mini-card {
    margin: 12px 14px;
    padding: 13px;
    background: var(--blue-pale);
    border: 1px solid var(--border);
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 11px;
}

.admin-avatar {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--pink-light);
    color: var(--pink);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    border: 2px solid #FFFFFF;
    overflow: hidden;
}

.admin-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.admin-info {
    min-width: 0;
}

.admin-name {
    color: var(--dark-text);
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.admin-role {
    color: var(--gray-text);
    font-size: 10px;
    margin-top: 3px;
}

.sidebar-footer {
    padding: 0 14px 16px;
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 13px;
    min-height: 47px;
    padding: 11px 14px;
    color: var(--pink);
    background: var(--pink-light);
    border: 1px solid #F6D6E1;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s ease;
}

.logout-btn i {
    color: var(--pink);
}

.logout-btn:hover {
    background: var(--pink);
    color: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(233, 139, 170, 0.20);
}

.logout-btn:hover i {
    color: #FFFFFF;
}

/* =========================================================
   MAIN CONTENT & HEADER
========================================================= */
.main-content {
    margin-left: 289px;
    padding: 34px 38px 50px;
    min-height: 100vh;
}

.top-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
}

.page-heading {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.welcome-text {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-text);
}

.page-title {
    font-size: 29px;
    font-weight: 800;
    letter-spacing: -0.8px;
    color: var(--blue-dark);
}

.page-subtitle {
    font-size: 13px;
    color: var(--gray-text);
}

.status-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 30px;
    color: #62747D;
    font-size: 11px;
    font-weight: 600;
    box-shadow: 0 5px 18px rgba(79,143,168,0.06);
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #76C7A5;
    box-shadow: 0 0 0 4px #E2F5EC;
}

/* =========================================================
   ALERTS / NOTIFICATIONS
========================================================= */
.alert-box {
    padding: 14px 18px;
    border-radius: 14px;
    margin-bottom: 22px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: var(--shadow);
}

.alert-success {
    background: #E3F2EA;
    color: #3B7258;
    border: 1px solid #BFE3CE;
}

.alert-error {
    background: var(--pink-light);
    color: var(--pink-dark);
    border: 1px solid #F8C3D3;
}

/* =========================================================
   PROFILE LAYOUT GRID
========================================================= */
.profile-grid {
    display: grid;
    grid-template-columns: 290px 1fr;
    gap: 22px;
    align-items: start;
}

.profile-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 26px;
    box-shadow: var(--shadow);
}

/* LEFT SIDEBAR PROFILE CARD */
.profile-sidebar-card {
    text-align: center;
}

.avatar-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 18px;
}

.avatar-circle-display {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: var(--pink-light);
    color: var(--pink);
    border: 3px solid var(--white);
    box-shadow: 0 8px 25px rgba(233, 139, 170, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 45px;
    overflow: hidden;
}

.avatar-circle-display img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.upload-icon-badge {
    position: absolute;
    bottom: 2px;
    right: 2px;
    background: var(--pink);
    color: var(--white);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: 3px solid var(--white);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

.upload-icon-badge:hover {
    background: var(--pink-dark);
    transform: scale(1.05);
}

.profile-sidebar-card h3 {
    font-size: 18px;
    font-weight: 800;
    color: var(--blue-dark);
    margin-bottom: 6px;
}

.role-pill {
    display: inline-block;
    padding: 5px 14px;
    background: var(--blue-pale);
    color: var(--blue-dark);
    border: 1px solid var(--border);
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

/* FORM STYLING */
.form-section-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--blue-dark);
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section-title i {
    color: var(--pink);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.form-group.full-width {
    grid-column: span 2;
}

.form-group label {
    font-size: 12px;
    font-weight: 700;
    color: var(--dark-text);
}

.form-group input {
    width: 100%;
    padding: 12px 15px;
    background: var(--blue-pale);
    border: 1px solid var(--border);
    border-radius: 12px;
    color: var(--dark-text);
    font-size: 13px;
    outline: none;
    transition: all 0.2s ease;
}

.form-group input:focus {
    background: var(--white);
    border-color: var(--pink);
    box-shadow: 0 0 0 4px var(--pink-light);
}

.password-container {
    position: relative;
    display: flex;
    align-items: center;
}

.password-container input {
    padding-right: 42px;
}

.toggle-pwd {
    position: absolute;
    right: 14px;
    color: var(--gray-text);
    cursor: pointer;
    font-size: 14px;
    transition: color 0.2s;
}

.toggle-pwd:hover {
    color: var(--pink);
}

/* FORM ACTIONS */
.form-actions {
    display: flex;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
}

.btn-save {
    background: var(--pink);
    color: var(--white);
    border: none;
    padding: 12px 26px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
}

.btn-save:hover {
    background: var(--pink-dark);
    box-shadow: 0 8px 20px rgba(233, 139, 170, 0.25);
    transform: translateY(-1px);
}

.btn-cancel {
    background: var(--blue-pale);
    color: var(--blue-dark);
    border: 1px solid var(--border);
    padding: 12px 26px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
}

.btn-cancel:hover {
    background: var(--blue-light);
}

/* =========================================================
   LOGOUT MODAL
========================================================= */
.logout-modal {
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    background: rgba(43, 66, 76, 0.52);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}

.logout-modal.show {
    opacity: 1;
    pointer-events: auto;
}

.modal-box {
    width: 100%;
    max-width: 390px;
    padding: 29px;
    background: #FFFFFF;
    border-radius: 18px;
    text-align: center;
    box-shadow: 0 25px 60px rgba(40,70,80,0.20);
    transform: scale(0.92);
    transition: transform 0.25s ease;
}

.logout-modal.show .modal-box {
    transform: scale(1);
}

.modal-icon {
    width: 58px;
    height: 58px;
    margin: 0 auto 15px;
    border-radius: 50%;
    background: var(--pink-light);
    color: var(--pink);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.modal-title {
    font-size: 18px;
    font-weight: 750;
    color: var(--blue-dark);
    margin-bottom: 7px;
}

.modal-text {
    font-size: 12px;
    color: var(--gray-text);
    line-height: 1.6;
    margin-bottom: 22px;
}

.modal-buttons {
    display: flex;
    gap: 10px;
}

.btn-confirm-logout,
.btn-cancel-logout {
    flex: 1;
    border: none;
    padding: 11px 18px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-confirm-logout {
    background: var(--pink);
    color: #FFFFFF;
}

.btn-confirm-logout:hover {
    background: var(--pink-dark);
}

.btn-cancel-logout {
    background: var(--blue-pale);
    color: var(--blue-dark);
}

.btn-cancel-logout:hover {
    background: var(--blue-light);
}

/* =========================================================
   RESPONSIVE STYLES
========================================================= */
@media (max-width: 1050px) {
    .sidebar { width: 215px; left: 16px; }
    .main-content { margin-left: 247px; padding: 28px 22px 40px; }
    .profile-grid { grid-template-columns: 1fr; }
}

@media (max-width: 750px) {
    .sidebar {
        position: relative;
        top: auto;
        left: auto;
        width: 100%;
        height: auto;
        min-height: auto;
        border-radius: 0;
        margin: 0;
    }
    .sidebar-menu { display: grid; grid-template-columns: repeat(2, 1fr); gap: 5px; flex: none; }
    .sidebar-menu li { margin-bottom: 0; }
    .admin-mini-card { margin-top: 14px; }
    .main-content { margin-left: 0; padding: 24px 18px 35px; }
    .top-header { flex-direction: column; align-items: flex-start; gap: 13px; }
    .status-pill { width: 100%; justify-content: center; }
}

@media (max-width: 520px) {
    .sidebar-menu { grid-template-columns: 1fr; }
    .form-grid { grid-template-columns: 1fr; }
    .form-group.full-width { grid-column: span 1; }
}
</style>
</head>

<body>

<!-- =========================================================
     SIDEBAR
========================================================= -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-main">SA<span>DESIGN</span></div>
        <div class="brand-sub">PRINTING &amp; ADVERTISING</div>
    </div>

    <div class="menu-label">Main Menu</div>

    <ul class="sidebar-menu">
        <li>
            <a href="admin_dashboard.php">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="manage_orders.php">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span>Manage Orders</span>
            </a>
        </li>
        <li>
            <a href="ManageService.php">
                <i class="fa-solid fa-layer-group"></i>
                <span>Manage Services</span>
            </a>
        </li>
        <li>
            <a href="StaffPage.php">
                <i class="fa-solid fa-users"></i>
                <span>Staff</span>
            </a>
        </li>
        <li class="active">
            <a href="AdminProfile.php">
                <i class="fa-solid fa-user-gear"></i>
                <span>Profile</span>
            </a>
        </li>
    </ul>

    <div class="admin-mini-card">
        <div class="admin-avatar">
            <?php if (!empty($admin_data['image']) && file_exists($admin_data['image'])): ?>
                <img src="<?php echo htmlspecialchars($admin_data['image']); ?>" alt="Admin Profile">
            <?php else: ?>
                <i class="fa-solid fa-user"></i>
            <?php endif; ?>
        </div>
        <div class="admin-info">
            <div class="admin-name"><?php echo htmlspecialchars($admin_data['name'] ?? 'Administrator'); ?></div>
            <div class="admin-role">System Admin</div>
        </div>
    </div>

    <div class="sidebar-footer">
        <div onclick="openLogoutModal()" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </div>
    </div>
</aside>

<!-- =========================================================
     MAIN CONTENT
========================================================= -->
<main class="main-content">

    <div class="top-header">
        <div class="page-heading">
            <span class="welcome-text">Account Management ⚙️</span>
            <h1 class="page-title">Admin Profile</h1>
            <p class="page-subtitle">Update your profile settings and personal account information.</p>
        </div>
        <div class="header-right">
            <div class="status-pill">
                <span class="status-dot"></span> System Online
            </div>
        </div>
    </div>

    <!-- ALERTS -->
    <?php if (!empty($success_msg)): ?>
        <div class="alert-box alert-success">
            <i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-box alert-error">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <!-- PROFILE FORM -->
    <form action="AdminProfile.php" method="POST" enctype="multipart/form-data">
        <div class="profile-grid">
            
            <!-- LEFT PROFILE CARD -->
            <div class="profile-card profile-sidebar-card">
                <div class="avatar-wrapper">
                    <div class="avatar-circle-display" id="avatarPreview">
                        <?php if (!empty($admin_data['image']) && file_exists($admin_data['image'])): ?>
                            <img src="<?php echo htmlspecialchars($admin_data['image']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="fa-solid fa-user-gear"></i>
                        <?php endif; ?>
                    </div>
                    
                    <label for="profile_image" class="upload-icon-badge" title="Upload Photo">
                        <i class="fa-solid fa-camera"></i>
                    </label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;" onchange="previewImage(event)">
                </div>

                <h3><?php echo htmlspecialchars($admin_data['name'] ?? 'Admin Name'); ?></h3>
                <span class="role-pill">System Administrator</span>
            </div>

            <!-- RIGHT FORM CARD -->
            <div class="profile-card">
                
                <div class="form-section-title">
                    <i class="fa-solid fa-id-card"></i> Personal Information
                </div>

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin_data['name'] ?? ''); ?>" required placeholder="Enter full name">
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin_data['email'] ?? ''); ?>" required placeholder="admin@sadesign.com">
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($admin_data['phone'] ?? ''); ?>" required placeholder="012-3456789">
                    </div>
                </div>

                <div class="form-section-title" style="margin-top: 10px;">
                    <i class="fa-solid fa-lock"></i> Account Security
                </div>

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($admin_data['username'] ?? ''); ?>" required placeholder="admin_username">
                    </div>

                    <div class="form-group full-width">
                        <label>Current Password</label>
                        <div class="password-container">
                            <input type="password" id="current_password" name="current_password" placeholder="••••••••">
                            <i class="fa-solid fa-eye-slash toggle-pwd" onclick="togglePassword('current_password', this)"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>New Password <small style="color: var(--gray-text); font-weight: normal;">(Optional)</small></label>
                        <div class="password-container">
                            <input type="password" id="new_password" name="new_password" placeholder="••••••••">
                            <i class="fa-solid fa-eye-slash toggle-pwd" onclick="togglePassword('new_password', this)"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••">
                            <i class="fa-solid fa-eye-slash toggle-pwd" onclick="togglePassword('confirm_password', this)"></i>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save">Save Changes</button>
                    <button type="reset" class="btn-cancel">Cancel</button>
                </div>

            </div>

        </div>
    </form>

</main>

<!-- LOGOUT CONFIRMATION MODAL -->
<div id="logoutConfirmationModal" class="logout-modal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 class="modal-title">Confirm Logout</h3>
        <p class="modal-text">Are you sure you want to log out of your administration panel?</p>
        <div class="modal-buttons">
            <button onclick="closeLogoutModal()" class="btn-cancel-logout">Cancel</button>
            <button onclick="executeLogout()" class="btn-confirm-logout">Logout</button>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    var reader = new FileReader();
    reader.onload = function() {
        var output = document.getElementById('avatarPreview');
        output.innerHTML = '<img src="' + reader.result + '" alt="Profile Preview">';
    }
    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}

function togglePassword(inputId, icon) {
    var input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    }
}

// LOGOUT MODAL LOGIC
const modalElement = document.getElementById('logoutConfirmationModal');

function openLogoutModal() {
    modalElement.classList.add('show');
}

function closeLogoutModal() {
    modalElement.classList.remove('show');
}

function executeLogout() {
    window.location.href = 'admin_logout.php';
}

window.onclick = function(event) {
    if (event.target === modalElement) {
        closeLogoutModal();
    }
};
</script>

</body>
</html>
