<?php declare(strict_types=1);

namespace 🖒\Controller\Admin;

use Common\Stdlib\PsrMessage;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
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
     * Browse likes.
     */
    public function browseAction()
    {
        $this->setBrowseDefaults('created');

        $query = $this->params()->fromQuery();

        $response = $this->api()->search('likes', $query);
        $this->paginator($response->getTotalResults());

        $likes = $response->getContent();

        return new ViewModel([
            'likes' => $likes,
            'query' => $query,
        ]);
    }

    /**
     * Show a like.
     */
    public function showAction()
    {
        $id = $this->params('id');
        $response = $this->api()->read('likes', $id);
        $like = $response->getContent();

        return new ViewModel([
            'like' => $like,
        ]);
    }

    /**
     * Delete a like.
     */
    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(\Omeka\Form\ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('likes', $this->params('id'));
                if ($response) {
                    $this->messenger()->addSuccess('Like successfully deleted.'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute('admin/like');
    }

    /**
     * Batch delete likes.
     */
    public function batchDeleteAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('admin/like');
        }

        $resourceIds = $this->params()->fromPost('resource_ids', []);
        if (!$resourceIds) {
            $this->messenger()->addError('You must select at least one like to batch delete.'); // @translate
            return $this->redirect()->toRoute('admin/like');
        }

        $form = $this->getForm(\Omeka\Form\ConfirmForm::class);
        $form->setData($this->getRequest()->getPost());
        if ($form->isValid()) {
            $response = $this->api($form)->batchDelete('likes', $resourceIds, [], ['continueOnError' => true]);
            if ($response) {
                $this->messenger()->addSuccess('Likes successfully deleted.'); // @translate
            }
        } else {
            $this->messenger()->addFormErrors($form);
        }

        return $this->redirect()->toRoute('admin/like');
    }

    /**
     * Batch reset likes for selected resources (admin batch edit action).
     */
    public function batchResetAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('admin/like');
        }

        $resourceIds = $this->params()->fromPost('resource_ids', []);
        if (!$resourceIds) {
            $this->messenger()->addError('You must select at least one resource.'); // @translate
            return $this->redirect()->toRoute('admin/like');
        }

        // Delete all likes for the selected resources.
        $api = $this->api();
        $count = 0;
        foreach ($resourceIds as $resourceId) {
            $likes = $api->search('likes', ['resource_id' => $resourceId])->getContent();
            foreach ($likes as $like) {
                $api->delete('likes', $like->id());
                $count++;
            }
        }

        $this->messenger()->addSuccess(new PsrMessage(
            '{count} likes have been reset.', // @translate
            ['count' => $count]
        ));

        return $this->redirect()->toRoute('admin/like');
    }

    /**
     * Show like details (for sidebar).
     */
    public function showDetailsAction()
    {
        $response = $this->api()->read('likes', $this->params('id'));
        $like = $response->getContent();

        $view = new ViewModel([
            'like' => $like,
        ]);
        $view->setTerminal(true);
        return $view;
    }

    /**
     * Toggle like via AJAX (for admin interface).
     */
    public function toggleAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->jSend()->fail(null, new PsrMessage('Method not allowed.')); // @translate
        }

        $user = $this->identity();
        if (!$user) {
            return $this->jSend()->fail(null, new PsrMessage('You must be logged in.')); // @translate
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
            return $this->jSend()->error(null, new PsrMessage('An error occurred.')); // @translate
        }
    }
}
