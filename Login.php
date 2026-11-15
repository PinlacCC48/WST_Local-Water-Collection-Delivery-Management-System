<!-- database connection -->
<?php require_once __DIR__ . '/admin_db1.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Page — Majestic Spring Water Station</title>

  <!-- External Stylesheet and Script -->
  <link rel="stylesheet" href="Login_Style.css" />
  <script src="Login_Script.js" defer></script>
</head>
<body>

  <!-- ======================Navigation Bar====================== -->
  <header class="top-navbar">
    <div class="brand-left">
      <button
        type="button"
        id="themeToggleBtn"
        class="theme-circle-btn"
      >
        <img
          id="themeToggleImg"
          src="Lightmode_Pic.png"
          alt="Light Mode Active"
          class="theme-icon-img"
          decoding="async"
        />
      </button>
      <div class="brand-titles">
        <h1>Majestic Spring Water Station</h1>
        <p>Water Collection &amp; Delivery Management System</p>
      </div>
    </div>

    <nav class="nav-right">
      <a href="Login.php"    id="navLoginBtn"    class="nav-pill-btn active">Login Page</a>
      <a href="Register.php" id="navRegisterBtn" class="nav-pill-btn">Register</a>
    </nav>
  </header>
  <!-- ======================Navigation Bar====================== -->

  <main class="login-workspace">
    <section class="login-split-card">

      <!--=========================Left Side Panel=========================-->
      <div class="left-info-panel">
        <div class="brand-showcase-box">
          <img
            src="Login_Container.png"
            alt="5-Gallon Water Container"
            class="container-image"
            decoding="async"
          />
          <h2>Majestic Spring</h2>
          <p>Online 5-Gallon Pickup &amp; Delivery Scheduling</p>
        </div>

        <div class="rate-cards-grid">
          <div class="rate-card">
            <span class="rc-top">5-GAL REFILL</span>
            <span class="rc-price">P30.00</span>
            <span class="rc-sub">Walk-in or Delivery</span>
          </div>
          <div class="rate-card">
            <span class="rc-top">NEW 5-GAL</span>
            <span class="rc-price">P250.00</span>
            <span class="rc-sub">Round or Slim</span>
          </div>
        </div>

        <div class="info-pills-stack">
          <div class="info-pill-row">
            <span class="ip-label">Operating Hours</span>
            <span class="ip-val">8:00 AM - 6:00 PM (20 Gal/Batch)</span>
          </div>
          <div class="info-pill-row">
            <span class="ip-label">Covered Area</span>
            <span class="ip-val">Brgy 22, 24, 26, 27, &amp; 35</span>
          </div>
          <div class="info-pill-row">
            <span class="ip-label">Payment Method</span>
            <span class="ip-val">Cash Only (Walk-In/Rider)</span>
          </div>
        </div>
      </div>
      <!--=========================Left Side Panel=========================-->

      <!--=========================RightSide Panel=========================-->
      <div class="right-form-panel">
        <div>
          <div class="signin-header">
            <h2>Sign In</h2>
            <p>Access your customer or station staff portal</p>
            <div class="signin-divider"></div>
          </div>

          <!-- Feedback Banner -->
          <div
            id="loginStatusBanner"
            class="status-banner<?php echo $loginStatusMessage !== '' ? ' show ' . htmlspecialchars($loginStatusType) : ''; ?>"
            role="status"
          ><?php echo htmlspecialchars($loginStatusMessage); ?></div>

         
          <form id="loginForm" method="POST" action="Login.php" novalidate>
       <!-- Login Field -->
            <div class="form-group">
              <label for="loginIdentifier" class="form-label">Email Address or Phone No.:*</label>
              <div class="input-wrapper">
                <input
                  type="text"
                  id="loginIdentifier"
                  name="login_identifier"
                  class="form-control-input"
                  placeholder="Enter email or phone number"
                  value="<?php echo htmlspecialchars($enteredIdentifier); ?>"
                  autocomplete="username"
                  required
                />
              </div>
            </div>
         <!-- Password -->
            <div class="form-group">
              <label for="loginPassword" class="form-label">Password:*</label>
              <div class="input-wrapper">
                <input
                  type="password"
                  id="loginPassword"
                  name="password"
                  class="form-control-input"
                  placeholder="Enter Password"
                  autocomplete="current-password"
                  required
                />
                <button type="button" id="togglePasswordBtn" class="password-toggle-btn">
                  Show
                </button>
              </div>
            </div>


       <!-- Remember Me & Forgot Password -->
            <div class="remember-forgot-row">
              <label class="remember-label" for="rememberMeChk">
                <input type="checkbox" id="rememberMeChk" name="remember_me" class="remember-checkbox" />
                <span>Remember me</span>
              </label>
              <button type="button" id="forgotPasswordBtn" class="forgot-link-btn">
                Forgot Password?
              </button>
            </div>

            <div class="signin-btn-stack">
              <button type="submit" id="loginSubmitBtn" class="btn-login-account">
                <span>Login to Account</span>
                <span>&#10095;</span>
              </button>
              <a href="Register.php" id="createAccountBtn" class="btn-create-account">
                New Customer? Create Account
              </a>
            </div>
          </form>
        </div>
      </div>
      <!--=========================RightSide Panel=========================-->

    </section>
  </main>

</body>
</html>