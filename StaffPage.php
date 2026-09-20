<?php
session_start();
include('db.php');

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

/* =========================================================
   1. TAMBAH STAFF BARU (ADD STAFF)
========================================================= */
if (isset($_POST['add_staff'])) {
    $id       = mysqli_real_escape_string($conn, $_POST['id']);
    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);

    $stmt = $conn->prepare("INSERT INTO staff (id, name, position, phone, email) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $id, $name, $position, $phone, $email);
    
    if ($stmt->execute()) {
        echo "<script>alert('Staff added successfully!'); window.location.href='StaffPage.php';</script>";
        exit();
    }
}

/* =========================================================
   2. KEMASKINI STAFF (EDIT STAFF)
========================================================= */
if (isset($_POST['edit_staff'])) {
    $id       = mysqli_real_escape_string($conn, $_POST['id']);
    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);

    $stmt = $conn->prepare("UPDATE staff SET name=?, position=?, phone=?, email=? WHERE id=?");
    $stmt->bind_param("sssss", $name, $position, $phone, $email, $id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Staff details updated successfully!'); window.location.href='StaffPage.php';</script>";
        exit();
    }
}

/* =========================================================
   3. PADAM STAFF (DELETE STAFF)
========================================================= */
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM staff WHERE id=?");
    $stmt->bind_param("s", $delete_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Staff record deleted successfully!'); window.location.href='StaffPage.php';</script>";
        exit();
    }
}

/* =========================================================
   4. DAPATKAN DATA & SEARCH
========================================================= */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM staff WHERE id LIKE ? OR name LIKE ? OR position LIKE ? OR email LIKE ? ORDER BY id DESC");
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM staff ORDER BY id DESC");
}

$staff_list = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $staff_list[] = $row;
    }
}

$total_staff = count($staff_list);
$designer_count = 0;
$manager_count = 0;

foreach ($staff_list as $stf) {
    $pos = strtolower($stf['position'] ?? '');
    if (strpos($pos, 'designer') !== false) {
        $designer_count++;
    } elseif (strpos($pos, 'manager') !== false) {
        $manager_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="ui_polish.css">
<title>Staff Directory | SA Design</title>

<!-- FONT AWESOME -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

/* =========================================================
   COLOR SYSTEM & ROOT
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

a { text-decoration: none; }
button, input, select { font-family: inherit; }

::-webkit-scrollbar { width: 7px; }
::-webkit-scrollbar-track { background: #EEF8FB; }
::-webkit-scrollbar-thumb { background: #B9DDE8; border-radius: 20px; }
::-webkit-scrollbar-thumb:hover { background: var(--pink); }

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

.brand-main span { color: #1C6EF2; }

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

.sidebar-menu li { margin-bottom: 6px; }

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

.sidebar-menu li a:hover i { color: var(--pink); }

.sidebar-menu li.active a {
    background: linear-gradient(135deg, var(--pink-light), var(--blue-pale));
    color: var(--pink);
    box-shadow: 0 7px 20px rgba(233, 139, 170, 0.10);
}

.sidebar-menu li.active a i { color: var(--pink); }

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
}

.admin-info { min-width: 0; }

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

.sidebar-footer { padding: 0 14px 16px; }

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
    width: 100%;
}

.logout-btn i { color: var(--pink); }

.logout-btn:hover {
    background: var(--pink);
    color: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(233, 139, 170, 0.20);
}

.logout-btn:hover i { color: #FFFFFF; }

/* =========================================================
   MAIN CONTENT AREA
========================================================= */

.main-content {
    margin-left: 289px;
    padding: 34px 38px 50px;
    min-height: 100vh;
}

/* =========================================================
   HEADER
========================================================= */

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
   STATISTICS GRID
========================================================= */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 20px;
}

.stat-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 18px 20px;
    min-height: 110px;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: all 0.25s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 35px rgba(79,143,168,0.12);
}

.stat-card::after {
    content: "";
    position: absolute;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    right: -30px;
    top: -30px;
    opacity: 0.16;
}

.total-card::after { background: var(--blue); }
.designer-card::after { background: var(--pink); }
.manager-card::after { background: var(--success); }

.stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 11px;
}

.stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
}

.total-card .stat-icon { background: var(--blue-light); color: var(--blue); }
.designer-card .stat-icon { background: var(--pink-light); color: var(--pink); }
.manager-card .stat-icon { background: #E3F2EA; color: #559B7D; }

.stat-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--gray-text);
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 28px;
    line-height: 1;
    font-weight: 800;
    color: var(--blue-dark);
}

