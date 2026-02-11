<html>
<head>
    <title>password </head>
</head>
<style>

body{
    margin:3px;
    padding:10px;
}
body .h2{
    color:yellow;
    
}
input{
    color:white;
    
}

</style>
<body>
<form action="resetPassword.php" method="post">
    <h2>Forgot Password</h2>

    <input type="email" name="email" placeholder="Enter Email" required><br>
    <input type="text" name="phone" placeholder="Enter Phone Number" required><br>

    <input type="submit" value="Reset Password">
</form>
</body>
</html>