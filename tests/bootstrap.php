<?php declare(strict_types=1);

/**
 * Bootstrap file for module tests.
 *
 * Use Common module Bootstrap helper for test setup.
 *
 * Note: The 🖒 module uses "like" as a table name, which is a MySQL reserved
 * keyword. We need to handle this specially before the standard schema drop.
 */

require dirname(__DIR__, 3) . '/bootstrap.php';

// Pre-drop the `like` table with proper quoting to avoid SQL error.
// The standard DbTestCase::dropSchema() doesn't quote reserved keywords properly.
try {
    $reader = new \Laminas\Config\Reader\Ini();
    $connection = \Doctrine\DBAL\DriverManager::getConnection([
        'driver' => 'pdo_mysql',
        'user' => $reader->fromFile(OMEKA_PATH . '/application/test/config/database.ini')['user'],
        'password' => $reader->fromFile(OMEKA_PATH . '/application/test/config/database.ini')['password'],
        'host' => $reader->fromFile(OMEKA_PATH . '/application/test/config/database.ini')['host'],
        'dbname' => $reader->fromFile(OMEKA_PATH . '/application/test/config/database.ini')['dbname'],
    ]);
    $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
    $connection->executeStatement('DROP TABLE IF EXISTS `like`');
    $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    $connection->close();
} catch (\Exception $e) {
    // Ignore errors - table might not exist.
}

require dirname(__DIR__, 3) . '/modules/Common/tests/Bootstrap.php';

\CommonTest\Bootstrap::bootstrap(
    [
        'Common',
        'Guest',
        '🖒',
    ],
    '🖒Test',
    __DIR__ . '/🖒Test'
);
