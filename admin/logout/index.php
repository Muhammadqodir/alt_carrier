<?php
// Fix session path for cPanel
$session_path = sys_get_temp_dir();
if (!is_writable($session_path)) {
    $session_path = dirname(__FILE__) . '/../../sessions';
    if (!file_exists($session_path)) {
        mkdir($session_path, 0700, true);
    }
}
session_save_path($session_path);

session_start();
session_destroy();
header('Location: /altcarrier/admin/login');
exit;
?>
