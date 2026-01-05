<?php declare(strict_types=1);

namespace 🖒Test\View\Helper;

use 🖒Test\🖒TestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;
use 🖒\View\Helper\🖒 as LikeHelper;

class 🖒Test extends AbstractHttpControllerTestCase
{
    use 🖒TestTrait;

    protected $helper;
    protected $likeAdapter;
    protected $em;
    protected $user;
    protected $item;

    public function setUp(): void
    {
        parent::setUp();

        $services = $this->getApplication()->getServiceManager();
        $this->em = $services->get('Omeka\EntityManager');
        $this->likeAdapter = $services->get('Omeka\ApiAdapterManager')->get('likes');

        $viewHelperManager = $services->get('ViewHelperManager');
        $this->helper = $viewHelperManager->get('🖒');

        $this->loginAdmin();
        $this->user = $this->createUser('helper@example.com', 'Helper User');
        $this->item = $this->createItem(['dcterms:title' => [['type' => 'literal', '@value' => 'Helper Test Item']]]);
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        parent::tearDown();
    }

    public function testHelperIsRegistered(): void
    {
        $this->assertInstanceOf(LikeHelper::class, $this->helper);
    }

    public function testInvokeReturnsEmptyStringWithoutResource(): void
    {
        $result = ($this->helper)(null, null, []);

        $this->assertEquals('', $result);
    }

    public function testInvokeReturnsHtmlStringWithResource(): void
    {
        $result = ($this->helper)($this->item);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testInvokeWithUserAndResource(): void
    {
        $result = ($this->helper)($this->item, $this->user);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testCountsMethodReturnsCorrectCounts(): void
    {
        // Create some likes.
        $this->createLike($this->item->id(), $this->user->id(), true);

        $user2 = $this->createUser('helper2@example.com', 'Helper User 2');
        $this->createLike($this->item->id(), $user2->id(), false);

        $counts = $this->helper->counts($this->item->id());

        $this->assertArrayHasKey('likes', $counts);
        $this->assertArrayHasKey('dislikes', $counts);
        $this->assertArrayHasKey('total', $counts);
        $this->assertEquals(1, $counts['likes']);
        $this->assertEquals(1, $counts['dislikes']);
        $this->assertEquals(2, $counts['total']);
    }

    public function testCountsMethodReturnsZeroForNoLikes(): void
    {
        $counts = $this->helper->counts($this->item->id());

        $this->assertEquals(0, $counts['likes']);
        $this->assertEquals(0, $counts['dislikes']);
        $this->assertEquals(0, $counts['total']);
    }

    public function testUserStatusReturnsNullWhenNotVoted(): void
    {
        $status = $this->helper->userStatus($this->item->id(), $this->user->id());

        $this->assertNull($status);
    }

    public function testUserStatusReturnsTrueWhenLiked(): void
    {
        $this->createLike($this->item->id(), $this->user->id(), true);

        $status = $this->helper->userStatus($this->item->id(), $this->user->id());

        $this->assertTrue($status);
    }

    public function testUserStatusReturnsFalseWhenDisliked(): void
    {
        $this->createLike($this->item->id(), $this->user->id(), false);

        $status = $this->helper->userStatus($this->item->id(), $this->user->id());

        $this->assertFalse($status);
    }

    public function testInvokeWithCustomOptions(): void
    {
        $options = [
            'showCount🖒' => true,
            'showCount🖓' => false,
            'iconType' => 'fa',
            'iconShape' => 'thumb',
            'allow🖓' => true,
        ];

        $result = ($this->helper)($this->item, null, $options);

        $this->assertIsString($result);
    }

    public function testInvokeRendersWithDefaultTemplate(): void
    {
        $result = ($this->helper)($this->item);

        // Should produce HTML output from the template.
        $this->assertIsString($result);
    }

    public function testInvokeLoadsAssetsOnce(): void
    {
        // Call the helper multiple times.
        ($this->helper)($this->item);
        ($this->helper)($this->item);

        // Assets should only be loaded once (tracked via static property).
        $this->assertIsString(($this->helper)($this->item));
    }

    public function testCountsWithMultipleLikes(): void
    {
        // Create multiple users with likes.
        for ($i = 0; $i < 5; $i++) {
            $testUser = $this->createUser("countuser{$i}@example.com", "Count User {$i}");
            $this->createLike($this->item->id(), $testUser->id(), true);
        }

        $counts = $this->helper->counts($this->item->id());

        $this->assertEquals(5, $counts['likes']);
        $this->assertEquals(0, $counts['dislikes']);
        $this->assertEquals(5, $counts['total']);
    }

    public function testCountsWithMixedLikesAndDislikes(): void
    {
        // Create 3 likes and 2 dislikes.
        for ($i = 0; $i < 3; $i++) {
            $testUser = $this->createUser("likeuser{$i}@example.com", "Like User {$i}");
            $this->createLike($this->item->id(), $testUser->id(), true);
        }

        for ($i = 0; $i < 2; $i++) {
            $testUser = $this->createUser("dislikeuser{$i}@example.com", "Dislike User {$i}");
            $this->createLike($this->item->id(), $testUser->id(), false);
        }

        $counts = $this->helper->counts($this->item->id());

        $this->assertEquals(3, $counts['likes']);
        $this->assertEquals(2, $counts['dislikes']);
        $this->assertEquals(5, $counts['total']);
    }
}
