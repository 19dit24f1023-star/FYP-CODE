<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/customer_mail.php';

if (empty($_SESSION['is_admin'])) {
    header('Location: login.php?role=admin');
    exit;
}

$requestId = (int)($_GET['id'] ?? $_POST['request_id'] ?? 0);
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = trim($_POST['price'] ?? '');
    $status = trim($_POST['status'] ?? 'Pending');
    $allowedStatuses = ['Pending', 'Quoted', 'Processing', 'Completed', 'Cancelled'];

    if ($requestId <= 0 || $price === '' || !is_numeric($price) || (float)$price <= 0) {
        $message = 'Please enter a valid quotation price.';
        $messageType = 'error';
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $message = 'Invalid custom request status.';
        $messageType = 'error';
    } else {
        $requestStatement = $conn->prepare('SELECT fullname, email, product_name, price, status FROM custom_request WHERE id = ? LIMIT 1');
        if (!$requestStatement) {
            exit('Unable to find custom request.');
        }
        $requestStatement->bind_param('i', $requestId);
        $requestStatement->execute();
        $before = $requestStatement->get_result()->fetch_assoc();
        $requestStatement->close();
        $stmt = $conn->prepare('UPDATE custom_request SET price = ?, status = ? WHERE id = ?');
        if (!$stmt) {
            exit('Unable to prepare custom request update.');
        }
        $amount = (float)$price;
        $stmt->bind_param('dsi', $amount, $status, $requestId);
        if (!$stmt->execute()) {
            $stmt->close();
            exit('Unable to update custom request.');
        }
        $stmt->close();
        $message = 'Quotation updated. The customer can now make payment from My Profile.';
        if ($before && (($before['price'] === null || (float)$before['price'] !== $amount) || (string)($before['status'] ?? '') !== $status)) {
            $notification = 'Your custom request CR' . str_pad((string)$requestId, 3, '0', STR_PAD_LEFT)
                . ' has been updated.';
            if ($before['price'] === null || (float)$before['price'] !== $amount) {
                $notification .= ' New quotation price: RM ' . number_format($amount, 2) . '.';
            }
            $notification .= ' Current status: ' . $status . '.';
            sendCustomerEmail((string)$before['email'], (string)$before['fullname'], 'Custom request update', customerEmailTemplate('Custom request updated', $notification));
        }
    }
}

$requests = [];
$result = mysqli_query($conn, 'SELECT * FROM custom_request ORDER BY id DESC');
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
}

$selected = null;
foreach ($requests as $row) {
    if ((int)$row['id'] === $requestId) {
        $selected = $row;
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
    <title>Manage Custom Requests | SA Design</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#eef6ff;color:#1e293b}
        .wrap{width:min(1120px,calc(100% - 40px));margin:40px auto}.head{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
        h1{color:#102f91;margin:0}.back{color:#1747c7;text-decoration:none;font-weight:700}.notice{padding:13px 16px;border-radius:10px;margin-bottom:18px;background:#e9f9ef;color:#168453}.notice.error{background:#fff0f4;color:#c21d67}
        .layout{display:grid;grid-template-columns:1.4fr .8fr;gap:20px}.card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 10px 28px rgba(30,50,100,.08)}
        .request{display:block;padding:15px;border:1px solid #e1e7f2;border-radius:12px;text-decoration:none;color:inherit;margin-bottom:10px}.request.active{border-color:#f0208d;background:#fff5fa}
        .request strong{display:block;color:#102f91;margin-bottom:5px}.request small{color:#64748b}.price{color:#f0208d;font-weight:800;float:right}
        label{display:block;margin:15px 0 7px;font-size:12px;font-weight:800;color:#475569}input,select{width:100%;padding:12px;border:1px solid #d4ddec;border-radius:10px;font:inherit}button{width:100%;margin-top:20px;padding:12px;border:0;border-radius:10px;background:#f0208d;color:#fff;font-weight:800;cursor:pointer}
        @media(max-width:760px){.layout{grid-template-columns:1fr}.head{align-items:flex-start;gap:15px;flex-direction:column}}
    </style>
</head>
<body>
<main class="wrap">
    <div class="head"><h1><i class="fa-solid fa-wand-magic-sparkles"></i> Custom Requests</h1><a class="back" href="admin_dashboard.php">Back to Dashboard</a></div>
    <?php if ($message !== ''): ?><div class="notice <?php echo $messageType === 'error' ? 'error' : ''; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <div class="layout">
        <section class="card">
            <h2>Customer Requests</h2>
            <?php if (!$requests): ?><p>No custom requests found.</p><?php endif; ?>
            <?php foreach ($requests as $row): ?>
                <a class="request <?php echo (int)$row['id'] === $requestId ? 'active' : ''; ?>" href="manage_custom_requests.php?id=<?php echo (int)$row['id']; ?>">
                    <span class="price"><?php echo $row['price'] !== null ? 'RM '.number_format((float)$row['price'], 2) : 'No price'; ?></span>
                    <strong>CR<?php echo str_pad((string)$row['id'], 3, '0', STR_PAD_LEFT); ?> · <?php echo htmlspecialchars($row['product_name']); ?></strong>
                    <small><?php echo htmlspecialchars($row['fullname']); ?> · <?php echo htmlspecialchars($row['status'] ?? 'Pending'); ?></small>
                </a>
            <?php endforeach; ?>
        </section>
        <section class="card">
            <h2>Update Quotation</h2>
            <?php if ($selected): ?>
                <p><strong>CR<?php echo str_pad((string)$selected['id'], 3, '0', STR_PAD_LEFT); ?></strong><br><?php echo htmlspecialchars($selected['product_name']); ?><br><?php echo htmlspecialchars($selected['fullname']); ?></p>
                <form method="post">
                    <input type="hidden" name="request_id" value="<?php echo (int)$selected['id']; ?>">
                    <label for="price">Quotation Price (RM)</label>
                    <input id="price" name="price" type="number" min="0.01" step="0.01" value="<?php echo $selected['price'] !== null ? htmlspecialchars($selected['price']) : ''; ?>" required>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php foreach (['Pending', 'Quoted', 'Processing', 'Completed', 'Cancelled'] as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo (($selected['status'] ?? 'Pending') === $status) ? 'selected' : ''; ?>><?php echo $status; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit"><i class="fa-solid fa-save"></i> Save Quotation</button>
                </form>
            <?php else: ?><p>Select a request to update its quotation.</p><?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>
