<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'wordgene';

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die('Database connection failed');
}

// Get the short code from the URL
$shortCode = ltrim($_SERVER['REQUEST_URI'], '/');

// Find the long URL
$stmt = $conn->prepare("SELECT long_url FROM urls WHERE short_code = ?");
$stmt->bind_param("s", $shortCode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Update click count
    $updateStmt = $conn->prepare("UPDATE urls SET clicks = clicks + 1 WHERE short_code = ?");
    $updateStmt->bind_param("s", $shortCode);
    $updateStmt->execute();
    
    // Redirect to the long URL
    header("Location: " . $row['long_url']);
    exit;
} else {
    // If short code not found, redirect to home page
    header("Location: index.html");
    exit;
}

$conn->close();
?> 