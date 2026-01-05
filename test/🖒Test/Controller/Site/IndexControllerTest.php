<?php declare(strict_types=1);

namespace 🖒Test\Controller\Site;

use Omeka\Test\AbstractHttpControllerTestCase;
use 🖒Test\🖒TestTrait;

/**
 * Functional tests for the Like site controller.
 *
 * Tests the public-facing AJAX endpoints for liking/disliking resources.
 */
class IndexControllerTest extends AbstractHttpControllerTestCase
{
    use 🖒TestTrait;

    /**
     * @var string Default site slug for testing.
     */
    protected $siteSlug;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
        $this->setupSite();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        $this->logout();
        parent::tearDown();
    }

    /**
     * Setup a test site.
     */
    protected function setupSite(): void
    {
        // Try to get existing default site or create one.
        $response = $this->api()->search('sites', ['limit' => 1]);
        $sites = $response->getContent();

        if (!empty($sites)) {
            $site = $sites[0];
            $this->siteSlug = $site->slug();
        } else {
            // Create a test site.
            $response = $this->api()->create('sites', [
                'o:title' => 'Test Site',
                'o:slug' => 'test-site',
                'o:theme' => 'default',
                'o:is_public' => true,
            ]);
            $site = $response->getContent();
            $this->siteSlug = $site->slug();
        }
    }

    /**
     * Test toggle route is matched.
     */
    public function testToggleRouteMatches(): void
    {
        $item = $this->createItem();

        $this->dispatch('/s/' . $this->siteSlug . '/like/toggle', 'POST', [
            'resource_id' => $item->id(),
            'liked' => 'true',
        ]);

        $this->assertMatchedRouteName('site/like');

        // Clean up.
        $likes = $this->api()->search('likes', ['resource_id' => $item->id()])->getContent();
        foreach ($likes as $like) {
            $this->createdLikes[] = $like->id();
        }
    }

    /**
     * Test toggle action creates like.
     */
    public function testToggleActionCreatesLike(): void
    {
        $item = $this->createItem();

        $this->dispatch('/s/' . $this->siteSlug . '/like/toggle', 'POST', [
            'resource_id' => $item->id(),
            'liked' => 'true',
        ]);

        $json = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('success', $json['status']);
        $this->assertEquals('created', $json['data']['action']);
        $this->assertTrue($json['data']['liked']);
        $this->assertEquals(1, $json['data']['likes']);
        $this->assertEquals(0, $json['data']['dislikes']);

        // Clean up.
        $likes = $this->api()->search('likes', ['resource_id' => $item->id()])->getContent();
        foreach ($likes as $like) {
            $this->createdLikes[] = $like->id();
        }
    }

    /**
     * Test toggle action creates dislike.
     */
    public function testToggleActionCreatesDislike(): void
    {
        $item = $this->createItem();

        $this->dispatch('/s/' . $this->siteSlug . '/like/toggle', 'POST', [
            'resource_id' => $item->id(),
            'liked' => 'false',
        ]);

        $json = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('success', $json['status']);
        $this->assertEquals('created', $json['data']['action']);
        $this->assertFalse($json['data']['liked']);
        $this->assertEquals(0, $json['data']['likes']);
        $this->assertEquals(1, $json['data']['dislikes']);

        // Clean up.
        $likes = $this->api()->search('likes', ['resource_id' => $item->id()])->getContent();
        foreach ($likes as $like) {
            $this->createdLikes[] = $like->id();
        }
    }

    /**
     * Test toggle action updates existing like.
     */
    public function testToggleActionUpdatesExisting(): void
    {
        $item = $this->createItem();
        $this->createLike($item->id(), null, true);

        // Toggle to dislike.
        $this->dispatch('/s/' . $this->siteSlug . '/like/toggle', 'POST', [
            'resource_id' => $item->id(),
            'liked' => 'false',
        ]);

        $json = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('success', $json['status']);
        $this->assertEquals('updated', $json['data']['action']);
        $this->assertFalse($json['data']['liked']);
    }

    /**
     * Test toggle action removes like on same state.
     */
    public function testToggleActionRemovesOnSameState(): void
    {
        $item = $this->createItem();
        $this->createLike($item->id(), null, true);

        // Toggle same state (like again = remove).
        $this->dispatch('/s/' . $this->siteSlug . '/like/toggle', 'POST', [
            'resource_id' => $item->id(),
            'liked' => 'true',
        ]);

        $json = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('success', $json['status']);
        $this->assertEquals('deleted', $json['data']['action']);
        $this->assertNull($json['data']['liked']);
        $this->assertEquals(0, $json['data']['likes']);
    }

    /**
     * Test status action returns counts.
     */
    public function testStatusActionReturnsCounts(): void
    {
        $item = $this->createItem();

        // Create some likes from different users.
        $user = $this->getCurrentUser();
        $this->createLike($item->id(), $user->getId(), true);

        $testUser = $this->createUser('test@example.com', 'Test User');
        $this->createLike($item->id(), $testUser->id(), true);

        $testUser2 = $this->createUser('test2@example.com', 'Test User 2');
        $this->createLike($item->id(), $testUser2->id(), false);

        $this->dispatch('/s/' . $this->siteSlug . '/like/status?resource_id=' . $item->id());

        $json = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('success', $json['status']);
        $this->assertEquals(2, $json['data']['likes']);
        $this->assertEquals(1, $json['data']['dislikes']);
        $this->assertEquals(3, $json['data']['total']);
        $this->assertTrue($json['data']['isLoggedIn']);
        $this->assertTrue($json['data']['liked']); // Admin's like.
    }

    /**
     * Test status action with POST.
     */
    public function testStatusActionWithPost(): void
    {
        $item = $this->createItem();
        $this->createLike($item->id());

        $this->dispatch('/s/' . $this->siteSlug . '/like/status', 'POST', [
            'resource_id' => $item->id(),
        ]);

        $json = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('success', $json['status']);
        $this->assertEquals(1, $json['data']['likes']);
    }

    /**
     * Test toggle with null liked value removes like.
     */
    public function testToggleWithNullRemovesLike(): void
    {
        $item = $this->createItem();
        $this->createLike($item->id(), null, true);

        $this->dispatch('/s/' . $this->siteSlug . '/like/toggle', 'POST', [
            'resource_id' => $item->id(),
            'liked' => 'null',
        ]);

        $json = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('success', $json['status']);
        $this->assertEquals('deleted', $json['data']['action']);
    }

    /**
     * Test toggle with empty liked value removes like.
     */
    public function testToggleWithEmptyLikedRemovesLike(): void
    {
        $item = $this->createItem();
        $this->createLike($item->id(), null, true);

        $this->dispatch('/s/' . $this->siteSlug . '/like/toggle', 'POST', [
            'resource_id' => $item->id(),
            'liked' => '',
        ]);

        $json = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('success', $json['status']);
        $this->assertEquals('deleted', $json['data']['action']);
    }
}
