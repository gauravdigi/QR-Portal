<?php
// In header.php or bootstrap/init.php
require 'config.php';
require 'error_handler.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    echo "Deleted";
} else {
    http_response_code(400);
    echo "Invalid request";
}
?>
