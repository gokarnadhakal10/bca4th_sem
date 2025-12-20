<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Hero Image</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #eef2f7;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    background: #fff;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    text-align: center;
    width: 350px;
}

h2 {
    margin-bottom: 25px;
    color: #333;
}

input[type="file"] {
    width: 100%;
    padding: 8px;
    margin-bottom: 20px;
    border: 1px solid #aaa;
    border-radius: 6px;
}

button {
    padding: 10px;
    width: 100%;
    background: #2196F3;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
}

button:hover {
    background: #0b66c0;
}

p {
    margin-top: 15px;
    font-weight: bold;
}
</style>
</head>
<body>

<div class="container">
    <h2>Update Hero Image</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="hero_image" required>
        <button type="submit">Upload</button>
        <a href="AdminDashboard.php">back</a>
    </form>
</div>

</body>
</html>
