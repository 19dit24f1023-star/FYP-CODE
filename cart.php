<?php
session_start();

// The cart is available to signed-in customers only.
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['login_notice'] = 'Please sign in or create an account to view your cart.';
    header('Location: login.php');
    exit();
}

// Initialise the cart if it does not exist yet.
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 1. REMOVE ITEM
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $item_id = $_GET['id'];
    unset($_SESSION['cart'][$item_id]);
    header("Location: cart.php");
    exit();
}

// 2. UPDATE QUANTITY
if (isset($_GET['action']) && $_GET['action'] == 'update') {
    $item_id = $_GET['id'];
    $type = $_GET['type'];
    
    if (isset($_SESSION['cart'][$item_id])) {
        if ($type == 'increase') {
            $_SESSION['cart'][$item_id]['quantity'] += 1;
        } elseif ($type == 'decrease' && $_SESSION['cart'][$item_id]['quantity'] > 1) {
            $_SESSION['cart'][$item_id]['quantity'] -= 1;
        }
    }
    header("Location: cart.php");
    exit();
}

// Calculate the cart subtotal.
$subtotal = 0;
$total_cart_count = 0;

foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_cart_count += (int)($item['quantity'] ?? 1);
}
$total = $subtotal;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shopping Cart | SA Design</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{min-height:100vh;background:#f6e8f2;color:#172033;font-family:'Plus Jakarta Sans',sans-serif}
a{text-decoration:none}

.navbar{height:84px;padding:16px 48px;display:flex;align-items:center;justify-content:space-between;gap:25px;background:#fff;border-bottom:1px solid #e8ebf2;position:relative;z-index:20}
.nav-logo{display:flex;align-items:center;min-width:max-content}
.logo-text{display:flex;flex-direction:column;gap:2px}
.logo-title{font-size:36px;font-weight:900;line-height:.9;letter-spacing:-2.5px;white-space:nowrap}
.logo-sa{color:#f0208d}.logo-design{color:#1557d6}
.logo-subtitle{color:#111827;font-size:12px;font-weight:900;letter-spacing:.7px;text-transform:uppercase}
.nav-links{display:flex;align-items:center;justify-content:center;gap:32px;list-style:none}
.nav-links a{color:#111827;font-size:13px;font-weight:800;text-transform:uppercase;transition:.2s}
.nav-links a:hover,.nav-links a.active{color:#f0208d}
.nav-icons{display:flex;align-items:center;gap:10px}
.login-pill{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 15px;border-radius:11px;background:linear-gradient(135deg,#f0208d,#ff3b86);color:#fff;font-size:12px;font-weight:800;text-transform:uppercase;box-shadow:0 8px 18px rgba(239,58,155,.18);transition:.25s}
.login-pill:hover{transform:translateY(-2px)}
    .logout-pill{border:0;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif}
    .logout-pill:focus{outline:none}
    .logout-popup{border-radius:18px!important}
    .logout-title{color:#102f91!important;font-weight:800!important}
    .logout-text{color:#64748b!important;font-size:13px!important}
    .logout-confirm,.logout-cancel{
        border-radius:10px!important;
        padding:10px 18px!important;
        font-family:'Plus Jakarta Sans',sans-serif!important;
        font-size:12px!important;
        font-weight:800!important;
    }

.icon-btn{position:relative;width:42px;height:42px;display:flex;align-items:center;justify-content:center;border:1px solid #f0208d;border-radius:50%;background:#fff;color:#f0208d;transition:.2s}
.icon-btn:hover{background:#ffe4f2;transform:translateY(-2px)}
.cart-badge{position:absolute;top:-4px;right:-4px;width:19px;height:19px;border:2px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#f0208d;color:#fff;font-size:9px;font-weight:900}
.menu-toggle{display:none;width:42px;height:42px;border:1px solid #dfe4ef;border-radius:11px;background:#fff;color:#172033;cursor:pointer}

.cart-hero{position:relative;overflow:hidden;padding:78px 70px 72px;background:linear-gradient(120deg,#cbd8ff 0%,#d9b9eb 52%,#ffc0dc 100%);border-bottom:1px solid #b7c8ff}
.cart-hero:before,.cart-hero:after{content:"";position:absolute;border-radius:50%;pointer-events:none}
.cart-hero:before{width:300px;height:300px;right:-90px;top:-150px;background:rgba(40,84,197,.20)}
.cart-hero:after{width:220px;height:220px;left:-90px;bottom:-130px;background:rgba(239,58,155,.20)}
.cart-hero-inner{position:relative;z-index:2;max-width:1050px;margin:auto;text-align:center}
.badge{display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border-radius:999px;background:#fff;border:1px solid #b7c8ff;color:#1747c7;font-size:10px;font-weight:800;letter-spacing:.7px}
.cart-hero h1{margin:15px 0 10px;color:#102f91;font-size:clamp(35px,5vw,52px);font-weight:900;letter-spacing:-2px}
.cart-hero h1 span{color:#f0208d}
.cart-hero p{max-width:720px;margin:auto;color:#64748b;font-size:14px;line-height:1.7}

.container{width:min(1120px,calc(100% - 100px));margin:48px auto 70px}
.cart-intro{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:25px}
.eyebrow{margin-bottom:7px;color:#f0208d;font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
.page-title{color:#102f91;font-size:30px;font-weight:900;letter-spacing:-1px}
.cart-count{color:#64748b;font-size:11px;font-weight:600}
.cart-grid{display:grid;grid-template-columns:minmax(0,1fr) 355px;gap:24px;align-items:start}
.cart-section,.summary-card{background:#fff;border:1px solid #e2e7f1;border-radius:22px;box-shadow:0 14px 35px rgba(28,53,118,.07)}
.cart-section{padding:5px 25px}
.select-box{width:18px;height:18px;accent-color:#f0208d;cursor:pointer}
.select-all-bar{display:flex;align-items:center;gap:10px;padding:15px 0;border-bottom:1px solid #e9edf5;color:#172033;font-size:.78rem;font-weight:800}
.select-all-bar span{color:#64748b;font-weight:600}
.cart-item{display:grid;grid-template-columns:28px minmax(270px,1fr) auto 104px 30px;align-items:center;column-gap:16px;padding:20px 0;border-bottom:1px solid #e9edf5}
.cart-item:last-child{border-bottom:0}
.item-info{min-width:0;display:flex;align-items:center;gap:17px}
.item-img{flex:0 0 88px;width:88px;height:88px;display:grid;place-items:center;overflow:hidden;border-radius:14px;background:#cddaff;color:#1747c7;font-size:1.35rem}
.item-img img{width:100%;height:100%;object-fit:cover}
.item-details h4{margin:0 0 6px;color:#172033;font-size:.95rem;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.item-details p{margin:0;color:#64748b;font-size:.74rem}
.item-details .order-details{max-width:360px;margin-top:7px;color:#7b8799;font-size:.70rem;line-height:1.5;white-space:pre-line}

.qty-control{display:flex;align-items:center;height:36px;overflow:hidden;border:1px solid #dce2ef;border-radius:10px;background:#fff}
.qty-btn{width:31px;height:34px;display:grid;place-items:center;color:#1747c7;background:#fff;font-size:1.1rem;transition:.15s}
.qty-btn:hover{color:#f0208d;background:#cbd8ff}
.qty-input{width:33px;border:0;outline:0;color:#172033;background:#fff;text-align:center;font-size:.82rem;font-weight:800}
.item-price{color:#102f91;font-size:.88rem;font-weight:800;text-align:right}
.remove-btn{width:30px;height:30px;display:grid;place-items:center;border-radius:50%;color:#9aa5b5;font-size:1rem;transition:.2s}
.remove-btn:hover{color:#f0208d;background:#ffe4f2}

.summary-card{position:sticky;top:105px;padding:27px}
.summary-card h3{margin:0 0 23px;padding-bottom:17px;border-bottom:1px solid #e9edf5;color:#102f91;font-size:19px;font-weight:900}
.summary-row{display:flex;justify-content:space-between;gap:12px;margin-bottom:14px;color:#64748b;font-size:.82rem}
.summary-row span:last-child{color:#172033;font-weight:700}
.coupon-box{display:flex;gap:8px;margin:24px 0;padding:4px;border:1px dashed #b8a9bc;border-radius:10px;background:#fff3fa}
.coupon-box input{min-width:0;flex:1;border:0;outline:0;background:transparent;padding:8px;color:#172033;font-size:.75rem}
.apply-btn{border:0;border-radius:8px;padding:8px 12px;background:#cddaff;color:#1747c7;font-size:.7rem;font-weight:800;cursor:pointer;transition:.2s}
.apply-btn:hover{background:#1747c7;color:#fff}
.summary-row.total{margin:0;padding-top:19px;border-top:1px solid #e9edf5;color:#172033;font-size:.95rem;font-weight:800}
.summary-row.total span:last-child{color:#f0208d;font-size:1.15rem}
.checkout-btn{width:100%;display:block;margin-top:23px;padding:14px;border:0;border-radius:11px;background:linear-gradient(135deg,#1747c7,#3157d5);color:#fff;font-size:.76rem;font-weight:800;letter-spacing:.05em;text-align:center;transition:.25s;box-shadow:0 10px 22px rgba(40,84,197,.18)}
.checkout-btn:hover{transform:translateY(-2px);background:linear-gradient(135deg,#f0208d,#ff3b86)}
.secure-note{margin:15px 0 0;color:#94a3b8;font-size:.65rem;text-align:center}
.empty-cart{padding:72px 20px;color:#64748b;text-align:center}
.empty-icon{width:64px;height:64px;display:grid;place-items:center;margin:0 auto 15px;border-radius:50%;background:#cddaff;color:#1747c7;font-size:1.4rem}
.empty-cart p{font-size:12px}
.continue-link{display:inline-flex;align-items:center;gap:6px;margin-top:15px;color:#1747c7;font-size:.8rem;font-weight:800}
.continue-link:hover{color:#f0208d}

.footer{padding:38px 70px;background:#0b1d69;color:#fff}
.footer-grid{width:min(1120px,100%);margin:auto;display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:35px}
.footer-brand{font-size:24px;font-weight:900}
.footer-brand span{color:#f0208d}
.footer h4{margin-bottom:10px;font-size:12px}
.footer p,.footer a{color:rgba(255,255,255,.72);font-size:11px;line-height:1.7}
.footer a{display:block;margin-bottom:4px}
.footer a:hover{color:#fff}
.footer-line{width:min(1120px,100%);margin:24px auto 0;padding-top:14px;border-top:1px solid rgba(255,255,255,.14);text-align:center;color:rgba(255,255,255,.58);font-size:10px}

@media(max-width:900px){
    .navbar{padding:15px 28px}
    .cart-grid{grid-template-columns:1fr}
    .summary-card{position:static}
    .container{width:min(100% - 56px,720px)}
}
@media(max-width:760px){
    .navbar{height:auto;min-height:76px;flex-wrap:wrap;padding:15px 20px}
    .logo-title{font-size:29px}.logo-subtitle{font-size:9px}
    .menu-toggle{display:flex;align-items:center;justify-content:center}
    .nav-icons{margin-left:auto}
    .nav-links{display:none;order:4;width:100%;padding:12px 0 3px;flex-direction:column;align-items:center;gap:14px;border-top:1px solid #eef1f6}
    .navbar.menu-open .nav-links{display:flex}
    .login-pill span{display:none}
    .login-pill{width:40px;height:40px;padding:0}
    .icon-btn{width:40px;height:40px}
    .cart-hero{padding:55px 22px}
    .cart-hero h1{font-size:38px}
    .container{width:calc(100% - 40px);margin:38px auto 55px}
    .cart-intro{align-items:flex-start;flex-direction:column;gap:8px}
    .cart-section{padding:4px 16px}
    .cart-item{grid-template-columns:28px 1fr auto 30px;gap:13px;padding:17px 0}
    .item-info{grid-column:2 / -1}
    .cart-item .qty-control{grid-column:2}
    .cart-item .item-price{grid-column:3;grid-row:2}
    .cart-item .remove-btn{grid-column:4;grid-row:2}
    .item-img{flex-basis:72px;width:72px;height:72px}
    .item-price{text-align:left}
    .summary-card{padding:22px}
    .footer{padding:35px 20px}
    .footer-grid{grid-template-columns:1fr;gap:24px}
}
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
        <li><a href="custom_request.php">Custom Request</a></li>
    </ul>

    <div class="nav-icons">
        <?php if(isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
            <a href="cust_profile.php" class="login-pill" title="Profile">
                <i class="fa-regular fa-user"></i>
                <span>Profile</span>
            </a>
            <button type="button" class="login-pill logout-pill" title="Logout" onclick="confirmLogout()">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Log out</span>
            </button>
        <?php else: ?>
            <a href="login.php" class="login-pill" title="Login">
                <i class="fa-regular fa-user"></i>
                <span>Login</span>
            </a>
        <?php endif; ?>

        <a href="cart.php" class="icon-btn" title="Cart" id="cartBtn" aria-label="Shopping cart">
            <i class="fa-solid fa-bag-shopping"></i>
            <span class="cart-badge" id="cartBadgeCount"><?= $total_cart_count ?></span>
        </a>
        <?php if(isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
            <a href="wishlist.php" class="icon-btn" title="Wishlist" aria-label="My wishlist"><i class="fa-regular fa-heart"></i></a>
        <?php endif; ?>
    </div>

    <button type="button" class="menu-toggle" id="menuToggle"
            aria-label="Open menu" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>
</nav>

<section class="cart-hero">
    <div class="cart-hero-inner">
        <span class="badge">
            <i class="fa-solid fa-bag-shopping"></i>
            YOUR SELECTION
        </span>

        <h1>Your <span>Shopping Cart</span></h1>

        <p>
            Review your selected products before proceeding to checkout.
        </p>
    </div>
</section>

<main class="container">

    <div class="cart-intro">
        <div>
            <p class="eyebrow">ORDER REVIEW</p>
            <h2 class="page-title">Shopping Cart</h2>
        </div>

        <span class="cart-count">
            <?= count($_SESSION['cart']) ?>
            item<?= count($_SESSION['cart']) == 1 ? '' : 's' ?> in cart
        </span>
    </div>

    <div class="cart-grid">

        <div class="cart-section">

            <?php if (!empty($_SESSION['cart'])): ?>

                <div class="select-all-bar">
                    <input type="checkbox" class="select-box" id="selectAll" checked>
                    <label for="selectAll">Select All</label>
                    <span id="selectedCount"><?= count($_SESSION['cart']) ?> item<?= count($_SESSION['cart']) == 1 ? '' : 's' ?> selected</span>
                </div>
                <button type="button" class="checkout-btn cart-whatsapp-btn" id="cartWhatsapp">
                    <i class="fa-brands fa-whatsapp"></i> ASK ABOUT SELECTED ITEMS
                </button>

                <?php foreach ($_SESSION['cart'] as $id => $item): ?>

                    <div class="cart-item" data-cart-id="<?= htmlspecialchars((string) $id) ?>"
                         data-price="<?= htmlspecialchars((string) ($item['price'] * $item['quantity'])) ?>">

                        <input type="checkbox" class="select-box item-select" checked
                               aria-label="Select <?= htmlspecialchars($item['name']) ?>">

                        <div class="item-info">

                            <div class="item-img">

                                <?php if (!empty($item['image'])): ?>

                                    <img src="<?= htmlspecialchars($item['image']) ?>"
                                         alt="Product">

                                <?php else: ?>

                                    <i class="fa-solid fa-bag-shopping"></i>

                                <?php endif; ?>

                            </div>

                            <div class="item-details">

                                <h4>
                                    <?= htmlspecialchars($item['name']) ?>
                                </h4>

                                <p>
                                    Size:
                                    <?= htmlspecialchars($item['size'] ?? 'Standard') ?>
                                </p>

                                <?php if (!empty($item['details'])): ?>

                                    <p class="order-details">
                                        <?= nl2br(htmlspecialchars($item['details'])) ?>
                                    </p>

                                <?php endif; ?>

                            </div>

                        </div>

                        <div class="qty-control">

                            <a href="cart.php?action=update&id=<?= urlencode($id) ?>&type=decrease"
                               class="qty-btn">−</a>

                            <input type="text"
                                   class="qty-input"
                                   value="<?= $item['quantity'] ?>"
                                   readonly>

                            <a href="cart.php?action=update&id=<?= urlencode($id) ?>&type=increase"
                               class="qty-btn">+</a>

                        </div>

                        <div class="item-price">
                            RM <?= number_format(
                                $item['price'] * $item['quantity'],
                                2
                            ) ?>
                        </div>

                        <a href="javascript:void(0);"
                        class="remove-btn"
                        title="Remove item"
                        onclick="confirmDelete('<?= urlencode($id) ?>', '<?= htmlspecialchars(addslashes($item['name'])) ?>')">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-cart">

                    <div class="empty-icon">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>

                    <p>Your cart is currently empty.</p>

                    <a href="index.php#products-section"
                       class="continue-link">
                        <i class="fa-solid fa-arrow-left"></i>
                        Continue Shopping
                    </a>

                </div>

            <?php endif; ?>

        </div>


        <div class="summary-card">

            <h3>
                <i class="fa-solid fa-receipt"></i>
                Order Summary
            </h3>

            <div class="summary-row">
                <span>Subtotal</span>
                <span id="subtotalAmount">RM <?= number_format($subtotal, 2) ?></span>
            </div>

            <div class="summary-row total">
                <span>Total</span>
                <span id="totalAmount">RM <?= number_format($total, 2) ?></span>
            </div>

            <?php if (!empty($_SESSION['cart'])): ?>

                <a href="order_page.php"
                   id="checkoutLink"
                   class="checkout-btn">
                    <i class="fa-solid fa-lock"></i>
                    PROCEED TO CHECKOUT
                </a>

            <?php else: ?>

                <button class="checkout-btn"
                        style="opacity:.5;cursor:not-allowed;"
                        disabled>
                    PROCEED TO CHECKOUT
                </button>

            <?php endif; ?>

            <p class="secure-note">
                <i class="fa-solid fa-shield-halved"></i>
                Secure checkout · Your details are protected
            </p>

        </div>

    </div>
</main>

<footer class="footer">

    <div class="footer-grid">

        <div>
            <div class="footer-brand">
                <span>SA</span> DESIGN
            </div>
            <p>Printing &amp; Advertising</p>
            <p>
                Quality printing solutions for business,
                events, school and personal needs.
            </p>
        </div>

        <div>
            <h4>QUICK LINKS</h4>
            <a href="index.php">Home</a>
            <a href="index.php#products-section">Products</a>
            <a href="about.php">About Us</a>
            <a href="custom_request.php">Custom Request</a>
        </div>

        <div>
            <h4>CONTACT</h4>
            <a href="https://wa.me/60194184147"
               target="_blank" rel="noopener">
                WhatsApp Us
            </a>
            <a href="mailto:salamakal@gmail.com">
                Email Us
            </a>
        </div>

    </div>

    <div class="footer-line">
        © 2026 Politeknik Muadzam Shah. All rights reserved.
    </div>

</footer>

<script>
const selectAll = document.getElementById('selectAll');
const itemSelects = Array.from(document.querySelectorAll('.item-select'));
const checkoutLink = document.getElementById('checkoutLink');
const cartWhatsapp = document.getElementById('cartWhatsapp');
const subtotalAmount = document.getElementById('subtotalAmount');
const totalAmount = document.getElementById('totalAmount');
const selectedCount = document.getElementById('selectedCount');

// 1. Fungsi Kemas Kini Status Pilihan & Jumlah Harga
function updateSelection() {
    const selected = itemSelects.filter((checkbox) => checkbox.checked);
    const subtotal = selected.reduce((sum, checkbox) => {
        return sum + Number(checkbox.closest('.cart-item').dataset.price || 0);
    }, 0);
    const total = subtotal;
    const money = (amount) => 'RM ' + amount.toFixed(2);

    if (selectAll) {
        selectAll.checked = selected.length === itemSelects.length && itemSelects.length > 0;
        selectAll.indeterminate = selected.length > 0 && selected.length < itemSelects.length;
    }
    if (selectedCount) {
        selectedCount.textContent = selected.length + ' item' + (selected.length === 1 ? '' : 's') + ' selected';
    }
    if (subtotalAmount) subtotalAmount.textContent = money(subtotal);
    if (totalAmount) totalAmount.textContent = money(total);

    if (checkoutLink) {
        checkoutLink.href = 'order_page.php?' + selected.map((checkbox) => {
            return 'selected[]=' + encodeURIComponent(checkbox.closest('.cart-item').dataset.cartId);
        }).join('&');
        checkoutLink.style.opacity = selected.length ? '1' : '.5';
        checkoutLink.style.pointerEvents = selected.length ? 'auto' : 'none';
    }

    if (cartWhatsapp) {
        cartWhatsapp.disabled = !selected.length;
        cartWhatsapp.style.opacity = selected.length ? '1' : '.5';
        cartWhatsapp.style.cursor = selected.length ? 'pointer' : 'not-allowed';
    }
}

// 2. Acara Pilih Semua (Select All)
if (selectAll) {
    selectAll.addEventListener('change', () => {
        itemSelects.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
        updateSelection();
    });
}

// 3. Acara Pilih Item Individu
itemSelects.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));

// 4. Acara Klik Butang WhatsApp (Diletakkan Secara Berasingan Dan Sentiasa Aktif)
if (cartWhatsapp) {
    cartWhatsapp.addEventListener('click', () => {
        const selected = itemSelects.filter((checkbox) => checkbox.checked);
        
        if (!selected.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Tiada Item Dipilih',
                text: 'Sila pilih sekurang-kurangnya satu item untuk ditanyakan.',
                confirmButtonColor: '#f0208d'
            });
            return;
        }

        const lines = selected.map((checkbox) => {
            const item = checkbox.closest('.cart-item');
            const title = item.querySelector('.item-details h4').textContent.trim();
            const qty = item.querySelector('.qty-input').value;
            const sizeElement = item.querySelector('.item-details p');
            const size = sizeElement ? sizeElement.textContent.trim() : '';
            
            return `• *${title}*\n  - Kuantiti: ${qty}\n  - ${size}`;
        });

        const message = 'Salam & Hi SA Design, saya mahu bertanyakan soalan berkenaan item berikut:\n\n' + lines.join('\n\n');
        
        window.open('https://wa.me/60194184147?text=' + encodeURIComponent(message), '_blank', 'noopener');
    });
}

// Jalankan fungsi awal semasa halaman dimuatkan
updateSelection();

// Match the home-page mobile navigation behaviour.
const menuToggle = document.getElementById('menuToggle');
const navbar = document.querySelector('.navbar');
if (menuToggle && navbar) {
    menuToggle.addEventListener('click', () => {
        const open = navbar.classList.toggle('menu-open');
        menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menuToggle.innerHTML = open
            ? '<i class="fa-solid fa-xmark"></i>'
            : '<i class="fa-solid fa-bars"></i>';
    });

    navbar.querySelectorAll('.nav-links a').forEach((link) => {
        link.addEventListener('click', () => {
            navbar.classList.remove('menu-open');
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
        });
    });
}

// 5. Pengesahan Log Keluar (Logout Confirmation)
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

function confirmDelete(itemId, itemName) {
    Swal.fire({
        title: 'Buang Item?',
        text: `Adakah anda pasti ingin membuang "${itemName}" daripada troli anda?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Buang',
        cancelButtonText: 'Batal',
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
            window.location.href = `cart.php?action=delete&id=${itemId}`;
        }
    });
}
</script>

</body>
</html>
