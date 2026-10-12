<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4 p-lg-5">
        <h1 class="h4 mb-3">Reset Password</h1>
        <p class="text-muted small mb-4">Set a new secure password for your account.</p>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
              <div><?php echo e($error); ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
          <div class="alert alert-success"><?php echo e($successMessage); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo e(app_url('reset_password.php')); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('reset_password_form')); ?>">
          <input type="hidden" name="selector" value="<?php echo e($selector); ?>">
          <input type="hidden" name="token" value="<?php echo e($token); ?>">

          <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input type="password" class="form-control" id="password" name="password" required minlength="8">
          </div>

          <div class="mb-4">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">Update Password</button>
            <a class="btn btn-outline-secondary" href="<?php echo e(app_url('login.php')); ?>">Back to Login</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
