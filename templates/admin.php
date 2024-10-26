```php
<?php
/**
 * Main admin dashboard template
 * @var array $stats Overall statistics
 * @var array $activity Recent activity data
 */
?>
<div class="admin-dashboard">
    <header class="dashboard-header">
        <h1>VenMail Email Manager</h1>
        <div class="actions">
            <a href="?action=bulk" class="btn btn-primary">Bulk Import</a>
            <a href="?action=settings" class="btn btn-secondary">Settings</a>
        </div>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Active Domains</h3>
            <div class="stat-value"><?= number_format($stats['active_domains']) ?></div>
            <div class="stat-change <?= $stats['domain_growth'] >= 0 ? 'positive' : 'negative' ?>">
                <?= $stats['domain_growth'] ?>% from last month
            </div>
        </div>

        <div class="stat-card">
            <h3>Monthly Revenue</h3>
            <div class="stat-value">$<?= number_format($stats['total_revenue'], 2) ?></div>
            <div class="stat-change <?= $stats['revenue_growth'] >= 0 ? 'positive' : 'negative' ?>">
                <?= $stats['revenue_growth'] ?>% from last month
            </div>
        </div>

        <div class="stat-card">
            <h3>Pending Setup</h3>
            <div class="stat-value"><?= $stats['pending_setup'] ?></div>
            <?php if ($stats['pending_setup'] > 0): ?>
                <a href="?action=pending" class="btn btn-small">Review</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-grid">
        <section class="dashboard-section">
            <h2>Recent Activity</h2>
            <div class="activity-log">
                <?php foreach ($activity as $item): ?>
                    <div class="activity-item">
                        <time datetime="<?= $item['timestamp'] ?>"><?= $item['formatted_time'] ?></time>
                        <div class="activity-content">
                            <strong><?= htmlspecialchars($item['action']) ?></strong>
                            <span><?= htmlspecialchars($item['details']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dashboard-section">
            <h2>DNS Status</h2>
            <div class="dns-status">
                <?php if (empty($stats['dns_issues'])): ?>
                    <div class="status-good">
                        All domains properly configured
                    </div>
                <?php else: ?>
                    <div class="status-warning">
                        <?= count($stats['dns_issues']) ?> domains need attention
                        <a href="?action=dns" class="btn btn-small">View</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
```


```php
<?php
/**
 * Bulk domain import template
 * @var array $plans Available email plans
 */
?>
<div class="bulk-import">
    <header class="page-header">
        <h1>Bulk Domain Import</h1>
        <a href="?action=dashboard" class="btn btn-link">← Back to Dashboard</a>
    </header>

    <div class="import-container">
        <form method="post" action="?action=bulk_process" enctype="multipart/form-data" class="import-form">
            <div class="form-section">
                <h3>Upload Domains</h3>
                <div class="form-field">
                    <label for="domains_file">CSV File</label>
                    <input type="file" id="domains_file" name="domains_file" accept=".csv" required>
                    <div class="field-help">
                        Format: Organization, Full Name, Email, Domain
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Plan Selection</h3>
                <div class="form-field">
                    <label for="plan_id">Email Plan</label>
                    <select id="plan_id" name="plan_id" required>
                        <option value="">Select a plan...</option>
                        <?php foreach ($plans as $plan): ?>
                            <option value="<?= $plan['id'] ?>">
                                <?= htmlspecialchars($plan['name']) ?> 
                                ($<?= number_format($plan['monthly_price'], 2) ?>/mo)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="subscription_mode">Billing Cycle</label>
                    <select id="subscription_mode" name="subscription_mode">
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly (Save 20%)</option>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h3>Options</h3>
                <div class="form-field checkbox">
                    <input type="checkbox" id="setup_dns" name="setup_dns" checked>
                    <label for="setup_dns">Automatically configure DNS records</label>
                </div>

                <div class="form-field checkbox">
                    <input type="checkbox" id="send_welcome" name="send_welcome" checked>
                    <label for="send_welcome">Send welcome emails to domain administrators</label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Start Import</button>
                <button type="button" onclick="previewFile()" class="btn btn-secondary">Preview CSV</button>
            </div>
        </form>

        <div id="preview-container" class="preview-section" style="display: none;">
            <h3>File Preview</h3>
            <div id="preview-content" class="preview-table"></div>
        </div>
    </div>
</div>

<script>
function previewFile() {
    const file = document.getElementById('domains_file').files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const text = e.target.result;
            const lines = text.split('\n');
            let html = '<table><thead><tr><th>Organization</th><th>Name</th><th>Email</th><th>Domain</th></tr></thead><tbody>';
            
            lines.slice(1, 6).forEach(line => {
                const columns = line.split(',');
                html += '<tr>';
                columns.forEach(column => {
                    html += `<td>${column.trim()}</td>`;
                });
                html += '</tr>';
            });
            
            if (lines.length > 6) {
                html += '<tr><td colspan="4">... and ' + (lines.length - 6) + ' more rows</td></tr>';
            }
            
            html += '</tbody></table>';
            document.getElementById('preview-content').innerHTML = html;
            document.getElementById('preview-container').style.display = 'block';
        };
        reader.readAsText(file);
    }
}
</script>
```


