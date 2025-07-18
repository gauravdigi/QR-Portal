<?php
// auth_check.php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: /qrcode");
    exit;
}