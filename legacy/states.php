<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once './pdo.php';

$isLoggedIn = !empty($_SESSION['account']);
$account = $_SESSION['account'];
$role = $_SESSION['role'];

$pdo = $database->getPdo();

$sql = "SELECT * FROM carbontotal";
$isChecksql = "SELECT * FROM carbontotal WHERE user = :account AND isCheck = '否'";
$isBuysql = "SELECT * FROM carbontotal WHERE buypeople = :account";
$jobsql = "SELECT * FROM jobinfo WHERE user = :account";
$jobsqls = "SELECT * FROM jobinfo";

$stmt = $pdo->prepare($sql);
$isCheckstmt = $pdo->prepare($isChecksql);
$isBuystmt = $pdo->prepare($isBuysql);
$jobstmt = $pdo->prepare($jobsql);
$jobstmts = $pdo->prepare($jobsqls);

$stmt->execute();
$isCheckstmt->bindParam(':account', $account, PDO::PARAM_STR);
$isBuystmt->bindParam(':account', $account, PDO::PARAM_STR);
$jobstmt->bindParam(':account', $account, PDO::PARAM_STR);
$isCheckstmt->execute();
$isBuystmt->execute();
$jobstmt->execute();
$jobstmts->execute();

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$isCheckresults = $isCheckstmt->fetchAll(PDO::FETCH_ASSOC);
$isBuyresults = $isBuystmt->fetchAll(PDO::FETCH_ASSOC);
$jobresults = $jobstmt->fetchAll(PDO::FETCH_ASSOC);
$jobresultss = $jobstmts->fetchAll(PDO::FETCH_ASSOC);

$pdo = null;
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>碳權交易整合平台</title>
  <link rel="stylesheet" href="./css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="./css/global.css">
  <link rel="stylesheet" href="./css/style.css">
  <link rel="shortcut icon" href="./image/logo.png" type="image/x-icon">
</head>

<body>
  <?php require './templates/header.php'; ?>
  <main class="hero-lg" id="rcarbon">
    <?php if (!$isLoggedIn) : ?>
    <div style="display: grid; place-items:center; height: calc(100vh - 200px); font-size: 62px;">
      <h2>請先登入</h2>
    </div>
    <?php else : ?>
    <div class="container-lg">
      <div class="row">
        <div class="col-9">
          <h5>查看狀態</h5>
        </div>
      </div>
    </div>
    <section id="product">
      <div class="container-lg">
        <ul class="nav nav-pills my-5" id="pills-tab" role="tablist">
          <button class="btn btn-outline-dark btn-reset mx-3 active" id="pills-home-tab" data-bs-toggle="pill"
            data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home"
            aria-selected="true">碳匯審核</button>
          <button class="btn btn-outline-dark btn-reset" id="pills-profile-tab" data-bs-toggle="pill"
            data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile"
            aria-selected="false">工作審核</button>
        </ul>
        <div class="tab-content" id="pills-tabContent">
          <div id="pills-home" class="tab-pane fade show active" role="tabpanel" aria-labelledby="pills-home-tab"
            tabindex="0">
            <?php if ($role == 1 && !empty($results)) : ?>
            <?php foreach ($results as $result) : ?>
            <?php include './templates/rcarbonInfo.php'?>
            <?php endforeach; ?>
            <?php elseif ($role == 2 && (!empty($isCheckresults) || !empty($isBuyresults))) : ?>
            <?php foreach ($isCheckresults as $result) : ?>
            <?php include './templates/rcarbonInfo.php'?>
            <?php endforeach; ?>
            <?php foreach ($isBuyresults as $result) : ?>
            <?php include './templates/rcarbonInfo.php'?>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div id="pills-profile" class="tab-pane fade" role="tabpanel" aria-labelledby="pills-profile-tab"
            tabindex="0">
            <div class="row py-2 my-4">
              <?php if($role != 1):?>
              <?php foreach ($jobresults as $result) : ?>
              <div class="col-lg-4 border rounded-3 shadow">
                <p class="py-2">申請人：<?=$result['user']?></p>
                <p class="py-2">申請原因：<?=$result['reason']?></p>
                <p class="py-2">是否有經驗：
                  <?=$result['experience']?>
                </p>
                <p class="py-2">年齡：
                  <?=$result['age']?>
                </p>
                <p class="py-2">居住地：<?=$result['residence']?></p>
                <p>聯絡電話：<?=$result['contact']?></p>
                <p class="py-2">通過狀態：<button class="btn btn-outline-danger" disabled><?=$result['isCheck']?></button>
                  <?php else: ?>
                  <?php endif; ?>
                </p>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </section>
    <?php endif; ?>
  </main>
  <?php require './templates/loginregist.php';?>
</body>
<script src="./js/bootstrap.bundle.min.js"></script>
<script src="./js/script.js"></script>

</html>