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
    </div>
  </div>
  <main>
    <section class="hero" id="about">
      <div class="container-lg">
        <div class="text-center">
          <h2>碳權與碳匯介紹</h2>
        </div>
        <div class="row mt-5 gap-lg-0 gap-3 justify-content-lg-start justify-content-center text-center py-3">
          <div class="col-lg-4 col-10">
            <div class="p-3">
              <h3 class="h3 about-title">什麼是碳權（Carbon Credit）？</h3>
              <i class="fa-solid fa-stamp" style="font-size: 3.2em; color: #4889a2;"></i>
              <p class="pt-4 lh-lg about-text">
                碳權是指企業或個體根據減少溫室氣體排放量所獲得的一種證書。這些證書代表了一定量的溫室氣體減排，可以在碳市場上進行買賣。這個機制鼓勵減少碳足跡，並促進投資於可再生能源和其他低碳技術。
              </p>
            </div>
          </div>
          <div class="col-lg-4 col-10">
            <div class="p-3">
              <h3 class="h3 about-title">什麼是碳匯（Carbon Sink）？</h3>
              <i class="fas fa-leaf" style="font-size: 3.2em; color: #4889a2;"></i>
              <p class="pt-4 lh-lg about-text">
                碳匯是自然界中可以吸收大氣中二氧化碳的資源，例如森林、海洋和泥炭地。它們通過光合作用等自然過程減少大氣中的二氧化碳濃度，是對抗全球暖化的重要自然資產。
              </p>
            </div>
          </div>
          <div class="col-lg-4 col-10">
            <div class="p-3">
              <h3 class="h3 about-title">碳權的功能與重要性</h3>
              <i class="fas fa-globe" style="font-size: 3.2em; color: #4889a2;"></i>
              <p class="pt-4 lh-lg about-text">
                碳權是指企業或個體根據減少溫室氣體排放量所獲得的一種證書。這些證書代表了一定量的溫室氣體減排，可以在碳市場上進行買賣。這個機制鼓勵減少碳足跡，並促進投資於可再生能源和其他低碳技術。
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="hero" id="intro">
      <div class="container-lg">
        <div class="text-center">
          <h2>平台功能介紹</h2>
          <div class="col-lg-8 col-10 mx-auto py-5">
            <p class="intro-subtxt lh-lg">
              平臺結合了政府與民間的碳權交易平台，主要目的是促進碳匯的有效管理與交易，同時確保環境可持續發展。通過這個平台，小規模土地所有者也可以參與到碳權交易中，促進社會各界對減碳的共同參與。
            </p>
          </div>
        </div>
        <div
          class="row mx-auto mt-4 gap-lg-0 gap-3 justify-content-lg-start justify-content-center text-center py-3 w-90">
          <div class="col-lg-4 col-10">
            <div class="rounded p-3 shadow bluer border">
              <h3 class="pb-3 text-orange bold">碳匯整合與交易</h3>
              <i class="fa-solid fa-people-group" style="font-size: 3.2em; color: #4889a2;"></i>
              <p class="pt-3 lh-lg">平台會整合私有地和公有地的碳匯總額，即使是小單位的碳匯也能與大企業進行交易。這有助於減少土地閒置和荒廢，並提高土地的有效利用。</p>
            </div>
          </div>
          <div class="col-lg-4 col-0 d-lg-block d-none"></div>
          <div class="col-lg-4 col-10">
            <div class="rounded p-3 shadow bluer border">
              <h3 class="pb-3 text-orange bold">價格機制</h3>
              <i class="fa-solid fa-coins" style="font-size: 3.2em; color: #4889a2;"></i>
              <p class="pt-3 lh-lg">交易系統根據市場供需和碳排放量來調整價格，並及時計算碳稅，以量計價，確保交易的公平性和透明度。</p>
            </div>
          </div>
          <div class="col-lg-4 col-10 d-lg-block d-none"></div>
          <div class="col-lg-4 col-10 d-lg-block d-none">
            <!-- <img src="./image/logosss.png" class="w-65" alt=""> -->
            <img src="./image/logos.png" class="rounded w-65" alt="">
          </div>
          <div class="col-lg-4 col-10 d-lg-block d-none"></div>
          <div class="col-lg-4 col-10">
            <div class="rounded p-3 shadow bluer border">
              <h3 class="pb-3 text-orange bold">技術支持</h3>
              <i class="fa-brands fa-ethereum" style="font-size: 3.2em; color: #4889a2;"></i>
              <p class="pt-3 lh-lg">結合區塊鏈技術，平台創建智能合約，以提高交易的安全性和公平性。</p>
            </div>
          </div>
          <div class="col-lg-4 col-10 d-lg-block d-none">

          </div>
          <div class="col-lg-4 col-10">
            <div class="rounded p-3 shadow bluer border">
              <h3 class="pb-3 text-orange bold">環境保護與社會責任</h3>
              <i class="fa-solid fa-earth-americas" style="font-size: 3.2em; color: #4889a2;"></i>
              <p class="pt-3 lh-lg">利用交易所得資金，聘請人員進行環境整理和保護，為社會提供額外的就業機會</p>
            </div>
          </div>
        </div>
      </div>
    </section>
    <footer>
      碳權交易整合平台 ｜ 2023 &copy;
    </footer>
  </main>
  <?php include('./templates/loginregist.php');?>
</body>
<script src="./js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/web3/4.2.2/web3.min.js" crossorigin="anonymous"
  referrerpolicy="no-referrer"></script>
<script src="./js/contract.js"></script>
<script src="./js/script.js"></script>

</html>