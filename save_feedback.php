<?php
// Database configuration
$host = 'localhost';
$dbname = 'altca762_db';
$username = 'altca762_user';
$password = 'AxQ!s5+*(ojKc1J;';

// Set response header
header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get and sanitize input data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $company_name = isset($_POST['company_name']) ? trim($_POST['company_name']) : '';
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    
    // Get user's IP address
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    // Validate required fields
    if (empty($email) || empty($name) || empty($comment)) {
        throw new Exception('Please fill in all required fields');
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Validate rating (assuming 1-5 scale)
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Invalid rating value');
    }
    
    // Prepare SQL statement
    $sql = "INSERT INTO reviews (ip, email, name, company_name, comment, rating, date) 
            VALUES (:ip, :email, :name, :company_name, :comment, :rating, NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':ip', $ip);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':company_name', $company_name);
    $stmt->bindParam(':comment', $comment);
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
    
    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your feedback! Your feedback has been submitted successfully. We will review it.'
        ]);
    } else {
        throw new Exception('Failed to save feedback');
    }
    
} catch (PDOException $e) {
    // Database error
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // General error
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
