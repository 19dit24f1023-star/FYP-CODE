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

/* =========================================================
   1. TOTAL ORDERS & CUSTOM REQUESTS
========================================================= */
$total_orders = 0;
$custom_pending = 0;

// Get total count of custom requests first
$stmtCustom = $conn->prepare("SELECT COUNT(*) AS total FROM custom_request");
if ($stmtCustom && $stmtCustom->execute()) {
    $res = $stmtCustom->get_result();
    if ($row = $res->fetch_assoc()) {
        $custom_pending = (int)$row['total'];
    }
    $stmtCustom->close();
}

// Get total count of all orders combined
$sqlTotal = "SELECT (SELECT COUNT(*) FROM orders) + ? AS total";
$stmtTotal = $conn->prepare($sqlTotal);
if ($stmtTotal) {
    $stmtTotal->bind_param("i", $custom_pending);
    $stmtTotal->execute();
    $res = $stmtTotal->get_result();
    if ($row = $res->fetch_assoc()) {
        $total_orders = (int)$row['total'];
    }
    $stmtTotal->close();
}

/* =========================================================
   2. FETCH ALL ORDERS (ORDERS + CUSTOM REQUESTS) & COUNT BY STATUS
========================================================= */
$all_orders = [];

// Gabungkan jadual orders dan custom_request
$sqlOrders = "
    SELECT status FROM orders
    UNION ALL
    SELECT COALESCE(status, 'pending') AS status FROM custom_request
";

$resOrders = $conn->query($sqlOrders);
if ($resOrders) {
    while ($row = $resOrders->fetch_assoc()) {
        $all_orders[] = $row;
    }
}

// Count orders by status
$pending_orders    = [];
$processing_orders = [];
$ready_orders      = [];
$completed_orders  = [];

foreach ($all_orders as $order) {
    switch (strtolower($order['status'])) {
        case 'pending':
            $pending_orders[] = $order;
            break;
        case 'processing':
            $processing_orders[] = $order;
            break;
        case 'ready':
            $ready_orders[] = $order;
            break;
        case 'completed':
            $completed_orders[] = $order;
            break;
    }
}

$processing_count = count($processing_orders);

/* =========================================================
   3. MONTHLY ORDERS
========================================================= */
$monthly_orders = array_fill(1, 12, 0);

$sqlMonthly = "
    SELECT MONTH(created_at) AS month, COUNT(*) AS total
    FROM (
        SELECT created_at FROM orders
        UNION ALL
        SELECT created_at FROM custom_request
    ) AS all_orders
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
";

$stmtMonthly = $conn->prepare($sqlMonthly);
if ($stmtMonthly && $stmtMonthly->execute()) {
    $res = $stmtMonthly->get_result();
    while ($row = $res->fetch_assoc()) {
        $month = (int)$row['month'];
        if ($month >= 1 && $month <= 12) {
            $monthly_orders[$month] = (int)$row['total'];
        }
    }
    $stmtMonthly->close();
}

/* =========================================================
   4. SERVICE POPULARITY
========================================================= */
$service_labels = [];
$service_data   = [];

$sqlServices = "
    SELECT product_name, SUM(quantity) AS total
    FROM (
        SELECT product_name, quantity FROM orders
        UNION ALL
        SELECT product_name, quantity FROM custom_request
    ) AS all_orders
    GROUP BY product_name
    ORDER BY total DESC
";

$stmtServices = $conn->prepare($sqlServices);
if ($stmtServices && $stmtServices->execute()) {
    $res = $stmtServices->get_result();
    while ($row = $res->fetch_assoc()) {
        $service_labels[] = $row['product_name'];
        $service_data[]   = (int)$row['total'];
    }
    $stmtServices->close();
}

/* =========================================================
   5. CHART PREPARATION & METRICS
========================================================= */
$pie_data = [
    count($pending_orders),
    count($processing_orders),
    count($ready_orders),
    count($completed_orders)
];

$monthly_total = array_sum($monthly_orders);
$current_year  = date('Y');
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="ui_polish.css">
<title>Admin Dashboard | SA Design</title>

