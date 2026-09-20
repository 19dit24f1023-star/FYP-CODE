<?php
session_start();
include('db.php');

/* =========================================================
   ADMIN ACCESS PROTECTION
========================================================= */
if (
    empty($_SESSION['is_logged_in']) ||
    empty($_SESSION['is_admin']) ||
    $_SESSION['is_admin'] !== true ||
    empty($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin' ||
    empty($_SESSION['admin_id'])
) {
    $_SESSION['login_notice'] = 'Access denied. Admin login is required.';
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name         = mysqli_real_escape_string($conn, $_POST['service_name']);
    $category     = mysqli_real_escape_string($conn, $_POST['category']);
    $price        = mysqli_real_escape_string($conn, $_POST['price']);
    $description  = mysqli_real_escape_string($conn, $_POST['description']);
    $status       = mysqli_real_escape_string($conn, $_POST['status']);

    // Proses Muat Naik Gambar
    $image_name = $_FILES['service_image']['name'];
    $target_dir = "uploads/";
    
    // Buat folder uploads jika tiada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = "";
    if (!empty($image_name)) {
        $target_file = $target_dir . time() . '_' . basename($image_name);
        move_uploaded_file($_FILES['service_image']['tmp_name'], $target_file);
    }

    // Insert ke jadual 'products'
    $query = "INSERT INTO products (name, category, price, description, image, status) 
              VALUES ('$name', '$category', '$price', '$description', '$target_file', '$status')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['msg'] = "Service successfully added!";
        echo "<script>alert('Service successfully added!'); window.location.href='ManageService.php';</script>";
        exit();
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="ui_polish.css">
    <title>Add New Service | SA Design</title>

    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
    /* =========================================================
       COLOR SYSTEM (Matches Admin Dashboard & ViewPage)
    ========================================================= */
    :root {
        --pink: #E98BAA;
        --pink-dark: #D97899;
        --pink-light: #FCE8EF;
        --pink-pale: #FFF5F8;

        --blue: #4F8FA8;
        --blue-dark: #39758D;
        --blue-light: #DFF3FA;
        --blue-pale: #EFF9FC;

        --dark-text: #304A56;
        --blue-text: #39758D;
        --gray-text: #7C929C;

        --light-gray: #EAF6FB;
        --border: #D7EAF0;
        --white: #FFFFFF;

        --shadow: 0 10px 35px rgba(79, 143, 168, 0.10);
    }

    /* =========================================================
       GLOBAL
    ========================================================= */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        background: var(--light-gray);
        color: var(--dark-text);
        min-height: 100vh;
    }

    a {
        text-decoration: none;
    }

    button {
        font-family: inherit;
    }

    /* =========================================================
       SCROLLBAR
    ========================================================= */
    ::-webkit-scrollbar {
        width: 7px;
    }

    ::-webkit-scrollbar-track {
        background: #EEF8FB;
    }

    ::-webkit-scrollbar-thumb {
        background: #B9DDE8;
        border-radius: 20px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--pink);
    }

    /* =========================================================
       SIDEBAR
    ========================================================= */
    .sidebar {
        position: fixed;
        top: 22px;
        left: 22px;
        width: 245px;
        height: calc(100vh - 44px);
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 24px;
        display: flex;
        flex-direction: column;
        z-index: 1000;
        overflow: hidden;
        box-shadow: 0 18px 45px rgba(79, 143, 168, 0.14);
    }

    .sidebar-brand {
        padding: 27px 25px 25px;
        border-bottom: 1px solid var(--border);
    }

    .brand-main {
        font-size: 25px;
        font-weight: 900;
        letter-spacing: -1.2px;
        color: #EF228B;
    }

    .brand-main span {
        color: #1C6EF2;
    }

    .brand-sub {
        margin-top: 5px;
        font-size: 8px;
        font-weight: 800;
        letter-spacing: 1.25px;
        color: #263238;
    }

    .menu-label {
        padding: 27px 24px 10px;
        color: #9AAEB7;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.4px;
    }

    .sidebar-menu {
        list-style: none;
        padding: 4px 13px;
        margin: 0;
        flex: 1;
        overflow-y: auto;
    }

    .sidebar-menu li {
        margin-bottom: 6px;
    }

    .sidebar-menu li a {
        display: flex;
        align-items: center;
        gap: 13px;
        min-height: 48px;
        padding: 11px 14px;
        color: #718A95;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.25s ease;
    }

    .sidebar-menu li a i {
        width: 23px;
        text-align: center;
        font-size: 16px;
        color: var(--blue);
    }

    .sidebar-menu li a:hover {
        background: var(--pink-light);
        color: var(--pink);
        transform: translateX(3px);
    }

    .sidebar-menu li a:hover i {
        color: var(--pink);
    }

    .sidebar-menu li.active a {
        background: linear-gradient(135deg, var(--pink-light), var(--blue-pale));
        color: var(--pink);
        box-shadow: 0 7px 20px rgba(233, 139, 170, 0.10);
    }

    .sidebar-menu li.active a i {
        color: var(--pink);
    }

    .admin-mini-card {
        margin: 12px 14px;
        padding: 13px;
        background: var(--blue-pale);
        border: 1px solid var(--border);
        border-radius: 15px;
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .admin-avatar {
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        border-radius: 50%;
        background: var(--pink-light);
        color: var(--pink);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        border: 2px solid #FFFFFF;
    }

    .admin-info {
        min-width: 0;
    }

    .admin-name {
        color: var(--dark-text);
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .admin-role {
        color: var(--gray-text);
        font-size: 10px;
        margin-top: 3px;
    }

    .sidebar-footer {
        padding: 0 14px 16px;
    }

    .logout-btn {
        display: flex;
        align-items: center;
        gap: 13px;
        min-height: 47px;
        padding: 11px 14px;
        color: var(--pink);
        background: var(--pink-light);
        border: 1px solid #F6D6E1;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
        width: 100%;
        text-align: left;
    }

    .logout-btn i {
        color: var(--pink);
    }

    .logout-btn:hover {
        background: var(--pink);
        color: #FFFFFF;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(233, 139, 170, 0.20);
    }

    .logout-btn:hover i {
        color: #FFFFFF;
    }

    /* =========================================================
       MAIN CONTENT AREA
    ========================================================= */
    .main-content {
        margin-left: 289px;
        padding: 34px 38px 50px;
        min-height: 100vh;
    }

    .top-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 25px;
    }

    .page-heading {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .welcome-text {
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-text);
    }

    .page-title {
        font-size: 29px;
        font-weight: 800;
        letter-spacing: -0.8px;
        color: var(--blue-dark);
    }

    .page-subtitle {
        font-size: 13px;
        color: var(--gray-text);
    }

    .status-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 15px;
        background: #FFFFFF;
        border: 1px solid var(--border);
        border-radius: 30px;
        color: #62747D;
        font-size: 11px;
        font-weight: 600;
        box-shadow: 0 5px 18px rgba(79,143,168,0.06);
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #76C7A5;
        box-shadow: 0 0 0 4px #E2F5EC;
    }

    /* =========================================================
       FORM CARD STYLE
    ========================================================= */
    .form-container {
        max-width: 780px;
        margin: 0 auto;
    }

    .table-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #EEF3F5;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .table-heading {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .table-heading-icon {
        width: 39px;
        height: 39px;
        border-radius: 50%;
        background: var(--pink-light);
        color: var(--pink);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }

    .table-title {
        font-size: 15px;
        font-weight: 750;
        color: var(--blue-dark);
    }

    .table-description {
        font-size: 10px;
        color: var(--gray-text);
        margin-top: 3px;
    }

    .card-body {
        padding: 28px 30px;
    }

    .form-group {
        margin-bottom: 22px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 700;
        color: var(--dark-text);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    input[type="text"],
    input[type="number"],
    select,
    textarea {
        width: 100%;
        padding: 12px 16px;
        font-family: inherit;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--blue-pale);
        color: var(--dark-text);
        font-size: 13px;
        outline: none;
        font-weight: 600;
        transition: all 0.25s ease;
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    select:focus,
    textarea:focus {
        border-color: var(--pink);
        background: var(--white);
        box-shadow: 0 0 0 3px rgba(233, 139, 170, 0.15);
    }

    textarea {
        height: 120px;
        resize: vertical;
    }

    input[type="file"] {
        width: 100%;
        padding: 12px;
        border: 1px dashed #B9DDE8;
        background: var(--blue-pale);
        border-radius: 12px;
        cursor: pointer;
        font-size: 12px;
        color: var(--dark-text);
    }

    /* RADIO BUTTONS */
    .radio-group {
        display: flex;
        gap: 25px;
        align-items: center;
        margin-top: 6px;
    }

    .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        color: var(--dark-text);
        text-transform: none;
        letter-spacing: normal;
    }

    .radio-option input[type="radio"] {
        accent-color: var(--pink);
        width: 17px;
        height: 17px;
        cursor: pointer;
    }

    /* ACTION BUTTONS TOOLBAR */
    .button-group {
        display: flex;
        gap: 12px;
        margin-top: 32px;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 12px 24px;
        border-radius: 12px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 750;
        cursor: pointer;
        border: none;
        transition: all 0.25s ease;
    }

    .btn-save {
        background: var(--pink);
        color: #FFFFFF;
        box-shadow: 0 6px 18px rgba(233, 139, 170, 0.25);
    }

    .btn-save:hover {
        background: var(--pink-dark);
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(233, 139, 170, 0.35);
    }

    .btn-cancel {
        background: var(--white);
        color: var(--blue-dark);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
    }

    .btn-cancel:hover {
        background: var(--blue-pale);
        color: var(--blue);
        transform: translateY(-2px);
    }

    /* =========================================================
       LOGOUT CONFIRMATION MODAL
    ========================================================= */
    .logout-modal {
        position: fixed;
        inset: 0;
        width: 100%;
        height: 100%;
        background: rgba(43, 66, 76, 0.52);
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
    }

    .logout-modal.show {
        opacity: 1;
        pointer-events: auto;
    }

    .modal-box {
        width: 100%;
        max-width: 390px;
        padding: 29px;
        background: #FFFFFF;
        border-radius: 18px;
        text-align: center;
        box-shadow: 0 25px 60px rgba(40,70,80,0.20);
        transform: scale(0.92);
        transition: transform 0.25s ease;
    }

    .logout-modal.show .modal-box {
        transform: scale(1);
    }

    .modal-icon {
        width: 58px;
        height: 58px;
        margin: 0 auto 15px;
        border-radius: 50%;
        background: var(--pink-light);
        color: var(--pink);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 750;
        color: var(--blue-dark);
        margin-bottom: 7px;
    }

    .modal-text {
        font-size: 12px;
        color: var(--gray-text);
        line-height: 1.6;
        margin-bottom: 22px;
    }

    .modal-buttons {
        display: flex;
        gap: 10px;
    }

    .btn-confirm-logout,
    .btn-cancel-logout {
        flex: 1;
        border: none;
        padding: 11px 18px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-confirm-logout {
        background: var(--pink);
        color: #FFFFFF;
    }

    .btn-confirm-logout:hover {
        background: var(--pink-dark);
    }

    .btn-cancel-logout {
        background: var(--blue-pale);
        color: var(--blue-dark);
    }

    .btn-cancel-logout:hover {
        background: var(--blue-light);
    }

    /* =========================================================
       RESPONSIVE BREAKPOINTS
    ========================================================= */
    @media (max-width: 1250px) {
        .sidebar { width: 225px; }
        .main-content { margin-left: 267px; padding-left: 28px; padding-right: 28px; }
    }

    @media (max-width: 1050px) {
        .sidebar { width: 215px; left: 16px; }
        .main-content { margin-left: 247px; padding: 28px 22px 40px; }
    }

    @media (max-width: 750px) {
        .sidebar {
            position: relative;
            top: auto;
            left: auto;
            width: 100%;
            height: auto;
            min-height: auto;
            border-radius: 0;
            margin: 0;
        }
        .sidebar-menu { display: grid; grid-template-columns: repeat(2, 1fr); gap: 5px; flex: none; }
        .sidebar-menu li { margin-bottom: 0; }
        .admin-mini-card { margin-top: 14px; }
        .main-content { margin-left: 0; padding: 24px 18px 35px; }
        .top-header { flex-direction: column; align-items: flex-start; gap: 13px; }
        .status-pill { width: 100%; justify-content: center; }
    }

    @media (max-width: 520px) {
        .sidebar-menu { grid-template-columns: 1fr; }
        .brand-main { font-size: 23px; }
        .page-title { font-size: 25px; }
    }
    </style>
</head>

<body>

<!-- =========================================================
     SIDEBAR NAVIGATION
========================================================= -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-main">SA<span>DESIGN</span></div>
        <div class="brand-sub">PRINTING &amp; ADVERTISING</div>
    </div>

    <div class="menu-label">Main Menu</div>

    <ul class="sidebar-menu">
        <li>
            <a href="admin_dashboard.php">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="manage_orders.php">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span>Manage Orders</span>
            </a>
        </li>
        <li class="active">
            <a href="ManageService.php">
                <i class="fa-solid fa-layer-group"></i>
                <span>Manage Services</span>
            </a>
        </li>
        <li>
            <a href="StaffPage.php">
                <i class="fa-solid fa-users"></i>
                <span>Staff</span>
            </a>
        </li>
        <li>
            <a href="AdminProfile.php">
                <i class="fa-solid fa-user-gear"></i>
                <span>Profile</span>
            </a>
        </li>
    </ul>

    <div class="admin-mini-card">
        <div class="admin-avatar">
            <i class="fa-solid fa-user"></i>
        </div>
        <div class="admin-info">
            <div class="admin-name">Administrator</div>
            <div class="admin-role">System Admin</div>
        </div>
    </div>

    <div class="sidebar-footer">
        <button type="button" onclick="openLogoutModal()" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </button>
    </div>
</aside>

<!-- =========================================================
     MAIN CONTENT AREA
========================================================= -->
<main class="main-content">

    <!-- TOP HEADER -->
    <div class="top-header">
        <div class="page-heading">
            <span class="welcome-text">Service Catalog Control 👋</span>
            <h1 class="page-title">Add New Service</h1>
            <p class="page-subtitle">Register a new offering or printable item to the store catalogue.</p>
        </div>
        <div class="header-right">
            <div class="status-pill">
                <span class="status-dot"></span> System Online
            </div>
        </div>
    </div>

    <!-- FORM CONTAINER -->
    <div class="form-container">
        <div class="table-card">
            <div class="table-header">
                <div class="table-heading">
                    <div class="table-heading-icon"><i class="fa-solid fa-plus-circle"></i></div>
                    <div>
                        <div class="table-title">Service Details</div>
                        <div class="table-description">Fill in the specification details for the new service</div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form action="Add.php" method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label>Service Name</label>
                        <input type="text" name="service_name" placeholder="e.g. Banner Printing" required>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="" disabled selected>Choose Category</option>
                            <option value="Stationery">Stationery</option>
                            <option value="Sportswear">Sportswear</option>
                            <option value="Large Format Printing">Large Format Printing</option>
                            <option value="Event Printing">Event Printing</option>
                            <option value="Outdoor Advertising">Outdoor Advertising</option>
                            <option value="Labels & Stickers">Labels & Stickers</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Price (RM)</label>
                        <input type="number" step="0.01" name="price" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Enter service details or description..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Upload Image</label>
                        <input type="file" name="service_image" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="status" value="Active" checked> Active
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="status" value="Inactive"> Inactive
                            </label>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn-action btn-save">
                            <i class="fa-solid fa-floppy-disk"></i> Save Service
                        </button>
                        <a href="ManageService.php" class="btn-action btn-cancel">
                            <i class="fa-solid fa-xmark"></i> Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

</main>

<!-- =========================================================
     LOGOUT CONFIRMATION MODAL
========================================================= -->
<div id="logoutConfirmationModal" class="logout-modal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 class="modal-title">Confirm Logout</h3>
        <p class="modal-text">Are you sure you want to log out of your administration panel?</p>
        <div class="modal-buttons">
            <button type="button" onclick="closeLogoutModal()" class="btn-cancel-logout">Cancel</button>
            <button type="button" onclick="executeLogout()" class="btn-confirm-logout">Logout</button>
        </div>
    </div>
</div>

<script>
    // LOGOUT MODAL CONTROLLER
    const modalElement = document.getElementById('logoutConfirmationModal');

    function openLogoutModal() {
        modalElement.classList.add('show');
    }

    function closeLogoutModal() {
        modalElement.classList.remove('show');
    }

    function executeLogout() {
        window.location.href = 'admin_logout.php';
    }

    window.onclick = function(event) {
        if (event.target === modalElement) {
            closeLogoutModal();
        }
    };

    <?php if (!empty($message)): ?>
        alert(<?php echo json_encode($message); ?>);
    <?php endif; ?>
</script>

</body>
</html>
