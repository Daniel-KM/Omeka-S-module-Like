<?php declare(strict_types=1);

namespace 🖒\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\UserRepresentation;
use Omeka\Settings\Settings;
use Omeka\Settings\SiteSettings;
use 🖒\Api\Adapter\LikeAdapter;

/**
 * View helper to display like/dislike buttons for a resource.
 *
 * Usage:
 * - $this->🖒($resource)
 * - $this->🖒($resource, $user)
 * - $this->🖒($resource, null, ['showCounts' => true, 'iconType' => 'unicode'])
 * - $this->🖒() // Uses resource and user from view
 */
class 🖒 extends AbstractHelper
{
    /**
     * @var bool
     */
    protected static $assetsLoaded = false;

    /**
     * @var \Omeka\Settings\Settings
     */
    protected $settings;

    /**
     * @var \Omeka\Settings\SiteSettings|null
     */
    protected $siteSettings;

    /**
     * @var \🖒\Api\Adapter\LikeAdapter
     */
    protected $likeAdapter;

    public function __construct(
        Settings $settings,
        ?SiteSettings $siteSettings,
        LikeAdapter $likeAdapter
    ) {
        $this->settings = $settings;
        $this->siteSettings = $siteSettings;
        $this->likeAdapter = $likeAdapter;
    }

    /**
     * Render like/dislike buttons.
     *
     * @param AbstractResourceEntityRepresentation|null $resource
     * @param UserRepresentation|null $user
     * @param array $options Available options:
     *   - showCount🖒: bool (default from settings)
     *   - showCount🖓: bool (default from settings)
     *   - iconType: 'unicode' or 'fa' (default from settings)
     *   - template: string (template path, default 'common/like')
     *   - allow🖓: bool (default from settings)
     * @return string
     */
    public function __invoke(
        ?AbstractResourceEntityRepresentation $resource = null,
        ?UserRepresentation $user = null,
        array $options = []
    ): string {
        $view = $this->getView();

        // Load css and js once.
        $this->loadAssets($view);

        // Try to get resource from view if not provided.
        if (!$resource) {
            $resource = $view->resource ?? $view->item ?? $view->itemSet ?? $view->media ?? null;
        }

        if (!$resource) {
            return '';
        }

        // Try to get user from view if not provided.
        if (!$user) {
            $user = $view->identity();
        }

        // Merge options with settings.
        $options = $this->getOptions($options);

        // Get current like status and counts.
        $resourceId = $resource->id();
        $counts = $this->likeAdapter->getLikeCounts($resourceId);

        // Get user id (User identity() or UserRepresentation).
        $userId = null;
        if ($user) {
            $userId = method_exists($user, 'id') ? $user->id() : $user->getId();
        }
        $userLiked = $userId
            ? $this->likeAdapter->getUserLikeStatus($resourceId, $userId)
            : null;

        // Determine the url for the toggle action.
        $toggleUrl = $this->getToggleUrl($view);

        return $view->partial($options['template'], [
            'resource' => $resource,
            'user' => $user,
            'options' => $options,
            'counts' => $counts,
            'userLiked' => $userLiked,
            'toggleUrl' => $toggleUrl,
            'isLoggedIn' => (bool) $user,
        ]);
    }

    /**
     * Get counts only (for use in templates).
     */
    public function counts(int $resourceId): array
    {
        return $this->likeAdapter->getLikeCounts($resourceId);
    }

    /**
     * Get user's like status for a resource.
     *
     * @return bool|null null = not voted, true = liked, false = disliked
     */
    public function userStatus(int $resourceId, int $userId): ?bool
    {
        return $this->likeAdapter->getUserLikeStatus($resourceId, $userId);
    }

    /**
     * Merge provided options with settings defaults.
     */
    protected function getOptions(array $options): array
    {
        // Get defaults from site settings first, then global settings.
        if ($this->siteSettings) {
            $showCount🖒 = $this->siteSettings->get('🖒_show_count_like', true);
            $showCount🖓 = $this->siteSettings->get('🖒_show_count_dislike', false);
            $iconType = $this->siteSettings->get('🖒_icon_type', 'unicode');
            $iconShape = $this->siteSettings->get('🖒_icon_shape', 'heart');
            $allow🖓 = $this->siteSettings->get('🖒_allow_dislike', false);
            $defaults = [
                'showCount🖒' => (bool) ($showCount🖒 === '' ? $this->settings->get('🖒_show_count_like', true) : $showCount🖒),
                'showCount🖓' => (bool) ($showCount🖓 === '' ? $this->settings->get('🖒_show_count_dislike', true) : $showCount🖓),
                'iconType' => $iconType === '' ? $this->settings->get('🖒_icon_type', 'unicode') : $iconType,
                'iconShape' => $iconShape === '' ? $this->settings->get('🖒_icon_shape', 'heart') : $iconShape,
                'allow🖓' => (bool) ($allow🖓 === '' ? $this->settings->get('🖒_allow_dislike', true) : $allow🖓),
                'template' => 'common/🖒',
            ];
        } else {
            $defaults = [
                'showCount🖒' => (bool) $this->settings->get('🖒_show_count_like', true),
                'showCount🖓' => (bool) $this->settings->get('🖒_show_count_dislike', false),
                'iconType' => $this->settings->get('🖒_icon_type', 'unicode'),
                'iconShape' => $this->settings->get('🖒_icon_shape', 'heart'),
                'allow🖓' => (bool) $this->settings->get('🖒_allow_dislike', true),
                'template' => 'common/🖒',
            ];
        }
        return array_merge($defaults, $options);
    }

    /**
     * Get the toggle url based on context (site or admin).
     */
    protected function getToggleUrl($view): string
    {
        return $this->siteSettings
            ? $view->url('site/like', ['action' => 'toggle'], true)
            : $view->url('admin/like', ['action' => 'toggle']);
    }

    /**
     * Load css and js assets once.
     */
    protected function loadAssets($view): void
    {
        if (self::$assetsLoaded) {
            return;
        }

        self::$assetsLoaded = true;

        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->appendStylesheet($assetUrl('css/🖒.css', '🖒'));
        $view->headScript()
            ->appendFile($assetUrl('js/common-dialog.js', 'Common'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('js/🖒.js', '🖒'), 'text/javascript', ['defer' => 'defer']);
    }
}
