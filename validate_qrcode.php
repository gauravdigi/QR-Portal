<?php
$secretKey = 'digisoftsolution';  // must match the generation secret key

// In header.php or bootstrap/init.php
require 'config.php';
require 'error_handler.php';

if (isset($_GET['id'], $_GET['token'], $_GET['expiry'])) {
    $user_id = $_GET['id'];
    $token = $_GET['token'];
    $expiryTime = $_GET['expiry'];

    // Recreate the hash token
    $rawData = $user_id . $expiryTime . $secretKey;
    $expectedToken = hash('sha256', $rawData);
    $expiryTimestamp = strtotime($expiryTime . ' 23:59:59');
    if ($token === $expectedToken) {
        if (time() < $expiryTimestamp) {
            // Valid token and not expired - fetch user data
            $stmt = $conn->prepare("SELECT name, type, flat_no, expiry_date, photo, qr_code, is_deleted FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                 include 'scan_qrcode.php';


            } else {
                $param1 = '❌ User not found.';
                include 'errors.php';
                // echo "❌ User not found.";
            }

            $stmt->close();

        } else {
            $param1 = '⚠️ QR Code is expired.';
                include 'errors.php';
      
        }
    } else {
        $param1 = '❌ Invalid QR Code token.';
                include 'errors.php';
        
    }
} else {
    $param1 = '❌ Missing required parameters.';
                include 'errors.php';
   
}

$conn->close();
