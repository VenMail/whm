<?php
// templates/admin/dns.php
?>
<div class="dns-manager">
    <div class="page-header">
        <h2>DNS Management</h2>
        <div class="page-actions">
            <button onclick="verifyAllDomains()" class="btn btn-secondary">
                <i class="icon icon-refresh-cw"></i> Verify All
            </button>
            <button onclick="exportDNSReport()" class="btn btn-secondary">
                <i class="icon icon-download"></i> Export Report
            </button>
        </div>
    </div>

    <div class="dns-toolbar">
        <div class="search-box">
            <i class="icon icon-search"></i>
            <input type="text" 
                   id="domain-search" 
                   placeholder="Search domains..."
                   oninput="filterDomains(this.value)">
        </div>

        <div class="filter-group">
            <button class="filter-btn active" data-filter="all">
                All Domains
            </button>
            <button class="filter-btn" data-filter="issues">
                Needs Attention
                <?php if ($stats['dns_issues'] > 0): ?>
                    <span class="badge"><?= $stats['dns_issues'] ?></span>
                <?php endif; ?>
            </button>
            <button class="filter-btn" data-filter="verified">
                Verified
            </button>
        </div>
    </div>

    <div class="domains-table">
        <table>
            <thead>
                <tr>
                    <th class="sortable" data-sort="domain">
                        Domain <i class="icon icon-chevron-down"></i>
                    </th>
                    <th>CNAME</th>
                    <th>MX</th>
                    <th>SPF</th>
                    <th>DKIM</th>
                    <th>Last Verified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($domains as $domain): ?>
                    <tr data-status="<?= $domain['all_verified'] ? 'verified' : 'issues' ?>"
                        data-domain="<?= htmlspecialchars($domain['domain']) ?>">
                        <td>
                            <?= htmlspecialchars($domain['domain']) ?>
                            <?php if ($domain['is_primary']): ?>
                                <span class="badge badge-primary">Primary</span>
                            <?php endif; ?>
                        </td>
                        <?php foreach (['cname', 'mx', 'spf', 'dkim'] as $record): ?>
                            <td>
                                <span class="status-icon <?= $domain[$record.'_verified'] ? 'verified' : 'pending' ?>"
                                      title="<?= $domain[$record.'_status'] ?? '' ?>">
                                    <?= $domain[$record.'_verified'] ? '✓' : '!' ?>
                                </span>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <?= $domain['last_verified'] ? 
                                date('Y-m-d H:i', strtotime($domain['last_verified'])) : 
                                'Never' ?>
                        </td>
                        <td class="actions">
                            <button onclick="verifyDNS('<?= $domain['id'] ?>')" 
                                    class="btn btn-icon"
                                    title="Verify DNS">
                                <i class="icon icon-refresh-cw"></i>
                            </button>
                            <button onclick="showRecords('<?= $domain['id'] ?>')"
                                    class="btn btn-icon"
                                    title="Show DNS Records">
                                <i class="icon icon-list"></i>
                            </button>
                            <button onclick="showDNSHistory('<?= $domain['id'] ?>')"
                                    class="btn btn-icon"
                                    title="View History">
                                <i class="icon icon-clock"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- DNS Records Modal Template -->
<template id="dns-modal-template">
    <div class="modal dns-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>DNS Records for <span class="domain-name"></span></h2>
                <button class="close-btn" onclick="closeModal(this)">&times;</button>
            </div>
            <div class="modal-body">
                <div class="dns-records">
                    <div class="dns-record">
                        <h4>CNAME Record</h4>
                        <div class="record-value">
                            <code class="record-cname"></code>
                            <button class="btn btn-small copy-btn" data-record="cname">
                                <i class="icon icon-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="dns-record">
                        <h4>MX Record</h4>
                        <div class="record-value">
                            <code class="record-mx"></code>
                            <button class="btn btn-small copy-btn" data-record="mx">
                                <i class="icon icon-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="dns-record">
                        <h4>SPF Record</h4>
                        <div class="record-value">
                            <code class="record-spf"></code>
                            <button class="btn btn-small copy-btn" data-record="spf">
                                <i class="icon icon-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="dns-record">
                        <h4>DKIM Record</h4>
                        <div class="record-value">
                            <code class="record-dkim"></code>
                            <button class="btn btn-small copy-btn" data-record="dkim">
                                <i class="icon icon-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary copy-all">
                    <i class="icon icon-copy"></i> Copy All
                </button>
                <button class="btn btn-secondary close-modal">Close</button>
            </div>
        </div>
    </div>
</template>