<!-- FONT AWESOME -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

button {
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
   MAIN CONTENT
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
   HERO GRID
========================================================= */
.hero-grid {
    display: grid;
    grid-template-columns: 1.6fr 0.9fr;
    gap: 18px;
    margin-bottom: 20px;
}

.hero-card {
    min-height: 245px;
    border-radius: 20px;
    padding: 25px 28px;
    background: linear-gradient(135deg, #4F8FA8 0%, #73B1C6 52%, #E98BAA 130%);
    position: relative;
    overflow: hidden;
    color: #FFFFFF;
    box-shadow: 0 12px 30px rgba(79,143,168,0.16);
}

.hero-card::before {
    content: "";
    position: absolute;
    width: 250px;
    height: 250px;
    border-radius: 50%;
    background: rgba(255,255,255,0.09);
    right: -80px;
    top: -110px;
}

.hero-card::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,0.07);
    right: 70px;
    bottom: -120px;
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 62%;
}

.hero-label {
    font-size: 12px;
    font-weight: 600;
    opacity: 0.88;
    margin-bottom: 5px;
}

.hero-number {
    font-size: 53px;
    line-height: 1;
    font-weight: 300;
    letter-spacing: -2px;
    margin-bottom: 17px;
}

.hero-description {
    font-size: 11px;
    line-height: 1.6;
    opacity: 0.90;
    max-width: 280px;
    margin-bottom: 18px;
}

.hero-metrics {
    display: flex;
    gap: 22px;
}

.hero-metric {
    display: flex;
    flex-direction: column;
}

.hero-metric-label {
    font-size: 9px;
    opacity: 0.72;
    margin-bottom: 2px;
}

.hero-metric-value {
    font-size: 17px;
    font-weight: 700;
}

.hero-action {
    position: absolute;
    z-index: 3;
    right: 24px;
    bottom: 22px;
    padding: 12px 16px;
    background: rgba(255,255,255,0.96);
    color: var(--blue-dark);
    border-radius: 10px;
    font-size: 10px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 9px;
    transition: all 0.25s ease;
}

.hero-action:hover {
    color: var(--pink);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(40,80,100,0.14);
}

.activity-card {
    min-height: 245px;
    padding: 24px;
    border-radius: 20px;
    background: linear-gradient(145deg, #E2F5FB, #FFF0F5);
    border: 1px solid #D5EAF1;
    position: relative;
    overflow: hidden;
}

.activity-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--dark-text);
    margin-bottom: 7px;
}

.activity-number {
    font-size: 46px;
    line-height: 1;
    font-weight: 400;
    letter-spacing: -2px;
    color: var(--blue-dark);
}

.activity-year {
    display: inline-block;
    margin-left: 6px;
    padding: 4px 7px;
    background: rgba(255,255,255,0.75);
    color: var(--pink);
    border-radius: 6px;
    font-size: 9px;
    font-weight: 800;
    vertical-align: middle;
}

.activity-text {
    margin-top: 14px;
    font-size: 11px;
    line-height: 1.6;
    color: #637B86;
    max-width: 260px;
}

.activity-gauge {
    position: absolute;
    width: 100px;
    height: 50px;
    border: 8px solid rgba(255,255,255,0.75);
    border-bottom: 0;
    border-radius: 100px 100px 0 0;
    right: 24px;
    top: 65px;
    transform: rotate(-8deg);
}

.activity-gauge::after {
    content: "";
    position: absolute;
    width: 7px;
    height: 7px;
    background: #FFFFFF;
    border: 3px solid var(--pink);
    border-radius: 50%;
    right: 10px;
    bottom: -4px;
}

.insight-box {
    position: absolute;
    left: 18px;
    right: 18px;
    bottom: 18px;
    min-height: 52px;
    padding: 9px 10px;
    background: rgba(255,255,255,0.78);
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.insight-left {
    display: flex;
    align-items: center;
    gap: 9px;
}

.insight-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: var(--pink-light);
    color: var(--pink);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.insight-text {
    font-size: 9px;
    line-height: 1.35;
    color: #667A83;
}

.insight-play {
    width: 31px;
    height: 31px;
    border: none;
    border-radius: 50%;
    background: var(--pink);
    color: #FFFFFF;
    cursor: pointer;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s ease;
}

.insight-play:hover {
    background: var(--blue);
    transform: scale(1.05);
}

/* =========================================================
   SECTION TITLE & STATISTICS
========================================================= */
.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 4px 0 13px;
}

