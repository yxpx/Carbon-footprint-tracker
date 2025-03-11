<?php
session_start();
include "../db/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
    $energy_source_id = $_POST["energy_source"];

    // Check if email already exists
    $result = pg_query_params($conn, "SELECT id FROM users WHERE email = $1", [$email]);
    if (pg_num_rows($result) > 0) {
        echo "Email already exists!";
        exit();
    }

    // Insert user into database
    $query = "INSERT INTO users (name, email, password, energy_source_id) VALUES ($1, $2, $3, $4) RETURNING id";
    $result = pg_query_params($conn, $query, [$name, $email, $password, $energy_source_id]);

    if ($row = pg_fetch_assoc($result)) {
        $_SESSION["user_id"] = $row["id"];
        $_SESSION["user_name"] = $name;
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Registration failed!";
    }
}
?>
