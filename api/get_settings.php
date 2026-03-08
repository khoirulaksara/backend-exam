<?php
require_once 'config.php';
header('Content-Type: application/json');

echo json_encode([
    'strict_violations' => $enable_strict_violations,
    'cbt_enabled' => isset($enable_cbt_login) ? $enable_cbt_login : true,
    'custom_user_agent' => isset($custom_user_agent) ? $custom_user_agent : 'Mozilla/5.0 Archangel/2.0 Archangel',
    'cbt_url' => isset($cbt_url) ? $cbt_url : 'https://cbt.serat.us/',
    'allowed_signature' => isset($ALLOWED_APP_SIGNATURE) ? $ALLOWED_APP_SIGNATURE : ""
]);
?>
