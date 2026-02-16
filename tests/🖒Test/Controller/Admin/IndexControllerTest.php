<?php declare(strict_types=1);

namespace 🖒Test\Controller\Admin;

use Omeka\Test\AbstractHttpControllerTestCase;
use 🖒Test\🖒TestTrait;

/**
 * Functional tests for the Like admin controller.
 *
 * Note: Many controller tests require proper view template resolution
 * which is complex with emoji namespaces. These tests focus on route
 * matching and basic functionality.
 */
class IndexControllerTest extends AbstractHttpControllerTestCase
{
    use 🖒TestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        $this->logout();
        parent::tearDown();
    }

    /**
     * Test browse route is matched.
     */
    public function testBrowseRouteMatches(): void
    {
        $this->dispatch('/admin/like');
        // Route should match even if view rendering fails.
        $this->assertMatchedRouteName('admin/like');
    }

    /**
     * Test admin requires authentication.
     */
    public function testAdminRequiresAuthentication(): void
    {
        $this->logout();

        $this->dispatch('/admin/like');
        // Should redirect to login.
        $this->assertResponseStatusCode(302);
    }

    /**
     * Test toggle action with valid data returns success.
     */
    public function testToggleActionWithValidData(): void
    {
        $item = $this->createItem();

        $this->dispatch('/admin/like/toggle', 'POST', [
            'resource_id' => $item->id(),
            'liked' => 'true',
        ]);

        // Toggle action returns JSON.
        $response = $this->getResponse();
        $body = $response->getBody();
        $json = json_decode($body, true);

        $this->assertIsArray($json);
        $this->assertEquals('success', $json['status']);
        $this->assertEquals('created', $json['data']['action']);
        $this->assertTrue($json['data']['liked']);
        $this->assertEquals(1, $json['data']['likes']);

        // Clean up the like.
        $likes = $this->api()->search('likes', ['resource_id' => $item->id()])->getContent();
        foreach ($likes as $like) {
            $this->createdLikes[] = $like->id();
        }
    }

    /**
     * Test toggle action removes like on same state.
     */
    public function testToggleActionRemovesLikeOnSameState(): void
    {
        $item = $this->createItem();
        $this->createLike($item->id(), null, true);

        // Toggle same state.
        $this->dispatch('/admin/like/toggle', 'POST', [
            'resource_id' => $item->id(),
            'liked' => 'true',
        ]);

        $response = $this->getResponse();
        $json = json_decode($response->getBody(), true);

        $this->assertEquals('success', $json['status']);
        $this->assertEquals('deleted', $json['data']['action']);
        $this->assertNull($json['data']['liked']);
        $this->assertEquals(0, $json['data']['likes']);
    }

    /**
     * Test toggle action updates like to dislike.
     */
    public function testToggleActionUpdatesLike(): void
    {
        $item = $this->createItem();
        $this->createLike($item->id(), null, true);

        $this->dispatch('/admin/like/toggle', 'POST', [
            'resource_id' => $item->id(),
            'liked' => 'false',
        ]);

        $response = $this->getResponse();
        $json = json_decode($response->getBody(), true);

        $this->assertEquals('success', $json['status']);
        $this->assertEquals('updated', $json['data']['action']);
        $this->assertFalse($json['data']['liked']);
        $this->assertEquals(0, $json['data']['likes']);
        $this->assertEquals(1, $json['data']['dislikes']);
    }

    /**
     * Test delete redirect on GET.
     *
     * Note: We can't test full delete flow because api()->read() uses DQL.
     */
    public function testDeleteActionRedirectsOnGet(): void
    {
        $this->dispatch('/admin/like/1/delete');
        $this->assertRedirectTo('/admin/like');
    }

    /**
     * Test batch delete requires POST.
     */
    public function testBatchDeleteRequiresPost(): void
    {
        $this->dispatch('/admin/like/batch-delete');
        $this->assertRedirectTo('/admin/like');
    }

    /**
     * Test batch delete requires selection.
     */
    public function testBatchDeleteRequiresSelection(): void
    {
        $this->dispatch('/admin/like/batch-delete', 'POST', [
            'confirmform_csrf' => $this->getCsrf(),
        ]);
        $this->assertRedirectTo('/admin/like');
    }

    /**
     * Get CSRF token for forms.
     */
    protected function getCsrf(): string
    {
        $csrf = new \Laminas\Form\Element\Csrf('confirmform_csrf');
        return $csrf->getValue();
    }
}