```php
<?php
/**
 * DNS status and management template
 * @var array $domains List of domains with DNS status
 */
?>
<div class="dns-management">
    <header class="page-header">
        <h1>DNS Management</h1>
        <a href="?action=dashboard" class="btn btn-link">← Back to Dashboard</a>
    </header>

    <div class="dns-container">
        <div class="status-filters">
            <button class="filter-btn active" data-filter="all">All Domains</button>
            <button class="filter-btn" data-filter="issues">Needs Attention</button>
            <button class="filter-btn" data-filter="verified">Verified</button>
        </div>

        <div class="domains-table">
            <table>
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>CNAME</th>
                        <th>MX</th>
                        <th>SPF</th>
                        <th>DKIM</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($domains as $domain): ?>
                        <tr data-status="<?= $domain['all_verified'] ? 'verified' : 'issues' ?>">
                            <td>
                                <?= htmlspecialchars($domain['domain']) ?>
                                <?php if ($domain['is_primary']): ?>
                                    <span class="badge">Primary</span>
                                <?php endif; ?>
                            </td>
                            <?php foreach (['cname', 'mx', 'spf', 'dkim'] as $record): ?>
                                <td>
                                    <span class="status-icon <?= $domain[$record.'_verified'] ? 'verified' : 'pending' ?>">
                                        <?= $domain[$record.'_verified'] ? '✓' : '!' ?>
                                    </span>
                                </td>
                            <?php endforeach; ?>
                            <td class="actions">
                                <button onclick="checkDNS('<?= $domain['id'] ?>')" class="btn btn-small">
                                    Verify
                                </button>
                                <button onclick="showRecords('<?= $domain['id'] ?>')" class="btn btn-small">
                                    Show Records
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DNS Records Modal Template -->
<template id="dns-modal-template">
    <div class="modal dns-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>DNS Records</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="dns-record">
                    <h4>CNAME Record</h4>
                    <code class="record-cname"></code>
                </div>
                <div class="dns-record">
                    <h4>MX Record</h4>
                    <code class="record-mx"></code>
                </div>
                <div class="dns-record">
                    <h4>SPF Record</h4>
                    <code class="record-spf"></code>
                </div>
                <div class="dns-record">
                    <h4>DKIM Record</h4>
                    <code class="record-dkim"></code>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary copy-all">Copy All</button>
                <button class="btn btn-secondary close-modal">Close</button>
            </div>
        </div>
    </div>
</template>

<script>
async function checkDNS(domainId) {
    try {
        const response = await fetch(`?action=check_dns&domain_id=${domainId}`);
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert('DNS check failed: ' + data.message);
        }
    } catch (error) {
        alert('Error checking DNS: ' + error.message);
    }
}

async function showRecords(domainId) {
    try {
        const response = await fetch(`?action=get_dns_records&domain_id=${domainId}`);
        const data = await response.json();
        
        if (data.success) {
            const template = document.getElementById('dns-modal-template');
            const modal = template.content.cloneNode(true);
            
            // Fill in records
            modal.querySelector('.record-cname').textContent = data.records.cname;
            modal.querySelector('.record-mx').textContent = data.records.mx;
            modal.querySelector('.record-spf').textContent = data.records.spf;
            modal.querySelector('.record-dkim').textContent = data.records.dkim;
            
            // Add to document
            document.body.appendChild(modal);
            
            // Setup event listeners
            setupModalListeners();
        } else {
            alert('Error loading DNS records: ' + data.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function setupModalListeners() {
    const modal = document.querySelector('.dns-modal');
    
    modal.querySelector('.close-btn').onclick = () => modal.remove();
    modal.querySelector('.close-modal').onclick = () => modal.remove();
    
    modal.querySelector('.copy-all').onclick = () => {
        const records = [...modal.querySelectorAll('code')]
            .map(code => code.textContent)
            .join('\n\n');
        
        navigator.clipboard.writeText(records)
            .then(() => alert('Records copied to clipboard'))
            .catch(err => alert('Failed to copy records'));
    };
}

// Setup filter buttons
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.onclick = () => {
        // Update active state
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        // Filter rows
        const filter = btn.dataset.filter;
        document.querySelectorAll('tr[data-status]').forEach(row => {
            row.style.display = 
                filter === 'all' || row.dataset.status === filter ? '' : 'none';
        });
    };
});
</script>
```


