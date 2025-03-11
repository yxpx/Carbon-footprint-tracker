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
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $household_size = $_POST['household_size'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = $1";
        $check_result = pg_query_params($conn, $check_query, [$email]);
        
        if (pg_num_rows($check_result) > 0) {
            $_SESSION['error'] = "Email already in use.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_query = "INSERT INTO users (name, email, password, household_size) VALUES ($1, $2, $3, $4) RETURNING id";
            $insert_result = pg_query_params($conn, $insert_query, [$name, $email, $hashed_password, $household_size]);
            
            if ($insert_result) {
                $user = pg_fetch_assoc($insert_result);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $name;
                
                $_SESSION['success'] = "Registration successful!";
                header("Location: " . BASE_URL . "/views/dashboard.php");
                exit();
            } else {
                $_SESSION['error'] = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<main>
    <h1>Register</h1>
    
    <div class="card">
        <form method="POST">
            <label for="name">Name:</label>
            <input type="text" name="name" required>
            
            <label for="email">Email:</label>
            <input type="email" name="email" required>
            
            <label for="password">Password:</label>
            <input type="password" name="password" required>
            
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" required>
            
            <label for="household_size">Household Size (Household size refers to the number of people living in your home who share energy usage and appliances.):</label>
            <input type="number" name="household_size" min="1" value="1">
            
            <button type="submit">Register</button>
        </form>
        
        <p class="text-center">Already have an account? <a href="<?php echo BASE_URL; ?>/views/login.php">Login here</a></p>
    </div>
</main>

<?php require_once(__DIR__ . "/../includes/footer.php"); ?>