<?php declare(strict_types=1);

namespace 🖒\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use 🖒\View\Helper\🖒;

class 🖒Factory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');

        // Site settings may not be available (e.g., in admin context).
        $siteSettings = null;
        $status = $services->get('Omeka\Status');
        if ($status->isSiteRequest()) {
            try {
                $siteSettings = $services->get('Omeka\Settings\Site');
            } catch (\Exception $e) {
                // Site settings not available.
            }
        }

        $apiAdapterManager = $services->get('Omeka\ApiAdapterManager');
        $likeAdapter = $apiAdapterManager->get('likes');

        return new 🖒(
            $settings,
            $siteSettings,
            $likeAdapter
        );
    }
}
