<?php
// In header.php or bootstrap/init.php
require 'config.php';
require 'error_handler.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $new_expiry_date = $_POST['new_expiry_date'];
    $flatno_id = $_POST['flat_no'];
    
        // Step 1: Get all users with the given flat number
        $sql = "SELECT id FROM users WHERE flat_no = '$flatno_id' AND is_deleted = 0 AND expiry_date >= CURDATE() AND type != 'Guest'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows <= 4) {
            // Step 2: Prepare the update statement once
            $stmt = $conn->prepare("UPDATE users SET expiry_date = ? WHERE id = ? AND is_deleted = 0  AND type != 'Guest'");
            
            while ($row = $result->fetch_assoc()) {
                $userid = $row['id'];
                // Bind and execute for each user
                $stmt->bind_param("si", $new_expiry_date, $userid);
                $stmt->execute();
            }

            $stmt->close();

            // Step 1: Fetch user and flat number
            $expsql = "SELECT flat_no FROM users WHERE id = ? AND is_deleted = 0";
            $stmt1 = $conn->prepare($expsql);
            $stmt1->bind_param("i", $user_id);
            $stmt1->execute();
            $result2 = $stmt1->get_result();
            // exipred users have update only with specfic id
            if ($result2 && $result2->num_rows > 0) {
                $userData = $result2->fetch_assoc();
                $flat_no = $userData['flat_no'];
                $user_type = $userData['type'];

                   if (strtolower($user_type) !== 'guest') {
                            // Step 3: Count how many active NON-GUEST users are already there for this flat
                            $countSql = "SELECT COUNT(*) as active_count FROM users 
                                         WHERE flat_no = ? 
                                           AND is_deleted = 0 
                                           AND expiry_date >= CURDATE() 
                                           AND id != ? 
                                           AND type != 'Guest'";
                            $stmtCount = $conn->prepare($countSql);
                            $stmtCount->bind_param("si", $flat_no, $user_id);
                            $stmtCount->execute();
                            $countResult = $stmtCount->get_result();
                            $countData = $countResult->fetch_assoc();
                            $activeCount = $countData['active_count'];

                            if ($activeCount >= 4) {
                                echo json_encode([
                                    "success" => false,
                                    "message" => "Limit reached: 4 active non-guest users already exist for this flat number."
                                ]);
                                exit;
                            }

                            // Step 4: Update expiry date for the specific non-guest user
                            $stmt2 = $conn->prepare("UPDATE users SET expiry_date = ? WHERE id = ? AND type != 'Guest'");
                            $stmt2->bind_param("si", $new_expiry_date, $user_id);
                            $stmt2->execute();
                            $stmt2->close();

                            echo json_encode([
                                "success" => true,
                                "message" => "Expiry date updated for selected user."
                            ]);
                            exit;

                        } else {
                            // Guest user should not be updated
                            echo json_encode([
                                "success" => false,
                                "message" => "Cannot update expiry date for Guest user."
                            ]);
                            exit;
                        }

            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "User not found or already deleted."
                ]);
                exit;
            }
            
                echo json_encode([
                    "success" => true,
                    "message" => "Expiry dates updated successfully for all active users with flat number $flatno_id"
                ]);
                exit;
        }else{
                   echo json_encode([
                "success" => false,
                "message" => "Already 4 active users with this flat number $flatno_id"
            ]);
            exit;
        }

    $conn->close();
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
?>
