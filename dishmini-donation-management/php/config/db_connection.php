<?php
$host = 'localhost';
$username = 'root';   // XAMPP default
$password = '';       // XAMPP default (හිස්)
$database = 'PawConnect_db';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>