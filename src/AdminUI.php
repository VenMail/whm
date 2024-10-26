<?php
namespace VenMail\WHMPlugin;

use Exception;

class AdminUI {
    private $plugin;
    private $addon;
    private $logger;
    private $template_dir;
    
    public function __construct(VenMailPlugin $plugin, AddonManager $addon) {
        $this->plugin = $plugin;
        $this->addon = $addon;
        $this->logger = new \Monolog\Logger('admin_ui');
        $this->template_dir = WHM_ROOT . '/addons/venmail/templates';
        
        // Ensure WHM header is loaded
        if (!class_exists('WHM')) {
            require_once '/usr/local/cpanel/php/WHM.php';
        }
    }
    
    public function render($action = 'dashboard') {
        try {
            // WHM header
            \WHM::header($this->plugin->getDisplayName(), 'VenMail');
            
            $output = match($action) {
                'dashboard' => $this->renderDashboard(),
                'plans' => $this->renderPlans(),
                'domains' => $this->renderDomains(),
                'bulk' => $this->renderBulkImport(),
                'dns' => $this->renderDNSManagement(),
                'settings' => $this->renderSettings(),
                default => throw new Exception("Invalid action: {$action}")
            };
            
            // WHM footer
            \WHM::footer();
            
            return $output;
        } catch (Exception $e) {
            $this->logger->error('Render failed', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            
            return $this->renderError($e->getMessage());
        }
    }
    
    private function renderDashboard() {
        try {
            $stats = $this->getStats();
            $activity = $this->getRecentActivity();
            
            return $this->template('admin', [
                'stats' => $stats,
                'activity' => $activity,
                'plugin_version' => $this->plugin->getVersion()
            ]);
        } catch (Exception $e) {
            $this->logger->error('Dashboard render failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function getStats() {
        try {
            $domains = $this->plugin->getAllDomains();
            $plans = $this->plugin->getPlans();
            
            $active_domains = count($domains['data'] ?? []);
            $pending_setup = $this->countPendingSetups($domains['data'] ?? []);
            
            // Calculate month-over-month growth
            $last_month = strtotime('-1 month');
            $domain_growth = array_reduce($domains['data'] ?? [], function($count, $domain) use ($last_month) {
                return $count + (strtotime($domain['created_at']) > $last_month ? 1 : 0);
            }, 0);
            
            $domain_growth_percentage = $active_domains > 0 
                ? round(($domain_growth / $active_domains) * 100, 1)
                : 0;
            
            return [
                'active_domains' => $active_domains,
                'pending_setup' => $pending_setup,
                'domain_growth' => $domain_growth_percentage,
                'total_revenue' => $this->calculateRevenue($domains['data'] ?? [], $plans['data'] ?? []),
                'revenue_growth' => $this->calculateRevenueGrowth($domains['data'] ?? []),
                'dns_issues' => $this->countDNSIssues($domains['data'] ?? [])
            ];
        } catch (Exception $e) {
            $this->logger->error('Stats calculation failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function getRecentActivity($limit = 10) {
        try {
            $log_file = WHM_ROOT . '/addons/venmail/logs/venmail.log';
            if (!file_exists($log_file)) {
                return [];
            }
            
            $activity = [];
            $lines = array_slice(file($log_file), -50);
            
            foreach ($lines as $line) {
                if (preg_match('/\[(.*?)\] (\w+)\.(\w+): (.*?)(?:\s+\{.*\})?$/', $line, $matches)) {
                    $activity[] = [
                        'timestamp' => $matches[1],
                        'formatted_time' => date('Y-m-d H:i:s', strtotime($matches[1])),
                        'level' => $matches[2],
                        'action' => $matches[3],
                        'details' => $matches[4]
                    ];
                }
                
                if (count($activity) >= $limit) {
                    break;
                }
            }
            
            return array_reverse($activity);
        } catch (Exception $e) {
            $this->logger->error('Activity log processing failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    private function template($name, $data = []) {
        if (!file_exists("{$this->template_dir}/{$name}.php")) {
            throw new Exception("Template not found: {$name}");
        }
        
        try {
            extract($data);
            ob_start();
            include "{$this->template_dir}/{$name}.php";
            return ob_get_clean();
        } catch (Exception $e) {
            $this->logger->error('Template render failed', [
                'template' => $name,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function renderError($message, $context = []) {
        return $this->template('error', [
            'message' => $message,
            'context' => $context
        ]);
    }
}