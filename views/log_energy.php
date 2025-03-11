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

// Fetch appliances from DB
$query = "SELECT id, name, power_rating FROM appliances ORDER BY name ASC";
$result = pg_query($conn, $query);
$appliances = pg_fetch_all($result);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $appliance_id = $_POST['appliance_id'];
    $usage_hours = $_POST['usage_hours'];
    $log_type = $_POST['log_type'];
    
    // Get appliance power rating
    $power_query = "SELECT power_rating FROM appliances WHERE id = $1";
    $power_result = pg_query_params($conn, $power_query, [$appliance_id]);
    $appliance = pg_fetch_assoc($power_result);
    $power_rating = $appliance['power_rating'];
    
    // Calculate energy consumption in kWh
    $kwh_consumed = ($power_rating * $usage_hours) / 1000;
    
    if ($log_type === 'single') {
        // Single day logging
        $log_date = $_POST['log_date'];
        
        $insert_query = "INSERT INTO energy_usage (user_id, date, kwh_consumed) 
                         VALUES ($1, $2, $3)
                         ON CONFLICT (user_id, date) 
                         DO UPDATE SET kwh_consumed = energy_usage.kwh_consumed + EXCLUDED.kwh_consumed";
        
        $insert_result = pg_query_params($conn, $insert_query, [$user_id, $log_date, $kwh_consumed]);
        
        if ($insert_result) {
            $_SESSION['success'] = "Energy usage logged successfully for " . date('F j, Y', strtotime($log_date)) . "!";
        } else {
            $_SESSION['error'] = "Error logging energy usage.";
        }
    } else {
        // Multiple days logging
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        // Validate dates
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        
        if ($start > $end) {
            $_SESSION['error'] = "Start date cannot be after end date.";
        } else {
            // Insert for each day in the range
            $interval = new DateInterval('P1D');
            $date_range = new DatePeriod($start, $interval, $end->modify('+1 day'));
            
            $success_count = 0;
            foreach ($date_range as $date) {
                $formatted_date = $date->format('Y-m-d');
                
                $insert_query = "INSERT INTO energy_usage (user_id, date, kwh_consumed) 
                                 VALUES ($1, $2, $3)
                                 ON CONFLICT (user_id, date) 
                                 DO UPDATE SET kwh_consumed = energy_usage.kwh_consumed + EXCLUDED.kwh_consumed";
                
                $insert_result = pg_query_params($conn, $insert_query, [$user_id, $formatted_date, $kwh_consumed]);
                
                if ($insert_result) {
                    $success_count++;
                }
            }
            
            if ($success_count > 0) {
                $_SESSION['success'] = "Energy usage logged successfully for $success_count days!";
            } else {
                $_SESSION['error'] = "Error logging energy usage.";
            }
        }
    }
    
    header("Location: " . BASE_URL . "/views/dashboard.php");
    exit();
}
?>

<main>
    <h2>Log Your Energy Usage</h2>
    
    <div class="card">
        <div class="log-options">
            <button class="log-option active" data-target="single-day">Single Day</button>
            <button class="log-option" data-target="multiple-days">Multiple Days</button>
        </div>
        
        <form method="POST" id="energy-form">
            <div id="single-day" class="log-section active">
                <input type="hidden" name="log_type" value="single">
                
                <label for="log_date">Date:</label>
                <input type="date" name="log_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div id="multiple-days" class="log-section">
                <input type="hidden" name="log_type" value="multiple">
                
                <label for="start_date">Start Date:</label>
                <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                
                <label for="end_date">End Date:</label>
                <input type="date" name="end_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <label for="appliance_id">Select Appliance:</label>
            <select name="appliance_id" required>
                <?php foreach ($appliances as $appliance): ?>
                    <option value="<?php echo $appliance['id']; ?>">
                        <?php echo htmlspecialchars($appliance['name']); ?> (<?php echo $appliance['power_rating']; ?> W)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="usage_hours">Hours Used:</label>
            <input type="number" name="usage_hours" step="0.1" min="0" required>
            
            <button type="submit">Log Energy Usage</button>
        </form>
    </div>
    
    <div class="action-buttons">
        <a href="<?php echo BASE_URL; ?>/views/dashboard.php" class="btn">Back to Dashboard</a>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const logOptions = document.querySelectorAll('.log-option');
        const logSections = document.querySelectorAll('.log-section');
        const energyForm = document.getElementById('energy-form');
        
        logOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Update active button
                logOptions.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding section
                const targetId = this.getAttribute('data-target');
                logSections.forEach(section => {
                    section.classList.remove('active');
                    if (section.id === targetId) {
                        section.classList.add('active');
                    }
                });
                
                // Update form log_type
                energyForm.querySelector('input[name="log_type"]').value = 
                    targetId === 'single-day' ? 'single' : 'multiple';
            });
        });
    });
    </script>
</main>

<?php require_once(__DIR__ . "/../includes/footer.php"); ?>
