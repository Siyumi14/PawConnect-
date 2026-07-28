<?php
session_start();
include 'php/config/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shelter') {
    header("Location: login.php");
    exit();
}

$shelter_id = $_SESSION['shelter_id'];

// Get total donations
$total_query = "SELECT SUM(amount) as total FROM donations WHERE shelter_id = ?";
$total_stmt = $conn->prepare($total_query);
$total_stmt->bind_param("i", $shelter_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_donations = $total_row['total'] ?? 0;

// Get current goal
$goal_query = "SELECT * FROM donation_goals WHERE shelter_id = ? ORDER BY created_at DESC LIMIT 1";
$goal_stmt = $conn->prepare($goal_query);
$goal_stmt->bind_param("i", $shelter_id);
$goal_stmt->execute();
$goal_result = $goal_stmt->get_result();
$current_goal = $goal_result->fetch_assoc();
$goal_amount = $current_goal['goal_amount'] ?? 1;
$progress_percent = min(($total_donations / $goal_amount) * 100, 100);

// Get recent donations
$donations_query = "SELECT d.*, u.full_name, u.email 
                    FROM donations d 
                    JOIN users u ON d.supporter_id = u.user_id 
                    WHERE d.shelter_id = ? 
                    ORDER BY d.donation_date DESC LIMIT 10";
$donations_stmt = $conn->prepare($donations_query);
$donations_stmt->bind_param("i", $shelter_id);
$donations_stmt->execute();
$donations_result = $donations_stmt->get_result();

// Get top donors
$top_donors_query = "SELECT u.full_name, COUNT(d.donation_id) as count, SUM(d.amount) as total
                     FROM donations d 
                     JOIN users u ON d.supporter_id = u.user_id 
                     WHERE d.shelter_id = ? 
                     GROUP BY d.supporter_id 
                     ORDER BY total DESC LIMIT 5";
$top_stmt = $conn->prepare($top_donors_query);
$top_stmt->bind_param("i", $shelter_id);
$top_stmt->execute();
$top_result = $top_stmt->get_result();
$top_donors = $top_result->fetch_all(MYSQLI_ASSOC);

// Donation count
$count_query = "SELECT COUNT(*) as count FROM donations WHERE shelter_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $shelter_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$donation_count = $count_result->fetch_assoc()['count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Management - PawConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/donation.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-paw fixed-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="shelter_dashboard.php">
                <i class="fas fa-paw" style="color:var(--primary);"></i> PawConnect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-bell"></i>
                            <span class="notification-dot"></span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <span class="avatar" style="background:var(--primary);">S</span>
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
                    <a class="nav-link" href="shelter_dashboard.php">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                    <a class="nav-link" href="shelter_pets.php">
                        <i class="fas fa-paw"></i> Pet Management
                    </a>
                    <a class="nav-link" href="shelter_adoptions.php">
                        <i class="fas fa-file-signature"></i> Adoptions
                    </a>
                    <a class="nav-link active" href="shelter_donations.php">
                        <i class="fas fa-donate"></i> Donations
                    </a>
                    <a class="nav-link" href="shelter_volunteers.php">
                        <i class="fas fa-users"></i> Volunteers
                    </a>
                    <a class="nav-link" href="shelter_products.php">
                        <i class="fas fa-store"></i> Marketplace
                    </a>
                    <div class="nav-divider"></div>
                    <div class="nav-section">Settings</div>
                    <a class="nav-link" href="shelter_profile_settings.php">
                        <i class="fas fa-cog"></i> My Profile
                    </a>
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 px-4 py-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1" style="color:var(--text);">Donation Management</h4>
                        <p class="text-muted small" style="color:var(--text-muted);">Track and manage all donations received by your shelter.</p>
                    </div>
                    <button class="btn-paw-primary" data-bs-toggle="modal" data-bs-target="#goalModal">
                        <i class="fas fa-bullseye me-2"></i>Set Goal
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon primary"><i class="fas fa-hand-holding-heart"></i></div>
                            <div class="stat-number">LKR <?php echo number_format($total_donations, 0); ?></div>
                            <div class="stat-label">Total Donations</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon secondary"><i class="fas fa-users"></i></div>
                            <div class="stat-number"><?php echo $donation_count; ?></div>
                            <div class="stat-label">Total Donors</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon accent"><i class="fas fa-bullseye"></i></div>
                            <div class="stat-number">LKR <?php echo number_format($goal_amount, 0); ?></div>
                            <div class="stat-label">Goal Amount</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon brown"><i class="fas fa-chart-line"></i></div>
                            <div class="stat-number"><?php echo number_format($progress_percent, 1); ?>%</div>
                            <div class="stat-label">Progress</div>
                        </div>
                    </div>
                </div>

                <!-- Goal Progress Card -->
                <div class="card-paw mb-4">
                    <div class="card-header">
                        <i class="fas fa-bullseye" style="color:var(--primary);"></i> Donation Goal Progress
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-semibold" style="color:var(--text);">LKR <?php echo number_format($total_donations, 0); ?></span>
                                    <span style="color:var(--text-muted);">Goal: LKR <?php echo number_format($goal_amount, 0); ?></span>
                                </div>
                                <div class="progress" style="height: 12px; background:var(--border-light); border-radius:20px;">
                                    <div class="progress-bar" style="width: <?php echo $progress_percent; ?>%; background:linear-gradient(90deg, var(--primary), var(--secondary)); border-radius:20px;"></div>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <span class="badge-paw primary" style="font-size:0.9rem; padding:8px 20px;">
                                    <?php echo number_format($progress_percent, 1); ?>% Complete
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Donations -->
                    <div class="col-lg-7">
                        <div class="card-paw">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-clock" style="color:var(--primary);"></i> Recent Donations</span>
                                <a href="#" class="text-decoration-none small" style="color:var(--primary);">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if ($donations_result->num_rows > 0): ?>
                                    <?php while ($donation = $donations_result->fetch_assoc()): ?>
                                        <div class="donation-item">
                                            <div class="donor-avatar">
                                                <?php echo strtoupper(substr($donation['full_name'], 0, 2)); ?>
                                            </div>
                                            <div class="donor-info">
                                                <div class="name"><?php echo htmlspecialchars($donation['full_name']); ?></div>
                                                <div class="email"><?php echo htmlspecialchars($donation['email']); ?></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="donation-amount">LKR <?php echo number_format($donation['amount'], 2); ?></div>
                                                <div class="donation-date"><?php echo date('d M Y', strtotime($donation['donation_date'])); ?></div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-center py-4" style="color:var(--text-muted);">No donations received yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Top Donors -->
                    <div class="col-lg-5">
                        <div class="card-paw">
                            <div class="card-header">
                                <i class="fas fa-trophy" style="color:var(--secondary);"></i> Top Donors
                            </div>
                            <div class="card-body">
                                <?php if (!empty($top_donors)): ?>
                                    <?php foreach ($top_donors as $index => $donor): ?>
                                        <div class="top-donor">
                                            <div class="rank <?php echo $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : '')); ?>">
                                                <?php echo $index + 1; ?>
                                            </div>
                                            <div class="info">
                                                <div class="name"><?php echo htmlspecialchars($donor['full_name']); ?></div>
                                                <div class="count"><?php echo $donor['count']; ?> donations</div>
                                            </div>
                                            <div class="amount">LKR <?php echo number_format($donor['total'], 2); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center py-4" style="color:var(--text-muted);">No donors yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Set Goal Modal -->
    <div class="modal fade modal-paw" id="goalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-bullseye me-2" style="color:var(--primary);"></i>Set Donation Goal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="update_donation_goal.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-paw-label">Goal Amount (LKR)</label>
                            <input type="number" step="0.01" name="goal_amount" class="form-paw" 
                                   value="<?php echo $current_goal['goal_amount'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-paw-label">Start Date</label>
                            <input type="date" name="start_date" class="form-paw" 
                                   value="<?php echo $current_goal['start_date'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-paw-label">End Date (Optional)</label>
                            <input type="date" name="end_date" class="form-paw">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-paw-ghost" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-paw-primary">Save Goal</button>
                    </div>
                </form>
            </div>
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
</body>
</html>