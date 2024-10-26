<?php
#!/usr/local/cpanel/3rdparty/bin/php
// scripts/install.php

if (posix_getuid() !== 0) {
    die("This script must be run as root\n");
}

// Define constants
define('WHM_ROOT', '/usr/local/cpanel/whostmgr/docroot/cgi');
define('PLUGIN_ROOT', WHM_ROOT . '/addons/venmail');

// Create required directories
$directories = [
    PLUGIN_ROOT . '/config',
    PLUGIN_ROOT . '/cache',
    PLUGIN_ROOT . '/data',
    PLUGIN_ROOT . '/logs',
    PLUGIN_ROOT . '/templates'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    chown($dir, 'root');
    chgrp($dir, 'wheel');
}

// Copy configuration files if they don't exist
$configFiles = [
    'venmail.conf',
    'addon_settings.conf',
    'plans.conf',
    'hooks.conf'
];

foreach ($configFiles as $file) {
    $target = PLUGIN_ROOT . "/config/{$file}";
    $example = PLUGIN_ROOT . "/config.example/{$file}.example";
    
    if (!file_exists($target) && file_exists($example)) {
        copy($example, $target);
        chmod($target, 0644);
    }
}

// Register WHM hooks
$hookScript = <<<EOT
#!/bin/sh
/usr/local/cpanel/3rdparty/bin/php {PLUGIN_ROOT}/scripts/hook_handler.php "\$@"
EOT;

$hookFile = '/usr/local/cpanel/hooks/email/addpop';
file_put_contents($hookFile, $hookScript);
chmod($hookFile, 0755);
chown($hookFile, 'root');
chgrp($hookFile, 'wheel');

// Create symbolic links
$links = [
    '/usr/local/cpanel/whostmgr/docroot/addon_plugins/venmail.png' => 
        PLUGIN_ROOT . '/html/assets/icon.png'
];

foreach ($links as $link => $target) {
    if (!file_exists($link) && file_exists($target)) {
        symlink($target, $link);
    }
}

// Verify installation
$requirements = [
    'php' => '7.4.0',
    'extensions' => ['curl', 'json', 'openssl', 'dom', 'mbstring']
];

// Check PHP version
if (version_compare(PHP_VERSION, $requirements['php'], '<')) {
    die("Error: PHP version {$requirements['php']} or higher is required\n");
}

// Check extensions
foreach ($requirements['extensions'] as $ext) {
    if (!extension_loaded($ext)) {
        die("Error: Required PHP extension '{$ext}' is not loaded\n");
    }
}

// Test API connection
try {
    $config = parse_ini_file(PLUGIN_ROOT . '/config/venmail.conf');
    
    $ch = curl_init($config['api_base_url'] . '/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode !== 200) {
        echo "Warning: Unable to connect to VenMail API\n";
    }
    
} catch (Exception $e) {
    echo "Warning: " . $e->getMessage() . "\n";
}

echo "VenMail plugin installed successfully\n";