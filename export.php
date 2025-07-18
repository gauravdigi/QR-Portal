<?php
include 'config.php';

$status = $_POST['status'] ?? 'All';

// SQL with dynamic status
$sql = "SELECT name, type, flat_no, expiry_date,
        CASE 
            WHEN is_deleted = 1 THEN 'Deleted'
            WHEN expiry_date < CURDATE() THEN 'Expired'
            ELSE 'Active'
        END AS status 
        FROM users WHERE 1=1";

// Apply filter based on dropdown
if ($status == 'expired') {
    $sql .= " AND expiry_date < CURDATE() AND is_deleted = 0";
} elseif ($status == 'deleted') {
    $sql .= " AND is_deleted = 1";
} elseif ($status == 'active') {
    $sql .= " AND expiry_date >= CURDATE() AND is_deleted = 0";
}

$sql .= " ORDER BY expiry_date DESC";
// "All" needs no extra filter

$result = $conn->query($sql);

// CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=users_export.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Name', 'Type', 'Flat No', 'Expiry Date', 'Status']);

while ($row = $result->fetch_assoc()) {
    $formattedDate = date("j F Y", strtotime($row['expiry_date']));
    fputcsv($output, [
        $row['name'],
        $row['type'],
        $row['flat_no'],
        $formattedDate,
        $row['status']
    ]);
}
fclose($output);
exit;

