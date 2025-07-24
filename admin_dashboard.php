<?php
session_start();
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Include database connection
include 'db.php';

// Handle provider verification
if (isset($_POST['verify_provider'])) {
    $provider_id = $_POST['provider_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    
    if ($action === 'approve') {
        $conn->query("UPDATE providers SET is_verified = 1 WHERE user_id = $provider_id");
        $_SESSION['message'] = "Provider verified successfully";
    } else {
        $reason = $conn->real_escape_string($_POST['rejection_reason']);
        $conn->query("UPDATE providers SET verification_notes = '$reason' WHERE user_id = $provider_id");
        $_SESSION['message'] = "Provider verification rejected with reason";
    }
    
    header("Location: admin_dashboard.php");
    exit;
}

// Get stats for dashboard
$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$providers_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'provider'")->fetch_assoc()['count'];
$services_count = $conn->query("SELECT COUNT(*) as count FROM services")->fetch_assoc()['count'];
$bookings_count = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$pending_verification = $conn->query("SELECT COUNT(*) as count FROM providers WHERE is_verified = 0 AND id_proof IS NOT NULL")->fetch_assoc()['count'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | UrbanServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #f76d2b;
            --primary-dark: #e05b1a;
            --secondary: #2d3748;
            --accent: #f0f4f8;
            --text: #2d3748;
            --light-text: #718096;
            --border: #e2e8f0;
            --white: #ffffff;
            --black: #000000;
            --success: #38a169;
            --error: #e53e3e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: var(--text);
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--secondary);
            color: var(--white);
            padding: 20px 0;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 10px;
            display: block;
            border: 3px solid var(--primary);
        }

        .sidebar-header h3 {
            color: var(--white);
            margin-bottom: 5px;
        }

        .sidebar-header p {
            color: var(--light-text);
            font-size: 14px;
        }

        .nav-menu {
            padding: 20px 0;
        }

        .nav-item {
            padding: 12px 20px;
            color: #cbd5e0;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }

        .nav-item:hover, .nav-item.active {
            background-color: rgba(255,255,255,0.1);
            color: var(--white);
        }

        .nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .header h1 {
            font-size: 24px;
            color: var(--secondary);
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: rgba(247, 109, 43, 0.1);
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card h3 {
            font-size: 14px;
            color: var(--light-text);
            margin-bottom: 10px;
        }

        .card p {
            font-size: 24px;
            font-weight: 700;
            color: var(--secondary);
        }

        /* Recent Activity Section */
        .recent-section {
            background-color: var(--white);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .section-header h2 {
            font-size: 20px;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            background-color: var(--accent);
            color: var(--secondary);
            font-weight: 600;
        }

        .data-table tr:hover {
            background-color: rgba(247, 109, 43, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background-color: rgba(56, 161, 105, 0.1);
            color: var(--success);
        }

        .status-pending {
            background-color: rgba(237, 137, 54, 0.1);
            color: #ed8936;
        }

        .status-unverified {
            background-color: rgba(229, 62, 62, 0.1);
            color: var(--error);
        }

        .action-link {
            color: var(--primary);
            text-decoration: none;
            margin-right: 10px;
        }

        .action-link:hover {
            text-decoration: underline;
        }

        .verify-btn {
            background-color: var(--success);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .verify-btn:hover {
            background-color: #2f855a;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
        }
 /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 50%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--secondary);
        }
        
        .close-modal {
            font-size: 24px;
            cursor: pointer;
            color: var(--light-text);
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .id-proof-preview {
            max-width: 100%;
            max-height: 300px;
            margin: 15px 0;
            border: 1px solid var(--border);
            border-radius: 4px;
        }
        
        .rejection-reason {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 4px;
            margin-top: 10px;
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--error);
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['name']) ?>&background=f76d2b&color=fff" 
                     alt="Profile" class="profile-img">
                <h3><?= htmlspecialchars($_SESSION['user']['name']) ?></h3>
                <p>Administrator</p>
            </div>
            
            <div class="nav-menu">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="#" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="providers.php" class="nav-item">
                    <i class="fas fa-user-tie"></i> Service Providers
                </a>
                <a href="services.php" class="nav-item">
                    <i class="fas fa-concierge-bell"></i> Services
                </a>
                <a href="bookings.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i> Bookings
                </a>
                <a href="transactions.php" class="nav-item">
                    <i class="fas fa-credit-card"></i> Transactions
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <div class="header">
                <h1>Admin Dashboard</h1>
                <div class="user-actions">
                    <span>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                    <a href="logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['message'] ?>
                    <?php unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard Stats -->
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Total Users</h3>
                    <p><?= $users_count ?></p>
                </div>
                <div class="card">
                    <h3>Service Providers</h3>
                    <p><?= $providers_count ?></p>
                </div>
                <div class="card">
                    <h3>Pending Verification</h3>
                    <p><?= $pending_verification ?></p>
                </div>
                <div class="card">
                    <h3>Services Offered</h3>
                    <p><?= $services_count ?></p>
                </div>
                <div class="card">
                    <h3>Total Bookings</h3>
                    <p><?= $bookings_count ?></p>
                </div>
            </div>

            <!-- Pending Verification Section -->
            <div class="recent-section">
                <div class="section-header">
                    <h2>Providers Pending Verification</h2>
                    <a href="providers.php" class="btn btn-primary">
                        <i class="fas fa-eye"></i> View All Providers
                    </a>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Specialization</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                      $pending_providers = $conn->query("
    SELECT u.id, u.name, u.email, u.phone, p.is_verified, p.experience as specialization, p.id_proof 
    FROM users u
    JOIN providers p ON u.id = p.user_id
    WHERE p.is_verified = 0 AND p.id_proof IS NOT NULL
    ORDER BY u.created_at DESC LIMIT 5
");

                        while ($provider = $pending_providers->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($provider['name']) ?></td>
                            <td><?= htmlspecialchars($provider['email']) ?></td>
                            <td><?= htmlspecialchars($provider['phone'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($provider['specialization'] ?? 'General') ?></td>
                            <td>
                                <span class="status-badge status-unverified">Unverified</span>
                            </td>
                         <td>
    <?php if (!empty($provider['id_proof'])): ?>
        <a href="uploads/id_proofs/<?= htmlspecialchars($provider['id_proof']) ?>" target="_blank" class="action-link">
            <i class="fas fa-id-card"></i> View ID
        </a>
    <?php endif; ?>
    
    <form method="POST" style="display: inline;">
        <input type="hidden" name="provider_id" value="<?= $provider['id'] ?>">
        <button type="submit" name="verify_provider" class="verify-btn" onclick="return confirm('Are you sure you want to verify this provider?');">
            <i class="fas fa-check"></i> Verify
        </button>
    </form>
    
    <a href="view_provider.php?id=<?= $provider['id'] ?>" class="action-link">
        <i class="fas fa-eye"></i> Profile
    </a>
</td>

                        </tr>
                        <?php endwhile; ?>
                        <?php if ($pending_providers->num_rows === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No providers pending verification</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Users Section -->
            <div class="recent-section">
                <div class="section-header">
                    <h2>Recent Users</h2>
                    <a href="users.php" class="btn btn-primary">
                        <i class="fas fa-eye"></i> View All
                    </a>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_users = $conn->query("
                            SELECT u.*, p.is_verified 
                            FROM users u
                            LEFT JOIN providers p ON u.id = p.user_id
                            ORDER BY u.created_at DESC LIMIT 5
                        ");
                        
                        while ($user = $recent_users->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                            <td>
                                <?php if ($user['role'] === 'provider'): ?>
                                    <span class="status-badge <?= $user['is_verified'] ? 'status-active' : 'status-unverified' ?>">
                                        <?= $user['is_verified'] ? 'Verified' : 'Unverified' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-active">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="action-link">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_user.php?id=<?= $user['id'] ?>" class="action-link" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Bookings Section -->
            <div class="recent-section">
                <div class="section-header">
                    <h2>Recent Bookings</h2>
                    <a href="bookings.php" class="btn btn-primary">
                        <i class="fas fa-eye"></i> View All
                    </a>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Service</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_bookings = $conn->query("
                            SELECT b.*, s.name as service_name, u.name as customer_name 
                            FROM bookings b
                            JOIN services s ON b.service_id = s.id
                            JOIN users u ON b.user_id = u.id
                            ORDER BY b.booking_date DESC LIMIT 5
                        ");
                        
                        while ($booking = $recent_bookings->fetch_assoc()):
                        ?>
                        <tr>
                            <td>#<?= $booking['id'] ?></td>
                            <td><?= htmlspecialchars($booking['service_name']) ?></td>
                            <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($booking['booking_date'])) ?></td>
                            <td>
                                <span class="status-badge <?= $booking['status'] === 'completed' ? 'status-active' : 'status-pending' ?>">
                                    <?= ucfirst($booking['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_booking.php?id=<?= $booking['id'] ?>" class="action-link">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="edit_booking.php?id=<?= $booking['id'] ?>" class="action-link">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>