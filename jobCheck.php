<?php
  require './pdo.php';
  session_start();
  $id = isset($_GET['id']) ? $_GET['id'] : die('Missing id parameter');
  $pdo = $database->getPdo();
  extract($_GET);
  $stmt = $pdo->prepare("UPDATE jobinfo SET isCheck = '已審核' WHERE id = :id");
  $stmts = $pdo->prepare("UPDATE user SET manager = '3' WHERE account = :user");
  
  $stmt->bindParam(':id', $id);
  $stmt->execute();
  $stmts->bindParam(':user', $user);

  $stmts->execute();
  header('Location: state.php');

?>