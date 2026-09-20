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

// 1. Feedback message (Notification Pop-up)
$message = "";
if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    unset($_SESSION['msg']);
}

// 2. Check ID parameter from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_orders.php");
    exit();
}

$order_id = $_GET['id'];
$source   = isset($_GET['source']) ? $_GET['source'] : 'Normal Order';

// Variables to store order information
$customer_name  = "N/A";
$customer_phone = "N/A";
$customer_email = "N/A";
$service_name   = "N/A";
$quantity       = "1";
$size           = "N/A";
$pickup_date    = "N/A";
$order_ref      = "N/A";
$payment_status = "Unpaid";
$amount         = "0.00";
$payment_method = "N/A";
$file_path      = "";
$order_status   = "Pending";
$jobsheet_flow  = "Admin";
$delivery_address = "N/A"; // <-- Tambah ini
$collection_method= "N/A";

// 3. PROCESS STATUS, PRICE & PAYMENT UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $new_flow   = mysqli_real_escape_string($conn, $_POST['jobsheet_flow']);
    
    if ($source == "Custom Request") {
        $numeric_id     = intval(preg_replace('/[^0-9]/', '', $order_id));
        $new_price      = isset($_POST['price']) ? (float)$_POST['price'] : 0.00;
        $new_pay_status = mysqli_real_escape_string($conn, $_POST['payment_status']);

        $update_sql = "UPDATE custom_request 
                       SET status = '$new_status', 
                           job_flow = '$new_flow', 
                           price = '$new_price', 
                           payment_status = '$new_pay_status' 
                       WHERE id = '$numeric_id'";
    } else {
        $update_sql = "UPDATE orders SET status = '$new_status', job_flow = '$new_flow' WHERE order_number = '$order_id'";
    }

    if (mysqli_query($conn, $update_sql)) {
        $_SESSION['msg'] = "Order details updated successfully!";
    } else {
        $_SESSION['msg'] = "Failed to update order: " . mysqli_error($conn);
    }

    header("Location: ViewPage.php?id=" . urlencode($order_id) . "&source=" . urlencode($source));
    exit();
}

// 4. RETRIEVE DATA FROM DATABASE
if ($source == "Custom Request") {
    $numeric_id = intval(preg_replace('/[^0-9]/', '', $order_id));
    $sql = "SELECT * FROM custom_request WHERE id = '$numeric_id'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $data           = mysqli_fetch_assoc($result);
        $customer_name  = $data['fullname'] ?? "N/A";
        $customer_phone = $data['phone'] ?? "N/A";
        $customer_email = $data['email'] ?? "N/A";
        $service_name   = $data['product_name'] ?? "Custom Design";
        $quantity       = $data['quantity'] ?? "1";
        $size           = $data['size'] ?? "Custom";
        $pickup_date    = $data['pickup_date'] ?? "N/A";
        $order_ref      = $data['order_ref'] ?? "SAD-" . date('Ymd') . "-" . strtoupper(substr(md5($order_id), 0, 5));
        $payment_status = $data['payment_status'] ?? "Pending";
        $amount         = number_format($data['price'] ?? 0, 2);
        $payment_method = $data['payment_method'] ?? "Online banking / FPX";
        $file_path      = $data['artwork'] ?? "";
        $order_status   = $data['status'] ?? "Pending";
        $jobsheet_flow  = $data['job_flow'] ?? "Admin";
        $delivery_address  = $data['delivery_address'] ?? "N/A";
        $collection_method = $data['collection_method'] ?? "N/A";
    }
} else {
    // Normal Order
    $sql = "SELECT 
                orders.*, 
                customers.name AS cust_name, 
                customers.phone_number AS cust_phone, 
                customers.email AS cust_email 
            FROM orders 
            LEFT JOIN customers ON orders.user_id = customers.id 
            WHERE orders.order_number = '$order_id'";
            
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $data           = mysqli_fetch_assoc($result);
        $customer_name  = $data['cust_name'] ?? "Unknown";
        $customer_phone = $data['cust_phone'] ?? "N/A";
        $customer_email = $data['cust_email'] ?? "N/A";
        $service_name   = $data['product_name'] ?? "N/A";
        $quantity       = $data['quantity'] ?? "1";
        $size           = $data['size'] ?? "CODE 4912 (47x18mm)";
        $pickup_date    = $data['pickup_date'] ?? "N/A";
        $order_ref      = $data['order_ref'] ?? $order_id;
        $payment_status = $data['payment_status'] ?? "Paid";
        $amount         = number_format($data['order_total'] ?? $data['total_price'] ?? 0, 2);
        $payment_method = $data['payment_method'] ?? "Online banking / FPX";
        $file_path      = $data['artwork'] ?? "";
        $order_status   = $data['status'] ?? "Pending";
        $jobsheet_flow  = $data['job_flow'] ?? "Admin";
        $delivery_address  = $data['delivery_address'] ?? "N/A";
        $collection_method = $data['collection_method'] ?? "N/A";
    }
}

