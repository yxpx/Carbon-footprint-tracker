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

// Fetch transport modes
$transport_query = pg_query($conn, "SELECT id, name, carbon_emission FROM transport_modes ORDER BY name ASC");
$transport_modes = pg_fetch_all($transport_query);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $transport_id = $_POST['transport_id'];
    $distance_km = $_POST['distance_km'];
    $log_date = $_POST['log_date'] ?? date('Y-m-d');
    
    // Check if there's already an entry for this user, transport mode, and date
    $check_query = "SELECT id FROM user_transport WHERE user_id = $1 AND transport_id = $2 AND date = $3";
    $check_result = pg_query_params($conn, $check_query, [$user_id, $transport_id, $log_date]);
    
    if (pg_num_rows($check_result) > 0) {
        // Update existing entry
        $update_query = "UPDATE user_transport SET distance_km = $1 WHERE user_id = $2 AND transport_id = $3 AND date = $4";
        $result = pg_query_params($conn, $update_query, [$distance_km, $user_id, $transport_id, $log_date]);
    } else {
        // Insert new entry
        $insert_query = "INSERT INTO user_transport (user_id, transport_id, distance_km, date) VALUES ($1, $2, $3, $4)";
        $result = pg_query_params($conn, $insert_query, [$user_id, $transport_id, $distance_km, $log_date]);
    }
    
    if ($result) {
        $_SESSION['success'] = "Transport usage logged successfully for " . date('F j, Y', strtotime($log_date)) . "!";
        header("Location: " . BASE_URL . "/views/dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Error logging transport usage: " . pg_last_error($conn);
    }
}
?>

<main class="form-page">
    <div class="container">
        <div class="form-container">
            <h1>Log Transport Usage</h1>
            <p class="form-description">Record your daily transportation to track your carbon footprint.</p>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="transport_id">Transport Mode:</label>
                    <select name="transport_id" id="transport_id" required>
                        <option value="">Select a transport mode</option>
                        <?php foreach ($transport_modes as $mode): ?>
                            <option value="<?php echo $mode['id']; ?>">
                                <?php echo htmlspecialchars($mode['name']); ?> 
                                (<?php echo number_format($mode['carbon_emission'], 3); ?> kg CO₂/km)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="distance_km">Distance (km):</label>
                    <input type="number" name="distance_km" id="distance_km" step="0.1" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="log_date">Date:</label>
                    <input type="date" name="log_date" id="log_date" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Log Transport</button>
                    <a href="<?php echo BASE_URL; ?>/views/dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once(__DIR__ . "/../includes/footer.php"); ?>
