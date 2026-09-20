<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/toyyibpay_create_bill.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$customerId = (int)$_SESSION['customer_id'];
$requestId = (int)($_GET['id'] ?? $_POST['request_id'] ?? 0);
$message = '';
$success_message = '';
$request = null;

// 1. Dapatkan data baki wallet pelanggan
$wallet_balance = 0.00;
$wallet_stmt = $conn->prepare("SELECT balance FROM wallet_accounts WHERE customer_id = ?");
if ($wallet_stmt) {
    $wallet_stmt->bind_param("i", $customerId);
    $wallet_stmt->execute();
    $wallet_row = $wallet_stmt->get_result()->fetch_assoc();
    $wallet_balance = (float)($wallet_row['balance'] ?? 0);
    $wallet_stmt->close();
}

// 2. Dapatkan data tempahan berdasarkan ID dan emel pelanggan
$stmt = $conn->prepare('SELECT * FROM custom_request WHERE id = ? AND email = (SELECT email FROM customers WHERE id = ?) LIMIT 1');
if ($stmt) {
    $stmt->bind_param('ii', $requestId, $customerId);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$request) {
    http_response_code(404);
    exit('Custom request not found.');
}

if (strcasecmp((string)($request['payment_status'] ?? 'Pending'), 'Paid') === 0) {
    header('Location: cust_profile.php?page=custom');
    exit;
}

$isPricePending = !isset($request['price']) || !is_numeric($request['price']) || (float)$request['price'] <= 0;
$total_amount = (float)($request['price'] ?? 0);

