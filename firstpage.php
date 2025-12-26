<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "voting_system");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch candidates/hero images
$result = $conn->query("SELECT * FROM candidates ORDER BY id DESC");

// Fetch Hero Image
$hero_img = "uploads/candidates.png"; // Default fallback
$h_query = $conn->query("SELECT image FROM hero_image ORDER BY id DESC LIMIT 1");
if($h_query && $h_query->num_rows > 0){
    $h_row = $h_query->fetch_assoc();
    if(!empty($h_row['image']) && file_exists("uploads/".$h_row['image'])){
        $hero_img = "uploads/".$h_row['image'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting System - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        header {
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .logo-text {
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        nav {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        nav a {
            padding: 10px 20px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        nav a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
        }

        nav a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .hero {
            margin-top: 80px;
            padding: 60px 40px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.95) 0%, rgba(118, 75, 162, 0.95) 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 40px;
            min-height: 500px;
        }

        .hero-content {
            flex: 1;
            max-width: 600px;
            color: white;
        }

        .hero-content h1 {
            font-size: 48px;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .hero-content p {
            font-size: 18px;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-primary {
            background: white;
            color: #667eea;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: white;
            color: #667eea;
        }

        .hero-image {
            flex: 1;
            max-width: 500px;
        }

        .hero-image img {
            width: 100%;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .candidates-section {
            padding: 60px 40px;
            background: #f8f9fa;
        }

        .section-title {
            text-align: center;
            font-size: 36px;
            margin-bottom: 50px;
            color: #333;
        }

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .candidate-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .candidate-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.3);
        }

        .candidate-card img {
            width: 100%;
            height: 280px;
            object-fit: cover;
        }

        .candidate-info {
            padding: 24px;
        }

        .candidate-info h3 {
            font-size: 24px;
            margin-bottom: 12px;
            color: #333;
        }

        .candidate-info p {
            color: #666;
            margin: 8px 0;
            font-size: 15px;
        }

        .position-tag {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            margin: 12px 0;
            font-weight: 600;
            font-size: 14px;
        }

        .vote-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .vote-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .no-candidates {
            text-align: center;
            padding: 60px 40px;
            background: white;
            border-radius: 12px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        .no-candidates i {
            font-size: 64px;
            color: #667eea;
            margin-bottom: 20px;
        }

        .no-candidates h3 {
            font-size: 28px;
            color: #333;
            margin-bottom: 12px;
        }

        .no-candidates p {
            color: #666;
            font-size: 16px;
        }

        footer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        .footer-links a:hover {
            opacity: 0.8;
            text-decoration: underline;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            transition: 0.3s;
        }

        .social-links a:hover {
            background: white;
            color: #667eea;
        }

        @media (max-width: 768px) {
            .header-container {
                padding: 15px 20px;
            }

            .hero {
                flex-direction: column;
                padding: 40px 20px;
                text-align: center;
            }

            .hero-content h1 {
                font-size: 32px;
            }

            .hero-content p {
                font-size: 16px;
            }

            .hero-buttons {
                justify-content: center;
            }

            .candidates-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .footer-links {
                flex-direction: column;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                font-size: 18px;
            }

            .hero-content h1 {
                font-size: 26px;
            }

            .btn {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-vote-yea"></i>
                </div>
                <span class="logo-text">Voting System</span>
            </div>
            
            <nav id="mainNav">
                 <a href="firstpage.php">Home</a>
                 <a href="login.html">Login</a>
                 <a href="studentRegistration.html">Register</a>
                 <a href="about.html">About Us</a>
                 <a href="noticeboard.php">Notice Board</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Choose Your Leader, Shape Your Future</h1>
            <p>
                Our secure online voting system empowers every college student to express their voice with confidence. 
                By voting digitally, you participate in choosing responsible leaders, strengthening transparency, 
                and promoting true campus democracy. Your vote shapes the future — choose wisely, participate actively, 
                and help build a stronger college community.
            </p>
            <div class="hero-buttons">
                <a href="login.html" class="btn btn-primary">Vote Now</a>
                <a href="studentRegistration.html" class="btn btn-secondary">Register</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="<?php echo htmlspecialchars($hero_img); ?>" alt="Voting System" onerror="this.src='https://via.placeholder.com/500x400?text=Online+Voting+System'">
        </div>
    </section>

    <!-- Candidates Section -->
    <section class="candidates-section">
        <h2 class="section-title">Candidates</h2>
        <div class="candidates-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="candidate-card">
                        <img src="uploads/<?php echo htmlspecialchars($row['photo']); ?>" 
                             alt="<?php echo htmlspecialchars($row['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/300x280?text=No+Image'">
                        <div class="candidate-info">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p><strong><i class="fas fa-graduation-cap"></i> Class:</strong> <?php echo htmlspecialchars($row['class']); ?></p>
                            <div class="position-tag">
                                <i class="fas fa-award"></i> <?php echo htmlspecialchars($row['position']); ?>
                            </div>
                            <button class="vote-btn" onclick="window.location.href='login.html'">
                                <i class="fas fa-vote-yea"></i> Vote Now
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-candidates">
                    <i class="fas fa-users-slash"></i>
                    <h3>No Candidates Available</h3>
                    <p>The candidate list will be updated soon. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <h3>Online Voting System</h3>
            <div class="footer-links">
                <a href="firstpage.php">Home</a>
                <a href="login.html">Login</a>
                <a href="studentRegistration.html">Register</a>
                <a href="help.html">Help</a>
            </div>
            <p style="margin-top: 20px; opacity: 0.9;">
                &copy; <?php echo date('Y'); ?>Voting System. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>