```php
<?php
/**
 * Error page template
 * @var string $message Error message
 * @var array $context Additional error context (optional)
 */
?>
<div class="error-page">
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1>Oops! Something went wrong</h1>
        
        <div class="error-message">
            <?= htmlspecialchars($message) ?>
        </div>
        
        <?php if (!empty($context)): ?>
            <div class="error-details">
                <div class="error-context">
                    <?php foreach ($context as $key => $value): ?>
                        <div class="context-item">
                            <strong><?= htmlspecialchars($key) ?>:</strong>
                            <span><?= htmlspecialchars($value) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="error-actions">
            <a href="?action=dashboard" class="btn btn-primary">Return to Dashboard</a>
            <?php if (!empty($context)): ?>


                <button onclick="toggleDetails()" class="btn btn-secondary">
                    Show Technical Details
                </button>
            <?php endif; ?>
            
            <?php if (isset($canRetry) && $canRetry): ?>
                <button onclick="retryOperation()" class="btn btn-retry">
                    Retry Operation
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleDetails() {
    const details = document.querySelector('.error-details');
    details.style.display = details.style.display === 'none' ? 'block' : 'none';
}

function retryOperation() {
    window.location.reload();
}
</script>
```

```php
<?php
/**
 * Settings page template
 * @var array $config Current configuration
 * @var array $plans Available plans
 */
?>
<div class="settings-page">
    <header class="page-header">
        <h1>VenMail Settings</h1>
        <a href="?action=dashboard" class="btn btn-link">← Back to Dashboard</a>
    </header>

    <form method="post" action="?action=save_settings" class="settings-form">
        <div class="settings-section">
            <h2>General Settings</h2>
            
            <div class="form-group">
                <label for="addon_enabled">Enable VenMail Addon</label>
                <div class="toggle-switch">
                    <input type="checkbox" id="addon_enabled" name="enabled" 
                           <?= $config['enabled'] ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="default_plan">Default Plan</label>
                <select id="default_plan" name="default_plan_id">
                    <?php foreach ($plans as $plan): ?>
                        <option value="<?= $plan['id'] ?>" 
                            <?= $config['default_plan_id'] == $plan['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($plan['name']) ?> 
                            ($<?= number_format($plan['monthly_price'], 2) ?>/mo)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="pricing_mode">Default Billing Cycle</label>
                <select id="pricing_mode" name="pricing_mode">
                    <option value="monthly" <?= $config['pricing_mode'] == 'monthly' ? 'selected' : '' ?>>
                        Monthly
                    </option>
                    <option value="yearly" <?= $config['pricing_mode'] == 'yearly' ? 'selected' : '' ?>>
                        Yearly
                    </option>
                </select>
            </div>
        </div>

        <div class="settings-section">
            <h2>DNS Configuration</h2>
            
            <div class="form-group">
                <label for="auto_dns">Automatic DNS Setup</label>
                <div class="toggle-switch">
                    <input type="checkbox" id="auto_dns" name="auto_dns_setup" 
                           <?= $config['auto_dns_setup'] ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="dns_verification">DNS Verification Timer</label>
                <select id="dns_verification" name="dns_verify_interval">
                    <option value="300" <?= $config['dns_verify_interval'] == 300 ? 'selected' : '' ?>>5 minutes</option>
                    <option value="600" <?= $config['dns_verify_interval'] == 600 ? 'selected' : '' ?>>10 minutes</option>
                    <option value="1800" <?= $config['dns_verify_interval'] == 1800 ? 'selected' : '' ?>>30 minutes</option>
                    <option value="3600" <?= $config['dns_verify_interval'] == 3600 ? 'selected' : '' ?>>1 hour</option>
                </select>
            </div>
        </div>

        <div class="settings-section">
            <h2>Notification Settings</h2>
            
            <div class="form-group">
                <label for="notify_email">Notification Email</label>
                <input type="email" id="notify_email" name="notification_email" 
                       value="<?= htmlspecialchars($config['notification_email']) ?>">
            </div>

            <div class="form-group">
                <label>Notification Events</label>
                <div class="checkbox-group">
                    <?php
                    $events = [
                        'provision_success' => 'Successful Provisioning',
                        'provision_failure' => 'Failed Provisioning',
                        'dns_issues' => 'DNS Issues',
                        'billing_events' => 'Billing Events'
                    ];
                    
                    foreach ($events as $value => $label):
                        $checked = in_array($value, $config['notification_events']);
                    ?>
                        <label class="checkbox">
                            <input type="checkbox" name="notification_events[]" 
                                   value="<?= $value ?>" <?= $checked ? 'checked' : '' ?>>
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h2>Advanced Settings</h2>
            
            <div class="form-group">
                <label for="log_level">Log Level</label>
                <select id="log_level" name="log_level">
                    <?php
                    $levels = ['DEBUG', 'INFO', 'WARNING', 'ERROR'];
                    foreach ($levels as $level):
                        $selected = $config['log_level'] == $level;
                    ?>
                        <option value="<?= $level ?>" <?= $selected ? 'selected' : '' ?>>
                            <?= $level ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="cache_ttl">Cache Duration (seconds)</label>
                <input type="number" id="cache_ttl" name="cache_ttl" 
                       value="<?= $config['cache_ttl'] ?>" min="0" step="300">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Settings</button>
            <button type="reset" class="btn btn-secondary">Reset Changes</button>
        </div>
    </form>
</div>

<script>
// Form validation and handling
document.querySelector('.settings-form').onsubmit = function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('?action=save_settings', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Settings saved successfully', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message || 'Error saving settings', 'error');
        }
    })
    .catch(error => {
        showNotification('Error saving settings: ' + error.message, 'error');
    });
};

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}
</script>
```

