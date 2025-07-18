<?php
session_start();

// In header.php or bootstrap/init.php
require 'config.php';
require 'error_handler.php';
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: / ");
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $password === $user['password']) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $login_error = "Invalid username or password.";
    }

    $stmt->close();
}

include 'header.php';
?>

  <?php if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true): ?>
      <div class="card shadow-lg p-5 mx-auto" style="max-width: 600px; border-radius: 1.25rem;">
      <!-- Login Form -->
      <h3 class="mb-4 text-center text-primary fw-bold">
        <i class="bi bi-person-lock me-2"></i>Admin Login
      </h3>

      <?php if (!empty($login_error)): ?>
        <div class="alert alert-danger"><?php echo $login_error; ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="mb-3">
          <label for="username" class="form-label fw-semibold">Username</label>
          <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label fw-semibold">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <input type="hidden" name="login" value="1">
        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login
          </button>
        </div>
      </form>
    </div>
    <?php else: ?>
      <?php include 'list.php'; ?>

      <small class="d-block text-muted text-end mt-3 mb-5">Developed & Desgined by <a href="https://www.digisoftsolution.com/" target="_blank" class="text-decoration-none">Digisoft Solution</a></small>
    <?php endif; ?>




<?php include 'footer.php'; ?>
