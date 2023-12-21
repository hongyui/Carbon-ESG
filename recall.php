<?php
require_once './pdo.php';
session_start();

if (isset($_SESSION['account'])) {
    $pdo = $database->getPdo();
    
    if (isset($_GET["id"])) {
        $id = $_GET["id"];

        $stmt = $pdo->prepare("UPDATE carbontotal SET recall = '是' WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        header('Location: admin.php');
        echo json_encode(["status" => "success"]);
        exit();
    } 
  }
  echo json_encode(["status" => "error"]);
  exit();
?>