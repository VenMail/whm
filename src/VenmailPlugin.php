<?php
namespace VenMail\WHMPlugin;

use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class VenMailPlugin {
    private $client;
    private $logger;
    private $config;
    private $cache;
    
    // Required WHM plugin properties
    private static $instance = null;
    private $version = '1.0.0';
    private $displayName = 'VenMail Email Manager';
    private $description = 'Professional email service integration';
    private $routePath = 'venmail';
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->initializePlugin();
    }
    
    private function initializePlugin() {
        $this->loadConfig();
        $this->initLogger();
        $this->initCache();
        $this->initClient();
    }
    
    private function loadConfig() {
        $configFile = WHM_ROOT . '/addons/venmail/config/venmail.conf';
        if (!file_exists($configFile)) {
            throw new \RuntimeException('Configuration file not found: ' . $configFile);
        }
        $this->config = parse_ini_file($configFile);
    }
    
    private function initLogger() {
        $this->logger = new Logger('venmail');
        $this->logger->pushHandler(new RotatingFileHandler(
            WHM_ROOT . '/addons/venmail/logs/venmail.log',
            7,
            Logger::INFO
        ));
    }
    
    private function initCache() {
        $this->cache = new FilesystemAdapter(
            'venmail',
            3600,
            WHM_ROOT . '/addons/venmail/cache'
        );
    }
    
    private function initClient() {
        $this->client = new Client([
            'base_uri' => $this->config['api_base_url'],
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['api_token'],
                'Content-Type' => 'application/json',
                'User-Agent' => 'VenMail-WHM-Plugin/' . $this->version
            ],
            'http_errors' => false,
            'verify' => !empty($this->config['verify_ssl'])
        ]);
    }
    
    // Required WHM plugin methods
    public function getVersion() {
        return $this->version;
    }
    
    public function getDisplayName() {
        return $this->displayName;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function getRoutePath() {
        return $this->routePath;
    }
    
    // Plugin functionality methods
    public function createDomain($params) {
        $this->validateDomainParams($params);
        
        $cacheKey = 'domain_create_' . md5(serialize($params));
        
        try {
            $response = $this->client->request('POST', '/partners/domains', [
                'json' => [
                    'organization' => $params['organization'],
                    'fullName' => $params['fullName'],
                    'email' => $params['email'],
                    'domain' => $params['domain'],
                    'plan_id' => $params['plan_id'],
                    'subscription_mode' => $params['subscription_mode'] ?? 'monthly'
                ]
            ]);
            
            $result = $this->handleResponse($response);
            
            if ($result['success']) {
                $this->logger->info('Domain created successfully', [
                    'domain' => $params['domain'],
                    'plan_id' => $params['plan_id']
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create domain', [
                'domain' => $params['domain'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function getPlans() {
        $cacheKey = 'venmail_plans';
        
        try {
            $cachedPlans = $this->cache->getItem($cacheKey);
            if ($cachedPlans->isHit()) {
                return $cachedPlans->get();
            }
            
            $response = $this->client->request('GET', '/partners/plans');
            $result = $this->handleResponse($response);
            
            if ($result['success']) {
                $cachedPlans->set($result);
                $cachedPlans->expiresAfter(3600); // Cache for 1 hour
                $this->cache->save($cachedPlans);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch plans', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function getDomainStatus($domainId) {
        try {
            $response = $this->client->request('GET', "/partners/domains/{$domainId}");
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get domain status', [
                'domain_id' => $domainId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function handleResponse($response) {
        $body = json_decode($response->getBody(), true);
        $statusCode = $response->getStatusCode();
        
        if ($statusCode >= 400) {
            $this->logger->error('API error response', [
                'status_code' => $statusCode,
                'body' => $body
            ]);
            
            throw new \RuntimeException(
                $body['message'] ?? 'API Error',
                $statusCode
            );
        }
        
        return $body;
    }
    
    private function validateDomainParams($params) {
        $required = ['organization', 'fullName', 'email', 'domain', 'plan_id'];
        $missing = array_diff($required, array_keys($params));
        
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing required fields: ' . implode(', ', $missing)
            );
        }
    }
}