<?php declare(strict_types=1);

namespace 🖒\Service\Controller\Site;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use 🖒\Controller\Site\IndexController;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        $likeAdapter = $services->get('Omeka\ApiAdapterManager')->get('likes');
        $settings = $services->get('Omeka\Settings');
        $siteSettings = $services->get('Omeka\Settings\Site');
        return new IndexController($likeAdapter, $settings, $siteSettings);
    }
}
