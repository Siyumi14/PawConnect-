<?php

session_start();

/*error_reporting(E_ALL);
ini_set('display_errors', 1);*/

include("../config/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION["role"] != "shelter") {
    header("Location: ../auth/login.php");
    exit();
}

$shelter_id = $_SESSION["user_id"];

/* makes it dynamic */ 
/* TOTAL PETS */
$sql = "SELECT COUNT(*) AS total_pets
        FROM pets
        WHERE shelter_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shelter_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total_pets = $row["total_pets"];


/* PENDING APPLICATIONS */
$sql = "SELECT COUNT(*) AS pending_applications
        FROM adoption_applications
        WHERE shelter_id = ?
        AND status = 'Pending'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shelter_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$pending_applications = $row["pending_applications"];


/* CUSTOMER ORDERS */
$sql = "SELECT COUNT(*) AS customer_orders
        FROM orders
        WHERE shelter_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shelter_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$customer_orders = $row["customer_orders"];


/* TOTAL DONATIONS */
$sql = "SELECT COALESCE(SUM(amount), 0) AS total_donations
        FROM donations
        WHERE shelter_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shelter_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total_donations = $row["total_donations"];

/* AVAILABLE FOR ADOPTION */
$sql = "SELECT COUNT(*) AS available_pets
        FROM pets
        WHERE shelter_id = ?
        AND status = 'Available'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shelter_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$available_pets = $row["available_pets"];

/* PENDING ADOPTION */
$sql = "SELECT COUNT(*) AS pending_pets
        FROM pets
        WHERE shelter_id = ?
        AND status = 'Pending'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shelter_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$pending_pets = $row["pending_pets"];

/* ADOPTED */
$sql = "SELECT COUNT(*) AS adopted_pets
        FROM pets
        WHERE shelter_id = ?
        AND status = 'Adopted'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shelter_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$adopted_pets = $row["adopted_pets"];

/* RECENT ADOPTION APPLICATIONS */
$sql = "SELECT 
            aa.application_id, /* aa - adoption application table */
            aa.status,
            aa.application_date,

            p.pet_name AS pet_name, /* p - pet table */
            p.image AS pet_image,

            u.full_name AS adopter_name /* u - users table */

        FROM adoption_applications aa

        INNER JOIN pets p
            ON aa.pet_id = p.pet_id

        INNER JOIN users u
            ON aa.supporter_id = u.user_id

        WHERE aa.shelter_id = ?

        ORDER BY aa.application_date DESC

        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shelter_id);
$stmt->execute();

$recent_applications = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shelter Dashboard | PawConnect</title>
    <link rel="stylesheet" href="../css/shelter-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> <!-- for the icons to work -->
</head>

