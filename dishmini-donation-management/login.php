<?php
session_start();
include 'php/config/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $query = "SELECT * FROM users WHERE email = ? AND role = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            if ($role == 'shelter') {
                $shelterQuery = "SELECT shelter_id FROM shelters WHERE user_id = ?";
                $shelterStmt = $conn->prepare($shelterQuery);
                $shelterStmt->bind_param("i", $row['user_id']);
                $shelterStmt->execute();
                $shelterResult = $shelterStmt->get_result();
                if ($shelterRow = $shelterResult->fetch_assoc()) {
                    $_SESSION['shelter_id'] = $shelterRow['shelter_id'];
                }
                header("Location: shelter_dashboard.php");
            } else {
                header("Location: adopter_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found with this role!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PawConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-paw"></i>
                </div>
                <h2>PawConnect</h2>
                <p>Welcome back! Enter your credentials to access your account.</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-paw-label">Email Address</label>
                    <input type="email" name="email" class="form-paw" placeholder="alex.rivera@example.com" required>
                </div>

                <div class="mb-3">
                    <label class="form-paw-label">Password</label>
                    <input type="password" name="password" class="form-paw" placeholder="••••••••" required>
                </div>

                <div class="mb-3">
                    <label class="form-paw-label">I am a</label>
                    <select name="role" class="form-paw" required>
                        <option value="shelter">🏢 Shelter Admin</option>
                        <option value="supporter">🐾 Adopter / Supporter</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label text-muted small" for="remember">Keep me logged in</label>
                    </div>
                    <a href="#" class="text-decoration-none small" style="color:var(--primary);">Forgot password?</a>
                </div>

                <button type="submit" class="btn-paw-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In to Account
                </button>
            </form>

            <div class="divider">
                <span>OR CONTINUE WITH</span>
            </div>

            <div class="row g-2">
                <div class="col-6">
                    <button class="social-btn" onclick="alert('Google login coming soon!')">
                        <i class="fab fa-google" style="color:#ea4335;"></i> Google
                    </button>
                </div>
                <div class="col-6">
                    <button class="social-btn" onclick="alert('Facebook login coming soon!')">
                        <i class="fab fa-facebook" style="color:#1877f2;"></i> Facebook
                    </button>
                </div>
            </div>

            <div class="footer-links">
                Don't have an account? <a href="#">Create one now</a>
            </div>
            <div class="footer-links" style="font-size:0.75rem;color:#b0a89c;margin-top:8px;">
                <a href="#">Home</a>
                <span class="dot">·</span>
                <a href="#">Support</a>
                <span class="dot">·</span>
                <a href="#">Privacy</a>
                <span class="dot">·</span>
                <a href="#">Status</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>