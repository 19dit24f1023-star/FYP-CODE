<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$customerId = (int)($_SESSION['customer_id'] ?? $_SESSION['user_id']);
$message = $_SESSION['wallet_error'] ?? '';
unset($_SESSION['wallet_error']);

$account = $conn->prepare('INSERT IGNORE INTO wallet_accounts (customer_id) VALUES (?)');
$account->bind_param('i', $customerId);
$account->execute();
$account->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = round((float)($_POST['amount'] ?? 0), 2);
    if ($amount < 5 || $amount > 10000) {
        $message = 'Top ups must be between RM 5.00 and RM 10,000.00.';
    } else {
        header('Location: wallet_toyyibpay.php?amount=' . urlencode(number_format($amount, 2, '.', '')));
        exit;
    }
}

$balanceStmt = $conn->prepare('SELECT balance FROM wallet_accounts WHERE customer_id = ?');
$balanceStmt->bind_param('i', $customerId);
$balanceStmt->execute();
$balance = (float)($balanceStmt->get_result()->fetch_assoc()['balance'] ?? 0);
$balanceStmt->close();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Wallet | SA Design</title>
    <link rel="stylesheet" href="ui_polish.css">
    
    <!-- Pautan Google Fonts Rasmi -->
    <link rel="preconnect" href="https://googleapis.com">
    <link rel="preconnect" href="https://gstatic.com" crossorigin>
    <link href="https://googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
    *{box-sizing:border-box}
    body{font-family:'Plus Jakarta Sans',sans-serif;background:#f6e8f2;margin:0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center}
    
    .wallet-container {
        width: min(480px, calc(100% - 40px));
        margin: 40px auto;
    }

    .card{
        background:#fff;
        padding:32px;
        border-radius:24px;
        border:1px solid #e1d8e5;
        box-shadow:0 20px 50px rgba(16,47,145,0.08);
        position:relative;
        overflow:hidden;
    }

    /* Hero Accent Background Line */
    .card::before {
        content:"";
        position:absolute;
        top:0; left:0; right:0;
        height:6px;
        background:linear-gradient(90deg, #1557d6 0%, #f0208d 100%);
    }

    h1{
        color:#102f91;
        font-size:26px;
        font-weight:900;
        letter-spacing:-1px;
        margin:0 0 4px 0;
    }
    
    .subtitle {
        color:#64748b;
        font-size:12px;
        margin:0 0 24px 0;
    }

    .balance-box {
        background:linear-gradient(135deg, #fff3fa 0%, #fff8fc 100%);
        border:1px solid #f2c9de;
        padding:24px;
        border-radius:18px;
        display:flex;
        align-items:center;
        gap:16px;
        margin-bottom:24px;
    }

    .balance-icon {
        width:54px;
        height:54px;
        background:#f0208d;
        color:#fff;
        border-radius:14px;
        display:flex;
        align-items:center;
        justify-content:center;
        box-shadow:0 8px 18px rgba(236,44,145,0.2);
    }

    .balance-icon svg {
        width: 24px;
        height: 24px;
        fill: currentColor;
    }

    .balance-details span {
        display:block;
        color:#7b8ba8;
        font-size:10px;
        font-weight:800;
        text-transform:uppercase;
        letter-spacing:0.5px;
        margin-bottom:2px;
    }

    .balance-amount {
        font-size:32px;
        color:#102f91;
        font-weight:900;
        line-height:1;
    }

    label{
        display:block;
        margin-bottom:8px;
        color:#35445d;
        font-weight:800;
        font-size:11px;
        text-transform:uppercase;
        letter-spacing:0.3px;
    }

    .input-wrapper {
        position:relative;
        display:flex;
        align-items:center;
    }

    .input-icon {
        position:absolute;
        left:16px;
        color:#94a3b8;
        font-size:14px;
        font-weight:700;
    }

    input[type="number"]{
        width:100%;
        padding:14px 14px 14px 44px;
        border-radius:12px;
        border:1px solid #d0c5d5;
        font-family:inherit;
        font-size:14px;
        font-weight:600;
        color:#182033;
        outline:none;
        transition:0.2s;
        background:#fcfafc;
    }

    input[type="number"]:focus{
        border-color:#f0208d;
        background:#fff;
        box-shadow:0 0 0 4px rgba(240,32,141,0.08);
    }

    button{
        width:100%;
        margin-top:20px;
        background:#f0208d;
        color:#fff;
        border:0;
        padding:14px;
        border-radius:13px;
        font-family:inherit;
        font-size:13px;
        font-weight:800;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        box-shadow:0 8px 18px rgba(236,44,145,0.16);
        transition:0.2s ease;
    }
    
    button:hover {
        background:#d61a7c;
        transform:translateY(-1px);
        box-shadow:0 10px 20px rgba(236,44,145,0.24);
    }

    button svg {
        width: 16px;
        height: 16px;
        fill: currentColor;
    }

    .error{
        padding:12px;
        border-radius:10px;
        background:#fff0f4;
        color:#c21d67;
        font-size:12px;
        font-weight:600;
        margin:0 0 20px 0;
        display:flex;
        align-items:center;
        gap:8px;
        border:1px solid #fbcfe8;
    }

    .back-link{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:6px;
        margin-top:20px;
        color:#1557d6;
        font-size:12px;
        text-decoration:none;
        font-weight:800;
        width:100%;
        transition:0.2s;
    }
    
    .back-link:hover {
        color:#f0208d;
    }

    .back-link svg {
        width: 12px;
        height: 12px;
        fill: currentColor;
    }
    </style>
</head>
<body>
    <div class="wallet-container">
        <main class="card">
            <h1>My Wallet</h1>
            <p class="subtitle">Securely top up your account balance for instant checkouts.</p>
            
            <?php if($message): ?>
                <div class="error">
                    <!-- SVG Exclamation Icon -->
                    <svg viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zm32 224a32 32 0 1 1 -64 0 32 32 0 1 1 64 0z"/></svg>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <div class="balance-box">
                <div class="balance-icon">
                    <!-- Kod SVG Dompet Fizikal (Kalis Isu Gambar Hilang) -->
                    <svg viewBox="0 0 512 512"><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V192c0-35.3-28.7-64-64-64H80c-8.8 0-16-7.2-16-16s7.2-16 16-16H448c13.3 0 24-10.7 24-24s-10.7-24-24-24H64zM416 336a32 32 0 1 1 0-64 32 32 0 1 1 0 64z"/></svg>
                </div>
                <div class="balance-details">
                    <span>Available Balance</span>
                    <div class="balance-amount">RM <?= number_format($balance,2) ?></div>
                </div>
            </div>

            <form method="post">
                <label for="amount">Top up amount</label>
                <div class="input-wrapper">
                    <span class="input-icon">RM</span>
                    <input id="amount" name="amount" type="number" min="5" max="10000" step="0.01" placeholder="0.00" required>
                </div>
                <button type="submit">
                    <!-- Kod SVG Kad Kredit Fizikal -->
                    <svg viewBox="0 0 576 512"><path d="M0 112c0-26.5 21.5-48 48-48H528c26.5 0 48 21.5 48 48V368c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112zm48 16V192H528V128H48zm0 112v80H528V240H48z"/></svg>
                    Top up with ToyyibPay
                </button>
            </form>
            
            <a href="cust_profile.php" class="back-link">
                <!-- Kod SVG Anak Panah Kembali -->
                <svg viewBox="0 0 448 512"><path d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.2 288H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H109.2l105.4-105.4c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"/></svg>
                Back to my account
            </a>
        </main>
    </div>
</body>
</html>
