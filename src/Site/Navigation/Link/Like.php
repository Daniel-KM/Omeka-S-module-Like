<?php declare(strict_types=1);

namespace 🖒\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\Navigation\Link\LinkInterface;
use Omeka\Stdlib\ErrorStore;

/**
 * Navigation link to the user's likes page.
 */
class Like implements LinkInterface
{
    public function getName()
    {
        return 'My Likes'; // @translate
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/label';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        if (!isset($data['label']) || $data['label'] === '') {
            $errorStore->addError('o:navigation', 'Invalid navigation: link without label'); // @translate
            return false;
        }
        return true;
    }

    public function getLabel(array $data, SiteRepresentation $site)
    {
        return $data['label'] ?? '';
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        $services = $site->getServiceLocator();
        $user = $services->get('Omeka\AuthenticationService')->getIdentity();
        $moduleManager = $services->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule('Guest');
        $isGuestActive = $module
            && $module->getState() === \Omeka\Module\Manager::STATE_ACTIVE;

        if ($user && $isGuestActive) {
            return [
                'label' => $data['label'],
                'route' => 'site/guest/like',
                'class' => 'like-link',
                'params' => [
                    'site-slug' => $site->slug(),
                ],
                'resource' => '🖒\Controller\Site\GuestController',
            ];
        }

        // Without the module Guest, its routes do not exist.
        if (!$isGuestActive) {
            return [
                'label' => $data['label'],
                'route' => 'login',
                'class' => 'like-link',
            ];
        }

        return [
            'label' => $data['label'],
            'route' => 'site/guest/anonymous',
            'class' => 'like-link',
            'params' => [
                'site-slug' => $site->slug(),
                'action' => 'login',
            ],
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return [
            'label' => $data['label'] ?? '',
        ];
    }
}
