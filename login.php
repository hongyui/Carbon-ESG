<?php
require_once 'pdo.php';

class Authentication
{
    private $pdo;

    public function __construct()
    {
        global $database;
        $this->pdo = $database->getPdo();
    }
    public function loginUser($account, $password)
    {
        try {
            $sql = "SELECT * FROM user WHERE account = :account AND password = :password";
            $statement = $this->pdo->prepare($sql);
            $statement->bindParam(':account', $account);
            $statement->bindParam(':password', $password);
            $statement->execute();

            if ($statement->rowCount() > 0) {
                $user = $statement->fetch(PDO::FETCH_ASSOC);

                session_start();
                $_SESSION['account'] = $account;
                $_SESSION['userAddress'] = $user['userAddress'];                

                if ($user['manager'] == '1') {
                    $_SESSION['role'] = 1;
                } elseif ($user['manager'] == '2') {
                    $_SESSION['role'] = 2;
                } else {
                    $_SESSION['role'] = 3;
                }

                $response = array('state' => true, 'message' => '登入成功');
            } else {
                $response = array('state' => false, 'message' => '帳號或密碼錯誤');
            }

            header('Content-Type: application/json');
            echo json_encode($response);
        } catch (Exception $e) {
            $errorResponse = array('state' => false, 'message' => 'Internal Server Error');
            echo json_encode($errorResponse);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account = $_POST['account'];
    $password = $_POST['password'];

    $auth = new Authentication();
    $auth->loginUser($account, $password);
}