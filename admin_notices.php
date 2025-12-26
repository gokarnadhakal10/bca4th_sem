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
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .tab {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        button {
            padding: 12px 25px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #5a67d8;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-danger {
            background: #e53e3e;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        .btn-success {
            background: #38a169;
        }
        
        .btn-success:hover {
            background: #2f855a;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }
        
        .notice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .notice-table th, .notice-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .notice-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .notice-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-active {
            color: #38a169;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #e53e3e;
            font-weight: 600;
        }
        
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
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .results-control {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-bullhorn"></i> Manage Notices & Results</h1>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('manage')">Manage Notices</button>
            <button class="tab" onclick="switchTab('add')"><?php echo $edit_notice ? 'Edit Notice' : 'Add New Notice'; ?></button>
            <button class="tab" onclick="switchTab('results')">Results Control</button>
        </div>
        
        <!-- Manage Notices Tab -->
        <div id="manage-tab" class="tab-content active">
            <h2>All Notices</h2>
            <table class="notice-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Published Date</th>
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
                        <td><?php echo $notice['id']; ?></td>
                        <td><?php echo htmlspecialchars($notice['title']); ?></td>
                        <td><span class="category-badge <?php echo $category_class; ?>"><?php echo $notice['category']; ?></span></td>
                        <td><span class="priority-badge <?php echo $priority_class; ?>"><?php echo $notice['priority']; ?></span></td>
                        <td><?php echo $published_date; ?></td>
                        <td>
                            <?php if($notice['is_active']): ?>
                                <span class="status-active">Active</span>
                            <?php else: ?>
                                <span class="status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="?edit=<?php echo $notice['id']; ?>" class="btn btn-sm" style="background: #4299e1;">Edit</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                                    <button type="submit" name="delete_notice" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete this notice?')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Add/Edit Notice Tab -->
        <div id="add-tab" class="tab-content">
            <h2><?php echo $edit_notice ? 'Edit Notice' : 'Add New Notice'; ?></h2>
            <form method="POST">
                <?php if($edit_notice): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_notice['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">Notice Title *</label>
                    <input type="text" id="title" name="title" 
                           value="<?php echo $edit_notice ? htmlspecialchars($edit_notice['title']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="content">Notice Content *</label>
                    <textarea id="content" name="content" required><?php echo $edit_notice ? htmlspecialchars($edit_notice['content']) : ''; ?></textarea>
                </div>
                
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
                
                <div class="form-group">
                    <label for="expires_at">Expiry Date (Optional)</label>
                    <input type="datetime-local" id="expires_at" name="expires_at" 
                           value="<?php echo $edit_notice && $edit_notice['expires_at'] ? date('Y-m-d\TH:i', strtotime($edit_notice['expires_at'])) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" 
                               <?php echo ($edit_notice && $edit_notice['is_active']) || !$edit_notice ? 'checked' : ''; ?>>
                        Active (Visible on noticeboard)
                    </label>
                </div>
                
                <div class="form-group">
                    <?php if($edit_notice): ?>
                        <button type="submit" name="update_notice" class="btn">Update Notice</button>
                        <a href="admin_notices.php" class="btn btn-secondary">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_notice" class="btn">Add Notice</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Results Control Tab -->
        <div id="results-tab" class="tab-content">
            <div class="results-control">
                <h2>Results Publication Control</h2>
                <p>Current Status: 
                    <strong style="color: <?php echo $session['results_published'] ? '#38a169' : '#e53e3e'; ?>">
                        <?php echo $session['results_published'] ? 'PUBLISHED' : 'NOT PUBLISHED'; ?>
                    </strong>
                </p>
                <p>When results are published, they will be visible on the public noticeboard.</p>
                
                <form method="POST">
                    <?php if($session['results_published']): ?>
                        <button type="submit" name="unpublish_results" class="btn btn-danger">
                            Unpublish Results from Noticeboard
                        </button>
                    <?php else: ?>
                        <button type="submit" name="publish_results" class="btn btn-success">
                            Publish Results to Noticeboard
                        </button>
                    <?php endif; ?>
                </form>
            </div>
            
            <h2>Preview of Results</h2>
            <p>This is how results will appear on the noticeboard:</p>
            
            <?php
            $preview_results = $conn->query("
                SELECT c.position, c.name as candidate_name, c.party, 
                       COUNT(v.id) as vote_count
                FROM candidates c 
                LEFT JOIN votes v ON c.id = v.candidate_id
                WHERE v.id IS NOT NULL
                GROUP BY c.position, c.id
                ORDER BY c.position, vote_count DESC
                LIMIT 3
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
                            <td><?php echo $row['vote_count']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #666; font-style: italic;">No voting data available yet.</p>
            <?php endif; ?>
        </div>
        
        <a href="AdminDashboard.php" class="back-link">← Back to Admin Dashboard</a>
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
        
        // If editing a notice, switch to edit tab
        <?php if($edit_notice): ?>
            document.addEventListener('DOMContentLoaded', function() {
                switchTab('add');
            });
        <?php endif; ?>
    </script>
</body>
</html>