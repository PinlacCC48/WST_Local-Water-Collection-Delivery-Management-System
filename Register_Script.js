/* =========   ELEMENT REFERENCES  ========== */
(() => {
  const body = document.body;
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeToggleImg = document.getElementById('themeToggleImg');
  const registerForm = document.getElementById('registerForm');
  const statusBanner = document.getElementById('registerStatusBanner');

  // Form Inputs
  const regFirstName = document.getElementById('regFirstName');
  const regLastName = document.getElementById('regLastName');
  const regNickname = document.getElementById('regNickname');
  const regHouseNo = document.getElementById('regHouseNo');
  const regStreetName = document.getElementById('regStreetName');
  const regBarangay = document.getElementById('regBarangay');
  const regCity = document.getElementById('regCity');
  const regPrimaryPhone = document.getElementById('regPrimaryPhone');
  const regBackupPhone = document.getElementById('regBackupPhone');
  const regEmail = document.getElementById('regEmail');
  const regPassword = document.getElementById('regPassword');
  const regConfirmPassword = document.getElementById('regConfirmPassword');
  const regGateNote = document.getElementById('regGateNote');

  // Buttons
  const togglePass1Btn = document.getElementById('togglePass1Btn');
  const togglePass2Btn = document.getElementById('togglePass2Btn');
  const clearFormBtn = document.getElementById('clearFormBtn');

  /* =========   light/dark mode images ========== */
  const syncThemeIcon = (isDark) => {
    if (!themeToggleImg) return;
    themeToggleImg.src = isDark ? 'Darkmode_Pic.png' : 'Lightmode_Pic.png';
    themeToggleImg.alt = isDark ? 'Dark Mode Active' : 'Light Mode Active';
  };

  /* =========   banner function  ========== */

  const showStatus = (message, type) => {
    if (!statusBanner) return;
    statusBanner.textContent = message;
    statusBanner.className = `status-banner show ${type}`;
  };

  const togglePasswordField = (inputEl, btnEl) => {
    if (!inputEl || !btnEl) return;
    const isHidden = inputEl.type === 'password';
    inputEl.type = isHidden ? 'text' : 'password';
    btnEl.textContent = isHidden ? 'Hide' : 'Show';
  };

  /* =========== Theme Initialization ================ */
  try {
    const savedTheme = localStorage.getItem('majestic_theme');
    const isDark = savedTheme === 'dark';
    body.classList.toggle('dark-mode', isDark);
    syncThemeIcon(isDark);
  } catch (err) {
    body.classList.remove('dark-mode');
    syncThemeIcon(false);
  }

  /* =========== Light / Dark Mode Toggle ================ */
  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      const isDark = body.classList.toggle('dark-mode');
      syncThemeIcon(isDark);
      try {
        localStorage.setItem('majestic_theme', isDark ? 'dark' : 'light');
      } catch (err) {
   
      }
    });
  }

  /* ===========  Show / Hide Password Toggles ================ */
  if (togglePass1Btn) {
    togglePass1Btn.addEventListener('click', () => {
      togglePasswordField(regPassword, togglePass1Btn);
    });
  }

  if (togglePass2Btn) {
    togglePass2Btn.addEventListener('click', () => {
      togglePasswordField(regConfirmPassword, togglePass2Btn);
    });
  }

  /* ===========  Clear Form Button ================ */
  if (clearFormBtn && registerForm) {
    clearFormBtn.addEventListener('click', () => {
      registerForm.reset();
      if (regFirstName) regFirstName.value = '';
      if (regLastName) regLastName.value = '';
      if (regNickname) regNickname.value = '';
      if (regHouseNo) regHouseNo.value = '';
      if (regStreetName) regStreetName.value = '';
      if (regBarangay) regBarangay.value = '';
      if (regCity) regCity.value = 'Caloocan City';
      if (regPrimaryPhone) regPrimaryPhone.value = '';
      if (regBackupPhone) regBackupPhone.value = '';
      if (regEmail) regEmail.value = '';
      if (regPassword) {
        regPassword.value = '';
        regPassword.type = 'password';
      }
      if (regConfirmPassword) {
        regConfirmPassword.value = '';
        regConfirmPassword.type = 'password';
      }
      if (regGateNote) regGateNote.value = '';
      if (togglePass1Btn) togglePass1Btn.textContent = 'Show';
      if (togglePass2Btn) togglePass2Btn.textContent = 'Show';
      if (statusBanner) {
        statusBanner.textContent = '';
        statusBanner.className = 'status-banner';
      }
      if (regFirstName) regFirstName.focus();
    });
  }

  /* =========== 5. Client-Side Validation Before Submitting to Register.php ================ */
  if (registerForm) {
    registerForm.addEventListener('submit', (e) => {
      const firstName = regFirstName.value.trim();
      const lastName = regLastName.value.trim();
      const nickname = regNickname.value.trim();
      const houseNo = regHouseNo.value.trim();
      const streetName = regStreetName.value.trim();
      const barangay = regBarangay.value.trim();
      const primaryPhone = regPrimaryPhone.value.trim();
      const email = regEmail.value.trim();
      const pass1 = regPassword.value;
      const pass2 = regConfirmPassword.value;

      if (
        !firstName ||
        !lastName ||
        !nickname ||
        !houseNo ||
        !streetName ||
        !barangay ||
        !primaryPhone ||
        !email ||
        !pass1 ||
        !pass2
      ) {
        e.preventDefault();
        showStatus(
          'Incomplete Registration: Please fill out all required fields marked with (*).',
          'error'
        );
        return;
      }

      if (pass1.length < 8) {
        e.preventDefault();
        showStatus(
          'Weak Password: Password must be at least 8 characters long.',
          'error'
        );
        return;
      }

      if (pass1 !== pass2) {
        e.preventDefault();
        showStatus(
          'Password Mismatch: Password and Confirm Password do not match.',
          'error'
        );
      }
    });
  }
})();