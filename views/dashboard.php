<?php
require_once(__DIR__ . "/../includes/header.php");
require_once(__DIR__ . "/../includes/navbar.php");
require_once(__DIR__ . "/../db/config.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/views/login.php");
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$result = pg_query_params($conn, "SELECT name, email, household_size FROM users WHERE id = $1", [$user_id]);
$user = pg_fetch_assoc($result);
$household_size = max(1, intval($user['household_size'] ?? 1)); // Ensure at least 1

// Determine time period (default to 30 days if not specified)
$period = $_GET['period'] ?? '30days';

// Set the date range based on the selected period
switch ($period) {
    case '7days':
        $dateInterval = "7 days";
        $chartLabel = "Last 7 Days";
        break;
    case '90days':
        $dateInterval = "90 days";
        $chartLabel = "Last 3 Months";
        break;
    case 'year':
        $dateInterval = "1 year";
        $chartLabel = "Last Year";
        break;
    default:
        $period = '30days'; // Set default
        $dateInterval = "30 days";
        $chartLabel = "Last 30 Days";
}

// Fetch energy usage summary
$energy_query = pg_query_params($conn, "SELECT SUM(kwh_consumed) AS total_energy FROM energy_usage WHERE user_id = $1", [$user_id]);
$energy_data = pg_fetch_assoc($energy_query);
$total_energy = $energy_data['total_energy'] ?? 0;

// Get Energy Usage Data for the selected period
$energyTrendQuery = pg_query_params($conn, 
    "SELECT date, SUM(kwh_consumed) as total 
     FROM energy_usage 
     WHERE user_id = $1 AND date >= CURRENT_DATE - INTERVAL '$dateInterval'
     GROUP BY date 
     ORDER BY date ASC", 
    [$user_id]
);

$energyDates = [];
$energyValues = [];
while ($row = pg_fetch_assoc($energyTrendQuery)) {
    // Format date as 'Mon DD' (e.g., Jan 15)
    $energyDates[] = date('M d', strtotime($row['date']));
    $energyValues[] = $row['total'];
}

// Fetch appliances used
$appliance_query = pg_query_params($conn, 
    "SELECT a.name, ua.usage_hours 
     FROM user_appliances ua 
     JOIN appliances a ON ua.appliance_id = a.id 
     WHERE ua.user_id = $1", [$user_id]
);
$appliances = pg_fetch_all($appliance_query) ?: [];

// Fetch carbon footprint from energy usage
$carbon_energy_query = pg_query_params($conn, 
    "SELECT SUM(kwh_consumed * 0.92) AS total_carbon 
     FROM energy_usage 
     WHERE user_id = $1", [$user_id] // 0.92 kg CO2 per kWh
);
$carbon_energy = pg_fetch_assoc($carbon_energy_query)['total_carbon'] ?? 0;

// Fetch carbon footprint from transport usage
$carbon_transport_query = pg_query_params($conn, 
    "SELECT SUM(tm.carbon_emission * ut.distance_km) AS transport_carbon 
     FROM user_transport ut 
     JOIN transport_modes tm ON ut.transport_id = tm.id 
     WHERE ut.user_id = $1", [$user_id]
);
$carbon_transport = pg_fetch_assoc($carbon_transport_query)['transport_carbon'] ?? 0;

$total_carbon = $carbon_energy + $carbon_transport;

// Average values for comparison
$avg_household_energy = 900; // kWh per month
$avg_carbon_footprint = 1000; // kg CO2 per month

// Per person calculations
$energy_per_person = $household_size > 0 ? $total_energy / $household_size : 0;
$carbon_per_person = $household_size > 0 ? $total_carbon / $household_size : 0;
$avg_energy_per_person = $avg_household_energy / 2.5; // Assuming average household size of 2.5
$avg_carbon_per_person = $avg_carbon_footprint / 2.5;
?>

