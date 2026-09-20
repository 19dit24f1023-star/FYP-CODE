<?php
session_start();

// Semak keselamatan Admin
if (empty($_SESSION['is_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied.");
}

if (isset($_GET['file'])) {
    $filename = basename($_GET['file']); // Elak Directory Traversal Attack
    $filepath = __DIR__ . '/uploads/' . $filename;

    if (file_exists($filepath)) {
        $mime_type = mime_content_type($filepath);
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        readfile($filepath);
        exit();
    } else {
        echo "Fail tidak dijumpai di server.";
    }
} else {
    echo "Tiada fail dinyatakan.";
}
?>