<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/db.php");


/* CHECK LOGIN */

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


/* GET SHELTER ID */

$sql = "SELECT shelter_id
        FROM shelters
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows == 0) {
    die("Shelter information not found.");
}


$shelter = $result->fetch_assoc();

$shelter_id = $shelter["shelter_id"];


/* ADD NEW PET */

if (isset($_POST["add_pet"])) {

    $pet_name = trim($_POST["pet_name"]);
    $species = trim($_POST["species"]);
    $breed = trim($_POST["breed"]);
    $age = intval($_POST["age"]);
    $gender = trim($_POST["gender"]);


    /* DEFAULT STATUS */

    $status = "Waiting";


/* CHECK IMAGE */

if (
    !isset($_FILES["image"]) ||
    $_FILES["image"]["error"] !== UPLOAD_ERR_OK
) {
    die("Please select a valid pet image.");
}


$image = $_FILES["image"];


/* VALIDATE IMAGE TYPE */

$finfo = finfo_open(FILEINFO_MIME_TYPE);

$image_type = finfo_file(
    $finfo,
    $image["tmp_name"]
);

finfo_close($finfo);


$allowed_types = [
    "image/jpeg",
    "image/png",
    "image/webp"
];


if (!in_array($image_type, $allowed_types)) {
    die("Only JPG, PNG, and WEBP images are allowed.");
}


/* VALIDATE IMAGE SIZE */

if ($image["size"] > 5 * 1024 * 1024) {
    die("Image size must be less than 5MB.");
}


    /* UPLOAD DIRECTORY */

    $upload_directory = "../uploads/pets/";


    if (!is_dir($upload_directory)) {
        mkdir($upload_directory, 0755, true);
    }


    /* CREATE UNIQUE FILE NAME */

    $file_extension = strtolower(
        pathinfo(
            $image["name"],
            PATHINFO_EXTENSION
        )
    );


    $new_file_name =
        time() . "_" .
        uniqid() . "." .
        $file_extension;


    $file_path =
        $upload_directory . $new_file_name;


    /* MOVE IMAGE */

    if (!move_uploaded_file(
        $image["tmp_name"],
        $file_path
    )) {
        die("Failed to upload image.");
    }


    /* INSERT PET */

    $sql = "INSERT INTO pets
            (
                shelter_id,
                pet_name,
                species,
                breed,
                age,
                gender,
                status,
                image
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";


    $stmt = $conn->prepare($sql);


    $stmt->bind_param(
        "isssisss",
        $shelter_id,
        $pet_name,
        $species,
        $breed,
        $age,
        $gender,
        $status,
        $new_file_name
    );


    if ($stmt->execute()) {

        header("Location: animals.php?success=pet_added");

        exit();

    } else {

        echo "Database Error: " . $stmt->error;

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add New Pet | PawConnect</title>

    <link rel="stylesheet"
          href="../css/add-pets.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

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

<main class="main-content">
    <section class="page-header">
        <div>

            <h1>Add New Pet</h1>

            <p>Add a new pet to your shelter.</p>

        </div>

    </section>


    <section class="dashboard-card">

        <form method="POST" enctype="multipart/form-data">


            <label>Pet Name</label>

            <input
                type="text"
                name="pet_name"
                required>


            <label>Species</label>

            <select
                name="species"
                required>

                <option value="">
                    Select Species
                </option>

                <option value="Dog">
                    Dog
                </option>

                <option value="Cat">
                    Cat
                </option>

                <option value="Other">
                    Other
                </option>

            </select>


            <label>Breed</label>

            <input
                type="text"
                name="breed"
                required>


            <label>Age</label>

            <input
                type="number"
                name="age"
                min="0"
                required>


            <label>Gender</label>

            <select
                name="gender"
                required>

                <option value="">
                    Select Gender
                </option>

                <option value="Male">
                    Male
                </option>

                <option value="Female">
                    Female
                </option>

            </select>


            <label>Pet Image</label>

            <input
                type="file"
                name="image"
                accept=".jpg,.jpeg,.png,.webp"
                required>


            <button
                type="submit"
                name="add_pet">

                <i class="fa-solid fa-plus"></i>

                Add Pet

            </button>


        </form>

    </section>

</main>

</body>

</html>