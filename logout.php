<?php
session_start();

// Padam semua data session
$_SESSION = array();

// Hapuskan session
session_destroy();

// Kembali ke homepage
header("Location: index.php");
exit();
?>