// 3. Proses form submit berdasarkan kaedah pilihan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isPricePending) {
    $payment_method = $_POST['payment_method'] ?? '';

    if (empty($payment_method)) {
        $message = 'Sila pilih salah satu kaedah pembayaran.';
    } elseif (!in_array($payment_method, ['Wallet', 'ToyyibPay', 'Cash on Collection'], true)) {
        $message = 'Please select a valid payment method.';
    }
    // ==========================================
    // PILIHAN A: WALLET SA DESIGN
    // ==========================================
    elseif ($payment_method === 'Wallet') {
        if ($wallet_balance < $total_amount) {
            $message = 'Your e-Wallet balance is insufficient. Please top up or select another method.';
        } else {
            $conn->begin_transaction();
            try {
                // Tolak baki wallet
                $new_balance = $wallet_balance - $total_amount;
                $up_wallet = $conn->prepare("UPDATE wallet_accounts SET balance = ? WHERE customer_id = ?");
                $up_wallet->bind_param("di", $new_balance, $customerId);
                $up_wallet->execute();
                $up_wallet->close();

                // Kemas kini status custom request
                $up_req = $conn->prepare("UPDATE custom_request SET payment_status = 'Paid', payment_method = 'Wallet' WHERE id = ?");
                $up_req->bind_param("i", $requestId);
                $up_req->execute();
                $up_req->close();

                $conn->commit();
                $success_message = 'Payment using e-Wallet successful!';
                header("Refresh: 2; url=cust_profile.php?page=custom");
            } catch (Exception $e) {
                $conn->rollback();
                $message = 'Wallet transaction issues:' . $e->getMessage();
            }
        }
    } 
    // ==========================================
    // PILIHAN B: ONLINE BANKING / FPX via TOYYIBPAY
    // ==========================================
    elseif ($payment_method === 'ToyyibPay') {
        $customer = $conn->prepare('SELECT name, email, phone_number FROM customers WHERE id = ? LIMIT 1');
        $customer->bind_param('i', $customerId);
        $customer->execute();
        $customerDetails = $customer->get_result()->fetch_assoc();
        $customer->close();

        $orderNumber = 'CR' . str_pad((string)$requestId, 5, '0', STR_PAD_LEFT);
        $bill = createToyyibPayBill(
            $orderNumber,
            (string)($customerDetails['name'] ?? 'SA Design Customer'),
            (string)($customerDetails['email'] ?? $request['email']),
            (string)($customerDetails['phone_number'] ?? ''),
            $total_amount,
            'FPX'
        );

        if ($bill['success']) {
            $update = $conn->prepare('UPDATE custom_request SET payment_method = ? WHERE id = ?');
            $method = 'ToyyibPay (FPX)';
            $update->bind_param('si', $method, $requestId);
            $update->execute();
            $update->close();

            header('Location: ' . $bill['payment_url']);
            exit;
        }

        $message = $bill['message'] ?? 'ToyyibPay could not create a payment bill. Please try again.';
    } 
    // ==========================================
    // PILIHAN C: CASH ON COLLECTION
    // ==========================================
        elseif ($payment_method === 'Cash on Collection') {
        // Tukar status kepada 'Pending Approval' untuk semakan admin
        $update = $conn->prepare("UPDATE custom_request SET payment_method = ?, payment_status = 'Pending Approval' WHERE id = ?");
        $update->bind_param('si', $payment_method, $requestId);
        
        if ($update->execute()) {
            $success_message = 'Payment submitted! Please wait for admin approval.';
            header("Refresh: 3; url=cust_profile.php?page=custom");
        } else {
            $message = 'Failed to update payment information.';
        }
        $update->close();
    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment | SA Design</title>
    <link rel="stylesheet" href="ui_polish.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #f6e8f2; color: #172033; font-family: 'Plus Jakarta Sans', Arial, sans-serif; }
        a { text-decoration: none; }
        .payment-nav { min-height: 84px; padding: 16px 48px; display: flex; align-items: center; justify-content: space-between; gap: 24px; background: #fff; border-bottom: 1px solid #e8ebf2; }
        .payment-brand { display: flex; flex-direction: column; line-height: .92; }
        .payment-brand-main { font-size: 31px; font-weight: 900; letter-spacing: -2px; }
        .payment-brand-main span:first-child { color: #f0208d; }
        .payment-brand-main span:last-child { color: #1557d6; }
        .payment-brand small { margin-top: 5px; color: #172033; font-size: 9px; font-weight: 900; letter-spacing: .8px; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: #1747c7; font-size: 12px; font-weight: 800; }
        .back-link:hover { color: #f0208d; }
        .payment-stage { width: min(100% - 40px, 610px); margin: 58px auto 70px; }
        .payment-card { position: relative; overflow: hidden; width: 100%; padding: 37px; background: #fff; border: 1px solid #e0e6f1; border-radius: 16px; box-shadow: 0 20px 48px rgba(28, 53, 118, .10); }
        .payment-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: linear-gradient(90deg, #1747c7 0 48%, #f0208d 48% 100%); }
        .payment-card h1 { margin: 0 0 8px; color: #102f91; font-size: 29px; font-weight: 900; letter-spacing: -1px; }
        .muted { color: #64748b; font-size: 12px; line-height: 1.6; }
        .amount { margin: 25px 0 26px; padding: 18px 20px; border: 1px solid #ffd2e5; border-radius: 12px; background: #fff3fa; color: #f0208d; font-size: 27px; font-weight: 900; }
        .amount::before { content: 'AMOUNT DUE'; display: block; margin-bottom: 4px; color: #a76a89; font-size: 10px; font-weight: 900; letter-spacing: 1px; }
        .amount.pending-amount { display: flex; align-items: center; justify-content: center; gap: 8px; border-color: #f7d799; background: #fff8e6; color: #b76a05; font-size: 17px; }
        .amount.pending-amount::before { display: none; }
        .payment-card form > label { display: block; margin: 0 0 11px; color: #102f91; font-size: 11px; font-weight: 900; letter-spacing: .8px; }
        .method-list { gap: 10px !important; }
        .method-item { position: relative; margin: 0 !important; padding: 16px !important; border: 1px solid #dce4f1 !important; border-radius: 12px !important; background: #fff !important; transition: .2s; }
        .method-item:hover { border-color: #9db7f4 !important; background: #f9fbff !important; }
        .method-item:has(input:checked) { border-color: #1747c7 !important; background: #eef3ff !important; box-shadow: inset 4px 0 #1747c7; }
        .method-item input { width: 17px; height: 17px; margin: 0 !important; accent-color: #1747c7; }
        .method-item strong { font-size: 13px !important; }
        #cashPanel { border-color: #b9e9d4 !important; border-radius: 12px !important; background: #effaf6 !important; color: #087154 !important; }
        button { width: 100%; margin-top: 22px; padding: 14px; border: 0; border-radius: 12px; background: linear-gradient(135deg, #f0208d, #ff3b86); color: #fff; cursor: pointer; font: 800 12px 'Plus Jakarta Sans', Arial, sans-serif; letter-spacing: .3px; box-shadow: 0 10px 20px rgba(240, 32, 141, .22); transition: .2s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 14px 25px rgba(240, 32, 141, .28); }
        .error { padding: 12px; border: 1px solid #f5baca; border-radius: 10px; background: #fff0f4; color: #c21d67; font-size: 12px; }
        .success { border: 1px solid #b9e9d4 !important; }
        .payment-card > a { display: block; margin-top: 20px; color: #1747c7; font-size: 12px; font-weight: 800; text-align: center; }
        .payment-card > a:hover { color: #f0208d; }
        @media (max-width: 760px) { .payment-nav { padding: 15px 20px; } .payment-brand-main { font-size: 27px; } .payment-stage { margin: 34px auto 48px; } .payment-card { padding: 29px 21px; } }
    </style>
</head>
<body>
    <header class="payment-nav">
        <a class="payment-brand" href="index.php" aria-label="SA Design home">
            <span class="payment-brand-main"><span>SA</span> <span>DESIGN</span></span>
            <small>PRINTING &amp; ADVERTISING</small>
        </a>
        <a class="back-link" href="cust_profile.php?page=custom"><i class="fa-solid fa-arrow-left"></i> Custom Requests</a>
    </header>
    <div class="payment-stage">
    <main class="payment-card">
        <h1>Make Payment</h1>
        <p class="muted">Custom Request #CR<?php echo str_pad((string)$requestId, 3, '0', STR_PAD_LEFT); ?> · <?php echo htmlspecialchars($request['product_name']); ?></p>
        
        <?php if ($isPricePending): ?>
            <div class="amount pending-amount">
                <i class="fa-solid fa-clock"></i> Quotation Pending
            </div>
            <p class="muted" style="text-align:center; line-height:1.5;">
                Your request is currently being reviewed by admin. Payment will be available once the quotation price is updated.
            </p>
            <a href="cust_profile.php?page=custom"><i class="fa-solid fa-arrow-left"></i> Back to Custom Requests</a>
        <?php else: ?>
            <div class="amount">RM <?php echo number_format($total_amount, 2); ?></div>
            
            <?php if ($message !== ''): ?>
                <div class="error" style="margin-bottom:15px;"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($success_message !== ''): ?>
                <div class="success" style="padding:10px; border-radius:10px; background:#e6f9ed; color:#15803d; font-size:12px; margin-bottom:15px; font-weight:bold; text-align:center;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="customPaymentForm">
                <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                
                <label>SELECT PAYMENT METHOD</label>
                <div class="method-list" style="display:grid; gap:10px;">
                    
                    <!-- 1. SALDO WALLET -->
                    <label class="method-item" style="display:flex; align-items:center; gap:12px; padding:14px; border:1px solid #d7dff0; border-radius:11px; cursor:pointer; background:#fff;">
                        <input type="radio" name="payment_method" value="Wallet" required onchange="togglePaymentPanels(this.value)">
                        <div style="flex:1;">
                            <strong style="color:#102f91; font-size:14px;"><i class="fa-solid fa-wallet" style="color:#f0208d; margin-right:5px;"></i> SA Design Wallet</strong>
                            <div class="muted" style="font-size:11px; margin-top:2px;">Current Balance: RM <?php echo number_format($wallet_balance, 2); ?></div>
                        </div>
                    </label>

                    <!-- 2. TOYYIBPAY BANKING -->
                    <label class="method-item" style="display:flex; align-items:center; gap:12px; padding:14px; border:1px solid #d7dff0; border-radius:11px; cursor:pointer; background:#fff;">
                        <input type="radio" name="payment_method" value="ToyyibPay" onchange="togglePaymentPanels(this.value)">
                        <div style="flex:1;">
                            <strong style="color:#102f91; font-size:14px;"><i class="fa-solid fa-building-columns" style="color:#1b6ef5; margin-right:5px;"></i> ToyyibPay Online Banking / FPX</strong>
                            <div class="muted" style="font-size:11px; margin-top:2px;">Pay securely via the ToyyibPay gateway.</div>
                        </div>
                    </label>

                    <!-- 3. CASH ON COLLECTION -->
                    <label class="method-item" style="display:flex; align-items:center; gap:12px; padding:14px; border:1px solid #d7dff0; border-radius:11px; cursor:pointer; background:#fff;">
                        <input type="radio" name="payment_method" value="Cash on Collection" onchange="togglePaymentPanels(this.value)">
                        <div style="flex:1;">
                            <strong style="color:#102f91; font-size:14px;"><i class="fa-solid fa-money-bill-wave" style="color:#16a34a; margin-right:5px;"></i> Cash on Collection </strong>
                            <div class="muted" style="font-size:11px; margin-top:2px;">Pay in cash when collecting the product at the store.</div>
                        </div>
                    </label>
                </div>

                <div id="cashPanel" style="display:none; background:#f0fdf4; padding:12px; border-radius:12px; border:1px solid #bbf7d0; margin-top:15px; font-size:12px; color:#166534; line-height:1.4;">
                    <i class="fa-solid fa-circle-info"></i> You have chosen to pay in cash at the counter. Please ensure you have sufficient cash ready when collecting the items. Click the confirmation button below to notify the management.
                </div>

                <button type="submit" id="btnSubmitPayment" style="margin-top:20px;"><i class="fa-solid fa-circle-check"></i> Confirm Payment</button>
            </form>
            <a href="cust_profile.php?page=custom">Back to Custom Requests</a>
        <?php endif; ?>
    </main>
    </div>

    <!-- JavaScript untuk Menukar Teks Butang & Paparan Sub-Panel Dinamik (Warna Ditetapkan Konsisten) -->
    <script>
        function togglePaymentPanels(method) {
            const cashPanel = document.getElementById('cashPanel');
            const btnSubmit = document.getElementById('btnSubmitPayment');
            
            // Set semula paparan panel luar talian
            cashPanel.style.display = 'none';
            
            // Pastikan warna butang kekal tema Pink SA Design untuk setiap kaedah
            btnSubmit.style.background = '#f0208d';
            
            // Menukar kandungan teks butang mengikut pilihan radio button pelanggan
            if (method === 'Wallet') {
                btnSubmit.innerHTML = '<i class="fa-solid fa-wallet"></i> Pay Using Wallet';
            } else if (method === 'ToyyibPay') {
                btnSubmit.innerHTML = '<i class="fa-solid fa-lock"></i> Proceed to ToyyibPay Payment';
            } else if (method === 'Cash on Collection') {
                cashPanel.style.display = 'block';
                btnSubmit.innerHTML = '<i class="fa-solid fa-store"></i> Confirm & Pay on Collection';
            }
        }
    </script>

</body>

</html>
