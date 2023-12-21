<?php
  require_once "./pdo.php";

  try {
    extract($_POST);
    session_start();
    $account = $_SESSION['account'];
    $pdo = $database->getPdo();

    $sql = "INSERT INTO jobinfo (reason, experience, age, residence, contact, user) VALUES (:reason, :experience, :age, :residence, :contact , :user)";

    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':reason', $reason);
    $stmt->bindParam(':experience', $experience);
    $stmt->bindParam(':age', $age);
    $stmt->bindParam(':residence', $residence);
    $stmt->bindParam(':contact', $contact);
    $stmt->bindParam(':user', $account);
    header('Location: state.php');
    $stmt->execute();


  } catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
  }
?>
