<?php
//WHOLE PERUBAHAN
session_start();

include("db.php");

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

$page = $_GET['page'] ?? 'profile';

$sql = "SELECT * FROM customers WHERE id = ?";

// Get customer's orders
$order_sql = "SELECT * FROM orders 
              WHERE user_id = ? 
              ORDER BY created_at DESC";
$order_stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($order_stmt, "i", $customer_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);

/*
 * Group order rows by order_number.
 * One checkout can contain multiple products, but all rows from that
 * checkout share the same order_number. This makes My Orders show
 * one card per order and lets the receipt contain all products.
 */
$orders = [];
if ($order_result) {
    while ($row = mysqli_fetch_assoc($order_result)) {
        $orderNo = $row['order_number'];

        if (!isset($orders[$orderNo])) {
            $orders[$orderNo] = [
                'order_number' => $row['order_number'],
                'created_at' => $row['created_at'],
                'status' => $row['status'],
                'payment_method' => $row['payment_method'] ?? 'N/A',
                'collection_method' => $row['collection_method'] ?? 'N/A',
                'shipping_fee' => (float)($row['shipping_fee'] ?? 0),
                'products' => [],
                'subtotal' => 0
            ];
        }

        $quantity = max(1, (int)($row['quantity'] ?? 1));
        $unitPrice = (float)($row['unit_price'] ?? 0);
        $lineTotal = (float)($row['order_total'] ?? ($unitPrice * $quantity));

        $orders[$orderNo]['products'][] = [
            'product_name' => $row['product_name'] ?? 'Product',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'size' => $row['size'] ?? 'Standard'
        ];

        $orders[$orderNo]['subtotal'] += $lineTotal;

        // Keep the latest/current status available for the grouped order.
        $orders[$orderNo]['status'] = $row['status'] ?? $orders[$orderNo]['status'];
        $orders[$orderNo]['payment_method'] = $row['payment_method'] ?? $orders[$orderNo]['payment_method'];
        $orders[$orderNo]['collection_method'] = $row['collection_method'] ?? $orders[$orderNo]['collection_method'];
        $orders[$orderNo]['shipping_fee'] = (float)($row['shipping_fee'] ?? $orders[$orderNo]['shipping_fee']);
    }
}
$order_list = array_values($orders);

/*
 * Use a separate result set for Order Tracking.
 * The My Orders section consumes the first result set while grouping
 * products by order_number, so tracking needs its own query result.
 */
$tracking_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$tracking_stmt = mysqli_prepare($conn, $tracking_sql);
mysqli_stmt_bind_param($tracking_stmt, "i", $customer_id);
mysqli_stmt_execute($tracking_stmt);
$tracking_result = mysqli_stmt_get_result($tracking_stmt);

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $customer_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$customer = mysqli_fetch_assoc($result);

if (!$customer) {
    die("Customer not found.");
}

$custName = $customer['name'];
$custEmail = $customer['email'];
$loyalty_stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(order_total), 0) AS total_spent FROM orders WHERE user_id = ?");
mysqli_stmt_bind_param($loyalty_stmt, "i", $customer_id);
mysqli_stmt_execute($loyalty_stmt);
$loyalty_row = mysqli_fetch_assoc(mysqli_stmt_get_result($loyalty_stmt));
mysqli_stmt_close($loyalty_stmt);
$loyalty_points = (int) floor(((float)($loyalty_row['total_spent'] ?? 0)) / 10);
$custPhone = $customer['phone_number'];
$wallet_stmt = mysqli_prepare($conn, "SELECT balance FROM wallet_accounts WHERE customer_id = ?");
if ($wallet_stmt) {
    mysqli_stmt_bind_param($wallet_stmt, "i", $customer_id);
    mysqli_stmt_execute($wallet_stmt);
    $wallet_row = mysqli_fetch_assoc(mysqli_stmt_get_result($wallet_stmt));
    mysqli_stmt_close($wallet_stmt);
    $wallet_balance = (float)($wallet_row['balance'] ?? 0);
} else {
    $wallet_balance = 0;
}

$custImage = !empty($customer['profile_image'])
    ? $customer['profile_image']
    : 'default.png';

// ========================================
// GET CUSTOMER CUSTOM REQUESTS
// custom_request uses email, NOT customer_id
// ========================================
$custom_requests = [];

/*
 * The admin side may use a price/quotation column and a status column.
 * Detect common names so this customer page remains compatible with the
 * existing custom_request table and can immediately display admin updates.
 */
$custom_columns = [];
$column_result = mysqli_query($conn, "SHOW COLUMNS FROM custom_request");
if ($column_result) {
    while ($column = mysqli_fetch_assoc($column_result)) {
        $custom_columns[] = $column['Field'];
    }
}

$custom_price_column = null;
foreach (['price', 'quoted_price', 'custom_price', 'total_price', 'amount'] as $candidate) {
    if (in_array($candidate, $custom_columns, true)) {
        $custom_price_column = $candidate;
        break;
    }
}

$custom_status_column = null;
foreach (['status', 'request_status', 'order_status'] as $candidate) {
    if (in_array($candidate, $custom_columns, true)) {
        $custom_status_column = $candidate;
        break;
    }
}

$custom_sql = "SELECT * FROM custom_request
               WHERE email = ?
               ORDER BY id DESC";

$custom_stmt = mysqli_prepare($conn, $custom_sql);
if ($custom_stmt) {
    mysqli_stmt_bind_param($custom_stmt, "s", $custEmail);
    mysqli_stmt_execute($custom_stmt);
    $custom_result = mysqli_stmt_get_result($custom_stmt);

    if ($custom_result) {
        while ($custom_row = mysqli_fetch_assoc($custom_result)) {
            $custom_requests[] = $custom_row;
        }
    }
    mysqli_stmt_close($custom_stmt);
}

$notifications = [];
foreach ($custom_requests as $custom_notification) {
    if (isset($custom_notification['price']) && is_numeric($custom_notification['price'])
        && (float)$custom_notification['price'] > 0
        && strcasecmp((string)($custom_notification['payment_status'] ?? 'Pending'), 'Paid') !== 0) {
        $notifications[] = [
            'icon' => 'fa-credit-card',
            'text' => 'Quotation ready for CR' . str_pad((string)$custom_notification['id'], 3, '0', STR_PAD_LEFT),
            'link' => 'cust_profile.php?page=custom'
        ];
    }
}
foreach ($order_list as $notification_order) {
    $notifications[] = [
        'icon' => 'fa-box',
        'text' => 'Order ' . $notification_order['order_number'] . ' is ' . ($notification_order['status'] ?? 'Pending'),
        'link' => 'cust_profile.php?page=tracking'
    ];
    if (count($notifications) >= 5) {
        break;
    }
}
$notificationCount = count($notifications);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account | SA Design</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>

