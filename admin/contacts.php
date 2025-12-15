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
    if ($_POST['action'] === 'update_offer_status' && isset($_POST['offer_id']) && isset($_POST['status'])) {
        $offer_id = intval($_POST['offer_id']);
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE offers SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $offer_id]);
    } elseif ($_POST['action'] === 'delete_offer' && isset($_POST['offer_id'])) {
        $offer_id = intval($_POST['offer_id']);
        
        $stmt = $pdo->prepare("DELETE FROM offers WHERE id = :id");
        $stmt->execute(['id' => $offer_id]);
    }
    
    header('Location: /admin/contacts.php');
    exit;
}

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Fetch offers based on filter
if ($filter === 'all') {
    $stmt = $pdo->query("SELECT * FROM offers ORDER BY date DESC");
} else {
    $stmt = $pdo->prepare("SELECT * FROM offers WHERE status = :status ORDER BY date DESC");
    $stmt->execute(['status' => $filter]);
}
$offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count offers by status
$offer_stats = [
    'total' => 0,
    'new' => 0,
    'contacted' => 0,
    'closed' => 0
];

$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM offers GROUP BY status");
$stats_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($stats_result as $stat) {
    $offer_stats[$stat['status']] = $stat['count'];
    $offer_stats['total'] += $stat['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Contacts - ALT Carrier Admin</title>
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
            font-size: 32px;
            font-weight: 500;
        }
        
        .header h1 span:first-child {
            color: #bf1b18;
        }
        
        .header h1 span:last-child {
            opacity: 0.8;
            font-size: 20px;
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
        
        .logout-btn {
            background-color: rgba(255,255,255,0.2);
            color: #fff;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .page-title {
            font-family: 'Teko', sans-serif;
            font-size: 36px;
            color: #182b48;
            margin-bottom: 30px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #182b48 0%, #2c4a6e 100%);
            color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card.active {
            background: linear-gradient(135deg, #bf1b18 0%, #8a1412 100%);
        }
        
        .stat-card.new {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .stat-card.contacted {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        }
        
        .stat-card.closed {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        }
        
        .stat-card h3 {
            font-size: 16px;
            font-weight: 400;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-family: 'Teko', sans-serif;
            font-size: 48px;
            font-weight: 500;
        }
        
        .table-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            padding: 20px 30px;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table-header h2 {
            font-family: 'Teko', sans-serif;
            font-size: 28px;
            color: #182b48;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background-color: #182b48;
            color: #fff;
            text-align: left;
            padding: 15px 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        td {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-new {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-contacted {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-closed {
            background-color: #d6d8db;
            color: #383d41;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
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
        
        .btn-delete {
            background-color: #dc3545;
            color: #fff;
        }
        
        .status-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            font-family: 'Bai Jamjuree', sans-serif;
            cursor: pointer;
            background-color: #fff;
        }
        
        .info-text {
            color: #666;
            font-size: 13px;
        }
        
        .email-link {
            color: #bf1b18;
            text-decoration: none;
            font-weight: 500;
        }
        
        .email-link:hover {
            text-decoration: underline;
        }
        
        .no-contacts {
            padding: 60px 20px;
            text-align: center;
            color: #999;
        }
        
        .export-btn {
            background-color: #28a745;
            color: #fff;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }
        
        .export-btn:hover {
            background-color: #218838;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .nav-links {
                flex-direction: column;
                width: 100%;
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
            <a href="/admin">Reviews</a>
            <a href="/admin/contacts.php" class="active">Customer Contacts</a>
            <a href="/admin/logout" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h1 class="page-title">Customer Contacts (Haul With Us)</h1>
        
        <div class="stats">
            <a href="?filter=all" style="text-decoration: none;">
                <div class="stat-card <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    <h3>Total Contacts</h3>
                    <div class="number"><?php echo $offer_stats['total']; ?></div>
                </div>
            </a>
            <a href="?filter=new" style="text-decoration: none;">
                <div class="stat-card new <?php echo $filter === 'new' ? 'active' : ''; ?>">
                    <h3>New</h3>
                    <div class="number"><?php echo $offer_stats['new']; ?></div>
                </div>
            </a>
            <a href="?filter=contacted" style="text-decoration: none;">
                <div class="stat-card contacted <?php echo $filter === 'contacted' ? 'active' : ''; ?>">
                    <h3>Contacted</h3>
                    <div class="number"><?php echo $offer_stats['contacted']; ?></div>
                </div>
            </a>
            <a href="?filter=closed" style="text-decoration: none;">
                <div class="stat-card closed <?php echo $filter === 'closed' ? 'active' : ''; ?>">
                    <h3>Closed</h3>
                    <div class="number"><?php echo $offer_stats['closed']; ?></div>
                </div>
            </a>
        </div>
        
        <div class="table-container">
            <div class="table-header">
                <h2>
                    <?php 
                    if ($filter === 'all') {
                        echo 'All Contacts';
                    } else {
                        echo ucfirst($filter) . ' Contacts';
                    }
                    ?>
                </h2>
            </div>
            
            <?php if (empty($offers)): ?>
                <div class="no-contacts">
                    <p>No customer contacts found<?php echo $filter !== 'all' ? ' with this status' : ''; ?>.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Person Name</th>
                            <th>Company Name</th>
                            <th>Email</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($offers as $offer): ?>
                            <tr>
                                <td>
                                    <div class="info-text">
                                        <?php echo date('M d, Y', strtotime($offer['date'])); ?><br>
                                        <?php echo date('H:i', strtotime($offer['date'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($offer['person_name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($offer['company_name']); ?>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($offer['email']); ?>" 
                                       class="email-link">
                                        <?php echo htmlspecialchars($offer['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="info-text">
                                        <?php echo htmlspecialchars($offer['ip']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $status = $offer['status'] ?? 'new';
                                    $statusClass = 'status-' . $status;
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_offer_status">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                            <select name="status" class="status-select" onchange="this.form.submit()">
                                                <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>New</option>
                                                <option value="contacted" <?php echo $status === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                                <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                            </select>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this contact?');">
                                            <input type="hidden" name="action" value="delete_offer">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
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
