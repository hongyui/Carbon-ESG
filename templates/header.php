<header class="position-fixed blur w-100 z-100">
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="index"><img src="../../image/logo.png" alt="" class="w-100" height="62"></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTogglerDemo02"
        aria-controls="navbarTogglerDemo02" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <?php if($role != 1) :?>
          <li class="nav-item">
            <a class="nav-link" aria-current="page" href="rcarbon">碳匯交易</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" aria-current="page" href="registJob">申請工作</a>
          </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link" aria-current="page" href="state">查看狀態</a>
          </li>
          <?php if($role == 1) :?>
          <li class="nav-item">
            <a class="nav-link" aria-current="page" href="admin">管理員審核</a>
          </li>
          <?php endif; ?>
          <?php if (!$isLoggedIn) : ?>
          <li class="nav-item">
            <a class="nav-link" aria-current="page" href="javascript:;" data-bs-toggle="modal"
              data-bs-target="#login">登入/註冊</a>
          </li>
          <?php endif; ?>
          <?php if ($isLoggedIn) : ?>
          <li class="nav-item">
            <a class="nav-link" aria-current="page" href="logout">登出</a>
          </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</header>