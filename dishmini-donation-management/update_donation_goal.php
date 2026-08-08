<?php
session_start();
include 'php/config/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'shelter') {
    header("Location: login.php");
    exit();
}

$shelter_id = $_SESSION['shelter_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal_amount = $_POST['goal_amount'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'] ?: null;

    $check_query = "SELECT goal_id FROM donation_goals WHERE shelter_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $shelter_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $update_query = "UPDATE donation_goals SET goal_amount = ?, start_date = ?, end_date = ? WHERE shelter_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("dssi", $goal_amount, $start_date, $end_date, $shelter_id);
        $update_stmt->execute();
    } else {
        $insert_query = "INSERT INTO donation_goals (shelter_id, goal_amount, start_date, end_date) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("idss", $shelter_id, $goal_amount, $start_date, $end_date);
        $insert_stmt->execute();
    }

    header("Location: shelter_donations.php");
    exit();
}
?>