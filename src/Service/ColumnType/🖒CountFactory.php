<?php declare(strict_types=1);

namespace 🖒\Service\ColumnType;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use 🖒\ColumnType\🖒Count;

class 🖒CountFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $apiAdapterManager = $services->get('Omeka\ApiAdapterManager');
        $likeAdapter = $apiAdapterManager->get('likes');

        return new 🖒Count($likeAdapter);
    }
}
