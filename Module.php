<?php declare(strict_types=1);

namespace 🖒;

if (!class_exists('Common\TraitModule', false)) {
    require_once dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\Stdlib\PsrMessage;
use Common\TraitModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use 🖒\Entity\Like;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\ItemSetRepresentation;
use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Module\AbstractModule;

/**
 * 🖒 module.
 *
 * Allow users to like or dislike resources.
 *
 * @copyright Daniel Berthereau, 2025
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    use TraitModule;

    const NAMESPACE = __NAMESPACE__;

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);
        $this->addAclRules();
    }

    protected function preInstall(): void
    {
        $services = $this->getServiceLocator();
        $translate = $services->get('ControllerPluginManager')->get('translate');

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.74')) {
            $message = new \Omeka\Stdlib\Message(
                $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.74'
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }

        if (!$this->checkModuleActiveVersion('Guest', '3.4.11')) {
            $message = new \Omeka\Stdlib\Message(
                $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Guest', '3.4.11'
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }
    }

    protected function postInstall(): void
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');

        // Enable advanced search field for admin and public.
        $searchFields = $settings->get('advancedsearch_search_fields');
        if ($searchFields !== null && !in_array('common/advanced-search/🖒', $searchFields)) {
            $searchFields[] = 'common/advanced-search/🖒';
            $settings->set('advancedsearch_search_fields', $searchFields);
        }
    }

    /**
     * Add ACL rules for this module.
     */
    protected function addAclRules(): void
    {
        /** @var \Omeka\Permissions\Acl $acl */
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');
        $settings = $services->get('Omeka\Settings');

        $roles = $acl->getRoles();

        // Public can view likes if setting allows.
        $publicCanView = (bool) $settings->get('🖒_allow_public_view', true);
        if ($publicCanView) {
            $acl
                ->allow(
                    null,
                    [Like::class],
                    ['read']
                )
                ->allow(
                    null,
                    [Api\Adapter\LikeAdapter::class],
                    ['search', 'read']
                );
        }

        // Authenticated users can create, update, delete their own likes.
        $acl
            ->allow(
                $roles,
                [Like::class],
                ['read', 'create', 'update', 'delete']
            )
            ->allow(
                $roles,
                [Api\Adapter\LikeAdapter::class],
                ['search', 'read', 'create', 'update', 'delete']
            )
            ->allow(
                $roles,
                [Controller\Site\IndexController::class],
                ['toggle', 'status']
            )
            ->allow(
                $roles,
                [Controller\Site\GuestController::class],
                ['browse']
            );

        // Admins can manage all likes.
        $adminRoles = [
            \Omeka\Permissions\Acl::ROLE_GLOBAL_ADMIN,
            \Omeka\Permissions\Acl::ROLE_SITE_ADMIN,
            \Omeka\Permissions\Acl::ROLE_EDITOR,
            \Omeka\Permissions\Acl::ROLE_REVIEWER,
        ];

        $acl
            ->allow(
                $adminRoles,
                [Like::class],
                ['read', 'create', 'update', 'delete', 'view-all']
            )
            ->allow(
                $adminRoles,
                [Api\Adapter\LikeAdapter::class],
                ['search', 'read', 'create', 'update', 'delete', 'batch-create', 'batch-update', 'batch-delete']
            )
            ->allow(
                $adminRoles,
                [Controller\Admin\IndexController::class]
            );
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $likeResources = $settings->get('🖒_resources', ['items']);
        $likeResourcesFlipped = array_flip($likeResources);

        // Add the Like term definition to JSON-LD context.
        $sharedEventManager->attach(
            '*',
            'api.context',
            [$this, 'handleApiContext']
        );

        // Add like data to the resource JSON-LD representation.
        $representations = [
            'items' => ItemRepresentation::class,
            'item_sets' => ItemSetRepresentation::class,
            'media' => MediaRepresentation::class,
        ];
        $representations = array_intersect_key($representations, $likeResourcesFlipped);
        foreach ($representations as $representation) {
            $sharedEventManager->attach(
                $representation,
                'rep.resource.json',
                [$this, 'filterJsonLd']
            );
        }

        // Add like filter to search queries.
        $adapters = [
            'items' => \Omeka\Api\Adapter\ItemAdapter::class,
            'item_sets' => \Omeka\Api\Adapter\ItemSetAdapter::class,
            'media' => \Omeka\Api\Adapter\MediaAdapter::class,
        ];
        $adapters = array_intersect_key($adapters, $likeResourcesFlipped);
        foreach ($adapters as $adapter) {
            $sharedEventManager->attach(
                $adapter,
                'api.search.query',
                [$this, 'handleApiSearchQuery']
            );
        }

        // Add headers to site views.
        $controllers = [
            'items' => 'Omeka\Controller\Site\Item',
            'item_sets' => 'Omeka\Controller\Site\ItemSet',
            'media' => 'Omeka\Controller\Site\Media',
        ];
        $controllers = array_intersect_key($controllers, $likeResourcesFlipped);
        foreach ($controllers as $controller) {
            // Add advanced search field.
            $sharedEventManager->attach(
                $controller,
                'view.advanced_search',
                [$this, 'handleViewAdvancedSearch']
            );

            // Add search filter display.
            $sharedEventManager->attach(
                $controller,
                'view.search.filters',
                [$this, 'filterSearchFilters']
            );

            // Add like button to resource show pages (public).
            $sharedEventManager->attach(
                $controller,
                'view.show.after',
                [$this, 'viewShowAfterResourcePublic']
            );
        }

        // Admin controllers.
        $adminControllers = [
            'items' => 'Omeka\Controller\Admin\Item',
            'item_sets' => 'Omeka\Controller\Admin\ItemSet',
            'media' => 'Omeka\Controller\Admin\Media',
        ];
        $adminControllers = array_intersect_key($adminControllers, $likeResourcesFlipped);
        foreach ($adminControllers as $controller) {
            // Add advanced search field.
            $sharedEventManager->attach(
                $controller,
                'view.advanced_search',
                [$this, 'handleViewAdvancedSearch']
            );

            // Add search filter display.
            $sharedEventManager->attach(
                $controller,
                'view.search.filters',
                [$this, 'filterSearchFilters']
            );

            // Load assets on browse pages for details panel.
            $sharedEventManager->attach(
                $controller,
                'view.browse.before',
                [$this, 'addAdminBrowseAssets']
            );

            // Add like details to admin sidebar.
            $sharedEventManager->attach(
                $controller,
                'view.details',
                [$this, 'viewDetails']
            );

            // Add like to resource show pages (admin).
            $sharedEventManager->attach(
                $controller,
                'view.show.sidebar',
                [$this, 'viewShowSidebarAdmin']
            );
        }

        // Add search fields to admin query form.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Query',
            'view.advanced_search',
            [$this, 'handleViewAdvancedSearch']
        );

        // Settings forms.
        $sharedEventManager->attach(
            \Omeka\Form\SettingForm::class,
            'form.add_elements',
            [$this, 'handleMainSettings']
        );

        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'handleSiteSettings']
        );

        // Integration with module AdvancedSearch.
        if (class_exists('AdvancedSearch\Module', false)) {
            $sharedEventManager->attach(
                \AdvancedSearch\FormAdapter\AbstractFormAdapter::class,
                'form.add_elements',
                [$this, 'handleAdvancedSearchFormElements']
            );
        }

        // Integration with module SearchSolr via AdvancedSearch.
        if (class_exists('SearchSolr\Module', false)) {
            // Add like count as indexable field.
            $sharedEventManager->attach(
                \SearchSolr\ValueExtractor\AbstractResourceEntityValueExtractor::class,
                'solr.value_extractor.fields',
                [$this, 'handleSolrValueExtractorFields']
            );
        }

        // Reindex resource on like change.
        $sharedEventManager->attach(
            Api\Adapter\LikeAdapter::class,
            'api.create.post',
            [$this, 'handleLikeChangeForReindex'],
            -100
        );
        $sharedEventManager->attach(
            Api\Adapter\LikeAdapter::class,
            'api.update.post',
            [$this, 'handleLikeChangeForReindex'],
            -100
        );
        $sharedEventManager->attach(
            Api\Adapter\LikeAdapter::class,
            'api.delete.post',
            [$this, 'handleLikeChangeForReindex'],
            -100
        );

        // Batch edit integration.
        $sharedEventManager->attach(
            \Omeka\Form\ResourceBatchUpdateForm::class,
            'form.add_elements',
            [$this, 'handleResourceBatchUpdateFormElements']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemAdapter::class,
            'api.preprocess_batch_update',
            [$this, 'handleResourceBatchUpdatePreprocess']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            'api.preprocess_batch_update',
            [$this, 'handleResourceBatchUpdatePreprocess']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\MediaAdapter::class,
            'api.preprocess_batch_update',
            [$this, 'handleResourceBatchUpdatePreprocess']
        );

        // Guest module integration.
        $sharedEventManager->attach(
            \Guest\Controller\Site\GuestController::class,
            'guest.widgets',
            [$this, 'handleGuestWidgets']
        );
    }

    /**
     * Add the Like JSON-LD context.
     */
    public function handleApiContext(Event $event): void
    {
        $context = $event->getParam('context');
        $context['o-module-🖒'] = 'http://omeka.org/s/vocabs/module/like#';
        $event->setParam('context', $context);
    }

    /**
     * Add like data to the resource JSON-LD.
     */
    public function filterJsonLd(Event $event): void
    {
        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');

        $resource = $event->getTarget();
        $jsonLd = $event->getParam('jsonLd');

        /** @var \🖒\Api\Adapter\LikeAdapter $adapter */
        $adapter = $services->get('Omeka\ApiAdapterManager')->get('likes');
        $counts = $adapter->getLikeCounts($resource->id());

        $jsonLd['o-module-🖒:🖒'] = $counts['likes'];
        $jsonLd['o-module-🖒:🖓'] = $counts['dislikes'];
        $jsonLd['o-module-🖒:total'] = $counts['total'];

        $event->setParam('jsonLd', $jsonLd);
    }

    /**
     * Handle API search query to filter by like status.
     */
    public function handleApiSearchQuery(Event $event): void
    {
        $query = $event->getParam('request')->getContent();

        $qb = $event->getParam('queryBuilder');
        $adapter = $event->getTarget();
        $expr = $qb->expr();

        // Filter by "has likes".
        if (isset($query['has_likes']) && $query['has_likes'] !== '') {
            $likeAlias = $adapter->createAlias();
            if ($query['has_likes']) {
                $qb->innerJoin(
                    Like::class,
                    $likeAlias,
                    'WITH',
                    $expr->andX(
                        $expr->eq($likeAlias . '.resource', 'omeka_root.id'),
                        $expr->eq($likeAlias . '.liked', 1)
                    )
                );
            } else {
                // Resources with no likes.
                $subQb = $adapter->getEntityManager()->createQueryBuilder();
                $subQb->select($likeAlias . '.resource')
                    ->from(Like::class, $likeAlias)
                    ->where($expr->eq($likeAlias . '.liked', 1));

                $qb->andWhere($expr->notIn('omeka_root.id', $subQb->getDQL()));
            }
        }

        // Filter by user's like status.
        if (!empty($query['like_status'])) {
            $user = $this->getServiceLocator()->get('Omeka\AuthenticationService')->getIdentity();
            if ($user) {
                $likeAlias = $adapter->createAlias();
                switch ($query['like_status']) {
                    case 'liked':
                        $qb->innerJoin(
                            Like::class,
                            $likeAlias,
                            'WITH',
                            $expr->andX(
                                $expr->eq($likeAlias . '.resource', 'omeka_root.id'),
                                $expr->eq($likeAlias . '.owner', $user->getId()),
                                $expr->eq($likeAlias . '.liked', 1)
                            )
                        );
                        break;
                    case 'disliked':
                        $qb->innerJoin(
                            Like::class,
                            $likeAlias,
                            'WITH',
                            $expr->andX(
                                $expr->eq($likeAlias . '.resource', 'omeka_root.id'),
                                $expr->eq($likeAlias . '.owner', $user->getId()),
                                $expr->eq($likeAlias . '.liked', 0)
                            )
                        );
                        break;
                    case 'voted':
                        $qb->innerJoin(
                            Like::class,
                            $likeAlias,
                            'WITH',
                            $expr->andX(
                                $expr->eq($likeAlias . '.resource', 'omeka_root.id'),
                                $expr->eq($likeAlias . '.owner', $user->getId())
                            )
                        );
                        break;
                    case 'not_voted':
                        $subQb = $adapter->getEntityManager()->createQueryBuilder();
                        $subQb->select($likeAlias . '.resource')
                            ->from(Like::class, $likeAlias)
                            ->where($expr->eq($likeAlias . '.owner', $user->getId()));

                        $qb->andWhere($expr->notIn('omeka_root.id', $subQb->getDQL()));
                        break;
                }
            }
        }

        // Sort by like count.
        if (!empty($query['sort_by']) && $query['sort_by'] === '🖒_count') {
            $likeAlias = $adapter->createAlias();
            $qb->leftJoin(
                Like::class,
                $likeAlias,
                'WITH',
                $expr->andX(
                    $expr->eq($likeAlias . '.resource', 'omeka_root.id'),
                    $expr->eq($likeAlias . '.liked', 1)
                )
            );
            $qb->addSelect('COUNT(' . $likeAlias . '.id) AS HIDDEN likeCount');
            $qb->addGroupBy('omeka_root.id');

            $sortOrder = isset($query['sort_order']) && strtoupper($query['sort_order']) === 'DESC' ? 'DESC' : 'ASC';
            $qb->addOrderBy('likeCount', $sortOrder);
        }

        // Sort by dislike count.
        if (!empty($query['sort_by']) && $query['sort_by'] === 'dislike_count') {
            $likeAlias = $adapter->createAlias();
            $qb->leftJoin(
                Like::class,
                $likeAlias,
                'WITH',
                $expr->andX(
                    $expr->eq($likeAlias . '.resource', 'omeka_root.id'),
                    $expr->eq($likeAlias . '.liked', 0)
                )
            );
            $qb->addSelect('COUNT(' . $likeAlias . '.id) AS HIDDEN dislikeCount');
            $qb->addGroupBy('omeka_root.id');

            $sortOrder = isset($query['sort_order']) && strtoupper($query['sort_order']) === 'DESC' ? 'DESC' : 'ASC';
            $qb->addOrderBy('dislikeCount', $sortOrder);
        }

        // Sort by total vote count.
        if (!empty($query['sort_by']) && $query['sort_by'] === 'vote_count') {
            $likeAlias = $adapter->createAlias();
            $qb->leftJoin(
                Like::class,
                $likeAlias,
                'WITH',
                $expr->eq($likeAlias . '.resource', 'omeka_root.id')
            );
            $qb->addSelect('COUNT(' . $likeAlias . '.id) AS HIDDEN voteCount');
            $qb->addGroupBy('omeka_root.id');

            $sortOrder = isset($query['sort_order']) && strtoupper($query['sort_order']) === 'DESC' ? 'DESC' : 'ASC';
            $qb->addOrderBy('voteCount', $sortOrder);
        }
    }

    /**
     * Add advanced search form field.
     */
    public function handleViewAdvancedSearch(Event $event): void
    {
        $query = $event->getParam('query', []);

        $partials = $event->getParam('partials', []);
        $partials[] = 'common/advanced-search/🖒';
        $event->setParam('partials', $partials);
    }

    /**
     * Filter search filters display.
     */
    public function filterSearchFilters(Event $event): void
    {
        $translate = $event->getTarget()->plugin('translate');
        $filters = $event->getParam('filters');
        $query = $event->getParam('query', []);

        if (!empty($query['like_status'])) {
            $filterLabel = $translate('Like status'); // @translate
            $statusLabels = [
                'liked' => $translate('Liked by me'), // @translate
                'disliked' => $translate('Disliked by me'), // @translate
                'voted' => $translate('Voted by me'), // @translate
                'not_voted' => $translate('Not voted by me'), // @translate
            ];
            $filters[$filterLabel][] = $statusLabels[$query['like_status']] ?? $query['like_status'];
        }

        if (isset($query['has_likes']) && $query['has_likes'] !== '') {
            $filterLabel = $translate('Has likes'); // @translate
            $filterValue = $query['has_likes'] ? $translate('Yes') : $translate('No');
            $filters[$filterLabel][] = $filterValue;
        }

        $event->setParam('filters', $filters);
    }

    /**
     * Display like button on public resource show pages.
     */
    public function viewShowAfterResourcePublic(Event $event): void
    {
        $view = $event->getTarget();
        $resource = $view->vars()->resource;

        if ($this->isLikeEnabledForResource($resource)) {
            echo '<div id="like-section" class="like-section">';
            echo $view->like($resource);
            echo '</div>';
        }
    }

    /**
     * Load assets on admin browse pages for details panel.
     */
    public function addAdminBrowseAssets(Event $event): void
    {
        $view = $event->getTarget();
        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->appendStylesheet($assetUrl('css/🖒.css', '🖒'));
        $view->headScript()
            ->appendFile($assetUrl('js/common-dialog.js', 'Common'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('js/🖒.js', '🖒'), 'text/javascript', ['defer' => 'defer']);
    }

    /**
     * Add like stats to admin sidebar.
     */
    public function viewShowSidebarAdmin(Event $event): void
    {
        $view = $event->getTarget();
        $resource = $view->vars()->resource;

        if ($this->isLikeEnabledForResource($resource)) {
            echo $view->partial('common/admin/🖒-details', [
                'resource' => $resource,
            ]);
        }
    }

    /**
     * Add like details to admin browse details.
     */
    public function viewDetails(Event $event): void
    {
        $representation = $event->getParam('entity');

        if (!$this->isLikeEnabledForResource($representation)) {
            return;
        }

        echo $event->getTarget()->partial('common/admin/🖒-details', [
            'resource' => $representation,
        ]);
    }

    /**
     * Handle AdvancedSearch form elements.
     */
    public function handleAdvancedSearchFormElements(Event $event): void
    {
        $form = $event->getTarget();
        $form->add([
            'name' => 'like_status',
            'type' => \Laminas\Form\Element\Select::class,
            'options' => [
                'label' => '🖒 status', // @translate
                'value_options' => [
                    '' => 'All', // @translate
                    'liked' => 'Liked by me', // @translate
                    'disliked' => 'Disliked by me', // @translate
                    'voted' => 'Voted by me', // @translate
                    'not_voted' => 'Not voted by me', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'like-status',
            ],
        ]);
    }

    /**
     * Handle Solr value extractor fields.
     */
    public function handleSolrValueExtractorFields(Event $event): void
    {
        $fields = $event->getParam('fields', []);
        $fields['🖒_count'] = 'Like count (module 🖒)'; // @translate
        $event->setParam('fields', $fields);
    }

    /**
     * Trigger reindex when like changes.
     */
    public function handleLikeChangeForReindex(Event $event): void
    {
        $services = $this->getServiceLocator();

        // Check if AdvancedSearch module is active.
        if (!$services->get('Omeka\ModuleManager')->getModule('AdvancedSearch')) {
            return;
        }

        $response = $event->getParam('response');
        $like = $response->getContent();

        $resource = $like->getResource();
        if (!$resource) {
            return;
        }

        // Trigger a partial update to reindex the resource.
        $api = $services->get('Omeka\ApiManager');
        $resourceName = $this->getResourceName($resource);
        if ($resourceName) {
            try {
                $api->update($resourceName, $resource->getId(), [], [], ['isPartial' => true, 'flushEntityManager' => false]);
            } catch (\Exception $e) {
                // Ignore errors.
            }
        }
    }

    /**
     * Add batch update form elements.
     */
    public function handleResourceBatchUpdateFormElements(Event $event): void
    {
        $form = $event->getTarget();

        $form->add([
            'name' => '🖒_action',
            'type' => \Laminas\Form\Element\Select::class,
            'options' => [
                'label' => 'Likes', // @translate
                'value_options' => [
                    '' => '[No change]', // @translate
                    'reset' => 'Reset all likes', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'like-action',
                'class' => 'chosen-select',
            ],
        ]);
    }

    /**
     * Handle batch update preprocess.
     */
    public function handleResourceBatchUpdatePreprocess(Event $event): void
    {
        $data = $event->getParam('data');

        if (empty($data['🖒_action'])) {
            return;
        }

        if ($data['🖒_action'] === 'reset') {
            $request = $event->getParam('request');
            $ids = (array) $request->getIds();

            $services = $this->getServiceLocator();
            $api = $services->get('Omeka\ApiManager');

            foreach ($ids as $id) {
                $likes = $api->search('likes', ['resource_id' => $id])->getContent();
                foreach ($likes as $like) {
                    $api->delete('likes', $like->id());
                }
            }
        }
    }

    /**
     * Check if likes are enabled for a resource type.
     */
    protected function isLikeEnabledForResource(AbstractResourceEntityRepresentation $resource): bool
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $likeResources = $settings->get('🖒_resources', ['items']);
        $resourceName = $resource->resourceName();
        return in_array($resourceName, $likeResources);
    }

    /**
     * Get resource name from entity.
     */
    protected function getResourceName($resource): ?string
    {
        if ($resource instanceof \Omeka\Entity\Item) {
            return 'items';
        } elseif ($resource instanceof \Omeka\Entity\ItemSet) {
            return 'item_sets';
        } elseif ($resource instanceof \Omeka\Entity\Media) {
            return 'media';
        }
        return null;
    }

    /**
     * Add widget to guest dashboard.
     */
    public function handleGuestWidgets(Event $event): void
    {
        $services = $this->getServiceLocator();
        $plugins = $services->get('ViewHelperManager');
        $partial = $plugins->get('partial');
        $translate = $plugins->get('translate');
        $siteSettings = $services->get('Omeka\Settings\Site');

        $widget = [];
        $widget['label'] = $siteSettings->get('🖒_guest_widget_label') ?: $translate('Likes'); // @translate
        $widget['content'] = $partial('guest/site/guest/widget/🖒');

        $widgets = $event->getParam('widgets');
        $widgets['🖒'] = $widget;
        $event->setParam('widgets', $widgets);
    }
}
