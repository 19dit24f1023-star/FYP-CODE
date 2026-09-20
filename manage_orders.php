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


// =========================================================
// GET SEARCH & FILTER (Default to 'Active')
// =========================================================

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
// Secara automatik hanya tunjuk tempahan aktif jika tiada filter dipilih
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'Active';
$filter_source = isset($_GET['source']) ? $_GET['source'] : 'All';

if (!in_array($filter_source, ['All', 'Normal Order', 'Custom Request'], true)) {
    $filter_source = 'All';
}


// =========================================================
// GET NORMAL ORDERS
// =========================================================

$orders = [];

$sql = "SELECT
            orders.id,
            orders.order_number,
            orders.user_id,
            orders.product_name,
            orders.status,
            orders.job_flow,
            customers.name
        FROM orders
        LEFT JOIN customers
            ON orders.user_id = customers.id
        ORDER BY orders.id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Orders Query Error: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = [
        "id" => $row['order_number'],
        "customer" => $row['name'] ?? "Unknown Customer",
        "service" => $row['product_name'],
        "status" => $row['status'],
        "job_flow" => $row['job_flow'] ?? "Admin",
        "source" => "Normal Order"
    ];
}


// =========================================================
// GET CUSTOM REQUEST
// =========================================================

$sql = "SELECT
            id,
            fullname,
            product_name,
            quantity,
            status,
            job_flow
        FROM custom_request
        ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Custom Request Query Error: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = [
        "id" => "CR" . str_pad($row['id'], 4, "0", STR_PAD_LEFT),
        "customer" => $row['fullname'],
        "service" => $row['product_name'],
        "status" => $row['status'] ?? "Pending",
        "job_flow" => $row['job_flow'] ?? "Admin",
        "source" => "Custom Request"
    ];
}


// =========================================================
// FILTER ORDERS
// =========================================================

$filtered_orders = [];

foreach ($orders as $order) {

    // Filter source order: normal order or custom request.
    if ($filter_source !== 'All' && $order['source'] !== $filter_source) {
        continue;
    }

    // Jika status diset ke 'Active', sembunyikan tempahan 'Completed'
    if ($filter_status === 'Active' && strtolower($order['status']) === 'completed') {
        continue;
    }

    // Jika filter spesifik dipilih (Pending, Processing, Ready, dll)
    if (
        $filter_status !== 'All' &&
        $filter_status !== 'Active' &&
        strtolower($order['status']) !== strtolower($filter_status)
    ) {
        continue;
    }

    // Filter carian teks
    if (
        $search_query !== '' &&
        strpos(strtolower($order['id']), strtolower($search_query)) === false &&
        strpos(strtolower($order['customer']), strtolower($search_query)) === false
    ) {
        continue;
    }

    $filtered_orders[] = $order;
}


// =========================================================
// ORDER COUNTS FOR SUMMARY
// =========================================================

$total_display_orders = count($filtered_orders);

$pending_count = 0;
$processing_count = 0;
$ready_count = 0;
$completed_count = 0;

