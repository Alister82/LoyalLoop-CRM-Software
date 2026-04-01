<?php
session_start();
if (!isset($_SESSION['shop_id'])) {
    header("Location: login.php");
    exit();
}
// Helper variable for queries
$shop_id = $_SESSION['shop_id'];
?>