.section-title {
    font-size: 16px;
    font-weight: 750;
    color: var(--blue-dark);
}

.section-caption {
    font-size: 10px;
    color: var(--gray-text);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
    margin-bottom: 19px;
}

.stat-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 17px;
    min-height: 122px;
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

.stat-description {
    margin-top: 7px;
    font-size: 9px;
    color: #9AA9B0;
}

/* =========================================================
   CHART CARDS
========================================================= */
.chart-section-large,
.chart-section-small {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: 17px;
    box-shadow: var(--shadow);
}

.chart-section-large {
    padding: 21px 23px 20px;
    margin-bottom: 18px;
}

.chart-grid-bottom {
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 18px;
}

.chart-section-small {
    padding: 20px;
    height: 360px;
    display: flex;
    flex-direction: column;
}

.chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 17px;
}

.chart-heading {
    display: flex;
    align-items: center;
    gap: 10px;
}

.chart-icon {
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

.chart-title {
    font-size: 15px;
    font-weight: 750;
    color: var(--blue-dark);
}

.chart-description {
    font-size: 10px;
    color: var(--gray-text);
    margin-top: 3px;
}

.chart-badge {
    padding: 7px 11px;
    background: var(--blue-pale);
    color: var(--blue);
    border-radius: 20px;
    font-size: 9px;
    font-weight: 800;
}

.large-chart-container {
    position: relative;
    width: 100%;
    height: 245px;
}

.chart-container {
    position: relative;
    width: 100%;
    flex: 1;
    min-height: 0;
}

.pie-chart-container {
    position: relative;
    width: 205px;
    height: 205px;
    margin: 0 auto;
}

.chart-footer {
    margin-top: 5px;
    padding-top: 11px;
    border-top: 1px solid #EEF3F5;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chart-footer-text {
    font-size: 10px;
    color: #9AA9B0;
}

.chart-footer-link {
    font-size: 10px;
    font-weight: 750;
    color: var(--pink);
}

.chart-footer-link:hover { color: var(--blue); }
.chart-footer-link i { margin-left: 4px; }

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
@media (max-width: 1250px) {
    .sidebar { width: 225px; }
    .main-content { margin-left: 267px; padding-left: 28px; padding-right: 28px; }
    .hero-grid { grid-template-columns: 1.3fr 0.8fr; }
}

@media (max-width: 1050px) {
    .sidebar { width: 215px; left: 16px; }
    .main-content { margin-left: 247px; padding: 28px 22px 40px; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .hero-grid { grid-template-columns: 1fr; }
    .chart-grid-bottom { grid-template-columns: 1fr; }
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
    .header-right { width: 100%; }
    .status-pill { width: 100%; justify-content: center; }
    .hero-content { max-width: 75%; }
}

@media (max-width: 520px) {
    .sidebar-menu { grid-template-columns: 1fr; }
    .brand-main { font-size: 23px; }
    .page-title { font-size: 25px; }
    .stats-grid { grid-template-columns: 1fr; }
    .hero-card, .activity-card { min-height: 250px; padding: 22px; }
    .hero-content { max-width: 100%; }
    .hero-number { font-size: 46px; }
    .hero-action { right: 20px; bottom: 18px; }
    .activity-gauge { opacity: 0.65; }
    .chart-section-large, .chart-section-small { padding: 17px; border-radius: 14px; }
    .large-chart-container { height: 220px; }
    .chart-section-small { height: 345px; }
    .pie-chart-container { width: 185px; height: 185px; }
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
        <li class="active">
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
            <span class="welcome-text">Welcome back, Admin 👋</span>
            <h1 class="page-title">Order Dashboard</h1>
            <p class="page-subtitle">Monitor your orders and business performance.</p>
        </div>
        <div class="header-right">
            <div class="status-pill">
                <span class="status-dot"></span> System Online
            </div>
        </div>
    </div>

    <!-- HERO SECTION -->
    <div class="hero-grid">
        <div class="hero-card">
            <div class="hero-content">
                <div class="hero-label">Total orders received</div>
                <div class="hero-number"><?php echo number_format(count($all_orders)); ?></div>
                <div class="hero-description">
                    Keep track of your incoming orders, current order progress and completed customer requests.
                </div>
                <div class="hero-metrics">
                    <div class="hero-metric">
                        <div class="hero-metric-label">Pending</div>
                        <div class="hero-metric-value"><?php echo number_format(count($pending_orders)); ?></div>
                    </div>
                    <div class="hero-metric">
                        <div class="hero-metric-label">Processing</div>
                        <div class="hero-metric-value"><?php echo number_format(count($processing_orders)); ?></div>
                    </div>
                    <div class="hero-metric">
                        <div class="hero-metric-label">Completed</div>
                        <div class="hero-metric-value"><?php echo number_format(count($completed_orders)); ?></div>
                    </div>
                </div>
            </div>
            <a href="manage_orders.php" class="hero-action">
                VIEW FULL ORDERS <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="activity-card">
            <div class="activity-title">Business Activity</div>
            <div class="activity-number">
                <?php echo number_format($monthly_total); ?>
                <span class="activity-year"><?php echo $current_year; ?></span>
            </div>
            <p class="activity-text">
                Total order activity recorded throughout the year. Keep monitoring your dashboard to manage daily operations.
            </p>
            <div class="activity-gauge"></div>
            <div class="insight-box">
                <div class="insight-left">
                    <div class="insight-icon"><i class="fa-solid fa-lightbulb"></i></div>
                    <div class="insight-text">Review your order status and service performance regularly.</div>
                </div>
                <button type="button" class="insight-play" onclick="document.getElementById('monthlySection').scrollIntoView({ behavior: 'smooth' });">
                    <i class="fa-solid fa-play"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ORDER OVERVIEW -->
    <div class="section-heading">
        <div class="section-title">Order Overview</div>
        <div class="section-caption">Current order statistics</div>
    </div>

    <div class="stats-grid">
        <div class="stat-card total-card">
            <div class="stat-top">
                <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            </div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?php echo count($all_orders); ?></div>
        </div>

        <div class="stat-card pending-card">
            <div class="stat-top">
                <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
            </div>
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?php echo count($pending_orders); ?></div>
        </div>

        <div class="stat-card processing-card">
            <div class="stat-top">
                <div class="stat-icon"><i class="fa-solid fa-gears"></i></div>
            </div>
            <div class="stat-label">Processing</div>
            <div class="stat-value"><?php echo $processing_count; ?></div>
        </div>

        <div class="stat-card ready-card">
            <div class="stat-top">
                <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
            </div>
            <div class="stat-label">Ready</div>
            <div class="stat-value"><?php echo count($ready_orders); ?></div>
        </div>

        <div class="stat-card completed-card">
            <div class="stat-top">
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            </div>
            <div class="stat-label">Completed</div>
            <div class="stat-value"><?php echo count($completed_orders); ?></div>
        </div>
    </div>

    <!-- MONTHLY ORDERS -->
    <div class="chart-section-large" id="monthlySection">
        <div class="chart-header">
            <div class="chart-heading">
                <div class="chart-icon"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <div class="chart-title">Monthly Orders</div>
                    <div class="chart-description">Order activity throughout the year</div>
                </div>
            </div>
            <div class="chart-badge"><?php echo $current_year; ?> Overview</div>
        </div>
        <div class="large-chart-container">
            <canvas id="monthlyLineChart"></canvas>
        </div>
    </div>

    <!-- BOTTOM CHARTS -->
    <div class="chart-grid-bottom">
        <div class="chart-section-small">
            <div class="chart-header">
                <div class="chart-heading">
                    <div class="chart-icon"><i class="fa-solid fa-chart-column"></i></div>
                    <div>
                        <div class="chart-title">Service Popularity</div>
                        <div class="chart-description">Most ordered services</div>
                    </div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="serviceBarChart"></canvas>
            </div>
        </div>

        <div class="chart-section-small">
            <div class="chart-header">
                <div class="chart-heading">
                    <div class="chart-icon"><i class="fa-solid fa-chart-pie"></i></div>
                    <div>
                        <div class="chart-title">Order Status</div>
                        <div class="chart-description">Current order distribution</div>
                    </div>
                </div>
            </div>
            <div class="pie-chart-container">
                <canvas id="recentPieChart"></canvas>
            </div>
            <div class="chart-footer">
                <span class="chart-footer-text">Order status overview</span>
                <a href="manage_orders.php" class="chart-footer-link">
                    View Orders <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

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

<!-- CHART JAVASCRIPT -->
<script>
Chart.defaults.font.family = '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif';
Chart.defaults.color = '#7C929C';

// MONTHLY ORDERS LINE CHART
const ctxLine = document.getElementById('monthlyLineChart').getContext('2d');
const lineGradient = ctxLine.createLinearGradient(0, 0, 0, 250);
lineGradient.addColorStop(0, 'rgba(233, 139, 170, 0.30)');
lineGradient.addColorStop(0.55, 'rgba(233, 139, 170, 0.12)');
lineGradient.addColorStop(1, 'rgba(233, 139, 170, 0.02)');

new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Monthly Orders',
            data: <?php echo json_encode(array_values($monthly_orders)); ?>,
            borderColor: '#E98BAA',
            backgroundColor: lineGradient,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 7,
            pointBackgroundColor: '#FFFFFF',
            pointBorderColor: '#E98BAA',
            pointBorderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#39758D',
                titleColor: '#FFFFFF',
                bodyColor: '#EAF6FB',
                padding: 11,
                cornerRadius: 10,
                displayColors: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                border: { display: false },
                grid: { color: 'rgba(79,143,168,0.12)' },
                ticks: { color: '#94A5AD', font: { size: 10 }, padding: 7 }
            },
            x: {
                border: { display: false },
                grid: { display: false },
                ticks: { color: '#8A9AA1', font: { size: 10 } }
            }
        }
    }
});

