<?php
session_start();
include 'php/config/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shelter') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$shelter_id = $_SESSION['shelter_id'];

// Fetch shelter data
$query = "SELECT s.*, u.email, u.full_name 
          FROM shelters s 
          JOIN users u ON s.user_id = u.user_id 
          WHERE s.shelter_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $shelter_id);
$stmt->execute();
$result = $stmt->get_result();
$shelter = $result->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $shelter_name = $_POST['shelter_name'];
    $description = $_POST['description'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];
    $facebook_link = $_POST['facebook_link'];
    $instagram_link = $_POST['instagram_link'];
    $website_link = $_POST['website_link'];
    
    $logo_path = $shelter['logo'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['logo']['type'], $allowed)) {
            $upload_dir = 'uploads/logos/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'shelter_' . $shelter_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $filename)) {
                $logo_path = 'uploads/logos/' . $filename;
            }
        }
    }
    
    $update_query = "UPDATE shelters SET 
                      shelter_name = ?, description = ?, contact_number = ?, 
                      address = ?, logo = ?, facebook_link = ?, 
                      instagram_link = ?, website_link = ? 
                    WHERE shelter_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssssssssi", $shelter_name, $description, $contact_number, 
                             $address, $logo_path, $facebook_link, $instagram_link, 
                             $website_link, $shelter_id);
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: shelter_profile_settings.php");
        exit();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    $verify_query = "SELECT password FROM users WHERE user_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("i", $user_id);
    $verify_stmt->execute();
    $user_data = $verify_stmt->get_result()->fetch_assoc();
    
    if (password_verify($current, $user_data['password'])) {
        if ($new === $confirm) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $hashed, $user_id);
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Password changed successfully!";
            } else {
                $_SESSION['error'] = "Failed to change password.";
            }
        } else {
            $_SESSION['error'] = "New passwords do not match!";
        }
    } else {
        $_SESSION['error'] = "Current password is incorrect!";
    }
    header("Location: shelter_profile_settings.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - PawConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-paw fixed-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="shelter_dashboard.php">
                <i class="fas fa-paw"></i> PawConnect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-bell"></i></a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <span class="avatar me-2">S</span>
                            Shelter Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="shelter_profile_settings.php"><i class="fas fa-user-cog me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar-paw">
                <div class="position-sticky">
                    <div class="nav-section">Main</div>
                    <a class="nav-link" href="shelter_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                    <a class="nav-link" href="shelter_pets.php"><i class="fas fa-paw"></i> Pet Management</a>
                    <a class="nav-link" href="shelter_adoptions.php"><i class="fas fa-file-signature"></i> Adoptions</a>
                    <a class="nav-link" href="shelter_donations.php"><i class="fas fa-donate"></i> Donations</a>
                    <a class="nav-link" href="shelter_volunteers.php"><i class="fas fa-users"></i> Volunteers</a>
                    <a class="nav-link" href="shelter_products.php"><i class="fas fa-store"></i> Marketplace</a>
                    <div class="nav-divider"></div>
                    <div class="nav-section">Settings</div>
                    <a class="nav-link active" href="shelter_profile_settings.php"><i class="fas fa-cog"></i> My Profile</a>
                    <a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 px-4 py-4">
                <h4 class="mb-1">Profile Settings</h4>
                <p class="text-muted small mb-4">Manage your shelter profile and account settings.</p>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Profile Header -->
                <div class="profile-header mb-4">
                    <div class="avatar-large">
                        <?php if (!empty($shelter['logo'])): ?>
                            <img src="<?php echo $shelter['logo']; ?>" alt="Shelter Logo">
                        <?php else: ?>
                            <div class="placeholder"><?php echo strtoupper(substr($shelter['shelter_name'], 0, 2)); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info">
                        <h3><?php echo htmlspecialchars($shelter['shelter_name']); ?></h3>
                        <div class="email"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($shelter['email']); ?></div>
                        <span class="role-badge"><i class="fas fa-shield-alt me-1"></i>Shelter Admin</span>
                    </div>
                    <div class="ms-auto">
                        <label class="btn btn-paw-outline" style="cursor:pointer;">
                            <i class="fas fa-camera me-2"></i>Change Logo
                            <input type="file" form="profileForm" name="logo" style="display:none;" accept="image/*">
                        </label>
                    </div>
                </div>

                <!-- Profile Form -->
                <form id="profileForm" method="POST" enctype="multipart/form-data">
                    <div class="settings-section">
                        <div class="section-title"><i class="fas fa-building"></i>Shelter Information</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-paw-label">Shelter Name</label>
                                    <input type="text" name="shelter_name" class="form-paw" 
                                           value="<?php echo htmlspecialchars($shelter['shelter_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-paw-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-paw" 
                                           value="<?php echo htmlspecialchars($shelter['contact_number'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-paw-label">Description</label>
                                    <textarea name="description" class="form-paw" rows="3"><?php echo htmlspecialchars($shelter['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-paw-label">Address</label>
                                    <input type="text" name="address" class="form-paw" 
                                           value="<?php echo htmlspecialchars($shelter['address'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="settings-section">
                        <div class="section-title"><i class="fas fa-share-alt"></i>Social Media Links</div>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-paw-label">Facebook</label>
                                <div class="social-link-input">
                                    <i class="icon fab fa-facebook"></i>
                                    <input type="url" name="facebook_link" placeholder="facebook.com/your-page" 
                                           value="<?php echo htmlspecialchars($shelter['facebook_link'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-paw-label">Instagram</label>
                                <div class="social-link-input">
                                    <i class="icon fab fa-instagram"></i>
                                    <input type="url" name="instagram_link" placeholder="instagram.com/your-page" 
                                           value="<?php echo htmlspecialchars($shelter['instagram_link'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-paw-label">Website</label>
                                <div class="social-link-input">
                                    <i class="icon fas fa-globe"></i>
                                    <input type="url" name="website_link" placeholder="your-website.com" 
                                           value="<?php echo htmlspecialchars($shelter['website_link'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-paw-primary">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </form>

                <!-- Change Password -->
                <div class="settings-section mt-4">
                    <div class="section-title"><i class="fas fa-key"></i>Change Password</div>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-paw-label">Current Password</label>
                                    <div class="password-input-group">
                                        <input type="password" name="current_password" class="form-paw" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-paw-label">New Password</label>
                                    <div class="password-input-group">
                                        <input type="password" name="new_password" class="form-paw" id="newPass" required>
                                    </div>
                                    <div class="password-strength"><div class="bar" id="strengthBar"></div></div>
                                    <div class="password-hint">Min 8 characters with letters & numbers</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-paw-label">Confirm Password</label>
                                    <div class="password-input-group">
                                        <input type="password" name="confirm_password" class="form-paw" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-paw-secondary">
                            <i class="fas fa-exchange-alt me-2"></i>Change Password
                        </button>
                    </form>
                </div>

            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-paw">
        <div class="container text-center">
            <span>© 2026 PawConnect. All rights reserved.</span>
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Support</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple password strength indicator
        document.getElementById('newPass')?.addEventListener('input', function() {
            const val = this.value;
            const bar = document.getElementById('strengthBar');
            let strength = 0;
            if (val.length >= 8) strength++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) strength++;
            if (/\d/.test(val)) strength++;
            if (strength === 0) { bar.style.width = '0%'; bar.className = 'bar'; }
            else if (strength === 1) { bar.style.width = '33%'; bar.className = 'bar weak'; }
            else if (strength === 2) { bar.style.width = '66%'; bar.className = 'bar medium'; }
            else { bar.style.width = '100%'; bar.className = 'bar strong'; }
        });
    </script>
</body>
</html>