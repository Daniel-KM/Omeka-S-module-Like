<?php declare(strict_types=1);

namespace 🖒Test\Form;

use Laminas\Form\Form;
use Omeka\Test\AbstractHttpControllerTestCase;
use 🖒\Form\SettingsFieldset;

class SettingsFieldsetTest extends AbstractHttpControllerTestCase
{
    protected $fieldset;

    public function setUp(): void
    {
        parent::setUp();

        $services = $this->getApplication()->getServiceManager();
        $formElementManager = $services->get('FormElementManager');

        $this->fieldset = $formElementManager->get(SettingsFieldset::class);
    }

    public function testFieldsetHasCorrectLabel(): void
    {
        $this->assertEquals('👍', $this->fieldset->getLabel());
    }

    public function testFieldsetHasIdAttribute(): void
    {
        $this->assertEquals('like', $this->fieldset->getAttribute('id'));
    }

    public function testFieldsetHasElementGroups(): void
    {
        $elementGroups = $this->fieldset->getOption('element_groups');
        $this->assertIsArray($elementGroups);
        $this->assertArrayHasKey('🖒', $elementGroups);
    }

    public function testFieldsetHasResourcesElement(): void
    {
        $this->assertTrue($this->fieldset->has('🖒_resources'));
        $element = $this->fieldset->get('🖒_resources');
        $this->assertEquals('Resources to enable likes on', $element->getLabel());

        $valueOptions = $element->getValueOptions();
        $this->assertArrayHasKey('items', $valueOptions);
        $this->assertArrayHasKey('item_sets', $valueOptions);
        $this->assertArrayHasKey('media', $valueOptions);
    }

    public function testFieldsetHasAllowDislikeElement(): void
    {
        $this->assertTrue($this->fieldset->has('🖒_allow_dislike'));
        $element = $this->fieldset->get('🖒_allow_dislike');
        $this->assertEquals('Allow 👎', $element->getLabel());
    }

    public function testFieldsetHasShowCountLikeElement(): void
    {
        $this->assertTrue($this->fieldset->has('🖒_show_count_like'));
        $element = $this->fieldset->get('🖒_show_count_like');
        $this->assertEquals('Show count of ❤️', $element->getLabel());
    }

    public function testFieldsetHasShowCountDislikeElement(): void
    {
        $this->assertTrue($this->fieldset->has('🖒_show_count_dislike'));
        $element = $this->fieldset->get('🖒_show_count_dislike');
        $this->assertEquals('Show count of 👎', $element->getLabel());
    }

    public function testFieldsetHasIconTypeElement(): void
    {
        $this->assertTrue($this->fieldset->has('🖒_icon_type'));
        $element = $this->fieldset->get('🖒_icon_type');
        $this->assertEquals('Icon style', $element->getLabel());

        $valueOptions = $element->getValueOptions();
        $this->assertArrayHasKey('unicode', $valueOptions);
        $this->assertArrayHasKey('fa', $valueOptions);
    }

    public function testFieldsetHasIconShapeElement(): void
    {
        $this->assertTrue($this->fieldset->has('🖒_icon_shape'));
        $element = $this->fieldset->get('🖒_icon_shape');
        $this->assertEquals('Icon shape', $element->getLabel());

        $valueOptions = $element->getValueOptions();
        $this->assertArrayHasKey('heart', $valueOptions);
        $this->assertArrayHasKey('thumb', $valueOptions);
        $this->assertArrayHasKey('reverse', $valueOptions);
        $this->assertArrayHasKey('thumb-reverse', $valueOptions);
        $this->assertArrayHasKey('reverse-thumb', $valueOptions);
    }

    public function testFieldsetHasAllowPublicViewElement(): void
    {
        $this->assertTrue($this->fieldset->has('🖒_allow_public_view'));
        $element = $this->fieldset->get('🖒_allow_public_view');
        $this->assertEquals('Anonymous visitor can view counts', $element->getLabel());
    }

    public function testFieldsetCanBeAttachedToForm(): void
    {
        $form = new Form();
        $form->add($this->fieldset, ['name' => 'like_settings']);

        $this->assertTrue($form->has('like_settings'));
    }

    public function testFieldsetElementsHaveCorrectIds(): void
    {
        $elements = [
            '🖒_resources',
            '🖒_allow_dislike',
            '🖒_show_count_like',
            '🖒_show_count_dislike',
            '🖒_icon_type',
            '🖒_icon_shape',
            '🖒_allow_public_view',
        ];

        foreach ($elements as $name) {
            $element = $this->fieldset->get($name);
            $this->assertEquals($name, $element->getAttribute('id'));
        }
    }
}
