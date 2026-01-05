<?php declare(strict_types=1);

/**
 * Bootstrap file for module tests.
 *
 * Use Common module Bootstrap helper for test setup.
 */

require dirname(__DIR__, 3) . '/modules/Common/test/Bootstrap.php';

\CommonTest\Bootstrap::bootstrap(
    [
        'Common',
        'Guest',
        '🖒',
    ],
    '🖒Test',
    __DIR__ . '/🖒Test'
);
