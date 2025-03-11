<?php
require_once(__DIR__ . "/../includes/header.php");
require_once(__DIR__ . "/../includes/navbar.php");
require_once(__DIR__ . "/../db/config.php");

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/views/dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please fill in all fields.";
    } else {
        // Check if user exists
        $query = "SELECT id, name, password FROM users WHERE email = $1";
        $result = pg_query_params($conn, $query, [$email]);
        
        if (pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                
                header("Location: " . BASE_URL . "/views/dashboard.php");
                exit();
            } else {
                $_SESSION['error'] = "Invalid password.";
            }
        } else {
            $_SESSION['error'] = "User not found.";
        }
    }
}
?>

<main>
    <h1>Login</h1>
    
    <div class="card">
        <form method="POST">
            <label for="email">Email:</label>
            <input type="email" name="email" required>
            
            <label for="password">Password:</label>
            <input type="password" name="password" required>
            
            <button type="submit">Login</button>
        </form>
        
        <p class="text-center">Don't have an account? <a href="<?php echo BASE_URL; ?>/views/register.php">Register here</a></p>
    </div>
</main>

<?php require_once(__DIR__ . "/../includes/footer.php"); ?>
