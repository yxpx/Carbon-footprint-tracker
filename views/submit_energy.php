<?php
session_start();
require_once("../db/config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["user_id"];
    $appliance_id = $_POST["appliance_id"];
    $hours_used = $_POST["hours_used"];

    // Get power rating of selected appliance
    $query = "SELECT power_rating FROM appliances WHERE id = $1";
    $result = pg_query_params($conn, $query, [$appliance_id]);
    $row = pg_fetch_assoc($result);

    if (!$row) {
        die("Invalid appliance selected.");
    }

    $power_rating = $row["power_rating"];  // Power in kW
    $energy_used = $power_rating * $hours_used;  // Energy = Power × Time (kWh)

    // Store in database
    $query = "INSERT INTO energy_usage (user_id, appliance_id, energy_used) VALUES ($1, $2, $3)";
    $result = pg_query_params($conn, $query, [$user_id, $appliance_id, $energy_used]);

    if ($result) {
        header("Location: ../views/dashboard.php");
        exit();
    } else {
        echo "Error saving data.";
    }
}
?>
