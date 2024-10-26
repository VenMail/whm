<?php
#!/usr/local/cpanel/3rdparty/bin/php
namespace VenMail\WHMPlugin;

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class VenMailCLI extends Application {
    private $logger;
    private $plugin;
    private $addon_manager;
    
    public function __construct() {
        parent::__construct('VenMail CLI', '1.0.0');
        
        $this->initializeLogger();
        $this->initializePlugin();
        $this->registerCommands();
    }
    
    private function initializeLogger() {
        $this->logger = new Logger('venmail_cli');
        $this->logger->pushHandler(new RotatingFileHandler(
            __DIR__ . '/logs/cli.log',
            7,
            Logger::DEBUG
        ));
    }
    
    private function initializePlugin() {
        try {
            $this->plugin = new VenMailPlugin();
            $this->addon_manager = new AddonManager($this->plugin);
        } catch (\Exception $e) {
            $this->logger->error('Initialization failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function registerCommands() {
        $this->add(new class extends Command {
            protected function configure() {
                $this->setName('setup')
                    ->setDescription('Setup email for domain')
                    ->addArgument('domain', InputArgument::REQUIRED, 'Domain name')
                    ->addOption('plan', 'p', InputOption::VALUE_REQUIRED, 'Plan ID')
                    ->addOption('no-dns', null, InputOption::VALUE_NONE, 'Skip DNS setup');
            }
            
            protected function execute(InputInterface $input, OutputInterface $output): int {
                $io = new SymfonyStyle($input, $output);
                $domain = $input->getArgument('domain');
                
                try {
                    $plugin = $this->getApplication()->getPlugin();
                    $result = $plugin->createDomain([
                        'domain' => $domain,
                        'plan_id' => $input->getOption('plan'),
                        'skip_dns' => $input->getOption('no-dns')
                    ]);
                    
                    if ($result['success']) {
                        $io->success("Domain {$domain} setup completed");
                        return Command::SUCCESS;
                    }
                    
                    $io->error($result['message'] ?? 'Setup failed');
                    return Command::FAILURE;
                    
                } catch (\Exception $e) {
                    $io->error($e->getMessage());
                    return Command::FAILURE;
                }
            }
        });
        
        $this->add(new class extends Command {
            protected function configure() {
                $this->setName('verify')
                    ->setDescription('Verify domain DNS setup')
                    ->addArgument('domain', InputArgument::REQUIRED, 'Domain name');
            }
            
            protected function execute(InputInterface $input, OutputInterface $output): int {
                $io = new SymfonyStyle($input, $output);
                $domain = $input->getArgument('domain');
                
                try {
                    $dns = new DNSManager($this->getApplication()->getLogger());
                    $result = $dns->verifyRecords($domain, []);
                    
                    $io->table(['Record Type', 'Status'], array_map(
                        fn($type, $status) => [$type, $status ? '✓' : '✗'],
                        array_keys($result),
                        array_values($result)
                    ));
                    
                    return in_array(false, $result) ? Command::FAILURE : Command::SUCCESS;
                    
                } catch (\Exception $e) {
                    $io->error($e->getMessage());
                    return Command::FAILURE;
                }
            }
        });
        
        // Add more commands...
        $this->registerBulkCommand();
        $this->registerSyncCommand();
        $this->registerConfigCommand();
    }
    
    private function registerBulkCommand() {
        $this->add(new class extends Command {
            protected function configure() {
                $this->setName('bulk-import')
                    ->setDescription('Bulk import domains from CSV')
                    ->addArgument('file', InputArgument::REQUIRED, 'CSV file path')
                    ->addOption('plan', 'p', InputOption::VALUE_REQUIRED, 'Default plan ID')
                    ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate import');
            }
            
            protected function execute(InputInterface $input, OutputInterface $output): int {
                $io = new SymfonyStyle($input, $output);
                $file = $input->getArgument('file');
                
                if (!file_exists($file)) {
                    $io->error("File not found: {$file}");
                    return Command::FAILURE;
                }
                
                try {
                    $domains = array_map('str_getcsv', file($file));
                    $headers = array_shift($domains);
                    
                    $progressBar = $io->createProgressBar(count($domains));
                    $progressBar->start();
                    
                    $results = ['success' => [], 'failed' => []];
                    
                    foreach ($domains as $row) {
                        $domain = array_combine($headers, $row);
                        
                        try {
                            if (!$input->getOption('dry-run')) {
                                $result = $this->getApplication()->getPlugin()->createDomain([
                                    'domain' => $domain['domain'],
                                    'plan_id' => $domain['plan_id'] ?? $input->getOption('plan'),
                                    'email' => $domain['email'] ?? "admin@{$domain['domain']}"
                                ]);
                                
                                if ($result['success']) {
                                    $results['success'][] = $domain['domain'];
                                } else {
                                    $results['failed'][] = [
                                        'domain' => $domain['domain'],
                                        'error' => $result['message']
                                    ];
                                }
                            }
                        } catch (\Exception $e) {
                            $results['failed'][] = [
                                'domain' => $domain['domain'],
                                'error' => $e->getMessage()
                            ];
                        }
                        
                        $progressBar->advance();
                    }
                    
                    $progressBar->finish();
                    $io->newLine(2);
                    
                    $io->success(sprintf(
                        'Processed %d domains: %d successful, %d failed',
                        count($domains),
                        count($results['success']),
                        count($results['failed'])
                    ));
                    
                    if (!empty($results['failed'])) {
                        $io->section('Failed Domains');
                        $io->table(
                            ['Domain', 'Error'],
                            array_map(fn($f) => [$f['domain'], $f['error']], $results['failed'])
                        );
                    }
                    
                    return empty($results['failed']) ? Command::SUCCESS : Command::FAILURE;
                    
                } catch (\Exception $e) {
                    $io->error($e->getMessage());
                    return Command::FAILURE;
                }
            }
        });
    }
    
    // Getter methods for commands
    public function getPlugin(): VenMailPlugin {
        return $this->plugin;
    }
    
    public function getLogger(): Logger {
        return $this->logger;
    }
}

// Run the application
if (php_sapi_name() === 'cli') {
    $cli = new VenMailCLI();
    $cli->run();
}