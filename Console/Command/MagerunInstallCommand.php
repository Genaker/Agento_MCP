<?php
/**
 * Agento Core Module
 * Install n98-magerun2 Command
 */

namespace Agento\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class MagerunInstallCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('agento:magerun:install')
            ->setDescription('Install n98-magerun2 PHAR file');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $magentoRoot = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));
        $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
        
        if (file_exists($magerunPath)) {
            $output->writeln('<info>n98-magerun2.phar already exists at: ' . $magerunPath . '</info>');
            $output->writeln('<comment>Use --force to reinstall</comment>');
            return Command::SUCCESS;
        }
        
        $output->writeln('<comment>Downloading n98-magerun2.phar...</comment>');
        
        try {
            // Download using curl
            $downloadUrl = 'https://files.magerun.net/n98-magerun2.phar';
            $ch = curl_init($downloadUrl);
            $fp = fopen($magerunPath, 'wb');
            
            if (!$fp) {
                throw new \Exception('Cannot create file: ' . $magerunPath);
            }
            
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            fclose($fp);
            
            if (!$result || $httpCode !== 200) {
                if (file_exists($magerunPath)) {
                    unlink($magerunPath);
                }
                throw new \Exception('Download failed. HTTP Code: ' . $httpCode . ($error ? ' Error: ' . $error : ''));
            }
            
            // Make executable
            chmod($magerunPath, 0755);
            
            // Verify installation
            $process = new Process(['php', $magerunPath, '--version'], $magentoRoot);
            $process->setTimeout(30);
            $process->run();
            
            if ($process->getExitCode() !== 0) {
                throw new \Exception('Verification failed. Magerun may not be working correctly.');
            }
            
            $output->writeln('<info>✓ Successfully installed n98-magerun2.phar</info>');
            $output->writeln('<info>Location: ' . $magerunPath . '</info>');
            $output->writeln('<comment>Version: ' . trim($process->getOutput()) . '</comment>');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error installing n98-magerun2: ' . $e->getMessage() . '</error>');
            if (file_exists($magerunPath)) {
                unlink($magerunPath);
            }
            return Command::FAILURE;
        }
    }
}








