<?php
include("../db/config.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$appliance_id = filter_input(INPUT_POST, 'appliance_id', FILTER_VALIDATE_INT);
$hours_used = filter_input(INPUT_POST, 'hours_used', FILTER_VALIDATE_FLOAT);

// Validate input
if (!$appliance_id || !$hours_used || $hours_used < 0) {
    $_SESSION['error'] = "Invalid input data.";
    header("Location: ../views/log_energy.php");
    exit();
}

// Get appliance power rating
$power_query = "SELECT power_rating FROM appliances WHERE id = $1";
$power_result = pg_query_params($conn, $power_query, [$appliance_id]);
$appliance = pg_fetch_assoc($power_result);
$power_rating = $appliance['power_rating'];

// Calculate energy consumption in kWh
$kwh_consumed = ($power_rating * $hours_used) / 1000;

// Insert energy usage
$query = "INSERT INTO energy_usage (user_id, date, kwh_consumed) 
          VALUES ($1, CURRENT_DATE, $2)
          ON CONFLICT (user_id, date) 
          DO UPDATE SET kwh_consumed = energy_usage.kwh_consumed + EXCLUDED.kwh_consumed";

$result = pg_query_params($conn, $query, [$user_id, $kwh_consumed]);

if (!$result) {
    $_SESSION['error'] = "Error saving energy data.";
    header("Location: ../views/log_energy.php");
    exit();
}

$_SESSION['success'] = "Energy usage added successfully!";
header("Location: ../views/dashboard.php");
?> 