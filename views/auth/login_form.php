<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4 p-lg-5">
        <h1 class="h4 mb-3">Sign In</h1>
        <p class="text-muted small mb-4">Use your username or email to continue.</p>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
              <div><?php echo e($error); ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" action="<?php echo e(app_url('login.php')); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('login_form')); ?>">

          <div class="mb-3">
            <label for="login" class="form-label">Username or Email</label>
            <input type="text" class="form-control" id="login" name="login" required value="<?php echo e($old['login'] ?? ''); ?>">
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>

          <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me" value="1">
            <label class="form-check-label" for="remember_me">Remember me</label>
          </div>

          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary">Login</button>
          </div>

          <div class="d-flex justify-content-between small">
            <a href="<?php echo e(app_url('forgot_password.php')); ?>">Forgot password?</a>
            <a href="<?php echo e(app_url('register_admin.php')); ?>">Register staff</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
