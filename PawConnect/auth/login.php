<?php

session_start();

include("../config/db.php");

/* error_reporting(E_ALL);
ini_set('display_errors', 1);*/

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["role"] = $user["role"];

            if ($user["role"] == "supporter") {
                header("Location: ../supporter/dashboard.php");
            } else {
                header("Location: ../shelter/dashboard.php");
            }
            exit();

        } else {
            $message = "Incorrect password.";
        }

    } else {
        $message = "No account found with this email.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PawConnect</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo">🐾</div>
            <h1>Welcome Back</h1>
            <p class="subtitle">Continue your journey towards creating happier homes.</p>
            <?php if ($message != "") { ?>
                <p class="error-message">
                    <?php echo $message; ?>
                </p>
            <?php } ?>

            <form method="POST">
            <label>Email Address</label>
                <input
                    type="email"
                    name="email"
                    value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>"
                    placeholder="Enter your email"
                    required>

            <label>Password</label>
                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="new-password"
                    required>

            <button type="submit">Login</button>
            </form>
            <p class="account-link">Don't have an account?<a href="registration.php">Create an account</a></p>
        </div>
    </div>
</body>
</html>