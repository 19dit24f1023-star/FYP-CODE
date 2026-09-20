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
// PROCESS ACTIONS (UPDATE FULFILLMENT STATUS)
// =========================================================
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $source   = mysqli_real_escape_string($conn, $_POST['source']);
    $action   = $_POST['action'];

    if ($action === 'complete_pickup') {
        $collector = mysqli_real_escape_string($conn, trim($_POST['collector_name']));
        $time_now  = date('Y-m-d H:i:s');

        if ($source === 'Normal Order') {
            $sql = "UPDATE orders SET 
                    fulfillment_status = 'Completed', 
                    collector_name = '$collector', 
                    completed_at = '$time_now' 
                    WHERE order_number = '$order_id'";
        } else {
            $clean_id = (int) str_replace('CR', '', $order_id);
            $sql = "UPDATE custom_request SET 
                    fulfillment_status = 'Completed', 
                    collector_name = '$collector', 
                    completed_at = '$time_now' 
                    WHERE id = $clean_id";
        }
        mysqli_query($conn, $sql);
        $message = "Order $order_id successfully marked as Picked Up!";
    } 
    elseif ($action === 'complete_delivery') {
        $tracking = mysqli_real_escape_string($conn, trim($_POST['tracking_no']));
        $time_now = date('Y-m-d H:i:s');

        if ($source === 'Normal Order') {
            $sql = "UPDATE orders SET 
                    fulfillment_status = 'Completed', 
                    tracking_no = '$tracking', 
                    completed_at = '$time_now' 
                    WHERE order_number = '$order_id'";
        } else {
            $clean_id = (int) str_replace('CR', '', $order_id);
            $sql = "UPDATE custom_request SET 
                    fulfillment_status = 'Completed', 
                    tracking_no = '$tracking', 
                    completed_at = '$time_now' 
                    WHERE id = $clean_id";
        }
        mysqli_query($conn, $sql);
        $message = "Order $order_id tracking updated and marked as Delivered!";
    }
}

// =========================================================
// FETCH COMPLETED ORDERS READY FOR PICKUP / DELIVERY
// =========================================================
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'pickup';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$items = [];

// 1. Fetch Normal Orders (Status = Completed)
$sql_orders = "SELECT 
                orders.order_number AS id,
                customers.name AS customer,
                customers.phone_number AS phone,
                orders.product_name AS service,
                orders.fulfillment_type,
                orders.fulfillment_status,
                orders.tracking_no,
                orders.collector_name,
                orders.completed_at,
                'Normal Order' AS source
               FROM orders
               LEFT JOIN customers ON orders.user_id = customers.id
               WHERE LOWER(orders.status) = 'completed'";

$res1 = mysqli_query($conn, $sql_orders);
while ($row = mysqli_fetch_assoc($res1)) {
    $items[] = $row;
}

// 2. Fetch Custom Requests (Status = Completed)
$sql_custom = "SELECT 
                id,
                fullname AS customer,
                phone,
                product_name AS service,
                fulfillment_type,
                fulfillment_status,
                tracking_no,
                collector_name,
                completed_at,
                'Custom Request' AS source
               FROM custom_request
               WHERE LOWER(status) = 'completed'";

$res2 = mysqli_query($conn, $sql_custom);
while ($row = mysqli_fetch_assoc($res2)) {
    $row['id'] = "CR" . str_pad($row['id'], 4, "0", STR_PAD_LEFT);
    $items[] = $row;
}

