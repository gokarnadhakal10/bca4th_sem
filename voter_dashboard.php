<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Dashboard</title>
    
     <style>
     body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #4CAF50, #2e8b57);
    margin: 0;
    padding: 0;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    text-align: center;
}

h1 {
    margin-bottom: 30px;
    font-size: 28px;
    text-shadow: 1px 1px 2px black;
}

.card {
    background: white;
    color: #333;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    display: inline-block;
    width: 250px;
    margin: 15px;
    padding: 20px;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: scale(1.05);
}

.card h2 {
    margin-bottom: 15px;
}

button {
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #45a049;
}
</style>
</head>
<body>
    <div class="container">
        <h1>Welcome to School Election Voting System</h1>

        <div class="card">
            <h2>School President</h2>
            <form action="candidates.php" method="GET">
                <input type="hidden" name="position" value="president">
                <button type="submit">View Candidates</button>
            </form>
        </div>

        <div class="card">
            <h2>School Vice President</h2>
            <form action="candidates.php" method="GET">
                <input type="hidden" name="position" value="vice_president">
                <button type="submit">View Candidates</button>
            </form>
        </div>

        <div class="card">
            <h2>Class CR</h2>
            <form action="candidates.php" method="GET">
                <input type="hidden" name="position" value="class_cr">
                <button type="submit">View Candidates</button>
            </form>
        </div>
    </div>
</body>
</html>


