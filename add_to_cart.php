<?php
session_start();
include('db.php');

/*
|--------------------------------------------------------------------------
| SA DESIGN - SECURE ADD TO CART (DYNAMIC DATABASE DRIVEN)
|--------------------------------------------------------------------------
| The browser is NOT trusted for product name, image or price.
| Product/specification prices are validated again on the server.
|--------------------------------------------------------------------------
*/

// 1. Protection Check - Ensure Login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['login_notice'] = 'Please sign in or create an account before adding items to your cart.';
    header('Location: login.php');
    exit();
}

// 2. Only POST Requests Are Accepted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['add_to_cart'])) {
    header('Location: index.php');
    exit();
}

// -------------------------------------------------------------------------
// 3. VALIDATE PRODUCT ID & FETCH MAIN PRODUCT FROM DATABASE
// -------------------------------------------------------------------------
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if ($product_id === false || $product_id === null) {
    $_SESSION['cart_error'] = 'Invalid product selected.';
    header('Location: index.php');
    exit();
}

// Fetch base product info securely using Prepared Statements
$stmt_prod = $conn->prepare("SELECT id, name, price, image, status FROM products WHERE id = ?");
$stmt_prod->bind_param("i", $product_id);
$stmt_prod->execute();
$res_prod = $stmt_prod->get_result();

if ($res_prod->num_rows === 0) {
    $_SESSION['cart_error'] = 'Product not found in system.';
    header('Location: index.php');
    exit();
}

$product = $res_prod->fetch_assoc();

if (strtolower($product['status']) !== 'active') {
    $_SESSION['cart_error'] = 'This product is currently unavailable.';
    header('Location: index.php');
    exit();
}

// -------------------------------------------------------------------------
// 4. VALIDATE QUANTITY
// -------------------------------------------------------------------------
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if ($quantity === false || $quantity === null) {
    $quantity = 1;
}

if ($quantity < 1 || $quantity > 999) {
    $_SESSION['cart_error'] = 'Quantity must be between 1 and 999.';
    header("Location: product.php?id=" . $product_id);
    exit();
}

// -------------------------------------------------------------------------
// 5. READ FORM INPUTS (VARIANT, SIZE & CUSTOM DETAILS)
// -------------------------------------------------------------------------
$size = trim($_POST['size'] ?? '');
$details = trim($_POST['details'] ?? '');

// Default fallback to base product price
$server_price = floatval($product['price']);
$validated_variant = 'Standard';

// -------------------------------------------------------------------------
// 6. DYNAMIC VARIANT VALIDATION FROM DATABASE (OPTIONS JSON)
// -------------------------------------------------------------------------
if (!empty($size)) {
    // Membaca json options dari pangkalan data
    $stmt_opt = $conn->prepare("SELECT options FROM products WHERE id = ?");
    $stmt_opt->bind_param("i", $product_id);
    $stmt_opt->execute();
    $res_opt = $stmt_opt->get_result();

    if ($res_opt->num_rows > 0) {
        $row_opt = $res_opt->fetch_assoc();
        $options_list = json_decode($row_opt['options'] ?? '[]', true) ?: [];

        $found_variant = false;
        foreach ($options_list as $opt) {
            if (isset($opt['name']) && $opt['name'] === $size) {
                // Tambah harga pilihan ke harga asas produk jika ada
                if (isset($opt['price']) && floatval($opt['price']) > 0) {
                    $server_price += floatval($opt['price']);
                }
                $validated_variant = htmlspecialchars($opt['name'], ENT_QUOTES, 'UTF-8');
                $found_variant = true;
                break;
            }
        }

        if (!$found_variant) {
            $validated_variant = htmlspecialchars($size, ENT_QUOTES, 'UTF-8');
        }
    } else {
        $validated_variant = htmlspecialchars($size, ENT_QUOTES, 'UTF-8');
    }
}

// -------------------------------------------------------------------------
// 7. SANITIZE CUSTOM DETAILS
// -------------------------------------------------------------------------
$details = mb_substr($details, 0, 1200);

// -------------------------------------------------------------------------
// 8. GENERATE A SAFE & UNIQUE CART ITEM ID
// -------------------------------------------------------------------------
$cart_item_id = trim($_POST['cart_item_id'] ?? '');

if ($cart_item_id === '' || !preg_match('/^[a-zA-Z0-9_-]{1,100}$/', $cart_item_id)) {
    $cart_item_id = $product_id . '-' . time() . '-' . bin2hex(random_bytes(4));
}

// -------------------------------------------------------------------------
// 9. ADD / UPDATE ITEM IN SESSION CART
// -------------------------------------------------------------------------
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$cart_item_id])) {
    $new_quantity = (int)$_SESSION['cart'][$cart_item_id]['quantity'] + $quantity;
    $_SESSION['cart'][$cart_item_id]['quantity'] = min(999, $new_quantity);
} else {
    $_SESSION['cart'][$cart_item_id] = [
        'product_id' => $product['id'],
        'name'       => $product['name'],
        'image'      => $product['image'],
        'price'      => round($server_price, 2),
        'size'       => $validated_variant,
        'details'    => $details,
        'quantity'   => $quantity
    ];
}

// -------------------------------------------------------------------------
// 10. SUCCESS REDIRECT
// -------------------------------------------------------------------------
$_SESSION['cart_success'] = htmlspecialchars($product['name']) . ' added to cart successfully.';

header('Location: cart.php');
exit();
?>