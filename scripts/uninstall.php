<?php
#!/usr/local/cpanel/3rdparty/bin/php
// scripts/uninstall.php

if (posix_getuid() !== 0) {
    die("This script must be run as root\n");
}

define('WHM_ROOT', '/usr/local/cpanel/whostmgr/docroot/cgi');
define('PLUGIN_ROOT', WHM_ROOT . '/addons/venmail');

// Remove WHM hooks
$hookFile = '/usr/local/cpanel/hooks/email/addpop';
if (file_exists($hookFile)) {
    unlink($hookFile);
}

// Remove symbolic links
$links = [
    '/usr/local/cpanel/whostmgr/docroot/addon_plugins/venmail.png'
];

foreach ($links as $link) {
    if (is_link($link)) {
        unlink($link);
    }
}

// Backup configuration
$backupDir = '/root/venmail_backup_' . date('Y-m-d_His');
mkdir($backupDir, 0700);

$configFiles = glob(PLUGIN_ROOT . '/config/*');
foreach ($configFiles as $file) {
    copy($file, $backupDir . '/' . basename($file));
}

// Backup domain associations
if (file_exists(PLUGIN_ROOT . '/data/domain_associations.json')) {
    copy(
        PLUGIN_ROOT . '/data/domain_associations.json',
        $backupDir . '/domain_associations.json'
    );
}

// Remove plugin directory
system('rm -rf ' . PLUGIN_ROOT);

echo "VenMail plugin uninstalled successfully\n";
echo "Configuration backup saved to: {$backupDir}\n";