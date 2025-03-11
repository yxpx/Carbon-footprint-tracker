<?php
require_once("config.php");

// Create tables
$tables = [
    // Users table
    "CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        household_size INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Appliances table
    "CREATE TABLE IF NOT EXISTS appliances (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        power_rating INT NOT NULL, -- in watts
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // User appliances table
    "CREATE TABLE IF NOT EXISTS user_appliances (
        id SERIAL PRIMARY KEY,
        user_id INT REFERENCES users(id),
        appliance_id INT REFERENCES appliances(id),
        usage_hours FLOAT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, appliance_id)
    )",
    
    // Energy usage table
    "CREATE TABLE IF NOT EXISTS energy_usage (
        id SERIAL PRIMARY KEY,
        user_id INT REFERENCES users(id),
        date DATE NOT NULL,
        kwh_consumed FLOAT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, date)
    )",
    
    // Transport modes table
    "CREATE TABLE IF NOT EXISTS transport_modes (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        carbon_emission FLOAT NOT NULL, -- kg CO2 per km
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // User transport table
    "CREATE TABLE IF NOT EXISTS user_transport (
        id SERIAL PRIMARY KEY,
        user_id INT REFERENCES users(id),
        transport_id INT REFERENCES transport_modes(id),
        distance_km FLOAT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, transport_id)
    )"
];

// Execute table creation queries
foreach ($tables as $query) {
    $result = pg_query($conn, $query);
    if (!$result) {
        echo "Error creating table: " . pg_last_error($conn) . "<br>";
    }
}

// Insert sample data for appliances
$appliances = [
    ["Refrigerator", 150],
    ["Air Conditioner", 1500],
    ["Washing Machine", 500],
    ["Television", 100],
    ["Computer", 300],
    ["Microwave", 1000],
    ["Electric Kettle", 1500],
    ["Dishwasher", 1200],
    ["Ceiling Fan", 75],
    ["Light Bulb (LED)", 10],
    ["Light Bulb (Incandescent)", 60],
    ["Water Heater", 4000],
    ["Hair Dryer", 1500],
    ["Vacuum Cleaner", 1400],
    ["Iron", 1000]
];

foreach ($appliances as $appliance) {
    $check_query = "SELECT id FROM appliances WHERE name = $1";
    $check_result = pg_query_params($conn, $check_query, [$appliance[0]]);
    
    if (pg_num_rows($check_result) == 0) {
        $insert_query = "INSERT INTO appliances (name, power_rating) VALUES ($1, $2)";
        $insert_result = pg_query_params($conn, $insert_query, [$appliance[0], $appliance[1]]);
        
        if (!$insert_result) {
            echo "Error inserting appliance: " . pg_last_error($conn) . "<br>";
        }
    }
}

// Insert sample data for transport modes
$transport_modes = [
    ["Car (Petrol)", 0.192],
    ["Car (Diesel)", 0.171],
    ["Car (Electric)", 0.053],
    ["Bus", 0.105],
    ["Train", 0.041],
    ["Bicycle", 0],
    ["Walking", 0],
    ["Motorcycle", 0.103],
    ["Plane", 0.255]
];

foreach ($transport_modes as $mode) {
    $check_query = "SELECT id FROM transport_modes WHERE name = $1";
    $check_result = pg_query_params($conn, $check_query, [$mode[0]]);
    
    if (pg_num_rows($check_result) == 0) {
        $insert_query = "INSERT INTO transport_modes (name, carbon_emission) VALUES ($1, $2)";
        $insert_result = pg_query_params($conn, $insert_query, [$mode[0], $mode[1]]);
        
        if (!$insert_result) {
            echo "Error inserting transport mode: " . pg_last_error($conn) . "<br>";
        }
    }
}

echo "Database setup completed successfully!";
?>
