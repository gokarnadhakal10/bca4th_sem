<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Online Voting System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1c1c1c;
            color: white;
            margin: 0;
        }
        header {
            background: #007bff;
            padding: 20px;
            text-align: center;
        }
        header h1 {
            margin: 0;
            font-size: 26px;
        }
        nav {
            display: flex;
            justify-content: center;
            gap: 20px;
            background: #0056b3;
            padding: 15px 0;
        }
        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 5px;
            transition: 0.3s;
        }
        nav a:hover {
            background: #003f7f;
        }
        .container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            padding: 30px;
        }
        .card {
            background: #fff;
            color: #000;
            width: 250px;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .card h3 {
            margin-bottom: 15px;
        }
        .card button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }
        .card button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<header>
    <h1>Welcome, <?php echo $_SESSION['name']; ?>!</h1>
</header>

<nav>
    <!-- <a href="admin_dashboard.php">Dashboard</a> -->
    <!-- <a href="manage_candidates.php">Manage Candidates</a> -->
    <!-- <a href="manage_voters.php">Manage Voters</a> -->
    <a href="results.php">View Results</a>
    <a href="logout.php">Logout</a>
</nav>

<div class="container">
    <div class="card">
        <h3>Manage President Candidates</h3>
        <form action="manage_candidates.php?position=president" method="get">
            <button>Manage</button>
        </form>
    </div>
    
    <div class="card">
        <h3>Manage Vice President Candidates</h3>
        <form action="manage_candidates.php?position=vice" method="get">
            <button>Manage</button>
        </form>
    </div>
    
    <div class="card">
        <h3>Manage Class Representatives</h3>
        <form action="manage_candidates.php?position=cr" method="get">
            <button>Manage</button>
        </form>
    </div>

    <div class="card">
        <h3>View All Voters</h3>
        <form action="manage_voters.php">
            <button>View</button>
        </form>
    </div>

    <div class="card">
        <h3>View Election Results</h3>
        <form action="results.php">
            <button>View</button>
        </form>
    </div>
</div>

</body>
</html>
