<?php
/**
 * Agento Core Module
 * Cache Clear Command
 */

namespace Agento\Core\Console\Command;

use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClearCommand extends Command
{
    /**
     * @var ObjectManagerFactory
     */
    private $objectManagerFactory;

    /**
     * @param ObjectManagerFactory $objectManagerFactory
     */
    public function __construct(ObjectManagerFactory $objectManagerFactory)
    {
        $this->objectManagerFactory = $objectManagerFactory;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('agento:cache:clear')
            ->setDescription('Clear Magento cache')
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                'Cache type to clear (leave empty to clear all)'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // Object manager will be initialized in execute
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omParams = $_SERVER;
        $objectManager = $this->objectManagerFactory->create($omParams);
        
        $cacheTypeList = $objectManager->get(TypeListInterface::class);
        $cacheManager = $objectManager->get(Manager::class);
        
        $cacheType = $input->getOption('type');
        
        try {
            if ($cacheType) {
                // Clear specific cache type
                $cacheTypeList->cleanType($cacheType);
                $output->writeln("<info>Cleared cache type: {$cacheType}</info>");
            } else {
                // Clear all cache types
                $types = $cacheTypeList->getTypes();
                $cacheTypes = array_keys($types);
                $cacheManager->clean($cacheTypes);
                
                foreach ($types as $type => $label) {
                    $output->writeln("<info>Cleared: {$label} ({$type})</info>");
                }
                
                $output->writeln('<info>All cache cleared successfully</info>');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error clearing cache: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}

