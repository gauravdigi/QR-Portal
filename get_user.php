<?php
// In header.php or bootstrap/init.php
require 'config.php';
require 'error_handler.php';

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("SELECT name, type, flat_no, expiry_date, photo, qr_code FROM users WHERE id = ? ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($name, $type, $flat_no, $expiry_date, $photo, $qr_code);

    if ($stmt->fetch()) {
        echo json_encode([
            'status' => 'success',
            'name' => $name,
            'type' => $type,
            'flat_no' => $flat_no,
            'expiry_date' => $expiry_date,
            'photo' => 'uploads/' . $photo,
            'qr_code' => 'qrcodes/' . $qr_code,
            'qr_name' => $qr_code,
            'qr_url' => 'https://www.digisoftsolution.com/qrcode/qrcods' . $qr_code // Full URL for WhatsApp sharing
        ]);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
    $stmt->close();
}
?>
