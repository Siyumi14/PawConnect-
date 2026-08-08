<?php
session_start();
include 'php/config/db_connection.php';



if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shelter') {
    header("Location: ../login.php");
    exit();
}

$shelter_id = $_SESSION['shelter_id'];

// Get monthly donation data
$monthly_query = "SELECT 
                    DATE_FORMAT(donation_date, '%Y-%m') as month,
                    SUM(amount) as total
                  FROM donations
                  WHERE shelter_id = ?
                  GROUP BY DATE_FORMAT(donation_date, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 12";

$monthly_stmt = $conn->prepare($monthly_query);
$monthly_stmt->bind_param("i", $shelter_id);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();

$months = [];
$amounts = [];

while ($row = $monthly_result->fetch_assoc()) {
    $months[] = $row['month'];
    $amounts[] = (float)$row['total'];
}

// Reverse arrays for chronological order
$months = array_reverse($months);
$amounts = array_reverse($amounts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Tracking - PawConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/shelter_navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/shelter_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Donation Tracking</h1>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line"></i> Monthly Donation Trends</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="donationChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-users"></i> Top Donors</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $top_donors_query = "SELECT 
                                                        u.full_name,
                                                        COUNT(d.donation_id) as donation_count,
                                                        SUM(d.amount) as total_donated
                                                    FROM donations d
                                                    JOIN users u ON d.supporter_id = u.user_id
                                                    WHERE d.shelter_id = ?
                                                    GROUP BY d.supporter_id
                                                    ORDER BY total_donated DESC
                                                    LIMIT 5";
                                
                                $top_stmt = $conn->prepare($top_donors_query);
                                $top_stmt->bind_param("i", $shelter_id);
                                $top_stmt->execute();
                                $top_result = $top_stmt->get_result();
                                ?>
                                
                                <ul class="list-group">
                                    <?php if ($top_result->num_rows > 0): ?>
                                        <?php while ($donor = $top_result->fetch_assoc()): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($donor['full_name']); ?>
                                                <span class="badge bg-primary rounded-pill">
                                                    LKR <?php echo number_format($donor['total_donated'], 2); ?>
                                                    <small class="text-muted">(<?php echo $donor['donation_count']; ?> donations)</small>
                                                </span>
                                            </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item text-center">No donors yet</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-calendar-alt"></i> Recent Donations</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $recent_query = "SELECT 
                                                    d.amount,
                                                    d.donation_date,
                                                    u.full_name
                                                FROM donations d
                                                JOIN users u ON d.supporter_id = u.user_id
                                                WHERE d.shelter_id = ?
                                                ORDER BY d.donation_date DESC
                                                LIMIT 10";
                                
                                $recent_stmt = $conn->prepare($recent_query);
                                $recent_stmt->bind_param("i", $shelter_id);
                                $recent_stmt->execute();
                                $recent_result = $recent_stmt->get_result();
                                ?>
                                
                                <ul class="list-group">
                                    <?php if ($recent_result->num_rows > 0): ?>
                                        <?php while ($donation = $recent_result->fetch_assoc()): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($donation['full_name']); ?>
                                                <span>
                                                    <span class="badge bg-success rounded-pill">
                                                        LKR <?php echo number_format($donation['amount'], 2); ?>
                                                    </span>
                                                    <small class="text-muted ms-2">
                                                        <?php echo date('Y-m-d H:i', strtotime($donation['donation_date'])); ?>
                                                    </small>
                                                </span>
                                            </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item text-center">No recent donations</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('donationChart').getContext('2d');
        const donationChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Monthly Donations (LKR)',
                    data: <?php echo json_encode($amounts); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'LKR ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>