<?php
require 'config.php';
require 'auth.php';
admin_required();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_notice'])) {
        $title = htmlspecialchars($_POST['title']);
        $content = htmlspecialchars($_POST['content']);
        $category = $_POST['category'];
        $priority = $_POST['priority'];
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : NULL;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $created_by = $_SESSION['admin_id'];
        
        $stmt = $conn->prepare("INSERT INTO notices (title, content, category, priority, expires_at, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $title, $content, $category, $priority, $expires_at, $is_active, $created_by);
        
        if ($stmt->execute()) {
            $success = "Notice added successfully!";
        } else {
            $error = "Failed to add notice: " . $conn->error;
        }
    }
    
    if (isset($_POST['update_notice'])) {
        $id = intval($_POST['id']);
        $title = htmlspecialchars($_POST['title']);
        $content = htmlspecialchars($_POST['content']);
        $category = $_POST['category'];
        $priority = $_POST['priority'];
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : NULL;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE notices SET title=?, content=?, category=?, priority=?, expires_at=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssssssi", $title, $content, $category, $priority, $expires_at, $is_active, $id);
        
        if ($stmt->execute()) {
            $success = "Notice updated successfully!";
        } else {
            $error = "Failed to update notice: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_notice'])) {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM notices WHERE id=$id");
        $success = "Notice deleted successfully!";
    }
    
    if (isset($_POST['publish_results'])) {
        $conn->query("UPDATE voting_session SET results_published=TRUE WHERE id=1");
        $success = "Results published to noticeboard!";
    }
    
    if (isset($_POST['unpublish_results'])) {
        $conn->query("UPDATE voting_session SET results_published=FALSE WHERE id=1");
        $success = "Results unpublished from noticeboard!";
    }
}

// Handle edit request
$edit_notice = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit_notice = $conn->query("SELECT * FROM notices WHERE id=$id")->fetch_assoc();
}

// Determine active tab
$active_tab = 'manage';
if (isset($_GET['tab'])) {
    $active_tab = $_GET['tab'];
} elseif ($edit_notice) {
    $active_tab = 'add';
}

// Get all notices
$notices = $conn->query("SELECT n.*, u.name as author_name FROM notices n LEFT JOIN users u ON n.created_by = u.id ORDER BY n.published_at DESC");

