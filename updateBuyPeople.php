<?php
require_once './pdo.php';
session_start();

if (isset($_SESSION['account'])) {
    $pdo = $database->getPdo();
    $account = $_SESSION['account'];
    
    if (isset($_GET["id"]) && isset($_GET["transactionAddress"])) {
        $id = $_GET["id"];
        $transactionAddress = $_GET["transactionAddress"];

        $stmt = $pdo->prepare("UPDATE carbontotal SET buy_people = :account, transactionAddress = :transactionAddress WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':account', $account);
        $stmt->bindParam(':transactionAddress', $transactionAddress);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
        exit();
    } 

}

echo json_encode(["status" => "error"]);
exit();
?>