*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;font-family:'Plus Jakarta Sans',sans-serif;background:#f6e8f2;color:#182033;min-height:100vh}
a{text-decoration:none}
.navbar{min-height:84px;background:#fff;display:flex;align-items:center;justify-content:space-between;padding:16px 48px;border-bottom:1px solid #e1d8e5;position:sticky;top:0;z-index:1000;box-shadow:0 3px 18px rgba(20,40,90,.05)}
.logo{line-height:1;text-align:left;min-width:245px}.logo-main{font-weight:900;font-size:30px;letter-spacing:-2px}.logo-main .sa{color:#f0208d}.logo-main .design{color:#1557d6}.logo-subtitle{font-size:9px;letter-spacing:1px;font-weight:800;color:#182033;margin-top:5px}
.nav-links{display:flex;align-items:center;gap:34px}.nav-links a{font-size:12px;font-weight:800;text-transform:uppercase;color:#182033;transition:.2s}.nav-links a:hover,.nav-links a.active{color:#f0208d}
.nav-actions{display:flex;align-items:center;gap:12px;min-width:245px;justify-content:flex-end}.nav-btn{height:42px;padding:0 18px;border-radius:13px;border:1px solid #f0208d;background:#f0208d;color:#fff;font-weight:800;font-size:12px;display:inline-flex;align-items:center;gap:9px;box-shadow:0 8px 18px rgba(236,44,145,.16);cursor:pointer}.nav-btn.logout{background:#f0208d}.cart-btn{width:46px;height:46px;border-radius:50%;border:1px solid #f0208d;color:#f0208d;background:#fff;display:flex;align-items:center;justify-content:center;font-size:18px}
.hero{position:relative;overflow:hidden;text-align:center;padding:70px 22px 72px;background:linear-gradient(110deg,#cbd8ff 0%,#d9b9eb 48%,#ffe4f2 100%);border-bottom:1px solid #b7c8ff}.hero:before,.hero:after{content:"";position:absolute;border-radius:50%;background:rgba(40,84,197,.12);pointer-events:none}.hero:before{width:280px;height:280px;right:-45px;top:-120px}.hero:after{width:190px;height:190px;left:-85px;bottom:-105px}.hero-inner{position:relative;z-index:1}.hero-badge{display:inline-flex;align-items:center;gap:9px;padding:8px 16px;border-radius:999px;background:#fff;border:1px solid #b7c8ff;color:#1747c7;font-size:11px;font-weight:800;text-transform:uppercase}.hero h1{font-size:48px;line-height:1.05;margin:27px 0 17px;color:#102f91;letter-spacing:-2.5px}.hero h1 span{color:#f0208d}.hero p{margin:0 auto;max-width:800px;color:#64748b;font-size:12px;line-height:1.6}
.account-wrap{width:min(1120px,calc(100% - 100px));margin:45px auto 70px}.account-tabs{display:flex;gap:8px;background:#fff;border:1px solid #e1d8e5;border-radius:18px;padding:8px;margin-bottom:24px;box-shadow:0 8px 25px rgba(20,40,90,.05)}.account-tabs a{flex:1;display:flex;align-items:center;justify-content:center;gap:8px;padding:11px 10px;border-radius:12px;color:#475569;font-weight:800;font-size:12px}.account-tabs a:hover,.account-tabs a.active{background:#f0208d;color:#fff;box-shadow:0 7px 17px rgba(242,56,150,.18)}
.section-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}.section-title h2{margin:0;color:#102f91;font-size:26px;font-weight:900;letter-spacing:-1px}.section-title p{margin:5px 0 0;color:#64748b}.card{background:#fff;border:1px solid #e1d8e5;border-radius:18px;box-shadow:0 12px 30px rgba(30,50,100,.06)}
.profile-card{display:grid;grid-template-columns:250px 1fr;overflow:hidden}.profile-side{padding:24px 24px;text-align:center;background:linear-gradient(180deg,#fff3fa,#fff);border-right:1px solid #e3dce7}.avatar{width:105px;height:105px;border-radius:50%;margin:0 auto 18px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#eef3ff;border:5px solid #fff;box-shadow:0 7px 22px rgba(34,67,150,.12)}.avatar img{width:100%;height:100%;object-fit:cover}.avatar i{font-size:45px;color:#7b8eac}.profile-side h3{margin:0 0 6px;font-size:18px;color:#182033}.profile-side p{margin:0;color:#64748b;font-size:12px;word-break:break-word}.edit-main{padding:26px}.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.info-item{border:1px solid #e3dce7;border-radius:12px;padding:14px;background:#fff3fa}.info-item label{display:block;color:#64748b;font-size:11px;font-weight:800;text-transform:uppercase;margin-bottom:8px}.info-value{display:flex;align-items:center;gap:10px;font-weight:700;color:#1e2a42}.info-value i{color:#f0208d;width:18px}.primary-btn{margin-top:20px;border:0;border-radius:11px;background:#f0208d;color:#fff;padding:11px 16px;font-weight:800;font-size:12px;cursor:pointer;box-shadow:0 8px 18px rgba(242,56,150,.18)}
.order-card,.custom-card{padding:20px;margin-bottom:14px}.order-head,.custom-head{display:flex;align-items:flex-start;justify-content:space-between;gap:20px}.icon-box{width:44px;height:44px;border-radius:14px;background:#cddaff;color:#1747c7;display:flex;align-items:center;justify-content:center;font-size:17px;flex:none}.head-left{display:flex;gap:14px;align-items:flex-start}.head-left h3{margin:0 0 5px;color:#102f91;font-size:17px}.muted{margin:0;color:#64748b;font-size:11px}.status{background:#ffe4f2;color:#f0208d;border-radius:999px;padding:7px 13px;font-size:12px;font-weight:800}.divider{height:1px;background:#e3dce7;margin:20px 0}.meta-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.meta-item span{display:block;color:#64748b;font-size:12px;margin-bottom:5px}.meta-item strong{color:#1d2940;font-size:12px}.custom-layout{display:grid;grid-template-columns:1fr 220px;gap:25px}.custom-info{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.artwork-panel{border:1px solid #e3dce7;border-radius:14px;padding:12px;background:#fff3fa;text-align:center}.artwork-panel span{display:block;text-align:left;color:#64748b;font-size:11px;font-weight:800;text-transform:uppercase;margin-bottom:10px}.artwork-img{width:100%;height:135px;object-fit:cover;border-radius:12px;border:1px solid #b7c8ff;background:#fff;display:block}.view-art{display:inline-flex;align-items:center;justify-content:center;gap:7px;margin-top:9px;padding:9px 12px;border-radius:10px;background:#cddaff;color:#1747c7;font-size:12px;font-weight:800}.empty{padding:55px 20px;text-align:center}.empty i{font-size:45px;color:#b5c3dc;margin-bottom:12px}.empty p{color:#64748b}
/* ORDER TRACKING */
.tracking-card{
    padding:24px;
    margin-bottom:16px;
}
.tracking-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:18px;
}
.tracking-order{
    display:flex;
    gap:14px;
    align-items:flex-start;
}
.tracking-order h3{
    margin:0 0 5px;
    color:#102f91;
    font-size:17px;
}
.tracking-number{
    margin:0;
    color:#64748b;
    font-size:11px;
}
.tracking-status{
    padding:7px 13px;
    border-radius:999px;
    background:#ffe4f2;
    color:#f0208d;
    font-size:11px;
    font-weight:800;
    white-space:nowrap;
}
.tracking-line{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    position:relative;
    margin:28px 5px 10px;
}
.tracking-line:before{
    content:"";
    position:absolute;
    left:8%;
    right:8%;
    top:18px;
    height:3px;
    background:#dfe5f3;
    z-index:0;
}
.tracking-step{
    position:relative;
    z-index:1;
    text-align:center;
}
.tracking-icon{
    width:38px;
    height:38px;
    margin:0 auto 9px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#e8e0eb;
    color:#64748b;
    border:3px solid #fff;
    box-shadow:0 4px 12px rgba(30,50,100,.08);
}
.tracking-step.active .tracking-icon{
    background:#f0208d;
    color:#fff;
}
.tracking-step.done .tracking-icon{
    background:#1747c7;
    color:#fff;
}
.tracking-step span{
    display:block;
    color:#64748b;
    font-size:10px;
    font-weight:800;
}
.tracking-step.active span,
.tracking-step.done span{
    color:#102f91;
}
.tracking-details{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:14px;
    margin-top:22px;
}
.tracking-detail{
    padding:13px;
    border:1px solid #e3dce7;
    border-radius:12px;
    background:#fff3fa;
}
.tracking-detail span{
    display:block;
    color:#64748b;
    font-size:10px;
    font-weight:800;
    text-transform:uppercase;
    margin-bottom:5px;
}
.tracking-detail strong{
    color:#1d2940;
    font-size:12px;
}
@media(max-width:800px){
    .tracking-details{grid-template-columns:1fr}
    .tracking-line{grid-template-columns:repeat(2,1fr);row-gap:20px}
    .tracking-line:before{display:none}
    .tracking-head{flex-direction:column}
}

.modal{display:none;position:fixed;inset:0;background:rgba(18,31,58,.48);z-index:2000;padding:24px;overflow:auto}.modal-content{background:#fff;width:min(500px,100%);margin:5vh auto;border-radius:18px;padding:24px;position:relative;box-shadow:0 25px 60px rgba(0,0,0,.2)}.close{position:absolute;right:20px;top:15px;font-size:28px;color:#64748b;cursor:pointer}.modal-content h2{margin:0 0 22px;color:#102f91}.modal-content label{display:block;font-size:12px;font-weight:800;color:#35445d;margin:15px 0 7px}.modal-content input,.modal-content textarea{width:100%;border:1px solid #d0c5d5;border-radius:12px;padding:12px 14px;font:inherit;outline:none}.modal-content input:focus,.modal-content textarea:focus{border-color:#6b8fe9;box-shadow:0 0 0 3px rgba(59,102,220,.08)}.modal-buttons{display:flex;gap:10px;justify-content:flex-end;margin-top:22px}.cancel-btn,.save-btn,.delete-address-btn,.confirm-btn{border:0;border-radius:11px;padding:11px 16px;font-weight:800;font-size:12px;cursor:pointer;text-decoration:none}.cancel-btn{background:#e8e0eb;color:#56647a}.save-btn,.confirm-btn{background:#f0208d;color:#fff}.delete-address-btn{background:#ffeaf3;color:#d61d76}.profile-top{display:flex;gap:14px;align-items:center;margin-bottom:22px}.edit-avatar-wrapper{width:92px;height:92px;border-radius:50%;position:relative;overflow:visible;background:#eef3ff;display:flex;align-items:center;justify-content:center}.edit-avatar-img{width:100%;height:100%;object-fit:cover;border-radius:50%}.edit-avatar-default{font-size:38px;color:#7b8eac}.camera-badge{position:absolute!important;right:-4px;bottom:-4px;width:31px;height:31px!important;padding:0!important;border-radius:50%;background:#f0208d;color:#fff!important;display:flex!important;align-items:center;justify-content:center;cursor:pointer!important;margin:0!important;z-index:1;border:2px solid #fff}.hidden{display:none!important}.profile-basic-info h3{margin:0 0 4px}.profile-basic-info p{margin:0;color:#64748b}.password-input-wrapper{position:relative}.password-input-wrapper input{padding-right:45px}.form-password-toggle{position:absolute;right:5px;top:4px;width:38px;height:38px;border:0;background:transparent;color:#64748b;cursor:pointer}.logout-modal-content{text-align:center}.logout-icon{font-size:44px;color:#f0208d;margin-bottom:10px}
.logout-popup{
    border-radius:18px !important;
}
.logout-title{
    color:#102f91 !important;
    font-family:'Plus Jakarta Sans',sans-serif !important;
    font-size:24px !important;
    font-weight:800 !important;
}
.logout-text{
    color:#64748b !important;
    font-family:'Plus Jakarta Sans',sans-serif !important;
    font-size:13px !important;
}
.logout-confirm,.logout-cancel{
    border-radius:10px !important;
    padding:10px 18px !important;
    font-family:'Plus Jakarta Sans',sans-serif !important;
    font-size:12px !important;
    font-weight:800 !important;
}

/* RECEIPT / MY ORDERS */
.order-products{margin-top:18px;display:grid;gap:8px}
.order-product-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    padding:10px 12px;
    border:1px solid #eee5ee;
    border-radius:10px;
    background:#fff8fc;
}

.tracking-filters{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
    background:#fff;
    border:1px solid #e1d8e5;
    border-radius:18px;
    padding:20px 22px;
    margin-bottom:20px;
    box-shadow:0 8px 25px rgba(20,40,90,.05)
}
.filter-field label{display:block;color:#35445d;font-size:11px;font-weight:900;letter-spacing:.3px;margin-bottom:8px}
.filter-field select{width:100%;height:48px;border:1px solid #d0c5d5;border-radius:12px;background:#fff;padding:0 14px;color:#182033;font:inherit;font-size:13px;outline:none;cursor:pointer}
.filter-field select:focus{border-color:#f0208d;box-shadow:0 0 0 3px rgba(240,32,141,.08)}
.tracking-item.is-hidden{display:none!important}
.tracking-method-note{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;margin:14px 0 6px;border:1px solid #e4dce8}.tracking-method-note>i{font-size:18px}.tracking-method-note strong{display:block;font-size:11px;font-weight:900;color:#182033}.tracking-method-note small{display:block;margin-top:3px;font-size:9px;color:#64748b}.self-collection-note{background:#f6f8ff}.self-collection-note>i{color:#2448b8}.delivery-note{background:#fff5fa}.delivery-note>i{color:#f0208d}.tracking-line-self{grid-template-columns:repeat(3,1fr)!important}.tracking-line-self .tracking-step span{max-width:140px}@media(max-width:700px){.tracking-line-self{grid-template-columns:1fr!important}.tracking-method-note{margin-top:10px}}
.custom-info-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}
.custom-details-box{border:1px solid #e3dce7;border-radius:13px;background:#fff8fc;padding:15px;margin-bottom:16px}
.custom-details-title{color:#102f91;font-size:10px;font-weight:900;letter-spacing:.4px;margin-bottom:9px;display:flex;align-items:center;gap:7px}
.custom-details-title i{color:#f0208d}
.custom-details-text{color:#475569;font-size:11px;line-height:1.7;white-space:normal}
.compact-details{background:#f7f9ff}
.custom-bottom-grid{display:grid;grid-template-columns:220px 1fr;gap:18px;align-items:stretch}
.custom-artwork-panel{min-height:180px}
.custom-action-panel{display:flex;flex-direction:column;justify-content:space-between;border:1px solid #e3dce7;border-radius:14px;padding:16px;background:#fff}
.custom-price-callout{padding:14px;border-radius:12px;background:#fff3fa;border:1px solid #f2c9de}
.custom-price-callout span{display:block;color:#64748b;font-size:9px;font-weight:900;letter-spacing:.5px;margin-bottom:5px}
.custom-price-callout strong{display:block;color:#f0208d;font-size:23px;font-weight:900}
.custom-price-callout small{display:block;color:#94a3b8;font-size:9px;margin-top:4px}
.custom-actions{margin-top:14px;justify-content:flex-start}
.custom-receipt-details{margin-top:16px;padding:12px;border-radius:10px;background:#fff8fc;border:1px solid #e8dfe7;color:#475569;font-size:10px;line-height:1.6}
.custom-receipt-details strong{display:block;color:#102f91;font-size:10px;margin-bottom:5px}
.custom-payment-notice{display:flex;align-items:flex-start;gap:9px;margin-top:12px;padding:10px;border:1px solid #f5c5dd;border-radius:10px;background:#fff3fa;color:#d61d76;font-size:10px}
.custom-payment-notice>i{margin-top:2px}.custom-payment-notice strong,.custom-payment-notice small{display:block}.custom-payment-notice strong{font-size:10px}.custom-payment-notice small{margin-top:3px;color:#64748b;font-size:9px}
.custom-pay-button{width:100%;margin-top:10px}.custom-payment-paid{margin-top:12px;padding:10px;border-radius:10px;background:#e9f9ef;color:#168453;font-size:10px;font-weight:800}
.notification-wrap{position:relative}.notification-btn{position:relative}.notification-badge{position:absolute;top:-5px;right:-5px;min-width:18px;height:18px;padding:0 4px;border:2px solid #fff;border-radius:999px;background:#f0208d;color:#fff;font-size:9px;font-weight:900;display:flex;align-items:center;justify-content:center}.notification-panel{position:absolute;right:0;top:52px;width:290px;background:#fff;border:1px solid #e2e7f1;border-radius:14px;padding:12px;box-shadow:0 18px 40px rgba(30,50,100,.16);z-index:1100;display:none}.notification-wrap:hover .notification-panel,.notification-wrap:focus-within .notification-panel{display:block}.notification-panel h4{margin:0 0 8px;color:#102f91;font-size:12px}.notification-item{display:flex;gap:9px;padding:9px 6px;border-top:1px solid #edf0f6;color:#475569;font-size:10px}.notification-item i{color:#f0208d;margin-top:2px}.notification-empty{padding:10px 6px;color:#94a3b8;font-size:10px}
.artwork-file-icon{height:135px;border:1px dashed #b7c8ff;border-radius:12px;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:#1747c7;font-size:10px;text-align:center;padding:10px}
.artwork-file-icon i{font-size:32px;color:#f0208d}
.artwork-missing{height:135px;display:flex;align-items:center;justify-content:center;color:#f0208d;font-size:11px;font-weight:800;text-align:center}
.skip-step{opacity:.45}

.order-product-info{min-width:0}
.order-product-info strong{
    display:block;
    color:#1d2940;
    font-size:12px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.order-product-info span{
    display:block;
    margin-top:3px;
    color:#64748b;
    font-size:10px;
}
.order-product-price{
    color:#102f91;
    font-size:12px;
    font-weight:900;
    white-space:nowrap;
}
.order-actions{
    display:flex;
    justify-content:flex-end;
    gap:9px;
    margin-top:18px;
    flex-wrap:wrap;
}
.receipt-action{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    padding:10px 14px;
    border:1px solid #d7dff0;
    border-radius:10px;
    background:#fff;
    color:#1747c7;
    font-size:11px;
    font-weight:800;
    cursor:pointer;
    transition:.2s;
}
.receipt-action:hover{transform:translateY(-2px);border-color:#f0208d;color:#f0208d}
.receipt-action.primary{background:#f0208d;border-color:#f0208d;color:#fff}
.receipt-action.primary:hover{background:#1747c7;border-color:#1747c7}
.receipt-modal{
    display:none;
    position:fixed;
    inset:0;
    z-index:3000;
    padding:12vh 16px 25px;
    background:rgba(18,31,58,.50);
    backdrop-filter:blur(5px);
    overflow:auto;
    isolation:isolate;
}
.receipt-modal.show{display:flex;align-items:flex-start;justify-content:center}
.receipt-box{
    width:min(520px,100%);
    background:#fff;
    border:1px solid rgba(255,255,255,.8);
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 28px 80px rgba(16,47,145,.28);
    position:relative;
    z-index:1;
}
.receipt-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:18px 24px;
    color:#fff;
    background:linear-gradient(125deg,#102f91 0%,#1747c7 57%,#f0208d 145%);
}
.receipt-brand{font-size:19px;font-weight:900;color:#fff;letter-spacing:-1px}
.receipt-brand span{color:#ffd6ec}
.receipt-close{
    width:36px;height:36px;border:0;border-radius:50%;
    background:rgba(255,255,255,.16);color:#fff;cursor:pointer;font-size:15px;
    transition:transform .2s,background .2s
}
.receipt-close:hover{transform:rotate(90deg);background:rgba(255,255,255,.28)}
.receipt-paper{position:relative;padding:24px;background:linear-gradient(180deg,#f7f9ff 0,#fff 28%)}
.receipt-paper:before{
    content:'CUSTOMER COPY';position:absolute;top:15px;right:22px;
    padding:5px 8px;border:1px solid #dbe5ff;border-radius:999px;
    color:#5270b9;background:#fff;font-size:8px;font-weight:900;letter-spacing:.7px
}
.receipt-check{
    width:52px;height:52px;margin:0 auto 10px;border-radius:17px;
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,#e0f8ea,#d8f0ff);color:#168453;font-size:21px;
    box-shadow:0 9px 20px rgba(22,132,83,.16)
}
.receipt-paper h2{margin:0;text-align:center;color:#102f91;font-size:24px;font-weight:900;letter-spacing:-.7px}
.receipt-subtitle{text-align:center;color:#64748b;font-size:11px;margin:7px 0 23px}
.receipt-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:22px}
.receipt-info{
    padding:11px 12px;border:1px solid #e5eaf7;border-radius:12px;background:#fff;
    box-shadow:0 4px 12px rgba(32,60,126,.04)
}
.receipt-info:nth-child(3n+1){border-left:3px solid #1747c7}
.receipt-info:nth-child(3n+2){border-left:3px solid #f0208d}
.receipt-info:nth-child(3n){border-left:3px solid #35a56e}
.receipt-info span{
    display:block;color:#7e8ba3;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.35px;margin-bottom:4px
}
.receipt-info strong{color:#182033;font-size:11px}
.receipt-items-title{
    display:flex;align-items:center;gap:8px;color:#102f91;font-size:12px;font-weight:900;margin-bottom:8px
}
.receipt-items-title:after{content:'';height:1px;flex:1;background:linear-gradient(90deg,#c6d7ff,transparent)}
.receipt-item-row{
    display:grid;grid-template-columns:1fr 55px 90px;gap:10px;
    padding:10px 3px;border-bottom:1px solid #edf0f6;
    color:#182033;font-size:10px;align-items:start
}
.receipt-item-row.header{
    padding:8px 9px;color:#5970a9;font-size:9px;font-weight:900;text-transform:uppercase;
    border:0;border-radius:8px;background:#edf3ff
}
.receipt-item-row>div:nth-child(2){text-align:center}
.receipt-item-row>div:last-child{text-align:right;font-weight:800}
.receipt-item-name{font-weight:800}
.receipt-item-size{display:block;color:#94a3b8;font-size:9px;margin-top:2px}
.receipt-totals{margin-top:16px;padding:13px 14px 2px;border:1px solid #e4eafa;border-radius:13px;background:#fafbff}
.receipt-total-row{display:flex;justify-content:space-between;margin-bottom:8px;color:#64748b;font-size:10px}
.receipt-total-row strong{color:#182033}
.receipt-total-row.grand{
    padding-top:12px;margin:10px -14px 0;border-top:1px dashed #c9d4ec;
    padding-left:14px;padding-right:14px;
    color:#182033;font-size:13px;font-weight:900
}
.receipt-total-row.grand strong{color:#f0208d;font-size:17px}
.receipt-status{
    margin-top:16px;padding:11px 12px;border:1px solid #dbe5ff;border-radius:11px;
    background:#f3f7ff;color:#1747c7;font-size:10px;font-weight:800
}
.receipt-footer{
    display:flex;gap:9px;padding:0 30px 25px
}
.receipt-footer .receipt-action{flex:1}
@media(max-width:520px){
    .receipt-modal{padding-top:6vh}
    .order-actions{justify-content:stretch}
    .order-actions .receipt-action{flex:1}
    .receipt-paper{padding:22px 18px}
    .receipt-info-grid{grid-template-columns:1fr}
    .receipt-footer{padding:0 18px 20px}
    .receipt-item-row{grid-template-columns:1fr 42px 75px}
}
@media print{
    @page{size:A4 portrait;margin:8mm}
    html,body{width:210mm!important;height:297mm!important;overflow:hidden!important;background:#fff!important}
    body *{visibility:hidden!important}
    .receipt-modal{display:none!important}
    .receipt-modal.print-active,
    .receipt-modal.print-active *{visibility:visible!important}
    .receipt-modal.print-active{
        display:block!important;position:absolute!important;inset:0!important;
        width:194mm!important;min-height:0!important;padding:0!important;
        background:#fff!important;overflow:hidden!important
    }
    .receipt-box{width:100%!important;box-shadow:none!important;border-radius:0!important}
    .receipt-header,.receipt-footer,.receipt-close{display:none!important}
    .receipt-paper:before{display:none!important}
    .receipt-paper{padding:7mm 8mm!important;break-inside:avoid!important;page-break-inside:avoid!important}
    .receipt-check{display:none!important}
    .receipt-paper h2{font-size:18px!important}
    .receipt-subtitle{margin:3px 0 10px!important;font-size:9px!important}
    .receipt-info-grid{grid-template-columns:repeat(3,1fr)!important;gap:5px!important;margin-bottom:9px!important}
    .receipt-info{padding:6px 7px!important;border-radius:6px!important}
    .receipt-info span{margin-bottom:2px!important;font-size:7px!important}
    .receipt-info strong{font-size:8px!important}
    .receipt-items-title{margin-bottom:4px!important;font-size:9px!important}
    .receipt-item-row{grid-template-columns:1fr 38px 68px!important;gap:6px!important;padding:4px 0!important;font-size:8px!important}
    .receipt-item-row.header,.receipt-item-size{font-size:7px!important}
    .receipt-totals{margin-top:7px!important;padding-top:6px!important}
    .receipt-total-row{margin-bottom:4px!important;font-size:8px!important}
    .receipt-total-row.grand{margin-top:5px!important;padding-top:6px!important;font-size:10px!important}
    .receipt-total-row.grand strong{font-size:12px!important}
    .receipt-status{margin-top:7px!important;padding:6px 7px!important;font-size:8px!important}
}

@media(max-width:1050px){.navbar{padding:0 25px}.nav-links{gap:20px}.nav-actions,.logo{min-width:auto}.profile-card{grid-template-columns:1fr}.profile-side{border-right:0;border-bottom:1px solid #e3dce7}.custom-layout{grid-template-columns:1fr}}
@media(max-width:800px){.custom-info-grid{grid-template-columns:1fr 1fr}.custom-bottom-grid{grid-template-columns:1fr}.navbar{height:auto;min-height:84px;flex-wrap:wrap;gap:15px;padding:16px 20px}.logo{width:auto}.nav-links{order:3;width:100%;justify-content:center;flex-wrap:wrap;gap:14px}.nav-actions{margin-left:auto}.nav-btn{padding:0 12px}.hero{padding:48px 20px}.hero h1{font-size:36px}.account-wrap{width:min(94%,1180px);margin-top:30px}.account-tabs{overflow:auto}.account-tabs a{min-width:150px}.info-grid,.meta-grid,.custom-info{grid-template-columns:1fr}.edit-main{padding:25px}}
@media(max-width:520px){.custom-info-grid{grid-template-columns:1fr}.nav-btn span{display:none}.nav-btn{width:44px;padding:0;justify-content:center}.nav-btn i{margin:0}.cart-btn{width:44px;height:44px}.logo-main{font-size:31px}.logo-subtitle{font-size:9px}.hero h1{font-size:34px;letter-spacing:-1.5px}.hero p{font-size:12px}.order-head,.custom-head{flex-direction:column}.account-tabs{border-radius:14px}.card{border-radius:17px}.profile-side{padding:24px 20px}}


/* =========================================================
   EXACT SHARED DESIGN SYSTEM — copied from custom_request.php
   ========================================================= */
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f6e8f2;color:#172033;min-height:100vh}
a{text-decoration:none}
button,input,textarea,select{font-family:inherit}
.navbar{height:84px;min-height:0;padding:16px 48px;display:flex;align-items:center;justify-content:space-between;gap:25px;background:#fff;border-bottom:1px solid #e8ebf2;box-shadow:none;position:relative;z-index:20}
.nav-logo{display:flex;align-items:center;min-width:max-content}.logo-text{display:flex;flex-direction:column;gap:2px}.logo-title{font-size:36px;font-weight:900;line-height:.9;letter-spacing:-2.5px;white-space:nowrap}
.logo-sa{color:#f0208d}.logo-design{color:#1b6ef5}.logo-subtitle{margin-top:0;color:#111827;font-size:12px;font-weight:900;letter-spacing:.7px;text-transform:uppercase}
.nav-links{display:flex;align-items:center;justify-content:center;gap:32px;list-style:none}.nav-links a{color:#111827;font-size:13px;font-weight:800;text-transform:uppercase;transition:.2s}.nav-links a:hover,.nav-links a.active{color:#f0208d}
.nav-icons{display:flex;align-items:center;gap:10px}.login-pill{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 15px;border-radius:11px;background:linear-gradient(135deg,#f0208d,#ff3b86);color:#fff;font-size:12px;font-weight:800;text-transform:uppercase;box-shadow:0 8px 18px rgba(239,58,155,.20);transition:.25s}.login-pill:hover{transform:translateY(-2px)}.logout-pill{border:0;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif}.icon-btn{position:relative;width:42px;height:42px;display:flex;align-items:center;justify-content:center;border:1px solid #f0208d;border-radius:50%;background:#fff;color:#f0208d;transition:.2s}.icon-btn:hover{background:#ffe4f2;transform:translateY(-2px)}.menu-toggle{display:none;width:42px;height:42px;border:1px solid #dfe4ef;border-radius:11px;background:#fff;color:#172033;cursor:pointer}
.hero{position:relative;overflow:hidden;height:317px;padding:78px 70px 72px;background:linear-gradient(120deg,#cbd8ff 0%,#d9b9eb 52%,#ffc0dc 100%);border-bottom:1px solid #e0e7f8}.hero::before{content:"";position:absolute;width:300px;height:300px;right:-90px;top:-150px;border-radius:50%;background:rgba(40,84,197,.20)}.hero::after{content:"";position:absolute;width:220px;height:220px;left:-90px;bottom:-130px;border-radius:50%;background:rgba(239,58,155,.20)}.hero-inner{position:relative;z-index:2;max-width:1050px;margin:auto;text-align:center}
.badge{display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border-radius:999px;background:white;border:1px solid #dbe4ff;color:#1747c7;font-size:10px;font-weight:800;letter-spacing:.7px}
.hero h1{margin:15px 0 10px;color:#102f91;font-size:clamp(35px,5vw,52px);font-weight:900;letter-spacing:-2px}.hero h1 span{color:#f0208d}.hero p{max-width:720px;margin:auto;color:#64748b;font-size:14px;line-height:1.7}
.account-wrap{width:min(1120px,calc(100% - 140px));margin:48px auto 70px}
.account-tabs{display:flex;gap:8px;background:#fff;border:1px solid #e2e7f1;border-radius:18px;padding:8px;margin-bottom:24px;box-shadow:0 8px 25px rgba(20,40,90,.05)}.account-tabs a{flex:1;display:flex;align-items:center;justify-content:center;gap:8px;padding:11px 10px;border-radius:12px;color:#475569;font-weight:800;font-size:12px}.account-tabs a:hover,.account-tabs a.active{background:#f0208d;color:#fff;box-shadow:0 7px 17px rgba(242,56,150,.18)}
.footer{padding:38px 70px;background:#0f1f61;color:#fff}.footer-grid{width:min(1120px,100%);margin:auto;display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:35px}.footer-brand{font-size:24px;font-weight:900}.footer-brand span{color:#f0208d}.footer h4{margin-bottom:10px;font-size:12px}.footer p,.footer a{color:rgba(255,255,255,.72);font-size:11px;line-height:1.7}.footer a{display:block;margin-bottom:4px}.footer a:hover{color:white}.footer-line{width:min(1120px,100%);margin:24px auto 0;padding-top:14px;border-top:1px solid rgba(255,255,255,.14);text-align:center;color:rgba(255,255,255,.58);font-size:10px}
/* Keep the original profile content; the shared header styles are defined above. */
.section-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}.section-title h2{margin:0;color:#102f91;font-size:20px;font-weight:900;letter-spacing:-.5px}.section-title p{margin:5px 0 0;color:#64748b;font-size:11px}
.card{background:#fff;border:1px solid #e2e7f1;border-radius:18px;box-shadow:0 12px 30px rgba(30,50,100,.06)}.profile-card{display:grid;grid-template-columns:190px 1fr;overflow:hidden}.profile-side{padding:20px 18px;text-align:center;background:#fff3fa;border-right:1px solid #e9edf5}.avatar{width:78px;height:78px;border-radius:50%;margin:0 auto 11px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#eef3ff;border:5px solid #fff;box-shadow:0 7px 22px rgba(34,67,150,.12)}.avatar img{width:100%;height:100%;object-fit:cover}.avatar i{font-size:34px;color:#7b8eac}.profile-side h3{margin:0 0 5px;font-size:14px;color:#172033}.profile-side p{margin:0;color:#64748b;font-size:10px;word-break:break-word}.edit-main{padding:20px}.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.info-item{border:1px solid #e2e7f1;border-radius:11px;padding:10px 11px;background:#fff3fa}.info-item label{display:block;color:#64748b;font-size:9px;font-weight:800;text-transform:uppercase;margin-bottom:5px}.info-value{display:flex;align-items:center;gap:8px;font-weight:800;color:#172033;font-size:11px;overflow-wrap:anywhere}.info-value i{color:#f0208d;width:15px}.primary-btn{margin-top:11px;border:0;border-radius:10px;background:#f0208d;color:#fff;padding:9px 13px;font-weight:800;font-size:10px;cursor:pointer;box-shadow:0 8px 18px rgba(242,56,150,.18)}
@media(max-width:1100px){.navbar{padding:16px 30px}.nav-links{gap:20px}}
@media(max-width:900px){.account-wrap{width:min(100% - 56px,720px)}.profile-card{grid-template-columns:1fr}.profile-side{border-right:0;border-bottom:1px solid #e9edf5}}
@media(max-width:760px){.navbar{height:auto;min-height:76px;flex-wrap:wrap;padding:15px 20px}.logo-title{font-size:29px}.logo-subtitle{font-size:9px}.menu-toggle{display:flex;align-items:center;justify-content:center}.nav-links{display:none;order:4;width:100%;padding:12px 0 3px;justify-content:center;flex-direction:column;align-items:center;gap:14px;border-top:1px solid #eef1f6}.navbar.menu-open .nav-links{display:flex}.nav-icons{margin-left:auto}.login-pill{justify-content:center}.login-pill span{display:none}.login-pill{width:42px;height:42px;padding:0}.icon-btn{width:42px;height:42px}.hero{height:auto;padding:55px 22px}.hero h1{font-size:38px}.account-wrap{width:calc(100% - 40px);margin:32px auto 45px}.account-tabs{overflow:auto}.account-tabs a{min-width:150px}.info-grid{grid-template-columns:1fr}.footer{padding:35px 20px}.footer-grid{grid-template-columns:1fr;gap:24px}}
@media(max-width:430px){.hero h1{font-size:33px}.meta-grid{grid-template-columns:1fr}}
.password-help{margin:6px 0 0;color:#64748b;font-size:11px;line-height:1.5}.form-field-error{display:none;margin:6px 0 0;color:#d61d76;font-size:11px;font-weight:700}.password-input-wrapper.has-error input{border-color:#e54883;box-shadow:0 0 0 3px rgba(229,72,131,.12)}.form-field-error.is-visible{display:block}.save-btn:disabled{opacity:.55;cursor:not-allowed;box-shadow:none}

</style>
<link rel="stylesheet" href="ui_polish.css">
</head>
<body>

<nav class="navbar">
    <a class="nav-logo" href="index.php" title="Back to Home">
        <div class="logo-text">
            <span class="logo-title"><span class="logo-sa">SA</span> <span class="logo-design">DESIGN</span></span>
            <span class="logo-subtitle">PRINTING &amp; ADVERTISING</span>
        </div>
    </a>

    <ul class="nav-links" id="mainNav">
        <li><a href="index.php">Home</a></li>
        <li><a href="index.php#products-section">Product</a></li>
        <li><a href="about.php">About Us</a></li>
        <li><a href="custom_request.php" class="<?php echo $page === 'custom' ? 'active' : ''; ?>">Custom Request</a></li>
    </ul>
    <div class="nav-icons">
        <a href="cust_profile.php" class="login-pill"><i class="fa-regular fa-user"></i><span>PROFILE</span></a>
        <button type="button" class="login-pill logout-pill" onclick="openLogoutModal()"><i class="fa-solid fa-right-from-bracket"></i><span>LOG OUT</span></button>
        <div class="notification-wrap">
            <button type="button" class="icon-btn notification-btn" aria-label="Notifications">
                <i class="fa-solid fa-bell"></i>
                <?php if ($notificationCount > 0): ?><span class="notification-badge"><?php echo min($notificationCount, 9); ?></span><?php endif; ?>
            </button>
            <div class="notification-panel">
                <h4>Notifications</h4>
                <?php if ($notifications): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <a class="notification-item" href="<?php echo htmlspecialchars($notification['link']); ?>"><i class="fa-solid <?php echo htmlspecialchars($notification['icon']); ?>"></i><span><?php echo htmlspecialchars($notification['text']); ?></span></a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="notification-empty">No new notifications.</div>
                <?php endif; ?>
            </div>
        </div>
        <a href="cart.php" class="icon-btn" aria-label="Cart"><i class="fa-solid fa-bag-shopping"></i></a>
    </div>

    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>
</nav>

<section class="hero">
    <div class="hero-inner">
        <span class="badge"><i class="fa-solid fa-user"></i> MY ACCOUNT</span>
        <h1>Manage Your <span>Profile</span></h1>
        <p>View your account information, orders and custom requests in one place.</p>
    </div>
</section>

<div class="account-wrap">
    <div class="account-tabs">
        <a href="cust_profile.php?page=profile" class="<?php echo $page === 'profile' ? 'active' : ''; ?>"><i class="fa-solid fa-user"></i> My Profile</a>
        <a href="cust_profile.php?page=orders" class="<?php echo $page === 'orders' ? 'active' : ''; ?>"><i class="fa-solid fa-box"></i> My Orders</a>
        <a href="cust_profile.php?page=tracking" class="<?php echo $page === 'tracking' ? 'active' : ''; ?>"><i class="fa-solid fa-truck-fast"></i> Order Tracking</a>
        <a href="cust_profile.php?page=custom" class="<?php echo $page === 'custom' ? 'active' : ''; ?>"><i class="fa-solid fa-wand-magic-sparkles"></i> Custom Request</a>
        <a href="wallet.php"><i class="fa-solid fa-wallet"></i> Wallet</a>
    </div>
    <div class="card loyalty-card"><i class="fa-solid fa-award"></i><div><strong><?= number_format($loyalty_points) ?> Loyalty Points</strong><p>Earn 1 point for every RM10 spent.</p></div></div>
    <div class="card loyalty-card"><i class="fa-solid fa-wallet"></i><div><strong>Wallet: RM <?= number_format($wallet_balance, 2) ?></strong><p>Top up securely or pay orders instantly.</p><a href="wallet.php">Open wallet</a></div></div>

    <?php if ($page === 'profile'): ?>
        <div class="section-title"><div><h2>My Profile</h2><p>Keep your personal information up to date.</p></div></div>
        <div class="card profile-card">
            <div class="profile-side">
                <div class="avatar">
                    <?php $profile_img = $customer['profile_image'] ?? ''; $profile_path = 'images/profile/' . $profile_img; ?>
                    <?php if (!empty($profile_img) && file_exists($profile_path)): ?>
                        <img src="<?php echo htmlspecialchars($profile_path); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <i class="fa-solid fa-user"></i>
                    <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($custName); ?></h3>
                <p><?php echo htmlspecialchars($custEmail); ?></p>
            </div>
            <div class="edit-main">
                <div class="info-grid">
                    <div class="info-item"><label>Full Name</label><div class="info-value"><i class="fa-solid fa-user"></i><?php echo htmlspecialchars($custName); ?></div></div>
                    <div class="info-item"><label>Email Address</label><div class="info-value"><i class="fa-solid fa-envelope"></i><?php echo htmlspecialchars($custEmail); ?></div></div>
                    <div class="info-item"><label>Phone Number</label><div class="info-value"><i class="fa-solid fa-phone"></i><?php echo htmlspecialchars($custPhone); ?></div></div>
                    <div class="info-item"><label>Account</label><div class="info-value"><i class="fa-solid fa-circle-check"></i> Active Customer</div></div>
                </div>
                <button type="button" class="primary-btn" onclick="openUpdateProfile()"><i class="fa-solid fa-pen"></i> Update Profile</button>
            </div>
        </div>

    <?php elseif ($page === 'orders'): ?>
        <div class="section-title">
            <div>
                <h2>My Orders</h2>
                <p>View your orders and keep your receipts for future reference.</p>
            </div>
        </div>

        <?php if (!empty($order_list)): ?>
            <?php foreach ($order_list as $order): ?>
                <?php
                    $orderTotal = $order['subtotal'] + $order['shipping_fee'];
                    $receiptId = 'receipt_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $order['order_number']);
                    $statusRaw = strtolower(trim($order['status'] ?? 'pending'));

                    if (in_array($statusRaw, ['completed','complete','delivered'], true)) {
                        $statusClass = 'completed';
                    } elseif (in_array($statusRaw, ['cancelled','canceled'], true)) {
                        $statusClass = 'cancelled';
                    } else {
                        $statusClass = 'pending';
                    }
                ?>

                <div class="card order-card">
                    <div class="order-head">
                        <div class="head-left">
                            <div class="icon-box"><i class="fa-solid fa-box"></i></div>
                            <div>
                                <h3>Order #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                                <p class="muted">
                                    <?php echo date('d F Y, h:i A', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="meta-grid">
                        <div class="meta-item">
                            <span>Products</span>
                            <strong><?php echo count($order['products']); ?> item<?php echo count($order['products']) > 1 ? 's' : ''; ?></strong>
                        </div>
                        <div class="meta-item">
                            <span>Payment</span>
                            <strong><?php echo htmlspecialchars($order['payment_method']); ?></strong>
                        </div>
                        <div class="meta-item">
                            <span>Total</span>
                            <strong>RM <?php echo number_format($orderTotal, 2); ?></strong>
                        </div>
                    </div>

                    <div class="order-products">
                        <?php foreach ($order['products'] as $product): ?>
                            <div class="order-product-row">
                                <div class="order-product-info">
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                    <span>
                                        <?php echo htmlspecialchars($product['size']); ?>
                                        &bull;
                                        <?php echo $product['quantity']; ?> pcs
                                    </span>
                                </div>
                                <div class="order-product-price">
                                    RM <?php echo number_format($product['line_total'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-actions">
                        <button type="button"
                                class="receipt-action primary"
                                onclick="openReceipt('<?php echo htmlspecialchars($receiptId, ENT_QUOTES); ?>')">
                            <i class="fa-solid fa-receipt"></i> View Receipt
                        </button>

                        <button type="button"
                                class="receipt-action"
                                onclick="printReceipt('<?php echo htmlspecialchars($receiptId, ENT_QUOTES); ?>')">
                            <i class="fa-solid fa-print"></i> Print Receipt
                        </button>
                    </div>
                </div>

                <!-- RECEIPT MODAL -->
                <div class="receipt-modal" id="<?php echo htmlspecialchars($receiptId); ?>" role="dialog" aria-modal="true" aria-hidden="true">
                    <div class="receipt-box">
                        <div class="receipt-header">
                            <div class="receipt-brand"><span>SA</span> DESIGN</div>
                            <button type="button" class="receipt-close"
                                    onclick="closeReceipt('<?php echo htmlspecialchars($receiptId, ENT_QUOTES); ?>')"
                                    aria-label="Close receipt">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="receipt-paper">
                            <div class="receipt-check">
                                <i class="fa-solid fa-check"></i>
                            </div>

                            <h2>Order Receipt</h2>
                            <p class="receipt-subtitle">
                                SA Design Printing &amp; Advertising
                            </p>

                            <div class="receipt-info-grid">
                                <div class="receipt-info">
                                    <span>Order Number</span>
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                </div>
                                <div class="receipt-info">
                                    <span>Date</span>
                                    <strong><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></strong>
                                </div>
                                <div class="receipt-info">
                                    <span>Customer</span>
                                    <strong><?php echo htmlspecialchars($custName); ?></strong>
                                </div>
                                <div class="receipt-info">
                                    <span>Payment Method</span>
                                    <strong><?php echo htmlspecialchars($order['payment_method']); ?></strong>
                                </div>
                                <div class="receipt-info">
                                    <span>Collection</span>
                                    <strong><?php echo htmlspecialchars($order['collection_method']); ?></strong>
                                </div>
                                <div class="receipt-info">
                                    <span>Status</span>
                                    <strong><?php echo htmlspecialchars($order['status']); ?></strong>
                                </div>
                            </div>

                            <div class="receipt-items-title">ORDER ITEMS</div>

                            <div class="receipt-item-row header">
                                <div>Item</div>
                                <div>Qty</div>
                                <div>Total</div>
                            </div>

                            <?php foreach ($order['products'] as $product): ?>
                                <div class="receipt-item-row">
                                    <div>
                                        <span class="receipt-item-name">
                                            <?php echo htmlspecialchars($product['product_name']); ?>
                                        </span>
                                        <span class="receipt-item-size">
                                            <?php echo htmlspecialchars($product['size']); ?>
                                            &bull; RM <?php echo number_format($product['unit_price'], 2); ?> / pc
                                        </span>
                                    </div>
                                    <div><?php echo $product['quantity']; ?></div>
                                    <div>RM <?php echo number_format($product['line_total'], 2); ?></div>
                                </div>
                            <?php endforeach; ?>

                            <div class="receipt-totals">
                                <div class="receipt-total-row">
                                    <span>Subtotal</span>
                                    <strong>RM <?php echo number_format($order['subtotal'], 2); ?></strong>
                                </div>
                                <div class="receipt-total-row">
                                    <span>Delivery</span>
                                    <strong>RM <?php echo number_format($order['shipping_fee'], 2); ?></strong>
                                </div>
                                <div class="receipt-total-row grand">
                                    <span>Total</span>
                                    <strong>RM <?php echo number_format($orderTotal, 2); ?></strong>
                                </div>
                            </div>

                            <div class="receipt-status">
                                <i class="fa-solid fa-circle-info"></i>
                                Keep this receipt for your records. Thank you for choosing SA Design.
                            </div>
                        </div>

                        <div class="receipt-footer">
                            <button type="button"
                                    class="receipt-action"
                                    onclick="closeReceipt('<?php echo htmlspecialchars($receiptId, ENT_QUOTES); ?>')">
                                Close
                            </button>
                            <button type="button"
                                    class="receipt-action primary"
                                    onclick="printReceipt('<?php echo htmlspecialchars($receiptId, ENT_QUOTES); ?>')">
                                <i class="fa-solid fa-print"></i> Print Receipt
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card empty">
                <i class="fa-solid fa-box-open"></i>
                <p>You don't have any orders yet.</p>
            </div>
        <?php endif; ?>

    <?php elseif ($page === 'tracking'): ?>
        <div class="section-title">
            <div>
                <h2>Order Tracking</h2>
                <p>Track your normal orders and custom requests in one place.</p>
            </div>
        </div>

        <div class="tracking-filters">
            <div class="filter-field">
                <label for="trackingType">ORDER TYPE</label>
                <select id="trackingType">
                    <option value="all">All Orders</option>
                    <option value="normal">Normal Order</option>
                    <option value="custom">Custom Request</option>
                </select>
            </div>
            <div class="filter-field">
                <label for="trackingCollection">COLLECTION METHOD</label>
                <select id="trackingCollection">
                    <option value="all">All</option>
                    <option value="Delivery">Delivery</option>
                    <option value="Self Collection">Self Collection</option>
                </select>
            </div>
        </div>

        <div id="trackingList">
        <?php
        $tracking_has_cards = false;
        if ($tracking_result && mysqli_num_rows($tracking_result) > 0):
            while ($tracking_order = mysqli_fetch_assoc($tracking_result)):
                $tracking_has_cards = true;
                $raw_status = strtolower(trim($tracking_order['status'] ?? 'pending'));
                $collection = trim($tracking_order['collection_method'] ?? '');

                if (in_array($raw_status, ['completed', 'complete', 'delivered'], true)) {
                    $tracking_stage = 5;
                    $display_status = 'Completed';
                } elseif (in_array($raw_status, ['out for delivery', 'out_for_delivery', 'delivery', 'shipping', 'shipped'], true)) {
                    $tracking_stage = 4;
                    $display_status = 'Out for Delivery';
                } elseif ($raw_status === 'ready') {
                    $tracking_stage = 3;
                    $display_status = 'Ready';
                } elseif (in_array($raw_status, ['processing', 'processed', 'confirmed'], true)) {
                    $tracking_stage = 2;
                    $display_status = 'Processing';
                } elseif (in_array($raw_status, ['cancelled', 'canceled'], true)) {
                    $tracking_stage = 0;
                    $display_status = 'Cancelled';
                } else {
                    $tracking_stage = 1;
                    $display_status = 'Pending';
                }
        ?>
                <div class="card tracking-card tracking-item"
                     data-order-type="normal"
                     data-collection="<?php echo htmlspecialchars($collection, ENT_QUOTES); ?>">
                    <div class="tracking-head">
                        <div class="tracking-order">
                            <div class="icon-box"><i class="fa-solid fa-box"></i></div>
                            <div>
                                <h3>Order #<?php echo htmlspecialchars($tracking_order['order_number']); ?></h3>
                                <p class="tracking-number">Ordered on <?php echo date('d F Y', strtotime($tracking_order['created_at'])); ?></p>
                            </div>
                        </div>
                        <span class="tracking-status"><?php echo htmlspecialchars($display_status); ?></span>
                    </div>

                    <?php if ($tracking_stage === 0): ?>
                        <div class="tracking-details">
                            <div class="tracking-detail"><span>Status</span><strong>Order Cancelled</strong></div>
                            <div class="tracking-detail"><span>Product</span><strong><?php echo htmlspecialchars($tracking_order['product_name']); ?></strong></div>
                            <div class="tracking-detail"><span>Total</span><strong>RM <?php echo number_format((float)($tracking_order['order_total'] ?? 0), 2); ?></strong></div>
                        </div>
                    <?php else: ?>
                        <?php if (strcasecmp($collection, 'Self Collection') === 0): ?>
                            <div class="tracking-method-note self-collection-note">
                                <i class="fa-solid fa-store"></i>
                                <div><strong>Self Collection</strong><small>Collect your order when it is ready.</small></div>
                            </div>
                            <div class="tracking-line tracking-line-self">
                                <div class="tracking-step <?php echo $tracking_stage >= 1 ? 'done' : ($tracking_stage === 1 ? 'active' : ''); ?>"><div class="tracking-icon"><i class="fa-solid fa-clipboard-check"></i></div><span>Order Received</span></div>
                                <div class="tracking-step <?php echo $tracking_stage >= 3 ? 'done' : ($tracking_stage === 2 ? 'active' : ''); ?>"><div class="tracking-icon"><i class="fa-solid fa-gears"></i></div><span>Processing</span></div>
                                <div class="tracking-step <?php echo $tracking_stage >= 3 ? 'active' : ''; ?>"><div class="tracking-icon"><i class="fa-solid fa-store"></i></div><span>Ready for Self Collection</span></div>
                            </div>
                        <?php else: ?>
                            <div class="tracking-method-note delivery-note">
                                <i class="fa-solid fa-truck"></i>
                                <div><strong>Delivery</strong><small>Your order will be delivered to you.</small></div>
                            </div>
                            <div class="tracking-line tracking-line-delivery">
                                <div class="tracking-step <?php echo $tracking_stage >= 1 ? 'done' : ($tracking_stage === 1 ? 'active' : ''); ?>"><div class="tracking-icon"><i class="fa-solid fa-clipboard-check"></i></div><span>Order Received</span></div>
                                <div class="tracking-step <?php echo $tracking_stage >= 3 ? 'done' : ($tracking_stage === 2 ? 'active' : ''); ?>"><div class="tracking-icon"><i class="fa-solid fa-gears"></i></div><span>Processing</span></div>
                                <div class="tracking-step <?php echo $tracking_stage >= 4 ? 'done' : ($tracking_stage === 3 ? 'active' : ''); ?>"><div class="tracking-icon"><i class="fa-solid fa-box"></i></div><span>Ready</span></div>
                                <div class="tracking-step <?php echo $tracking_stage >= 5 ? 'done' : ($tracking_stage === 4 ? 'active' : ''); ?>"><div class="tracking-icon"><i class="fa-solid fa-truck-fast"></i></div><span>Out for Delivery</span></div>
                                <div class="tracking-step <?php echo $tracking_stage === 5 ? 'active' : ''; ?>"><div class="tracking-icon"><i class="fa-solid fa-house-circle-check"></i></div><span>Delivered</span></div>
                            </div>
                        <?php endif; ?>

                        <div class="tracking-details">
                            <div class="tracking-detail"><span>Product</span><strong><?php echo htmlspecialchars($tracking_order['product_name']); ?></strong></div>
                            <div class="tracking-detail"><span>Quantity</span><strong><?php echo (int)($tracking_order['quantity'] ?? 0); ?> pcs</strong></div>
                            <div class="tracking-detail"><span>Total</span><strong>RM <?php echo number_format((float)($tracking_order['order_total'] ?? 0), 2); ?></strong></div>
                        </div>
                    <?php endif; ?>
                </div>
        <?php endwhile; endif; ?>

        <?php foreach ($custom_requests as $custom_tracking):
            $custom_status = $custom_status_column && isset($custom_tracking[$custom_status_column]) ? trim((string)$custom_tracking[$custom_status_column]) : 'Pending';
            $raw_custom_status = strtolower($custom_status);
            $custom_collection = trim($custom_tracking['collection_method'] ?? '');

            if (in_array($raw_custom_status, ['completed', 'complete', 'collected', 'delivered'], true)) {
                $custom_stage = 5;
                $custom_display_status = 'Completed';
            } elseif (in_array($raw_custom_status, ['out for delivery', 'out_for_delivery', 'shipping', 'shipped'], true)) {
                $custom_stage = 4;
                $custom_display_status = 'Out for Delivery';
            } elseif (in_array($raw_custom_status, ['ready', 'ready for collection'], true)) {
                $custom_stage = 3;
                $custom_display_status = $custom_collection === 'Self Collection' ? 'Ready for Collection' : 'Ready';
            } elseif (in_array($raw_custom_status, ['processing', 'processed', 'confirmed'], true)) {
                $custom_stage = 2;
                $custom_display_status = 'Processing';
            } elseif (in_array($raw_custom_status, ['cancelled', 'canceled'], true)) {
                $custom_stage = 0;
                $custom_display_status = 'Cancelled';
            } else {
                $custom_stage = 1;
                $custom_display_status = 'Pending';
            }
            $custom_price = ($custom_price_column && isset($custom_tracking[$custom_price_column]) && $custom_tracking[$custom_price_column] !== '' && is_numeric($custom_tracking[$custom_price_column])) ? (float)$custom_tracking[$custom_price_column] : null;
            $custom_date = !empty($custom_tracking['created_at']) ? date('d F Y', strtotime($custom_tracking['created_at'])) : 'Date unavailable';
        ?>
            <div class="card tracking-card tracking-item"
                 data-order-type="custom"
                 data-collection="<?php echo htmlspecialchars($custom_collection, ENT_QUOTES); ?>">
                <div class="tracking-head">
                    <div class="tracking-order">
                        <div class="icon-box"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                        <div>
                            <h3>Custom Order #CR<?php echo str_pad((int)$custom_tracking['id'], 3, '0', STR_PAD_LEFT); ?></h3>
                            <p class="tracking-number">Submitted on <?php echo htmlspecialchars($custom_date); ?></p>
                        </div>
                    </div>
                    <span class="tracking-status"><?php echo htmlspecialchars($custom_display_status); ?></span>
                </div>

                <?php if ($custom_stage === 0): ?>
                    <div class="tracking-details">
                        <div class="tracking-detail"><span>Type</span><strong>Custom Request</strong></div>
                        <div class="tracking-detail"><span>Product</span><strong><?php echo htmlspecialchars($custom_tracking['product_name'] ?? 'Custom Product'); ?></strong></div>
                        <div class="tracking-detail"><span>Price</span><strong><?php echo $custom_price !== null ? 'RM '.number_format($custom_price,2) : 'Price Pending'; ?></strong></div>
                    </div>
                <?php else: ?>
                    <?php if (strcasecmp($custom_collection, 'Self Collection') === 0): ?>
                        <div class="tracking-method-note self-collection-note">
                            <i class="fa-solid fa-store"></i>
                            <div><strong>Self Collection</strong><small>Collect your custom order when it is ready.</small></div>
                        </div>
                        <div class="tracking-line tracking-line-self">
                            <div class="tracking-step <?php echo $custom_stage >= 1 ? 'done' : ($custom_stage === 1 ? 'active' : ''); ?>"><div class="tracking-icon"><i class="fa-solid fa-clipboard-check"></i></div><span>Order Received</span></div>
                            <div class="tracking-step <?php echo $custom_stage >= 3 ? 'done' : ($custom_stage === 2 ? 'active' : ''); ?>"><div class="tracking-icon"><i class="fa-solid fa-gears"></i></div><span>Processing</span></div>
                            <div class="tracking-step <?php echo $custom_stage >= 3 ? 'active' : ''; ?>"><div class="tracking-icon"><i class="fa-solid fa-store"></i></div><span>Ready for Self Collection</span></div>
                        </div>
                    <?php else: ?>
                        <div class="tracking-method-note delivery-note">
                            <i class="fa-solid fa-truck"></i>
                            <div><strong>Delivery</strong><small>Your custom order will be delivered to you.</small></div>
                        </div>
                        <div class="tracking-line tracking-line-delivery">
                            <div class="tracking-step <?php echo $custom_stage >= 1 ? 'done' : ($custom_stage === 1 ? 'active' : ''); ?>"><div class="tracking-icon"><i class="fa-solid fa-clipboard-check"></i></div><span>Order Received</span></div>
                            <div class="tracking-step <?php echo $custom_stage >= 3 ? 'done' : ($custom_stage === 2 ? 'active' : ''); ?>"><div class="tracking-icon"><i class="fa-solid fa-gears"></i></div><span>Processing</span></div>
                            <div class="tracking-step <?php echo $custom_stage >= 4 ? 'done' : ($custom_stage === 3 ? 'active' : ''); ?>"><div class="tracking-icon"><i class="fa-solid fa-box"></i></div><span>Ready</span></div>
                            <div class="tracking-step <?php echo $custom_stage >= 5 ? 'done' : ($custom_stage === 4 ? 'active' : ''); ?>"><div class="tracking-icon"><i class="fa-solid fa-truck-fast"></i></div><span>Out for Delivery</span></div>
                            <div class="tracking-step <?php echo $custom_stage === 5 ? 'active' : ''; ?>"><div class="tracking-icon"><i class="fa-solid fa-house-circle-check"></i></div><span>Delivered</span></div>
                        </div>
                    <?php endif; ?>
                    <div class="tracking-details">
                        <div class="tracking-detail"><span>Type</span><strong>Custom Request</strong></div>
                        <div class="tracking-detail"><span>Product</span><strong><?php echo htmlspecialchars($custom_tracking['product_name'] ?? 'Custom Product'); ?></strong></div>
                        <div class="tracking-detail"><span>Price</span><strong><?php echo $custom_price !== null ? 'RM '.number_format($custom_price,2) : 'Price Pending'; ?></strong></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (!$tracking_has_cards && empty($custom_requests)): ?>
            <div class="card empty" id="trackingEmptyInitial"><i class="fa-solid fa-truck-fast"></i><p>You don't have any orders to track yet.</p></div>
        <?php else: ?>
            <div class="card empty" id="trackingEmptyFiltered" style="display:none"><i class="fa-solid fa-filter-circle-xmark"></i><p>No orders match the selected filters.</p></div>
        <?php endif; ?>
        </div>

    <?php elseif ($page === 'custom'): ?>
        <div class="section-title">
            <div>
                <h2>Custom Request</h2>
                <p>View your submitted custom requests, product details, quotation and receipt.</p>
            </div>
        </div>

        <?php if (!empty($custom_requests)): ?>
            <?php foreach ($custom_requests as $custom): ?>
                <?php
                    $customReceiptId = 'custom_receipt_' . (int)$custom['id'];
                    $customPrice = ($custom_price_column && isset($custom[$custom_price_column]) && $custom[$custom_price_column] !== '' && is_numeric($custom[$custom_price_column])) ? (float)$custom[$custom_price_column] : null;
                    $customStatus = ($custom_status_column && isset($custom[$custom_status_column]) && trim((string)$custom[$custom_status_column]) !== '') ? trim((string)$custom[$custom_status_column]) : 'Pending';
                    $customPaymentStatus = trim((string)($custom['payment_status'] ?? 'Pending'));
                    $customPaymentMethod = trim((string)($custom['payment_method'] ?? ''));
                    $customDate = !empty($custom['created_at']) ? date('d F Y, h:i A', strtotime($custom['created_at'])) : 'Date unavailable';
                    $customDetails = trim((string)($custom['additional_info'] ?? ''));
                ?>

                <div class="card custom-card">
                    <div class="custom-head">
                        <div class="head-left">
                            <div class="icon-box"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                            <div>
                                <h3>Custom Order #CR<?php echo str_pad((int)$custom['id'], 3, '0', STR_PAD_LEFT); ?></h3>
                                <p class="muted"><i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($customDate); ?></p>
                            </div>
                        </div>
                        <span class="status custom-status-badge"><?php echo htmlspecialchars($customStatus); ?></span>
                    </div>

                    <div class="divider"></div>

                    <div class="custom-info-grid">
                        <div class="meta-item"><span>Product</span><strong><?php echo htmlspecialchars($custom['product_name'] ?? 'N/A'); ?></strong></div>
                        <div class="meta-item"><span>Quantity</span><strong><?php echo (int)($custom['quantity'] ?? 0); ?> pcs</strong></div>
                        <div class="meta-item"><span>Collection</span><strong><?php echo htmlspecialchars($custom['collection_method'] ?? 'N/A'); ?></strong></div>
                        <div class="meta-item price-meta"><span>Quotation Price</span><strong><?php echo $customPrice !== null ? 'RM '.number_format($customPrice, 2) : 'Price Pending'; ?></strong></div>
                    </div>

                    <div class="custom-details-box">
                        <div class="custom-details-title"><i class="fa-solid fa-list-check"></i> PRODUCT DETAILS</div>
                        <?php if ($customDetails !== ''): ?>
                            <div class="custom-details-text"><?php echo nl2br(htmlspecialchars($customDetails)); ?></div>
                        <?php else: ?>
                            <div class="custom-details-text muted">No product details provided.</div>
                        <?php endif; ?>
                    </div>

                    <?php if (($custom['collection_method'] ?? '') === 'Delivery' && !empty($custom['delivery_address'])): ?>
                        <div class="custom-details-box compact-details">
                            <div class="custom-details-title"><i class="fa-solid fa-truck"></i> DELIVERY ADDRESS</div>
                            <div class="custom-details-text"><?php echo nl2br(htmlspecialchars($custom['delivery_address'])); ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="custom-bottom-grid">
                        <div class="artwork-panel custom-artwork-panel">
                            <span>Artwork</span>
                            <?php if (!empty($custom['artwork'])): ?>
                                <?php $artworkName = basename($custom['artwork']); $artworkPath = 'uploads/' . $artworkName; ?>
                                <?php if (file_exists($artworkPath)): ?>
                                    <?php $isImage = in_array(strtolower(pathinfo($artworkName, PATHINFO_EXTENSION)), ['jpg','jpeg','png'], true); ?>
                                    <?php if ($isImage): ?><a href="<?php echo htmlspecialchars($artworkPath); ?>" target="_blank"><img src="<?php echo htmlspecialchars($artworkPath); ?>" alt="Artwork" class="artwork-img"></a><?php else: ?><div class="artwork-file-icon"><i class="fa-solid fa-file-lines"></i><strong><?php echo htmlspecialchars($artworkName); ?></strong></div><?php endif; ?>
                                    <a href="<?php echo htmlspecialchars($artworkPath); ?>" target="_blank" class="view-art"><i class="fa-solid fa-eye"></i> View Artwork</a>
                                <?php else: ?>
                                    <div class="artwork-missing">Artwork file not found</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="artwork-missing">No artwork uploaded</div>
                            <?php endif; ?>
                        </div>

                        <div class="custom-action-panel">
                            <div class="custom-price-callout">
                                <span>FINAL QUOTATION</span>
                                <strong><?php echo $customPrice !== null ? 'RM '.number_format($customPrice, 2) : 'Price Pending'; ?></strong>
                                <?php if ($customPrice === null): ?><small>Price will appear after the quotation is updated.</small><?php endif; ?>
                            </div>
                            <?php if ($customPrice !== null && strcasecmp($customPaymentStatus, 'Paid') !== 0): ?>
                               <div class="custom-payment-notice">
                                   <i class="fa-solid fa-bell"></i>
                                   <div><strong>Quotation updated</strong><small>Please choose a payment method to continue.</small></div>
                               </div>
                               <a class="receipt-action primary custom-pay-button" href="custom_payment.php?id=<?php echo (int)$custom['id']; ?>">
                                   <i class="fa-solid fa-credit-card"></i> Make Payment
                               </a>
                            <?php elseif (strcasecmp($customPaymentStatus, 'Paid') === 0): ?>
                               <div class="custom-payment-paid"><i class="fa-solid fa-circle-check"></i> Paid<?php echo $customPaymentMethod !== '' ? ' via '.htmlspecialchars($customPaymentMethod) : ''; ?></div>
                            <?php endif; ?>
                            <div class="order-actions custom-actions">
                                <button type="button" class="receipt-action primary" onclick="openReceipt('<?php echo htmlspecialchars($customReceiptId, ENT_QUOTES); ?>')"><i class="fa-solid fa-receipt"></i> View Receipt</button>
                                <button type="button" class="receipt-action" onclick="printReceipt('<?php echo htmlspecialchars($customReceiptId, ENT_QUOTES); ?>')"><i class="fa-solid fa-print"></i> Print Receipt</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="receipt-modal" id="<?php echo htmlspecialchars($customReceiptId); ?>" role="dialog" aria-modal="true" aria-hidden="true">
                    <div class="receipt-box">
                        <div class="receipt-header">
                            <div class="receipt-brand"><span>SA</span> DESIGN</div>
                            <button type="button" class="receipt-close" onclick="closeReceipt('<?php echo htmlspecialchars($customReceiptId, ENT_QUOTES); ?>')" aria-label="Close receipt"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="receipt-paper">
                            <div class="receipt-check"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                            <h2>Custom Request Receipt</h2>
                            <p class="receipt-subtitle">SA Design Printing &amp; Advertising</p>

                            <div class="receipt-info-grid">
                                <div class="receipt-info"><span>Request Number</span><strong>CR<?php echo str_pad((int)$custom['id'], 3, '0', STR_PAD_LEFT); ?></strong></div>
                                <div class="receipt-info"><span>Date</span><strong><?php echo htmlspecialchars($customDate); ?></strong></div>
                                <div class="receipt-info"><span>Customer</span><strong><?php echo htmlspecialchars($custName); ?></strong></div>
                                <div class="receipt-info"><span>Collection</span><strong><?php echo htmlspecialchars($custom['collection_method'] ?? 'N/A'); ?></strong></div>
                                <div class="receipt-info"><span>Status</span><strong><?php echo htmlspecialchars($customStatus); ?></strong></div>
                                <div class="receipt-info"><span>Phone</span><strong><?php echo htmlspecialchars($custom['phone'] ?? $custPhone); ?></strong></div>
                            </div>

                            <div class="receipt-items-title">CUSTOM PRODUCT</div>
                            <div class="receipt-item-row header"><div>Item</div><div>Qty</div><div>Total</div></div>
                            <div class="receipt-item-row">
                                <div><span class="receipt-item-name"><?php echo htmlspecialchars($custom['product_name'] ?? 'Custom Product'); ?></span><span class="receipt-item-size">Custom quotation</span></div>
                                <div><?php echo (int)($custom['quantity'] ?? 0); ?></div>
                                <div><?php echo $customPrice !== null ? 'RM '.number_format($customPrice, 2) : 'Pending'; ?></div>
                            </div>

                            <div class="custom-receipt-details">
                                <strong>Product Details</strong>
                                <div><?php echo $customDetails !== '' ? nl2br(htmlspecialchars($customDetails)) : 'N/A'; ?></div>
                            </div>

                            <div class="receipt-totals">
                                <div class="receipt-total-row grand"><span>Total Quotation</span><strong><?php echo $customPrice !== null ? 'RM '.number_format($customPrice, 2) : 'Price Pending'; ?></strong></div>
                            </div>

                            <div class="receipt-status"><i class="fa-solid fa-circle-info"></i> This receipt reflects the quotation price provided for your custom request.</div>
                        </div>
                        <div class="receipt-footer">
                            <button type="button" class="receipt-action" onclick="closeReceipt('<?php echo htmlspecialchars($customReceiptId, ENT_QUOTES); ?>')">Close</button>
                            <button type="button" class="receipt-action primary" onclick="printReceipt('<?php echo htmlspecialchars($customReceiptId, ENT_QUOTES); ?>')"><i class="fa-solid fa-print"></i> Print Receipt</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card empty"><i class="fa-solid fa-clipboard-question"></i><p>You have not submitted any custom request yet.</p></div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<footer class="footer">
    <div class="footer-line">© 2026 Politeknik Muadzam Shah. All rights reserved.</div>
</footer>

<!-- UPDATE PROFILE MODAL -->
<div id="updateProfileModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeUpdateProfile()">&times;</span>
        <h2>Update Profile</h2>
        <form id="updateProfileForm" enctype="multipart/form-data" novalidate>
            <div class="profile-top">
                <div class="edit-avatar-wrapper">
                    <?php $profile_img = $customer['profile_image'] ?? ''; $img_path = (strpos($profile_img,'images/profile/')===0) ? $profile_img : 'images/profile/'.$profile_img; ?>
                    <?php if (!empty($profile_img) && file_exists($img_path)): ?><img src="<?php echo htmlspecialchars($img_path); ?>" id="avatar-preview" class="edit-avatar-img" alt="Profile Picture"><?php else: ?><div id="avatar-default-icon" class="edit-avatar-default"><i class="fa-solid fa-user"></i></div><img src="" id="avatar-preview" class="edit-avatar-img hidden" alt="Profile Picture"><?php endif; ?>
                    <label for="profile_picture" class="camera-badge"><i class="fa-solid fa-camera"></i></label>
                </div>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="hidden" onchange="previewImage(event)">
                <div class="profile-basic-info"><h3><?php echo htmlspecialchars($customer['name']); ?></h3><p><?php echo htmlspecialchars($customer['email']); ?></p></div>
            </div>
            <label>Full Name</label><input type="text" name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
            <label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
            <label>Phone Number</label><input type="text" name="phone_number" value="<?php echo htmlspecialchars($customer['phone_number']); ?>" required>
            <label>New Password</label><div class="password-input-wrapper" id="newPasswordWrapper"><input type="password" id="newPassword" name="new_password" placeholder="Enter new password" minlength="6" autocomplete="new-password" aria-describedby="passwordHelp newPasswordError"><button type="button" class="form-password-toggle" onclick="togglePassword('newPassword','newPasswordEye')"><i class="fa-solid fa-eye" id="newPasswordEye"></i></button></div><p id="passwordHelp" class="password-help">Leave blank to keep your current password. A new password must contain at least 6 characters.</p><p id="newPasswordError" class="form-field-error" role="alert"></p>
            <label>Confirm Password</label><div class="password-input-wrapper" id="confirmPasswordWrapper"><input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm new password" autocomplete="new-password" aria-describedby="confirmPasswordError"><button type="button" class="form-password-toggle" onclick="togglePassword('confirmPassword','confirmPasswordEye')"><i class="fa-solid fa-eye" id="confirmPasswordEye"></i></button></div><p id="confirmPasswordError" class="form-field-error" role="alert"></p>
            <div class="modal-buttons"><button type="button" class="cancel-btn" onclick="closeUpdateProfile()">Cancel</button><button type="submit" class="save-btn">Update</button></div>
        </form>
    </div>
</div>

<!-- LOGOUT MODAL -->
<div id="logoutModal" class="modal">
    <div class="modal-content logout-modal-content">
        <span class="close" onclick="closeLogoutModal()">&times;</span>
        <i class="fa-solid fa-right-from-bracket logout-icon"></i>
        <h2>Logout</h2>
        <p>Are you sure you want to logout?</p>
        <div class="modal-buttons">
            <button type="button" class="cancel-btn" onclick="closeLogoutModal()">Cancel</button>
            <button type="button" class="confirm-btn" onclick="confirmLogout()">Logout</button>
        </div>
    </div>
</div>

<script>
function openLogoutModal(){
    Swal.fire({
        title: 'Logout',
        text: 'Are you sure you want to logout?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Logout',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#f0208d',
        cancelButtonColor: '#cbd8ff',
        reverseButtons: true,
        background: '#ffffff',
        customClass: {
            popup: 'logout-popup',
            title: 'logout-title',
            htmlContainer: 'logout-text',
            confirmButton: 'logout-confirm',
            cancelButton: 'logout-cancel'
        }
    }).then(function(result){
        if(result.isConfirmed){
            window.location.href = 'logout.php';
        }
    });
}

const menuToggle = document.getElementById('menuToggle');
const navbar = document.querySelector('.navbar');
if (menuToggle && navbar) {
    menuToggle.addEventListener('click', function () {
        const isOpen = navbar.classList.toggle('menu-open');
        menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        menuToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
        menuToggle.innerHTML = isOpen
            ? '<i class="fa-solid fa-xmark"></i>'
            : '<i class="fa-solid fa-bars"></i>';
    });
}

document.querySelectorAll('.receipt-modal').forEach(function(modal){
    document.body.appendChild(modal);
});

function openReceipt(id){
    const modal = document.getElementById(id);
    if(!modal) return;
    modal.classList.add('show');
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeReceipt(id){
    const modal = document.getElementById(id);
    if(!modal) return;
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function printReceipt(id){
    const modal = document.getElementById(id);
    if(!modal) return;

    document.querySelectorAll('.receipt-modal.print-active').forEach(function(receipt){
        receipt.classList.remove('print-active');
    });

    modal.classList.add('show');
    modal.classList.add('print-active');
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    setTimeout(function(){
        window.print();
    },150);
}

window.addEventListener('click',function(e){
    if(e.target.classList && e.target.classList.contains('receipt-modal')){
        e.target.classList.remove('show');
        e.target.style.display = 'none';
        e.target.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
});

window.addEventListener('afterprint',function(){
    document.querySelectorAll('.receipt-modal.show').forEach(function(modal){
        modal.classList.remove('show');
        modal.classList.remove('print-active');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    });
    document.body.style.overflow = '';
});

function closeLogoutModal(){document.getElementById('logoutModal').style.display='none'}
function confirmLogout(){
    closeLogoutModal();
    window.location.href = 'logout.php';
}
function openUpdateProfile(){document.getElementById('updateProfileModal').style.display='block'}
function closeUpdateProfile(){document.getElementById('updateProfileModal').style.display='none'}
function togglePassword(inputId,iconId){const input=document.getElementById(inputId),icon=document.getElementById(iconId);if(input.type==='password'){input.type='text';icon.classList.replace('fa-eye','fa-eye-slash')}else{input.type='password';icon.classList.replace('fa-eye-slash','fa-eye')}}
function previewImage(event){const file=event.target.files[0],img=document.getElementById('avatar-preview'),def=document.getElementById('avatar-default-icon');if(!file)return;const reader=new FileReader();reader.onload=e=>{img.src=e.target.result;img.classList.remove('hidden');if(def)def.classList.add('hidden')};reader.readAsDataURL(file)}
window.addEventListener('click',function(e){['logoutModal','updateProfileModal'].forEach(id=>{const m=document.getElementById(id);if(e.target===m)m.style.display='none'})});


// ================================
// ORDER TRACKING FILTERS
// ================================
(function(){
    const typeFilter = document.getElementById('trackingType');
    const collectionFilter = document.getElementById('trackingCollection');
    const items = Array.from(document.querySelectorAll('.tracking-item'));
    const empty = document.getElementById('trackingEmptyFiltered');
    if (!typeFilter || !collectionFilter) return;

    function applyTrackingFilters(){
        const type = typeFilter.value;
        const collection = collectionFilter.value;
        let visible = 0;

        items.forEach(function(item){
            const matchesType = type === 'all' || (item.dataset.orderType || '').toLowerCase() === type.toLowerCase();
            const itemCollection = (item.dataset.collection || '').trim().toLowerCase();
            const wantedCollection = collection.trim().toLowerCase();
            const matchesCollection = collection === 'all' || itemCollection === wantedCollection;
            const show = matchesType && matchesCollection;
            item.classList.toggle('is-hidden', !show);
            if (show) visible++;
        });

        if (empty) empty.style.display = visible === 0 ? 'block' : 'none';
    }

    typeFilter.addEventListener('change', applyTrackingFilters);
    collectionFilter.addEventListener('change', applyTrackingFilters);
    applyTrackingFilters();
})();

function showProfileError(message) {
    return Swal.fire({
        title: 'Tidak dapat kemas kini profil',
        text: message,
        icon: 'error',
        confirmButtonText: 'Semak semula',
        confirmButtonColor: '#f0208d',
        allowOutsideClick: false
    });
}

const updateProfileForm = document.getElementById('updateProfileForm');
const newPasswordInput = document.getElementById('newPassword');
const confirmPasswordInput = document.getElementById('confirmPassword');
const profileSaveButton = updateProfileForm.querySelector('button[type="submit"]');

function setPasswordFieldError(input, message) {
    const wrapper = document.getElementById(input.id + 'Wrapper');
    const error = document.getElementById(input.id + 'Error');
    wrapper.classList.toggle('has-error', Boolean(message));
    error.classList.toggle('is-visible', Boolean(message));
    error.textContent = message;
    input.setAttribute('aria-invalid', message ? 'true' : 'false');
}

function validatePasswordFields() {
    const password = newPasswordInput.value;
    const confirmation = confirmPasswordInput.value;
    let passwordError = '';
    let confirmationError = '';

    if (password && password.length < 6) {
        passwordError = 'Password must contain at least 6 characters.';
    }
    if (confirmation && !password) {
        confirmationError = 'Enter a new password first.';
    } else if (password.length >= 6 && confirmation && password !== confirmation) {
        confirmationError = 'Passwords do not match.';
    } else if (password.length >= 6 && !confirmation) {
        confirmationError = 'Please confirm your new password.';
    }

    setPasswordFieldError(newPasswordInput, passwordError);
    setPasswordFieldError(confirmPasswordInput, confirmationError);
    const isValid = !passwordError && !confirmationError;
    profileSaveButton.disabled = !isValid;
    return isValid;
}

newPasswordInput.addEventListener('input', validatePasswordFields);
confirmPasswordInput.addEventListener('input', validatePasswordFields);
validatePasswordFields();

updateProfileForm.addEventListener('submit', async function(e){
    e.preventDefault();
    const form = this;
    if (!validatePasswordFields()) {
        (newPasswordInput.value.length < 6 ? newPasswordInput : confirmPasswordInput).focus();
        return;
    }
    const requiredFields = [['name', 'Full name'], ['email', 'Email'], ['phone_number', 'Phone number']];
    const missing = requiredFields.find(([field]) => !form.elements[field].value.trim());
    if (missing) {
        showProfileError(`${missing[1]} is required.`).then(() => form.elements[missing[0]].focus());
        return;
    }
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.textContent = 'Updating...';
    try {
        const response = await fetch('update_profile.php', {method:'POST', body:new FormData(form)});
        const rawResponse = await response.text();
        let data;
        try { data = JSON.parse(rawResponse); } catch (_) { throw new Error('The server returned an invalid response.'); }
        if (!response.ok || data.status !== 'success') throw new Error(data.message || 'Unable to update profile.');
        closeUpdateProfile();
        Swal.fire({title:'Successfully!', text:data.message, icon:'success', confirmButtonColor:'#f0208d'}).then(()=>location.reload());
    } catch (error) {
        showProfileError(error.message || 'Terdapat masalah teknikal. Sila cuba lagi.');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Update';
    }
});
</script>
</body>
</html>
