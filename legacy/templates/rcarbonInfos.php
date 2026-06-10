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
        <?php if ((empty($result['buy_people']))):?>
        <div class="d-flex justify-content-end">
          <button class="btn btn-outline-success py-2 me-3" data-bs-toggle="modal"
            data-bs-target="#contacts">聯絡資訊</button>
          <button type="button" class="btn btn-outline-danger py-2" data-bs-toggle="modal" data-bs-target="#buy">
            購買
          </button>
        </div>
        <?php elseif (($role == 1 || $role == 2) && $result["is_check"] == '是' && $result['buy_people'] == null ):?>
        <button class="btn btn-outline-success py-2" disabled>已審核</button>
        <?php elseif (($role == 1 || $role == 2) && !empty($result['recall'])) : ?>
        <button class="btn btn-outline-danger py-2" disabled>請重新申請</button>
        <?php elseif ($result["is_check"] == '否') : ?>
        <button class="btn btn-outline-danger py-2" disabled>等待審核</button>
        <?php elseif ( !empty($result['buy_people'])) : ?>
        <button class="btn btn-outline-danger py-2" disabled>已售出</button>
        <?php elseif ($role == 2 && !empty($result['buy_people'])) : ?>
        <button class="btn btn-outline-danger py-2" disabled>已購買</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>