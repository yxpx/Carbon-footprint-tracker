<?php
$host = "localhost";
$dbname = "energy_tracker";
$user = "postgres";
$password = "password";

$conn = pg_connect("host=$host dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Database connection failed: " . pg_last_error());
}
?>
