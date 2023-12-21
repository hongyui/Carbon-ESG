<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
require_once './pdo.php';
$isLoggedIn = !empty($_SESSION['account']);
$account = $_SESSION['account'];
$role = $_SESSION['role'];

$pdo = $database->getPdo();

$adminsql = "SELECT * FROM carbontotal";
$adminstmt = $pdo->prepare($adminsql);
$adminstmt->bindParam(':account', $account, PDO::PARAM_STR);
$adminstmt->execute();
$adminData = $adminstmt->fetchAll(PDO::FETCH_ASSOC);

$adminsql = "SELECT * FROM carbontotal";
$adminstmt = $pdo->prepare($adminsql);
$adminstmt->bindParam(':account', $account, PDO::PARAM_STR);
$adminstmt->execute();
$adminData = $adminstmt->fetchAll(PDO::FETCH_ASSOC);

$adminjobsql = "SELECT * FROM jobinfo";
$adminjobstmt = $pdo->prepare($adminjobsql);
$adminjobstmt->execute();
$adminjobsresult = $adminjobstmt->fetchAll(PDO::FETCH_ASSOC);

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
        <h5>管理員審核</h5>
      </div>
    </div>
    <section id="product">
      <div class="container-lg">
        <ul class="nav nav-pills my-5" id="pills-tab" role="tablist">
          <button class="btn btn-outline-dark btn-reset me-3 active" id="pills-home-tab" data-bs-toggle="pill"
            data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home"
            aria-selected="true">審核碳匯</button>
          <button class="btn btn-outline-dark btn-reset" id="pills-profile-tab" data-bs-toggle="pill"
            data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile"
            aria-selected="false">審核工作申請</button>
        </ul>
        <div class="tab-content" id="pills-tabContent">
          <div id="pills-home" class="tab-pane fade show active" role="tabpanel" aria-labelledby="pills-home-tab"
            tabindex="0">
            <?php 
                $nopeople = true;
                foreach ($adminData as $result):
                if ($result["is_check"] == '否' && empty($result['recall'])):
              ?>
            <div class="row align-items-center py-2 my-4 border rounded-3 shadow">
              <div class="col-3">
                <img src="images/<?= $result['image_path']; ?>" alt="" class="w-100"
                  style="max-height: 260px; object-fit: cover;">
              </div>
              <div class="col-6">
                <p class="py-2 h5">所在位置: <?= $result['location']; ?></p>
                <p class="py-2 h5">細節描述: <?= $result['detal']; ?></p>
                <p class="py-2 h5 text-danger">售價: <?= $result['price']; ?>元</p>
                <p class="py-2 h5">碳匯總量: <?= $result['carbontotal']; ?>頓</p>
                <p class="py-2 h5">購買人:<?= $result['buy_people'] ? $result['buy_people'] : '無人購買' ?></p>
              </div>
              <div class="col-3 align-self-end">
                <div class="d-flex text-end">
                  <?php if ($result["is_check"] == '否'): ?>
                  <a href="recall.php?id=<?= $result['id']; ?>" class="btn btn-outline-danger w-50 py-2 me-2">撤回</a>
                  <a href="rcarbonCheck.php?id=<?= $result['id']; ?>" class="btn btn-outline-danger w-50 py-2">確認上架</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php
                $nopeople = false;
                endif;
                endforeach;
                if ($nopeople) {
                    echo "<h2 class='text-center h1'>無人申請</h2>";
                }
              ?>
          </div>
        </div>
        <div id="pills-profile" class="tab-pane fade" role="tabpanel" aria-labelledby="pills-profile-tab" tabindex="0">
          <?php if(empty($adminjobsresult)): ?>
          <h2 class='text-center h1'>無人申請</h2>
          <?php else: ?>
          <div class="row gap-3">
            <?php foreach($adminjobsresult as $result): ?>
            <div class="col-lg-3 rounded border shadow">
              <p class="px-3 py-2 h5">申請原因:<?=$result['reason']?></p>
              <p class="px-3 py-2 h5">是否有經驗:<?=$result['experience']?></p>
              <p class="px-3 py-2 h5">年齡:<?=$result['age']?></p>
              <p class="px-3 py-2 h5">居住地(鄉鎮):<?=$result['residence']?></p>
              <p class="px-3 py-2 h5">聯絡方式:<?=$result['contact']?></p>
              <p class="px-3 py-2 h5">是否審核:
                <?=$result['isCheck']?>
              </p>
              <?php if($role == 1 && $result['isCheck'] == '否'): ?>
              <div class="text-end pt-0 p-3">
                <a class="btn btn-outline-danger me-2" href="jobrecall.php?id=<?=$result['id']?>">撤回</a>
                <a class="btn btn-outline-success" href="jobCheck.php?id=<?=$result['id']?>">確認審核</a>
              </div>
              <?php else:?>
              <div class="text-end pt-0 p-3">
                <button class="btn btn-outline-success" disabled>已審核</button>
              </div>
              <?php endif;?>
            </div>
            <?php endforeach ?>
          </div>
          <?php endif ?>
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