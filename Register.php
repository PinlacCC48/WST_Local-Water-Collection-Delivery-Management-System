<!-- database connection -->
<?php require_once __DIR__ . '/admin_db1.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register — Majestic Spring Water Station</title>

  <!-- External Stylesheet and Script -->
  <link rel="stylesheet" href="Register_Style.css" />
  <script src="Register_Script.js" defer></script>
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
      <a href="Login.php"    id="navLoginBtn"    class="nav-pill-btn">Login Page</a>
      <a href="Register.php" id="navRegisterBtn" class="nav-pill-btn active">Register</a>
    </nav>
  </header>
  <!-- ======================Navigation Bar====================== -->

  <!-- ======================Sub-Header Title & Divider====================== -->
  <section class="sub-header">
    <h2 id="pageTitle">Create Household Customer Account</h2>
    <p>Register your address and permanent Gallon Owner Tag. (Nickname)</p>
  </section>
  <!-- ======================Sub-Header Title & Divider====================== -->

  <!-- ======================Main Registration Card====================== -->
  <main class="register-workspace">
    <section class="register-card">

      <!-- Feedback Banner -->
      <div
        id="registerStatusBanner"
        class="status-banner<?php echo $registerStatusMessage !== '' ? ' show ' . htmlspecialchars($registerStatusType) : ''; ?>"
        role="status"
      ><?php echo htmlspecialchars($registerStatusMessage); ?></div>

    
      <form id="registerForm" method="POST" action="Register.php" novalidate>
        <input type="hidden" name="csrf_token" value="majestic_csrf_token_2026" />

        <!--=========================Box 1: Customer Name & Gallon Tag=========================-->
        <div class="group-box">
          <h3 class="group-title">1. Customer Name &amp; Gallon Tag</h3>
          <div class="grid-3">
            <div class="form-group">
              <label for="regFirstName" class="form-label">First Name:*</label>
              <input
                type="text"
                id="regFirstName"
                name="first_name"
                class="form-control-input"
                placeholder="e.g., Micael"
                value="<?php echo htmlspecialchars($firstName); ?>"
                autocomplete="given-name"
                required
              />
            </div>

            <div class="form-group">
              <label for="regLastName" class="form-label">Last Name:*</label>
              <input
                type="text"
                id="regLastName"
                name="last_name"
                class="form-control-input"
                placeholder="e.g., Masta"
                value="<?php echo htmlspecialchars($lastName); ?>"
                autocomplete="family-name"
                required
              />
            </div>

            <div class="form-group">
              <label for="regNickname" class="form-label">Nickname (Tag for Gallon):*</label>
              <input
                type="text"
                id="regNickname"
                name="container_label"
                class="form-control-input"
                placeholder="e.g., Masta"
                value="<?php echo htmlspecialchars($containerLabel); ?>"
                autocomplete="nickname"
                required
              />
            </div>
          </div>
        </div>
        <!--=========================Box 1: Customer Name & Gallon Tag=========================-->

        <!--=========================Box 2: Home Pickup & Delivery Address=========================-->
        <div class="group-box">
          <h3 class="group-title">2. Home Pickup &amp; Delivery Address</h3>
          <div class="grid-address">
            <div class="form-group">
              <label for="regHouseNo" class="form-label">House No:*</label>
              <input
                type="text"
                id="regHouseNo"
                name="house_no"
                class="form-control-input"
                placeholder="e.g., 125"
                value="<?php echo htmlspecialchars($houseNo); ?>"
                required
              />
            </div>

            <div class="form-group">
              <label for="regStreetName" class="form-label">Street Name:*</label>
              <input
                type="text"
                id="regStreetName"
                name="street_name"
                class="form-control-input"
                placeholder="e.g., Laon-laan Street"
                value="<?php echo htmlspecialchars($streetName); ?>"
                autocomplete="address-line1"
                required
              />
            </div>

            <div class="form-group">
              <label for="regBarangay" class="form-label">Barangay:*</label>
              <select id="regBarangay" name="barangay" class="form-control-select" required>
                <option value="" <?php echo $barangay === '' ? 'selected' : ''; ?>>Select Barangay...</option>
                <option value="Barangay 22" <?php echo ($barangay === 'Barangay 22' || $barangay === 'Brgy. 22') ? 'selected' : ''; ?>>Barangay 22</option>
                <option value="Barangay 24" <?php echo ($barangay === 'Barangay 24' || $barangay === 'Brgy. 24') ? 'selected' : ''; ?>>Barangay 24</option>
                <option value="Barangay 26" <?php echo ($barangay === 'Barangay 26' || $barangay === 'Brgy. 26') ? 'selected' : ''; ?>>Barangay 26</option>
                <option value="Barangay 27" <?php echo ($barangay === 'Barangay 27' || $barangay === 'Brgy. 27') ? 'selected' : ''; ?>>Barangay 27</option>
                <option value="Barangay 35" <?php echo ($barangay === 'Barangay 35' || $barangay === 'Brgy. 35') ? 'selected' : ''; ?>>Barangay 35</option>
              </select>
            </div>

            <div class="form-group">
              <label for="regCity" class="form-label">City:*</label>
              <input
                type="text"
                id="regCity"
                name="city"
                class="form-control-input city-badge-input"
                value="Caloocan City"
                readonly
              />
            </div>
          </div>
        </div>
        <!--=========================Box 2: Home Pickup & Delivery Address=========================-->

        <!--=========================Box 3: Contact Number & Account Security=========================-->
        <div class="group-box">
          <h3 class="group-title">3. Contact Information and Password</h3>
          <div class="grid-3">
            <div class="form-group">
              <label for="regPrimaryPhone" class="form-label">Primary Phone #:*</label>
              <input
                type="tel"
                id="regPrimaryPhone"
                name="primary_phone"
                class="form-control-input"
                placeholder="e.g., 0955454554"
                value="<?php echo htmlspecialchars($primaryPhone); ?>"
                autocomplete="tel"
                required
              />
            </div>

            <div class="form-group">
              <label for="regBackupPhone" class="form-label">Backup Phone # (Optional):</label>
              <input
                type="tel"
                id="regBackupPhone"
                name="backup_phone"
                class="form-control-input"
                placeholder="e.g., 09178889900"
                value="<?php echo htmlspecialchars($backupPhone); ?>"
              />
            </div>

            <div class="form-group">
              <label for="regEmail" class="form-label">Email Address:*</label>
              <input
                type="email"
                id="regEmail"
                name="email_address"
                class="form-control-input"
                placeholder="e.g., micael.masta@email.com"
                value="<?php echo htmlspecialchars($emailAddress); ?>"
                autocomplete="email"
                required
              />
            </div>
          </div>

          <div class="grid-3 row-top-gap">
            <div class="form-group">
              <label for="regPassword" class="form-label">Password:*</label>
              <div class="input-wrapper">
                <input
                  type="password"
                  id="regPassword"
                  name="password"
                  class="form-control-input"
                  placeholder="At least 8 characters"
                  autocomplete="new-password"
                  required
                />
                <button type="button" id="togglePass1Btn" class="password-toggle-btn">
                  Show
                </button>
              </div>
            </div>

            <div class="form-group">
              <label for="regConfirmPassword" class="form-label">Confirm Password:*</label>
              <div class="input-wrapper">
                <input
                  type="password"
                  id="regConfirmPassword"
                  name="confirm_password"
                  class="form-control-input"
                  placeholder="Re-enter password"
                  autocomplete="new-password"
                  required
                />
                <button type="button" id="togglePass2Btn" class="password-toggle-btn">
                  Show
                </button>
              </div>
            </div>

            <div class="form-group">
              <label for="regGateNote" class="form-label">Gate/Rider Note:</label>
              <input
                type="text"
                id="regGateNote"
                name="gate_rider_note"
                class="form-control-input"
                placeholder="e.g., Ring doorbell several times"
                value="<?php echo htmlspecialchars($gateRiderNote); ?>"
              />
            </div>
          </div>
        </div>
        <!--=========================Box 3: Contact Number & Account Security=========================-->

        <!--=========================Bottom Action Buttons=========================-->
        <div class="register-btn-row">
          <button type="submit" id="registerSubmitBtn" class="btn-register-submit">
            Register Account
          </button>
          <button type="button" id="clearFormBtn" class="btn-clear-form">
            Clear
          </button>
          <a href="Login.php" id="backToLoginBtn" class="btn-back-login">
            Back to Login
          </a>
        </div>
        <!--=========================Bottom Buttons=========================-->

      </form>
    </section>
  </main>
  <!-- ======================Main Registration Box====================== -->

</body>
</html>