<body>
    <!-- SIDEBAR brand section -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">🐾</div>
            <h2><?php echo $_SESSION["full_name"]; ?></h2>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active"><span><i class="fa-solid fa-chart-line"></i></span>Dashboard</a>
            <a href="animals.php" class="menu-item"><span><i class="fa-solid fa-paw"></i></span>Animal Management</a>
            <a href="adoption.php" class="menu-item"><span><i class="fa-solid fa-clipboard-list"></i></span>Adoption Management</a>
            <a href="marketplace.php" class="menu-item"><span><i class="fa-solid fa-shopping-cart"></i></span>Marketplace</a>
            <a href="donations.php" class="menu-item"><span><i class="fa-solid fa-donate"></i></span>Donations</a>
            <a href="volunteers.php" class="menu-item"><span><i class="fa-solid fa-hands-helping"></i></span>Volunteers</a>
            <a href="about.php" class="menu-item"><span><i class="fa-solid fa-circle-info"></i></span>About Us</a>
            <a href="settings.php" class="menu-item"><span><i class="fa-solid fa-gear"></i></span>Profile Settings</a>
        </nav>
            <a href="../auth/logout.php" class="logout"><span><i class="fa-solid fa-sign-out-alt"></i></span>Logout</a>

    <!-- Platform Footer -->
    <div class="sidebar-footer">
        <p>Powered by</p><strong>PawConnect</strong>
    </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOP BAR -->
        <header class="topbar">
            <div>
                <h1>Dashboard</h1>
                <p>Manage your shelter activities from one place.</p>
            </div>
            <div class="admin-profile">
                <div class="profile-image"><i class="fa-solid fa-user"></i></div>
                <div>
                    <strong>
                        <?php echo $_SESSION["full_name"]; ?>
                    </strong>
                    <small>Shelter Admin</small>
                </div>
            </div>
        </header>

        <!-- WELCOME SECTION -->
        <section class="welcome-section">
            <div>
                <h2>
                    Welcome back,
                    <?php echo $_SESSION["full_name"]; ?>! 
                </h2>
                <p>Here's what's happening with your shelter today.</p>
            </div>
        </section>

        <!-- STATISTICS - dynamic -->
        <section class="statistics">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-paw"></i></div>
                <div>
                    <!-- PHP is used to dynamically get values from the DB -->
                    <h3><?php echo $total_pets; ?></h3> 
                    <p>Total Pets</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-list"></i></div>
                <div>    
                    <h3><?php echo $pending_applications; ?></h3>
                    <p>Pending Applications</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-shopping-cart"></i></div>
                <div>
                    <h3><?php echo $customer_orders; ?></h3>
                    <p>Customer Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-coins"></i></div>
                <div>
                    <h3>Rs. <?php echo number_format($total_donations, 2); ?></h3>
                    <p>Total Donations</p>       
                </div>
            </div>
        </section>

        <!-- DASHBOARD CONTENT -->
        <section class="dashboard-grid">

            <!-- RECENT ADOPTION APPLICATIONS - dynamic -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Recent Adoption Applications</h2>
                    <a href="adoption.php">View All</a>
                </div>

        <?php if ($recent_applications->num_rows > 0): ?>
            <?php while ($application = $recent_applications->fetch_assoc()): ?>
                <div class="application-item">
                    <div class="pet-avatar">
                        <?php if (!empty($application["pet_image"])): ?>
                            <img src="../uploads/pets/dog1.jpeg<?= htmlspecialchars($application["pet_image"]) ?>" alt="<?= htmlspecialchars($application["pet_name"]) ?>"> 
                        <?php else: ?>
                            <i class="fa-solid fa-paw"></i>
                        <?php endif; ?>
                    </div>

                    <div>
                    <strong><?= htmlspecialchars($application["pet_name"]) ?></strong>
                    <p>Application from<?= htmlspecialchars($application["adopter_name"]) ?></p>
                    </div>
                    <span class="status <?= strtolower($application["status"]) ?>"><?= htmlspecialchars($application["status"]) ?></span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
        <p class="no-data">No recent adoption applications.</p>
        <?php endif; ?>

            <!-- PET STATUS - dynamic -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Pet Overview</h2>
                    <a href="animals.php">Manage Pets</a>
                </div>

                <div class="pet-status-row">
                    <span>Available for Adoption</span>
                    <strong><?= $available_pets ?></strong>
                </div>

                <div class="pet-status-row">
                    <span>Pending Adoption</span>
                    <strong><?= $pending_pets ?></strong>
                </div>

                <div class="pet-status-row">
                    <span>Adopted</span>
                    <strong><?= $adopted_pets ?></strong>
                </div>
            </div>
        </section>

<!-- RECENT SHELTER ACTIVITY -->
<section class="dashboard-card recent-activity">
    <div class="card-header">
        <h2>Recent Shelter Activity</h2>
        <a href="animals.php">View Details</a>
    </div>

    <div class="activity-list">
        <!-- Activity 1 -->
        <div class="activity-item">
            <div class="activity-icon"><i class="fa-solid fa-paw"></i></div>
            <div class="activity-details">
                <strong>New pet added</strong>
                <p>Max was added to the shelter.</p>
                <small>2 hours ago</small>
            </div>
        </div>

        <!-- Activity 2 -->
        <div class="activity-item">
            <div class="activity-icon"><i class="fa-solid fa-list"></i></div>
            <div class="activity-details">
                <strong>New adoption application</strong>
                <p>A new application was submitted for Luna.</p>
                <small>5 hours ago</small>
            </div>
        </div>

        <!-- Activity 3 -->
        <div class="activity-item">
            <div class="activity-icon"><i class="fa-solid fa-coins"></i></div>
            <div class="activity-details">
                <strong>New donation received</strong>
                <p>A donation of Rs. 5,000 was received.</p>
                <small>Yesterday</small>
            </div>
        </div>

        <!-- Activity 4 -->
        <div class="activity-item">
            <div class="activity-icon"><i class="fa-solid fa-shopping-cart"></i></div>
            <div class="activity-details">
                <strong>New customer order</strong>
                <p>A new marketplace order was placed.</p>
                <small>Yesterday</small>
            </div>
        </div>
    </div>
</section>
    </main>

</body>
</html>