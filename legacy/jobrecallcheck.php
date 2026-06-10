<?php
  require './pdo.php';
  $id = isset($_GET['id']) ? $_GET['id'] : die('Missing id parameter');
  $pdo = $database->getPdo();
  
  // $stmt = $pdo->prepare("UPDATE jobrecall SET is_check = '1' WHERE account IN (SELECT account FROM jobrecall GROUP BY account HAVING COUNT(account) > 1)");
  $stmt = $pdo->prepare("UPDATE jobrecall SET is_check = '1'");
  $stmt->bindParam(':id', $id);
  $stmt->execute();
  header('Location: state.php');

?>