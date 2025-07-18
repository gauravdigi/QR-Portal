<?php
// In header.php or bootstrap/init.php
require 'config.php';
require 'error_handler.php';

if (isset($_POST['flat_no'])) {
    $flat_no = $_POST['flat_no'];
    $today = date('Y-m-d');

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM users 
        WHERE flat_no = ? 
          AND is_deleted = 0
          AND (expiry_date IS NULL OR expiry_date >= ?)
    ");
    $stmt->bind_param("ss", $flat_no, $today);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    echo json_encode(["tooMany" => $count >= 4]);
    exit;
}

echo json_encode(["tooMany" => false]);

