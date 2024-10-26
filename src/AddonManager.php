<?php
namespace VenMail\WHMPlugin;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Cpanel;

class AddonManager {
    private $plugin;
    private $dns;
    private $config;
    private $logger;
    private $filesystem;
    
    public function __construct(VenMailPlugin $plugin) {
        $this->plugin = $plugin;
        $this->dns = new DNSManager();
        $this->logger = new \Monolog\Logger('addon_manager');
        $this->filesystem = new Filesystem();
        $this->loadConfig();
    }
    
    private function loadConfig() {
        $configFile = WHM_ROOT . '/addons/venmail/config/addon_settings.conf';
        
        if (!file_exists($configFile)) {
            throw new Exception('Addon configuration file not found');
        }
        
        $this->config = parse_ini_file($configFile);
        
        if ($this->config === false) {
            throw new Exception('Failed to parse addon configuration');
        }
    }
    
    public function isEnabled() {
        return ($this->config['enabled'] ?? '') === 'true';
    }
    
    public function onDomainPurchase($params) {
        if (!$this->isEnabled()) {
            $this->logger->info('Addon is disabled, skipping domain setup');
            return true;
        }
        
        $this->logger->info('Processing domain purchase', ['domain' => $params['domain']]);
        
        try {
            // Validate domain
            if (!$this->validateDomain($params['domain'])) {
                throw new Exception('Invalid domain name');
            }
            
            // Create domain in VenMail
            $result = $this->plugin->createDomain([
                'organization' => $params['organization'] ?? $params['username'],
                'fullName' => $params['fullname'],
                'email' => $params['email'],
                'domain' => $params['domain'],
                'plan_id' => $params['plan_id'] ?? $this->config['default_plan_id']
            ]);
            
            if ($result['success']) {
                // Setup DNS records if enabled
                if ($this->config['auto_dns_setup'] === 'true') {
                    $this->dns->setupRecords($params['domain'], $result['data']);
                }
                
                // Store domain association
                $this->storeDomainAssociation($params['domain'], $result['data']['id']);
                
                $this->logger->info('Domain setup completed', [
                    'domain' => $params['domain'],
                    'venmail_id' => $result['data']['id']
                ]);
                
                return true;
            }
            
            throw new Exception('Failed to create domain in VenMail: ' . 
                ($result['message'] ?? 'Unknown error'));
            
        } catch (Exception $e) {
            $this->logger->error('Domain setup failed', [
                'domain' => $params['domain'],
                'error' => $e->getMessage()
            ]);
            
            if ($this->config['support_ticket_on_failure'] === 'true') {
                $this->createSupportTicket($params['domain'], $e->getMessage());
            }
            return false;
        }
    }

    private function createSupportTicket($domain, $error) {
        try {
            $cpanel = new Cpanel();
            $ticket_data = [
                'subject' => "VenMail Setup Failed: {$domain}",
                'message' => "Error setting up VenMail for domain {$domain}:\n\n{$error}",
                'department' => $this->config['support_department'] ?? 'Hosting Support',
                'priority' => $this->config['support_priority'] ?? 'Medium'
            ];
            
            $cpanel->api2('Support', 'create_ticket', $ticket_data);
            
            $this->logger->info('Support ticket created', [
                'domain' => $domain,
                'error' => $error
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to create support ticket', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function validateDomain($domain) {
        return (
            // Check basic domain format
            preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $domain) &&
            // Ensure domain exists in WHM
            $this->domainExistsInWHM($domain)
        );
    }
    
    private function domainExistsInWHM($domain) {
        try {
            $cpanel = new Cpanel();
            $result = $cpanel->api2('DomainInfo', 'domains_data', [
                'domain' => $domain
            ]);
            
            return !empty($result->data) && isset($result->data[0]->domain);
        } catch (Exception $e) {
            $this->logger->error('Domain validation failed', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    private function storeDomainAssociation($domain, $venmail_id) {
        $data_dir = WHM_ROOT . '/addons/venmail/data';
        $associations_file = $data_dir . '/domain_associations.json';
        
        try {
            // Ensure data directory exists
            if (!is_dir($data_dir)) {
                $this->filesystem->mkdir($data_dir, 0755);
            }
            
            // Load existing associations
            $associations = [];
            if (file_exists($associations_file)) {
                $content = file_get_contents($associations_file);
                if ($content) {
                    $associations = json_decode($content, true) ?: [];
                }
            }
            
            // Add new association
            $associations[$domain] = [
                'venmail_id' => $venmail_id,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'active'
            ];
            
            // Save updated associations
            $this->filesystem->dumpFile(
                $associations_file,
                json_encode($associations, JSON_PRETTY_PRINT)
            );
            
            $this->logger->info('Domain association stored', [
                'domain' => $domain,
                'venmail_id' => $venmail_id
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Failed to store domain association', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function getDomainAssociation($domain) {
        $associations_file = WHM_ROOT . '/addons/venmail/data/domain_associations.json';
        
        try {
            if (!file_exists($associations_file)) {
                return null;
            }
            
            $associations = json_decode(file_get_contents($associations_file), true);
            return $associations[$domain] ?? null;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to get domain association', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    public function removeDomainAssociation($domain) {
        $associations_file = WHM_ROOT . '/addons/venmail/data/domain_associations.json';
        
        try {
            if (!file_exists($associations_file)) {
                return true;
            }
            
            $associations = json_decode(file_get_contents($associations_file), true);
            
            if (isset($associations[$domain])) {
                unset($associations[$domain]);
                
                $this->filesystem->dumpFile(
                    $associations_file,
                    json_encode($associations, JSON_PRETTY_PRINT)
                );
                
                $this->logger->info('Domain association removed', [
                    'domain' => $domain
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to remove domain association', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    public function updateConfig($newConfig) {
        try {
            // Validate config
            $this->validateConfig($newConfig);
            
            // Merge with existing config
            $this->config = array_merge($this->config, $newConfig);
            
            // Write to file
            $configContent = '';
            foreach ($this->config as $key => $value) {
                $configContent .= "{$key} = \"{$value}\"\n";
            }
            
            $configFile = WHM_ROOT . '/addons/venmail/config/addon_settings.conf';
            $this->filesystem->dumpFile($configFile, $configContent);
            
            $this->logger->info('Configuration updated');
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to update configuration', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function validateConfig($config) {
        $required = ['enabled', 'default_plan_id', 'pricing_mode'];
        $missing = array_diff($required, array_keys($config));
        
        if (!empty($missing)) {
            throw new Exception('Missing required configuration keys: ' . 
                implode(', ', $missing));
        }
        
        if (isset($config['pricing_mode']) && 
            !in_array($config['pricing_mode'], ['monthly', 'yearly'])) {
            throw new Exception('Invalid pricing_mode value');
        }
    }
}