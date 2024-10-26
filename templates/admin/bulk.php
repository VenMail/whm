<?php
// templates/admin/bulk.php
?>
<div class="bulk-import">
    <div class="page-header">
        <h2>Bulk Domain Import</h2>
        <a href="?action=dashboard" class="btn btn-link">
            <i class="icon icon-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="import-container">
        <form method="post" 
              action="?action=bulk_process" 
              enctype="multipart/form-data" 
              class="import-form"
              id="bulk-import-form">
            
            <div class="form-section">
                <h3>Upload Domains</h3>
                <div class="form-field">
                    <label for="domains_file">CSV File</label>
                    <div class="file-upload">
                        <input type="file" 
                               id="domains_file" 
                               name="domains_file" 
                               accept=".csv" 
                               required
                               onchange="handleFileSelect(this)">
                        <div class="upload-placeholder">
                            <i class="icon icon-upload"></i>
                            <span>Choose CSV file or drag it here</span>
                        </div>
                    </div>
                    <div class="field-help">
                        <p>Required columns: Organization, Full Name, Email, Domain</p>
                        <p><a href="#" onclick="downloadTemplate()">Download template file</a></p>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Plan Selection</h3>
                <div class="form-field">
                    <label for="plan_id">Default Email Plan</label>
                    <select id="plan_id" name="plan_id" required>
                        <option value="">Select a plan...</option>
                        <?php foreach ($plans as $plan): ?>
                            <option value="<?= $plan['id'] ?>" 
                                    data-price="<?= $plan['monthly_price'] ?>"
                                    <?= $plan['id'] == $default_plan_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($plan['name']) ?> 
                                ($<?= number_format($plan['monthly_price'], 2) ?>/mo)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="field-help">
                        This plan will be used if not specified in CSV
                    </div>
                </div>

                <div class="form-field">
                    <label for="subscription_mode">Default Billing Cycle</label>
                    <select id="subscription_mode" name="subscription_mode">
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly (Save 20%)</option>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h3>Options</h3>
                <div class="form-field checkbox">
                    <label>
                        <input type="checkbox" id="setup_dns" name="setup_dns" checked>
                        Automatically configure DNS records
                    </label>
                </div>

                <div class="form-field checkbox">
                    <label>
                        <input type="checkbox" id="send_welcome" name="send_welcome" checked>
                        Send welcome emails to domain administrators
                    </label>
                </div>

                <div class="form-field checkbox">
                    <label>
                        <input type="checkbox" id="skip_verification" name="skip_verification">
                        Skip domain ownership verification
                    </label>
                </div>
            </div>

            <div id="preview-container" class="preview-section" style="display: none;">
                <h3>File Preview</h3>
                <div class="preview-stats">
                    <span id="total-domains">0 domains</span>
                    <span id="estimated-cost">Estimated cost: $0/month</span>
                </div>
                <div id="preview-content" class="preview-table"></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="icon icon-upload"></i> Start Import
                </button>
                <button type="button" onclick="previewFile()" class="btn btn-secondary">
                    <i class="icon icon-eye"></i> Preview CSV
                </button>
            </div>
        </form>
    </div>
</div>

<script>
async function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        await previewFile(file);
        updateEstimatedCost();
    }
}

async function previewFile(file = null) {
    const fileInput = document.getElementById('domains_file');
    file = file || fileInput.files[0];
    
    if (!file) return;
    
    try {
        const reader = new FileReader();
        reader.onload = function(e) {
            const text = e.target.result;
            const lines = text.split('\n');
            let html = '<table><thead><tr>';
            
            // Headers
            const headers = lines[0].split(',');
            headers.forEach(header => {
                html += `<th>${header.trim()}</th>`;
            });
            html += '</tr></thead><tbody>';
            
            // Preview first 5 rows
            const previewRows = lines.slice(1, 6);
            previewRows.forEach(line => {
                html += '<tr>';
                line.split(',').forEach(cell => {
                    html += `<td>${cell.trim()}</td>`;
                });
                html += '</tr>';
            });
            
            // Show total count if more rows exist
            if (lines.length > 6) {
                html += `<tr><td colspan="${headers.length}">
                    ... and ${lines.length - 6} more rows
                </td></tr>`;
            }
            
            html += '</tbody></table>';
            
            document.getElementById('preview-content').innerHTML = html;
            document.getElementById('preview-container').style.display = 'block';
            document.getElementById('total-domains').textContent = 
                `${lines.length - 1} domains`;
        };
        reader.readAsText(file);
    } catch (error) {
        showError('Error reading file: ' + error.message);
    }
}

function updateEstimatedCost() {
    const planSelect = document.getElementById('plan_id');
    const totalDomains = parseInt(
        document.getElementById('total-domains').textContent
    );
    const pricePerDomain = parseFloat(
        planSelect.options[planSelect.selectedIndex].dataset.price
    );
    
    const monthlyTotal = (totalDomains * pricePerDomain).toFixed(2);
    document.getElementById('estimated-cost').textContent = 
        `Estimated cost: $${monthlyTotal}/month`;
}

function downloadTemplate() {
    const template = 'Organization,Full Name,Email,Domain,Plan ID\n' +
                    'Example Inc,John Doe,admin@example.com,example.com,1';
    
    const blob = new Blob([template], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('href', url);
    a.setAttribute('download', 'domain-import-template.csv');
    a.click();
    window.URL.revokeObjectURL(url);
}

// Form submission handling
document.getElementById('bulk-import-form').onsubmit = async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    
    try {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="icon icon-loader"></i> Processing...';
        
        const response = await fetch('?action=bulk_process', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = `?action=bulk_results&batch_id=${result.batch_id}`;
        } else {
            showError(result.message || 'Import failed');
        }
    } catch (error) {
        showError('Error processing import: ' + error.message);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="icon icon-upload"></i> Start Import';
    }
};

function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.textContent = message;
    
    const form = document.querySelector('.import-form');
    form.insertBefore(alert, form.firstChild);
    
    setTimeout(() => alert.remove(), 5000);
}
</script>