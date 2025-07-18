<?php
$host = '127.0.0.1';
$dbname = 'qrcode';
$username = 'root';
$password = 'Developer@123';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
