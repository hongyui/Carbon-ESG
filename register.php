<?php
require_once 'pdo.php';

class Registration
{
    private $pdo;

    public function __construct()
    {
        global $database;
        $this->pdo = $database->getPdo();
    }

    public function registerUser($account, $password, $userAddress)
    {
        try {
            $checkSql = "SELECT COUNT(*) FROM user WHERE account = :account";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->bindParam(':account', $account);
            $checkStmt->execute();
            $count = $checkStmt->fetchColumn();

            if ($count > 0) {
                $response = array('state' => false, 'message' => '帳號重複，請重新註冊');
            } else {
                $manager = '2';
                $insertSql = "INSERT INTO user (account, password , manager , userAddress) VALUES (:account, :password , :manager , :userAddress)";
                $insertStmt = $this->pdo->prepare($insertSql);
                $insertStmt->bindParam(':account', $account);
                $insertStmt->bindParam(':password', $password);
                $insertStmt->bindParam(':manager', $manager);
                $insertStmt->bindParam(':userAddress', $userAddress);
                
                if ($insertStmt->execute()) {
                    $response = array('state' => true, 'message' => '註冊成功！');
                } else {
                    $response = array('state' => false, 'message' => '註冊失敗');
                }
            }
        } catch (PDOException $e) {
            $response = array('state' => false, 'message' => '資料庫錯誤：' . $e->getMessage());
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account = $_POST['account'];
    $password = $_POST['password'];
    $userAddress= $_POST['userAddress'];

    $registration = new Registration();
    $registration->registerUser($account, $password, $userAddress);
}

?>