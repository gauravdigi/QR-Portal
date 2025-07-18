<?php
require 'config.php';
require 'error_handler.php';

$flat_no = $_POST['flat_no'] ?? '';

if ($flat_no) {
    $stmt = $conn->prepare("
        SELECT expiry_date 
        FROM users 
        WHERE flat_no = ? 
          AND expiry_date >= CURDATE()
          AND is_deleted = 0
          AND type != 'Guest'
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->bind_param("s", $flat_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "success" => true,
            "expiry_date" => $row['expiry_date']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No active user found."
        ]);
    }

    $stmt->close();
} else {
    echo json_encode([
        "success" => false,
        "message" => "Flat number is missing."
    ]);
}

?>
