<?php

require 'config.php';

 require "auth.php";
admin_required();
$result= $conn-> query("SELECT * FROM users");
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
.back-btn{
    display: inline-block;
    padding: 10px 18px;
    background-color: #0d6efd;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.3s ease;
}

.back-btn:hover{
    background-color: #084298;
}

</style>
</head>
<body>
<!-- BACK BUTTON -->
<a href="AdminDashboard.php" class="back-btn">
    <button>⬅ Back </button>
</a>
<h2>Voter Management</h2>
<table>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>mobile</th>
    <th>role</th>
    <th>faculty</th>
    <th>class</th>
    <th>photo</th>
    <th>password</th>
     <th>Action</th>
</tr>
<?php while ($row = $result->fetch_assoc()){
?>



<tr>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo $row['name']; ?></td>
    <td><?php echo $row['email']; ?></td>
    <td><?php echo $row['mobile']; ?></td>
    <td><?php echo $row['role']; ?></td>
    <td><?php echo $row['faculty']; ?></td>
    <td><?php echo $row['class']; ?></td>

   
    <td>********</td>

    <td>
        <?php if (!empty($row['photo'])) { ?>
            <img src="<?php echo $row['photo']; ?>" width="80" height="80" style="object-fit:cover; border-radius:5px;">
        <?php } else { ?>
            No Photo
        <?php } ?>
    </td>

    <!-- ACTION COLUMN -->
    <td>
        <a href="edit_voter.php?id=<?php echo $row['id']; ?>">Edit</a>
        |
        <a href="block_voter.php?id=<?php echo $row['id']; ?>"
           onclick="return confirm('Are you sure?')">
            <?php echo ($row['status'] == 'active') ? 'Block' : 'Unblock'; ?>
        </a>
    </td>
</tr>




<?php } ?>

</table>
</body>
</html>



