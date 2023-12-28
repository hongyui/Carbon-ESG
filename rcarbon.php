<?php
  require_once './pdo.php';
  session_start();
  $isLoggedIn = isset($_SESSION['account']) ? true : false;
  $account = $_SESSION['account'];
  $role = $_SESSION['role'];
  $userAddress = $_SESSION['userAddress'];

  $pdo = $database->getPdo();
  $sql = "SELECT * FROM carbontotal WHERE is_check = '是'";
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
  <?php include('./templates/header.php');?>
  <main class="hero-lg" id="rcarbon">
    <?php if (!$isLoggedIn) : ?>
    <div style="display: grid; place-items:center; height: calc(100vh - 200px); font-size: 62px;">
      <h2>請先登入</h2>
    </div>
    <?php else: ?>
    <div class="container-lg">
      <div class="row align-items-center">
        <div class="col-9">
          <h5>登記碳匯</h5>
        </div>
        <!-- <div class="col-3">
          <input type="text" class="form-control ms-auto" placeholder="搜尋">
        </div> -->
      </div>
      <button class="btn btn-outline-dark btn-reset my-5" data-bs-toggle="modal" data-bs-target="#rcarbonM">登記</button>
    </div>
    <section id="product">
      <div class="container-lg">
        <?php foreach ($results as $result) : ?>
        <?php 
        if (empty($result['buy_people'])) {
            include('./templates/rcarbonInfos.php');
        }
        ?>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
    <div class="modal fade" id="rcarbonM" tabindex="-1" aria-labelledby="rcarbonMLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title fs-5 text-center" id="rcarbonMLabel">請輸入以下資訊</h3>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" class="py-4 w-75 mx-auto" enctype="multipart/form-data" id="rcarbonForm">
              <label class="py-3" for="location">所在位置</label>
              <input type="text" placeholder="" class="form-control" name="location" id="location" required>
              <label class="py-3" for="detal">細節描述(選填)</label>
              <input type="text" class="form-control" name="detal" id="detal">
              <label class="py-3" for="price">出售金額(元)</label>
              <input type="text" placeholder="" class="form-control" name="price" id="price" required>
              <label class="py-3" for="carbontotal">碳匯總量(噸)</label>
              <input type="text" placeholder="" class="form-control" name="carbontotal" id="carbontotal" required>
              <label class="py-3" for="cleanup">整理人員需求(是/否)</label>
              <div class="my-3">
                <input type="radio" class="form-check-input" id="cleanup_yes" name="cleanup" value="是">
                <label for="cleanup_yes">是</label>
                <input type="radio" class="form-check-input" id="cleanup_no" name="cleanup" value="否" checked>
                <label for="cleanup_no">否</label>
              </div>
              <img src="#" id="imagePreview" alt="檔案預覽"
                style="max-width: 100%; max-height: 200px; margin-top: 10px; display: none;">
              <div class="d-flex align-items-center justify-content-between">
                <label class="py-3" for="fileInput">實景照片:</label>
                <input type="file" name="images" id="fileInput" class="form-control mx-0 w-75">
              </div>
              <label class="py-3" for="contact">聯絡方式</label>
              <input type="text" name="contact" id="contact" class="form-control" required>
              <div class="text-center">
                <button type="submit" class="btn btn-outline-dark btn-reset mt-2">送出</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="buy" tabindex="-1" aria-labelledby="buyLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title fs-5 text-center" id="buyLabel">確認交易資訊</h3>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="py-2 h5">賣家名稱: <?= $result['account']; ?></p>
            <p class="py-2 h5">賣家錢包地址: <span class="h6"><?= $userAddress ?></span></p>
            <p class="py-2 h5">所在位置: <?= $result['location']; ?></p>
            <p class="py-2 h5">碳匯總量: <?= number_format($result['carbontotal']); ?>頓</p>
            <p class="py-2 h5 text-danger">售價: <?= number_format($result['price']); ?>元</p>
            <div class="text-end">
              <a class="btn btn-outline-danger py-2" href="javascript:void(0);"
                onclick="handlePurchase(<?=$result['id']?>, '<?= $userAddress ?>', '<?= $result['price']; ?>')">確認購買</a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="contacts" tabindex="-1" aria-labelledby="contactsLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title fs-5 text-center" id="contactsLabel">聯絡資訊</h3>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="py-2 h5">賣家名稱: <?= $result['account']; ?></p>
            <p class=" py-2 h5">賣家電話: <?= $result['contact']; ?></p>
          </div>
        </div>
      </div>
    </div>
    <?php include('./templates/loginregist.php');?>
  </main>
</body>
<script src="./js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/web3/4.2.2/web3.min.js" crossorigin="anonymous"
  referrerpolicy="no-referrer">
</script>
<script src="./js/utils/web3.js"></script>
<script src="./js/contract.js"></script>
<script src="./js/script.js"></script>
<script src="./js/rcarbon.js"></script>

</html>