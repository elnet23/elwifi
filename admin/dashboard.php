<?php
session_start();
include_once '../api/config.php';

// Cek login
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

// Get admin data
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Get statistics
$total_vouchers = 0;
$active_vouchers = 0;
$used_vouchers = 0;

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM vouchers");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$total_vouchers = $result['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM vouchers WHERE status = 'active'");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$active_vouchers = $result['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM vouchers WHERE status = 'used'");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$used_vouchers = $result['total'];

// Get recent activities
$activities = [];
$stmt = $conn->prepare("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ELNET WiFi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
        }
        
        .sidebar-header {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid #34495e;
        }
        
        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 12px;
            opacity: 0.7;
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-item {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            color: white;
        }
        
        .nav-item:hover {
            background: #34495e;
        }
        
        .nav-item.active {
            background: #3498db;
        }
        
        .nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .logout-btn {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            padding: 12px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 24px;
        }
        
        .header .admin-info {
            text-align: right;
        }
        
        .header .admin-info p {
            color: #666;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 36px;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        
        .stat-card.total i { color: #3498db; }
        .stat-card.total h3 { color: #3498db; }
        
        .stat-card.active i { color: #27ae60; }
        .stat-card.active h3 { color: #27ae60; }
        
        .stat-card.used i { color: #f39c12; }
        .stat-card.used h3 { color: #f39c12; }
        
        .content-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-header h2 {
            color: #2c3e50;
            font-size: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item p {
            margin: 0;
            color: #333;
        }
        
        .activity-item small {
            color: #666;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                order: 2;
            }
            
            .main-content {
                order: 1;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-wifi" style="font-size: 48px;"></i>
                <h2>ELNET WiFi</h2>
                <p>Admin Dashboard</p>
            </div>
            
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="generate_voucher.php" class="nav-item">
                    <i class="fas fa-plus-circle"></i>
                    Generate Voucher
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-list"></i>
                    Daftar Voucher
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    Laporan
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    Pengaturan
                </a>
            </div>
            
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <div class="main-content">
            <div class="header">
                <div>
                    <h1>Dashboard</h1>
                    <p>Selamat datang, <?php echo htmlspecialchars($admin['username']); ?>!</p>
                </div>
                <div class="admin-info">
                    <p>Last login: <?php echo date('d M Y H:i', strtotime($admin['last_login'])); ?></p>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card total">
                    <i class="fas fa-ticket-alt"></i>
                    <h3><?php echo $total_vouchers; ?></h3>
                    <p>Total Voucher</p>
                </div>
                
                <div class="stat-card active">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $active_vouchers; ?></h3>
                    <p>Voucher Aktif</p>
                </div>
                
                <div class="stat-card used">
                    <i class="fas fa-history"></i>
                    <h3><?php echo $used_vouchers; ?></h3>
                    <p>Voucher Terpakai</p>
                </div>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2>Generate Voucher</h2>
                    <a href="generate_voucher.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Generate Voucher Baru
                    </a>
                </div>
                
                <p>Generate voucher WiFi untuk pelanggan dengan berbagai paket yang tersedia. Pilih paket, jumlah voucher, dan sistem akan otomatis membuat kode voucher unik.</p>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2>Aktivitas Terkini</h2>
                </div>
                
                <div class="activity-list">
                    <?php if (empty($activities)): ?>
                        <p style="text-align: center; color: #666; padding: 20px;">Belum ada aktivitas</p>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-item">
                                <p><?php echo htmlspecialchars($activity['activity']); ?></p>
                                <small><?php echo date('d M Y H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto refresh statistics every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>