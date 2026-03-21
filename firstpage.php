<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "voting_system");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch candidates/hero images
$candidates_by_position = [];
$query = $conn->query("SELECT * FROM candidates");
if ($query) {
    while ($row = $query->fetch_assoc()) {
        $pos = ucwords(strtolower(trim($row['position'])));
        $candidates_by_position[$pos][] = $row;
    }
}
ksort($candidates_by_position);

// Fetch Hero Image
$hero_img = "uploads/candidates.png"; // Default fallback
$h_query = $conn->query("SELECT image FROM hero_image ORDER BY id DESC LIMIT 1");
if($h_query && $h_query->num_rows > 0){
    $h_row = $h_query->fetch_assoc();
    if(!empty($h_row['image']) && file_exists("uploads/".$h_row['image'])){
        $hero_img = "uploads/".$h_row['image'];
    }
}

// Fetch Latest Notices (Limit 3)
$notices_query = $conn->query("SELECT * FROM notices WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY published_at DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting System - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="firstpage.css"> <!-- External CSS -->
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
                 <a href="result.php">Result</a>
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
              
            </div>
        </div>
        <div class="hero-image">
            <img src="<?php echo htmlspecialchars($hero_img); ?>" alt="Voting System" onerror="this.src='https://via.placeholder.com/500x400?text=Online+Voting+System'">
        </div>
    </section>

    <!-- Notices Section -->
    <?php if($notices_query && $notices_query->num_rows > 0): ?>
    <section class="notices-section" style="padding: 40px 20px; background: #fff; max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
            <h2 style="color: #333; font-size: 24px;">Latest Announcements</h2>
            <a href="noticeboard.php" style="color: #4361ee; text-decoration: none; font-weight: 600;">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="notices-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <?php while($notice = $notices_query->fetch_assoc()): ?>
            <div class="notice-preview" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #4361ee;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;"><?php echo date('M d, Y', strtotime($notice['published_at'])); ?> <span style="background: #e3f2fd; color: #1976d2; padding: 2px 6px; border-radius: 4px; margin-left: 5px;"><?php echo htmlspecialchars($notice['category']); ?></span></div>
                <h3 style="font-size: 18px; margin-bottom: 10px; color: #1a1a2e;"><?php echo htmlspecialchars($notice['title']); ?></h3>
                <p style="font-size: 14px; color: #555; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars(substr($notice['content'], 0, 100)) . '...'; ?></p>
            </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Candidates Section -->
    <section class="candidates-section">
        <h2 class="section-title">Candidates</h2>
      
        <div class="positions-container">
            <?php foreach ($candidates_by_position as $position => $list): ?>
            <div class="position-column">
                <h3 style="text-align:center; margin-bottom:25px; color: #333; font-size: 22px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">
                <?php echo htmlspecialchars($position); ?>
                </h3>

                <?php if (!empty($list)): ?>
                    <?php foreach ($list as $row): ?>
                        <div class="candidate-card">
                            <img src="uploads/<?php echo htmlspecialchars($row['photo']); ?>"
                                 alt="<?php echo htmlspecialchars($row['name']); ?>"
                                 onerror="this.src='https://via.placeholder.com/300x280?text=No+Image'">

                            <div class="candidate-info">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <p><strong>Faculty:</strong> <?php echo htmlspecialchars($row['faculty'] ?? 'N/A'); ?></p>
                                <p><strong>Class:</strong> <?php echo htmlspecialchars($row['class']); ?></p>
                                <?php if(!empty($row['party_name'])): ?>
                                <p><strong>Party:</strong> <?php echo htmlspecialchars($row['party_name']); ?></p>
                                <?php endif; ?>
                                <?php if(!empty($row['party_image'])): ?>
                                <div style="margin-top: 8px;">
                                    <img src="uploads/<?php echo htmlspecialchars($row['party_image']); ?>" 
                                         alt="Party Symbol" 
                                         style="height: 30px; object-fit: contain;"
                                         onerror="this.style.display='none'">
                                </div>
                                <?php endif; ?>

                                <button class="vote-btn" onclick="window.location.href='login.html'">
                                    <i class="fas fa-vote-yea"></i> Vote Now
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; color:#666; font-style: italic;">No candidates available.</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <h3>Online Voting System</h3>
            <div class="footer-links">
                <a href="firstpage.php">Home</a>
                <a href="login.html">Login</a>
                <a href="result.php">Results</a>
                <a href="help.html">Help</a>
            </div>
            <p style="margin-top: 20px; opacity: 0.9;">
                &copy; <?php echo date('Y'); ?> Voting System. All rights reserved.
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