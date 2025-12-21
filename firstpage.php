

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Online Voting System</title>

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, Helvetica, sans-serif;
    }

   body {
   
    background-size: 300px 200px; /* smaller size */
    height: 100vh;
    display: flex;
    flex-direction: column;
    color: white;
}
gfd
    header {
      width: 100%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 40px;
      background: rgba(11, 92, 183, 0.9);
      color: white;
      position: fixed;
      top: 0;
      z-index: 1000;
    }

    .text {
      color: rgb(24, 189, 9);
      font-size: 24px;
      font-weight: bold;
      background-color: rgb(254, 254, 250);
      padding: 10px 20px;
      border-radius: 200px;
    }

    /* nav {
      display: flex;
      gap: 10px;
    }

    nav a {
      padding: 10px 30px;
      background: white;
      color: black;
      text-decoration: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
      text-align: center;
      min-width: 100px;
      transition: 0.3s;
    }

    nav a:hover {
      background: #4f0cc3;
      color: white;
    } */
     nav {
    width: 100%;
    background: rgba(11,92,183,0.9);
    padding: 15px 0;
    display: flex;
    justify-content: center;
    gap: 20px;
    position: fixed;
    top: 0;
    z-index: 1000;
}
nav a {
    color: white;
    text-decoration: none;
    font-weight: bold;
    padding: 10px 15px;
    border-radius: 5px;
}
nav a:hover {
    background: #4f0cc3;
    color: white;
}

    .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 120px 60px 60px 60px;
      flex: 1;
      background-color: rgba(154, 143, 143, 0.3);
    }

    .text-section {
      width: 55%;
      background-color: rgba(30, 46, 58, 0.6);
      padding: 40px;
      border-radius: 10px;
    }

    .text-section h1 {
      font-size: 40px;
      margin-bottom: 20px;
    }

    .text-section p {
      line-height: 1.7;
      font-size: 18px;
    }

    .image-section img {
      width: 430px;
      height: auto;
      border-radius: 220px;
      margin-right: 50px;
    }
  </style>
</head>

<body>
<?php
require "config.php";

// Get latest hero image
$hero_sql = "SELECT * FROM hero_image ORDER BY updated_at DESC LIMIT 1";
$hero_result = $conn->query($hero_sql);
$hero = $hero_result->fetch_assoc();
$hero_image = $hero ? 'uploads/'.$hero['image'] : 'voteImage.png';
?>

  <header>
    <div class="text">Online Voting System</div>

    <nav>
      <a href="firstpage.php">Home</a>
      <a href="login.html">Login</a>
      <a href="studentRegistration.html">Register</a>
       <a href="result.php">Result</a>
      <a href="about.html">About Us</a>
      <a href="help.html">Help</a>
    </nav>
  </header>

  <div class="container">
    <div class="text-section">
      <h1>Choose Your Leader</h1>
     <p>
  Our secure online voting system empowers every college student to express their voice with confidence. 
  By voting digitally, you participate in choosing responsible leaders, strengthening transparency, 
  and promoting true campus democracy. Your vote shapes the future — choose wisely, participate actively, 
  and help build a stronger college community.
</p>

    </div>

    <div class="image-section">
    <img src="<?php echo $hero_image; ?>" alt="Voting System">
</div>

  </div>

</body>
</html>





<?php include "hero.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Online Voting System</title>

<style>




    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, Helvetica, sans-serif;
    }

   body {
   
    background-size: 300px 200px; /* smaller size */
    height: 100vh;
    display: flex;
    flex-direction: column;
    color: white;
}
gfd
    header {
      width: 100%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 40px;
      background: rgba(11, 92, 183, 0.9);
      color: white;
      position: fixed;
      top: 0;
      z-index: 1000;
    }

    .text {
      color: rgb(24, 189, 9);
      font-size: 24px;
      font-weight: bold;
      background-color: rgb(254, 254, 250);
      padding: 10px 20px;
      border-radius: 200px;
    }

    /* nav {
      display: flex;
      gap: 10px;
    }

    nav a {
      padding: 10px 30px;
      background: white;
      color: black;
      text-decoration: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
      text-align: center;
      min-width: 100px;
      transition: 0.3s;
    }

    nav a:hover {
      background: #4f0cc3;
      color: white;
    } */
     nav {
    width: 100%;
    background: rgba(11,92,183,0.9);
    padding: 15px 0;
    display: flex;
    justify-content: center;
    gap: 20px;
    position: fixed;
    top: 0;
    z-index: 1000;
}
nav a {
    color: white;
    text-decoration: none;
    font-weight: bold;
    padding: 10px 15px;
    border-radius: 5px;
}
nav a:hover {
    background: #4f0cc3;
    color: white;
}

    .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 120px 60px 60px 60px;
      flex: 1;
      background-color: rgba(154, 143, 143, 0.3);
    }

    .text-section {
      width: 55%;
      background-color: rgba(30, 46, 58, 0.6);
      padding: 40px;
      border-radius: 10px;
    }

    .text-section h1 {
      font-size: 40px;
      margin-bottom: 20px;
    }

    .text-section p {
      line-height: 1.7;
      font-size: 18px;
    }

    .image-section img {
      width: 430px;
      height: auto;
      border-radius: 220px;
      margin-right: 50px;
    }
 


</style>
</head>

<body>

<?php include "header.html"; ?>

<div class="container">
  <div class="text-section">
    <h1>Choose Your Leader</h1>
    <p>
      Our secure online voting system empowers every college student to express their voice with confidence.
    </p>
  </div>

  <div class="image-section">
    <img src="<?php echo $hero_image; ?>" alt="Voting System">
  </div>
</div>

</body>
</html>
