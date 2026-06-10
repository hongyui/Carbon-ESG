<?php
  if (session_status() == PHP_SESSION_NONE) {
    session_start();
  }
  require_once './pdo.php';
  $isLoggedIn = !empty($_SESSION['account']);
  $account = $_SESSION['account'];
  $role = $_SESSION['role'];

  $pdo = $database->getPdo();
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
        <h5>確認購買</h5>
      </div>
    </div>
    <section id="buycheck">
      <div class="container-lg">

      </div>
    </section>
    <?php endif; ?>
  </main>
  <?php require './templates/loginregist.php';?>
</body>
<script src="./js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/web3/4.2.2/web3.min.js" crossorigin="anonymous"
  referrerpolicy="no-referrer"></script>
<script src="./js/script.js"></script>
<script src="./js/utils/web3.js"></script>
<script src="./js/contract.js"></script>
<script>
connectWallet();
</script>

</html>