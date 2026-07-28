<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION["role"] != "shelter") {
    header("Location: ../auth/login.php");
    exit();
}


/* GET LOGGED-IN USER ID */
$user_id = $_SESSION["user_id"];

/* GET THE CORRECT SHELTER ID */
$sql = "SELECT shelter_id, shelter_name
        FROM shelters
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$shelter_result = $stmt->get_result();

if ($shelter_result->num_rows == 0) {
    die("Shelter information not found.");
}

$shelter = $shelter_result->fetch_assoc();

$shelter_id = $shelter["shelter_id"];
$shelter_name = $shelter["shelter_name"];

/* SEARCH AND FILTER */
$search = $_GET["search"] ?? "";
$status = $_GET["status"] ?? "";

/* FETCH PETS */
$sql = "SELECT *
        FROM pets
        WHERE shelter_id = ?";

$params = [$shelter_id];
$types = "i";

/* SEARCH BY PET NAME */
if (!empty($search)) {
    $sql .= " AND pet_name LIKE ?";
    $search_value = "%" . $search . "%";
    $params[] = $search_value;
    $types .= "s";
}

/* FILTER BY STATUS */
if (!empty($status)) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animal Management | PawConnect</title>
    <link rel="stylesheet" href="../css/animals.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> <!-- for the icons used -->
</head>

<body>
     <!-- SIDEBAR -->
    <aside class="sidebar">
    <div class="brand">
        <div class="brand-icon">
        <i class="fa-solid fa-paw"></i>
        </div>
        <h2><?php echo htmlspecialchars($shelter_name); ?></h2>
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

    <!-- FOOTER -->
    <div class="sidebar-footer">
        <p>Powered by</p><strong>PawConnect</strong>
    </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOP BAR -->
        <header class="topbar">
            <div>
                <h1>Animal Management</h1>
                <p>Manage all pets in your shelter.</p>
            </div>

            <div class="admin-profile">
                <div class="profile-image"><i class="fa-solid fa-user"></i></div>

                <div>
                    <strong><?php echo $_SESSION["full_name"]; ?></strong>
                    <small>Shelter Admin</small>
                </div>
            </div>
        </header>

        <!-- PAGE HEADER -->
        <section class="page-header">
            <div>
                <h2>Pet Listing</h2>
            </div>
            <a href="add-pets.php" class="add-pet-button"><i class="fa-solid fa-plus"></i> Add New Pet</a> <!-- when button clicked goes to add pets.php -->
        </section>

        <!-- SEARCH AND FILTER -->
        <section class="filter-section">
            <form method="GET" action="animals.php">
                <input type="text"
                       name="search"
                       placeholder="Search pets by name...">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="Waiting">Waiting</option>
                    <option value="Pending Adoption">Pending Adoption</option>
                    <option value="Adopted">Adopted</option>
                </select>
                <button type="submit"><i class="fa-solid fa-search"></i> Search</button>
            </form>
        </section>

        <!-- PET TABLE -->
        <section class="pets-section">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>All Pets</h2>   
                    <span>
                        <?php echo $result->num_rows; ?>
                        <?php echo ($result->num_rows == 1) ? "pet" : "pets"; ?> <!-- this part counts the number of pets in the table and displays it -->
                    </span>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Pet Name</th>
                                <th>Species</th>
                                <th>Breed</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($pet = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($pet["image"])): ?>
                                            <img
                                                src="../uploads/pets/<?php echo htmlspecialchars($pet["image"]); ?>" /*images are taken from the uploads/pets folder and displayed in the table*/
                                                alt="<?php echo htmlspecialchars($pet["pet_name"]); ?>"
                                                class="pet-table-image">
                            <?php else: ?>
                            <div class="pet-table-placeholder">
                                <i class="fa-solid fa-paw"></i>
                            </div>
                            <?php endif; ?>
                                        </td>

    <td>
        <?php echo htmlspecialchars($pet["pet_name"]); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($pet["species"]); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($pet["breed"]); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($pet["age"]); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($pet["gender"]); ?>
    </td>

    <td>
        <span class="status"><?php echo htmlspecialchars($pet["status"]); ?></span>
    </td>

    <td>
        <a href="edit-pet.php?id=<?php echo $pet["pet_id"]; ?>" class="edit-button">
            <i class="fa-solid fa-pen"></i>Edit</a>

        <a href="delete-pet.php?id=<?php echo $pet["pet_id"]; ?>" class="delete-button"
            onclick="return confirm('Are you sure you want to delete this pet?');">
            <i class="fa-solid fa-trash"></i>Delete</a>
    </td>
</tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="no-pets">No pets have been added yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</body>
</html>