.designer-card .stat-value { color: var(--pink-dark); }
.manager-card .stat-value { color: #3A7F93; }

/* =========================================================
   SEARCH & FILTER TOOLBAR
========================================================= */

.toolbar-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 20px;
    box-shadow: var(--shadow);
    margin-bottom: 25px;
}

.toolbar-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 15px;
}

.toolbar-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 750;
    color: var(--blue-dark);
}

.toolbar-title-icon {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: var(--pink-light);
    color: var(--pink);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
}

.result-badge {
    padding: 6px 12px;
    background: var(--blue-pale);
    color: var(--blue);
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
}

.toolbar-form {
    display: flex;
    gap: 12px;
    align-items: center;
}

.search-input-wrapper {
    position: relative;
    flex: 1;
}

.search-input-wrapper input {
    width: 100%;
    height: 44px;
    padding: 10px 42px 10px 16px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--blue-pale);
    color: var(--dark-text);
    font-size: 12px;
    outline: none;
    transition: all 0.25s ease;
}

.search-input-wrapper input:focus {
    border-color: var(--pink);
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(233, 139, 170, 0.15);
}

.search-input-wrapper i {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--blue);
}

.btn-add-staff {
    height: 44px;
    padding: 0 20px;
    border: none;
    border-radius: 12px;
    background: var(--pink);
    color: #FFFFFF;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.25s ease;
}

.btn-add-staff:hover {
    background: var(--pink-dark);
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(233, 139, 170, 0.25);
}

/* =========================================================
   STAFF TABLE / CARDS
========================================================= */

.table-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 18px;
    box-shadow: var(--shadow);
    overflow: hidden;
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
}

.custom-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.custom-table th {
    background: var(--blue-pale);
    color: var(--blue-dark);
    padding: 16px 20px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    border-bottom: 1px solid var(--border);
}

.custom-table td {
    padding: 16px 20px;
    font-size: 13px;
    color: var(--dark-text);
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.custom-table tr:last-child td {
    border-bottom: none;
}

.custom-table tr:hover {
    background: #FBFDFE;
}

.badge-id {
    display: inline-block;
    padding: 5px 10px;
    background: var(--blue-light);
    color: var(--blue-dark);
    border-radius: 8px;
    font-weight: 800;
    font-size: 11px;
}

.badge-position {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    background: var(--pink-light);
    color: var(--pink-dark);
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.staff-info-box {
    display: flex;
    align-items: center;
    gap: 12px;
}

.staff-avatar-mini {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--blue-pale);
    color: var(--blue);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    border: 1px solid var(--border);
}

.table-actions {
    display: flex;
    gap: 8px;
}

.btn-tbl-edit, .btn-tbl-delete {
    height: 34px;
    padding: 0 12px;
    border: none;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.25s ease;
}

.btn-tbl-edit {
    background: var(--blue-pale);
    color: var(--blue-dark);
}

.btn-tbl-edit:hover {
    background: var(--blue);
    color: #FFFFFF;
}

.btn-tbl-delete {
    background: var(--pink-pale);
    color: var(--pink-dark);
    border: 1px solid var(--pink-light);
}

.btn-tbl-delete:hover {
    background: var(--pink);
    color: #FFFFFF;
}

.no-records {
    text-align: center;
    padding: 50px 20px;
}

.empty-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--pink-light);
    color: var(--pink);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin: 0 auto 15px;
}

.empty-title {
    font-size: 15px;
    font-weight: 750;
    color: var(--blue-dark);
    margin-bottom: 5px;
}

.empty-text {
    font-size: 11px;
    color: var(--gray-text);
}

/* =========================================================
   MODALS (COMMON & EDIT & ADD & LOGOUT & DELETE)
========================================================= */

.custom-modal {
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

.custom-modal.show {
    opacity: 1;
    pointer-events: auto;
}

.modal-box {
    width: 100%;
    max-width: 480px;
    background: #FFFFFF;
    border-radius: 20px;
    box-shadow: 0 25px 60px rgba(40,70,80,0.20);
    transform: scale(0.92);
    transition: transform 0.25s ease;
    overflow: hidden;
}

.custom-modal.show .modal-box { transform: scale(1); }

.modal-head {
    padding: 20px 24px;
    background: var(--blue-pale);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-head-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
    font-weight: 800;
    color: var(--blue-dark);
}

.modal-head-title i { color: var(--pink); }

.modal-close-btn {
    background: transparent;
    border: none;
    font-size: 18px;
    color: var(--gray-text);
    cursor: pointer;
    transition: color 0.2s;
}

.modal-close-btn:hover { color: var(--pink); }

.modal-body-content {
    padding: 24px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--gray-text);
    margin-bottom: 6px;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group select {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--blue-pale);
    color: var(--dark-text);
    font-size: 12px;
    outline: none;
    transition: all 0.25s ease;
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--pink);
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(233, 139, 170, 0.15);
}

