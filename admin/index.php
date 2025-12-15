<?php
// Fix session path for cPanel
$session_path = sys_get_temp_dir();
if (!is_writable($session_path)) {
    $session_path = dirname(__FILE__) . '/../sessions';
    if (!file_exists($session_path)) {
        mkdir($session_path, 0700, true);
    }
}
session_save_path($session_path);

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /admin/login');
    exit;
}

// Database configuration
$host = 'localhost';
$dbname = 'altca762_db';
$username = 'altca762_user';
$password = 'AxQ!s5+*(ojKc1J;';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status' && isset($_POST['review_id']) && isset($_POST['status'])) {
        $review_id = intval($_POST['review_id']);
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE reviews SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $review_id]);
    } elseif ($_POST['action'] === 'delete' && isset($_POST['review_id'])) {
        $review_id = intval($_POST['review_id']);
        
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
        $stmt->execute(['id' => $review_id]);
    }
    
    header('Location: /admin');
    exit;
}

// Fetch all reviews
$stmt = $pdo->query("SELECT * FROM reviews ORDER BY date DESC");
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count reviews by status
$stats = [
    'total' => count($reviews),
    'published' => 0,
    'pending' => 0
];

foreach ($reviews as $review) {
    if (isset($review['status'])) {
        if ($review['status'] === 'published') {
            $stats['published']++;
        } elseif ($review['status'] === 'pending') {
            $stats['pending']++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - ALT Carrier Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Teko:wght@400;500&family=Bai+Jamjuree:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Bai Jamjuree', sans-serif;
            background-color: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #182b48 0%, #bf1b18 100%);
            color: #fff;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-family: 'Teko', sans-serif;
            font-size: 36px;
        }
        
        .header h1 span {
            color: #fff;
            opacity: 0.9;
        }
        
        .logout-btn {
            background-color: rgba(255,255,255,0.2);
            color: #fff;
            padding: 10px 20px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: rgba(255,255,255,0.3);
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav-links a {
            color: #fff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .nav-links a.active {
            background-color: rgba(255,255,255,0.2);
            font-weight: 500;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #182b48;
        }
        
        .stat-card.published {
            border-left-color: #28a745;
        }
        
        .stat-card.pending {
            border-left-color: #ffc107;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            font-weight: 400;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            font-family: 'Teko', sans-serif;
            font-size: 48px;
            color: #182b48;
            line-height: 1;
        }
        
        .reviews-table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background-color: #182b48;
            color: #fff;
            padding: 20px 30px;
        }
        
        .table-header h2 {
            font-family: 'Teko', sans-serif;
            font-size: 28px;
            font-weight: 500;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background-color: #f8f9fa;
            color: #182b48;
            font-weight: 500;
            text-align: left;
            padding: 15px 20px;
            border-bottom: 2px solid #dee2e6;
            font-size: 14px;
        }
        
        td {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .stars {
            color: #ffc107;
            font-size: 18px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-published {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-family: 'Bai Jamjuree', sans-serif;
            transition: opacity 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .btn-publish {
            background-color: #28a745;
            color: #fff;
        }
        
        .btn-unpublish {
            background-color: #ffc107;
            color: #000;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: #fff;
        }
        
        .comment-text {
            max-width: 400px;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .info-text {
            color: #666;
            font-size: 13px;
        }
        
        .no-reviews {
            padding: 60px 20px;
            text-align: center;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            table {
                font-size: 13px;
            }
            
            th, td {
                padding: 10px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><span>ALT</span> Carrier <span>| Admin Panel</span></h1>
        <div class="nav-links">
            <a href="/admin" class="active">Reviews</a>
            <a href="/admin/contacts.php">Customer Contacts</a>
            <a href="/admin/logout" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat-card">
                <h3>Total Reviews</h3>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card published">
                <h3>Published</h3>
                <div class="number"><?php echo $stats['published']; ?></div>
            </div>
            <div class="stat-card pending">
                <h3>Pending</h3>
                <div class="number"><?php echo $stats['pending']; ?></div>
            </div>
        </div>
        
        <div class="reviews-table-container">
            <div class="table-header">
                <h2>Manage Reviews</h2>
            </div>
            
            <?php if (empty($reviews)): ?>
                <div class="no-reviews">
                    <p>No reviews found.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name / Company</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td>
                                    <div class="info-text">
                                        <?php echo date('M d, Y', strtotime($review['date'])); ?><br>
                                        <?php echo date('H:i', strtotime($review['date'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($review['name']); ?></strong><br>
                                    <?php if (!empty($review['company_name'])): ?>
                                        <span class="info-text"><?php echo htmlspecialchars($review['company_name']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="stars">
                                        <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="comment-text">
                                        <?php echo htmlspecialchars($review['comment']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="info-text">
                                        <?php echo htmlspecialchars($review['email']); ?><br>
                                        IP: <?php echo htmlspecialchars($review['ip']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $status = $review['status'] ?? 'pending';
                                    $statusClass = 'status-' . $status;
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <?php if ($status !== 'published'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                <input type="hidden" name="status" value="published">
                                                <button type="submit" class="btn btn-publish">Publish</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                <input type="hidden" name="status" value="pending">
                                                <button type="submit" class="btn btn-unpublish">Unpublish</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" class="btn btn-delete">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
