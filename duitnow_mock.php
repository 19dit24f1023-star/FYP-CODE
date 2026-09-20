<?php
session_start();
if (!isset($_GET['order']) || !isset($_SESSION['pending_order'])) {
    header('Location: index.php');
    exit();
}

$orderNumber = $_GET['order'];
$orderData = $_SESSION['pending_order'];
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DuitNow QR Sandbox | SA Design</title>
    <link rel="stylesheet" href="https://cloudflare.com">
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; color: #1e293b; }
        .qr-card { background: #ffffff; width: 100%; max-width: 420px; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); padding: 32px; text-align: center; box-sizing: border-box; }
        .badge { background: #fee2e2; color: #dc2626; padding: 6px 14px; border-radius: 999px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px; display: inline-block; margin-bottom: 20px; }
        .logo-section { display: flex; justify-content: center; align-items: center; gap: 8px; margin-bottom: 24px; }
        .logo-section h2 { font-size: 20px; font-weight: 800; margin: 0; color: #102f91; }
        .logo-section h2 span { color: #f0208d; }
        .amount-display { font-size: 32px; font-weight: 800; color: #0f172a; margin: 10px 0; }
        .order-ref { font-size: 13px; color: #64748b; margin-bottom: 24px; }
        .qr-placeholder { background: #fff0f6; border: 3px solid #f9a8d4; border-radius: 16px; padding: 20px; display: inline-block; position: relative; margin-bottom: 24px; }
        .qr-placeholder img { width: 220px; height: 220px; display: block; border-radius: 8px; }
        .instruction { font-size: 13px; color: #475569; line-height: 1.6; margin-bottom: 28px; padding: 0 10px; }
        .btn-group { display: flex; flex-direction: column; gap: 10px; }
        .btn { border: 0; border-radius: 12px; padding: 14px; font-size: 13px; font-weight: 800; cursor: pointer; transition: 0.2s; text-decoration: none; display: block; }
        .btn-success { background: #16a34a; color: #ffffff; box-shadow: 0 8px 16px rgba(22,163,74,0.2); }
        .btn-success:hover { background: #15803d; transform: translateY(-1px); }
        .btn-cancel { background: #f1f5f9; color: #64748b; }
        .btn-cancel:hover { background: #e2e8f0; }
        @media (max-width: 480px) {
            body { padding: 16px; align-items: flex-start; }
            .qr-card { width: 100%; padding: 24px 16px; margin: 16px 0; }
            .qr-placeholder { padding: 12px; }
            .qr-placeholder img { width: min(220px, calc(100vw - 88px)); height: auto; aspect-ratio: 1; }
            .amount-display { font-size: 28px; }
        }
    </style>
</head>
<body>

<div class="qr-card">
    <span class="badge"><i class="fa-solid fa-flask"></i> FYP SANDBOX SIMULATOR</span>
    
    <div class="logo-section">
        <h2><span>SA</span> DESIGN • DuitNow QR</h2>
    </div>

    <p style="margin:0; font-size:13px; color:#64748b; font-weight:600;">Total Payment</p>
    <div class="amount-display">RM <?php echo number_format($orderData['total'], 2); ?></div>
    <div class="order-ref">Order No.: <strong><?php echo htmlspecialchars($orderNumber); ?></strong></div>

    <!-- QR Code Dinamik (Menjana gambar QR mengikut nombor pesanan & jumlah) -->
    <div class="qr-placeholder">
        <?php 
            $qrPayload = "DuitNow QR - SA Design | Order: " . $orderNumber . " | Amount: RM " . number_format($orderData['total'], 2);
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($qrPayload);
        ?>
        <img src="<?php echo $qrUrl; ?>" alt="DuitNow QR Mock">
    </div>

    <p class="instruction">
        <i class="fa-solid fa-circle-info" style="color:#2563eb;"></i> 
        Please click the button below to simulate a successful QR code scan using a customer's mobile phone.
    </p>

    <div class="btn-group">
        <a href="payment_return.php?order_id=<?php echo urlencode($orderNumber); ?>" class="btn btn-success">
            <i class="fa-solid fa-circle-check"></i> CONFIRMED SUCCESSFUL
        </a>
        <a href="cart.php" class="btn btn-cancel">Cancel Transaction</a>
    </div>
</div>

</body>
</html>
