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

// Process form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package = $_POST['package'] ?? '';
    $count = intval($_POST['count'] ?? 0);
    $prefix = $_POST['prefix'] ?? 'ELNET';
    $profile = $_POST['profile'] ?? '';
    
    // Validation
    if (empty($package) || empty($count) || empty($profile)) {
        $error = 'Semua field harus diisi';
    } elseif ($count < 1 || $count > 100) {
        $error = 'Jumlah voucher harus antara 1-100';
    } else {
        // Generate vouchers
        $generated_vouchers = [];
        $success_count = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $voucher_code = generateVoucherCode($prefix);
            
            // Calculate expiry date
            $expires_at = getExpiryDate($package);
            
            // Insert to database
            $stmt = $conn->prepare("INSERT INTO vouchers (code, package, price, expires_at, created_by) VALUES (?, ?, ?, ?, ?)");
            $price = getPrice($package);
            $stmt->bind_param("ssdss", $voucher_code, $package, $price, $expires_at, $admin['id']);
            
            if ($stmt->execute()) {
                $generated_vouchers[] = $voucher_code;
                $success_count++;
                
                // Log activity
                logActivity("Generated voucher {$voucher_code} ({$package})");
            }
        }
        
        $message = "Berhasil generate {$success_count} voucher {$package}";
        
        // Send to MikroTik (simulation)
        if ($success_count > 0) {
            sendToMikrotik($generated_vouchers, $profile);
        }
    }
}

function generateVoucherCode($prefix) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = $prefix . '-';
    for ($i = 0; $i < 8; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

function getExpiryDate($package) {
    $now = new DateTime();
    switch ($package) {
        case '6 Jam':
            $now->add(new DateInterval('PT6H'));
            break;
        case '1 Hari':
            $now->add(new DateInterval('P1D'));
            break;
        case '7 Hari':
            $now->add(new DateInterval('P7D'));
            break;
        case '30 Hari':
            $