const eyes = document.querySelectorAll('.fa-eye-slash');
const inputs = document.querySelectorAll('[type="password"]');
const registerForm = document.querySelector('#RegisterForm');
const loginForm = document.querySelector('#LoginForm');

eyes.forEach((eye, index) => {
  eye.addEventListener('click', () => togglePasswordVisibility(index));
});

function togglePasswordVisibility(index) {
  eyes[index].classList.toggle('fa-eye-slash');
  if (inputs[index].getAttribute('type') === 'password') {
    inputs[index].setAttribute('type', 'text');
  } else {
    inputs[index].setAttribute('type', 'password');
  }
  eyes[index].classList.toggle('fa-eye');
}

const registConnectWallet = () => {

};
registerForm.addEventListener('submit', function (e) {
  e.preventDefault();

  const accountInput = registerForm.querySelector('input[name="Raccount"]');
  const passwordInput = registerForm.querySelector('input[name="Rpassword"]');

  const account = accountInput.value;
  const password = passwordInput.value;

  if (window.ethereum) {
    window.ethereum
      .request({ method: 'eth_requestAccounts' })
      .then((accounts) => {
        const userAddress = accounts[0];
        window.ethereum.selectedAddress = null;

        fetch('register.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            userAddress: userAddress,
            account: account,
            password: password,
          }),
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error('發生錯誤: ' + response.statusText);
            }
            return response.json();
          })
          .then((data) => {
            handleResponse(data, accountInput, passwordInput);
          })
          .catch((error) => {
            console.error(error);
          });
      })
      .catch((error) => {
        console.error("連接到MetaMask時發生錯誤", error);
      });
  } else {
    alert('請安裝MetaMask！');
  }
});

loginForm.addEventListener('submit', function (e) {
  e.preventDefault();

  const accountInput = loginForm.querySelector('input[name="Laccount"]');
  const passwordInput = loginForm.querySelector('input[name="Lpassword"]');

  const account = accountInput.value;
  const password = passwordInput.value;

  fetch('login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      account: account,
      password: password,
    }),
  })
    .then(response => response.json())
    .then(data => handleResponse(data, accountInput, passwordInput))
    .catch(error => console.error('Error:', error));
});

function handleResponse(data, accountInput, passwordInput) {
  alert(data.message);
  if (!data.state) {
    accountInput.value = '';
    passwordInput.value = '';
  }
  window.location.reload();
}

