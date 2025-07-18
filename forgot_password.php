<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
$mail = new PHPMailer(true);

include 'config.php';
include 'header.php';
session_start();

// Check if user is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: /qrcode"); // Change to your home/dashboard page
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime('+10 minutes'));

        $stmt2 = $conn->prepare("UPDATE admin SET reset_token = ?, token_update_time = ? WHERE email = ?");
        $stmt2->bind_param("sss", $token, $expiry, $email);
        $stmt2->execute();

        // Email setup
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'uiuxtest1097@gmail.com';      // Your Gmail
            $mail->Password   = 'igfu frzl jvhg pcnj';         // App Password (not your regular password!)
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('uiuxtest1097@gmail.com', 'Your Name');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password';
            $resetLink = "https://digisoftsolution.com/qrcode/reset_password.php?token=$token";
            $mail->Body = "Click <a href='$resetLink'>here</a> to reset your password.<br>This link will expire in 10 minutes.";

            $mail->send();
            $message = "✅ Reset link has been sent to your email.";
        } catch (Exception $e) {
            $message = "❌ Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $message = "❌ Email not found.";
    }
}
?>


<div class="card shadow-lg p-5 mx-auto" style="max-width: 600px; border-radius: 1.25rem;">
    <h3>Forgot Password</h3>
    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required class="form-control">
        <button type="submit" class="btn btn-primary mt-2">Send Reset Link</button>
    </form>
        <div class="text-end mt-3">
          <a href="/qrcode/" class="text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Back
          </a>
        </div>
</div>

<?php if (!empty($message)): ?>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            swal({
                title: "Info",
                text: "<?= addslashes(strip_tags($message)) ?>",
                icon: "info"
            });
        });
    </script>
<?php endif; ?>

<?php include 'footer.php'; ?>

