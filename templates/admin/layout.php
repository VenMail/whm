<?php
// templates/admin/layout.php

require_once '/usr/local/cpanel/php/WHM.php';

WHM::header("VenMail Email Manager");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'VenMail Email Manager') ?></title>
    <link rel="stylesheet" href="html/assets/styles.css">
</head>
<body class="whm-theme">
    <div class="venmail-wrapper">
        <header class="venmail-header">
            <div class="header-content">
                <div class="header-title">
                    <img src="html/assets/icon.png" alt="VenMail" class="header-icon">
                    <h1><?= htmlspecialchars($title ?? 'VenMail Email Manager') ?></h1>
                </div>
                <?php if (isset($breadcrumbs)): ?>
                    <nav class="breadcrumbs">
                        <a href="?action=dashboard">Dashboard</a>
                        <?php foreach ($breadcrumbs as $label => $url): ?>
                            <span class="separator">/</span>
                            <a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?></a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
            </div>
        </header>

        <main class="venmail-content">
            <?php if (isset($flash_message)): ?>
                <div class="alert alert-<?= $flash_message['type'] ?>" role="alert">
                    <?= htmlspecialchars($flash_message['message']) ?>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>

        <footer class="venmail-footer">
            <div class="footer-content">
                <p>VenMail Manager v<?= htmlspecialchars($version) ?></p>
                <p><a href="https://docs.venmail.io" target="_blank">Documentation</a> | <a href="mailto:support@venmail.io">Support</a></p>
            </div>
        </footer>
    </div>
    <script src="html/assets/script.js"></script>
</body>
</html>
<?php WHM::footer(); ?>