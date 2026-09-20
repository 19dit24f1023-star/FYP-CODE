<?php
session_start();

// 1. Semak pengesahan log masuk
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['login_notice'] = 'Please sign in to use your wishlist.';
    header('Location: login.php');
    exit();
}

include 'db.php'; // 2. Sambungkan ke pangkalan data untuk dapatkan data produk

// 3. Dapatkan parameter dari URL
$action = $_GET['action'] ?? '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$redirect = $_GET['redirect'] ?? 'wishlist';

// 4. Pastikan array wishlist wujud
if (!isset($_SESSION['wishlist']) || !is_array($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

// 5. Proses Tambah / Buang (Toggle & Remove)
if ($product_id > 0) {
    if ($action === 'toggle') {
        $key = array_search($product_id, $_SESSION['wishlist'], true);
        
        if ($key !== false) {
            // Jika sudah ada, buang dari wishlist
            unset($_SESSION['wishlist'][$key]);
            $_SESSION['wishlist'] = array_values($_SESSION['wishlist']); // Reset indeks array
        } else {
            // Jika tiada, masukkan ke wishlist
            $_SESSION['wishlist'][] = $product_id;
        }

        // Lencongkan semula mengikut lokasi asal
        if ($redirect === 'product') {
            header("Location: product.php?id=" . $product_id);
            exit();
        } else {
            header("Location: wishlist.php");
            exit();
        }
    }

    if ($action === 'remove') {
        $_SESSION['wishlist'] = array_values(array_diff($_SESSION['wishlist'], [$product_id]));
        header('Location: wishlist.php');
        exit();
    }
}

// 6. Ambil data produk sebenar dari pangkalan data berdasarkan ID dalam $_SESSION['wishlist']
$savedProducts = [];
if (!empty($_SESSION['wishlist']) && isset($conn) && $conn instanceof mysqli) {
    // Bina placeholder '?' mengikut bilangan item dalam wishlist
    $placeholders = implode(',', array_fill(0, count($_SESSION['wishlist']), '?'));
    $types = str_repeat('i', count($_SESSION['wishlist']));

    $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$_SESSION['wishlist']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $savedProducts[$row['id']] = $row;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist | SA Design</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="ui_polish.css">
    <style>
        body { background:#f6e8f2; color:#172033; font-family: system-ui, -apple-system, sans-serif; }
        .wishlist-page { width:min(1120px, calc(100% - 48px)); margin:48px auto 70px; }
        .wishlist-heading { display:flex; justify-content:space-between; align-items:flex-end; gap:20px; margin-bottom:24px; }
        .wishlist-heading h1 { color:#102f91; font-size:clamp(28px,4vw,42px); letter-spacing:-1.5px; margin:0; }
        .wishlist-heading p { margin:7px 0 0; color:#64748b; font-size:13px; }
        .wishlist-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
        .wishlist-card { overflow:hidden; background:#fff; border:1px solid #e2e7f1; border-radius:18px; box-shadow:0 12px 30px rgba(28,53,118,.07); }
        .wishlist-card img { width:100%; height:190px; object-fit:contain; padding:18px; background:#edf3ff; box-sizing:border-box; }
        .wishlist-card-body { padding:17px; }
        .wishlist-card h2 { font-size:15px; color:#172033; margin:0; }
        .wishlist-card p { margin:8px 0 15px; color:#f0208d; font-size:13px; font-weight:800; }
        .wishlist-actions { display:flex; gap:8px; }
        .wishlist-actions a { flex:1; padding:10px; border-radius:10px; text-align:center; font-size:11px; font-weight:800; text-decoration:none; }
        .wishlist-view { background:#edf3ff; color:#1747c7; }
        .wishlist-remove { background:#fff0f7; color:#d51b79; }
        .wishlist-empty { padding:70px 20px; text-align:center; background:#fff; border:1px solid #e2e7f1; border-radius:20px; color:#64748b; }
        .wishlist-empty i { display:block; margin-bottom:14px; color:#f0208d; font-size:40px; }
        .back-btn { text-decoration:none; color:#102f91; font-weight:bold; font-size:14px; }
        @media(max-width:760px) { 
            .wishlist-page { width:calc(100% - 32px); margin-top:30px; }
            .wishlist-heading { align-items:flex-start; flex-direction:column; }
            .wishlist-grid { grid-template-columns:1fr; } 
        }
    </style>
</head>
<body>
    <main class="wishlist-page">
        <div class="wishlist-heading">
            <div>
                <p class="eyebrow" style="margin:0; font-size:12px; font-weight:bold; color:#f0208d;">CUSTOMER AREA</p>
                <h1>My Wishlist</h1>
                <p>Save products you want to order later.</p>
            </div>
            <a class="back-btn" href="index.php">← Continue Shopping</a>
        </div>
        
        <?php if (!empty($savedProducts)): ?>
            <div class="wishlist-grid">
                <?php foreach ($savedProducts as $savedId => $item): ?>
                    <article class="wishlist-card">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        <div class="wishlist-card-body">
                            <h2><?= htmlspecialchars($item['name']) ?></h2>
                            <p><?= htmlspecialchars($item['price']) ?></p>
                            <div class="wishlist-actions">
                                <a class="wishlist-view" href="product.php?id=<?= $savedId ?>">View Product</a>
                                <a class="wishlist-remove" href="wishlist.php?action=remove&id=<?= $savedId ?>">Remove</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="wishlist-empty">
                <i class="fa-regular fa-heart"></i>
                <strong>Your wishlist is empty</strong>
                <p>Add products here to find them quickly later.</p>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>