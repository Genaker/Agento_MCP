<?php

use Agento\Core\Mcp\Tools\ClearCacheTool;
use Agento\Core\Mcp\Tools\ClearRedisTool;
use Agento\Core\Mcp\Tools\ExecuteSqlTool;
use Agento\Core\Mcp\Tools\MagerunTool;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\ResourceConnection;
use PhpMcp\Server\JsonRpc\Contents\TextContent;

// Use Mockery for mocking
if (!class_exists('Mockery')) {
    // If Mockery is not available, skip these tests
    return;
}

test('ExecuteSqlTool throws exception for empty query', function () {
    $resourceConnection = Mockery::mock(ResourceConnection::class);
    $tool = new ExecuteSqlTool($resourceConnection);
    
    expect(fn() => $tool->executeSql(''))
        ->toThrow(InvalidArgumentException::class, 'Query is required');
})->group('agento');

test('ExecuteSqlTool executes query and returns TextContent', function () {
    $connection = Mockery::mock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
    $connection->shouldReceive('fetchAll')
        ->once()
        ->with('SELECT 1 as test')
        ->andReturn([['test' => 1]]);
    
    $resourceConnection = Mockery::mock(ResourceConnection::class);
    $resourceConnection->shouldReceive('getConnection')
        ->once()
        ->with('default')
        ->andReturn($connection);
    
    $tool = new ExecuteSqlTool($resourceConnection);
    $result = $tool->executeSql('SELECT 1 as test', 'default', 'table');
    
    expect($result)->toBeInstanceOf(TextContent::class)
        ->and($result->getText())->toContain('test')
        ->and($result->getText())->toContain('1');
})->group('agento');

test('ExecuteSqlTool supports JSON format', function () {
    $connection = Mockery::mock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
    $connection->shouldReceive('fetchAll')
        ->once()
        ->andReturn([['id' => 1, 'name' => 'Test']]);
    
    $resourceConnection = Mockery::mock(ResourceConnection::class);
    $resourceConnection->shouldReceive('getConnection')
        ->once()
        ->andReturn($connection);
    
    $tool = new ExecuteSqlTool($resourceConnection);
    $result = $tool->executeSql('SELECT * FROM test', 'default', 'json');
    
    expect($result)->toBeInstanceOf(TextContent::class)
        ->and($result->getText())->toContain('"id":')
        ->and($result->getText())->toContain('"name":');
})->group('agento');

test('ExecuteSqlTool supports CSV format', function () {
    $connection = Mockery::mock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
    $connection->shouldReceive('fetchAll')
        ->once()
        ->andReturn([['id' => 1, 'name' => 'Test']]);
    
    $resourceConnection = Mockery::mock(ResourceConnection::class);
    $resourceConnection->shouldReceive('getConnection')
        ->once()
        ->andReturn($connection);
    
    $tool = new ExecuteSqlTool($resourceConnection);
    $result = $tool->executeSql('SELECT * FROM test', 'default', 'csv');
    
    expect($result)->toBeInstanceOf(TextContent::class)
        ->and($result->getText())->toContain('id,name')
        ->and($result->getText())->toContain('1,"Test"');
})->group('agento');

test('ExecuteSqlTool handles empty results', function () {
    $connection = Mockery::mock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
    $connection->shouldReceive('fetchAll')
        ->once()
        ->andReturn([]);
    
    $resourceConnection = Mockery::mock(ResourceConnection::class);
    $resourceConnection->shouldReceive('getConnection')
        ->once()
        ->andReturn($connection);
    
    $tool = new ExecuteSqlTool($resourceConnection);
    $result = $tool->executeSql('SELECT * FROM empty_table', 'default', 'table');
    
    expect($result)->toBeInstanceOf(TextContent::class)
        ->and($result->getText())->toBe('No results found.');
})->group('agento');

test('ClearCacheTool clears specific cache type', function () {
    $cacheTypeList = Mockery::mock(TypeListInterface::class);
    $cacheTypeList->shouldReceive('cleanType')
        ->once()
        ->with('config');
    
    $cacheManager = Mockery::mock(CacheManager::class);
    
    $tool = new ClearCacheTool($cacheTypeList, $cacheManager);
    $result = $tool->clearCache('config');
    
    expect($result)->toBeInstanceOf(TextContent::class)
        ->and($result->getText())->toContain('Cleared cache type: config');
})->group('agento');

test('ClearCacheTool clears all cache types', function () {
    $cacheTypeList = Mockery::mock(TypeListInterface::class);
    $cacheTypeList->shouldReceive('getTypes')
        ->once()
        ->andReturn(['config' => null, 'layout' => null]);
    
    $cacheManager = Mockery::mock(CacheManager::class);
    $cacheManager->shouldReceive('clean')
        ->once()
        ->with(['config', 'layout']);
    
    $tool = new ClearCacheTool($cacheTypeList, $cacheManager);
    $result = $tool->clearCache();
    
    expect($result)->toBeInstanceOf(TextContent::class)
        ->and($result->getText())->toContain('All cache cleared successfully');
})->group('agento');

test('MagerunTool throws exception for empty command', function () {
    $tool = new MagerunTool();
    
    expect(fn() => $tool->executeMagerun(''))
        ->toThrow(InvalidArgumentException::class, 'Command is required');
})->group('agento');

test('MagerunTool throws exception when magerun not found', function () {
    $tool = new MagerunTool();
    
    // This will fail because magerun doesn't exist in test environment
    // We can't easily mock file_exists, so we'll test the exception path
    // by using a path that definitely doesn't exist
    expect(fn() => $tool->executeMagerun('db:query', ['query' => 'SELECT 1']))
        ->toThrow(RuntimeException::class);
})->group('agento')->skip('Requires magerun installation or file system mocking');

test('ClearRedisTool returns error when Redis extension not loaded', function () {
})->group('agento')->skip('clear_redis tool test skipped');

test('ClearRedisTool returns TextContent instance', function () {
})->group('agento')->skip('clear_redis tool test skipped');

