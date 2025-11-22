<?php

// Test all magerun commands with --help flag
$magerunCommands = [
    // Admin Commands
    'admin:notifications',
    'admin:token:create',
    'admin:user:activate',
    'admin:user:change-password',
    'admin:user:change-status',
    'admin:user:deactivate',
    'admin:user:delete',
    'admin:user:list',
    
    // Cache Commands
    'cache:catalog:image:flush',
    'cache:clean',
    'cache:disable',
    'cache:enable',
    'cache:flush',
    'cache:list',
    'cache:remove:id',
    'cache:report',
    'cache:view',
    
    // Composer Commands
    'composer:redeploy-base-packages',
    
    // Configuration Commands
    'config:data:acl',
    'config:data:di',
    'config:data:indexer',
    'config:data:mview',
    'config:env:create',
    'config:env:delete',
    'config:env:set',
    'config:env:show',
    'config:search',
    'config:store:delete',
    'config:store:get',
    'config:store:set',
    
    // Customer Commands
    'customer:add-address',
    'customer:change-password',
    'customer:create',
    'customer:delete',
    'customer:info',
    'customer:list',
    'customer:token:create',
    
    // Database Commands
    'db:add-default-authorization-entries',
    'db:console',
    'db:create',
    'db:drop',
    'db:dump',
    'db:import',
    'db:info',
    'db:maintain:check-tables',
    'db:query',
    'db:status',
    'db:variables',
    
    // Development Commands
    'dev:theme:build-hyva',
    'dev:symlinks',
    'dev:asset:clear',
    'dev:console',
    'dev:decrypt',
    'dev:di:preferences:list',
    'dev:encrypt',
    'dev:keep-calm',
    'dev:log:size',
    'dev:module:create',
    'dev:module:detect-composer-dependencies',
    'dev:module:list',
    'dev:module:observer:list',
    'dev:report:count',
    'dev:template-hints-blocks',
    'dev:template-hints',
    'dev:theme:list',
    'dev:translate:admin',
    'dev:translate:export',
    'dev:translate:set',
    'dev:translate:shop',
    
    // EAV Commands
    'eav:attribute:list',
    'eav:attribute:view',
    'eav:attribute:remove',
    
    // Generation Commands
    'generation:flush',
    
    // Gift Card Commands
    'giftcard:create',
    'giftcard:info',
    'giftcard:pool:generate',
    'giftcard:remove',
    
    // GitHub Commands
    'github:pr',
    
    // Indexing Commands
    'index:list',
    'index:trigger:recreate',
    
    // Installation Commands
    'install',
    
    // Integration Commands
    'integration:create',
    'integration:delete',
    'integration:list',
    'integration:show',
    
    // Magerun Commands
    'magerun:config:dump',
    'magerun:config:info',
    
    // Media Commands
    'media:dump',
    
    // Script Commands
    'script',
    
    // Search Commands
    'search:engine:list',
    
    // System Commands
    'design:demo-notice',
    'sys:check',
    'sys:cron:history',
    'sys:cron:kill',
    'sys:cron:list',
    'sys:cron:run',
    'sys:cron:schedule',
    'sys:maintenance',
    'sys:setup:change-version',
    'sys:setup:compare-versions',
    'sys:setup:downgrade-versions',
    'sys:store:config:base-url:list',
    'sys:store:list',
    'sys:url:list',
    'sys:url:regenerate',
    
    // Route Commands
    'route:list'
];

foreach ($magerunCommands as $command) {
    test("magerun {$command} --help", function () use ($command) {
        $magentoRoot = getMagentoRoot();
        $magerunPath = $magentoRoot . '/bin/n98-magerun2.phar';
        
        if (!file_exists($magerunPath)) {
            test()->fail('Magerun is not installed. Run: php bin/magento agento:magerun:install');
            //test()->markTestSkipped('Magerun is not installed. Run: php bin/magento agento:magerun:install');
        }
        
        $process = executeCommand(['php', $magerunPath, $command, '--help'], $magentoRoot, 30);
        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();
        $combinedOutput = $output . $errorOutput;
        
        // If command doesn't exist (exit code != 0 and mentions "command not found" or "no commands defined"), skip it
        if ($process->getExitCode() !== 0) {
            if (preg_match('/command.*not.*found|unknown.*command|does.*not.*exist|no.*commands.*defined/i', $combinedOutput)) {
                test()->markTestSkipped("Command {$command} is not available in this Magento installation");
            }
        }
        
        expect($process->getExitCode())->toBe(0)
            ->and($combinedOutput)->toMatch('/Usage:|help|Description:|Options:|Arguments:/');
    })->group('agento');
}

