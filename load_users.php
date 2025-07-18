<?php
// In header.php or bootstrap/init.php
require 'config.php';
require 'error_handler.php';

$filter = $_GET['filter'] ?? 'active';

if ($filter === 'deleted') {
    $sql = "SELECT id, name, type, flat_no, expiry_date, photo, is_deleted 
            FROM users 
            WHERE is_deleted = 1 
            ORDER BY id DESC";
    $stmt = $conn->prepare($sql);

} elseif ($filter === 'expired') {
    // Users not deleted, but whose expiry_date is less than today
    $sql = "SELECT id, name, type, flat_no, expiry_date, photo, is_deleted 
            FROM users 
            WHERE is_deleted = 0 
            AND expiry_date < CURDATE() 
            ORDER BY id DESC";
    $stmt = $conn->prepare($sql);

} elseif($filter === 'active') {
    // 'active' or default
    $sql = "SELECT id, name, type, flat_no, expiry_date, photo, is_deleted 
            FROM users 
            WHERE is_deleted = 0 
            AND expiry_date >= CURDATE() 
            ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
}else{
     $sql = "SELECT id, name, type, flat_no, expiry_date, photo, is_deleted 
            FROM users 
            ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

          // Determine user status
          $status = '';
          $statusClass = '';

          if ($row['is_deleted']) {
              $status = 'Deleted';
              $statusClass = 'text-danger';
          } elseif (strtotime($row['expiry_date']) < strtotime(date('Y-m-d'))) {
              $status = 'Expired';
              $statusClass = 'text-warning';
          } else {
              $status = 'Active';
              $statusClass = 'text-success';
          }
          if ($row['is_deleted'] == 1) {
            $editBtn="<button class='btn btn-sm btn-outline-secondary' disabled><i class='bi bi-pencil-square'></i> Edit</button>";
            $deleteBtn = "<button class='btn btn-sm btn-outline-secondary' disabled><i class='bi bi-trash3-fill'></i> Deleted</button>";
        } else {
            $editBtn = " <button class='btn btn-sm btn-outline-warning editExpiryBtn' data-id='{$row['id']}' data-expiry='{$row['expiry_date']} '><i class='bi bi-pencil-square'></i>Edit</button>";
            $deleteBtn = "<button class='btn btn-sm btn-outline-danger deletebtn' data-id='{$row['id']}'><i class='bi bi-trash3-fill'></i> Delete</button>";
        }
        
        $photoPath = 'uploads/' . $row['photo'];
        echo "<tr>
                <td><img src='{$photoPath}' style='width:50px; height:50px; border-radius:50%; object-fit:cover;'></td>
                <td><span title='". htmlspecialchars($row['name']) ."'>" . htmlspecialchars($row['name']) . "</span></td>
                <td>" . htmlspecialchars($row['type']) . "</td>
                <td>" . htmlspecialchars($row['flat_no']) . "</td>
                <td>" . htmlspecialchars(date('j F Y', strtotime($row['expiry_date']))) . "</td>
                <td class=".$statusClass. ">" . $status ."</td>
                <td class='actionTd'>
                    {$editBtn}
                    <button class='btn btn-sm btn-outline-info viewuser' data-id='{$row['id']}'><i class='bi bi-eye-fill'></i>View</button>
                    {$deleteBtn} 

                </td>
              </tr>";
    }
} else {
    echo "<tr>
        <td class='text-center'></td>
        <td class='text-center'></td>
        <td class='text-center'></td>
        <td class='text-center'>No records found</td>
        <td class='text-center'></td>
        <td class='text-center'></td>
        <td class='text-center'></td>
    </tr>";
}
?>
