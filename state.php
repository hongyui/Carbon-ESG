<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
require_once './pdo.php';
$isLoggedIn = !empty($_SESSION['account']);
$account = $_SESSION['account'];
$role = $_SESSION['role'];

$pdo = $database->getPdo();
$usersql = "SELECT * FROM carbontotal WHERE account = :account";
$userstmt = $pdo->prepare($usersql);
$userstmt->bindParam(':account', $account, PDO::PARAM_STR);
$userstmt->execute();
$userData = $userstmt->fetchAll(PDO::FETCH_ASSOC);

$adminsql = "SELECT * FROM carbontotal";
$adminstmt = $pdo->prepare($adminsql);
$adminstmt->bindParam(':account', $account, PDO::PARAM_STR);
$adminstmt->execute();
$adminData = $adminstmt->fetchAll(PDO::FETCH_ASSOC);

$adminjobsql = "SELECT * FROM jobinfo";
$adminjobstmt = $pdo->prepare($adminjobsql);
$adminjobstmt->execute();
$adminjobsresult = $adminjobstmt->fetchAll(PDO::FETCH_ASSOC);

$userjobsql = "SELECT * FROM jobinfo WHERE user = :account";
$userjobstmt = $pdo->prepare($userjobsql);
$userjobstmt->bindParam(':account', $account, PDO::PARAM_STR);
$userjobstmt->execute();
$userjobsresult = $userjobstmt->fetchAll(PDO::FETCH_ASSOC);

$isBuysql = "SELECT * FROM carbontotal WHERE buy_people = :account";
$isBuystmt = $pdo->prepare($isBuysql);
$isBuystmt->bindParam(':account', $account, PDO::PARAM_STR);
$isBuystmt->execute();
$isBuyresult = $isBuystmt->fetchAll(PDO::FETCH_ASSOC);

$jobrecallsql = "SELECT is_check FROM jobrecall WHERE account = :account";
$jobrecallstmt = $pdo->prepare($jobrecallsql);
$jobrecallstmt->bindParam(':account', $result['account'], PDO::PARAM_STR);
$jobrecallstmt->execute();
$jobrecallresult = $jobrecallstmt->fetchAll(PDO::FETCH_ASSOC);

