<?php
session_start();

$total_cart_count = 0;
foreach (($_SESSION['cart'] ?? []) as $item) {
    $total_cart_count += (int)($item['quantity'] ?? 1);
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
include 'db.php';

$review_message = '';
$review_success = false;
$review_image = null;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// ==========================================
// GET PRODUCT DATA FROM DATABASE
// ==========================================
$product_stmt = $conn->prepare("
    SELECT *
    FROM products
    WHERE id = ?
");

if (!$product_stmt) {
    die("Database error: " . $conn->error);
}

$product_stmt->bind_param("i", $id);
$product_stmt->execute();

$product_result = $product_stmt->get_result();
$product_data = $product_result->fetch_assoc();

$product_stmt->close();

if (!$product_data) {
    die("Product not found.");
}

// ==========================================
// CONVERT JSON DATA
// ==========================================
$product = [
    'id'          => $product_data['id'] ?? $id,
    'name'        => $product_data['name'] ?? '',
    'category'    => $product_data['category'] ?? '',
    'badge'       => $product_data['badge'] ?? '',
    'price'       => $product_data['price'] ?? '',
    'description' => $product_data['description'] ?? '',

    'features' => json_decode(
        $product_data['features'] ?? '[]',
        true
    ) ?: [],

    'specifications' => json_decode(
        $product_data['specifications'] ?? '{}',
        true
    ) ?: [],

    'image' => !empty($product_data['image'])
        ? $product_data['image']
        : 'product_images/placeholder.png',

    'gallery' => json_decode(
        $product_data['gallery'] ?? '[]',
        true
    ) ?: [],

    // SATU COLUMN OPTIONS
    'options' => json_decode(
        $product_data['options'] ?? '[]',
        true
    ) ?: [],

    'minimum_order' => $product_data['minimum_order'] ?? '',
    'status'        => $product_data['status'] ?? ''
];

// ==========================================
// FALLBACK GALLERY
// ==========================================
if (empty($product['gallery'])) {
    $product['gallery'] = [$product['image']];
}

$gallery_images = $product['gallery'];

$is_wishlisted =
    isset($_SESSION['wishlist']) &&
    in_array($id, $_SESSION['wishlist'], true);

// --- 2. PROSES SUBMIT REVIEW ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['review_action'] ?? '') === 'submit_review') {
    $review_product_id = (int)($_POST['product_id'] ?? 0);
    $review_rating = (int)($_POST['rating'] ?? 0);
    $review_text = trim($_POST['review_text'] ?? '');
    $customer_id = (int)($_SESSION['user_id'] ?? 0);

    if ($customer_id <= 0) {
        $review_message = 'Please login before submitting a review.';
    } elseif ($review_product_id !== $id) {
        $review_message = 'Invalid product.';
    } elseif ($review_rating < 1 || $review_rating > 5) {
        $review_message = 'Please select a rating from 1 to 5 stars.';
    } elseif ($review_text === '') {
        $review_message = 'Please write a short review.';
    } elseif (mb_strlen($review_text) > 500) {
        $review_message = 'Review must not exceed 500 characters.';
    } else {
        if (isset($_FILES['review_image']) && $_FILES['review_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['review_image']['error'] !== UPLOAD_ERR_OK || $_FILES['review_image']['size'] > 5 * 1024 * 1024) {
                $review_message = 'Review image must be a valid file under 5MB.';
            } else {
                $review_extension = strtolower(pathinfo($_FILES['review_image']['name'], PATHINFO_EXTENSION));
                if (!in_array($review_extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $review_message = 'Review image must be JPG, PNG or WEBP.';
                } else {
                    $review_directory = __DIR__ . '/uploads/reviews/';
                    if (!is_dir($review_directory) && !mkdir($review_directory, 0755, true)) {
                        $review_message = 'Unable to prepare review image storage.';
                    } else {
                        $review_image = 'review_' . $customer_id . '_' . bin2hex(random_bytes(6)) . '.' . $review_extension;
                        if (!move_uploaded_file($_FILES['review_image']['tmp_name'], $review_directory . $review_image)) {
                            $review_message = 'Unable to save the review image.';
                            $review_image = null;
                        }
                    }
                }
            }
        }
    }

    if ($review_message === '') {
        $stmt = $conn->prepare("INSERT INTO product_reviews (product_id, customer_id, rating, review_text, review_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $review_product_id, $customer_id, $review_rating, $review_text, $review_image);

        if ($stmt->execute()) {
            $review_success = true;
            $review_message = 'Thank you! Your review has been submitted.';
        } else {
            $review_message = 'Unable to submit your review. Please try again.';
        }
        $stmt->close();
    }
}

// --- 3. AMBIL RATING & REVIEW DARI DATABASE ---
$average_rating = 0;
$total_reviews = 0;
$rating_breakdown = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$reviews = [];

if (isset($conn) && $conn instanceof mysqli) {
    $rating_stmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS total_reviews FROM product_reviews WHERE product_id = ?");
    $rating_stmt->bind_param("i", $id);
    $rating_stmt->execute();
    $rating_result = $rating_stmt->get_result()->fetch_assoc();
    $average_rating = (float)($rating_result['avg_rating'] ?? 0);
    $total_reviews = (int)($rating_result['total_reviews'] ?? 0);
    $rating_stmt->close();

    $breakdown_stmt = $conn->prepare("SELECT rating, COUNT(*) AS rating_count FROM product_reviews WHERE product_id = ? GROUP BY rating");
    $breakdown_stmt->bind_param("i", $id);
    $breakdown_stmt->execute();
    $breakdown_result = $breakdown_stmt->get_result();
    while ($breakdown = $breakdown_result->fetch_assoc()) {
        $r = (int)$breakdown['rating'];
        if ($r >= 1 && $r <= 5) {
            $rating_breakdown[$r] = (int)$breakdown['rating_count'];
        }
    }
    $breakdown_stmt->close();

    $review_stmt = $conn->prepare("
        SELECT pr.rating, pr.review_text, pr.review_image, pr.created_at, c.name
        FROM product_reviews pr
        INNER JOIN customers c ON c.id = pr.customer_id
        WHERE pr.product_id = ?
        ORDER BY pr.created_at DESC
    ");
    $review_stmt->bind_param("i", $id);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    while ($row = $review_result->fetch_assoc()) {
        $reviews[] = $row;
    }
    $review_stmt->close();
}
// ==========================================
// ASINGKAN OPTIONS KEPADA TYPE & SIZE
// ==========================================
$type_options = [];
$size_options = [];

if (!empty($product['options'])) {
    foreach ($product['options'] as $opt) {
        $label = is_array($opt) ? ($opt['label'] ?? $opt['name'] ?? $opt['value'] ?? '') : (string)$opt;
        $price = is_array($opt) ? ($opt['price'] ?? $opt['option_price'] ?? $product['price']) : $product['price'];

        // Jika jenis
        if (strpos($label, 'type_options - ') === 0) {
            $clean_label = str_replace('type_options - ', '', $label);
            $type_options[] = [
                'label' => $clean_label,
                'price' => (float)$price
            ];
        } 
        // Jika saiz
        elseif (strpos($label, 'size_options - ') === 0) {
            $clean_label = str_replace('size_options - ', '', $label);
            $size_options[] = [
                'label' => $clean_label,
                'price' => (float)$price
            ];
        } 
        // Pilihan am (fallback)
        else {
            $type_options[] = [
                'label' => $label,
                'price' => (float)$price
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($product['name']); ?> - SA Design</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <style>
    :root {
      --primary-color: #1557d6;
      --primary-gradient: linear-gradient(135deg, #f0208d 0%, #1557d6 100%);
      --accent-color: #f0208d;
      --whatsapp-color: #25d366;
      --whatsapp-gradient: linear-gradient(135deg, #25d366 0%, #12b04c 100%);
      --text-dark: #0f172a;
      --text-muted: #64748b;
      --bg-light: #f8fafc;
      --border-color: #e2e8f0;
      --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
      --shadow-md: 0 10px 30px -5px rgba(0,0,0,0.08);
      --radius-lg: 18px;
      --radius-md: 12px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; }
    body { background: #f7f8fb; color: var(--text-dark); padding: 0; line-height: 1.6; }

    .app-container {
      width: 100%; max-width: none; min-height: 100vh; margin: 0 auto; background: #ffffff;
      border-radius: 0; overflow: hidden; box-shadow: none; border: none;
    }

    header {
      display: flex; justify-content: space-between; align-items: center;
      padding: 18px clamp(24px, 5vw, 72px); border-bottom: 1px solid #edf1f7; background: #fff;
    }
    .brand { display: flex; flex-direction: column; color: var(--text-dark); text-decoration: none; line-height: 1; }
    .brand-name { font-size: 28px; font-weight: 900; letter-spacing: -2px; white-space: nowrap; }
    .brand-name .sa { color: #f0208d; }
    .brand-name .design { color: #1557d6; }
    .brand-tagline { margin-top: 5px; color: #172033; font-size: 9px; font-weight: 800; letter-spacing: .7px; }
    .nav-links { display: flex; align-items: center; gap: clamp(16px, 2.4vw, 34px); list-style: none; }
    .nav-links a { color: #172033; text-decoration: none; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .02em; transition: color .2s ease; }
    .nav-links a:hover, .nav-links a.active { color: #f0208d; }
    .nav-actions { display: flex; align-items: center; gap: 10px; }
    .login-pill, .cart-link { display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 800; transition: transform .2s ease, box-shadow .2s ease; }
    .login-pill { gap: 7px; padding: 10px 18px; border-radius: 999px; color: #fff; background: linear-gradient(135deg, #f0208d, #ff3b86); font-size: 12px; text-transform: uppercase; box-shadow: 0 8px 17px rgba(240, 32, 141, .18); }
    .cart-link { width: 40px; height: 40px; color: #f0208d; background: #fff; border: 1px solid #f0208d; border-radius: 50%; }
    .login-pill:hover, .cart-link:hover { transform: translateY(-2px); box-shadow: 0 9px 17px rgba(21, 87, 214, .18); }
    .logout-pill { border: 0; cursor: pointer; font-family: 'Plus Jakarta Sans', sans-serif; }
    .logout-pill:focus { outline: none; }
    .logout-popup { border-radius: 18px !important; }
    .logout-title { color: #102f91 !important; font-weight: 800 !important; }
    .logout-text { color: #64748b !important; font-size: 13px !important; }
    .logout-confirm, .logout-cancel { border-radius: 10px !important; padding: 10px 18px !important; font-weight: 800 !important; }

    .product-detail-section { width: min(1200px, calc(100% - 80px)); margin: 0 auto; padding: 28px 0 64px; }
    .product-hero {
      position: relative; overflow: hidden; padding: 24px 22px 18px; margin-bottom: 26px;
      background: linear-gradient(120deg, #cbd8ff 0%, #d9b9eb 52%, #ffc0dc 100%);
      border: 1px solid #dbe5ff; border-radius: 26px; box-shadow: 0 14px 26px rgba(27, 70, 155, 0.06);
    }
    .product-hero::before, .product-hero::after { content: ""; position: absolute; border-radius: 50%; pointer-events: none; }
    .product-hero::before { width: 220px; height: 220px; right: -70px; top: -120px; background: rgba(40,84,197,.18); }
    .product-hero::after { width: 180px; height: 180px; left: -60px; bottom: -110px; background: rgba(239,58,155,.14); }
    .section-badge {
      position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px;
      border-radius: 999px; background: rgba(255,255,255,.9); border: 1px solid rgba(171,193,255,.8);
      color: #1747c7; font-size: 10px; font-weight: 800; letter-spacing: .7px; text-transform: uppercase;
    }
    .product-hero h2 { position: relative; z-index: 1; margin-top: 14px; color: #102f91; font-size: clamp(28px, 4vw, 42px); line-height: 1.08; letter-spacing: -1.5px; font-weight: 900; }
    .product-hero p { position: relative; z-index: 1; margin-top: 8px; max-width: 760px; color: #586576; font-size: 13px; line-height: 1.7; }
    .back-btn { 
      background: #f5f7fb; border: 1px solid #e5ebf5; color: #586576; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none;
      display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 24px; margin-bottom: 26px; transition: all 0.2s ease;
    }
    .back-btn:hover { background: #ebf0f8; color: var(--text-dark); transform: translateX(-3px); }

    .detail-grid { display: grid; grid-template-columns: 1.08fr 1fr; gap: 54px; align-items: start; }
    .gallery-container { display: flex; flex-direction: column; gap: 16px; position: relative; top: 0; }
    .detail-gallery {
      position: relative; background: #edf3ff; border: 1px solid #dfeafe; border-radius: 18px; height: 520px;
      display: flex; align-items: center; justify-content: center; padding: 24px; overflow: hidden; box-shadow: 0 6px 18px rgba(17, 24, 39, 0.04);
    }
    .detail-gallery img { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); filter: drop-shadow(0 8px 16px rgba(0,0,0,0.06)); }
    .detail-gallery:hover img { transform: scale(1.03); }

    .image-counter { 
      position: absolute; top: 16px; left: 16px; background: rgba(255, 255, 255, 0.85); padding: 6px 14px; 
      border-radius: 30px; font-size: 12px; font-weight: 700; color: var(--text-dark); backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,0.6); box-shadow: var(--shadow-sm);
    }

    .nav-arrow {
      position: absolute; top: 50%; transform: translateY(-50%); background: #ffffff; border: 1px solid var(--border-color);
      width: 44px; height: 44px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 14px rgba(0,0,0,0.08); font-weight: bold; font-size: 16px; color: var(--text-dark); transition: all 0.2s;
    }
    .nav-arrow:hover { background: var(--primary-color); color: #fff; border-color: var(--primary-color); transform: translateY(-50%) scale(1.08); }
    .nav-arrow.left { left: 16px; }
    .nav-arrow.right { right: 16px; }

    .thumbnails-list { display: flex; gap: 12px; overflow-x: auto; padding: 4px 0; }
    .thumb-item {
      width: 76px; height: 76px; border-radius: var(--radius-md); border: 2px solid var(--border-color);
      overflow: hidden; cursor: pointer; flex-shrink: 0; background: #fff; padding: 6px; transition: all 0.2s;
    }
    .thumb-item img { width: 100%; height: 100%; object-fit: contain; }
    .thumb-item:hover { border-color: #a5b4fc; }
    .thumb-item.active { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(21, 87, 214, 0.18); }

    .detail-info .badge-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .detail-info .badge { 
      font-size: 11px; font-weight: 800; color: var(--primary-color); letter-spacing: 1px; text-transform: uppercase; 
      background: rgba(21, 87, 214, 0.08); padding: 6px 12px; border-radius: 20px; 
    }
    .stock-tag { font-size: 12px; font-weight: 700; color: #10b981; display: flex; align-items: center; gap: 6px; background: #ecfdf5; padding: 4px 12px; border-radius: 20px; }
    .stock-tag::before { content: ""; display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 50%; }

    .detail-info h1 { font-size: clamp(32px, 3vw, 54px); font-weight: 800; color: var(--text-dark); margin-bottom: 12px; line-height: 1.1; letter-spacing: -0.05em; }
    
    .price-card {
      background: linear-gradient(180deg, #f1f6ff 0%, #eef3ff 100%); padding: 18px 20px; border-radius: 14px;
      border: 1px solid #dfeaff; margin-bottom: 20px; display: flex; align-items: baseline; gap: 10px;
    }
    .price-card .price-tag { font-size: clamp(28px, 2vw, 40px); color: #f0208d; font-weight: 800; letter-spacing: -0.04em; }
    .price-card .price-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }

    .detail-info .desc { font-size: 14px; color: var(--text-muted); line-height: 1.7; margin-bottom: 24px; }

    .features-box { background: #fbfcff; border-radius: 14px; padding: 18px 20px; margin-bottom: 25px; border: 1px solid #e4eaf7; }
    .features-box h4 { font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--text-dark); margin-bottom: 12px; letter-spacing: 0.8px; }
    .features-box ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
    .features-box li { font-size: 13px; color: #334155; display: flex; align-items: flex-start; gap: 10px; font-weight: 500; }
    .features-box li::before { 
      content: "✓"; color: #fff; background: var(--primary-color); width: 18px; height: 18px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; flex-shrink: 0; margin-top: 2px;
    }

    .input-group { margin-bottom: 22px; }
    .input-group label { display: block; font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }

    .custom-select, .custom-textarea {
      width: 100%; padding: 14px 16px; border-radius: var(--radius-md); border: 1.5px solid var(--border-color);
      background: #fff; font-size: 14px; color: var(--text-dark); outline: none; transition: all 0.2s; font-weight: 500;
    }
    .custom-select:focus, .custom-textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 4px rgba(21, 87, 214, 0.08); }
    .custom-textarea { resize: vertical; min-height: 100px; line-height: 1.5; }
    .field-help { display:flex; justify-content:space-between; gap:12px; margin-top:7px; color:var(--text-muted); font-size:11px; line-height:1.45; }
    .field-help .char-count { flex:0 0 auto; font-weight:700; white-space:nowrap; }

    .promo-info-card {
      background: #f0f9ff; border: 1.5px dashed #0284c7; border-radius: var(--radius-md); padding: 16px 20px;
      color: #0369a1; font-size: 13px; line-height: 1.7; margin-bottom: 22px;
    }
    .promo-info-card strong { color: #0c4a6e; }

    .quantity-control { display: inline-flex; align-items: center; border: 1.5px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; background: #fff; }
    .quantity-control button { background: #fff; border: none; width: 44px; height: 44px; cursor: pointer; font-size: 18px; font-weight: 700; color: var(--text-dark); transition: 0.2s; }
    .quantity-control button:hover { background: #f1f5f9; color: var(--primary-color); }
    .quantity-control span { width: 50px; text-align: center; font-size: 16px; font-weight: 800; color: var(--text-dark); }

    .btn-group { display: flex; gap: 14px; margin-top: 30px; margin-bottom: 30px; }
    .btn-add-cart {
      flex: 1; background: linear-gradient(135deg, rgba(21, 87, 214, 0.08), rgba(240, 32, 141, 0.08)); color: #1557d6; border: 1px solid rgba(21, 87, 214, 0.25);
      padding: 16px; border-radius: var(--radius-md); font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.2s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-add-cart:hover { background: linear-gradient(135deg, rgba(21, 87, 214, 0.12), rgba(240, 32, 141, 0.12)); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(21, 87, 214, .12); }

    .btn-buy-now {
      flex: 1.2; background: linear-gradient(135deg, #f0208d, #ff3b86); color: #fff; border: none;
      padding: 16px; border-radius: var(--radius-md); font-size: 15px; font-weight: 800; cursor: pointer; transition: all 0.2s;
      display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 8px 20px rgba(240,32,141,0.28);
    }
    .btn-buy-now:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(240,32,141,0.38); }

    .accordion-container { border-top: 1px solid var(--border-color); margin-top: 25px; }
    .accordion-item { border-bottom: 1px solid var(--border-color); }
    .accordion-header {
      width: 100%; padding: 18px 0; background: none; border: none; text-align: left;
      font-size: 14px; font-weight: 700; color: var(--text-dark); display: flex; justify-content: space-between;
      align-items: center; cursor: pointer; transition: color 0.2s;
    }
    .accordion-header:hover { color: var(--primary-color); }
    .accordion-content { display: none; padding-bottom: 18px; font-size: 13px; color: var(--text-muted); line-height: 1.7; }
    .accordion-content table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .accordion-content td { padding: 8px 0; border-bottom: 1px dashed var(--border-color); }
    .accordion-content td:first-child { font-weight: 700; color: var(--text-dark); width: 45%; }

    .reviews-section { width: min(1440px, 100%); margin: 0 auto; padding: 0 clamp(24px, 5vw, 72px) 70px; }
    .reviews-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 28px; box-shadow: 0 10px 30px rgba(15, 23, 42, .05); }
    .reviews-title-row { display:flex; justify-content:space-between; align-items:center; gap:20px; margin-bottom:24px; flex-wrap:wrap; }
    .reviews-title-row h2 { color:#0f172a; font-size:22px; font-weight:800; }
    .rating-summary { display:flex; align-items:center; gap:10px; background:#fff8fc; border:1px solid #f7d5e7; padding:10px 15px; border-radius:14px; }
    .rating-number {font-size:24px;font-weight:900;color:#e63891}
    .rating-score{min-width:105px;text-align:center}
    .rating-breakdown{min-width:210px}
    .rating-row{display:grid;grid-template-columns:22px 1fr 20px;align-items:center;gap:7px;font-size:10px;color:#64748b;margin:3px 0}
    .rating-bar{height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden}
    .rating-bar span{display:block;height:100%;background:#f59e0b;border-radius:99px}
    .rating-row small{text-align:right;color:#94a3b8}

    .stars-display {color:#f59e0b;letter-spacing:2px;font-size:16px}
    .review-count {font-size:11px;color:#64748b;font-weight:700}
    .review-form { background:#f8faff; border:1px solid #e2e8f0; border-radius:16px; padding:20px; margin-bottom:25px; }
    .review-form h3 {font-size:14px;color:#142a83;margin-bottom:14px}
    .security-note{display:flex;align-items:center;gap:7px;margin:-4px 0 13px;color:#64748b;font-size:10px}
    .security-note i{color:#10b981}

    .star-rating { display:flex; flex-direction:row-reverse; justify-content:flex-end; gap:4px; margin-bottom:14px; }
    .star-rating input {display:none}
    .star-rating label { color:#cbd5e1; font-size:28px; cursor:pointer; transition:.15s; }
    .star-rating label:hover, .star-rating label:hover ~ label, .star-rating input:checked ~ label {color:#f59e0b}
    .review-textarea { width:100%; min-height:95px; resize:vertical; border:1px solid #dce2ef; border-radius:12px; padding:12px; font:inherit; font-size:12px; outline:none; background:#fff; }
    .review-textarea:focus { border-color:#1557d6; box-shadow:0 0 0 3px rgba(21,87,214,.08); }
    .review-submit { margin-top:12px; border:0; border-radius:10px; padding:11px 18px; background:linear-gradient(135deg,#f0208d,#1557d6); color:#fff; font-size:12px; font-weight:800; cursor:pointer; }
    .review-submit:hover {transform:translateY(-1px)}
    .review-login { color:#64748b; font-size:12px; line-height:1.6; }
    .review-login a {color:#ef3a9b;font-weight:800;text-decoration:none}
    .review-alert { padding:10px 12px; border-radius:10px; margin-bottom:14px; font-size:11px; font-weight:700; }
    .review-alert.success {background:#ecfdf5;color:#047857;border:1px solid #bbf7d0}
    .review-alert.error {background:#fff1f2;color:#be123c;border:1px solid #fecdd3}
    .review-list {display:grid;gap:12px}
    .review-item { border-bottom:1px solid #e2e8f0; padding:16px 0; }
    .review-item:last-child {border-bottom:0}
    .review-head { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:6px; }
    .reviewer-name {font-size:12px;font-weight:800;color:#172033}
    .review-date {font-size:10px;color:#94a3b8}
    .review-stars {color:#f59e0b;font-size:13px;letter-spacing:1px}
    .review-comment {font-size:12px;color:#475569;line-height:1.7;margin-top:6px}
    .no-reviews { text-align:center; padding:25px; color:#94a3b8; font-size:12px; }

    .minimum-order-note { display:flex; align-items:center; gap:7px; flex-wrap:wrap; margin-top:9px; padding:9px 12px; border-radius:10px; background:#fff8e8; border:1px solid #fde3a7; color:#9a6700; font-size:10px; line-height:1.45; }
    .minimum-order-note i { color:#f59e0b; font-size:11px; }
    .minimum-order-note strong { font-weight:800; }
    .minimum-order-note span { width:100%; padding-left:18px; color:#856f3c; }

    @media (max-width: 900px) {
      header { flex-wrap: wrap; gap: 16px; }
      .nav-links { order: 3; width: 100%; justify-content: center; flex-wrap: wrap; }
      .detail-grid { grid-template-columns: 1fr; gap: 35px; }
      .product-detail-section { padding: 24px 20px; }
      .gallery-container { position: relative; top: 0; }
      .detail-gallery { height: 360px; }
      header { padding: 16px 20px; }
      .btn-group { flex-direction: column; }
    }

    @media (max-width: 520px) {
      .brand-name { font-size: 25px; }
      .login-pill span { display: none; }
      .login-pill { width: 40px; height: 40px; padding: 0; border-radius: 50%; }
      .nav-links { gap: 14px; }
      .nav-links a { font-size: 11px; }
    }

    .navbar{height:84px;padding:16px 48px;display:flex;align-items:center;justify-content:space-between;gap:25px;background:#fff;border-bottom:1px solid #e8ebf2;position:relative;z-index:20}
    .nav-logo{display:flex;align-items:center;min-width:max-content;text-decoration:none}.logo-text{display:flex;flex-direction:column;gap:2px}.logo-title{font-size:36px;font-weight:900;line-height:.9;letter-spacing:-2.5px;white-space:nowrap}.logo-sa{color:#f0208d}.logo-design{color:#1557d6}.logo-subtitle{color:#111827;font-size:12px;font-weight:900;letter-spacing:.7px;text-transform:uppercase}
    .navbar .nav-links{display:flex;align-items:center;justify-content:center;gap:32px;list-style:none}.navbar .nav-links a{color:#111827;font-size:13px;font-weight:800;text-transform:uppercase;transition:.2s}.navbar .nav-links a:hover,.navbar .nav-links a.active{color:#f0208d}
    .nav-icons{display:flex;align-items:center;gap:10px}.navbar .login-pill{display:inline-flex;align-items:center;gap:7px;padding:10px 15px;border-radius:12px;background:linear-gradient(135deg,#f0208d,#ff3b86);color:#fff;font-size:12px;font-weight:800;text-transform:uppercase;box-shadow:0 8px 18px rgba(239,58,155,.2);transition:.2s}.navbar .login-pill:hover{transform:translateY(-2px)}.navbar .icon-btn{position:relative;width:42px;height:42px;border:1px solid #f0208d;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#fff;color:#f0208d;transition:.2s}.navbar .icon-btn:hover{transform:translateY(-2px);background:#ffe4f2}.cart-badge{position:absolute;top:-4px;right:-4px;width:19px;height:19px;border:2px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#f0208d;color:#fff;font-size:9px;font-weight:900}.menu-toggle{display:none;width:42px;height:42px;border:1px solid #dfe4ef;border-radius:12px;background:#fff;color:#172033;cursor:pointer}
    @media(max-width:760px){.navbar{height:auto;min-height:76px;padding:15px 20px;flex-wrap:wrap}.logo-title{font-size:29px}.logo-subtitle{font-size:9px}.menu-toggle{display:flex;align-items:center;justify-content:center}.nav-icons{margin-left:auto}.navbar .nav-links{display:none;order:4;width:100%;padding:12px 0 3px;flex-direction:column;align-items:center;gap:14px;border-top:1px solid #eef1f6}.navbar.menu-open .nav-links{display:flex}.navbar .login-pill span{display:none}.navbar .login-pill{width:40px;height:40px;padding:0;justify-content:center}.navbar .icon-btn{width:40px;height:40px}}
  </style>
  <link rel="stylesheet" href="ui_polish.css">
</head>
<body>

<div class="app-container">
  <nav class="navbar">
    <a class="nav-logo" href="index.php" title="Back to Home"><div class="logo-text"><span class="logo-title"><span class="logo-sa">SA</span> <span class="logo-design">DESIGN</span></span><span class="logo-subtitle">PRINTING &amp; ADVERTISING</span></div></a>
    <ul class="nav-links" id="mainNav">
      <li><a href="index.php">Home</a></li>
      <li><a href="index.php#products-section" class="active">Product</a></li>
      <li><a href="about.php">About Us</a></li>
      <li><a href="custom_request.php">Custom Request</a></li>
    </ul>
    <div class="nav-icons">
      <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
        <a href="cust_profile.php" class="login-pill" title="My profile"><i class="fa-regular fa-user"></i><span>Profile</span></a>
        <button type="button" class="login-pill logout-pill" onclick="confirmLogout()" title="Log out"><i class="fa-solid fa-right-from-bracket"></i><span>Log out</span></button>
      <?php else: ?>
        <a class="login-pill" href="login.php"><i class="fa-regular fa-user"></i><span>Login</span></a>
      <?php endif; ?>
      <a class="icon-btn" href="cart.php" id="cartBtn" aria-label="Shopping cart"><i class="fa-solid fa-bag-shopping"></i><span class="cart-badge"><?= $total_cart_count ?></span></a>
      <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
        <a class="icon-btn wishlist-nav-btn" href="wishlist.php" aria-label="My wishlist"><i class="fa-<?= !empty($_SESSION['wishlist']) ? 'solid' : 'regular' ?> fa-heart"></i></a>
      <?php endif; ?>
    </div>
    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
  </nav>

  <section class="product-detail-section">
    <div class="product-hero">
      <span class="section-badge">Custom Print Solutions</span>
      <h2><?php echo htmlspecialchars($product['name']); ?></h2>
      <p>Explore a premium print-ready product with a clean, fast and professional ordering flow.</p>
    </div>
    <a href="index.php" class="back-btn">← Back to Products</a>

    <div class="detail-grid">
      <!-- Left Column: Gallery Display -->
      <div class="gallery-container">
        <div class="detail-gallery">
          <span class="image-counter" id="imgCounter">01 / <?php echo str_pad(count($gallery_images), 2, '0', STR_PAD_LEFT); ?></span>
          <button class="nav-arrow left" onclick="prevImage()">❮</button>
          <img id="detailImg" src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://via.placeholder.com/500x500/f8fafc/6366f1?text=<?php echo rawurlencode($product['name']); ?>'">
          <button class="nav-arrow right" onclick="nextImage()">❯</button>
        </div>

        <!-- Thumbnails Selector -->
        <div class="thumbnails-list">
          <?php foreach ($gallery_images as $idx => $gImg): ?>
            <div class="thumb-item <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="selectImage(<?php echo $idx; ?>)">
              <img src="<?php echo htmlspecialchars($gImg); ?>" alt="Thumbnail" onerror="this.src='https://via.placeholder.com/100x100/f8fafc/6366f1?text=Preview'">
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Right Column: Details & Ordering Form -->
      <div class="detail-info">
        <div class="badge-row">
          <span class="badge"><?php echo htmlspecialchars($product['badge']); ?></span>
          <span class="stock-tag">In Stock</span>
          <a class="wishlist-product-btn <?= $is_wishlisted ? 'active' : '' ?>" href="wishlist.php?action=toggle&id=<?= $id ?>&redirect=product" aria-label="Toggle wishlist">
            <i class="fa-<?= $is_wishlisted ? 'solid' : 'regular' ?> fa-heart"></i>
            <?= $is_wishlisted ? 'Saved' : 'Save' ?>
          </a>
        </div>
        
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        
        <div class="price-card">
          <span class="price-label">Price:</span>
          <span class="price-tag" id="displayPrice"><?php echo htmlspecialchars($product['price']); ?></span>
        </div>

        <p class="desc"><?php echo htmlspecialchars($product['description']); ?></p>
        
        <?php if (!empty($product['features'])) : ?>
        <div class="features-box">
          <h4>Key Features</h4>
          <ul>
            <?php foreach ($product['features'] as $ft): ?>
              <li><?php echo htmlspecialchars($ft); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <!-- Dropdown Jenis Baju -->
<?php if (!empty($type_options)): ?>
<div class="input-group">
    <label>Select Type:</label>
    <select id="typeOption" class="custom-select" onchange="updateTotalPrice()">
        <?php foreach ($type_options as $type): ?>
            <option value="<?= htmlspecialchars($type['label'], ENT_QUOTES, 'UTF-8') ?>" 
                    data-price="<?= htmlspecialchars(number_format($type['price'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($type['label'], ENT_QUOTES, 'UTF-8') ?> - RM <?= number_format($type['price'], 2) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<?php endif; ?>

<!-- Dropdown Saiz Baju -->
<?php if (!empty($size_options)): ?>
<div class="input-group">
    <label>Select Size:</label>
    <select id="sizeOption" class="custom-select" onchange="updateTotalPrice()">
        <?php foreach ($size_options as $size): ?>
            <option value="<?= htmlspecialchars($size['label'], ENT_QUOTES, 'UTF-8') ?>"
                    data-price="<?= htmlspecialchars(number_format($size['price'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($size['label'], ENT_QUOTES, 'UTF-8') ?> 
                <?= $size['price'] > 0 ? '- RM ' . number_format($size['price'], 2) : '' ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<?php endif; ?>

        <?php if ($id === 2 || $id === 3 || $id === 4 || $id === 6) : ?>
        <div class="input-group">
          <label>Additional Notes / Remarks:</label>
          <textarea id="requestNotes" class="custom-textarea" placeholder="e.g. Color preferences, specific logo placements, special finishing requests, etc."></textarea>
        </div>
        <?php endif; ?>

        <div class="input-group">
          <label>Quantity Unit(s):</label>
          <div class="quantity-control">
            <button type="button" onclick="updateQty(-1)">-</button>
            <span id="qtyVal"><?= $id === 2 ? 15 : 1 ?></span>
            <button type="button" onclick="updateQty(1)">+</button>
          </div>
          <?php if ($id === 2): ?>
          <div class="minimum-order-note">
            <i class="fa-solid fa-circle-info"></i>
            <strong>Minimum Order: 15 PCS</strong>
            <span>Please select at least 15 pieces for apparel.</span>
          </div>
          <?php endif; ?>
        </div>

        <div class="btn-group">
          <button class="btn-add-cart" onclick="addToCart()">🛒 Add to Cart</button>
          <button class="btn-buy-now" onclick="buyNowWhatsApp()">💬 Order via WhatsApp</button>
        </div>

        <div class="accordion-container">
          <div class="accordion-item">
            <button class="accordion-header" onclick="toggleAccordion(this)">
              <span>Specifications & Materials</span>
              <span>+</span>
            </button>
            <div class="accordion-content">
              <?php if (!empty($product['specifications'])): ?>
                <table>
                  <?php foreach ($product['specifications'] as $key => $val): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($key); ?></td>
                      <td><?php echo htmlspecialchars($val); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </table>
              <?php else: ?>
                <p>Full technical specifications will be verified during artwork confirmation with our design team.</p>
              <?php endif; ?>
            </div>
          </div>

          <div class="accordion-item">
            <button class="accordion-header" onclick="toggleAccordion(this)">
              <span>Shipping & Returns Policy</span>
              <span>+</span>
            </button>
            <div class="accordion-content">
              <p>• Nationwide delivery available via reliable courier partners.</p>
              <p>• Items are securely packaged with protective bubble wrap for transit.</p>
              <p>• Custom-printed items featuring manufacturing defects attributed to our end will be eligible for reprinting or replacement.</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</div>

<section class="reviews-section" id="reviews">
  <div class="reviews-card">
    <div class="reviews-title-row">
      <h2><i class="fa-regular fa-star"></i> Customer Reviews</h2>
      <div class="rating-summary">
        <div class="rating-score">
          <span class="rating-number"><?= number_format($average_rating, 1) ?></span>
          <span class="stars-display">
            <?php
              $rounded_rating = (int)round($average_rating);
              for ($s = 1; $s <= 5; $s++) {
                  echo $s <= $rounded_rating ? '★' : '☆';
              }
            ?>
          </span>
          <span class="review-count"><?= $total_reviews ?> review<?= $total_reviews == 1 ? '' : 's' ?></span>
        </div>
        <div class="rating-breakdown">
          <?php for ($r = 5; $r >= 1; $r--): ?>
            <?php $percent = $total_reviews > 0 ? ($rating_breakdown[$r] / $total_reviews) * 100 : 0; ?>
            <div class="rating-row">
              <span><?= $r ?>★</span>
              <div class="rating-bar"><span style="width:<?= number_format($percent, 1, '.', '') ?>%"></span></div>
              <small><?= $rating_breakdown[$r] ?></small>
            </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>

    <?php if ($review_message !== ''): ?>
      <div class="review-alert <?= $review_success ? 'success' : 'error' ?>">
        <i class="fa-solid <?= $review_success ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= htmlspecialchars($review_message) ?>
      </div>
    <?php endif; ?>

    <div class="review-form"><div class="security-note"><i class="fa-solid fa-shield-halved"></i> You can share another review anytime after trying this product.</div>
      <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
        <h3>How was your experience with this product?</h3>
        <form method="POST" enctype="multipart/form-data" action="product.php?id=<?= $id ?>#reviews">
          <input type="hidden" name="review_action" value="submit_review">
          <input type="hidden" name="product_id" value="<?= $id ?>">

          <div class="star-rating" aria-label="Choose your rating">
            <input type="radio" id="star5" name="rating" value="5" required>
            <label for="star5" title="5 stars">★</label>

            <input type="radio" id="star4" name="rating" value="4">
            <label for="star4" title="4 stars">★</label>

            <input type="radio" id="star3" name="rating" value="3">
            <label for="star3" title="3 stars">★</label>

            <input type="radio" id="star2" name="rating" value="2">
            <label for="star2" title="2 stars">★</label>

            <input type="radio" id="star1" name="rating" value="1">
            <label for="star1" title="1 star">★</label>
          </div>

          <textarea class="review-textarea" name="review_text" maxlength="500" placeholder="Tell us about your experience..." required></textarea>
          <label class="review-upload">📷 Add a product photo (optional)
            <input type="file" name="review_image" accept="image/png,image/jpeg,image/webp">
          </label>

          <button type="submit" class="review-submit">
            <i class="fa-solid fa-paper-plane"></i> Submit Review
          </button>
        </form>
      <?php else: ?>
        <p class="review-login">
          <i class="fa-solid fa-lock"></i>
          Please <a href="login.php">login or register</a> to leave a review and rating.
        </p>
      <?php endif; ?>
    </div>

    <div class="review-list">
      <?php if (!empty($reviews)): ?>
        <?php foreach ($reviews as $review): ?>
          <article class="review-item">
            <div class="review-head">
              <div>
                <div class="reviewer-name">
                  <i class="fa-solid fa-circle-user"></i>
                  <?= htmlspecialchars($review['name']) ?>
                </div>
                <div class="review-stars">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <?= $s <= (int)$review['rating'] ? '★' : '☆' ?>
                  <?php endfor; ?>
                </div>
              </div>
              <span class="review-date">
                <?= date('d M Y', strtotime($review['created_at'])) ?>
              </span>
            </div>
            <!-- Papar teks ulasan -->
<p><?php echo htmlspecialchars($review['review_text'] ?? ''); ?></p>

<!-- Papar imej hanya jika wujud -->
<?php if (!empty($review['review_image'])): ?>
    <img src="uploads/reviews/<?php echo htmlspecialchars($review['review_image']); ?>" alt="Review Image">
<?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-reviews">
          <i class="fa-regular fa-comment-dots"></i>
          <p>No reviews yet. Be the first customer to review this product!</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<footer class="sa-footer">
  <div class="sa-footer-grid">
    <div><div class="sa-footer-brand"><span>SA</span> DESIGN</div><p>Printing &amp; Advertising</p><p>Quality printing solutions for business, events, school and personal needs.</p></div>
    <div><h4>QUICK LINKS</h4><a href="index.php">Home</a><a href="index.php#products-section">Products</a><a href="about.php">About Us</a><a href="custom_request.php">Custom Request</a></div>
    <div><h4>CONTACT</h4><a href="https://wa.me/60194184147" target="_blank" rel="noopener">WhatsApp Us</a><a href="mailto:salamakal@gmail.com">Email Us</a></div>
  </div>
  <div class="sa-footer-line">&copy; 2026 Politeknik Muadzam Shah. All rights reserved.</div>
</footer>
</div>

<div id="loginRequiredModal" class="login-required-modal" aria-hidden="true">
  <div class="login-required-dialog" role="dialog" aria-modal="true" aria-labelledby="loginRequiredTitle">
    <button type="button" class="login-required-close" aria-label="Close">&times;</button>
    <i class="fa-solid fa-lock"></i>
    <h2 id="loginRequiredTitle">Login required</h2>
    <p>Please log in or create an account before adding items to your cart or placing an order.</p>
    <a href="login.php" class="login-required-button">Login or Register</a>
  </div>
</div>
<style>
  .login-required-modal { position:fixed; inset:0; z-index:3000; display:none; align-items:center; justify-content:center; padding:20px; background:rgba(15,23,42,.68); backdrop-filter:blur(5px); }.login-required-modal.is-open { display:flex; animation:login-modal-fade .22s ease-out; }.login-required-dialog { position:relative; width:min(430px,100%); padding:42px 34px 34px; overflow:hidden; border:1px solid rgba(255,255,255,.72); border-radius:24px; background:linear-gradient(145deg,#fff 0%,#f8faff 100%); text-align:center; box-shadow:0 28px 70px rgba(15,23,42,.34); animation:login-modal-rise .28s cubic-bezier(.2,.8,.2,1); }.login-required-dialog::before { content:""; position:absolute; inset:0 0 auto; height:7px; background:var(--primary-gradient); }.login-required-dialog > i { position:relative; display:grid; place-items:center; width:62px; height:62px; margin:0 auto 18px; border:1px solid #dce6ff; border-radius:18px; background:linear-gradient(135deg,#edf3ff,#fff0f8); color:#1557d6; font-size:24px; box-shadow:0 10px 22px rgba(21,87,214,.13); }.login-required-dialog h2 { margin:0 0 10px; color:var(--text-dark); font-size:25px; line-height:1.2; letter-spacing:-.6px; }.login-required-dialog p { max-width:330px; margin:0 auto; color:var(--text-muted); font-size:14px; line-height:1.7; }.login-required-button { display:inline-flex; align-items:center; justify-content:center; min-height:46px; margin-top:25px; padding:12px 22px; border-radius:12px; background:var(--primary-gradient); color:#fff; font-size:14px; font-weight:800; text-decoration:none; box-shadow:0 10px 20px rgba(240,32,141,.2); transition:transform .2s ease,box-shadow .2s ease; }.login-required-button:hover { transform:translateY(-2px); box-shadow:0 14px 24px rgba(21,87,214,.25); }.login-required-button:focus-visible,.login-required-close:focus-visible { outline:3px solid rgba(21,87,214,.3); outline-offset:3px; }.login-required-close { position:absolute; top:14px; right:14px; display:grid; place-items:center; width:34px; height:34px; border:0; border-radius:50%; background:transparent; color:#64748b; font-size:27px; line-height:1; cursor:pointer; transition:background .2s ease,color .2s ease; }.login-required-close:hover { background:#edf3ff; color:#1557d6; } @keyframes login-modal-fade { from { opacity:0; } to { opacity:1; } } @keyframes login-modal-rise { from { opacity:0; transform:translateY(14px) scale(.97); } to { opacity:1; transform:translateY(0) scale(1); } }

  /* Product-page mobile safeguards: keep content fluid without changing desktop layout. */
  html, body { max-width:100%; overflow-x:hidden; }
  .detail-grid, .detail-info, .gallery-container, .reviews-card { min-width:0; }
  .detail-gallery img, .review-item img { max-width:100%; height:auto; }

  @media (max-width: 600px) {
    .product-detail-section { width:calc(100% - 32px); padding:20px 0 48px; }
    .product-hero { padding:20px 16px 18px; margin-bottom:20px; border-radius:18px; }
    .product-hero h2 { font-size:clamp(1.8rem, 9vw, 2.35rem); letter-spacing:-1.2px; }
    .back-btn { min-height:44px; margin-bottom:20px; padding:10px 14px; }
    .detail-grid { gap:28px; }
    .detail-gallery { height:clamp(260px, 86vw, 360px); padding:16px; border-radius:16px; }
    .nav-arrow { top:auto; bottom:12px; transform:none; width:44px; height:44px; }
    .nav-arrow:hover { transform:scale(1.04); }
    .nav-arrow.left { left:12px; }
    .nav-arrow.right { right:12px; }
    .image-counter { top:12px; left:12px; font-size:11px; padding:5px 10px; }
    .thumbnails-list { gap:8px; padding-bottom:6px; }
    .thumb-item { width:64px; height:64px; }

    .detail-info .badge-row { align-items:flex-start; justify-content:flex-start; flex-wrap:wrap; gap:8px; }
    .detail-info h1 { font-size:clamp(1.8rem, 9vw, 2.5rem); overflow-wrap:anywhere; }
    .price-card { flex-wrap:wrap; padding:15px 16px; }
    .features-box, .promo-info-card { padding:16px; }
    .field-help { flex-wrap:wrap; }
    .field-help .char-count { margin-left:auto; }
    .custom-select, .custom-textarea { max-width:100%; }
    .btn-group { gap:10px; margin:24px 0; }
    .btn-add-cart, .btn-buy-now { min-height:48px; padding:12px; }

    .accordion-header { min-height:48px; padding:13px 0; gap:12px; }
    .accordion-content table, .accordion-content tbody, .accordion-content tr, .accordion-content td { display:block; width:100%; }
    .accordion-content tr { padding:8px 0; border-bottom:1px dashed var(--border-color); }
    .accordion-content td { padding:2px 0; border:0; overflow-wrap:anywhere; }
    .accordion-content td:first-child { width:auto; }

    .reviews-section { padding:0 16px 48px; }
    .reviews-card { padding:18px 16px; border-radius:16px; }
    .reviews-title-row { align-items:stretch; gap:14px; margin-bottom:18px; }
    .reviews-title-row h2 { font-size:20px; }
    .rating-summary { width:100%; flex-wrap:wrap; justify-content:center; }
    .rating-score, .rating-breakdown { min-width:0; }
    .rating-breakdown { width:100%; }
    .review-form { padding:16px; }
    .review-submit { min-height:44px; width:100%; }
    .review-head { align-items:flex-start; flex-wrap:wrap; }

    .login-required-modal { padding:14px; }
    .login-required-dialog { width:100%; max-height:calc(100vh - 28px); overflow-y:auto; padding:32px 20px 24px; }
    .login-required-close { width:44px; height:44px; }
    .login-required-button { min-height:44px; display:inline-flex; align-items:center; justify-content:center; }
    .navbar .nav-links a { display:inline-flex; align-items:center; min-height:44px; }
  }

  @media (max-width: 380px) {
    .navbar { padding:12px 14px; gap:10px; }
    .navbar .logo-title { font-size:26px; letter-spacing:-1.6px; }
    .navbar .logo-subtitle { font-size:7px; letter-spacing:.45px; }
    .product-detail-section { width:calc(100% - 24px); }
    .product-hero { padding:18px 14px 16px; }
    .detail-gallery { height:260px; padding:12px; }
    .section-badge { max-width:100%; overflow-wrap:anywhere; }
    .stock-tag, .detail-info .badge { max-width:100%; }
    .rating-summary { padding:10px; }
  }
</style>

<script>
  const isLoggedIn = <?= isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true ? 'true' : 'false' ?>;
  const loginRequiredModal = document.getElementById('loginRequiredModal');
  function showLoginRequiredModal() { loginRequiredModal.classList.add('is-open'); loginRequiredModal.setAttribute('aria-hidden', 'false'); }
  document.querySelector('.login-required-close').addEventListener('click', () => { loginRequiredModal.classList.remove('is-open'); loginRequiredModal.setAttribute('aria-hidden', 'true'); });
  loginRequiredModal.addEventListener('click', (event) => { if (event.target === loginRequiredModal) { loginRequiredModal.classList.remove('is-open'); loginRequiredModal.setAttribute('aria-hidden', 'true'); } });
  if (!isLoggedIn) document.getElementById('cartBtn').addEventListener('click', (event) => { event.preventDefault(); showLoginRequiredModal(); });
  
  const productId = <?php echo json_encode($id); ?>;
  const productName = <?php echo json_encode($product['name']); ?>;
  const productImage = <?php echo json_encode($product['image']); ?>;
  const images = <?php echo json_encode($gallery_images); ?>;
  let currentImgIdx = 0;
  let currentQty = productId === 2 ? 15 : 1;

  function setRemarkGuidance() {
    const rules = {
      stampText: { max: 500, help: 'Enter stamp text details.' },
      cardDetails: { max: 500, help: 'Enter card text details.' },
      windflagDetails: { max: 500, help: 'Enter design brief details.' },
      stickerDetails: { max: 500, help: 'Enter sticker text details.' },
      requestNotes: { max: 500, help: 'Enter production notes.' }
    };

    Object.entries(rules).forEach(([id, rule]) => {
      const field = document.getElementById(id);
      if (!field) return;
      field.maxLength = rule.max;
      const helper = document.createElement('div');
      helper.className = 'field-help';
      helper.innerHTML = `<span>${rule.help} Maximum ${rule.max} characters.</span><span class="char-count">0/${rule.max}</span>`;
      field.insertAdjacentElement('afterend', helper);
      const count = helper.querySelector('.char-count');
      const updateCount = () => { count.textContent = `${field.value.length}/${rule.max}`; };
      field.addEventListener('input', updateCount);
      updateCount();
    });
  }

  window.onload = function() {
  setRemarkGuidance();
  if (document.getElementById('cardType')) {
    updateCardQtyDropdown();
  }
  if (document.getElementById('windflagSize')) {
    onWindflagSelectionChange();
  }
  if (document.getElementById('stickerSize')) {
    updateStickerQtyDropdown();
  }
};

  function updateGallery() {
    const imgEl = document.getElementById('detailImg');
    imgEl.src = images[currentImgIdx];

    const total = images.length;
    document.getElementById('imgCounter').textContent = `${String(currentImgIdx + 1).padStart(2, '0')} / ${String(total).padStart(2, '0')}`;

    const thumbs = document.querySelectorAll('.thumb-item');
    thumbs.forEach((t, i) => {
      if(i === currentImgIdx) t.classList.add('active');
      else t.classList.remove('active');
    });
  }

  function selectImage(index) {
    currentImgIdx = index;
    updateGallery();
  }

  function nextImage() {
    currentImgIdx = (currentImgIdx + 1) % images.length;
    updateGallery();
  }

  function prevImage() {
    currentImgIdx = (currentImgIdx - 1 + images.length) % images.length;
    updateGallery();
  }

  function onCodeChange() {
    const select = document.getElementById('codeSelector');
    const displayPriceEl = document.getElementById('displayPrice');
    if (!select || !displayPriceEl) return;

    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    const imgIdx = parseInt(selectedOption.getAttribute('data-img'));

    displayPriceEl.textContent = `RM ${price} / PCS`;
    if(!isNaN(imgIdx) && imgIdx < images.length) {
      currentImgIdx = imgIdx;
      updateGallery();
    }
  }

  function onBajuSelectionChange() {
    const select = document.getElementById('bajuType');
    const displayPriceEl = document.getElementById('displayPrice');
    if (!select || !displayPriceEl) return;

    const price = select.options[select.selectedIndex].getAttribute('data-price');
    displayPriceEl.textContent = `RM ${price}`;
  }

  function onBannerSelectionChange() {
    const select = document.getElementById('bannerSize');
    const displayPriceEl = document.getElementById('displayPrice');
    if (!select || !displayPriceEl) return;

    const price = select.options[select.selectedIndex].getAttribute('data-price');
    displayPriceEl.textContent = `RM ${price}`;
  }

  function onWindflagSelectionChange() {
    const sizeSelect = document.getElementById('windflagSize');
    const designSelect = document.getElementById('windflagDesignOpt');
    const displayPriceEl = document.getElementById('displayPrice');

    if (!sizeSelect || !displayPriceEl) return;

    const basePrice = parseFloat(sizeSelect.options[sizeSelect.selectedIndex].getAttribute('data-price')) || 0;
    const designFee = designSelect ? parseFloat(designSelect.options[designSelect.selectedIndex].getAttribute('data-fee')) || 0 : 0;

    const totalPrice = (basePrice + designFee).toFixed(2);
    displayPriceEl.textContent = `RM ${totalPrice}`;
  }

  function updateCardQtyDropdown() {
    const typeSelect = document.getElementById('cardType');
    const qtySelect = document.getElementById('cardQty');
    if (!typeSelect || !qtySelect || !cardOptions) return;

    const selectedType = typeSelect.value;
    const options = cardOptions[selectedType] || [];

    let html = '';
    options.forEach(opt => {
      html += `<option value="${opt.label}" data-price="${opt.price}">${opt.label} — RM ${opt.price}</option>`;
    });
    qtySelect.innerHTML = html;
    onCardSelectionChange();
  }

  function onCardSelectionChange() {
    const qtySelect = document.getElementById('cardQty');
    const displayPriceEl = document.getElementById('displayPrice');
    if (!qtySelect || !displayPriceEl) return;

    const selectedQtyOpt = qtySelect.options[qtySelect.selectedIndex];
    const basePrice = selectedQtyOpt ? parseFloat(selectedQtyOpt.getAttribute('data-price')).toFixed(2) : '0.00';
    displayPriceEl.textContent = `RM ${basePrice}`;
  }

  function updateStickerQtyDropdown() {
    const sizeSelect = document.getElementById('stickerSize');
    const qtySelect = document.getElementById('stickerQty');
    if (!sizeSelect || !qtySelect || !stickerOptions) return;

    const selectedSize = sizeSelect.value;
    const options = stickerOptions[selectedSize] || [];

    let html = '';
    options.forEach(opt => {
      html += `<option value="${opt.label}" data-price="${opt.price}">${opt.label} — RM ${opt.price}</option>`;
    });
    qtySelect.innerHTML = html;
    onStickerSelectionChange();
  }

  function onStickerSelectionChange() {
    const qtySelect = document.getElementById('stickerQty');
    const displayPriceEl = document.getElementById('displayPrice');
    if (!qtySelect || !displayPriceEl) return;

    const selectedQtyOpt = qtySelect.options[qtySelect.selectedIndex];
    const price = selectedQtyOpt ? parseFloat(selectedQtyOpt.getAttribute('data-price')).toFixed(2) : '0.00';
    displayPriceEl.textContent = `RM ${price}`;
  }

  function updateQty(change) {
    const minimumQty = productId === 2 ? 15 : 1;
    if (currentQty + change >= minimumQty) {
      currentQty += change;
      document.getElementById('qtyVal').textContent = currentQty;
    }
  }

  function toggleAccordion(btn) {
    const content = btn.nextElementSibling;
    const isVisible = content.style.display === 'block';
    
    document.querySelectorAll('.accordion-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.accordion-header span:last-child').forEach(el => el.textContent = '+');

    if (!isVisible) {
      content.style.display = 'block';
      btn.querySelector('span:last-child').textContent = '−';
    }
  }

  function updateTotalPrice() {
    const typeSelect = document.getElementById('typeOption');
    const sizeSelect = document.getElementById('sizeOption');
    const displayPriceEl = document.getElementById('displayPrice');

    if (!displayPriceEl) return;

    let typePrice = 0;
    let sizePrice = 0;

    if (typeSelect && typeSelect.options[typeSelect.selectedIndex]) {
        typePrice = parseFloat(typeSelect.options[typeSelect.selectedIndex].dataset.price) || 0;
    }

    if (sizeSelect && sizeSelect.options[sizeSelect.selectedIndex]) {
        sizePrice = parseFloat(sizeSelect.options[sizeSelect.selectedIndex].dataset.price) || 0;
    }

    // Jika saiz mempunyai harga tersendiri yang menggantikan atau menambah harga jenis:
    // (Gunakan sizePrice jika wujud, jika tidak gunakan typePrice / base price)
    let finalPrice = sizePrice > 0 ? sizePrice : typePrice;

    // Jika saiz adalah harga tambahan (add-on), gunakan:
    // let finalPrice = typePrice + sizePrice;

    displayPriceEl.textContent = `RM ${finalPrice.toFixed(2)}`;
}

function addToCart() {
    if (!isLoggedIn) {
        showLoginRequiredModal();
        return;
    }

    currentQty = Math.max(1, Math.min(999, parseInt(currentQty, 10) || 1));

    const typeSelect = document.getElementById('typeOption');
    const sizeSelect = document.getElementById('sizeOption');

    let selectedType = typeSelect ? typeSelect.value : 'Standard';
    let selectedSize = sizeSelect ? sizeSelect.value : 'Standard';
    let price = parseFloat(<?php echo json_encode((float)$product['price']); ?>) || 0;

    if (typeSelect && typeSelect.options[typeSelect.selectedIndex]) {
        const selectedOpt = typeSelect.options[typeSelect.selectedIndex];
        price = parseFloat(selectedOpt.dataset.price) || price;
    }

    const details = [];
    if (typeSelect) details.push(`Type: ${selectedType}`);
    if (sizeSelect) details.push(`Size: ${selectedSize}`);

    const requestNotes = document.getElementById('requestNotes');
    if (requestNotes && requestNotes.value.trim()) {
        details.push(`Notes: ${requestNotes.value.trim()}`);
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'add_to_cart.php';

    const fields = {
        add_to_cart: '1',
        product_id: productId,
        cart_item_id: `${productId}_${Date.now()}`,
        product_name: productName,
        product_price: price.toFixed(2),
        product_image: productImage,
        size: selectedSize,
        details: details.join('\n'),
        quantity: currentQty
    };

    Object.entries(fields).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

  function buyNowWhatsApp() {
    if (!isLoggedIn) {
      showLoginRequiredModal();
      return;
    }

    const phone = '60194184147';
    let details = `Dear Sales Team,\n\nI would like to place an order for the following item:\n\n*Product Name:* ${productName}\n*Quantity:* ${currentQty}\n`;

    if (productId === 1) {
      const codeSelect = document.getElementById('codeSelector');
      const code = codeSelect ? codeSelect.options[codeSelect.selectedIndex].text : '';
      const text = document.getElementById('stampText').value.trim();
      details += `*Stamp Code/Model:* ${code}\n*Stamp Details:* ${text}\n`;
    } else if (productId === 2) {
      const type = document.getElementById('bajuType').value;
      const size = document.getElementById('bajuSize').value;
      const notes = document.getElementById('requestNotes').value.trim();
      details += `*Apparel Type:* ${type}\n*Size:* ${size}\n*Remarks:* ${notes}\n`;
    } else if (productId === 3) {
      const size = document.getElementById('bannerSize').value;
      const notes = document.getElementById('requestNotes').value.trim();
      details += `*Banner Dimension:* ${size}\n*Finishing/Remarks:* ${notes}\n`;
    } else if (productId === 4) {
      const type = document.getElementById('cardType').value;
      const qty = document.getElementById('cardQty').value;
      const cardText = document.getElementById('cardDetails').value.trim();
      details += `*Card Finishing:* ${type}\n*Quantity Package:* ${qty}\n*Card Details:* ${cardText}\n`;
    } else if (productId === 5) {
      const size = document.getElementById('windflagSize').value;
      const designOpt = document.getElementById('windflagDesignOpt').value;
      const wfDetails = document.getElementById('windflagDetails').value.trim();
      details += `*Windflag Height:* ${size}\n*Design Option:* ${designOpt}\n*Design Brief:* ${wfDetails}\n`;
    } else if (productId === 6) {
      const shape = document.getElementById('stickerShape').value;
      const size = document.getElementById('stickerSize').value;
      const qty = document.getElementById('stickerQty').value;
      const stickerText = document.getElementById('stickerDetails').value.trim();
      details += `*Shape:* ${shape}\n*Size:* ${size}\n*Quantity Package:* ${qty}\n*Sticker Details:* ${stickerText}\n`;
    }

    details += `\nPlease confirm pricing and delivery arrangements. Thank you.`;

    const url = `https://wa.me/${phone}?text=${encodeURIComponent(details)}`;
    window.open(url, '_blank');
  }
</script>

<script>
function confirmLogout() {
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
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = 'logout.php';
    }
  });
}

const menuToggle = document.getElementById('menuToggle');
const navbar = document.querySelector('.navbar');
menuToggle.addEventListener('click', () => {
  const isOpen = navbar.classList.toggle('menu-open');
  menuToggle.setAttribute('aria-expanded', String(isOpen));
  menuToggle.innerHTML = isOpen
    ? '<i class="fa-solid fa-xmark"></i>'
    : '<i class="fa-solid fa-bars"></i>';
});
document.querySelectorAll('.nav-links a').forEach(link => {
  link.addEventListener('click', () => navbar.classList.remove('menu-open'));
});

function onProductOptionChange() {
    const select = document.getElementById('productOption');
    const displayPriceEl = document.getElementById('displayPrice');

    if (!select || !displayPriceEl) return;

    const selectedOption =
        select.options[select.selectedIndex];

    const price =
        parseFloat(selectedOption.dataset.price) || 0;

    displayPriceEl.textContent =
        `RM ${price.toFixed(2)}`;
}
</script>

</body>
</html>
