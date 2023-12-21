<?php
require_once './pdo.php';
session_start();
$account = $_SESSION['account'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES["images"])) {
  extract($_POST);
  $isPeople = isset($_POST['cleanup']) ? $_POST['cleanup'] : '否';

  if (!is_dir("images")) {
    mkdir("images");
  }
  $ext = strtolower(pathinfo($_FILES["images"]["name"], PATHINFO_EXTENSION));
  $image_name = md5(uniqid()) . "." . $ext;
  $target = "images/" . $image_name;
  if (move_uploaded_file($_FILES["images"]["tmp_name"], $target)) {
    $pdo = $database->getPdo();
    $sql = "INSERT INTO carbontotal (location, detal, price, carbontotal, image_path, account, is_people, contact) VALUES (:location, :detal, :price, :carbontotal, :image_path, :account, :is_people, :contact)";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':detal', $detal);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':carbontotal', $carbontotal);
    $stmt->bindParam(':image_path', $image_name);
    $stmt->bindParam(':account', $account);
    $stmt->bindParam(':is_people', $isPeople);
    $stmt->bindParam(':contact', $contact);
    $stmt->execute();
    
    $response = array('state' => 'success', 'message' => '上傳成功等待後台人員回應');
    echo json_encode($response);
  } else {
    $response = array('state' => false, 'message' => '檔案上傳失敗');
    echo json_encode($response);
  }
  $pdo = null;
}