// SERVICE POPULARITY BAR CHART
const ctxBar = document.getElementById('serviceBarChart').getContext('2d');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($service_labels); ?>,
        datasets: [{
            label: 'Units Ordered',
            data: <?php echo json_encode($service_data); ?>,
            backgroundColor: [
                '#E98BAA', '#F0A8C0', '#F5BED0', '#D8EEF5',
                '#BBDCE8', '#4F8FA8', '#73AFC1', '#9BC7D5'
            ],
            borderRadius: 7,
            borderSkipped: false,
            maxBarThickness: 36
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#39758D',
                titleColor: '#FFFFFF',
                bodyColor: '#EAF6FB',
                padding: 10,
                cornerRadius: 9,
                displayColors: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                border: { display: false },
                grid: { color: 'rgba(79,143,168,0.11)' },
                ticks: { color: '#94A5AD', font: { size: 9 } }
            },
            x: {
                border: { display: false },
                grid: { display: false },
                ticks: { color: '#71858E', font: { size: 9 }, maxRotation: 35, minRotation: 0 }
            }
        }
    }
});

// ORDER STATUS DOUGHNUT CHART
const ctxPie = document.getElementById('recentPieChart').getContext('2d');
new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Processing', 'Ready', 'Completed'],
        datasets: [{
            data: <?php echo json_encode($pie_data); ?>,
            backgroundColor: ['#E98BAA', '#4F8FA8', '#86B9C9', '#73B394'],
            borderWidth: 0,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '69%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 12,
                    color: '#71858E',
                    font: { size: 9, weight: '600' }
                }
            },
            tooltip: {
                backgroundColor: '#39758D',
                titleColor: '#FFFFFF',
                bodyColor: '#EAF6FB',
                padding: 10,
                cornerRadius: 9
            }
        }
    }
});

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