foreach ($filtered_orders as $order) {
    switch (strtolower($order['status'])) {
        case 'pending':
            $pending_count++;
            break;
        case 'processing':
            $processing_count++;
            break;
        case 'ready':
            $ready_count++;
            break;
        case 'completed':
            $completed_count++;
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="ui_polish.css">
<title>Manage Orders | SA Design</title>

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
   STATISTICS GRID (5 COLUMNS)
========================================================= */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 17px;
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
.pending-card::after { background: var(--pink); }
.processing-card::after { background: #4F8FA8; }
.ready-card::after { background: #73B7CA; }
.completed-card::after { background: #78BFA4; }

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
.pending-card .stat-icon { background: var(--pink-light); color: var(--pink); }
.processing-card .stat-icon { background: #DFF3FA; color: #39758D; }
.ready-card .stat-icon { background: #E3F4F9; color: #5796AA; }
.completed-card .stat-icon { background: #E3F2EA; color: #559B7D; }

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

.pending-card .stat-value { color: var(--pink-dark); }
.processing-card .stat-value { color: #39758D; }

/* =========================================================
   SEARCH & FILTER TOOLBAR
========================================================= */

.toolbar-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 20px;
    box-shadow: var(--shadow);
    margin-bottom: 20px;
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
    cursor: pointer;
}

.filter-select select {
    height: 44px;
    min-width: 210px;
    padding: 0 16px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--blue-pale);
    color: var(--dark-text);
    font-size: 12px;
    outline: none;
    cursor: pointer;
    transition: all 0.25s ease;
}

.filter-select select:focus {
    border-color: var(--pink);
    box-shadow: 0 0 0 3px rgba(233, 139, 170, 0.15);
}

.btn-reset {
    height: 44px;
    padding: 0 18px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: #FFFFFF;
    color: var(--gray-text);
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.25s ease;
}

.btn-reset:hover {
    background: var(--pink-light);
    color: var(--pink);
    border-color: #F6D6E1;
}

/* =========================================================
   TABLE DATA CARD
========================================================= */

.table-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 18px;
    box-shadow: var(--shadow);
    overflow: hidden;
}

.table-header {
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #EEF3F5;
}

.table-heading {
    display: flex;
    align-items: center;
    gap: 12px;
}

.table-icon {
    width: 39px;
    height: 39px;
    border-radius: 50%;
    background: var(--pink-light);
    color: var(--pink);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.table-title {
    font-size: 15px;
    font-weight: 750;
    color: var(--blue-dark);
}

.table-subtitle {
    font-size: 10px;
    color: var(--gray-text);
    margin-top: 2px;
}

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

thead th {
    padding: 14px 24px;
    background: #F8FCFD;
    color: var(--gray-text);
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}

tbody td {
    padding: 16px 24px;
    border-bottom: 1px solid #EEF3F5;
    color: var(--dark-text);
    font-size: 12px;
    vertical-align: middle;
}

tbody tr { transition: background 0.2s ease; }
tbody tr:hover { background: var(--blue-pale); }
tbody tr:last-child td { border-bottom: none; }

.order-id {
    font-weight: 800;
    color: var(--blue-dark);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.order-id i { color: var(--pink); font-size: 10px; }

.customer-name { font-weight: 700; color: var(--dark-text); }
.service-name { color: var(--gray-text); font-weight: 600; }

/* BADGES */

.status-badge, .flow-badge, .source-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.badge-pending { background: var(--pink-light); color: var(--pink-dark); }
.badge-processing { background: var(--blue-light); color: var(--blue-dark); }
.badge-ready { background: #E3F4F9; color: #3A7F93; }
.badge-completed { background: #E3F2EA; color: #4B8E71; }
.badge-default { background: #EEF3F5; color: var(--gray-text); }

.flow-admin { background: #EBF3F7; color: var(--blue-dark); border: 1px solid #D1E5EE; }
.flow-design { background: var(--pink-light); color: var(--pink-dark); border: 1px solid #F6C8D7; }
.flow-production { background: #E2F5EC; color: #3C8363; border: 1px solid #C4ECDA; }

.source-normal { background: var(--blue-pale); color: var(--blue-dark); }
.source-custom { background: var(--pink-pale); color: var(--pink-dark); border: 1px solid var(--pink-light); }

.btn-action-view {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: var(--blue-pale);
    color: var(--blue-dark);
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    transition: all 0.25s ease;
}

.btn-action-view:hover {
    background: var(--pink);
    color: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(233, 139, 170, 0.25);
}

.empty-state {
    text-align: center;
    padding: 50px 20px !important;
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

.table-footer {
    padding: 14px 24px;
    background: #F8FCFD;
    border-top: 1px solid #EEF3F5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11px;
    color: var(--gray-text);
}

.table-footer strong { color: var(--blue-dark); }

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

.logout-modal.show .modal-box { transform: scale(1); }

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

.btn-confirm-logout, .btn-cancel-logout {
    flex: 1;
    border: none;
    padding: 11px 18px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-confirm-logout { background: var(--pink); color: #FFFFFF; }
.btn-confirm-logout:hover { background: var(--pink-dark); }

.btn-cancel-logout { background: var(--blue-pale); color: var(--blue-dark); }
.btn-cancel-logout:hover { background: var(--blue-light); }

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
    .filter-select select, .btn-reset { width: 100%; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 520px) {
    .sidebar-menu { grid-template-columns: 1fr; }
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
        <li class="active">
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
            <span class="welcome-text">Order Management</span>
            <h1 class="page-title">Manage Orders</h1>
            <p class="page-subtitle">View, search, and manage all active customer orders in one place.</p>
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
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="stat-label">Showing Orders</div>
            <div class="stat-value"><?php echo number_format($total_display_orders); ?></div>
        </div>

        <div class="stat-card pending-card">
            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?php echo number_format($pending_count); ?></div>
        </div>

        <div class="stat-card processing-card">
            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-gears"></i>
                </div>
            </div>
            <div class="stat-label">Processing</div>
            <div class="stat-value"><?php echo number_format($processing_count); ?></div>
        </div>

        <div class="stat-card ready-card">
            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>
            </div>
            <div class="stat-label">Ready</div>
            <div class="stat-value"><?php echo number_format($ready_count); ?></div>
        </div>

        <div class="stat-card completed-card">
            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="stat-label">Completed</div>
            <div class="stat-value"><?php echo number_format($completed_count); ?></div>
        </div>

    </div>


    <!-- TOOLBAR (SEARCH & FILTER) -->
    <div class="toolbar-card">

        <div class="toolbar-top">
            <div class="toolbar-title">
                <div class="toolbar-title-icon">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                Search &amp; Filter Orders
            </div>

            <div class="result-badge">
                <?php echo $total_display_orders; ?> Result(s)
            </div>
        </div>

        <form method="GET" action="manage_orders.php" class="toolbar-form">

            <div class="search-input-wrapper">
                <input
                    type="text"
                    name="search"
                    placeholder="Search Order ID or Customer name..."
                    value="<?php echo htmlspecialchars($search_query); ?>"
                >
                <i class="fa-solid fa-magnifying-glass" onclick="this.closest('form').submit();"></i>
            </div>

            <div class="filter-select">
                <select name="status" onchange="this.closest('form').submit();">
                    <option value="Active" <?php echo ($filter_status == 'Active') ? 'selected' : ''; ?>>
                        Filter: Active Orders Only
                    </option>
                    <option value="All" <?php echo ($filter_status == 'All') ? 'selected' : ''; ?>>
                        All Orders (Inc. Completed)
                    </option>
                    <option value="Pending" <?php echo ($filter_status == 'Pending') ? 'selected' : ''; ?>>
                        Pending
                    </option>
                    <option value="Processing" <?php echo ($filter_status == 'Processing') ? 'selected' : ''; ?>>
                        Processing
                    </option>
                    <option value="Ready" <?php echo ($filter_status == 'Ready') ? 'selected' : ''; ?>>
                        Ready
                    </option>
                    <option value="Completed" <?php echo ($filter_status == 'Completed') ? 'selected' : ''; ?>>
                        Completed
                    </option>
                </select>
            </div>

            <div class="filter-select">
                <select name="source" onchange="this.closest('form').submit();">
                    <option value="All" <?php echo ($filter_source === 'All') ? 'selected' : ''; ?>>
                        All Order Types
                    </option>
                    <option value="Normal Order" <?php echo ($filter_source === 'Normal Order') ? 'selected' : ''; ?>>
                        Normal Orders
                    </option>
                    <option value="Custom Request" <?php echo ($filter_source === 'Custom Request') ? 'selected' : ''; ?>>
                        Custom Requests
                    </option>
                </select>
            </div>

            <?php if ($search_query !== '' || $filter_status !== 'Active' || $filter_source !== 'All'): ?>
                <a href="manage_orders.php" class="btn-reset">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            <?php endif; ?>

        </form>

    </div>


    <!-- TABLE CONTAINER -->
    <div class="table-card">

        <div class="table-header">
            <div class="table-heading">
                <div class="table-icon">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <div class="table-title">Customer Orders</div>
                    <div class="table-subtitle">Review and manage recent active incoming orders</div>
                </div>
            </div>

            <div class="result-badge">
                <?php echo $total_display_orders; ?> Total
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Flow Job Sheet</th>
                        <th>Source</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($filtered_orders) > 0): ?>

                    <?php foreach ($filtered_orders as $order): ?>

                        <?php
                        // Status Badge Setup
                        $statusClass = 'badge-default';
                        $statusIcon  = 'fa-circle';

                        switch (strtolower($order['status'])) {
                            case 'pending':
                                $statusClass = 'badge-pending';
                                $statusIcon  = 'fa-clock';
                                break;

                            case 'processing':
                                $statusClass = 'badge-processing';
                                $statusIcon  = 'fa-gears';
                                break;

                            case 'ready':
                                $statusClass = 'badge-ready';
                                $statusIcon  = 'fa-box-open';
                                break;

                            case 'completed':
                                $statusClass = 'badge-completed';
                                $statusIcon  = 'fa-circle-check';
                                break;
                        }

                        // Flow Job Sheet Badge Setup
                        $flowClass = 'flow-admin';
                        $flowIcon  = 'fa-user-gear';

                        switch (strtolower($order['job_flow'])) {
                            case 'design':
                                $flowClass = 'flow-design';
                                $flowIcon  = 'fa-pen-ruler';
                                break;

                            case 'production':
                                $flowClass = 'flow-production';
                                $flowIcon  = 'fa-print';
                                break;

                            case 'admin':
                            default:
                                $flowClass = 'flow-admin';
                                $flowIcon  = 'fa-user-gear';
                                break;
                        }

                        $sourceClass = ($order['source'] === 'Custom Request') ? 'source-custom' : 'source-normal';
                        $sourceIcon  = ($order['source'] === 'Custom Request') ? 'fa-wand-magic-sparkles' : 'fa-cart-shopping';
                        ?>

                        <tr>
                            <td>
                                <span class="order-id">
                                    <i class="fa-solid fa-hashtag"></i>
                                    <?php echo htmlspecialchars($order['id']); ?>
                                </span>
                            </td>

                            <td>
                                <span class="customer-name">
                                    <?php echo htmlspecialchars($order['customer']); ?>
                                </span>
                            </td>

                            <td>
                                <span class="service-name">
                                    <?php echo htmlspecialchars($order['service']); ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <i class="fa-solid <?php echo $statusIcon; ?>"></i>
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>

                            <td>
                                <span class="flow-badge <?php echo $flowClass; ?>">
                                    <i class="fa-solid <?php echo $flowIcon; ?>"></i>
                                    <?php echo htmlspecialchars(ucfirst($order['job_flow'])); ?>
                                </span>
                            </td>

                            <td>
                                <span class="source-badge <?php echo $sourceClass; ?>">
                                    <i class="fa-solid <?php echo $sourceIcon; ?>"></i>
                                    <?php echo htmlspecialchars($order['source']); ?>
                                </span>
                            </td>

                            <td>
                                <a href="ViewPage.php?id=<?php echo urlencode($order['id']); ?>&source=<?php echo urlencode($order['source']); ?>" class="btn-action-view">
                                    <i class="fa-solid fa-eye"></i> Details
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-icon">
                                <i class="fa-solid fa-box-open"></i>
                            </div>
                            <div class="empty-title">No active orders found</div>
                            <div class="empty-text">Try changing your search or selecting 'All Orders' in the filter dropdown.</div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div>
                Showing <strong><?php echo $total_display_orders; ?></strong> order record(s)
            </div>

            <div>
                <i class="fa-solid fa-shield-halved"></i> Order Management Active
            </div>
        </div>

    </div>

</main>


<!-- =========================================================
     LOGOUT CONFIRMATION MODAL
========================================================= -->

<div id="logoutConfirmationModal" class="logout-modal">
    <div class="modal-box">
        <div class="modal-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h3 class="modal-title">Confirm Logout</h3>

        <p class="modal-text">
            Are you sure you want to log out of your administration panel?
        </p>

        <div class="modal-buttons">
            <button type="button" onclick="closeLogoutModal()" class="btn-cancel-logout">Cancel</button>
            <button type="button" onclick="executeLogout()" class="btn-confirm-logout">Logout</button>
        </div>
    </div>
</div>


<script>

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

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLogoutModal();
    }
});

</script>

</body>

</html>
