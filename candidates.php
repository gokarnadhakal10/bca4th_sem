<?php
require 'config.php';
admin_required();
$candidates = $mysqli->query("SELECT * FROM candidates ORDER BY id DESC");
?>
<!doctype html>
<html><head><title>Candidates</title></head><body>
<h2>Candidates</h2>
<a href="add_candidate.php">Add New</a>
<table border="1"><thead><tr><th>ID</th><th>Name</th><th>Party</th><th>Photo</th></tr></thead>
<?php while($r = $candidates->fetch_assoc()): ?>
<tr>
<td><?=h($r['id'])?></td>
<td><?=h($r['Name'])?></td>
<td><?=h($r['Party'])?></td>
<td><?=h($r['Position'])?></td>
<td><?=h($r['votes'])?></td>
<td><?php if($r['Photo']) echo "<img src='uploads/".h($r['photo'])."' width='60'>"; ?></td>
</tr>
<?php endwhile; ?>
</table>
</body></html> 