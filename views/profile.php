<?php
require_once(__DIR__ . "/../includes/header.php");
require_once(__DIR__ . "/../includes/navbar.php");
require_once(__DIR__ . "/../db/config.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/views/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$result = pg_query_params($conn, "SELECT name, email, household_size FROM users WHERE id = $1", [$user_id]);
$user = pg_fetch_assoc($result);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $household_size = $_POST['household_size'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Update basic info
    if (!empty($name) && !empty($email) && !empty($household_size)) {
        // Check if email is already in use by another user
        $email_check = pg_query_params($conn, "SELECT id FROM users WHERE email = $1 AND id != $2", [$email, $user_id]);
        
        if (pg_num_rows($email_check) > 0) {
            $_SESSION['error'] = "Email is already in use by another account.";
        } else {
            $update_query = "UPDATE users SET name = $1, email = $2, household_size = $3 WHERE id = $4";
            $update_result = pg_query_params($conn, $update_query, [$name, $email, $household_size, $user_id]);
            
            if ($update_result) {
                $_SESSION['success'] = "Profile updated successfully!";
                // Refresh user data
                $result = pg_query_params($conn, "SELECT name, email, household_size FROM users WHERE id = $1", [$user_id]);
                $user = pg_fetch_assoc($result);
            } else {
                $_SESSION['error'] = "Error updating profile.";
            }
        }
    }
    
    // Update password if requested
    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New passwords do not match.";
        } else {
            // Verify current password
            $password_query = pg_query_params($conn, "SELECT password FROM users WHERE id = $1", [$user_id]);
            $password_data = pg_fetch_assoc($password_query);
            
            if (password_verify($current_password, $password_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $password_update = pg_query_params($conn, "UPDATE users SET password = $1 WHERE id = $2", [$hashed_password, $user_id]);
                
                if ($password_update) {
                    $_SESSION['success'] = "Password updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating password.";
                }
            } else {
                $_SESSION['error'] = "Current password is incorrect.";
            }
        }
    }
    
    // Redirect to refresh the page and avoid form resubmission
    header("Location: " . BASE_URL . "/views/profile.php");
    exit();
}
?>

<main>
    <h1>Profile Settings</h1>
    
    <div class="card">
        <h3>Update Profile Information</h3>
        <form method="POST">
            <label for="name">Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            
            <label for="email">Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            
            <label for="household_size">Household Size (Household size refers to the number of people living in your home who share energy usage and appliances.):</label>
            <input type="number" name="household_size" min="1" value="<?php echo htmlspecialchars($user['household_size'] ?? 1); ?>" required>
            <button type="submit">Update Profile</button>
        </form>
    </div>
    
    <div class="card">
        <h3>Change Password</h3>
        <form method="POST">
            <label for="current_password">Current Password:</label>
            <input type="password" name="current_password" required>
            
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" required>
            
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" name="confirm_password" required>
            
            <button type="submit">Change Password</button>
        </form>
    </div>
    
    <div class="action-buttons">
        <a href="<?php echo BASE_URL; ?>/views/dashboard.php" class="btn">Back to Dashboard</a>
    </div>
</main>

<?php require_once(__DIR__ . "/../includes/footer.php"); ?> 