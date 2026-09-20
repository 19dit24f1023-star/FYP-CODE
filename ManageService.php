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

/* =========================================================
   ADD SERVICE PROCESS
========================================================= */
if (isset($_POST['add_service'])) {
    $name          = trim($_POST['service_name']);
    $category      = trim($_POST['category']);
    $badge         = trim($_POST['badge']);
    $price         = floatval($_POST['price']);
    $description   = trim($_POST['description']);
    $minimum_order = trim($_POST['minimum_order']);
    $status        = trim($_POST['status']);
    $image_path    = "";

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed        = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_extension, $allowed)) {
            $new_filename = time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $image_path   = $target_dir . $new_filename;
            move_uploaded_file($_FILES["image"]["tmp_name"], $image_path);
        }
    }

    $stmt = $conn->prepare("INSERT INTO products (name, category, badge, price, description, minimum_order, status, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdssss", $name, $category, $badge, $price, $description, $minimum_order, $status, $image_path);
    
    if ($stmt->execute()) {
        echo "<script>alert('Service added successfully!'); window.location.href='ManageService.php';</script>";
        exit();
    }
}

/* =========================================================
   UPDATE SERVICE PROCESS
========================================================= */
if (isset($_POST['update_service'])) {
    $id            = intval($_POST['service_id']);
    $name          = trim($_POST['service_name']);
    $category      = trim($_POST['category']);
    $badge         = trim($_POST['badge']);
    $price         = floatval($_POST['price']);
    $description   = trim($_POST['description']);
    $minimum_order = trim($_POST['minimum_order']);
    $status        = trim($_POST['status']);

    $stmt_old = $conn->prepare("SELECT image, gallery FROM products WHERE id = ?");
    $stmt_old->bind_param("i", $id);
    $stmt_old->execute();
    $old_data = $stmt_old->get_result()->fetch_assoc();

    $image_path = $old_data['image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed        = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_extension, $allowed)) {
            if (!empty($old_data['image']) && file_exists($old_data['image'])) {
                unlink($old_data['image']);
            }
            $new_filename = time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $image_path   = $target_dir . $new_filename;
            move_uploaded_file($_FILES["image"]["tmp_name"], $image_path);
        }
    }

    $features_arr = [];
    if (!empty($_POST['features'])) {
        foreach ($_POST['features'] as $feat) {
            $feat_clean = trim($feat);
            if (!empty($feat_clean)) {
                $features_arr[] = $feat_clean;
            }
        }
    }
    $features_json = json_encode($features_arr, JSON_UNESCAPED_UNICODE);

    $specs_arr = [];
    if (!empty($_POST['spec_names']) && !empty($_POST['spec_values'])) {
        $spec_names  = $_POST['spec_names'];
        $spec_values = $_POST['spec_values'];
        for ($i = 0; $i < count($spec_names); $i++) {
            $s_name = trim($spec_names[$i]);
            $s_val  = trim($spec_values[$i]);
            if (!empty($s_name) && !empty($s_val)) {
                $specs_arr[$s_name] = $s_val;
            }
        }
    }
    $specifications_json = json_encode($specs_arr, JSON_UNESCAPED_UNICODE);

    $options_arr = [];
    if (!empty($_POST['option_names']) && isset($_POST['option_prices'])) {
        $opt_names  = $_POST['option_names'];
        $opt_prices = $_POST['option_prices'];
        for ($i = 0; $i < count($opt_names); $i++) {
            $o_n = trim($opt_names[$i]);
            $o_p = floatval($opt_prices[$i]);
            if (!empty($o_n)) {
                $options_arr[] = ['name' => $o_n, 'price' => $o_p];
            }
        }
    }
    $options_json = json_encode($options_arr, JSON_UNESCAPED_UNICODE);

    $gallery_arr = json_decode($old_data['gallery'] ?? '[]', true) ?: [];
    if (isset($_FILES['gallery_images'])) {
        $target_dir = "uploads/gallery/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['gallery_images']['error'][$key] == 0) {
                $ext = strtolower(pathinfo($_FILES['gallery_images']['name'][$key], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $g_filename  = time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $g_path      = $target_dir . $g_filename;
                    if (move_uploaded_file($tmp_name, $g_path)) {
                        $gallery_arr[] = $g_path;
                    }
                }
            }
        }
    }
    $gallery_json = json_encode($gallery_arr, JSON_UNESCAPED_UNICODE);

    $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, badge = ?, price = ?, description = ?, features = ?, specifications = ?, gallery = ?, options = ?, minimum_order = ?, status = ?, image = ? WHERE id = ?");
    $stmt->bind_param("sssdssssssssi", $name, $category, $badge, $price, $description, $features_json, $specifications_json, $gallery_json, $options_json, $minimum_order, $status, $image_path, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Service updated successfully!'); window.location.href='ManageService.php';</script>";
        exit();
    }
}