```php
<?php
/**
 * Results template for bulk operations
 * @var array $results Operation results
 */
?>
<div class="results-page">
    <header class="page-header">
        <h1>Operation Results</h1>
        <a href="?action=dashboard" class="btn btn-link">← Back to Dashboard</a>
    </header>

    <div class="results-summary">
        <div class="summary-stat <?= $results['success_count'] > 0 ? 'success' : '' ?>">
            <span class="stat-label">Successful</span>
            <span class="stat-value"><?= $results['success_count'] ?></span>
        </div>
        
        <div class="summary-stat <?= $results['failed_count'] > 0 ? 'error' : '' ?>">
            <span class="stat-label">Failed</span>
            <span class="stat-value"><?= $results['failed_count'] ?></span>
        </div>
        
        <div class="summary-stat">
            <span class="stat-label">Total Time</span>
            <span class="stat-value"><?= $results['duration'] ?>s</span>
        </div>
    </div>

    <?php if (!empty($results['success'])): ?>
        <section class="results-section">
            <h2>Successful Operations</h2>
            <div class="results-table">
                <table>
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Status</th>
                            <th>Details</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['success'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['domain']) ?></td>
                                <td><span class="badge success">Success</span></td>
                                <td><?= htmlspecialchars($item['details']) ?></td>
                                <td>
                                    <button onclick="viewDetails('<?= $item['id'] ?>')" 
                                            class="btn btn-small">
                                        View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($results['failed'])): ?>
        <section class="results-section">
            <h2>Failed Operations</h2>
            <div class="results-table">
                <table>
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Error</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['failed'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['domain']) ?></td>
                                <td><?= htmlspecialchars($item['error']) ?></td>
                                <td>
                                    <button onclick="retryOperation('<?= $item['domain'] ?>')" 
                                            class="btn btn-small">
                                        Retry
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <div class="results-actions">
        <button onclick="downloadReport()" class="btn btn-primary">
            Download Report
        </button>
        <?php if (!empty($results['failed'])): ?>
            <button onclick="retryAll()" class="btn btn-secondary">
                Retry Failed
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
async function viewDetails(id) {
    try {
        const response = await fetch(`?action=get_details&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            showDetailsModal(data.details);
        } else {
            alert('Error loading details: ' + data.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function retryOperation(domain) {
    if (confirm(`Retry operation for ${domain}?`)) {
        try {
            const response = await fetch('?action=retry', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ domain })
            });
            
            const data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                alert('Retry failed: ' + data.message);
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }
}

function retryAll() {
    if (confirm('Retry all failed operations?')) {
        const domains = [...document.querySelectorAll('.results-table tr')]
            .map(row => row.querySelector('td:first-child').textContent)
            .filter(Boolean);
            
        Promise.all(domains.map(domain => retryOperation(domain)))
            .then(() => location.reload());
    }
}

function downloadReport() {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `operation-report-${timestamp}.csv`;
    
    let csv = 'Domain,Status,Details\n';
    
    // Add successful operations
    document.querySelectorAll('.success tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 3) {
            csv += `${cells[0].textContent},Success,${cells[2].textContent}\n`;
        }
    });
    
    // Add failed operations
    document.querySelectorAll('.failed tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 2) {
            csv += `${cells[0].textContent},Failed,${cells[1].textContent}\n`;
        }
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('href', url);
    a.setAttribute('download', filename);
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>
```