.modal-foot {
    padding: 16px 24px;
    background: #F8FCFD;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-modal-cancel, .btn-modal-submit {
    height: 40px;
    padding: 0 20px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
}

.btn-modal-cancel {
    background: var(--blue-pale);
    color: var(--blue-dark);
}

.btn-modal-cancel:hover { background: var(--border); }

.btn-modal-submit {
    background: var(--pink);
    color: #FFFFFF;
}

.btn-modal-submit:hover { background: var(--pink-dark); }

/* ALERT & CONFIRM MODAL STYLES */
.alert-box {
    max-width: 390px;
    padding: 29px;
    text-align: center;
}

.alert-icon {
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

.alert-title {
    font-size: 18px;
    font-weight: 750;
    color: var(--blue-dark);
    margin-bottom: 7px;
}

.alert-text-body {
    font-size: 12px;
    color: var(--gray-text);
    line-height: 1.6;
    margin-bottom: 22px;
}

.alert-buttons-row {
    display: flex;
    gap: 10px;
}

.btn-confirm-action, .btn-cancel-action {
    flex: 1;
    border: none;
    padding: 11px 18px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-confirm-action { background: var(--pink); color: #FFFFFF; }
.btn-confirm-action:hover { background: var(--pink-dark); }

.btn-cancel-action { background: var(--blue-pale); color: var(--blue-dark); }
.btn-cancel-action:hover { background: var(--blue-light); }

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1250px) {
    .sidebar { width: 225px; }
    .main-content { margin-left: 267px; padding-left: 28px; padding-right: 28px; }
}

@media (max-width: 1050px) {
    .sidebar { width: 215px; left: 16px; }
    .main-content { margin-left: 247px; padding: 28px 22px 40px; }
    .stats-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 750px) {
    .sidebar { position: relative; top: auto; left: auto; width: 100%; height: auto; border-radius: 0; margin: 0; }
    .sidebar-menu { display: grid; grid-template-columns: repeat(2, 1fr); gap: 5px; }
    .main-content { margin-left: 0; padding: 24px 18px 35px; }
    .top-header { flex-direction: column; align-items: flex-start; gap: 13px; }
    .toolbar-form { flex-direction: column; align-items: stretch; }
    .btn-add-staff { width: 100%; justify-content: center; }
    .stats-grid { grid-template-columns: 1fr; }
}

</style>

</head>

<body>

<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">

    <div class="sidebar-brand">
        <div class="brand-main">
            SA<span>DESIGN</span>
        </div>
        <div class="brand-sub">
            PRINTING &amp; ADVERTISING
        </div>
    </div>

    <div class="menu-label">
        Main Menu
    </div>

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
        <li class="active">
            <a href="StaffPage.php">
                <i class="fa-solid fa-users"></i>
                <span>Staff</span>
            </a>
        </li>
        <li>
            <a href="AdminProfile.php">
                <i class="fa-solid fa-user-gear"></i>
                <span>Profile</span>
            </a>
        </li>
    </ul>

    <div class="admin-mini-card">
        <div class="admin-avatar">
            <i class="fa-solid fa-user"></i>
        </div>
        <div class="admin-info">
            <div class="admin-name">Administrator</div>
            <div class="admin-role">System Admin</div>
        </div>
    </div>

    <div class="sidebar-footer">
        <button type="button" onclick="openLogoutModal()" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </button>
    </div>

</aside>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="main-content">

    <!-- TOP HEADER -->
    <div class="top-header">
        <div class="page-heading">
            <span class="welcome-text">Team Directory</span>
            <h1 class="page-title">Manage Staff</h1>
            <p class="page-subtitle">View, add, update, and manage team members.</p>
        </div>

        <div class="status-pill">
            <span class="status-dot"></span>
            System Online
        </div>
    </div>


    <!-- STATISTICS CARDS -->
    <div class="stats-grid">

        <div class="stat-card total-card">
            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="stat-label">Total Staff</div>
            <div class="stat-value"><?php echo number_format($total_staff); ?></div>
        </div>

        <div class="stat-card designer-card">
            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-palette"></i>
                </div>
            </div>
            <div class="stat-label">Graphic Designers</div>
            <div class="stat-value"><?php echo number_format($designer_count); ?></div>
        </div>

        <div class="stat-card manager-card">
            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
            </div>
            <div class="stat-label">Managers</div>
            <div class="stat-value"><?php echo number_format($manager_count); ?></div>
        </div>

    </div>


    <!-- TOOLBAR (SEARCH & ADD) -->
    <div class="toolbar-card">

        <div class="toolbar-top">
            <div class="toolbar-title">
                <div class="toolbar-title-icon">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                Search Team Members
            </div>

            <div class="result-badge">
                <?php echo $total_staff; ?> Member(s)
            </div>
        </div>

        <div class="toolbar-form">

            <form method="GET" action="StaffPage.php" class="search-input-wrapper">
                <input
                    type="text"
                    name="search"
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search by ID, Name, Position or Email..."
                >
                <i class="fa-solid fa-magnifying-glass"></i>
            </form>

            <button type="button" class="btn-add-staff" onclick="openAddModal()">
                <i class="fa-solid fa-user-plus"></i> Add Staff
            </button>

        </div>

    </div>


    <!-- STAFF TABLE CARD -->
    <div class="table-card">
        <div class="table-responsive">

            <?php if ($total_staff > 0): ?>

                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Staff Member</th>
                            <th>Position</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_list as $stf): ?>
                            <tr>
                                <td>
                                    <span class="badge-id"><?php echo htmlspecialchars($stf['id']); ?></span>
                                </td>
                                <td>
                                    <div class="staff-info-box">
                                        <div class="staff-avatar-mini">
                                            <?php echo strtoupper(substr($stf['name'], 0, 1)); ?>
                                        </div>
                                        <strong style="color: var(--blue-dark);"><?php echo htmlspecialchars($stf['name']); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-position">
                                        <i class="fa-solid fa-briefcase"></i>
                                        <?php echo htmlspecialchars($stf['position'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="fa-solid fa-phone" style="color: var(--gray-text); font-size: 11px; margin-right: 4px;"></i>
                                    <?php echo htmlspecialchars($stf['phone'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <i class="fa-solid fa-envelope" style="color: var(--gray-text); font-size: 11px; margin-right: 4px;"></i>
                                    <?php echo htmlspecialchars($stf['email']); ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end;">
                                        <button
                                            type="button"
                                            class="btn-tbl-edit"
                                            onclick="openStaffModal('<?php echo addslashes($stf['id']); ?>', '<?php echo addslashes($stf['name']); ?>', '<?php echo addslashes($stf['position'] ?? ''); ?>', '<?php echo addslashes($stf['phone'] ?? ''); ?>', '<?php echo addslashes($stf['email']); ?>')"
                                        >
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="btn-tbl-delete"
                                            onclick="openDeleteModal('<?php echo addslashes($stf['id']); ?>', '<?php echo addslashes($stf['name']); ?>')"
                                        >
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php else: ?>

                <div class="no-records">
                    <div class="empty-icon">
                        <i class="fa-solid fa-users-slash"></i>
                    </div>
                    <div class="empty-title">No staff members found</div>
                    <div class="empty-text">Try adjusting your search criteria or add a new staff member.</div>
                </div>

            <?php endif; ?>

        </div>
    </div>

</main>


<!-- =========================================================
     ADD STAFF MODAL
========================================================= -->

<div id="addStaffModal" class="custom-modal">
    <div class="modal-box">

        <div class="modal-head">
            <div class="modal-head-title">
                <i class="fa-solid fa-user-plus"></i>
                <span>Add New Staff</span>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeAddModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="StaffPage.php" method="POST">
            <div class="modal-body-content">
                <div class="form-group">
                    <label>Staff ID</label>
                    <input type="text" name="id" required placeholder="Example: SAD001">
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="Full Name">
                </div>

                <div class="form-group">
                    <label>Position</label>
                    <select name="position" required>
                        <option value="Graphic Designer">Graphic Designer</option>
                        <option value="Manager">Manager</option>
                        <option value="Staff">Staff</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="012-3456789">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="staff@email.com">
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeAddModal()">Cancel</button>
                <button type="submit" name="add_staff" class="btn-modal-submit">Save Staff</button>
            </div>
        </form>

    </div>
</div>


<!-- =========================================================
     EDIT STAFF MODAL
========================================================= -->

<div id="editStaffModal" class="custom-modal">
    <div class="modal-box">

        <div class="modal-head">
            <div class="modal-head-title">
                <i class="fa-solid fa-user-pen"></i>
                <span>Edit Staff Details</span>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeStaffModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="StaffPage.php" method="POST">
            <div class="modal-body-content">
                <div class="form-group">
                    <label>Staff ID</label>
                    <input type="text" name="id" id="editDbId" readonly style="background-color: var(--blue-light); cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="editStaffName" required>
                </div>

                <div class="form-group">
                    <label>Position</label>
                    <select name="position" id="editStaffPosition">
                        <option value="Graphic Designer">Graphic Designer</option>
                        <option value="Manager">Manager</option>
                        <option value="Staff">Staff</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" id="editStaffPhone">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="editStaffEmail" required>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeStaffModal()">Cancel</button>
                <button type="submit" name="edit_staff" class="btn-modal-submit">Update Details</button>
            </div>
        </form>

    </div>
</div>


<!-- =========================================================
     DELETE CONFIRMATION MODAL
========================================================= -->

<div id="deleteConfirmationModal" class="custom-modal">
    <div class="modal-box alert-box">

        <div class="alert-icon">
            <i class="fa-solid fa-trash-can"></i>
        </div>

        <h3 class="alert-title">Confirm Delete</h3>

        <p class="alert-text-body">
            Are you sure you want to delete staff member <strong id="deleteStaffName" style="color: var(--pink-dark);"></strong>? This action cannot be undone.
        </p>

        <div class="alert-buttons-row">
            <button type="button" onclick="closeDeleteModal()" class="btn-cancel-action">Cancel</button>
            <button type="button" onclick="executeDelete()" class="btn-confirm-action">Delete</button>
        </div>

    </div>
</div>


<!-- =========================================================
     LOGOUT CONFIRMATION MODAL
========================================================= -->

<div id="logoutConfirmationModal" class="custom-modal">
    <div class="modal-box alert-box">

        <div class="alert-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h3 class="alert-title">Confirm Logout</h3>

        <p class="alert-text-body">
            Are you sure you want to log out of your administration panel?
        </p>

        <div class="alert-buttons-row">
            <button type="button" onclick="closeLogoutModal()" class="btn-cancel-action">Cancel</button>
            <button type="button" onclick="executeLogout()" class="btn-confirm-action">Logout</button>
        </div>

    </div>
</div>


<script>

/* ADD MODAL FUNCTIONS */
const addModal = document.getElementById('addStaffModal');

function openAddModal() {
    addModal.classList.add('show');
}

function closeAddModal() {
    addModal.classList.remove('show');
}

/* EDIT MODAL FUNCTIONS */
const editModal = document.getElementById('editStaffModal');

function openStaffModal(id, name, position, phone, email) {
    document.getElementById('editDbId').value = id;
    document.getElementById('editStaffName').value = name;
    document.getElementById('editStaffPosition').value = position;
    document.getElementById('editStaffPhone').value = phone;
    document.getElementById('editStaffEmail').value = email;

    editModal.classList.add('show');
}

function closeStaffModal() {
    editModal.classList.remove('show');
}

/* DELETE MODAL FUNCTIONS */
const deleteModal = document.getElementById('deleteConfirmationModal');
let targetDeleteId = null;

function openDeleteModal(id, staffName) {
    targetDeleteId = id;
    document.getElementById('deleteStaffName').textContent = "'" + staffName + "'";
    deleteModal.classList.add('show');
}

function closeDeleteModal() {
    deleteModal.classList.remove('show');
    targetDeleteId = null;
}

function executeDelete() {
    if (targetDeleteId) {
        window.location.href = "StaffPage.php?delete=" + encodeURIComponent(targetDeleteId);
    }
}

/* LOGOUT MODAL FUNCTIONS */
const logoutModal = document.getElementById('logoutConfirmationModal');

function openLogoutModal() {
    logoutModal.classList.add('show');
}

function closeLogoutModal() {
    logoutModal.classList.remove('show');
}

function executeLogout() {
    window.location.href = 'admin_logout.php';
}

/* GLOBAL MODAL EVENTS */
window.onclick = function(event) {
    if (event.target === logoutModal) {
        closeLogoutModal();
    }
    if (event.target === addModal) {
        closeAddModal();
    }
    if (event.target === editModal) {
        closeStaffModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
};

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLogoutModal();
        closeAddModal();
        closeStaffModal();
        closeDeleteModal();
    }
});

</script>

</body>

</html>
