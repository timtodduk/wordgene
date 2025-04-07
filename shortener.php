<?php
header('Content-Type: application/json');

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'wordgene';

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Create URLs table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS urls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    long_url TEXT NOT NULL,
    short_code VARCHAR(10) NOT NULL UNIQUE,
    clicks INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createTable)) {
    die(json_encode(['success' => false, 'message' => 'Error creating table']));
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$longUrl = $data['url'] ?? '';

if (empty($longUrl)) {
    echo json_encode(['success' => false, 'message' => 'URL is required']);
    exit;
}

// Validate URL
if (!filter_var($longUrl, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid URL format']);
    exit;
}

// Generate short code
function generateShortCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Check if URL already exists
$stmt = $conn->prepare("SELECT short_code, clicks, created_at FROM urls WHERE long_url = ?");
$stmt->bind_param("s", $longUrl);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'shortUrl' => 'http://' . $_SERVER['HTTP_HOST'] . '/' . $row['short_code'],
        'clicks' => $row['clicks'],
        'created' => date('M d, Y', strtotime($row['created_at']))
    ]);
    exit;
}

// Generate new short code
$shortCode = generateShortCode();
$stmt = $conn->prepare("INSERT INTO urls (long_url, short_code) VALUES (?, ?)");
$stmt->bind_param("ss", $longUrl, $shortCode);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'shortUrl' => 'http://' . $_SERVER['HTTP_HOST'] . '/' . $shortCode,
        'clicks' => 0,
        'created' => date('M d, Y')
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error creating short URL']);
}

$conn->close();
?> 