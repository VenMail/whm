<?php
// templates/admin/dashboard.php
?>
<div class="dashboard">
    <div class="dashboard-header">
        <div class="dashboard-actions">
            <a href="?action=bulk" class="btn btn-primary">
                <i class="icon icon-upload"></i> Bulk Import
            </a>
            <a href="?action=settings" class="btn btn-secondary">
                <i class="icon icon-settings"></i> Settings
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Active Domains</h3>
            <div class="stat-value"><?= number_format($stats['active_domains']) ?></div>
            <div class="stat-change <?= $stats['domain_growth'] >= 0 ? 'positive' : 'negative' ?>">
                <i class="icon <?= $stats['domain_growth'] >= 0 ? 'icon-trend-up' : 'icon-trend-down' ?>"></i>
                <?= abs($stats['domain_growth']) ?>% from last month
            </div>
        </div>

        <div class="stat-card">
            <h3>Monthly Revenue</h3>
            <div class="stat-value">$<?= number_format($stats['total_revenue'], 2) ?></div>
            <div class="stat-change <?= $stats['revenue_growth'] >= 0 ? 'positive' : 'negative' ?>">
                <i class="icon <?= $stats['revenue_growth'] >= 0 ? 'icon-trend-up' : 'icon-trend-down' ?>"></i>
                <?= abs($stats['revenue_growth']) ?>% from last month
            </div>
        </div>

        <div class="stat-card">
            <h3>DNS Health</h3>
            <?php if (empty($stats['dns_issues'])): ?>
                <div class="status-good">
                    <i class="icon icon-check-circle"></i>
                    All domains verified
                </div>
            <?php else: ?>
                <div class="status-warning">
                    <i class="icon icon-alert-triangle"></i>
                    <?= count($stats['dns_issues']) ?> domains need attention
                    <a href="?action=dns" class="btn btn-small">Review</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="stat-card">
            <h3>Pending Setup</h3>
            <div class="stat-value"><?= $stats['pending_setup'] ?></div>
            <?php if ($stats['pending_setup'] > 0): ?>
                <a href="?action=pending" class="btn btn-small">Process Queue</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-grid">
        <section class="dashboard-section recent-activity">
            <div class="section-header">
                <h2>Recent Activity</h2>
                <a href="?action=activity" class="btn btn-link">View All</a>
            </div>
            <div class="activity-list">
                <?php foreach ($activity as $item): ?>
                    <div class="activity-item">
                        <time datetime="<?= $item['timestamp'] ?>">
                            <?= $item['formatted_time'] ?>
                        </time>
                        <div class="activity-content">
                            <span class="activity-level <?= strtolower($item['level']) ?>">
                                <?= htmlspecialchars($item['level']) ?>
                            </span>
                            <strong><?= htmlspecialchars($item['action']) ?></strong>
                            <span><?= htmlspecialchars($item['details']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dashboard-section quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-grid">
                <a href="?action=dns" class="action-card">
                    <i class="icon icon-dns"></i>
                    <span>DNS Manager</span>
                </a>
                <a href="?action=plans" class="action-card">
                    <i class="icon icon-package"></i>
                    <span>Email Plans</span>
                </a>
                <a href="?action=reports" class="action-card">
                    <i class="icon icon-chart"></i>
                    <span>Reports</span>
                </a>
                <a href="?action=help" class="action-card">
                    <i class="icon icon-help-circle"></i>
                    <span>Help</span>
                </a>
            </div>
        </section>
    </div>
</div>