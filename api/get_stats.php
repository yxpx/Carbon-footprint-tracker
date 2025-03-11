<?php
session_start();
require_once("../db/config.php");

$user_id = $_SESSION["user_id"];

// Get total energy consumption
$query = "SELECT SUM(energy_used) AS total_energy FROM energy_usage WHERE user_id = $1";
$result = pg_query_params($conn, $query, [$user_id]);
$totalEnergy = pg_fetch_result($result, 0, "total_energy") ?: 0;

// Get carbon footprint (assuming 0.92 kg CO₂ per kWh)
$carbonFootprint = $totalEnergy * 0.92;

// Get recent entries
$query = "SELECT e.date, a.name AS appliance, e.energy_used 
          FROM energy_usage e 
          JOIN appliances a ON e.appliance_id = a.id 
          WHERE e.user_id = $1 
          ORDER BY e.date DESC 
          LIMIT 5";
$result = pg_query_params($conn, $query, [$user_id]);
$entries = pg_fetch_all($result) ?: [];

echo json_encode([
    "totalEnergy" => $totalEnergy,
    "carbonFootprint" => round($carbonFootprint, 2),
    "entries" => $entries
]);
?>
