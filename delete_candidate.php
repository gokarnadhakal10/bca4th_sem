<?php
require 'config.php';

 require "auth.php";
admin_required();
$id = intval($_GET['id'] ?? 0);
if ($id){
$stmt = $mysqli->prepare("DELETE FROM candidates WHERE id=?");
$stmt->bind_param('i',$id);
$stmt->execute();
}
header('Location: AdminDashboard.php');
exit;