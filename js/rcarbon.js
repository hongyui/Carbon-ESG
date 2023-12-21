document.querySelector('#fileInput').addEventListener('change', handleFileSelect);

function handleFileSelect() {
  let fileInput = document.querySelector('#fileInput');
  let imagePreview = document.querySelector('#imagePreview');

  if (fileInput.files && fileInput.files[0]) {
    let file = fileInput.files[0];
    let allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];

    if (allowedTypes.includes(file.type)) {
      let reader = new FileReader();

      reader.onload = function (e) {
        imagePreview.src = e.target.result;
        imagePreview.style.display = 'block';
      }

      reader.readAsDataURL(file);
    } else {
      alert('請選擇正確的檔案格式，只允許 jpg、jpeg 和 png 檔案。');
      fileInput.value = '';
      imagePreview.style.display = 'none';
    }
  }
}

document.querySelector('#rcarbonForm').addEventListener('submit', function (e) {
  e.preventDefault();

  let location = document.querySelector('#location').value;
  let detal = document.querySelector('#detal').value;
  let price = document.querySelector('#price').value;
  let carbontotal = document.querySelector('#carbontotal').value;
  let cleanup = document.querySelector('input[name="cleanup"]:checked').value;
  let fileInput = document.querySelector('#fileInput');
  let images = fileInput.files[0];
  let contact = document.querySelector('#contact').value;

  let formData = new FormData();
  formData.append('location', location);
  formData.append('detal', detal);
  formData.append('price', price);
  formData.append('carbontotal', carbontotal);
  formData.append('cleanup', cleanup);
  formData.append('images', images);
  formData.append('contact', contact);

  fetch('carbonsave.php', {
    method: 'POST',
    body: formData,
  })
    .then(response => response.json())
    .then(responseData => {
      alert('狀態：' + responseData.state + '\n消息：' + responseData.message);
      window.location.href = '../state';
    })
    .catch(error => {
      console.error('發生錯誤：', error.message);
    });
});

async function handlePurchase(id, userAddress, price) {
  try {
    const buyerAddress = await connectWallet();
    const transactionAddress = await sendTransaction(buyerAddress[0], userAddress, price);

    await updateAndAlert(id,transactionAddress);
  } catch (error) {
    console.error('購買流程錯誤:', error.message);
  }
}

function updateAndAlert(id,transactionAddress) {
  fetch(`updateBuyPeople.php?id=${id}&transactionAddress=${transactionAddress}`)
      .then(response => {
          if (!response.ok) {
              throw new Error('更新失敗');
          }
          return response.json();
      })
      .then(data => {
          if (data.status === 'success') {
              alert('購買成功！\n合約地址：'+ transactionAddress);
              location.reload();
          } else {
              throw new Error('更新失敗');
          }
      })
      .catch(error => {
          console.error('更新失敗', error);
      });
}
