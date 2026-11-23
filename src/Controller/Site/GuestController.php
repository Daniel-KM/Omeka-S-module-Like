<?php declare(strict_types=1);

namespace 🖒\Controller\Site;

use Laminas\View\Model\ViewModel;

/**
 * Controller for guest user likes pages.
 *
 * Provides browse functionality for authenticated users to view their likes.
 */
class GuestController extends IndexController
{
    /**
     * Browse user's likes.
     */
    public function browseAction()
    {
        $user = $this->identity();
        if (!$user) {
            // Without the module Guest, its routes do not exist.
            return class_exists('Guest\\Module', false)
                ? $this->redirect()->toRoute('site/guest/anonymous', [
                    'site-slug' => $this->params('site-slug'),
                ])
                : $this->redirect()->toRoute('login');
        }

        $site = $this->currentSite();
        $userId = $user->getId();

        // Get user's likes with pagination.
        $query = $this->params()->fromQuery();
        $query['owner_id'] = $userId;

        $this->setBrowseDefaults('created', 'desc');

        $response = $this->api()->search('likes', $query);
        $likes = $response->getContent();
        $totalCount = $response->getTotalResults();

        $this->paginator($totalCount);

        // Get the resources for each like.
        $resources = [];
        foreach ($likes as $like) {
            $resource = $like->resource();
            if ($resource) {
                $resources[$like->id()] = $resource;
            }
        }

        $view = new ViewModel([
            'site' => $site,
            'user' => $user,
            'likes' => $likes,
            'resources' => $resources,
            'totalCount' => $totalCount,
        ]);

        return $view->setTemplate('guest/site/guest/🖒-browse');
    }
}
