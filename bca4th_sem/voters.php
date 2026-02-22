<?php
session_start();
require 'config.php';
require "auth.php";
admin_required();

$result = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Management - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --sidebar-width: 260px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body {
            background: #f0f2f5;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding: 20px;
            z-index: 100;
            transition: all 0.3s;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .nav-links { list-style: none; }
        .nav-links li { margin-bottom: 10px; }
        
        .nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(67, 97, 238, 0.2);
            color: white;
            transform: translateX(5px);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .welcome-text h2 { color: var(--dark); font-size: 24px; }
        .welcome-text p { color: #666; font-size: 14px; }

        /* Dashboard Sections */
        .dashboard-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .section-title { font-size: 18px; font-weight: 600; color: var(--dark); display: flex; align-items: center; gap: 10px; }

        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 13px;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-warning { background: #f1c40f; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-info { background: #3498db; color: white; }
        
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }

        /* Tables */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: 600; color: #555; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #555;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active { background: #e8f5e9; color: #2e7d32; }
        .status-blocked { background: #ffebee; color: #c62828; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo-icon"><i class="fas fa-vote-yea"></i></div>
        <h3>Admin Panel</h3>
    </div>
    <ul class="nav-links">
        <li><a href="AdminDashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
        <li><a href="voters.php" class="active"><i class="fas fa-users"></i> Users</a></li>
        <li><a href="candidates.php"><i class="fas fa-user-tie"></i> Candidates</a></li>
        <li><a href="admin_result.php"><i class="fas fa-chart-bar"></i> Results</a></li>
        <li><a href="admin_notices.php"><i class="fas fa-bullhorn"></i> Notices</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <div class="welcome-text">
            <h2>Voter Management</h2>
            <p>Manage registered voters, update details, and control access.</p>
        </div>
        <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="dashboard-section">
        <div class="section-header">
            <div class="section-title"><i class="fas fa-users"></i> Registered Voters List</div>
            <a href="studentRegistration.html" class="btn btn-primary"><i class="fas fa-plus"></i> Add Voter</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Info</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Faculty / Class</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { 
                        $status = isset($row['status']) ? $row['status'] : 'Active';
                        $role = $row['role'];
                    ?>
                    <tr>
                        <td>
                            #<?php echo $row['id']; ?>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <?php if (!empty($row['photo']) && file_exists($row['photo'])) { ?>
                                    <img src="<?php echo htmlspecialchars($row['photo']); ?>" class="user-avatar" alt="User">
                                <?php } else { ?>
                                    <div class="user-avatar"><?php echo strtoupper(substr($row['name'], 0, 1)); ?></div>
                                <?php } ?>
                                <span style="font-weight: 500;"><?php echo htmlspecialchars($row['name']); ?></span>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 13px;"><?php echo htmlspecialchars($row['email']); ?></div>
                            <div style="font-size: 12px; color: #888;"><?php echo htmlspecialchars($row['mobile']); ?></div>
                        </td>
                        <td>
                            <span style="background: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                <?php echo htmlspecialchars($role); ?>
                            </span>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($row['faculty']); ?></div>
                            <div style="font-size: 12px; color: #888;"><?php echo htmlspecialchars($row['class']); ?></div>
                        </td>
                        <td>
                            <span class="status-badge <?php echo strtolower($status) == 'active' ? 'status-active' : 'status-blocked'; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="edit_voter.php?id=<?php echo $row['id']; ?>" class="btn btn-info" style="padding: 5px 10px;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (strtolower($status) == 'active') { ?>
                                    <a href="block_voter.php?id=<?php echo $row['id']; ?>" class="btn btn-danger" style="padding: 5px 10px;" onclick="return confirm('Are you sure you want to block this user?')">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                <?php } else { ?>
                                    <a href="block_voter.php?id=<?php echo $row['id']; ?>" class="btn btn-success" style="padding: 5px 10px;" onclick="return confirm('Are you sure you want to unblock this user?')">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
