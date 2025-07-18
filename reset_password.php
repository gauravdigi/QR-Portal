<?php
include 'config.php';
include 'header.php';

$token = $_GET['token'] ?? '';
$message = '';

if (!$token) {
    die("Invalid token.");
}

// Verify token and expiry
$stmt = $conn->prepare("SELECT reset_token, token_update_time FROM admin WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Invalid or expired token.");
}


if($user['reset_token']==$token){

$token_update_time = $user['token_update_time']; // e.g. "2025-06-04 13:14:01"
$current_time = new DateTime(); // Current time
$token_time = new DateTime($token_update_time); // Token time from DB

// Calculate the difference in minutes
$interval = $current_time->getTimestamp() - $token_time->getTimestamp();

if ($interval > 0) { // 600 seconds = 10 minutes
    die("Token expired.");
} else {

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new_pass !== $confirm) {
        $message = "Passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $hashedPass = password_hash($new_pass, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE admin SET password = ?, reset_token = NULL,  token_update_time = NULL WHERE reset_token = ?");
        $stmt->bind_param("ss", $hashedPass, $token);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "✅ Password reset successful. <a href='/qrcode'>Login now</a>";
        } else {
            $message = "❌ Something went wrong. Please try again.";
        }

        $stmt->close();
    }
}
?>
<div class="card shadow-lg p-5 mx-auto" style="max-width: 600px; border-radius: 1.25rem;">
    <h3>Reset Password</h3>
    <?php if ($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>New Password</label>
        <input type="password" name="new_password" class="form-control" required>

        <label class="mt-2">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" required>

        <button type="submit" class="btn btn-success mt-3">Reset Password</button>
    </form>
    <div class="text-end mt-3">
      <a href="/qrcode/forgot_password.php" class="text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Back
      </a>
    </div>
</div>
 <?php   }

}else{
      die("Invalid or expired token.");
}


?>



<?php include 'footer.php'; ?>
