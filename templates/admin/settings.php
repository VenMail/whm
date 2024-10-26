<?php
// templates/admin/settings.php
?>
<div class="settings-page">
    <div class="page-header">
        <h2>VenMail Settings</h2>
        <a href="?action=dashboard" class="btn btn-link">
            <i class="icon icon-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <form method="post" action="?action=save_settings" class="settings-form">
        <div class="settings-grid">
            <div class="settings-section">
                <h3>General Settings</h3>
                
                <div class="form-field">
                    <label for="addon_enabled">Enable VenMail Addon</label>
                    <div class="toggle-switch">
                        <input type="checkbox" 
                               id="addon_enabled" 
                               name="enabled" 
                               <?= $config['enabled'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </div>
                </div>

                <div class="form-field">
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

                <div class="form-field">
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
                <h3>DNS Configuration</h3>
                
                <div class="form-field">
                    <label for="auto_dns">Automatic DNS Setup</label>
                    <div class="toggle-switch">
                        <input type="checkbox" 
                               id="auto_dns" 
                               name="auto_dns_setup"
                               <?= $config['auto_dns_setup'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </div>
                </div>

                <div class="form-field">
                    <label for="dns_verification">DNS Verification Timer</label>
                    <select id="dns_verification" name="dns_verify_interval">
                        <option value="300" <?= $config['dns_verify_interval'] == 300 ? 'selected' : '' ?>>
                            5 minutes
                        </option>
                        <option value="600" <?= $config['dns_verify_interval'] == 600 ? 'selected' : '' ?>>
                            10 minutes
                        </option>
                        <option value="1800" <?= $config['dns_verify_interval'] == 1800 ? 'selected' : '' ?>>
                            30 minutes
                        </option>
                        <option value="3600" <?= $config['dns_verify_interval'] == 3600 ? 'selected' : '' ?>>
                            1 hour
                        </option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="dns_retries">DNS Setup Retries</label>
                    <input type="number" 
                           id="dns_retries" 
                           name="dns_setup_retries" 
                           value="<?= $config['dns_setup_retries'] ?>"
                           min="1" 
                           max="10">
                </div>
            </div>

            <div class="settings-section">
                <h3>Notification Settings</h3>
                
                <div class="form-field">
                    <label for="notify_email">Notification Email</label>
                    <input type="email" 
                           id="notify_email" 
                           name="notification_email"
                           value="<?= htmlspecialchars($config['notification_email']) ?>">
                </div>

                <div class="form-field">
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
                                <input type="checkbox" 
                                       name="notification_events[]"
                                       value="<?= $value ?>" 
                                       <?= $checked ? 'checked' : '' ?>>
                                <?= $label ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h3>Advanced Settings</h3>
                
                <div class="form-field">
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

                <div class="form-field">
                    <label for="cache_ttl">Cache Duration (seconds)</label>
                    <input type="number" 
                           id="cache_ttl" 
                           name="cache_ttl"
                           value="<?= $config['cache_ttl'] ?>" 
                           min="0" 
                           step="300">
                </div>

                <div class="form-field">
                    <label for="queue_timeout">Queue Timeout (seconds)</label>
                    <input type="number" 
                           id="queue_timeout" 
                           name="queue_timeout"
                           value="<?= $config['queue_timeout'] ?>" 
                           min="300" 
                           max="7200">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Settings</button>
            <button type="reset" class="btn btn-secondary">Reset Changes</button>
        </div>
    </form>
</div>

<script>
document.querySelector('.settings-form').onsubmit = async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    
    try {
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';
        
        const response = await fetch('?action=save_settings', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Settings saved successfully', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(result.message || 'Error saving settings', 'error');
        }
    } catch (error) {
        showNotification('Error: ' + error.message, 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Save Settings';
    }
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

// Form validation
document.querySelectorAll('.settings-form input, .settings-form select').forEach(input => {
    input.addEventListener('change', function() {
        validateField(this);
    });
});

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    switch(field.id) {
        case 'notify_email':
            isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            errorMessage = 'Please enter a valid email address';
            break;
            
        case 'cache_ttl':
            isValid = value >= 0 && value <= 86400;
            errorMessage = 'Cache duration must be between 0 and 86400 seconds';
            break;
            
        case 'queue_timeout':
            isValid = value >= 300 && value <= 7200;
            errorMessage = 'Queue timeout must be between 300 and 7200 seconds';
            break;
            
        case 'dns_retries':
            isValid = value >= 1 && value <= 10;
            errorMessage = 'DNS retries must be between 1 and 10';
            break;
    }
    
    const errorElement = field.parentElement.querySelector('.field-error');
    if (!isValid) {
        if (!errorElement) {
            const error = document.createElement('div');
            error.className = 'field-error';
            error.textContent = errorMessage;
            field.parentElement.appendChild(error);
        }
        field.classList.add('invalid');
    } else {
        if (errorElement) {
            errorElement.remove();
        }
        field.classList.remove('invalid');
    }
    
    return isValid;
}

// Form reset handling
document.querySelector('button[type="reset"]').onclick = function(e) {
    e.preventDefault();
    
    if (confirm('Are you sure you want to reset all changes?')) {
        document.querySelector('.settings-form').reset();
        document.querySelectorAll('.field-error').forEach(error => error.remove());
        document.querySelectorAll('.invalid').forEach(field => field.classList.remove('invalid'));
    }
};
</script>
