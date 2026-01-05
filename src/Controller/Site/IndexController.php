<?php declare(strict_types=1);

namespace 🖒\Controller\Site;

use Common\Stdlib\PsrMessage;
use Laminas\Mvc\Controller\AbstractActionController;
use 🖒\Api\Adapter\LikeAdapter;

class IndexController extends AbstractActionController
{
    /**
     * @var \🖒\Api\Adapter\LikeAdapter
     */
    protected $likeAdapter;

    public function __construct(LikeAdapter $likeAdapter)
    {
        $this->likeAdapter = $likeAdapter;
    }

    /**
     * Toggle a like/dislike on a resource (AJAX endpoint).
     *
     * Expected POST parameters:
     * - resource_id: int
     * - liked: bool (true for like, false for dislike)
     */
    public function toggleAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->jSend()->fail(null, new PsrMessage('Method not allowed.')); // @translate
        }

        $user = $this->identity();
        if (!$user) {
            return $this->jSend()->fail(
                ['requireLogin' => true],
                new PsrMessage('You must be logged in to like or dislike resources.') // @translate
            );
        }

        $resourceId = (int) $this->params()->fromPost('resource_id');
        $liked = $this->params()->fromPost('liked');

        if (!$resourceId) {
            return $this->jSend()->fail(null, new PsrMessage('Invalid resource.')); // @translate
        }

        // Convert liked to boolean or null.
        if ($liked === 'null' || $liked === '' || $liked === null) {
            $liked = null;
        } else {
            $liked = filter_var($liked, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        try {
            $result = $this->likeAdapter->toggleLike($resourceId, $user->getId(), $liked);
            $counts = $this->likeAdapter->getLikeCounts($resourceId);

            return $this->jSend()->success([
                'action' => $result['action'],
                'liked' => $result['liked'],
                'likes' => $counts['likes'],
                'dislikes' => $counts['dislikes'],
                'total' => $counts['total'],
            ]);
        } catch (\Exception $e) {
            return $this->jSend()->error(null, new PsrMessage('An error occurred while processing your request.')); // @translate
        }
    }

    /**
     * Get the current like status and counts for a resource (AJAX endpoint).
     *
     * Expected GET/POST parameters:
     * - resource_id: int
     */
    public function statusAction()
    {
        $resourceId = (int) $this->params()->fromQuery('resource_id', $this->params()->fromPost('resource_id'));

        if (!$resourceId) {
            return $this->jSend()->fail(null, new PsrMessage('Invalid resource.')); // @translate
        }

        $counts = $this->likeAdapter->getLikeCounts($resourceId);

        $user = $this->identity();
        $userLiked = $user
            ? $this->likeAdapter->getUserLikeStatus($resourceId, $user->getId())
            : null;

        return $this->jSend()->success([
            'liked' => $userLiked,
            'likes' => $counts['likes'],
            'dislikes' => $counts['dislikes'],
            'total' => $counts['total'],
            'isLoggedIn' => (bool) $user,
        ]);
    }
}