$jobrecallchecksql = "SELECT * FROM jobrecall";
$jobrecallcheckstmt = $pdo->prepare($jobrecallchecksql);
$jobrecallcheckstmt->execute();
$jobrecallcheckresult = $jobrecallcheckstmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

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
  <main class="hero-lg container-lg" id="rcarbon">
    <?php if (!$isLoggedIn) : ?>
    <div style="display: grid; place-items:center; height: calc(100vh - 200px); font-size: 62px;">
      <h2>請先登入</h2>
    </div>
    <?php else : ?>
    <div class="row">
      <div class="col-9">
        <h5>查看狀態</h5>
      </div>
    </div>
    <section id="product">
      <div class="container-lg">
        <ul class="nav nav-pills my-5" id="pills-tab" role="tablist">
          <button class="btn btn-outline-dark me-3 active" id="pills-home-tab" data-bs-toggle="pill"
            data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home"
            aria-selected="true">碳匯狀態</button>
          <button class="btn btn-outline-dark me-3" id="pills-profile-tab" data-bs-toggle="pill"
            data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile"
            aria-selected="false">工作申請狀態</button>
          <button class="btn btn-outline-dark" id="pills-contact-tab" data-bs-toggle="pill"
            data-bs-target="#pills-contact" type="button" role="tab" aria-controls="pills-contact"
            aria-selected="false">工作回報狀態</button>
        </ul>
        <div class="tab-content" id="pills-tabContent">
          <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab"
            tabindex="0">
            <?php if ($role == 1): ?>
            <?php foreach ($adminData as $result): ?>
            <div class="border rounded-3 shadow p-3 mb-3">
              <div class="row align-items-center justify-content-center">
                <div class="col-lg-3 col-10">
                  <img src="images/<?= $result['image_path']; ?>" alt="" class="w-100"
                    style="max-height: 260px; object-fit: cover;">
                </div>
                <div class="col-lg-9 col-10">
                  <p class="py-2 h5">所在位置: <?= $result['location']; ?></p>
                  <p class="py-2 h5">細節描述: <?= $result['detal']; ?></p>
                  <p class="py-2 h5 text-danger">售價: <?= number_format($result['price']); ?>元</p>
                  <p class="py-2 h5">碳匯總量: <?= number_format($result['carbontotal']); ?>頓</p>
                  <p class="py-2 h5">購買人:<?= $result['buy_people'] ? $result['buy_people'] : '無人購買' ?></p>
                  <?php if ($result['transactionAddress'] !== null): ?>
                  <p class="py-2 h5 text-break">交易地址:<?= $result['transactionAddress'] ?></p>
                  <?php endif; ?>
                  <div class="text-end">
                    <?php if ($result["is_check"] == '是' && $result['buy_people'] == null ):?>
                    <button class="btn btn-outline-success py-2" disabled>已審核</button>
                    <?php elseif (!empty($result['recall'])) : ?>
                    <button class="btn btn-outline-danger py-2" disabled>已撤回</button>
                    <?php elseif ($result["is_check"] == '否') : ?>
                    <button class="btn btn-outline-danger py-2" disabled>等待審核</button>
                    <?php elseif ( !empty($result['buy_people'])) : ?>
                    <button class="btn btn-outline-danger py-2" disabled>已售出</button> <?php endif; ?>
                  </div>
                </div>
              </div>
            </div> <?php endforeach ?>
            <?php else: ?>
            <?php foreach ($userData as $result): ?>
            <div class="border rounded-3 shadow p-3 mb-3">
              <div class="row align-items-center justify-content-center">
                <div class="col-lg-3 col-10">
                  <img src="images/<?= $result['image_path']; ?>" alt="" class="w-100"
                    style="max-height: 260px; object-fit: cover;">
                </div>
                <div class="col-lg-9 col-10">
                  <p class="py-2 h5">所在位置: <?= $result['location']; ?></p>
                  <p class="py-2 h5">細節描述: <?= $result['detal']; ?></p>
                  <p class="py-2 h5 text-danger">售價: <?= number_format($result['price']); ?>元</p>
                  <p class="py-2 h5">碳匯總量: <?= number_format($result['carbontotal']); ?>頓</p>
                  <p class="py-2 h5">購買人:<?= $result['buy_people'] ? $result['buy_people'] : '無人購買' ?></p>
                  <?php if ($result['transactionAddress'] !== null): ?>
                  <p class="py-2 h5 text-break">交易地址:<?= $result['transactionAddress'] ?></p>
                  <?php endif; ?>
                  <div class="text-end">
                    <?php if ($result['is_check'] == '否' && $result['buy_people'] == null && empty($result['recall'])): ?>
                    <button class="btn btn-outline-danger py-2" disabled>等待審核</button>
                    <?php elseif ($result['is_check'] == '是' && $result['buy_people'] == null && empty($result['recall'])): ?>
                    <button class="btn btn-outline-success py-2" disabled>已審核</button>
                    <?php elseif ($account != $result['buy_people'] && empty($result['recall'])): ?>
                    <button class="btn btn-outline-danger py-2" disabled>已售出</button>
                    <?php else: ?>
                    <button class="btn btn-outline-danger py-2" disabled>請重新申請</button>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach ?>
            <?php foreach ($isBuyresult as $result): ?>
            <?php include('./templates/rcarbonInfos.php') ?>
            <?php endforeach ?>
            <?php endif; ?>
          </div>
          <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab"
            tabindex="0">
            <?php if ($role == 1): ?>
            <div class="row gap-3">
              <?php foreach ($adminjobsresult as $result): ?>
              <div class="col-lg-3 rounded border shadow">
                <p class="px-3 py-2 h5">申請人:<?=$result['user']?></p>
                <p class="px-3 py-2 h5">申請原因:<?=$result['reason']?></p>
                <p class="px-3 py-2 h5">是否有經驗:<?=$result['experience']?></p>
                <p class="px-3 py-2 h5">年齡:<?=$result['age']?></p>
                <p class="px-3 py-2 h5">居住地(鄉鎮):<?=$result['residence']?></p>
                <p class="px-3 py-2 h5">聯絡方式:<?=$result['contact']?></p>
                <p class="px-3 py-2 h5">工作申請:
                  <?=$result['isCheck']?>
                </p>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="row gap-3">
              <?php foreach ($userjobsresult as $result): ?>
              <div class="col-lg-3 rounded border shadow">
                <p class="px-3 py-2 h5">申請原因:<?=$result['reason']?></p>
                <p class="px-3 py-2 h5">是否有經驗:<?=$result['experience']?></p>
                <p class="px-3 py-2 h5">年齡:<?=$result['age']?></p>
                <p class="px-3 py-2 h5">居住地(鄉鎮):<?=$result['residence']?></p>
                <p class="px-3 py-2 h5">聯絡方式:<?=$result['contact']?></p>
                <p class="px-3 py-2 h5">工作申請:<?=$result['isCheck']?></p>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
          <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab"
            tabindex="0">
            <?php foreach ($jobrecallcheckresult as $result): ?>
            <?php if ($result['is_check'] == 0):?>
            <div class='col-lg-3 rounded border shadow'>
              <p class="px-3 py-2 h5">回報人員: <?= $result['account']?></p>
              <p class="px-3 py-2 h5">開始時間: <br><?= $result['datetime_start'] ?></p>
              <p class="px-3 py-2 h5">結束時間: <br><?= $result['datetime_end'] ?></p>
              <div class="px-3 py-2 ">
                <label for="" class="px-3 py-2">前實景照片</label>
                <img class="w-50 h-50 object-cover" src="images/<?= $result['front_image_path'] ?>" alt="">
              </div>
              <div class="px-3 py-2">
                <label for="" class="px-3 py-2">後實景照片</label>
                <img class="w-50 h-50 object-cover" src="images/<?= $result['back_image_path'] ?>" alt="">
              </div>
              <div class="text-end px-3 py-2">
                <button class="btn btn-outline-danger" disabled>等待審核</button>
              </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php foreach ($jobrecallcheckresult as $result): ?>
            <?php if ($result['is_check'] == 1):?>
            <div class='col-lg-3 rounded border shadow'>
              <p class="px-3 py-2 h5">回報人員: <?= $result['account']?></p>
              <p class="px-3 py-2 h5">開始時間: <br><?= $result['datetime_start'] ?></p>
              <p class="px-3 py-2 h5">結束時間: <br><?= $result['datetime_end'] ?></p>
              <div class="px-3 py-2 ">
                <label for="" class="px-3 py-2">前實景照片</label>
                <img class="w-50 h-50 object-cover" src="images/<?= $result['front_image_path'] ?>" alt="">
              </div>
              <div class="px-3 py-2">
                <label for="" class="px-3 py-2">後實景照片</label>
                <img class="w-50 h-50 object-cover" src="images/<?= $result['back_image_path'] ?>" alt="">
              </div>
              <div class="text-end px-3 py-2">
                <button class="btn btn-outline-success" disabled>已審核</button>
              </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
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