<?php
include "db.php";
session_start();

if(!isset($_SESSION['reset_email']))
{
    header("Location: forgotpassword.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
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
            background:green;
            color:white;
            border:none;
            cursor:pointer;
        }
        .error{
            color:red;
        }
        .success{
            color:green;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Reset Password</h2>

<?php
if(isset($_POST['reset']))
{
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if($password != $confirm)
    {
        echo "<p class='error'>Passwords do not match!</p>";
    }
    else
    {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];

        $sql = "UPDATE users SET password='$hashed' WHERE email='$email'";
        
        if($conn->query($sql))
        {
            echo "<p class='success'>Password Updated Successfully!</p>";
            session_destroy();
        }
        else
        {
            echo "<p class='error'>Error updating password!</p>";
        }
    }
}
?>

    <form method="POST" onsubmit="return validate()">
        <input type="password" name="password" id="password" placeholder="New Password" required>
        <input type="password" name="confirm" id="confirm" placeholder="Confirm Password" required>
        <button type="submit" name="reset">Update Password</button>

    </form>
    <button type="button" onclick="window.location.href='login.html'" 
        style="margin-top:10px; background:#555;">
    Back to Login
</button>
</div>

<script>
function validate(){
    var p = document.getElementById("password").value;
    var c = document.getElementById("confirm").value;

    if(p !== c){
        alert("Passwords do not match!");
        return false;
    }
    return true;
}
</script>

</body>
</html>