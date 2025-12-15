<?php
require "connection_database.php";
$result= $mysqli-> query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html>
<head>
<title>Voter Management</title>
<style>
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    border: 1px solid #999;
    padding: 8px;
    text-align: center;
}
a {
    text-decoration: none;
    padding: 5px 10px;
}
</style>
</head>
<body>

<h2>Voter Management</h2>
<table>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>mobile</th>
    <th>role</th>
    <th>password</th>
</tr>
<?php while ($row = $result->fetch_assoc()){
?>
<tr>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo $row['name']; ?></td>
    <td><?php echo $row['email']; ?></td>
    <td><?php echo $row['mobile']; ?></td>
    <td><?php echo $row['role']; ?></td>
    <td><?php echo $row['password']; ?></td>
    
    <td>
 <a href="edit_voter.php?id=<?php echo $row['id']; ?>">Edit</a>
        |
        <a href="block_voter.php?id=<?php echo $row['id']; ?>">
            <?php echo ($row['status']=='active') ? 'Block' : 'Unblock'; ?>
        </a>
    </td>
</tr>
<?php } ?>

</table>
</body>
</html>



