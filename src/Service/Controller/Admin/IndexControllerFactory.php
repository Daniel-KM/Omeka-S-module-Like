<?php declare(strict_types=1);

namespace 🖒\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use 🖒\Controller\Admin\IndexController;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $likeAdapter = $services->get('Omeka\ApiAdapterManager')->get('likes');
        $settings = $services->get('Omeka\Settings');
        return new IndexController($likeAdapter, $settings);
    }
}