// Filter data by Tab and Search Query
$filtered_items = [];
foreach ($items as $item) {
    $type = strtolower($item['fulfillment_type'] ?? 'pickup');

    if ($tab === 'pickup' && $type !== 'pickup') continue;
    if ($tab === 'delivery' && $type !== 'delivery') continue;

    if ($search !== '') {
        $search_lower = strtolower($search);
        if (
            strpos(strtolower($item['id']), $search_lower) === false &&
            strpos(strtolower($item['customer']), $search_lower) === false &&
            strpos(strtolower($item['phone'] ?? ''), $search_lower) === false
        ) {
            continue;
        }
    }

    $filtered_items[] = $item;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="ui_polish.css">
    <title>Fulfillment Hub | SA Design</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --pink: #E98BAA;
            --pink-light: #FCE8EF;
            --blue: #4F8FA8;
            --blue-dark: #39758D;
            --blue-light: #DFF3FA;
            --blue-pale: #EFF9FC;
            --dark-text: #304A56;
            --gray-text: #7C929C;
            --light-gray: #EAF6FB;
            --border: #D7EAF0;
            --white: #FFFFFF;
            --success: #78BFA4;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: "Segoe UI", sans-serif; background: var(--light-gray); color: var(--dark-text); min-height: 100vh; }
        a { text-decoration: none; }

        /* SIDEBAR */
        .sidebar { position: fixed; top: 22px; left: 22px; width: 245px; height: calc(100vh - 44px); background: var(--white); border: 1px solid var(--border); border-radius: 24px; display: flex; flex-direction: column; z-index: 1000; box-shadow: 0 18px 45px rgba(79, 143, 168, 0.14); }
        .sidebar-brand { padding: 27px 25px 25px; border-bottom: 1px solid var(--border); }
        .brand-main { font-size: 25px; font-weight: 900; color: #EF228B; }
        .brand-main span { color: #1C6EF2; }
        .sidebar-menu { list-style: none; padding: 15px 13px; flex: 1; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 13px; padding: 12px 14px; color: #718A95; border-radius: 12px; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .sidebar-menu li.active a, .sidebar-menu li a:hover { background: var(--pink-light); color: var(--pink); }

        /* MAIN CONTENT */
        .main-content { margin-left: 289px; padding: 34px 38px 50px; }
        .page-title { font-size: 28px; font-weight: 800; color: var(--blue-dark); margin-bottom: 5px; }
        .page-subtitle { font-size: 13px; color: var(--gray-text); margin-bottom: 25px; }

        /* ALERT MESSAGE */
        .alert-success { background: #E2F5EC; color: #3C8363; border: 1px solid #C4ECDA; padding: 12px 18px; border-radius: 12px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }

        /* TABS */
        .tabs-container { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 12px 24px; border-radius: 12px; background: var(--white); border: 1px solid var(--border); color: var(--gray-text); font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .tab-btn.active { background: var(--blue-dark); color: #FFF; border-color: var(--blue-dark); }

        /* SEARCH BAR */
        .search-card { background: var(--white); padding: 18px; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 20px; }
        .search-form { display: flex; gap: 10px; }
        .search-input { flex: 1; padding: 10px 15px; border-radius: 10px; border: 1px solid var(--border); outline: none; background: var(--blue-pale); font-size: 13px; }
        .btn-search { padding: 10px 20px; background: var(--pink); color: #FFF; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }

        /* CARDS GRID */
        .orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .order-card { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 20px; box-shadow: 0 10px 25px rgba(79, 143, 168, 0.08); display: flex; flex-direction: column; justify-content: space-between; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--blue-pale); padding-bottom: 10px; }
        .order-id { font-weight: 800; color: var(--blue-dark); font-size: 15px; }
        .badge-status { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; }
        .status-ready { background: #FFF3CD; color: #856404; }
        .status-done { background: #E2F5EC; color: #3C8363; }

        .customer-info { margin-bottom: 15px; }
        .cust-name { font-weight: 700; font-size: 14px; color: var(--dark-text); }
        .cust-phone { font-size: 12px; color: var(--gray-text); margin-top: 2px; }
        .service-title { font-size: 12px; background: var(--blue-pale); padding: 8px 12px; border-radius: 8px; color: var(--blue-dark); font-weight: 600; margin-top: 8px; }

        /* ACTION FORM */
        .action-box { margin-top: 15px; border-top: 1px dashed var(--border); padding-top: 15px; }
        .form-control { width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 12px; margin-bottom: 8px; outline: none; }
        .btn-submit { width: 100%; padding: 10px; border: none; border-radius: 8px; background: var(--blue-dark); color: #FFF; font-weight: 700; font-size: 12px; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: var(--pink); }

        .history-info { font-size: 11px; color: var(--gray-text); line-height: 1.5; background: #F8FCFD; padding: 10px; border-radius: 8px; }
        .empty-box { text-align: center; padding: 40px; background: var(--white); border-radius: 16px; border: 1px solid var(--border); grid-column: 1 / -1; color: var(--gray-text); }

        @media (max-width: 1050px) {
            .sidebar { width: 215px; left: 16px; }
            .main-content { margin-left: 247px; padding: 28px 22px 40px; }
            .orders-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
        }

        @media (max-width: 750px) {
            body { overflow-x: hidden; }
            .sidebar { position: relative; top: auto; left: auto; width: 100%; height: auto; margin: 0 0 20px; border-radius: 0; }
            .sidebar-brand { padding: 20px; }
            .sidebar-menu { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 5px; padding: 12px; }
            .sidebar-menu li a { min-width: 0; margin: 0; padding: 11px 10px; font-size: 12px; }
            .main-content { margin-left: 0; padding: 24px 18px 35px; }
            .tabs-container { flex-wrap: wrap; }
            .tab-btn { flex: 1 1 200px; justify-content: center; }
            .search-form { flex-direction: column; }
            .search-input, .btn-search { width: 100%; min-height: 42px; }
            .orders-grid { grid-template-columns: 1fr; gap: 14px; }
        }

        @media (max-width: 430px) {
            .main-content { padding: 20px 14px 30px; }
            .page-title { font-size: 24px; }
            .page-subtitle { line-height: 1.55; }
            .sidebar-menu { grid-template-columns: 1fr; }
            .tabs-container { flex-direction: column; }
            .tab-btn { width: 100%; flex-basis: auto; }
            .order-card { padding: 16px; }
            .card-header { align-items: flex-start; gap: 8px; flex-direction: column; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-main">SA<span>DESIGN</span></div>
    </div>
    <ul class="sidebar-menu">
        <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a></li>
        <li><a href="manage_orders.php"><i class="fa-solid fa-boxes-stacked"></i><span>Manage Orders</span></a></li>
        <li class="active"><a href="fulfillment.php"><i class="fa-solid fa-truck-ramp-box"></i><span>Fulfillment Hub</span></a></li>
    </ul>
</aside>

<main class="main-content">
    <h1 class="page-title">Fulfillment Hub</h1>
    <p class="page-subtitle">Uruskan penyerahan barang yang telah siap (Self-Pickup & Delivery Log).</p>

    <?php if ($message !== ''): ?>
        <div class="alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $message; ?></div>
    <?php endif; ?>

    <div class="tabs-container">
        <a href="fulfillment.php?tab=pickup" class="tab-btn <?php echo ($tab === 'pickup') ? 'active' : ''; ?>">
            <i class="fa-solid fa-store"></i> Self Pickup
        </a>
        <a href="fulfillment.php?tab=delivery" class="tab-btn <?php echo ($tab === 'delivery') ? 'active' : ''; ?>">
            <i class="fa-solid fa-truck-fast"></i> Postage / Delivery
        </a>
    </div>

    <div class="search-card">
        <form method="GET" action="fulfillment.php" class="search-form">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
            <input type="text" name="search" class="search-input" placeholder="Cari Order ID, Nama Pelanggan, atau No. Telefon..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
        </form>
    </div>

    <div class="orders-grid">
        <?php if (count($filtered_items) > 0): ?>
            <?php foreach ($filtered_items as $item): ?>
                <?php $is_done = ($item['fulfillment_status'] === 'Completed'); ?>
                <div class="order-card">
                    <div>
                        <div class="card-header">
                            <span class="order-id">#<?php echo htmlspecialchars($item['id']); ?></span>
                            <span class="badge-status <?php echo $is_done ? 'status-done' : 'status-ready'; ?>">
                                <?php echo $is_done ? ($tab === 'pickup' ? 'Picked Up' : 'Delivered') : 'Ready for ' . ucfirst($tab); ?>
                            </span>
                        </div>

                        <div class="customer-info">
                            <div class="cust-name"><?php echo htmlspecialchars($item['customer']); ?></div>
                            <div class="cust-phone"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($item['phone'] ?? 'N/A'); ?></div>
                            <div class="service-title"><i class="fa-solid fa-box"></i> <?php echo htmlspecialchars($item['service']); ?></div>
                        </div>
                    </div>

                    <div class="action-box">
                        <?php if (!$is_done): ?>
                            <?php if ($tab === 'pickup'): ?>
                                <form method="POST">
                                    <input type="hidden" name="order_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="source" value="<?php echo $item['source']; ?>">
                                    <input type="hidden" name="action" value="complete_pickup">
                                    <input type="text" name="collector_name" class="form-control" placeholder="Nama Ambil (cth: Diri sendiri / Suami)" required>
                                    <button type="submit" class="btn-submit"><i class="fa-solid fa-hand-holding"></i> Sahkan Pickup</button>
                                </form>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="order_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="source" value="<?php echo $item['source']; ?>">
                                    <input type="hidden" name="action" value="complete_delivery">
                                    <input type="text" name="tracking_no" class="form-control" placeholder="Masukkan No Tracking Pos" required>
                                    <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Sahkan Delivery</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="history-info">
                                <?php if ($tab === 'pickup'): ?>
                                    <strong><i class="fa-solid fa-user-check"></i> Diambil oleh:</strong> <?php echo htmlspecialchars($item['collector_name']); ?><br>
                                <?php else: ?>
                                    <strong><i class="fa-solid fa-barcode"></i> Tracking No:</strong> <?php echo htmlspecialchars($item['tracking_no']); ?><br>
                                <?php endif; ?>
                                <strong><i class="fa-solid fa-clock"></i> Masa:</strong> <?php echo date('d/m/Y h:i A', strtotime($item['completed_at'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-box">
                <i class="fa-solid fa-inbox fa-2x" style="margin-bottom:10px;"></i>
                <p>Tiada rekod tempahan ditemui untuk kategori ini.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
