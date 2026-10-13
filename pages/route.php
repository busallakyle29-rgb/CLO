<?php
declare(strict_types=1);

$pageName = (string) ($_GET['page'] ?? '');
$allowedPages = [
    'admin',
    'all_documents',
    'archive_documents',
    'audit_trail',
    'dashboard',
    'dashboard_confidentiality',
    'department_management',
    'document_management',
    'document_routing',
    'document_tracking',
    'document_type_management',
    'forgot_password',
    'import_documents',
    'login',
    'output_tracking_slip_form',
    'pending_access_requests',
    'pending_documents',
    'register_admin',
    'reports',
    'reset_password',
    'role_management',
    'system_settings',
    'user_profile',
];

if (!in_array($pageName, $allowedPages, true)) {
    http_response_code(404);
    exit('Page not found.');
}

require __DIR__ . '/' . $pageName . '/' . $pageName . '.php';
