<?php
require "config.php";
require "auth.php";
admin_required();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Hero Image | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 100%; max-width: 500px; }
        h2 { text-align: center; color: #333; margin-bottom: 30px; font-size: 24px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
        input[type="file"] { width: 100%; padding: 10px; border: 2px solid #e1e1e1; border-radius: 8px; }
        button { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #666; text-decoration: none; }
        .back-link:hover { color: #667eea; }
    </style>
</head>
<body>

    <div class="container">
        <h2><i class="fas fa-image"></i> Update Hero Image</h2>
        <form action="update_hero.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Select New Image</label>
                <input type="file" name="hero_image" accept="image/*" required>
            </div>
            <button type="submit">Upload Image</button>
            <a href="AdminDashboard.php" class="back-link">Back to Dashboard</a>
        </form>
    </div>
</body>
</html>
