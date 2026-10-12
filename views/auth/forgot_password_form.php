<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4 p-lg-5">
        <h1 class="h4 mb-3">Forgot Password</h1>
        <p class="text-muted small mb-4">Verify your account details to continue.</p>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
              <div><?php echo e((string) $error); ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
          <div class="alert alert-info"><?php echo e($successMessage); ?></div>
        <?php endif; ?>

        <?php if (!empty($resetLink)): ?>
          <div class="alert alert-warning small">
            Verified reset link:<br>
            <a href="<?php echo e($resetLink); ?>"><?php echo e($resetLink); ?></a>
          </div>
        <?php endif; ?>

        <form method="post" action="<?php echo e(app_url('forgot_password.php')); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('forgot_password_form')); ?>">

          <div class="mb-3">
            <label for="login" class="form-label">Username or Email</label>
            <input type="text" class="form-control" id="login" name="login" required value="<?php echo e($old['login'] ?? ''); ?>">
          </div>

          <div class="mb-4">
            <label for="employee_no" class="form-label">Employee Number</label>
            <input type="text" class="form-control" id="employee_no" name="employee_no" required value="<?php echo e($old['employee_no'] ?? ''); ?>">
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">Continue</button>
            <a class="btn btn-outline-secondary" href="<?php echo e(app_url('login.php')); ?>">Back to Login</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
