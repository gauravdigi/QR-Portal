<?php
require 'config.php'; // DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    $stmt = $conn->prepare("UPDATE users SET is_deleted = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        http_response_code(500);
        echo "error";
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(400);
    echo "Invalid request.";
}
