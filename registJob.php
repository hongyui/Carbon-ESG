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
  <section id="job" class="hero-lg">
    <?php if (!$isLoggedIn) : ?>
    <div style="display: grid; place-items:center; height: calc(100vh - 200px); font-size: 62px;">
      <h2>請先登入</h2>
    </div>
    <?php else : ?>
    <div class="container-lg">
      <h5>資料填寫</h5>

      <button type="button" class="btn btn-outline-dark btn-reset my-5" data-bs-toggle="modal" data-bs-target="#abc">
        工作申請資料
      </button>
      <button type="button" class="btn btn-outline-dark btn-reset my-5" data-bs-toggle="modal" data-bs-target="#def">
        工作回報
      </button>

      <div class="modal fade" id="abc" tabindex="-1" aria-labelledby="abc" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form action="jobsave.php" method="post" class="py-4 w-75 mx-auto" accept-charset="UTF-8">
                <label for="reason" class="py-3">申請原因</label>
                <textarea name="reason" id="reason" class="form-control" required></textarea>
                <label for="experience" class="py-3">相關工作經驗(是/否)</label>
                <input type="radio" name="experience" id="experienceYes" value="是" required checked>
                <label for="experienceYes">是</label>
                <input type="radio" name="experience" id="experienceNo" value="否">
                <label for="experienceNo">否</label>
                <br>
                <label for="age" class="py-3">年齡</label>
                <input type="number" name="age" id="age" class="form-control" required>
                <label for="residence" class="py-3">居住地(鄉鎮)</label>
                <input type="text" name="residence" id="residence" class="form-control" required>
                <label for="contact" class="py-3">聯絡方式</label>
                <input type="tel" name="contact" id="contact" class="form-control" required>
                <div class="text-end py-3">
                  <button type="submit" class="btn btn-outline-dark">提交</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="def" tabindex="-1" aria-labelledby="defLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form action="jobsave.php" method="post" class="py-4 w-75 mx-auto" accept-charset="UTF-8">
                <label for="reason" class="py-3">申請原因</label>
                <textarea name="reason" id="reason" class="form-control" required></textarea>
                <label for="experience" class="py-3">相關工作經驗(是/否)</label>
                <input type="radio" name="experience" id="experienceYes" value="是" required checked>
                <label for="experienceYes">是</label>
                <input type="radio" name="experience" id="experienceNo" value="否">
                <label for="experienceNo">否</label>
                <br>
                <label for="age" class="py-3">年齡</label>
                <input type="number" name="age" id="age" class="form-control" required>
                <label for="residence" class="py-3">居住地(鄉鎮)</label>
                <input type="text" name="residence" id="residence" class="form-control" required>
                <label for="contact" class="py-3">聯絡方式</label>
                <input type="tel" name="contact" id="contact" class="form-control" required>
                <div class="text-end py-3">
                  <button type="submit" class="btn btn-outline-dark">提交</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </section>
  <?php include('./templates/loginregist.php');?>
</body>
<script src="./js/bootstrap.bundle.min.js"></script>
<script src="./js/script.js"></script>

</html>