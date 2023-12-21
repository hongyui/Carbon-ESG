<div class="modal fade" id="login" tabindex="-1" aria-labelledby="loginLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane"
              type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">登入</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane"
              type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">註冊</button>
          </li>
        </ul>
        <div class="tab-content" id="myTabContent">
          <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab"
            tabindex="0">
            <form id="LoginForm" class="px-5" method="POST" action="carbonsave.php">
              <input type="text" class="form-control my-5 w-100 rounded-pill" placeholder="帳號" name="Laccount" required>
              <div class="position-relative">
                <input type="password" class="form-control mt-5 mb-4 w-100 rounded-pill" placeholder="密碼"
                  name="Lpassword" required>
                <i class="fa-solid fa-eye-slash eye"></i>
              </div>
              <div class="text-center pb-2">
                <button type="submit" class="btn btn-md btn-primary m-3">登入</button>
              </div>
            </form>
          </div>
          <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
            <form id="RegisterForm" class="px-5" method="POST">
              <input type="text" class="form-control my-5 w-100 rounded-pill" placeholder="帳號" name="Raccount" required>
              <div class="position-relative">
                <input type="password" class="form-control mt-5 mb-4 w-100 rounded-pill" placeholder="密碼"
                  name="Rpassword" required>
                <i class="fa-solid fa-eye-slash eye"></i>
              </div>
              <p id="error-message" class="text-danger fw-bold text-center d-none">帳號密碼錯誤</p>
              <div class="text-center pb-2">
                <button type="submit" class="btn btn-md btn-primary m-3" id="registerButton">註冊</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>