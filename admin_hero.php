
<html>
<head>
    <title>hero image</title>
</head>
<body>
    <?php
<h2>Update Hero Image</h2>
<form action="update_hero.php" method="POST" enctype="multipart/form-data">
    <label>Select New Hero Image:</label>
    <input type="file" name="hero_image" required>
    <button type="submit">Update</button>
</form>
?>
</body>
</html>