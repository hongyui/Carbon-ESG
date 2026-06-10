<?php
require_once './pdo.php';
session_start();
$role = $_SESSION['role'];
$isLoggedIn = !empty($_SESSION['account']);
$account = $_SESSION['account'];

// $jobsql = "SELECT * FROM carbontotal WHERE is_people = '是' AND is_check = '是'";
$pdo = $database->getPdo();
$jobsql = "SELECT * FROM carbontotal";
$jobstmt = $pdo->prepare($jobsql);
$jobstmt->execute();
$jobData = $jobstmt->fetchAll(PDO::FETCH_ASSOC);


$ischeckpeople = "SELECT ischeck FROM jobinfo WHERE user = :account";
$ischeckpeoplestmt = $pdo->prepare($ischeckpeople);
$ischeckpeoplestmt->bindParam(':account', $account);
$ischeckpeoplestmt->execute();
$ischeck = $ischeckpeoplestmt->fetchColumn();

$jobrecall = "SELECT * FROM jobrecall";
$jobrecallstmt = $pdo->prepare($jobrecall);
$jobrecallstmt->execute();
$jobrecall = $jobrecallstmt->fetchAll(PDO::FETCH_ASSOC);

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
      <h5>工作申請</h5>
      <button type="button" class="btn btn-outline-dark btn-reset my-5" data-bs-toggle="modal"
        data-bs-target="#registjob">
        工作申請資料
      </button>
      <div class="modal fade" id="registjob" tabindex="-1" aria-labelledby="registjob" aria-hidden="true">
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

      <?php foreach($jobData as $result):?>
      <?php if($role != 2 && $result['is_people'] == '是' && $result['is_check'] == '是' && $result['is_job_check'] =='否'):?>
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
            <div class="text-end">
              <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#jobrecall">
                工作回報
              </button>
            </div>
          </div>
        </div>
      </div>
      <?php endif ;?>
      <div class="modal fade" id="jobrecall" tabindex="-1" aria-labelledby="jobrecallLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h3>工作回報</h3>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form action="registerjobsave.php" method="post" class="pb-4 pt-2 w-100" enctype="multipart/form-data">
                <div class="d-flex align-items-center py-3">
                  <label for="" class="pe-2">開始時間</label>
                  <input type="datetime-local" class="form-control w-75" id="datetimeStart" name="datetimeStart">
                </div>
                <div class="d-flex align-items-center py-3">
                  <label for="" class="pe-2">結束時間</label>
                  <input type="datetime-local" class="form-control w-75" id="datetimeEnd" name="datetimeEnd">
                </div>
                <label for="" class="py-3">環境前後實景照片</label>
                <img src="#" id="imagePreviewFront" alt="前實景照片預覽"
                  style="max-width: 100%; max-height: 200px; margin-top: 10px; display: none;">
                <div class="d-flex align-items-center py-3">
                  <label for="" class="pe-2">前實景照片</label>
                  <input type="file" name="images[]" id="fileInputFront" class="form-control w-75"
                    onchange="previewImage('fileInputFront', 'imagePreviewFront')">
                </div>
                <img src="#" id="imagePreviewBack" alt="後實景照片預覽"
                  style="max-width: 100%; max-height: 200px; margin-top: 10px; display: none;">
                <div class="d-flex align-items-center py-3">
                  <label for="" class="pe-2">後實景照片</label>
                  <input type="file" name="images[]" id="fileInputBack" class="form-control w-75"
                    onchange="previewImage('fileInputBack', 'imagePreviewBack')">
                </div>
                <div class="d-flex align-items-center py-3">
                  <label for="" class="pe-3">內容描述</label>
                  <textarea class="form-control w-75" name="content"
                    style="min-height:calc(1.5em + .75rem + calc(var(--bs-border-width) * 2));"></textarea>
                </div>
                <input type="hidden" name="jobId" value="<?= $result['id']; ?>">
                <div class="text-end pt-3">
                  <button type="submit" class="btn btn-outline-dark">提交</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
  <?php include('./templates/loginregist.php');?>
</body>
<script src="./js/bootstrap.bundle.min.js"></script>
<script src="./js/script.js"></script>
<script src="./js/registjob.js"></script>

</html>