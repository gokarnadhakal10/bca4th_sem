<?php
include "db.php";
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        body{
            font-family: Arial;
            background:#f2f2f2;
        }
        .box{
            width:350px;
            margin:100px auto;
            padding:20px;
            background:white;
            border-radius:8px;
            box-shadow:0 0 10px gray;
        }
        input{
            width:100%;
            padding:10px;
            margin:8px 0;
        }
        button{
            width:100%;
            padding:10px;
            background:blue;
            color:white;
            border:none;
            cursor:pointer;
        }
        .error{
            color:red;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Forgot Password</h2>

    <?php
    if(isset($_POST['verify']))
    {
        $email = $_POST['email'];
        $mobile = $_POST['mobile'];

        $sql = "SELECT * FROM users WHERE email='$email' AND mobile='$mobile'";
        $result = $conn->query($sql);

        if($result->num_rows > 0)
        {
            $_SESSION['reset_email'] = $email;
            header("Location: resetpassword.php");
        }
        else
        {
            echo "<p class='error'>Wrong Email or Mobile!</p>";
        }
    }
    ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter Email" required>
        <input type="text" name="mobile" placeholder="Enter Mobile" required>
        <button type="submit" name="verify">Submit</button>
    </form>
</div>

</body>
</html>