<main class="dashboard">
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
        
        <div class="summary-cards">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bolt"></i>
                    <h3>Energy Usage</h3>
                </div>
                <div class="card-body">
                    <div class="card-value"><?php echo number_format($total_energy, 1); ?> kWh</div>
                    <div class="card-comparison">
                        <?php if ($energy_per_person > $avg_energy_per_person): ?>
                            <span class="text-danger">
                                <?php echo number_format(($energy_per_person / $avg_energy_per_person - 1) * 100, 1); ?>% above average
                            </span>
                        <?php else: ?>
                            <span class="text-success">
                                <?php echo number_format((1 - $energy_per_person / $avg_energy_per_person) * 100, 1); ?>% below average
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-cloud"></i>
                    <h3>Carbon Footprint</h3>
                </div>
                <div class="card-body">
                    <div class="card-value"><?php echo number_format($total_carbon, 1); ?> kg CO₂</div>
                    <div class="card-comparison">
                        <?php if ($carbon_per_person > $avg_carbon_per_person): ?>
                            <span class="text-danger">
                                <?php echo number_format(($carbon_per_person / $avg_carbon_per_person - 1) * 100, 1); ?>% above average
                            </span>
                        <?php else: ?>
                            <span class="text-success">
                                <?php echo number_format((1 - $carbon_per_person / $avg_carbon_per_person) * 100, 1); ?>% below average
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-home"></i>
                    <h3>Household Size</h3>
                </div>
                <div class="card-body">
                    <div class="card-value"><?php echo $household_size; ?> <?php echo $household_size == 1 ? 'person' : 'people'; ?></div>
                    <div class="card-comparison">
                        <span class="text-info">
                            <?php echo number_format($energy_per_person, 1); ?> kWh per person
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-charts">
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Energy Usage Trend</h3>
                    <div class="time-selector">
                        <a href="?period=7days" class="time-btn <?php echo $period == '7days' ? 'active' : ''; ?>">7d</a>
                        <a href="?period=30days" class="time-btn <?php echo $period == '30days' ? 'active' : ''; ?>">30d</a>
                        <a href="?period=90days" class="time-btn <?php echo $period == '90days' ? 'active' : ''; ?>">3m</a>
                        <a href="?period=year" class="time-btn <?php echo $period == 'year' ? 'active' : ''; ?>">1y</a>
                    </div>
                </div>
                <canvas id="energyChart"></canvas>
            </div>
            
            <div class="chart-container">
                <h3>Carbon Footprint Breakdown</h3>
                <canvas id="carbonChart"></canvas>
            </div>
        </div>
        
        <div class="dashboard-details">
            <div class="quick-recommendations">
                <h3>Quick Recommendations</h3>
                <ul class="recommendations-list">
                    <?php if ($total_energy > $avg_household_energy): ?>
                        <li>Consider using energy-efficient appliances to reduce your electricity consumption.</li>
                        <li>Turn off lights and unplug devices when not in use.</li>
                    <?php else: ?>
                        <li>Great job keeping your energy usage below average! Consider installing solar panels to further reduce your carbon footprint.</li>
                    <?php endif; ?>
                    
                    <?php if ($carbon_transport > ($avg_carbon_footprint * 0.3)): ?>
                        <li>Try using public transportation, carpooling, or cycling to reduce transport emissions.</li>
                    <?php else: ?>
                        <li>Your transport emissions are well-managed. Keep up the good work!</li>
                    <?php endif; ?>
                    
                    <li>Visit the <a href="<?php echo BASE_URL; ?>/views/report.php">recommendations page</a> for more personalized tips.</li>
                </ul>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="<?php echo BASE_URL; ?>/views/log_energy.php" class="btn btn-primary">Log Energy Usage</a>
            <a href="<?php echo BASE_URL; ?>/views/log_transport.php" class="btn btn-primary">Log Transport</a>
            <a href="<?php echo BASE_URL; ?>/views/report.php" class="btn btn-secondary">View Report</a>
        </div>
    </div>
</main>

<style>
/* Dashboard specific styles */
.summary-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    flex: 1;
    min-width: 250px;
    background-color: var(--bg-card);
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 20px;
}

.card-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.card-header i {
    font-size: 1.5rem;
    margin-right: 10px;
    color: var(--primary-color);
}

.card-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.card-value {
    font-size: 1.8rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.card-comparison {
    font-size: 0.9rem;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.time-selector {
    display: flex;
    background-color: var(--bg-card);
    border-radius: 4px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.time-btn {
    padding: 4px 8px;
    font-size: 0.8rem;
    color: var(--text-color);
    text-decoration: none;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.time-btn:last-child {
    border-right: none;
}

.time-btn:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.time-btn.active {
    background-color: var(--primary-color);
    color: white;
}
</style>

<script>
// Energy Usage Chart
document.addEventListener('DOMContentLoaded', function() {
    const energyCtx = document.getElementById('energyChart').getContext('2d');
    const energyChart = new Chart(energyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($energyDates); ?>,
            datasets: [{
                label: 'Energy Usage (kWh)',
                data: <?php echo json_encode($energyValues); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                tension: 0.3,
                pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: false
                },
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'kWh'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            }
        }
    });
    
    // Carbon Footprint Chart
    const carbonCtx = document.getElementById('carbonChart').getContext('2d');
    const carbonChart = new Chart(carbonCtx, {
        type: 'doughnut',
        data: {
            labels: ['Energy', 'Transport'],
            datasets: [{
                data: [<?php echo $carbon_energy; ?>, <?php echo $carbon_transport; ?>],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value.toFixed(1)} kg CO₂ (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once(__DIR__ . "/../includes/footer.php"); ?>