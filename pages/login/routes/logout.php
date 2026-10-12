<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

auth_logout();
start_secure_session();
flash_set('success', 'You have been logged out successfully.');
redirect_to('index.php');
