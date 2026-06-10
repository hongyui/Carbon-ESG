function previewImage(inputId, previewId) {
  var input = document.querySelector(`#${inputId}`);
  var preview = document.querySelector(`#${previewId}`);

  if (input.files && input.files[0]) {
    var reader = new FileReader();

    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };

    reader.readAsDataURL(input.files[0]);
  }
}

document.querySelector('#fileInputFront').addEventListener('change', checkFileType);
document.querySelector('#fileInputBack').addEventListener('change', checkFileType);

function checkFileType(event) {
  var fileInput = event.target;
  var allowedExtensions = /(\.png|\.jpg|\.jpeg)$/i;

  if (!allowedExtensions.exec(fileInput.value)) {
    alert('只允許上傳 png, jpg 或 jpeg 格式的檔案！');
    fileInput.value = '';
    return false;
  }
}

