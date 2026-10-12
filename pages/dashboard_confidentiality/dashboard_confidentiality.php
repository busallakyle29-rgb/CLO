<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

auth_require_page('dashboard.php');
redirect_to('dashboard.php');
exit;
