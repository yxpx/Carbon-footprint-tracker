<?php
session_start();
include "../db/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Fetch user data
    $query = "SELECT id, name, password FROM users WHERE email = $1";
    $result = pg_query_params($conn, $query, [$email]);

    if ($row = pg_fetch_assoc($result)) {
        // Verify password
        if (password_verify($password, $row["password"])) {
            $_SESSION["user_id"] = $row["id"];
            $_SESSION["user_name"] = $row["name"];
            header("Location: /views/dashboard.php");
            exit();
        } else {
            echo "Invalid password!";
        }
    } else {
        echo "User not found!";
    }
}
?>