// 5. FILE PATH LOGIC
$file_path = trim($file_path);

if (!empty($file_path)) {
    $clean_filename = str_replace('uploads/', '', $file_path);
    $relative_file_path = 'uploads/' . $clean_filename;
    $file_exists = true; 
} else {
    $clean_filename     = '';
    $relative_file_path = '';
    $file_exists        = false;
}
?> 
<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="ui_polish.css">
    <title>View Order Details - <?php echo htmlspecialchars($order_id); ?> | SA Design</title> 
    
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style> 
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
        width: 100%;
        text-align: left;
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

    .action-toolbar {
        display: flex;
        gap: 12px;
        margin-bottom: 22px;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 12px 22px;
        border-radius: 12px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 750;
        cursor: pointer;
        border: none;
        transition: all 0.25s ease;
    }

    .btn-save {
        background: var(--pink);
        color: #FFFFFF;
        box-shadow: 0 6px 18px rgba(233, 139, 170, 0.25);
    }

    .btn-save:hover {
        background: var(--pink-dark);
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(233, 139, 170, 0.35);
    }

    .btn-back {
        background: var(--white);
        color: var(--blue-dark);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
    }

    .btn-back:hover {
        background: var(--blue-pale);
        color: var(--blue);
        transform: translateY(-2px);
    }

    .info-cards-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .table-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .table-card-full {
        grid-column: span 2;
    }

    .table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #EEF3F5;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .table-heading {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .table-heading-icon {
        width: 39px;
        height: 39px;
        border-radius: 50%;
        background: var(--pink-light);
        color: var(--pink);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }

    .table-title {
        font-size: 15px;
        font-weight: 750;
        color: var(--blue-dark);
    }

    .table-description {
        font-size: 10px;
        color: var(--gray-text);
        margin-top: 3px;
    }

    .table-wrapper {
        width: 100%;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 12px;
    }

    tbody td {
        padding: 15px 24px;
        border-bottom: 1px solid #EEF3F5;
        color: var(--dark-text);
        vertical-align: top; /* <--- Tukar kepada top supaya sentiasa lurus dari atas */
    }

    tbody tr:last-child td {
        border-bottom: none;
    }

    tbody td:first-child {
        width: 35%;
        font-weight: 700;
        color: var(--gray-text);
        background: #F9FDFF;
    }

    tbody td:last-child {
        color: var(--dark-text);
        font-weight: 600;
    }

    .order-id-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--blue-dark);
        font-weight: 800;
    }

    .order-id-label i {
        font-size: 11px;
        color: var(--blue);
    }

    .remarks-container {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 4px 0;
    }

    .remarks-row {
        display: flex;
        align-items: center;
        font-size: 12px;
    }

    .remarks-label {
        width: 130px;
        font-weight: 700;
        color: var(--gray-text);
    }

    .remarks-value {
        color: var(--dark-text);
    }

    .badge-ref {
        background-color: var(--blue-pale);
        color: var(--blue-dark);
        padding: 4px 10px;
        border-radius: 8px;
        font-weight: 800;
        display: inline-block;
        border: 1px solid var(--border);
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 20px;
    }

    .summary-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 20px;
        box-shadow: var(--shadow);
    }

    .summary-card h3 {
        color: var(--blue-dark);
        font-size: 12px;
        font-weight: 750;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        border-bottom: 1px solid #EEF3F5;
        padding-bottom: 8px;
    }

    .file-box {
        background: var(--blue-pale);
        border: 1px dashed #B9DDE8;
        padding: 16px 14px;
        text-align: center;
        border-radius: 12px;
    }

    .file-box p {
        color: var(--dark-text);
        font-weight: 600;
        word-break: break-all;
        font-size: 11px;
    }

    .btn-file-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 11px;
        padding: 8px 14px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 700;
        color: #FFFFFF;
        transition: all 0.2s ease;
    }

    .btn-file-blue { 
        background: var(--blue); 
    }
    .btn-file-blue:hover {
        background: var(--blue-dark);
    }

    .btn-file-pink { 
        background: var(--pink); 
    }
    .btn-file-pink:hover {
        background: var(--pink-dark);
    }

    select {
        width: 100%;
        height: 42px;
        padding: 0 12px;
        font-family: inherit;
        font-size: 12px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--blue-pale);
        color: var(--dark-text);
        outline: none;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    select:focus {
        border-color: var(--pink);
        box-shadow: 0 0 0 3px rgba(233, 139, 170, 0.15);
        background: var(--white);
    }

    .mini-info-table {
        width: 100%;
    }

    .mini-info-table td {
        padding: 8px 0;
        border-bottom: 1px solid #EEF3F5;
        background: transparent !important;
    }

    .mini-info-table tr:last-child td {
        border-bottom: none;
    }

    .mini-info-table td:first-child {
        font-weight: 700;
        color: var(--gray-text);
        width: 45%;
    }

    .mini-info-table td:last-child {
        color: var(--dark-text);
        text-align: right;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge i { font-size: 8px; }
    .badge-pending { background: var(--pink-light); color: var(--pink-dark); }
    .badge-processing { background: var(--blue-light); color: var(--blue-dark); }
    .badge-ready { background: #E3F4F9; color: #5796AA; }
    .badge-completed { background: #E3F2EA; color: #559B7D; }
    .badge-default { background: #EEF3F5; color: var(--gray-text); }

    .app-modal {
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

    .app-modal.show {
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

    .app-modal.show .modal-box {
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

    .modal-icon-blue {
        background: var(--blue-light);
        color: var(--blue-dark);
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

    .btn-modal-confirm,
    .btn-modal-cancel {
        flex: 1;
        border: none;
        padding: 11px 18px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-modal-confirm {
        background: var(--pink);
        color: #FFFFFF;
    }

    .btn-modal-confirm:hover {
        background: var(--pink-dark);
    }

    .btn-modal-cancel {
        background: var(--blue-pale);
        color: var(--blue-dark);
    }

    .btn-modal-cancel:hover {
        background: var(--blue-light);
    }

    @media (max-width: 1250px) {
        .sidebar { width: 225px; }
        .main-content { margin-left: 267px; padding-left: 28px; padding-right: 28px; }
        .summary-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 1050px) {
        .sidebar { width: 215px; left: 16px; }
        .main-content { margin-left: 247px; padding: 28px 22px 40px; }
        .info-cards-row { grid-template-columns: 1fr; }
        .table-card-full { grid-column: span 1; }
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
        .summary-grid { grid-template-columns: 1fr; }
        .remarks-row { flex-direction: column; align-items: flex-start; gap: 4px; }
    }

    @media (max-width: 520px) {
        .sidebar-menu { grid-template-columns: 1fr; }
        .brand-main { font-size: 23px; }
        .page-title { font-size: 25px; }
    }
    </style> 
</head> 
<body>

<!-- SIDEBAR NAVIGATION -->
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

<!-- MAIN CONTENT AREA -->
<main class="main-content">

    <form id="operationsForm" method="POST" action="">

        <input type="hidden" name="update_status" value="1">

        <!-- TOP HEADER -->
        <div class="top-header">
            <div class="page-heading">
                <span class="welcome-text">Order Management</span>
                <h1 class="page-title">Order View Panel</h1>
                <p class="page-subtitle">Inspect order details and manage operational status.</p>
            </div>
            <div class="header-right">
                <div class="status-pill">
                    <span class="status-dot"></span> System Online
                </div>
            </div>
        </div>

        <!-- ACTIONS TOOLBAR -->
        <div class="action-toolbar">
            <button type="button" onclick="openSaveModal()" class="btn-action btn-save">
                <i class="fa-solid fa-floppy-disk"></i> Save Operations
            </button>
            <a href="manage_orders.php" class="btn-action btn-back">
                <i class="fa-solid fa-arrow-left"></i> Return Back
            </a>
        </div>

        <!-- CUSTOMER + ORDER RECORDS -->
        <div class="info-cards-row">
            
            <!-- CUSTOMER INFO CARD -->
            <div class="table-card">
                <div class="table-header">
                    <div class="table-heading">
                        <div class="table-heading-icon"><i class="fa-solid fa-user-tie"></i></div>
                        <div>
                            <div class="table-title">Customer Information</div>
                            <div class="table-description">Personal client profile metadata logs</div>
                        </div>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table>
                        <tr>
                            <td>Full Client Name</td>
                            <td><?php echo htmlspecialchars($customer_name); ?></td>
                        </tr>
                        <tr>
                            <td>Phone Connection No</td>
                            <td><?php echo htmlspecialchars($customer_phone); ?></td>
                        </tr>
                        <tr>
                            <td>Digital Email Address</td>
                            <td><?php echo htmlspecialchars($customer_email); ?></td>
                        </tr>
                        <tr>
                            <td>Collection Method</td>
                            <td>
                                <span class="badge-ref">
                                    <?php echo htmlspecialchars($collection_method); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Delivery Address</td>
                            <td style="vertical-align: top; line-height: 1.5; word-break: break-word;">
                                <?php echo !empty($delivery_address) && $delivery_address !== 'N/A' 
                                    ? nl2br(htmlspecialchars($delivery_address)) 
                                    : '<em>Self Pickup / No Address Provided</em>'; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- ORDER ATTR CARD -->
            <div class="table-card">
                <div class="table-header">
                    <div class="table-heading">
                        <div class="table-heading-icon"><i class="fa-solid fa-file-invoice"></i></div>
                        <div>
                            <div class="table-title">Order Information</div>
                            <div class="table-description">Standard itemized specifications entries</div>
                        </div>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table>
                        <tr>
                            <td>Requested Service</td>
                            <td><?php echo htmlspecialchars($service_name); ?></td>
                        </tr>
                        <tr>
                            <td>Quantity Ordered</td>
                            <td><?php echo htmlspecialchars($quantity); ?> unit(s)</td>
                        </tr>
                        <tr>
                            <td>Dimensions / Size</td>
                            <td><?php echo htmlspecialchars($size); ?></td>
                        </tr>
                        <tr>
                            <td>Estimated Pickup Date</td>
                            <td><?php echo htmlspecialchars($pickup_date); ?></td>
                        </tr>
                        <tr>
                            <td>System Remarks</td>
                            <td>
                                <div class="remarks-container">
                                    <div class="remarks-row">
                                        <span class="remarks-label">Order Reference:</span>
                                        <span class="remarks-value badge-ref"><?php echo htmlspecialchars($order_ref); ?></span>
                                    </div>
                                    <div class="remarks-row">
                                        <span class="remarks-label">Payment Method:</span>
                                        <span class="remarks-value" style="font-weight: 700; color: var(--blue-dark);"><?php echo htmlspecialchars($payment_method); ?></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- PAYMENT STATEMENT DETAILS CARD -->
            <div class="table-card table-card-full">
                <div class="table-header">
                    <div class="table-heading">
                        <div class="table-heading-icon"><i class="fa-solid fa-credit-card"></i></div>
                        <div>
                            <div class="table-title">Payment Information</div>
                            <div class="table-description">Verified accounts receivables auditing ledger balances</div>
                        </div>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table>
                        <tr>
                            <td>Invoice Status</td>
                            <td>
                                <?php if ($source == "Custom Request"): ?>
                                    <select name="payment_status" style="max-width: 200px;">
                                        <option value="Pending" <?php if(strtolower($payment_status) == 'pending') echo 'selected'; ?>>Pending</option>
                                        <option value="Paid" <?php if(strtolower($payment_status) == 'paid') echo 'selected'; ?>>Paid</option>
                                        <option value="Unpaid" <?php if(strtolower($payment_status) == 'unpaid') echo 'selected'; ?>>Unpaid</option>
                                    </select>
                                <?php else: ?>
                                    <span class="status-badge <?php echo (strtolower($payment_status) == 'paid') ? 'badge-completed' : 'badge-pending'; ?>">
                                        <i class="fa-solid <?php echo (strtolower($payment_status) == 'paid') ? 'fa-circle-check' : 'fa-clock'; ?>"></i>
                                        <?php echo htmlspecialchars($payment_status); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Gross Amount Due (RM)</td>
                            <td>
                                <?php if ($source == "Custom Request"): ?>
                                    <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($data['price'] ?? '0.00'); ?>" 
                                        style="height:42px; padding:0 12px; border:1px solid var(--border); border-radius:10px; font-weight:bold; color:var(--pink); background:var(--blue-pale); width:180px;">
                                <?php else: ?>
                                    <span style="color: var(--pink); font-size: 15px; font-weight: 800;">RM <?php echo htmlspecialchars($amount); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Gateway Used</td>
                            <td><?php echo htmlspecialchars($payment_method); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>

        <!-- LOWER MULTI OPERATIONS WIDGET GRID -->
        <div class="summary-grid">
            
            <!-- UPLOADED DESIGN CARD (NO MINI IMAGE PREVIEW) -->
            <div class="summary-card">
                <h3>Uploaded Design</h3>
                <div class="file-box">
                    <?php if ($file_exists): ?>
                        
                        <div style="font-size: 35px; color: var(--blue); margin-bottom: 10px;">
                            <i class="fa-solid fa-file-image"></i>
                        </div>

                        <p style="font-size: 11px; font-weight: 700; margin-bottom: 12px;"><?php echo htmlspecialchars($clean_filename); ?></p>

                        <div style="display: flex; gap: 6px; justify-content: center;">
                            <a href="<?php echo htmlspecialchars($relative_file_path); ?>" target="_blank" class="btn-file-action btn-file-blue">
                                <i class="fa-solid fa-eye"></i> View
                            </a>
                            <a href="<?php echo htmlspecialchars($relative_file_path); ?>" download="<?php echo htmlspecialchars($clean_filename); ?>" class="btn-file-action btn-file-pink">
                                <i class="fa-solid fa-download"></i> Download
                            </a>
                        </div>

                    <?php else: ?>
                        <div style="font-size: 35px; color: var(--gray-text); margin-bottom: 8px;">
                            <i class="fa-solid fa-image-slash"></i>
                        </div>
                        <p style="color: var(--gray-text); font-weight: normal; font-size: 11px;">No vector artwork loaded / File not found</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PIPELINE WORKFLOW SELECT -->
            <div class="summary-card">
                <h3>Order Status</h3>
                <select name="status">
                    <option value="Pending" <?php if(strtolower($order_status) == 'pending') echo 'selected'; ?>>Pending</option>
                    <option value="Processing" <?php if(strtolower($order_status) == 'processing') echo 'selected'; ?>>Processing</option>
                    <option value="Ready" <?php if(strtolower($order_status) == 'ready') echo 'selected'; ?>>Ready</option>
                    <option value="Completed" <?php if(strtolower($order_status) == 'completed') echo 'selected'; ?>>Completed</option>
                </select>
            </div>

            <!-- MINI METADATA FOOTPRINT -->
            <div class="summary-card">
                <h3>Order Summary</h3>
                <table class="mini-info-table">
                    <tr>
                        <td>Order ID</td>
                        <td class="order-id-label"><i class="fa-solid fa-hashtag"></i> <?php echo htmlspecialchars($order_id); ?></td>
                    </tr>
                    <tr>
                        <td>Total Bill</td>
                        <td style="font-weight: 800; color: var(--blue-dark);">RM <?php echo htmlspecialchars($amount); ?></td>
                    </tr>
                </table>
            </div>

            <!-- JOBSHEET DEPT SELECTION -->
            <div class="summary-card">
                <h3>Flow Job Sheet</h3>
                <select name="jobsheet_flow">
                    <option value="Admin" <?php if(strtolower($jobsheet_flow) == 'admin') echo 'selected'; ?>>Admin</option>
                    <option value="Design" <?php if(strtolower($jobsheet_flow) == 'design') echo 'selected'; ?>>Design</option>
                    <option value="Production" <?php if(strtolower($jobsheet_flow) == 'production') echo 'selected'; ?>>Production</option>
                </select>
            </div>

        </div>

    </form>
</main>

<!-- SAVE OPERATIONS CONFIRMATION MODAL -->
<div id="saveConfirmationModal" class="app-modal">
    <div class="modal-box">
        <div class="modal-icon modal-icon-blue"><i class="fa-solid fa-floppy-disk"></i></div>
        <h3 class="modal-title">Save Operations</h3>
        <p class="modal-text">Are you sure you want to save the changes to this order's status and job flow?</p>
        <div class="modal-buttons">
            <button type="button" onclick="closeSaveModal()" class="btn-modal-cancel">Cancel</button>
            <button type="button" onclick="executeSaveOperations()" class="btn-modal-confirm">Save Changes</button>
        </div>
    </div>
</div>

<!-- LOGOUT CONFIRMATION MODAL -->
<div id="logoutConfirmationModal" class="app-modal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 class="modal-title">Confirm Logout</h3>
        <p class="modal-text">Are you sure you want to log out of your administration panel?</p>
        <div class="modal-buttons">
            <button type="button" onclick="closeLogoutModal()" class="btn-modal-cancel">Cancel</button>
            <button type="button" onclick="executeLogout()" class="btn-modal-confirm">Logout</button>
        </div>
    </div>
</div>

<script>
    const saveModal = document.getElementById('saveConfirmationModal');

    function openSaveModal() {
        saveModal.classList.add('show');
    }

    function closeSaveModal() {
        saveModal.classList.remove('show');
    }

    function executeSaveOperations() {
        document.getElementById('operationsForm').submit();
    }

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

    window.onclick = function(event) {
        if (event.target === logoutModal) {
            closeLogoutModal();
        }
        if (event.target === saveModal) {
            closeSaveModal();
        }
    };

    <?php if (!empty($message)): ?>
        alert(<?php echo json_encode($message); ?>);
    <?php endif; ?>
</script>

</body>
</html>