// Get voting session status
$session = $conn->query("SELECT * FROM voting_session WHERE id=1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Notices - Admin Panel</title>
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

        /* Tabs */
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .tab { padding: 10px 20px; border: none; background: none; cursor: pointer; font-weight: 600; color: #666; border-radius: 8px; transition: all 0.3s; }
        .tab:hover { background: #f0f2f5; color: var(--primary); }
        .tab.active { background: var(--primary); color: white; }
        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }

        /* Forms & Tables */
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        textarea { min-height: 120px; resize: vertical; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 14px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }

        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: 600; color: #555; text-transform: uppercase; font-size: 12px; }
        td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .category-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-general { background: #e3f2fd; color: #1976d2; }
        .badge-election { background: #e8f5e9; color: #388e3c; }
        .badge-result { background: #fff3e0; color: #f57c00; }
        .badge-announcement { background: #f3e5f5; color: #7b1fa2; }
        .badge-urgent { background: #ffebee; color: #d32f2f; }
        
        .priority-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .priority-low { background: #c6f6d5; color: #22543d; }
        .priority-medium { background: #bee3f8; color: #2a4365; }
        .priority-high { background: #fed7d7; color: #742a2a; }
        .priority-urgent { background: #fed7d7; color: #742a2a; border: 1px solid #fc8181; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
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
            <li><a href="voters.php"><i class="fas fa-users"></i> Voters</a></li>
            <li><a href="candidates.php"><i class="fas fa-user-tie"></i> Candidates</a></li>
            <li><a href="admin_result.php"><i class="fas fa-chart-bar"></i> Results</a></li>
            <li><a href="admin_notices.php" class="active"><i class="fas fa-bullhorn"></i> Notices</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="welcome-text">
                <h2>Manage Notices & Results</h2>
                <p>Create announcements and control result publication.</p>
            </div>
            <a href="AdminDashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-section">
            <div class="tabs">
                <button class="tab <?php echo $active_tab == 'manage' ? 'active' : ''; ?>" onclick="switchTab('manage')"><i class="fas fa-list"></i> Manage Notices</button>
                <button class="tab <?php echo $active_tab == 'add' ? 'active' : ''; ?>" onclick="switchTab('add')"><i class="fas fa-plus-circle"></i> <?php echo $edit_notice ? 'Edit Notice' : 'Add New Notice'; ?></button>
                <button class="tab <?php echo $active_tab == 'results' ? 'active' : ''; ?>" onclick="switchTab('results')"><i class="fas fa-toggle-on"></i> Results Control</button>
            </div>
            
            <!-- Manage Notices Tab -->
            <div id="manage-tab" class="tab-content <?php echo $active_tab == 'manage' ? 'active' : ''; ?>">
                <div class="table-responsive">
                    <table class="notice-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Published</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($notice = $notices->fetch_assoc()): 
                                $published_date = date('M d, Y', strtotime($notice['published_at']));
                                $category_class = 'badge-' . strtolower($notice['category']);
                                $priority_class = 'priority-' . strtolower($notice['priority']);
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($notice['title']); ?></strong></td>
                                <td><span class="category-badge <?php echo $category_class; ?>"><?php echo $notice['category']; ?></span></td>
                                <td><span class="priority-badge <?php echo $priority_class; ?>"><?php echo $notice['priority']; ?></span></td>
                                <td><?php echo $published_date; ?></td>
                                <td>
                                    <?php if($notice['is_active']): ?>
                                        <span style="color: #2ecc71; font-weight: 600;"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span style="color: #e74c3c; font-weight: 600;"><i class="fas fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="?edit=<?php echo $notice['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;"><i class="fas fa-edit"></i></a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                                            <button type="submit" name="delete_notice" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Are you sure you want to delete this notice?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Add/Edit Notice Tab -->
            <div id="add-tab" class="tab-content <?php echo $active_tab == 'add' ? 'active' : ''; ?>">
                <form method="POST" style="max-width: 800px;">
                    <?php if($edit_notice): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_notice['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Notice Title *</label>
                        <input type="text" id="title" name="title" value="<?php echo $edit_notice ? htmlspecialchars($edit_notice['title']) : ''; ?>" required placeholder="Enter notice title">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Notice Content *</label>
                        <textarea id="content" name="content" required placeholder="Enter detailed notice content..."><?php echo $edit_notice ? htmlspecialchars($edit_notice['content']) : ''; ?></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="General" <?php echo ($edit_notice && $edit_notice['category'] == 'General') ? 'selected' : ''; ?>>General</option>
                                <option value="Election" <?php echo ($edit_notice && $edit_notice['category'] == 'Election') ? 'selected' : ''; ?>>Election</option>
                                <option value="Result" <?php echo ($edit_notice && $edit_notice['category'] == 'Result') ? 'selected' : ''; ?>>Result</option>
                                <option value="Announcement" <?php echo ($edit_notice && $edit_notice['category'] == 'Announcement') ? 'selected' : ''; ?>>Announcement</option>
                                <option value="Urgent" <?php echo ($edit_notice && $edit_notice['category'] == 'Urgent') ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority">
                                <option value="Low" <?php echo ($edit_notice && $edit_notice['priority'] == 'Low') ? 'selected' : ''; ?>>Low</option>
                                <option value="Medium" <?php echo ($edit_notice && $edit_notice['priority'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="High" <?php echo ($edit_notice && $edit_notice['priority'] == 'High') ? 'selected' : ''; ?>>High</option>
                                <option value="Urgent" <?php echo ($edit_notice && $edit_notice['priority'] == 'Urgent') ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="expires_at">Expiry Date (Optional)</label>
                        <input type="datetime-local" id="expires_at" name="expires_at" value="<?php echo $edit_notice && $edit_notice['expires_at'] ? date('Y-m-d\TH:i', strtotime($edit_notice['expires_at'])) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1" style="width: auto;" <?php echo ($edit_notice && $edit_notice['is_active']) || !$edit_notice ? 'checked' : ''; ?>>
                            Active (Visible on noticeboard)
                        </label>
                    </div>
                    
                    <div class="form-group" style="margin-top: 30px;">
                        <?php if($edit_notice): ?>
                            <button type="submit" name="update_notice" class="btn btn-primary"><i class="fas fa-save"></i> Update Notice</button>
                            <a href="admin_notices.php" class="btn btn-secondary">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_notice" class="btn btn-primary"><i class="fas fa-plus"></i> Add Notice</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Results Control Tab -->
            <div id="results-tab" class="tab-content <?php echo $active_tab == 'results' ? 'active' : ''; ?>">
                <div style="background: #f8f9fa; padding: 25px; border-radius: 12px; border-left: 5px solid var(--primary); margin-bottom: 30px;">
                    <h3 style="margin-bottom: 10px; color: var(--dark);">Results Publication Status</h3>
                    <p style="margin-bottom: 20px;">
                        Current Status: 
                        <strong style="color: <?php echo $session['results_published'] ? '#2ecc71' : '#e74c3c'; ?>; font-size: 18px;">
                            <?php echo $session['results_published'] ? 'PUBLISHED' : 'NOT PUBLISHED'; ?>
                        </strong>
                    </p>
                    <p style="color: #666; margin-bottom: 20px;">When results are published, they will be visible to all users on the public noticeboard and result page.</p>
                    
                    <form method="POST">
                        <?php if($session['results_published']): ?>
                            <button type="submit" name="unpublish_results" class="btn btn-danger">
                                <i class="fas fa-eye-slash"></i> Unpublish Results
                            </button>
                        <?php else: ?>
                            <button type="submit" name="publish_results" class="btn btn-success">
                                <i class="fas fa-eye"></i> Publish Results
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
                
                <h3 style="margin-bottom: 15px; color: var(--dark);">Preview of Results</h3>
                <div class="table-responsive">
                    <?php
                    $preview_results = $conn->query("
                        SELECT c.position, c.name as candidate_name, c.party_name AS party, 
                            COUNT(v.id) as vote_count
                        FROM candidates c 
                        LEFT JOIN votes v ON c.id = v.candidate_id
                        WHERE v.id IS NOT NULL
                        GROUP BY c.position, c.id
                        ORDER BY c.position, vote_count DESC
                        LIMIT 5
                    ");
                    
                    if ($preview_results->num_rows > 0): ?>
                        <table class="notice-table">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Candidate</th>
                                    <th>Party</th>
                                    <th>Votes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $preview_results->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['position']); ?></td>
                                    <td><?php echo htmlspecialchars($row['candidate_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['party']); ?></td>
                                    <td><strong><?php echo $row['vote_count']; ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: #666; font-style: italic; padding: 20px; text-align: center; background: #f8f9fa; border-radius: 8px;">No voting data available yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Deactivate all tab buttons
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activate clicked tab button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>