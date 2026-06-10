<?php
require_once './pdo.php';
session_start();
$account = $_SESSION['account'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jobId']) && isset($_FILES["images"])) {
    $errorMessages = [];

    try {
        $jobId = $_POST['jobId'];
        $carbontotalUpdateValue = '是';
            
        $sqlUpdate = "UPDATE carbontotal SET is_job_check = :isJobCheck WHERE id = :jobId";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':isJobCheck', $carbontotalUpdateValue);
        $stmtUpdate->bindParam(':jobId', $jobId);
        $stmtUpdate->execute();

        $datetimeStart = $_POST['datetimeStart'];
        $datetimeEnd = $_POST['datetimeEnd'];

        if (!is_dir("images")) {
            mkdir("images", 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES["images"]["name"][0], PATHINFO_EXTENSION));
        $frontImageName = md5(uniqid()) . "_front." . $ext;
        $frontTarget = "images/" . $frontImageName;
        
        $ext = strtolower(pathinfo($_FILES["images"]["name"][1], PATHINFO_EXTENSION));
        $backImageName = md5(uniqid()) . "_back." . $ext;
        $backTarget = "images/" . $backImageName;

        $frontTmpName = $_FILES["images"]["tmp_name"][0];
        if (!move_uploaded_file($frontTmpName, $frontTarget)) {
            throw new Exception('前照片上傳失敗');
        }
        
        $backTmpName = $_FILES["images"]["tmp_name"][1];
        if (!move_uploaded_file($backTmpName, $backTarget)) {
            throw new Exception('後照片上傳失敗');
        }
        

        $pdo = $database->getPdo();
        $sql = "INSERT INTO jobrecall (account, datetime_start, datetime_end, front_image_path, back_image_path) VALUES (:account, :datetimeStart, :datetimeEnd, :frontImagePath, :backImagePath)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':account', $account);
        $stmt->bindParam(':datetimeStart', $datetimeStart);
        $stmt->bindParam(':datetimeEnd', $datetimeEnd);
        $stmt->bindParam(':frontImagePath', $frontImageName);
        $stmt->bindParam(':backImagePath', $backImageName);
        // $stmt->bindParam(':uploadCount', $uploadCount);
        $stmt->execute();

        $response = array('state' => 'success', 'message' => '上傳成功等待後台人員回應', 'redirect' => 'registJob.php');
        echo '<script>';
        echo 'alert("' . $response['message'] . '");';
        echo 'window.location.href = "state";';
        echo '</script>';

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $errorMessages[] = '檔案上傳失敗: ' . $e->getMessage();
        $response = array('state' => false, 'messages' => $errorMessages);
        echo '<script>';
        foreach ($errorMessages as $errorMessage) {
            echo 'alert("' . $errorMessage . '");';
        }
        echo 'window.location.href = "registJob";';
        echo '</script>';
    } finally {
        $pdo = null;
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
}
?>