/* =========================================================
   DELETE SERVICE PROCESS
========================================================= */
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $stmt_old = $conn->prepare("SELECT image, gallery FROM products WHERE id = ?");
    $stmt_old->bind_param("i", $delete_id);
    $stmt_old->execute();
    $res_old = $stmt_old->get_result()->fetch_assoc();

    if ($res_old) {
        if (!empty($res_old['image']) && file_exists($res_old['image'])) {
            unlink($res_old['image']);
        }

        $gallery_arr = json_decode($res_old['gallery'] ?? '[]', true) ?: [];
        foreach ($gallery_arr as $g_path) {
            if (!empty($g_path) && file_exists($g_path)) {
                unlink($g_path);
            }
        }
    }

    $stmt_del = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt_del->bind_param("i", $delete_id);
    
    if ($stmt_del->execute()) {
        echo "<script>alert('Service deleted successfully!'); window.location.href='ManageService.php';</script>";
        exit();
    }
}

/* =========================================================
   FETCH SERVICES DATA
========================================================= */
$services = [];
$result   = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $services[] = $row;
    }
}

$total_services    = count($services);
$active_services   = 0;
$inactive_services = 0;

foreach ($services as $srv) {
    if (strtolower($srv['status']) === 'active') {
        $active_services++;
    } else {
        $inactive_services++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="ui_polish.css">
<title>Manage Services | SA Design</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* =========================================================
   COLOR SYSTEM & ROOT
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

    --success: #78BFA4;
    --warning: #E7B66E;

    --shadow: 0 10px 35px rgba(79, 143, 168, 0.10);
}

/* =========================================================
   GLOBAL
========================================================= */
* { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: var(--light-gray); color: var(--dark-text); min-height: 100vh; }
a { text-decoration: none; }
button, input, select, textarea { font-family: inherit; }

::-webkit-scrollbar { width: 7px; }
::-webkit-scrollbar-track { background: #EEF8FB; }
::-webkit-scrollbar-thumb { background: #B9DDE8; border-radius: 20px; }
::-webkit-scrollbar-thumb:hover { background: var(--pink); }

/* =========================================================
   SIDEBAR
========================================================= */
.sidebar { position: fixed; top: 22px; left: 22px; width: 245px; height: calc(100vh - 44px); background: var(--white); border: 1px solid var(--border); border-radius: 24px; display: flex; flex-direction: column; z-index: 1000; overflow: hidden; box-shadow: 0 18px 45px rgba(79, 143, 168, 0.14); }
.sidebar-brand { padding: 27px 25px 25px; border-bottom: 1px solid var(--border); }
.brand-main { font-size: 25px; font-weight: 900; letter-spacing: -1.2px; color: #EF228B; }
.brand-main span { color: #1C6EF2; }
.brand-sub { margin-top: 5px; font-size: 8px; font-weight: 800; letter-spacing: 1.25px; color: #263238; }
.menu-label { padding: 27px 24px 10px; color: #9AAEB7; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.4px; }
.sidebar-menu { list-style: none; padding: 4px 13px; margin: 0; flex: 1; overflow-y: auto; }
.sidebar-menu li { margin-bottom: 6px; }
.sidebar-menu li a { display: flex; align-items: center; gap: 13px; min-height: 48px; padding: 11px 14px; color: #718A95; border-radius: 12px; font-size: 13px; font-weight: 600; transition: all 0.25s ease; }
.sidebar-menu li a i { width: 23px; text-align: center; font-size: 16px; color: var(--blue); }
.sidebar-menu li a:hover { background: var(--pink-light); color: var(--pink); transform: translateX(3px); }
.sidebar-menu li a:hover i { color: var(--pink); }
.sidebar-menu li.active a { background: linear-gradient(135deg, var(--pink-light), var(--blue-pale)); color: var(--pink); box-shadow: 0 7px 20px rgba(233, 139, 170, 0.10); }
.sidebar-menu li.active a i { color: var(--pink); }

.admin-mini-card { margin: 12px 14px; padding: 13px; background: var(--blue-pale); border: 1px solid var(--border); border-radius: 15px; display: flex; align-items: center; gap: 11px; }
.admin-avatar { width: 42px; height: 42px; flex-shrink: 0; border-radius: 50%; background: var(--pink-light); color: var(--pink); display: flex; align-items: center; justify-content: center; font-size: 17px; border: 2px solid #FFFFFF; }
.admin-info { min-width: 0; }
.admin-name { color: var(--dark-text); font-size: 12px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.admin-role { color: var(--gray-text); font-size: 10px; margin-top: 3px; }
.sidebar-footer { padding: 0 14px 16px; }
.logout-btn { display: flex; align-items: center; gap: 13px; min-height: 47px; padding: 11px 14px; color: var(--pink); background: var(--pink-light); border: 1px solid #F6D6E1; border-radius: 12px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.25s ease; width: 100%; }
.logout-btn i { color: var(--pink); }
.logout-btn:hover { background: var(--pink); color: #FFFFFF; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(233, 139, 170, 0.20); }
.logout-btn:hover i { color: #FFFFFF; }

/* =========================================================
   MAIN CONTENT AREA
========================================================= */
.main-content { margin-left: 289px; padding: 34px 38px 50px; min-height: 100vh; }

.top-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; }
.page-heading { display: flex; flex-direction: column; gap: 5px; }
.welcome-text { font-size: 12px; font-weight: 600; color: var(--gray-text); }
.page-title { font-size: 29px; font-weight: 800; letter-spacing: -0.8px; color: var(--blue-dark); }
.page-subtitle { font-size: 13px; color: var(--gray-text); }
.status-pill { display: flex; align-items: center; gap: 8px; padding: 10px 15px; background: #FFFFFF; border: 1px solid var(--border); border-radius: 30px; color: #62747D; font-size: 11px; font-weight: 600; box-shadow: 0 5px 18px rgba(79,143,168,0.06); }
.status-dot { width: 8px; height: 8px; border-radius: 50%; background: #76C7A5; box-shadow: 0 0 0 4px #E2F5EC; }

/* =========================================================
   STATISTICS GRID
========================================================= */
.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
.stat-card { background: #FFFFFF; border: 1px solid var(--border); border-radius: 16px; padding: 17px; min-height: 110px; position: relative; overflow: hidden; box-shadow: var(--shadow); transition: all 0.25s ease; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(79,143,168,0.12); }
.stat-card::after { content: ""; position: absolute; width: 80px; height: 80px; border-radius: 50%; right: -30px; top: -30px; opacity: 0.16; }

.stat-card.total-card::after { background: var(--blue); }
.stat-card.active-card::after { background: var(--success); }
.stat-card.inactive-card::after { background: var(--pink); }

.stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 11px; }
.stat-icon { width: 38px; height: 38px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 15px; }

.stat-card.total-card .stat-icon { background: var(--blue-light); color: var(--blue); }
.stat-card.active-card .stat-icon { background: #E3F2EA; color: #559B7D; }
.stat-card.inactive-card .stat-icon { background: var(--pink-light); color: var(--pink); }

.stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--gray-text); font-weight: 700; margin-bottom: 4px; }
.stat-value { font-size: 28px; line-height: 1; font-weight: 800; color: var(--blue-dark); }

/* =========================================================
   TOOLBAR CARD
========================================================= */
.toolbar-card { background: #FFFFFF; border: 1px solid var(--border); border-radius: 18px; padding: 20px; box-shadow: var(--shadow); margin-bottom: 20px; }
.toolbar-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; }
.toolbar-title { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 750; color: var(--blue-dark); }
.toolbar-title-icon { width: 35px; height: 35px; border-radius: 50%; background: var(--pink-light); color: var(--pink); display: flex; align-items: center; justify-content: center; font-size: 13px; }
.result-badge { padding: 6px 12px; background: var(--blue-pale); color: var(--blue); border-radius: 20px; font-size: 10px; font-weight: 800; }

.toolbar-form { display: flex; gap: 12px; align-items: center; }
.search-input-wrapper { position: relative; flex: 1; }
.search-input-wrapper input { width: 100%; height: 44px; padding: 10px 42px 10px 16px; border: 1px solid var(--border); border-radius: 12px; background: var(--blue-pale); color: var(--dark-text); font-size: 12px; outline: none; transition: all 0.25s ease; }
.search-input-wrapper input:focus { border-color: var(--pink); background: #FFFFFF; box-shadow: 0 0 0 3px rgba(233, 139, 170, 0.15); }
.search-input-wrapper i { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--blue); }

.btn-add-service { height: 44px; padding: 0 20px; border: none; border-radius: 12px; background: var(--pink); color: #FFFFFF; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s ease; }
.btn-add-service:hover { background: var(--pink-dark); transform: translateY(-1px); box-shadow: 0 5px 15px rgba(233, 139, 170, 0.25); }

/* =========================================================
   PRODUCTS GRID
========================================================= */
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
.product-card { background: #FFFFFF; border: 1px solid var(--border); border-radius: 18px; box-shadow: var(--shadow); overflow: hidden; display: flex; flex-direction: column; position: relative; transition: all 0.25s ease; }
.product-card:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(79,143,168,0.12); }

.card-img-container { width: 100%; height: 180px; background: var(--blue-pale); border-bottom: 1px solid var(--border); position: relative; }
.card-img { width: 100%; height: 100%; object-fit: cover; }
.card-badge { position: absolute; top: 12px; right: 12px; background: var(--pink); color: #FFF; font-size: 10px; font-weight: 800; padding: 5px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 10px rgba(233, 139, 170, 0.3); }

.card-body { padding: 18px; flex: 1; }
.card-title { font-size: 15px; font-weight: 800; color: var(--blue-dark); margin-bottom: 4px; }
.card-category { font-size: 11px; color: var(--gray-text); font-weight: 600; margin-bottom: 12px; }
.card-price { font-size: 17px; font-weight: 800; color: var(--blue-dark); }

.card-actions { padding: 12px 18px; background: #F8FCFD; border-top: 1px solid #EEF3F5; display: flex; gap: 8px; }
.btn-card-edit, .btn-card-delete { flex: 1; height: 36px; border: none; border-radius: 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.25s ease; }
.btn-card-edit { background: var(--blue-pale); color: var(--blue-dark); }
.btn-card-edit:hover { background: var(--blue-light); }
.btn-card-delete { background: var(--pink-pale); color: var(--pink-dark); border: 1px solid var(--pink-light); }
.btn-card-delete:hover { background: var(--pink-light); color: var(--pink); }

/* =========================================================
   MODALS
========================================================= */
.custom-modal, .logout-modal { position: fixed; inset: 0; background: rgba(43, 66, 76, 0.52); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 9999; opacity: 0; pointer-events: none; transition: opacity 0.25s ease; }
.custom-modal.show, .logout-modal.show { opacity: 1; pointer-events: auto; }

.modal-box { width: 100%; max-width: 680px; max-height: 90vh; background: #FFFFFF; border-radius: 20px; box-shadow: 0 25px 60px rgba(40,70,80,0.20); overflow-y: auto; transform: scale(0.95); transition: transform 0.25s ease; }
.custom-modal.show .modal-box, .logout-modal.show .modal-box { transform: scale(1); }

.modal-head { padding: 20px 24px; background: var(--blue-pale); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.modal-head h3 { font-size: 16px; font-weight: 800; color: var(--blue-dark); }
.modal-head button { background: none; border: none; font-size: 18px; cursor: pointer; color: var(--gray-text); transition: color 0.2s; }
.modal-head button:hover { color: var(--pink); }

.modal-body-content { padding: 24px; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: var(--gray-text); margin-bottom: 6px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; border: 1px solid var(--border); border-radius: 10px; background: var(--blue-pale); font-size: 12px; padding: 10px 14px; color: var(--dark-text); outline: none; transition: border-color 0.2s ease; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--pink); background: #FFFFFF; }
.form-group textarea { resize: vertical; min-height: 80px; }
.form-row { display: flex; gap: 12px; }
.form-row .form-group { flex: 1; }

.section-title { font-size: 12px; font-weight: 800; color: var(--blue-dark); margin: 20px 0 10px; border-bottom: 2px solid var(--pink-light); padding-bottom: 6px; display: flex; justify-content: space-between; align-items: center; }
.dynamic-row { display: flex; gap: 8px; margin-bottom: 8px; align-items: center; }
.dynamic-row input { height: 38px; padding: 0 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--blue-pale); font-size: 12px; flex: 1; }
.btn-row-remove { background: var(--pink-pale); color: var(--pink); border: 1px solid var(--pink-light); border-radius: 8px; width: 38px; height: 38px; cursor: pointer; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.btn-row-remove:hover { background: var(--pink-light); }
.btn-row-add { background: var(--blue-light); color: var(--blue-dark); border: none; padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; cursor: pointer; transition: background 0.2s; }
.btn-row-add:hover { background: #CDECF7; }

.modal-foot { padding: 16px 24px; background: #F8FCFD; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; }
.btn-modal-cancel, .btn-modal-submit { height: 40px; padding: 0 20px; border-radius: 10px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s; }
.btn-modal-cancel { background: var(--blue-pale); color: var(--blue-dark); }
.btn-modal-cancel:hover { background: var(--blue-light); }
.btn-modal-submit { background: var(--pink); color: #FFFFFF; }
.btn-modal-submit:hover { background: var(--pink-dark); }

/* LOGOUT & ALERT MODAL BOX */
.alert-box { max-width: 390px; padding: 29px; text-align: center; }
.modal-icon { width: 58px; height: 58px; margin: 0 auto 15px; border-radius: 50%; background: var(--pink-light); color: var(--pink); display: flex; align-items: center; justify-content: center; font-size: 22px; }
.modal-title { font-size: 18px; font-weight: 750; color: var(--blue-dark); margin-bottom: 7px; }
.modal-text { font-size: 12px; color: var(--gray-text); line-height: 1.6; margin-bottom: 22px; }
.modal-buttons { display: flex; gap: 10px; }
.btn-confirm-logout, .btn-cancel-logout { flex: 1; border: none; padding: 11px 18px; border-radius: 10px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; }
.btn-confirm-logout { background: var(--pink); color: #FFFFFF; }
.btn-confirm-logout:hover { background: var(--pink-dark); }
.btn-cancel-logout { background: var(--blue-pale); color: var(--blue-dark); }
.btn-cancel-logout:hover { background: var(--blue-light); }

/* RESPONSIVE DESIGN */
@media (max-width: 1250px) {
    .sidebar { width: 225px; }
    .main-content { margin-left: 267px; padding-left: 28px; padding-right: 28px; }
}
@media (max-width: 1050px) {
    .sidebar { width: 215px; left: 16px; }
    .main-content { margin-left: 247px; padding: 28px 22px 40px; }
    .stats-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 750px) {
    .sidebar { position: relative; top: auto; left: auto; width: 100%; height: auto; border-radius: 0; margin: 0 0 20px; }
    .sidebar-menu { display: grid; grid-template-columns: repeat(2, 1fr); gap: 5px; }
    .main-content { margin-left: 0; padding: 24px 18px 35px; }
    .top-header { flex-direction: column; align-items: flex-start; gap: 13px; }
    .toolbar-form { flex-direction: column; align-items: stretch; }
    .btn-add-service { width: 100%; justify-content: center; }
    .stats-grid { grid-template-columns: repeat(1, 1fr); }
    .form-row { flex-direction: column; gap: 0; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-main">SA<span>DESIGN</span></div>
        <div class="brand-sub">PRINTING &amp; ADVERTISING</div>
    </div>
    <div class="menu-label">Main Menu</div>
    <ul class="sidebar-menu">
        <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a></li>
        <li><a href="manage_orders.php"><i class="fa-solid fa-boxes-stacked"></i><span>Manage Orders</span></a></li>
        <li class="active"><a href="ManageService.php"><i class="fa-solid fa-layer-group"></i><span>Manage Services</span></a></li>
        <li><a href="StaffPage.php"><i class="fa-solid fa-users"></i><span>Staff</span></a></li>
        <li><a href="AdminProfile.php"><i class="fa-solid fa-user-gear"></i><span>Profile</span></a></li>
    </ul>
    <div class="admin-mini-card">
        <div class="admin-avatar"><i class="fa-solid fa-user"></i></div>
        <div class="admin-info">
            <div class="admin-name">Administrator</div>
            <div class="admin-role">System Admin</div>
        </div>
    </div>
    <div class="sidebar-footer">
        <button type="button" onclick="openLogoutModal()" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </button>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="main-content">
    <div class="top-header">
        <div class="page-heading">
            <span class="welcome-text">Products / Services Management</span>
            <h1 class="page-title">Manage Services</h1>
            <p class="page-subtitle">Add, update, or organize product offerings &amp; variations.</p>
        </div>
        <div class="status-pill"><span class="status-dot"></span> System Online</div>
    </div>

    <!-- STATS GRID -->
    <div class="stats-grid">
        <div class="stat-card total-card">
            <div class="stat-top">
                <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
            </div>
            <div class="stat-label">Total Services</div>
            <div class="stat-value"><?= number_format($total_services) ?></div>
        </div>
        <div class="stat-card active-card">
            <div class="stat-top">
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            </div>
            <div class="stat-label">Active Services</div>
            <div class="stat-value"><?= number_format($active_services) ?></div>
        </div>
        <div class="stat-card inactive-card">
            <div class="stat-top">
                <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
            </div>
            <div class="stat-label">Inactive Services</div>
            <div class="stat-value"><?= number_format($inactive_services) ?></div>
        </div>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar-card">
        <div class="toolbar-top">
            <div class="toolbar-title">
                <div class="toolbar-title-icon"><i class="fa-solid fa-sliders"></i></div>
                Search &amp; Manage Services
            </div>
            <div class="result-badge" id="serviceCountBadge"><?= $total_services ?> Result(s)</div>
        </div>
        <div class="toolbar-form">
            <div class="search-input-wrapper">
                <input type="text" id="searchInput" onkeyup="filterServices()" placeholder="Search Services by name or category...">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <button type="button" class="btn-add-service" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Add Service</button>
        </div>
    </div>

    <!-- PRODUCTS GRID -->
    <div class="products-grid" id="productsGrid">
        <?php foreach ($services as $srv): ?>
            <div class="product-card" data-status="<?= strtolower($srv['status']) ?>">
                <div class="card-img-container">
                    <?php if (!empty($srv['badge'])): ?>
                        <span class="card-badge"><?= htmlspecialchars($srv['badge']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($srv['image']) && file_exists($srv['image'])): ?>
                        <img src="<?= htmlspecialchars($srv['image']) ?>" class="card-img" alt="Service Image">
                    <?php else: ?>
                        <div style="display:flex; align-items:center; justify-content:center; height:100%; color: var(--gray-text);"><i class="fa-regular fa-image" style="font-size: 38px;"></i></div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= htmlspecialchars($srv['name']) ?></div>
                    <div class="card-category"><?= htmlspecialchars($srv['category']) ?></div>
                    <div class="card-price">RM<?= number_format((float)$srv['price'], 2) ?></div>
                </div>
                <div class="card-actions">
                    <button type="button" class="btn-card-edit" onclick='openEditModal(<?= json_encode($srv, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                    <button type="button" class="btn-card-delete" onclick="openDeleteModal(<?= $srv['id'] ?>, '<?= addslashes($srv['name']) ?>')"><i class="fa-solid fa-trash-can"></i> Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- ADD SERVICE MODAL -->
<div id="addServiceModal" class="custom-modal">
    <div class="modal-box">
        <div class="modal-head">
            <h3>Add New Service</h3>
            <button type="button" onclick="closeAddModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="ManageService.php" method="POST" enctype="multipart/form-data">
            <div class="modal-body-content">
                <div class="form-group"><label>Service Name</label><input type="text" name="service_name" required></div>
                <div class="form-row">
                    <div class="form-group"><label>Category</label>
                        <select name="category">
                            <option value="Stationery">Stationery</option>
                            <option value="Sportswear">Sportswear</option>
                            <option value="Large Format Printing">Large Format Printing</option>
                            <option value="Event Printing">Event Printing</option>
                            <option value="Outdoor Advertising">Outdoor Advertising</option>
                            <option value="Labels & Stickers">Labels &amp; Stickers</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Badge (e.g. Popular/New)</label><input type="text" name="badge" placeholder="HOT"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Base Price (RM)</label><input type="number" step="0.01" name="price" required></div>
                    <div class="form-group"><label>Minimum Order</label><input type="text" name="minimum_order" placeholder="e.g. 100 pcs"></div>
                </div>
                <div class="form-group"><label>Status</label>
                    <select name="status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description" placeholder="Enter product description..."></textarea></div>
                <div class="form-group"><label>Main Image</label><input type="file" name="image" accept="image/*"></div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeAddModal()">Cancel</button>
                <button type="submit" name="add_service" class="btn-modal-submit">Add Service</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT SERVICE MODAL -->
<div id="editServiceModal" class="custom-modal">
    <div class="modal-box">
        <div class="modal-head">
            <h3>Edit Service &amp; Details</h3>
            <button type="button" onclick="closeEditModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="ManageService.php" method="POST" enctype="multipart/form-data">
            <div class="modal-body-content">
                <input type="hidden" name="service_id" id="editId">

                <div class="form-group"><label>Service Name</label><input type="text" name="service_name" id="editName" required></div>
                <div class="form-row">
                    <div class="form-group"><label>Category</label>
                        <select name="category" id="editCategory">
                            <option value="Stationery">Stationery</option>
                            <option value="Sportswear">Sportswear</option>
                            <option value="Large Format Printing">Large Format Printing</option>
                            <option value="Event Printing">Event Printing</option>
                            <option value="Outdoor Advertising">Outdoor Advertising</option>
                            <option value="Labels & Stickers">Labels &amp; Stickers</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Badge</label><input type="text" name="badge" id="editBadge"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Base Price (RM)</label><input type="number" step="0.01" name="price" id="editPrice" required></div>
                    <div class="form-group"><label>Minimum Order</label><input type="text" name="minimum_order" id="editMinimumOrder"></div>
                </div>
                <div class="form-group"><label>Status</label>
                    <select name="status" id="editStatus"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description" id="editDescription"></textarea></div>
                <div class="form-group"><label>Main Image (Optional Update)</label><input type="file" name="image" accept="image/*"></div>

                <div class="section-title">
                    <span>Product Features</span>
                    <button type="button" class="btn-row-add" onclick="addFeatureRow()"><i class="fa-solid fa-plus"></i> Add Feature</button>
                </div>
                <div id="featuresContainer"></div>

                <div class="section-title">
                    <span>Product Specifications</span>
                    <button type="button" class="btn-row-add" onclick="addSpecRow()"><i class="fa-solid fa-plus"></i> Add Spec</button>
                </div>
                <div id="specsContainer"></div>

                <div class="section-title">
                    <span>Type Options (e.g. Short Sleeve / Collar)</span>
                    <button type="button" class="btn-row-add" onclick="addTypeOptionRow()"><i class="fa-solid fa-plus"></i> Add Type Option</button>
                </div>
                <div id="typeOptionsContainer"></div>

                <div class="section-title">
                    <span>Size Options (e.g. S, M, L, XL)</span>
                    <button type="button" class="btn-row-add" onclick="addSizeOptionRow()"><i class="fa-solid fa-plus"></i> Add Size Option</button>
                </div>
                <div id="sizeOptionsContainer"></div>

                <div class="section-title"><span>Add Gallery Images</span></div>
                <div class="form-group"><input type="file" name="gallery_images[]" multiple accept="image/*"></div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" name="update_service" class="btn-modal-submit">Save All Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE MODAL -->
<div id="deleteConfirmationModal" class="custom-modal">
    <div class="modal-box alert-box">
        <div class="modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 class="modal-title">Confirm Delete</h3>
        <p class="modal-text">Are you sure you want to delete <strong id="deleteServiceName"></strong>?</p>
        <div class="modal-buttons">
            <button type="button" onclick="closeDeleteModal()" class="btn-cancel-logout">Cancel</button>
            <button type="button" onclick="executeDelete()" class="btn-confirm-logout">Delete</button>
        </div>
    </div>
</div>

<!-- LOGOUT MODAL -->
<div id="logoutConfirmationModal" class="logout-modal">
    <div class="modal-box alert-box">
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
function filterServices() {
    const filter = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.product-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const title = card.querySelector('.card-title').textContent.toLowerCase();
        const category = card.querySelector('.card-category').textContent.toLowerCase();
        if (title.includes(filter) || category.includes(filter)) {
            card.style.display = "";
            visibleCount++;
        } else {
            card.style.display = "none";
        }
    });

    document.getElementById('serviceCountBadge').textContent = visibleCount + ' Result(s)';
}

function addFeatureRow(val = '') {
    const container = document.getElementById('featuresContainer');
    const div = document.createElement('div');
    div.className = 'dynamic-row';
    div.innerHTML = `
        <input type="text" name="features[]" value="${escapeHtml(val)}" placeholder="e.g. Waterproof Material">
        <button type="button" class="btn-row-remove" onclick="this.parentElement.remove()"><i class="fa-solid fa-trash"></i></button>
    `;
    container.appendChild(div);
}

function addSpecRow(name = '', val = '') {
    const container = document.getElementById('specsContainer');
    const div = document.createElement('div');
    div.className = 'dynamic-row';
    div.innerHTML = `
        <input type="text" name="spec_names[]" value="${escapeHtml(name)}" placeholder="Spec Name">
        <input type="text" name="spec_values[]" value="${escapeHtml(val)}" placeholder="Value">
        <button type="button" class="btn-row-remove" onclick="this.parentElement.remove()"><i class="fa-solid fa-trash"></i></button>
    `;
    container.appendChild(div);
}

function addTypeOptionRow(name = '', price = '0.00') {
    const container = document.getElementById('typeOptionsContainer');
    const div = document.createElement('div');
    div.className = 'dynamic-row';
    let fullName = name;
    if (fullName && !fullName.startsWith('type_options - ')) {
        fullName = 'type_options - ' + fullName;
    }
    div.innerHTML = `
        <input type="hidden" name="option_names[]" value="${escapeHtml(fullName || 'type_options - ')}" class="type-name-hidden">
        <input type="text" value="${escapeHtml(name.replace('type_options - ', ''))}" placeholder="Type Name (e.g. Roundneck)" oninput="updateOptionHiddenName(this, 'type_options - ')">
        <input type="number" step="0.01" name="option_prices[]" value="${price}" placeholder="Price RM">
        <button type="button" class="btn-row-remove" onclick="this.parentElement.remove()"><i class="fa-solid fa-trash"></i></button>
    `;
    container.appendChild(div);
}

function addSizeOptionRow(name = '', price = '0.00') {
    const container = document.getElementById('sizeOptionsContainer');
    const div = document.createElement('div');
    div.className = 'dynamic-row';
    let fullName = name;
    if (fullName && !fullName.startsWith('size_options - ')) {
        fullName = 'size_options - ' + fullName;
    }
    div.innerHTML = `
        <input type="hidden" name="option_names[]" value="${escapeHtml(fullName || 'size_options - ')}" class="size-name-hidden">
        <input type="text" value="${escapeHtml(name.replace('size_options - ', ''))}" placeholder="Size Name (e.g. S, M, L, XL)" oninput="updateOptionHiddenName(this, 'size_options - ')">
        <input type="number" step="0.01" name="option_prices[]" value="${price}" placeholder="Price RM">
        <button type="button" class="btn-row-remove" onclick="this.parentElement.remove()"><i class="fa-solid fa-trash"></i></button>
    `;
    container.appendChild(div);
}

function updateOptionHiddenName(inputEl, prefix) {
    const hiddenInput = inputEl.parentElement.querySelector('input[type="hidden"]');
    if (hiddenInput) {
        hiddenInput.value = prefix + inputEl.value;
    }
}

function escapeHtml(text) {
    if (typeof text !== 'string') return text;
    return text.replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function parseOptionsRecursively(data) {
    if (!data) return;
    if (Array.isArray(data)) {
        data.forEach(item => {
            let label = '';
            let price = 0;
            if (typeof item === 'object' && item !== null) {
                label = item.name || item.label || item.type || '';
                price = item.price || 0;
            } else if (typeof item === 'string' || typeof item === 'number') {
                label = String(item);
            }

            if (label.startsWith('size_options - ')) {
                addSizeOptionRow(label, price);
            } else if (label.startsWith('type_options - ')) {
                addTypeOptionRow(label, price);
            } else if (label) {
                addTypeOptionRow(label, price);
            }
        });
    }
}

function openEditModal(srv) {
    document.getElementById('editId').value = srv.id || '';
    document.getElementById('editName').value = srv.name || '';
    document.getElementById('editCategory').value = srv.category || 'Stationery';
    document.getElementById('editBadge').value = srv.badge || '';
    document.getElementById('editPrice').value = srv.price || '0.00';
    document.getElementById('editMinimumOrder').value = srv.minimum_order || '';
    document.getElementById('editStatus').value = srv.status || 'Active';
    document.getElementById('editDescription').value = srv.description || '';

    document.getElementById('featuresContainer').innerHTML = '';
    document.getElementById('specsContainer').innerHTML = '';
    document.getElementById('typeOptionsContainer').innerHTML = '';
    document.getElementById('sizeOptionsContainer').innerHTML = '';

    try {
        const features = typeof srv.features === 'string' ? JSON.parse(srv.features) : srv.features;
        if (Array.isArray(features)) features.forEach(f => addFeatureRow(f));
    } catch (e) { console.error(e); }

    try {
        const specs = typeof srv.specifications === 'string' ? JSON.parse(srv.specifications) : srv.specifications;
        if (specs && typeof specs === 'object' && !Array.isArray(specs)) {
            for (const [sKey, sVal] of Object.entries(specs)) addSpecRow(sKey, sVal);
        }
    } catch (e) { console.error(e); }

    try {
        const options = typeof srv.options === 'string' ? JSON.parse(srv.options) : srv.options;
        parseOptionsRecursively(options);
    } catch (e) { console.error(e); }

    document.getElementById('editServiceModal').classList.add('show');
}

function closeEditModal() { document.getElementById('editServiceModal').classList.remove('show'); }
function openAddModal() { document.getElementById('addServiceModal').classList.add('show'); }
function closeAddModal() { document.getElementById('addServiceModal').classList.remove('show'); }

let targetDeleteId = null;
function openDeleteModal(id, name) {
    targetDeleteId = id;
    document.getElementById('deleteServiceName').textContent = name;
    document.getElementById('deleteConfirmationModal').classList.add('show');
}
function closeDeleteModal() { document.getElementById('deleteConfirmationModal').classList.remove('show'); }
function executeDelete() {
    if (targetDeleteId) window.location.href = "ManageService.php?delete_id=" + targetDeleteId;
}

/* LOGOUT MODAL FUNCTIONS */
const logoutModalElement = document.getElementById('logoutConfirmationModal');
function openLogoutModal() { logoutModalElement.classList.add('show'); }
function closeLogoutModal() { logoutModalElement.classList.remove('show'); }
function executeLogout() { window.location.href = 'admin_logout.php'; }

window.onclick = function(event) {
    if (event.target === logoutModalElement) closeLogoutModal();
};
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') closeLogoutModal();
});
</script>
</body>
</html>
