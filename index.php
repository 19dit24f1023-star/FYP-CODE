<?php
session_start();
include('db.php'); // Sambungan ke pangkalan data

// Ambil jumlah item dalam cart dari session
$total_cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_cart_count += isset($item['quantity']) ? (int)$item['quantity'] : 1;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SA Design | Printing & Advertising</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://cdnjs.cloudflare.com">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

*{margin:0;padding:0;box-sizing:border-box;scroll-behavior:smooth}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f6e8f2;color:#172033;min-height:100vh}
button,input,select{font:inherit}
a{text-decoration:none}
.page-card{width:100%;min-height:100vh;background:#fff;overflow:hidden}

/* NAVBAR */
.navbar{height:84px;padding:16px 48px;display:flex;align-items:center;justify-content:space-between;gap:25px;background:#fff;border-bottom:1px solid #e8ebf2;position:relative;z-index:20}
.nav-logo{display:flex;align-items:center;min-width:max-content}
.logo-text{display:flex;flex-direction:column;gap:2px}
.logo-title{font-size:36px;font-weight:900;line-height:.9;letter-spacing:-2.5px;white-space:nowrap}
.logo-sa{color:#f0208d}
.logo-design{color:#1557d6}
.logo-subtitle{color:#111827;font-size:12px;font-weight:900;letter-spacing:.7px;text-transform:uppercase}
.nav-links{display:flex;align-items:center;justify-content:center;gap:32px;list-style:none}
.nav-links a{color:#111827;font-size:13px;font-weight:800;text-transform:uppercase;transition:.2s}
.nav-links a:hover,.nav-links a.active{color:#f0208d}
.nav-icons{display:flex;align-items:center;gap:10px}
.login-pill{display:inline-flex;align-items:center;gap:7px;padding:10px 15px;border-radius:11px;background:linear-gradient(135deg,#f0208d,#ff3b86);color:#fff;font-size:12px;font-weight:800;text-transform:uppercase;box-shadow:0 8px 18px rgba(239,58,155,.20);transition:.2s}
.login-pill:hover{transform:translateY(-2px)}
.logout-pill{border:0;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif}
.logout-pill:focus{outline:none}

.icon-btn{position:relative;width:42px;height:42px;border:1px solid #f0208d;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#fff;color:#f0208d;transition:.2s}
.icon-btn:hover{transform:translateY(-2px);background:#ffe4f2}
.cart-badge{position:absolute;top:-4px;right:-4px;width:19px;height:19px;border:2px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#f0208d;color:#fff;font-size:9px;font-weight:900}
.menu-toggle{display:none;width:42px;height:42px;border:1px solid #dfe4ef;border-radius:11px;background:#fff;color:#172033;cursor:pointer}

/* HERO */
.hero-stage{position:relative;min-height:500px;display:flex;align-items:center;overflow:hidden;background:linear-gradient(120deg,#fff 0%,#cbd8ff 62%,#c7d5ff 100%)}
.hero-stage:before{content:"";position:absolute;inset:0;background:linear-gradient(135deg,transparent 0 58%,rgba(40,84,197,.20) 58% 62%,transparent 62%),linear-gradient(140deg,transparent 0 66%,rgba(239,58,155,.20) 66% 74%,transparent 74%);z-index:1;pointer-events:none}
.hero-slider{position:absolute;inset:0;z-index:0}
.hero-slide{position:absolute;inset:0;opacity:0;transition:opacity .8s ease}
.hero-slide.active{opacity:1}
.hero-slide img{width:100%;height:100%;object-fit:cover;display:block}
.hero-copy{position:relative;z-index:3;max-width:560px;padding:48px 20px 48px 70px}
.hero-title{font-size:clamp(32px,4vw,56px);line-height:.96;font-weight:900;letter-spacing:-3px;color:#111827;text-transform:uppercase;margin-bottom:15px}
.hero-title .accent{color:#f0208d}
.hero-copy p{max-width:500px;color:#525b6c;font-size:13px;line-height:1.6;margin-bottom:20px}
.hero-actions{display:flex;align-items:center;gap:13px;flex-wrap:wrap}
.btn-secondary{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:14px 25px;border-radius:14px;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;transition:.2s;color:#0f172a;border:2px solid #0f172a;background:rgba(255,255,255,.45)}
.btn-secondary:hover{transform:translateY(-3px);background:#0f172a;color:#fff;box-shadow:0 10px 22px rgba(15,23,42,.18)}
.hero-dots{position:absolute;right:34px;bottom:24px;z-index:4;display:flex;align-items:center;gap:8px}
.hero-dots span{width:10px;height:10px;border-radius:50%;background:rgba(255,255,255,.7);cursor:pointer;transition:.25s}
.hero-dots span.active{width:28px;border-radius:999px;background:#f0208d}

/* ABOUT */
.about-section{padding:58px 70px 70px;background:linear-gradient(180deg,#0b237f,#16318f)}
.section-header{text-align:center;margin-bottom:36px}
.section-badge{display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border-radius:999px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.16);color:#fff;font-size:10px;font-weight:800;letter-spacing:.7px}
.section-header h2{margin-top:11px;font-size:34px;font-weight:900;letter-spacing:-1.2px}
.about-section .section-header h2{color:#fff}
.about-section .section-header p{max-width:700px;margin:6px auto 0;color:rgba(255,255,255,.84);font-size:13px;line-height:1.6}
.about-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.about-card{padding:25px 20px;text-align:center;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:18px;backdrop-filter:blur(5px);transition:.25s}
.about-card:hover{transform:translateY(-5px);background:rgba(255,255,255,.13)}
.about-card i{font-size:26px;color:#fff;margin-bottom:12px}
.about-card h3{color:#fff;font-size:16px;margin-bottom:8px}
.about-card p{color:rgba(255,255,255,.82);font-size:12px;line-height:1.6}

/* SMART FINDER */
.smart-finder-section{padding:58px 70px 25px;background:#fff}
.smart-finder-card{position:relative;overflow:hidden;max-width:1120px;margin:auto;padding:34px 38px;border:1px solid #b7c8ff;border-radius:26px;background:linear-gradient(120deg,#cbd8ff,#d9b9eb 52%,#ffc0dc);box-shadow:0 14px 34px rgba(40,84,197,.20)}
.smart-finder-card:before,.smart-finder-card:after{content:"";position:absolute;border-radius:50%;pointer-events:none}
.smart-finder-card:before{width:180px;height:180px;right:-55px;top:-95px;background:rgba(40,84,197,.20)}
.smart-finder-card:after{width:130px;height:130px;left:-45px;bottom:-80px;background:rgba(239,58,155,.20)}
.smart-finder-head{position:relative;z-index:1;text-align:center;max-width:720px;margin:auto auto 24px}
.finder-badge{display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border-radius:999px;background:#fff;border:1px solid #b7c8ff;color:#1747c7;font-size:10px;font-weight:800;letter-spacing:.7px}
.smart-finder-head h2{margin:12px 0 6px;color:#102f91;font-size:28px;font-weight:900;letter-spacing:-1px}
.smart-finder-head p{color:#64748b;font-size:13px}
.finder-options{position:relative;z-index:1;display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.finder-option{border:1px solid #b7c8ff;background:rgba(255,255,255,.92);border-radius:17px;padding:17px 12px;cursor:pointer;text-align:center;color:#172033;transition:.22s}
.finder-option i{display:block;margin-bottom:8px;font-size:21px;color:#1747c7}
.finder-option strong{display:block;font-size:13px;font-weight:800}
.finder-option span{display:block;margin-top:4px;color:#94a3b8;font-size:10px}
.finder-option:hover{transform:translateY(-3px);border-color:#f0208d;box-shadow:0 10px 22px rgba(239,58,155,.12)}
.finder-option.active{color:#fff;border-color:transparent;background:linear-gradient(135deg,#1747c7,#f0208d);box-shadow:0 12px 24px rgba(40,84,197,.20)}
.finder-option.active i,.finder-option.active span{color:#fff}
.finder-result{position:relative;z-index:1;display:none;margin-top:18px;padding:16px 18px;border-radius:15px;background:rgba(255,255,255,.85);border:1px solid #d5cbe0}
.finder-result.show{display:flex;align-items:center;justify-content:space-between;gap:18px}
.finder-result-text strong{display:block;color:#102f91;font-size:13px;margin-bottom:4px}
.finder-result-text span{color:#64748b;font-size:11px;line-height:1.5}
.finder-view-btn{flex-shrink:0;border:0;border-radius:11px;padding:11px 15px;background:#1747c7;color:#fff;cursor:pointer;font-size:11px;font-weight:800}
.finder-view-btn:hover{background:#f0208d}
.smart-match-label{display:none;position:absolute;right:13px;top:13px;padding:5px 9px;border-radius:999px;background:#f0208d;color:#fff;font-size:9px;font-weight:800;z-index:3}
.product-card.smart-match .smart-match-label{display:block}
.product-card.smart-match{border-color:#f0208d!important;box-shadow:0 0 0 3px rgba(239,58,155,.10),0 18px 34px rgba(239,58,155,.14)!important}

/* PRODUCTS */
.products-section{padding:68px 70px;background:linear-gradient(180deg,#fff,#f1eaf5)}
.products-section .section-header h2{color:#102f91}
.products-section .section-header p{color:#64748b;font-size:13px;margin-top:6px}
.products-section .section-badge{background:#d5dfff;border:1px solid #b7c8ff;color:#1747c7}
.products-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.product-card{position:relative;background:#fff;border:1px solid #e1d8e5;border-radius:18px;overflow:hidden;cursor:pointer;box-shadow:0 8px 20px rgba(28,53,118,.07);transition:.3s}
.product-card:hover{transform:translateY(-7px);border-color:#a9bdf0;box-shadow:0 20px 34px rgba(28,53,118,.15)}
.product-img-box{height:225px;background:#faf4f8;border-bottom:1px solid #e5dfea;overflow:hidden}
.product-img-box img{width:100%;height:100%;object-fit:contain;background:#fff;transition:.5s}
.product-card:hover .product-img-box img{transform:scale(1.06)}
.badge-tag{position:absolute;top:13px;left:13px;padding:5px 10px;border-radius:999px;background:#1747c7;color:#fff;font-size:9px;font-weight:800;letter-spacing:.4px;z-index:2}
.product-details{padding:19px 20px 20px}
.product-cat{color:#1747c7;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.5px}
.product-details h3{min-height:44px;color:#172033;font-size:15px;line-height:1.4;font-weight:800;margin:5px 0 14px}
.product-bottom{display:flex;align-items:center;justify-content:space-between;gap:10px}
.price{color:#e63891;font-size:16px;font-weight:900}
.add-cart-btn{width:42px;height:42px;border:0;border-radius:12px;background:#1747c7;color:#fff;cursor:pointer;transition:.2s}
.add-cart-btn:hover{background:#f0208d;box-shadow:0 8px 16px rgba(239,58,155,.26)}

/* CUSTOM REQUEST */
.custom-request-section{padding:12px 70px 65px;background:#f1eaf5}
.custom-request-card{position:relative;overflow:hidden;min-height:195px;padding:36px 42px;display:flex;align-items:center;justify-content:space-between;gap:30px;border:1px solid #b7c8ff;border-radius:26px;background:linear-gradient(115deg,#e8eeff,#f8ebf4);box-shadow:0 15px 35px rgba(40,84,197,.20)}
.custom-request-card:after{content:"";position:absolute;width:190px;height:190px;right:-72px;top:-98px;border-radius:50%;background:rgba(239,58,155,.12)}
.custom-request-content{position:relative;z-index:2;max-width:760px}
.custom-request-card .section-badge{background:rgba(255,255,255,.8);border:1px solid #b7c8ff;color:#1747c7;margin-bottom:12px}
.custom-request-card h2{color:#102f91;font-size:28px;font-weight:900;line-height:1.2;margin:0 0 9px}
.custom-request-card p{color:#64748b;font-size:13px;line-height:1.6}
.custom-request-card .btn-banner{position:relative;z-index:2;flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;gap:9px;min-width:205px;padding:14px 22px;border-radius:13px;background:linear-gradient(135deg,#1747c7,#3157d5);color:#fff;font-size:12px;font-weight:800;box-shadow:0 12px 25px rgba(40,84,197,.25);transition:.25s}
.custom-request-card .btn-banner:hover{transform:translateY(-4px);background:linear-gradient(135deg,#f0208d,#ff3b86)}

/* FLOATING ACTIONS (DIUBAH KE SEBELAH KIRI) */
.floating-actions{position:fixed;left:22px;bottom:22px;z-index:2500;display:flex;flex-direction:column;align-items:flex-start;gap:9px}
.float-whatsapp,.float-top{font-family:'Plus Jakarta Sans',sans-serif;display:flex;align-items:center;justify-content:center;text-decoration:none;cursor:pointer;transition:.25s;box-shadow:0 12px 28px rgba(15,23,42,.16)}
.float-whatsapp{width:52px;height:52px;padding:0;border:0;border-radius:50%;background:#20d466;color:#fff;gap:0;font-size:22px;font-weight:800}
.float-whatsapp i{font-size:24px}.float-whatsapp:hover{transform:translateY(-3px)}
.float-top{width:40px;height:40px;border:0;border-radius:50%;background:#1747c7;color:#fff;font-size:14px;opacity:0;visibility:hidden;transform:translateY(10px)}
.float-top.show{opacity:1;visibility:visible;transform:translateY(0)}.float-top:hover{background:#f0208d}

/* MODAL */
.login-required-modal{position:fixed;inset:0;z-index:3000;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(17,24,39,.55)}
.login-required-modal.is-open{display:flex}
.login-required-dialog{position:relative;width:min(420px,100%);padding:36px 30px 30px;border-radius:20px;background:#fff;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.2)}
.login-required-dialog>i{display:grid;place-items:center;width:54px;height:54px;margin:0 auto 15px;border-radius:50%;background:#efeeff;color:#5f58c8;font-size:22px}
.login-required-dialog h2{margin-bottom:9px;color:#111827;font-size:22px}.login-required-dialog p{color:#6b7280;font-size:13px;line-height:1.6}
.login-required-close{position:absolute;top:10px;right:13px;border:0;background:transparent;color:#6b7280;font-size:25px;cursor:pointer}
.login-required-button{display:inline-block;margin-top:22px;padding:12px 19px;border-radius:10px;background:#5f58c8;color:#fff;font-size:13px;font-weight:800}

/* FOOTER */
.site-footer{padding:38px 70px;background:#0b1d69;color:#fff}
.footer-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:35px;max-width:1120px;margin:auto}
.site-footer h3{font-size:17px;margin-bottom:9px}.site-footer h4{font-size:12px;margin-bottom:10px}.site-footer p,.site-footer a{color:rgba(255,255,255,.72);font-size:11px;line-height:1.7}.site-footer a{display:block;margin-bottom:4px}.site-footer a:hover{color:#fff}
.footer-brand{font-size:24px;font-weight:900}.footer-brand span{color:#f0208d}.footer-line{max-width:1120px;margin:25px auto 0;padding-top:15px;border-top:1px solid rgba(255,255,255,.14);text-align:center;color:rgba(255,255,255,.6);font-size:10px}

/* RESPONSIVE */
@media(max-width:1100px){
    .navbar{padding:16px 30px}.nav-links{gap:20px}.about-grid{grid-template-columns:repeat(2,1fr)}.products-grid{grid-template-columns:repeat(2,1fr)}
    .hero-copy{padding-left:45px}.smart-finder-section,.products-section{padding-left:40px;padding-right:40px}
}
@media(max-width:760px){
    .navbar{height:auto;min-height:76px;padding:15px 20px;flex-wrap:wrap}.logo-title{font-size:29px}.logo-subtitle{font-size:9px}.menu-toggle{display:flex;align-items:center;justify-content:center}.nav-icons{margin-left:auto}.nav-links{display:none;order:4;width:100%;padding:12px 0 3px;flex-direction:column;align-items:center;gap:14px;border-top:1px solid #eef1f6}.navbar.menu-open .nav-links{display:flex}
    .login-pill span{display:none}.login-pill{width:40px;height:40px;padding:0;justify-content:center}.icon-btn{width:40px;height:40px}
    .hero-stage{min-height:500px}.hero-copy{max-width:100%;padding:45px 24px}.hero-title{font-size:42px;letter-spacing:-2px}.hero-copy p{max-width:360px}.hero-dots{right:20px;bottom:18px}
    .about-section{padding:50px 20px 58px}.about-grid{grid-template-columns:1fr}.section-header h2{font-size:28px}
    .smart-finder-section{padding:45px 20px 20px}.smart-finder-card{padding:28px 18px}.finder-options{grid-template-columns:repeat(2,1fr);gap:10px}.smart-finder-head h2{font-size:23px}.finder-result.show{flex-direction:column;align-items:stretch}.finder-view-btn{width:100%}
    .products-section{padding:52px 20px}.products-grid{grid-template-columns:1fr;gap:18px}.product-img-box{height:220px}
    .custom-request-section{padding:10px 20px 50px}.custom-request-card{flex-direction:column;align-items:flex-start;padding:30px 24px;min-height:auto}.custom-request-card h2{font-size:24px}.custom-request-card .btn-banner{width:100%;min-width:0}
    .floating-actions{left:12px;bottom:12px}.float-whatsapp{width:48px;height:48px;padding:0;font-size:23px}
    .footer-grid{grid-template-columns:1fr 1fr;gap:24px}.footer-brand-wrap{grid-column:1/-1}.site-footer{padding:35px 20px}
}
@media(max-width:430px){
    .hero-title{font-size:35px}.finder-option{padding:14px 8px}.finder-option i{font-size:19px}.finder-option strong{font-size:12px}.finder-option span{font-size:9px}
    .footer-grid{grid-template-columns:1fr}
}

#bp-webchat-container,
  div[class*="bpWebchat"],
  iframe[id*="bp-webchat"] {
    left: 20px !important;
    right: auto !important;
  }
</style>
<link rel="stylesheet" href="ui_polish.css">
</head>
<body>

<div class="page-card">

<nav class="navbar">
    <a class="nav-logo" href="index.php" title="Back to Home">
        <div class="logo-text">
            <span class="logo-title"><span class="logo-sa">SA</span> <span class="logo-design">DESIGN</span></span>
            <span class="logo-subtitle">PRINTING &amp; ADVERTISING</span>
        </div>
    </a>

    <ul class="nav-links" id="mainNav">
        <li><a href="index.php" class="active">Home</a></li>
        <li><a href="#products-section">Product</a></li>
        <li><a href="about.php">About Us</a></li>
        <li><a href="custom_request.php">Custom Request</a></li>
    </ul>

    <div class="nav-icons">
        <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
            <a href="cust_profile.php" class="login-pill" title="My profile">
                <i class="fa-regular fa-user"></i><span>Profile</span>
            </a>
            <button type="button" class="login-pill logout-pill" title="Log out" onclick="confirmLogout()">
                <i class="fa-solid fa-right-from-bracket"></i><span>Log out</span>
            </button>
        <?php else: ?>
            <a href="login.php" class="login-pill" title="Login">
                <i class="fa-regular fa-user"></i><span>Login</span>
            </a>
        <?php endif; ?>
        <a href="cart.php" class="icon-btn" title="Cart" id="cartBtn" aria-label="Shopping cart">
            <i class="fa-solid fa-bag-shopping"></i>
            <span class="cart-badge" id="cartBadgeCount"><?= $total_cart_count ?></span>
        </a>
        <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
            <a href="wishlist.php" class="icon-btn" title="Wishlist" aria-label="My wishlist"><i class="fa-regular fa-heart"></i></a>
        <?php endif; ?>
    </div>
    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>
</nav>

<section class="hero-stage">
    <div class="hero-slider">
        <div class="hero-slide active"><img src="/product_images/banner1.png" alt="SA Design printing showcase"></div>
        <div class="hero-slide"><img src="/product_images/banner2.png" alt="SA Design design showcase"></div>
    </div>

    <div class="hero-copy">
        <div class="hero-title">We Print<br><span class="accent">Your Ideas</span><br>We Advertise<br>Your Business</div>
        <p>We empower businesses, designers, and enthusiasts to transform their concepts into tangible, high-quality prints and advertising materials.</p>
        <div class="hero-actions">
            <a href="#products-section" class="btn-secondary">View Products <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>

    <div class="hero-dots" aria-label="Hero slides">
        <span class="active" data-index="0"></span>
        <span data-index="1"></span>
    </div>
</section>

<section class="about-section" id="why-choose-us">
    <div class="section-header">
        <span class="section-badge"><i class="fa-solid fa-sparkles"></i> WHY CHOOSE US</span>
        <h2>Why Choose SA Design?</h2>
        <p>We deliver premium printing and advertising solutions with fast service and excellent quality.</p>
    </div>
    <div class="about-grid">
        <div class="about-card"><i class="fa-solid fa-gem"></i><h3>Premium Quality</h3><p>We use quality materials and reliable printing technology.</p></div>
        <div class="about-card"><i class="fa-solid fa-truck-fast"></i><h3>Fast Delivery</h3><p>Fast and organised service for your important deadlines.</p></div>
        <div class="about-card"><i class="fa-solid fa-headset"></i><h3>Friendly Support</h3><p>Our team is ready to help you choose the right solution.</p></div>
        <div class="about-card"><i class="fa-solid fa-shield-heart"></i><h3>Affordable Price</h3><p>Competitive pricing without compromising print quality.</p></div>
    </div>
</section>

<section class="smart-finder-section" id="smart-product-finder">
    <div class="smart-finder-card">
        <div class="smart-finder-head">
            <span class="finder-badge"><i class="fa-solid fa-wand-magic-sparkles"></i> SMART PRODUCT FINDER</span>
            <h2>What are you printing for?</h2>
            <p>Choose your purpose and we’ll recommend products that fit your needs.</p>
        </div>
        <div class="finder-options">
            <button type="button" class="finder-option" data-finder="business"><i class="fa-solid fa-briefcase"></i><strong>Business</strong><span>Brand &amp; promote</span></button>
            <button type="button" class="finder-option" data-finder="event"><i class="fa-solid fa-champagne-glasses"></i><strong>Event</strong><span>Celebrate &amp; decorate</span></button>
            <button type="button" class="finder-option" data-finder="school"><i class="fa-solid fa-school"></i><strong>School</strong><span>Activities &amp; teams</span></button>
            <button type="button" class="finder-option" data-finder="personal"><i class="fa-solid fa-user"></i><strong>Personal</strong><span>For your own ideas</span></button>
        </div>
        <div class="finder-result" id="finderResult">
            <div class="finder-result-text">
                <strong id="finderResultTitle">Recommended for you</strong>
                <span id="finderResultText">We found suitable products below.</span>
            </div>
            <button type="button" class="finder-view-btn" id="finderViewProducts">View Recommendations <i class="fa-solid fa-arrow-down"></i></button>
        </div>
    </div>
</section>

<section class="products-section" id="products-section">
    <div class="section-header">
        <span class="section-badge"><i class="fa-solid fa-crown"></i> TOP SELECTION</span>
        <h2>Featured Products</h2>
        <p>High quality custom prints designed for your business &amp; event needs.</p>
    </div>

    <!-- SEKSYEN PRODUK DINAMIK (DISAMBUNGKAN KE DATABASE MANAGE SERVICES) -->
    <div class="products-grid">
        <?php
        // Ambil perkhidmatan/produk dari pangkalan data yang berstatus Active
        $query_prod = mysqli_query($conn, "SELECT * FROM products WHERE LOWER(status) = 'active' ORDER BY id DESC");

        if ($query_prod && mysqli_num_rows($query_prod) > 0):
            while ($prod = mysqli_fetch_assoc($query_prod)):
                // Penetapan tagging Smart Product Finder berdasarkan kategori
                $cat_lower = strtolower($prod['category'] ?? '');
                $data_use = "business,event,school,personal";
                if (strpos($cat_lower, 'sportswear') !== false) {
                    $data_use = "school,event,personal";
                } elseif (strpos($cat_lower, 'stationery') !== false) {
                    $data_use = "business,school,personal";
                } elseif (strpos($cat_lower, 'large format') !== false || strpos($cat_lower, 'outdoor') !== false) {
                    $data_use = "business,event,school";
                }
        ?>
            <div class="product-card" onclick="location.href='product.php?id=<?php echo $prod['id']; ?>'" data-use="<?php echo $data_use; ?>">
                <span class="smart-match-label">RECOMMENDED</span>
                <div class="product-img-box">
                    <img src="<?php echo htmlspecialchars(!empty($prod['image']) ? $prod['image'] : 'product_images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($prod['name'] ?? $prod['service_name'] ?? 'Product'); ?>">
                    <?php if (!empty($prod['badge'])): ?>
                        <span class="badge-tag"><?php echo htmlspecialchars($prod['badge']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="product-details">
                    <p class="product-cat"><?php echo htmlspecialchars($prod['category'] ?? 'Printing Services'); ?></p>
                    <h3><?php echo htmlspecialchars($prod['name'] ?? $prod['service_name'] ?? 'Custom Service'); ?></h3>
                    <div class="product-bottom">
                        <span class="price">
                            <?php 
                            if (!empty($prod['price_range'])) {
                                echo htmlspecialchars($prod['price_range']);
                            } else {
                                echo 'RM ' . number_format((float)($prod['price'] ?? 0), 2);
                            }
                            ?>
                        </span>
                        <form action="add_to_cart.php" method="POST" onclick="event.stopPropagation();">
                            <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($prod['name'] ?? $prod['service_name'] ?? ''); ?>">
                            <input type="hidden" name="product_price" value="<?php echo $prod['price'] ?? '0.00'; ?>">
                            <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($prod['image'] ?? ''); ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" name="add_to_cart" class="add-cart-btn" aria-label="Add to cart">
                                <i class="fa-solid fa-cart-plus"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php 
            endwhile;
        else:
        ?>
            <p style="grid-column: 1/-1; text-align: center; color: #64748b; padding: 40px 0;">Tiada produk aktif dipaparkan pada masa ini.</p>
        <?php endif; ?>
    </div>
</section>

<section class="custom-request-section" id="custom-request">
    <div class="custom-request-card">
        <div class="custom-request-content">
            <span class="section-badge"><i class="fa-solid fa-palette"></i> CUSTOM REQUEST</span>
            <h2>Need a special design or bulk order?</h2>
            <p>Send us your idea and we will help you create the perfect print product.</p>
        </div>
        <a href="custom_request.php" class="btn-banner"><i class="fa-solid fa-pen-ruler"></i> Start Custom Request</a>
    </div>
</section>

</div>

<div class="floating-actions" aria-label="Quick actions">
    <a href="https://wa.me/60194184147" target="_blank" rel="noopener" class="float-whatsapp" title="Chat with us">
        <i class="fa-brands fa-whatsapp"></i>
    </a>
    <button type="button" class="float-top" id="backToTop" title="Back to top" aria-label="Back to top">
        <i class="fa-solid fa-arrow-up"></i>
    </button>
</div>

<div id="loginRequiredModal" class="login-required-modal" aria-hidden="true">
    <div class="login-required-dialog" role="dialog" aria-modal="true" aria-labelledby="loginRequiredTitle">
        <button type="button" class="login-required-close" aria-label="Close">&times;</button>
        <i class="fa-solid fa-lock"></i>
        <h2 id="loginRequiredTitle">Login required</h2>
        <p>Please log in or create an account before adding items to your cart or proceeding to checkout.</p>
        <a href="login.php" class="login-required-button">Login or Register</a>
    </div>
</div>

<footer class="site-footer">
    <div class="footer-grid">
        <div class="footer-brand-wrap">
            <div class="footer-brand"><span>SA</span> DESIGN</div>
            <p>Printing &amp; Advertising</p>
            <p>Quality printing solutions for business, events, school and personal needs.</p>
        </div>
        <div>
            <h4>QUICK LINKS</h4>
            <a href="index.php">Home</a>
            <a href="#products-section">Products</a>
            <a href="about.php">About Us</a>
            <a href="custom_request.php">Custom Request</a>
        </div>
        <div>
            <h4>CONTACT</h4>
            <a href="https://wa.me/60194184147" target="_blank" rel="noopener">WhatsApp Us</a>
            <a href="custom_request.php">Request a Quote</a>
        </div>
    </div>
    <div class="footer-line">© 2026 Politeknik Muadzam Shah. All rights reserved.</div>
</footer>

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
</script>

<style>
.logout-popup { border-radius: 18px !important; padding: 28px !important; }
.logout-title { color: #102f91 !important; font-family: 'Plus Jakarta Sans', Arial, sans-serif !important; font-size: 24px !important; font-weight: 800 !important; }
.logout-text { color: #64748b !important; font-family: 'Plus Jakarta Sans', Arial, sans-serif !important; font-size: 13px !important; }
.logout-confirm, .logout-cancel { border-radius: 10px !important; padding: 10px 18px !important; font-family: 'Plus Jakarta Sans', Arial, sans-serif !important; font-size: 12px !important; font-weight: 800 !important; }
</style>

<script>
const isLoggedIn = <?= isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true ? 'true' : 'false' ?>;
const loginRequiredModal = document.getElementById('loginRequiredModal');
const showLoginRequiredModal = () => {
    loginRequiredModal.classList.add('is-open');
    loginRequiredModal.setAttribute('aria-hidden','false');
};
const closeLoginRequiredModal = () => {
    loginRequiredModal.classList.remove('is-open');
    loginRequiredModal.setAttribute('aria-hidden','true');
};

if (!isLoggedIn) {
    document.querySelectorAll('form[action="add_to_cart.php"]').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            showLoginRequiredModal();
        });
    });
    document.getElementById('cartBtn').addEventListener('click', event => {
        event.preventDefault();
        showLoginRequiredModal();
    });
}
document.querySelector('.login-required-close').addEventListener('click', closeLoginRequiredModal);
loginRequiredModal.addEventListener('click', event => {
    if(event.target === loginRequiredModal) closeLoginRequiredModal();
});

const menuToggle = document.getElementById('menuToggle');
const navbar = document.querySelector('.navbar');
menuToggle.addEventListener('click', () => {
    const open = navbar.classList.toggle('menu-open');
    menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    menuToggle.innerHTML = open
        ? '<i class="fa-solid fa-xmark"></i>'
        : '<i class="fa-solid fa-bars"></i>';
});

document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => navbar.classList.remove('menu-open'));
});

const heroSlides = document.querySelectorAll('.hero-slide');
const heroDots = document.querySelectorAll('.hero-dots span');
let heroIndex = 0;
let heroTimer;

function showHeroSlide(index){
    heroSlides.forEach((slide,i) => slide.classList.toggle('active',i === index));
    heroDots.forEach((dot,i) => dot.classList.toggle('active',i === index));
    heroIndex = index;
}
function nextHeroSlide(){
    showHeroSlide((heroIndex + 1) % heroSlides.length);
}
heroDots.forEach(dot => {
    dot.addEventListener('click', () => {
        showHeroSlide(Number(dot.dataset.index));
        clearInterval(heroTimer);
        heroTimer = setInterval(nextHeroSlide,4500);
    });
});
heroTimer = setInterval(nextHeroSlide,4500);

const backToTop = document.getElementById('backToTop');
window.addEventListener('scroll',() => {
    backToTop.classList.toggle('show',window.scrollY > 450);
});
backToTop.addEventListener('click',() => window.scrollTo({top:0,behavior:'smooth'}));

const finderOptions = document.querySelectorAll('.finder-option');
const finderResult = document.getElementById('finderResult');
const finderResultTitle = document.getElementById('finderResultTitle');
const finderResultText = document.getElementById('finderResultText');
const finderViewProducts = document.getElementById('finderViewProducts');
const productCards = document.querySelectorAll('.product-card');

const recommendations = {
    business:{
        title:'Best for Business',
        text:'Self Ink, Cards, Banner, Sticker and Windflag are recommended for branding and promotion.'
    },
    event:{
        title:'Best for Events',
        text:'Banner, Cards, Windflag and Sticker are great for events and special occasions.'
    },
    school:{
        title:'Best for School',
        text:'Sublimation Shirts, Banner, Self Ink and Sticker are suitable for school activities and teams.'
    },
    personal:{
        title:'Best for Personal',
        text:'Cards, Sublimation, Self Ink and Sticker are suitable for personal projects and gifts.'
    }
};

finderOptions.forEach(option => {
    option.addEventListener('click',() => {
        const type = option.dataset.finder;
        const recommendation = recommendations[type];
        finderOptions.forEach(btn => btn.classList.remove('active'));
        option.classList.add('active');

        productCards.forEach(card => {
            const uses = (card.dataset.use || '').split(',');
            card.classList.toggle('smart-match',uses.includes(type));
        });

        finderResultTitle.textContent = recommendation.title;
        finderResultText.textContent = recommendation.text;
        finderResult.classList.add('show');
    });
});

finderViewProducts.addEventListener('click',() => {
    document.getElementById('products-section').scrollIntoView({behavior:'smooth',block:'start'});
});
</script>

<script src="https://cdn.botpress.cloud/webchat/v5.0/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/08/27/03/20260827030653-0KCJM3IK.js" defer></script>

<script>
  window.botpress.on("webchat:ready", () => {
    window.botpress.config({
      composerPlaceholder: "Type your message...",
      botName: "SA Design AI",
      // Set kedudukan di sebelah kiri
      style: {
        position: "left"
      }
    });
  });
</script>

</body>
</html>