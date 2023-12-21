<?php
session_start();
$isLoggedIn = isset($_SESSION['account']) ? true : false;
$role = $_SESSION['role'];
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
  <?php include('./templates/header.php');?>
  <div class="mask-dark"></div>
  <img src="./image/pexels-zhengxun-lin-15379002.jpg" class="w-100 vh-100" alt="">
  <div class="text">
    <div class="container">
      <h2>碳權交易整合平台</h2>
      <h3>ＤＥＭＯ</h3>
    </div>
  </div>
  <main>
    <section class="hero" id="about">
      <div class="container-lg">
        <div class="text-center">
          <h2>臺灣碳權交易所</h2>
          <p class="py-4">協助企業達成碳中和目標並與產業成長發展取得平衡，共同落實國家政策。</p>
        </div>
        <div class="row mt-5 align-items-center">
          <div class="col-lg-6 col-12">
            <div class="py-2 ps-4">
              <h3>臺灣碳權交易所 2050淨零排放之目標</h3>
              <p class="my-3">
                為達成我國2050淨零排放之目標，依據2023年2月公布之氣候變遷因應法，由臺灣證券交易所與行政院國家發展基金管理會共同投資成立臺灣碳權交易所，藉由交易平台之建置，有效媒合供需，創造企業減碳誘因，進一步促進低碳生產技術及創新產業發展。未來本公司將與策略夥伴攜手合作，推廣培育綠色生態系統，減緩氣候變遷影響，協助企業達成碳中和目標並與產業成長發展取得平衡，共同落實國家政策。
              </p>
            </div>
          </div>
          <div class="col-lg-6 col-12">
            <img src="https://picsum.photos/id/1/280/180" class="w-100" alt="">
          </div>
        </div>
      </div>
    </section>
    <section class="hero" id="register">
      <div class="container-lg">
        <div class="text-center">
          <h2></h2>
        </div>
      </div>
    </section>
  </main>
  <?php include('./templates/loginregist.php');?>
</body>
<script src="./js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/web3/4.2.2/web3.min.js" crossorigin="anonymous"
  referrerpolicy="no-referrer"></script>
<script src="./js/contract.js"></script>
<script src="./js/script.js"></script>

</html>