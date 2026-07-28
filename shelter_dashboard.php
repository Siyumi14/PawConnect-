<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shelter') {
    header("Location: login.php");
    exit();
}
include 'php/config/db_connection.php';
$shelter_id = $_SESSION['shelter_id'];

// Get counts
$pet_count = $conn->query("SELECT COUNT(*) as count FROM pets WHERE shelter_id = $shelter_id")->fetch_assoc()['count'] ?? 0;
$adoption_count = $conn->query("SELECT COUNT(*) as count FROM adoption_applications WHERE shelter_id = $shelter_id")->fetch_assoc()['count'] ?? 0;
$donation_total = $conn->query("SELECT SUM(amount) as total FROM donations WHERE shelter_id = $shelter_id")->fetch_assoc()['total'] ?? 0;
$volunteer_count = $conn->query("SELECT COUNT(DISTINCT supporter_id) as count FROM volunteer_registrations vr JOIN volunteer_events ve ON vr.event_id = ve.event_id WHERE ve.shelter_id = $shelter_id")->fetch_assoc()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PawConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-paw fixed-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#"><i class="fas fa-paw"></i> PawConnect</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-bell"></i></a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <span class="avatar me-2">S</span> Shelter
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
                    <a class="nav-link active" href="shelter_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                    <a class="nav-link" href="shelter_pets.php"><i class="fas fa-paw"></i> Pet Management</a>
                    <a class="nav-link" href="shelter_adoptions.php"><i class="fas fa-file-signature"></i> Adoptions</a>
                    <a class="nav-link" href="shelter_donations.php"><i class="fas fa-donate"></i> Donations</a>
                    <a class="nav-link" href="shelter_volunteers.php"><i class="fas fa-users"></i> Volunteers</a>
                    <a class="nav-link" href="shelter_products.php"><i class="fas fa-store"></i> Marketplace</a>
                    <div class="nav-divider"></div>
                    <div class="nav-section">Settings</div>
                    <a class="nav-link" href="shelter_profile_settings.php"><i class="fas fa-cog"></i> My Profile</a>
                    <a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </nav>

            <!-- Main -->
            <main class="col-md-9 col-lg-10 px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1">Shelter Overview</h4>
                        <p class="text-muted small">Welcome back! Here's what's happening at your shelter today.</p>
                    </div>
                    <span class="badge bg-success fs-6 px-3 py-2"><i class="fas fa-circle me-1" style="font-size:0.5rem;"></i> Active</span>
                </div>

                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon orange"><i class="fas fa-paw"></i></div>
                            <div class="stat-number"><?php echo $pet_count; ?></div>
                            <div class="stat-label">Total Pets</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon green"><i class="fas fa-file-signature"></i></div>
                            <div class="stat-number"><?php echo $adoption_count; ?></div>
                            <div class="stat-label">Adoption Applications</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon blue"><i class="fas fa-donate"></i></div>
                            <div class="stat-number">LKR <?php echo number_format($donation_total, 0); ?></div>
                            <div class="stat-label">Total Donations</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                            <div class="stat-number"><?php echo $volunteer_count; ?></div>
                            <div class="stat-label">Active Volunteers</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card-paw">
                            <div class="card-header">Recent Activity</div>
                            <div class="card-body">
                                <p class="text-muted text-center py-4">Recent activity will appear here.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card-paw">
                            <div class="card-header">Quick Actions</div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="shelter_pets.php?action=add" class="btn btn-paw-primary"><i class="fas fa-plus me-2"></i>Add New Pet</a>
                                    <a href="shelter_adoptions.php" class="btn btn-paw-outline"><i class="fas fa-file-signature me-2"></i>Review Applications</a>
                                    <a href="shelter_donations.php" class="btn btn-paw-outline"><i class="fas fa-donate me-2"></i>View Donations</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-paw">
        <div class="container text-center">
            <span>© 2026 PawConnect. All rights reserved.</span>
            <a href="#">Privacy</a> · <a href="#">Terms</a> · <a href="#">Support</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>