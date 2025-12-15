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
    
    // Get the number of reviews to fetch (default: 10)
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Prepare SQL statement to get recent published reviews only
    $sql = "SELECT name, company_name, comment, rating, date 
            FROM reviews 
            WHERE status = 'published'
            ORDER BY date DESC 
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the date for each review
    foreach ($reviews as &$review) {
        $review['date'] = date('M d, Y', strtotime($review['date']));
    }
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews
    ]);
    
} catch (PDOException $e) {
    // Database error
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'reviews' => []
    ]);
} catch (Exception $e) {
    // General error
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'reviews' => []
    ]);
}
?>
