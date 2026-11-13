/* =========   ELEMENT REFERENCES  ========== */
(() => {
  const body = document.body;
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeToggleImg = document.getElementById('themeToggleImg');
  const loginForm = document.getElementById('loginForm');
  const loginIdentifier = document.getElementById('loginIdentifier');
  const loginPassword = document.getElementById('loginPassword');
  const togglePasswordBtn = document.getElementById('togglePasswordBtn');
  const rememberMeChk = document.getElementById('rememberMeChk');
  const forgotPasswordBtn = document.getElementById('forgotPasswordBtn');
  const statusBanner = document.getElementById('loginStatusBanner');

 
  /* =========   light/dark image  ========== */
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

  /* =========== Theme setter & Remembered User ================ */
  try {
    const savedTheme = localStorage.getItem('majestic_theme');
    const isDark = savedTheme === 'dark';
    body.classList.toggle('dark-mode', isDark);
    syncThemeIcon(isDark);

    const savedUser = localStorage.getItem('majestic_remembered_user');
    if (savedUser && loginIdentifier && !loginIdentifier.value) {
      loginIdentifier.value = savedUser;
      if (rememberMeChk) rememberMeChk.checked = true;
    }
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

  /* =========== Show / Hide Password Toggle ================ */
  if (togglePasswordBtn && loginPassword) {
    togglePasswordBtn.addEventListener('click', () => {
      const isHidden = loginPassword.type === 'password';
      loginPassword.type = isHidden ? 'text' : 'password';
      togglePasswordBtn.textContent = isHidden ? 'Hide' : 'Show';
    });
  }

  /* =========== Forgot Password Notice ================ */
  if (forgotPasswordBtn) {
    forgotPasswordBtn.addEventListener('click', () => {
      showStatus(
        'Password Reset: Please contact the Majestic Spring Station via Majestic@gmail.com or call 09672543030',
        'success'
      );
    });
  }

  /* =========== Form Validation Before Submitting to Login.php ================ */
  if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
      const idVal = loginIdentifier.value.trim();
      const passVal = loginPassword.value.trim();

      if (!idVal || !passVal) {
        e.preventDefault();
        showStatus(
          'Invalid Account or Password: Please enter both your Email Address / Phone No. and Password.',
          'error'
        );
        return;
      }

      try {
        if (rememberMeChk && rememberMeChk.checked) {
          localStorage.setItem('majestic_remembered_user', idVal);
        } else {
          localStorage.removeItem('majestic_remembered_user');
        }
      } catch (err) {
      }
    });
  }
})();