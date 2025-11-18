<?php
session_start();

// Redirect if user is not logged in or role is not voter
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'voter') {
    header("Location: login.html");
    exit;
}

// Optional: get user info from session
$name = $_SESSION['name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard</title>
    <style>
        /* Body & font */
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #74ebd5, #acb6e5);
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            margin-top: 40px;
            margin-bottom: 30px;
            color: #333;
            text-align: center;
        }

        /* Cards container */
        .cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }

        /* Individual card */
        .card {
            background: white;
            color: black;
            padding: 20px;
            border-radius: 12px;
            width: 220px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .card h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .card button {
            padding: 10px 20px;
            background: #0f8f55;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }

        .card button:hover {
            background: #0d6a42;
        }

        /* Logout button */
        .home-btn {
            margin-top: 30px;
            padding: 10px 20px;
            background: #ff4d4d;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }

        .home-btn:hover {
            background: #cc0000;
        }

    </style>
</head>
<body>

    <h1>Welcome, <?php echo htmlspecialchars($name); ?>!</h1>

    <div class="cards">
        <div class="card">
            <h3>President</h3>
            <form action="voter_president_vote.php" method="post">
                <button type="submit">Vote</button>
            </form>
        </div>

        <div class="card">
            <h3>Vice President</h3>
            <form action="voter_vice_vote.php" method="post">
                <button type="submit">Vote</button>
            </form>
        </div>

        <div class="card">
            <h3>Class CR</h3>
            <form action="voter_cr_vote.php" method="post">
                <button type="submit">Vote</button>
            </form>
        </div>
    </div>

    <!-- Logout -->
    <form action="firstpage.html" method="post">
        <button class="home-btn" type="submit">home</button>
    </form>

</body>
</html>
