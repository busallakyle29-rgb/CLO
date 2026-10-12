<?php
$firstAdminSetup = !auth_has_admin_user();
$registerFormUrl = app_url('register_admin.php');
?>
<div class="auth-register-form">
  <div class="login-office-name">CITY LEGAL OFFICE</div>
  <div class="login-system-name">DOCUMENT TRACKING SYSTEM</div>

  <h1 class="login-title"><?php echo $firstAdminSetup ? 'Create Admin Account' : 'Create Staff Account'; ?></h1>
  <p class="login-subtitle">
    <?php echo $firstAdminSetup
      ? 'Create an administrator account for system management.'
      : 'Create a staff account. The admin must approve your account before you can log in.'; ?>
  </p>

  <?php if (!$firstAdminSetup): ?>
    <div class="register-approval-notice">
      <i class="bi bi-info-circle-fill"></i>
      <span>Your account will remain pending until an administrator approves it.</span>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="login-alert" role="alert">
      <div class="login-alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <div>
        <?php foreach ($errors as $error): ?>
          <div class="login-error"><?php echo e((string) $error); ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" action="<?php echo e($registerFormUrl); ?>" id="registerForm" autocomplete="off" novalidate>
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('register_admin_form')); ?>">

    <div class="register-progress" aria-label="Registration progress">
      <div class="register-progress-step is-active" data-progress-step="account">
        <span>1</span>
        <strong>Account</strong>
      </div>
      <div class="register-progress-line"></div>
      <div class="register-progress-step" data-progress-step="security">
        <span>2</span>
        <strong>Security</strong>
      </div>
    </div>

    <section class="register-step register-step-account is-active" aria-labelledby="account-details-title">
      <div class="register-section-heading">
        <span class="register-section-icon"><i class="bi bi-person-vcard"></i></span>
        <div>
          <h2 id="account-details-title">Account Details</h2>
          <p>Enter the staff member's basic information.</p>
        </div>
      </div>

      <div class="register-grid">
      <div class="login-field">
        <label class="login-label" for="employee_no" >Employee No</label>
        <div class="input-wrapper">
          <i class="bi bi-123"></i>
          <input class="login-input" id="employee_no" name="employee_no" autocomplete="off" placeholder="CLO-12345" value="<?php echo e(auth_normalize_employee_no((string) ($old['employee_no'] ?? ''))); ?>">
        </div>
      </div>

      <div class="login-field">
        <label class="login-label" for="username">Username</label>
        <div class="input-wrapper">
          <i class="bi bi-person"></i>
          <input class="login-input" id="username" name="username" autocomplete="off" required value="<?php echo e($old['username'] ?? ''); ?>">
        </div>
      </div>

      <div class="login-field split-field">
        <label class="login-label" for="first_name">First Name</label>
        <div class="input-wrapper">
          <i class="bi bi-person-vcard"></i>
          <input class="login-input" id="first_name" name="first_name" autocomplete="off" required value="<?php echo e($old['first_name'] ?? ''); ?>">
        </div>
      </div>

      <div class="login-field split-field">
        <label class="login-label" for="last_name">Last Name</label>
        <div class="input-wrapper">
          <i class="bi bi-person-vcard"></i>
          <input class="login-input" id="last_name" name="last_name" autocomplete="off" required value="<?php echo e($old['last_name'] ?? ''); ?>">
        </div>
      </div>

      <div class="login-field split-field">
        <label class="login-label" for="email">Email</label>
        <div class="input-wrapper">
          <i class="bi bi-envelope"></i>
          <input type="email" class="login-input" id="email" name="email" autocomplete="off" required value="<?php echo e($old['email'] ?? ''); ?>">
        </div>
      </div>

      <div class="login-field split-field">
        <label class="login-label" for="department_id">Department</label>
        <div class="input-wrapper select-wrapper">
          <i class="bi bi-building"></i>
          <select class="login-input login-select" id="department_id_display" disabled aria-label="Department (fixed)">
            <?php foreach ($departments as $department): ?>
            <option value="<?php echo e((string) $department['department_id']); ?>" selected>
              <?php echo e($department['department_name']); ?>
            </option>
            <?php endforeach; ?>
          </select>
          <?php if ($departments !== []): ?>
            <input type="hidden" id="department_id" name="department_id" value="<?php echo e((string) $departments[0]['department_id']); ?>">
          <?php endif; ?>
        </div>
      </div>

      <?php if (!$firstAdminSetup): ?>
        <div class="login-field full-width">
          <label class="login-label" for="role_id">Role</label>
          <div class="input-wrapper select-wrapper">
            <i class="bi bi-person-badge"></i>
            <select class="login-input login-select" id="role_id" name="role_id" required>
              <option value="">Select Role</option>
              <?php foreach ($registerableRoles as $role): ?>
                <option value="<?php echo e((string) $role['role_id']); ?>" <?php echo (($old['role_id'] ?? '') == $role['role_id']) ? 'selected' : ''; ?>>
                  <?php echo e($role['role_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      <?php endif; ?>
      </div>
    </section>

    <section class="register-step register-step-security" aria-labelledby="security-details-title" hidden>
      <div class="register-section-heading">
        <span class="register-section-icon"><i class="bi bi-shield-lock"></i></span>
        <div>
          <h2 id="security-details-title">Security Details</h2>
          <p>Create the password and recovery information.</p>
        </div>
      </div>

      <div class="register-grid">
      <div class="login-field split-field">
        <label class="login-label" for="password">Password</label>
        <div class="input-wrapper">
          <i class="bi bi-lock"></i>
          <input type="password" class="login-input" id="password" name="password" autocomplete="new-password" required minlength="8" aria-describedby="passwordStrength">
          <button type="button" class="password-toggle register-password-toggle" data-register-password-toggle="password" aria-label="Show password">
            <i class="bi bi-eye"></i>
          </button>
        </div>
        <div class="password-strength" id="passwordStrength" aria-live="polite">Use at least 8 characters.</div>
      </div>

      <div class="login-field split-field">
        <label class="login-label" for="confirm_password">Confirm Password</label>
        <div class="input-wrapper">
          <i class="bi bi-lock-fill"></i>
          <input type="password" class="login-input" id="confirm_password" name="confirm_password" autocomplete="new-password" required minlength="8" aria-describedby="passwordMatch">
          <button type="button" class="password-toggle register-password-toggle" data-register-password-toggle="confirm_password" aria-label="Show password">
            <i class="bi bi-eye"></i>
          </button>
        </div>
        <div class="password-match" id="passwordMatch" aria-live="polite"></div>
      </div>

      <div class="login-field full-width">
        <label class="login-label" for="security_color">Favorite Color</label>
        <div class="input-wrapper">
          <i class="bi bi-palette"></i>
          <input class="login-input" id="security_color" name="security_color" autocomplete="off" required value="<?php echo e($old['security_color'] ?? ''); ?>">
        </div>
      </div>

      <div class="login-field full-width">
        <label class="login-label" for="security_food">Favorite Food</label>
        <div class="input-wrapper">
          <i class="bi bi-egg-fried"></i>
          <input class="login-input" id="security_food" name="security_food" autocomplete="off" required value="<?php echo e($old['security_food'] ?? ''); ?>">
        </div>
      </div>

      <div class="login-field full-width">
        <label class="login-label" for="security_birthday">Full Birthdate</label>
        <div class="input-wrapper">
          <i class="bi bi-calendar-date"></i>
          <input type="date" class="login-input" id="security_birthday" name="security_birthday" autocomplete="off" min="<?php echo e((new DateTimeImmutable('today'))->modify('-120 years')->format('Y-m-d')); ?>" max="<?php echo e((new DateTimeImmutable('today'))->format('Y-m-d')); ?>" required value="<?php echo e($old['security_birthday'] ?? ''); ?>">
        </div>
      </div>
      </div>

      <div class="register-review" aria-labelledby="register-review-title">
        <h3 id="register-review-title"><i class="bi bi-clipboard-check"></i> Review Details</h3>
        <dl>
          <div><dt>Employee No</dt><dd data-review="employee_no">-</dd></div>
          <div><dt>Name</dt><dd data-review="full_name">-</dd></div>
          <div><dt>Email</dt><dd data-review="email">-</dd></div>
          <div><dt>Department</dt><dd data-review="department_id">-</dd></div>
          <div><dt>Role</dt><dd data-review="role_id">-</dd></div>
        </dl>
      </div>
    </section>

    <div class="register-step-actions">
      <button type="button" class="login-button register-button register-next-button" id="registerNextButton">
        <span>Next</span>
        <i class="bi bi-arrow-right login-arrow"></i>
      </button>

      <button type="button" class="register-back-button" id="registerBackButton">
        <i class="bi bi-arrow-left"></i>
        <span>Back</span>
      </button>

      <button type="submit" class="login-button register-button register-submit-button" id="registerSubmitButton">
        <span class="login-button-icon"><i class="bi bi-person-plus"></i></span>
        <span><?php echo $firstAdminSetup ? 'Create Admin Account' : 'Submit Registration'; ?></span>
        <i class="bi bi-arrow-right login-arrow"></i>
      </button>
    </div>
  </form>

  <div class="register-section">
    <span>Already have an account?</span>
    <a href="#" data-auth-toggle="login">Sign In</a>
  </div>
</div>
