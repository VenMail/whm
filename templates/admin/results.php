<?php
// templates/admin/results.php
?>
<div class="results-page">
    <div class="page-header">
        <h2>Operation Results</h2>
        <a href="?action=dashboard" class="btn btn-link">
            <i class="icon icon-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="results-summary">
        <div class="summary-stats">
            <div class="stat-card <?= $results['success_count'] > 0 ? 'success' : '' ?>">
                <div class="stat-icon">
                    <i class="icon icon-check-circle"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Successful</span>
                    <span class="stat-value"><?= $results['success_count'] ?></span>
                </div>
            </div>
            
            <div class="stat-card <?= $results['failed_count'] > 0 ? 'error' : '' ?>">
                <div class="stat-icon">
                    <i class="icon icon-x-circle"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Failed</span>
                    <span class="stat-value"><?= $results['failed_count'] ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="icon icon-clock"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Total Time</span>
                    <span class="stat-value"><?= $results['duration'] ?>s</span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($results['success'])): ?>
        <section class="results-section">
            <h3>Successful Operations</h3>
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
                                <td>
                                    <span class="badge success">Success</span>
                                </td>
                                <td><?= htmlspecialchars($item['details']) ?></td>
                                <td>
                                    <button onclick="viewDetails('<?= $item['id'] ?>')" 
                                            class="btn btn-icon"
                                            title="View Details">
                                        <i class="icon icon-eye"></i>
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
            <h3>Failed Operations</h3>
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
                                <td class="error-message">
                                    <?= htmlspecialchars($item['error']) ?>
                                </td>
                                <td>
                                    <button onclick="retryOperation('<?= $item['domain'] ?>')"
                                            class="btn btn-icon"
                                            title="Retry">
                                        <i class="icon icon-refresh-cw"></i>
                                    </button>
                                    <button onclick="viewError('<?= $item['domain'] ?>')"
                                            class="btn btn-icon"
                                            title="View Error Details">
                                        <i class="icon icon-alert-circle"></i>
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
            <i class="icon icon-download"></i> Download Report
        </button>
        <?php if (!empty($results['failed'])): ?>
            <button onclick="retryAll()" class="btn btn-secondary">
                <i class="icon icon-refresh-cw"></i> Retry Failed
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
            showError('Error loading details: ' + data.message);
        }
    } catch (error) {
        showError('Error: ' + error.message);
    }
}

async function retryOperation(domain) {
    if (!confirm(`Retry operation for ${domain}?`)) return;
    
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
            showError('Retry failed: ' + data.message);
        }
    } catch (error) {
        showError('Error: ' + error.message);
    }
}

function retryAll() {
    if (!confirm('Retry all failed operations?')) return;
    
    const domains = [...document.querySelectorAll('.results-table tr')]
        .map(row => row.querySelector('td:first-child')?.textContent)
        .filter(Boolean);
        
    Promise.all(domains.map(domain => retryOperation(domain)))
        .then(() => location.reload());
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
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

function showError(message) {
    const notification = document.createElement('div');
    notification.className = 'notification error';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

function showDetailsModal(details) {
    const modal = document.createElement('div');
    modal.className = 'modal details-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Operation Details</h3>
                <button onclick="this.closest('.modal').remove()" class="close-btn">×</button>
            </div>
            <div class="modal-body">
                <div class="details-grid">
                    ${Object.entries(details).map(([key, value]) => `
                        <div class="detail-item">
                            <strong>${key}:</strong>
                            <span>${value}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="this.closest('.modal').remove()" class="btn btn-secondary">
                    Close
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}
</script>
