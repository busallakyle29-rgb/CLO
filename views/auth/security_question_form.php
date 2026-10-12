<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4 p-lg-5">
        <h1 class="h4 mb-3">Verify Identity</h1>
        <p class="text-muted small mb-4">Answer the security question to continue.</p>

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

        <form method="post" action="<?php echo e(app_url('forgot_password.php')); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token('security_question_form')); ?>">
          <input type="hidden" name="step" value="security_question">

          <div class="mb-4">
            <label for="answer" class="form-label"><?php echo e((string) ($question ?? 'Security question')); ?></label>
            <input type="text" class="form-control" id="answer" name="answer" required autocomplete="off">
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">Verify Answer</button>
            <a class="btn btn-outline-secondary" href="<?php echo e(app_url('forgot_password.php')); ?>">Start Again</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
