<?php
session_start();
include("db.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Order ID missing.");
}

$order_id = $_GET['id'];
$source   = isset($_GET['source']) ? $_GET['source'] : 'Normal Order';

// Ambil data dari pangkalan data
if ($source == "Custom Request") {
    $numeric_id = intval(preg_replace('/[^0-9]/', '', $order_id));
    $sql = "SELECT * FROM custom_request WHERE id = '$numeric_id'";
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);

    $cust_name  = $data['fullname'] ?? '';
    $cust_phone = $data['phone'] ?? '';
    $product    = $data['product_name'] ?? 'Custom Order';
    $pickup     = $data['pickup_date'] ?? '';
    $price      = number_format($data['price'] ?? 0, 2);
    $remarks    = $data['remarks'] ?? '';
} else {
    $sql = "SELECT orders.*, customers.name AS cust_name, customers.phone_number AS cust_phone 
            FROM orders 
            LEFT JOIN customers ON orders.user_id = customers.id 
            WHERE orders.order_number = '$order_id'";
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);

    $cust_name  = $data['cust_name'] ?? '';
    $cust_phone = $data['cust_phone'] ?? '';
    $product    = $data['product_name'] ?? '';
    $pickup     = $data['pickup_date'] ?? '';
    $price      = number_format($data['total_price'] ?? 0, 2);
    $remarks    = $data['remarks'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Sheet - <?php echo htmlspecialchars($order_id); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 20px;
        }

        .jobsheet-card {
            width: 210mm; /* Saiz Lebar A4 */
            min-height: 148mm; /* Saiz A5 Horizontal */
            background: #fff;
            margin: auto;
            padding: 20px;
            border: 2px solid #800000; /* Warna Merah SA Design */
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #800000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .title {
            font-size: 28px;
            font-weight: bold;
            color: #800000;
        }

        .company-info {
            text-align: right;
        }

        .company-info h2 {
            margin: 0;
            color: #800000;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .box {
            border: 1px solid #ccc;
            padding: 10px;
            min-height: 120px;
        }

        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }

        /* Tetapan Khas Cetakan */
        @media print {
            .no-print { display: none; }
            body { background: white; padding: 0; }
            .jobsheet-card { box-shadow: none; border: 2px solid #000; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding: 10px 20px; background: #00AEEF; color: white; border: none; cursor: pointer; font-weight: bold; border-radius: 5px;">
        Print / Save to PDF
    </button>
</div>

<div class="jobsheet-card">
    <div class="header">
        <div>
            <div class="title">JOB SHEET</div>
            <div><strong>Job No:</strong> <?php echo htmlspecialchars($order_id); ?></div>
        </div>
        <div class="company-info">
            <h2>SA DESIGN</h2>
            <small>PRINTING & ADVERTISING</small>
        </div>
    </div>

    <table width="100%" cellpadding="6" style="margin-bottom: 15px;">
        <tr>
            <td><strong>Name:</strong> <?php echo htmlspecialchars($cust_name); ?></td>
            <td><strong>Tarikh Pickup:</strong> <?php echo htmlspecialchars($pickup); ?></td>
        </tr>
        <tr>
            <td><strong>No. Tel:</strong> <?php echo htmlspecialchars($cust_phone); ?></td>
            <td><strong>Jenis Product:</strong> <?php echo htmlspecialchars($product); ?></td>
        </tr>
    </table>

    <div class="details-grid">
        <div class="box">
            <strong>Lakaran / Spec:</strong>
            <p><?php echo nl2br(htmlspecialchars($remarks)); ?></p>
        </div>
        <div class="box">
            <strong>Flow Status:</strong><br><br>
            [  ] ADMIN<br>
            [  ] DESIGNER<br>
            [  ] PRODUCTION
        </div>
    </div>

    <table width="100%" border="1" cellspacing="0" cellpadding="8" style="border-collapse: collapse; text-align: center;">
        <tr>
            <th>Total Amount</th>
            <th>Payment Status</th>
        </tr>
        <tr>
            <td>RM <?php echo htmlspecialchars($price); ?></td>
            <td>Paid</td>
        </tr>
    </table>
</div>

</body>
</html>