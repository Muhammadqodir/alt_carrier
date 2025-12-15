<?php
header('Content-Type: application/json');

// Database configuration
$host = 'localhost';
$dbname = 'altca762_db';
$username = 'altca762_user';
$password = 'AxQ!s5+*(ojKc1J;';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$company_name = isset($_POST['company_name']) ? trim($_POST['company_name']) : '';
$person_name = isset($_POST['person_name']) ? trim($_POST['person_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validate required fields
if (empty($company_name) || empty($person_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Get IP address
$ip = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

try {
    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO offers (company_name, person_name, email, ip, date) VALUES (:company_name, :person_name, :email, :ip, NOW())");
    
    $stmt->execute([
        'company_name' => $company_name,
        'person_name' => $person_name,
        'email' => $email,
        'ip' => $ip
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you! We will contact you soon.'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error saving your information. Please try again.'
    ]);
}
?>
