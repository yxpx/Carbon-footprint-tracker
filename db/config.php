<?php
// Database connection parameters
$host = "localhost";
$dbname = "energy_tracker";
$user = "postgres";
$password = "password"; // Enter your password 

// Establish connection
$conn_string = "host=$host dbname=$dbname user=$user password=$password";
$conn = pg_connect($conn_string);

// Check connection
if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

// Set timezone
date_default_timezone_set('UTC');
?>
