<?php
// templates/admin/error.php
?>
<div class="error-page">
    <div class="error-container">
        <div class="error-icon">
            <i class="icon icon-alert-triangle"></i>
        </div>
        
        <h1>Oops! Something went wrong</h1>
        
        <div class="error-message">
            <?= htmlspecialchars($message) ?>
        </div>
        
        <?php if (!empty($context)): ?>
            <div class="error-details" style="display: none;">
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
            <a href="?action=dashboard" class="btn btn-primary">
                <i class="icon icon-home"></i> Return to Dashboard
            </a>
            
            <?php if (!empty($context)): ?>
                <button onclick="toggleDetails()" class="btn btn-secondary">
                    <i class="icon icon-code"></i> Show Technical Details
                </button>
            <?php endif; ?>
            
            <?php if (isset($canRetry) && $canRetry): ?>
                <button onclick="retryOperation()" class="btn btn-retry">
                    <i class="icon icon-refresh-cw"></i> Retry Operation
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleDetails() {
    const details = document.querySelector('.error-details');
    const button = document.querySelector('.btn-secondary');
    
    if (details.style.display === 'none') {
        details.style.display = 'block';
        button.innerHTML = '<i class="icon icon-code"></i> Hide Technical Details';
    } else {
        details.style.display = 'none';
        button.innerHTML = '<i class="icon icon-code"></i> Show Technical Details';
    }
}

function retryOperation() {
    window.location.reload();
}
</script>
