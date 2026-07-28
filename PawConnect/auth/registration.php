<?php

include("../config/db.php");

/*error_reporting(E_ALL);
ini_set('display_errors', 1); */


$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    //data collection from the form by user
    $full_name = $_POST["full_name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $role = $_POST["role"];  //shelter & pet supporter(adopter, donor, volunteer)

    //hashes the PW for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

// INSERT USER INTO USERS TABLE

$sql = "INSERT INTO users
        (full_name, email, password, role)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssss",
    $full_name,
    $email,
    $hashed_password,
    $role
);

if ($stmt->execute()) {

    // GET THE NEW USER'S ID
    $user_id = $conn->insert_id;


    // IF THE USER IS A SHELTER
    if ($role == "shelter") {

        // Use the registered name as the initial shelter name
        $shelter_name = $full_name;

        // Default values for the other shelter fields
        $description = "";
        $contact_number = "";
        $address = "";
        $logo = "";
        $facebook_link = "";
        $instagram_link = "";
        $website_link = "";


        // INSERT SHELTER INFORMATION
        $shelter_sql = "INSERT INTO shelters
        (
            user_id,
            shelter_name,
            description,
            contact_number,
            address,
            logo,
            facebook_link,
            instagram_link,
            website_link
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";


        $shelter_stmt = $conn->prepare($shelter_sql);

        $shelter_stmt->bind_param(
            "issssssss",
            $user_id,
            $shelter_name,
            $description,
            $contact_number,
            $address,
            $logo,
            $facebook_link,
            $instagram_link,
            $website_link
        );

        if (!$shelter_stmt->execute()) {

            echo "Shelter registration error: "
                 . $shelter_stmt->error;
        }
    }
    echo "Registration successful.";
}else {
    echo "Registration error: " . $stmt->error;
}

    header("Location: login.php?success=registered&email=" . urlencode($email)); //this part auto directs the user to the login page after successful registration
    exit();
    } else {
        $message = "Error: Email may already be registered.";
    }

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | PawConnect</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> <!-- for the icons to work -->
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo">🐾</div>
            <h1>Create Your Account</h1>
            <p class="subtitle">Join our community and help give pets a loving home.</p>
            <?php if ($message != "") { ?>
                <p class="message">
                    <?php echo $message; ?>
                </p>
            <?php } ?>

            <form method="POST">
                <label>Full Name</label>
                <input
                    type="text"
                    name="full_name"
                    placeholder="Enter your full name"
                    required>

                <label>Email Address</label>
                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required>

                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    placeholder="Create a password"
                    required>

                <center><label>I want to register as</label></center>
                <div class="role-selection">

                    <label class="role-card">
                        <input
                            type="radio"
                            name="role"
                            value="supporter"
                            required>

                        <div class="role-content">
                            <span class="role-icon"><i class="fas fa-home"></i></span>
                            <strong>Pet Supporter</strong>
                            <small>Adopt, donate, sponsor, and support animals in need.</small>
                        </div>
                    </label>

                    <label class="role-card">
                        <input
                            type="radio"
                            name="role"
                            value="shelter"
                            required>
                        
                        <div class="role-content">
                            <span class="role-icon"><i class="fas fa-paw"></i></span>
                            <strong>Shelter</strong>
                            <small>Manage pets and adoption applications.</small>
                        </div>
                    </label>
                </div>
                <button type="submit">Create Account</button>
            </form>
            <p class="account-link">Already have an account?<a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>