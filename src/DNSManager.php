<?php
namespace VenMail\WHMPlugin;

use Cpanel;
use Exception;

class DNSManager {
    private $cpanel;
    private $logger;
    
    public function __construct(\Monolog\Logger $logger = null) {
        if (!class_exists('Cpanel')) {
            require_once '/usr/local/cpanel/php/cpanel.php';
        }
        
        $this->cpanel = new Cpanel();
        $this->logger = $logger ?: new \Monolog\Logger('dns_manager');
    }
    
    public function setupRecords($domain, $data) {
        $this->logger->info('Setting up DNS records', ['domain' => $domain]);
        
        try {
            // Add CNAME record
            $this->addRecord($domain, [
                'name' => 'mail',
                'type' => 'CNAME',
                'cname' => $data['dns_cname'],
                'ttl' => 14400
            ]);
            
            // Add MX record
            $this->addRecord($domain, [
                'name' => '@',
                'type' => 'MX',
                'exchange' => $data['mx_record'],
                'preference' => 10,
                'ttl' => 14400
            ]);
            
            // Add SPF record
            $this->addRecord($domain, [
                'name' => '@',
                'type' => 'TXT',
                'txtdata' => $data['spf_record'],
                'ttl' => 14400
            ]);
            
            // Add DKIM record
            $this->addRecord($domain, [
                'name' => "{$data['dkim_selector']}._domainkey",
                'type' => 'TXT',
                'txtdata' => $data['dkim_record'],
                'ttl' => 14400
            ]);
            
            $this->logger->info('DNS records setup completed', ['domain' => $domain]);
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('DNS setup failed', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function addRecord($domain, $record) {
        $result = $this->cpanel->api2('ZoneEdit', 'add_zone_record', array_merge(
            ['domain' => $domain],
            $record
        ));
        
        if ($result->status != 1) {
            throw new Exception("Failed to add {$record['type']} record: " . 
                ($result->errors[0] ?? 'Unknown error'));
        }
        
        return true;
    }
    
    public function verifyRecords($domain, $data) {
        $this->logger->info('Verifying DNS records', ['domain' => $domain]);
        
        $results = [
            'cname' => $this->verifyRecord($domain, DNS_CNAME, $data['dns_cname']),
            'mx' => $this->verifyRecord($domain, DNS_MX, $data['mx_record']),
            'spf' => $this->verifySPFRecord($domain, $data['spf_record']),
            'dkim' => $this->verifyDKIMRecord($domain, $data)
        ];
        
        $this->logger->info('DNS verification results', [
            'domain' => $domain,
            'results' => $results
        ]);
        
        return $results;
    }
    
    private function verifyRecord($domain, $type, $expected) {
        try {
            $records = dns_get_record($domain, $type);
            
            foreach ($records as $record) {
                if (isset($record['target']) && $record['target'] === $expected) {
                    return true;
                }
                if (isset($record['txt']) && $record['txt'] === $expected) {
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            $this->logger->error('DNS verification failed', [
                'domain' => $domain,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    private function verifySPFRecord($domain, $expected) {
        try {
            $records = dns_get_record($domain, DNS_TXT);
            
            foreach ($records as $record) {
                if (isset($record['txt']) && 
                    strpos($record['txt'], 'v=spf1') === 0 &&
                    $this->compareSPF($record['txt'], $expected)) {
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            $this->logger->error('SPF verification failed', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    private function verifyDKIMRecord($domain, $data) {
        $selector = $data['dkim_selector'];
        $dkimDomain = "{$selector}._domainkey.{$domain}";
        
        try {
            $records = dns_get_record($dkimDomain, DNS_TXT);
            
            foreach ($records as $record) {
                if (isset($record['txt']) && 
                    strpos($record['txt'], 'v=DKIM1') === 0 &&
                    $record['txt'] === $data['dkim_record']) {
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            $this->logger->error('DKIM verification failed', [
                'domain' => $domain,
                'selector' => $selector,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    private function compareSPF($actual, $expected) {
        // Normalize SPF records for comparison
        $normalizeSpf = function($spf) {
            $parts = explode(' ', $spf);
            sort($parts);
            return implode(' ', $parts);
        };
        
        return $normalizeSpf($actual) === $normalizeSpf($expected);
    }
}