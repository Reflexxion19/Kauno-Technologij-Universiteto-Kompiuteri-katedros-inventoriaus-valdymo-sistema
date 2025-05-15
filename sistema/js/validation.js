const input_email_login = document.getElementById('email_login');
const input_password_login = document.getElementById('password_login');
const input_name = document.getElementById('name');
const input_surname = document.getElementById('surname');
const input_email_register = document.getElementById('email_register');
const input_password_register = document.getElementById('password_register');
const input_repeated_password = document.getElementById('repeated_password');

input_password_register.addEventListener('change', (ev) => {
  input_repeated_password.setAttribute('pattern', ev.target.value);
});

input_email_login.setCustomValidity('Įveskite duomenis!');
input_password_login.setCustomValidity('Įveskite duomenis!');
input_name.setCustomValidity('Įveskite duomenis!');
input_surname.setCustomValidity('Įveskite duomenis!');
input_email_register.setCustomValidity('Įveskite duomenis!');
input_password_register.setCustomValidity('Įveskite duomenis!');
input_repeated_password.setCustomValidity('Įveskite duomenis!');

input_email_login.addEventListener('input', function() {
  this.setCustomValidity('');
  if (!this.validity.valid) {
    this.setCustomValidity('Įveskite egzistuojantį el. pašto adresą!');
  }
});

input_password_login.addEventListener('input', function() {
  this.setCustomValidity('');
  if (!this.validity.valid) {
    this.setCustomValidity('Įveskite duomenis!');
  }
});

input_name.addEventListener('input', function() {
  this.setCustomValidity('');
  if (!this.validity.valid) {
    this.setCustomValidity('Vardas turi būti sudarytas tik iš raidžių!');
  }
});

input_surname.addEventListener('input', function() {
  this.setCustomValidity('');
  if (!this.validity.valid) {
    this.setCustomValidity('Pavardė turi būti sudaryta tik iš raidžių!');
  }
});

input_email_register.addEventListener('input', function() {
  this.setCustomValidity('');
  if (!this.validity.valid) {
    this.setCustomValidity('El. pašto adresas turi atitikti KTU išduoto ilgojo el. pašto adreso formatą!');
  }
});

input_password_register.addEventListener('input', function() {
  this.setCustomValidity('');
  if (!this.validity.valid) {
    this.setCustomValidity('Slaptažodis turi būti sudarytas bent iš 8 simbolių ir turėti bent vieną didžiąją raidę, mažąją raidę, skaičių ir specialų simbolį!');
  }
});

input_repeated_password.addEventListener('input', function() {
  this.setCustomValidity('');
  if (!this.validity.valid) {
    this.setCustomValidity('Slaptažodžiai turi sutapti!');
  }
});