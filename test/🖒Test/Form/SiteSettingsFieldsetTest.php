<?php declare(strict_types=1);

namespace 🖒Test\Form;

use Laminas\Form\Form;
use Omeka\Test\AbstractHttpControllerTestCase;
use 🖒\Form\SiteSettingsFieldset;

class SiteSettingsFieldsetTest extends AbstractHttpControllerTestCase
{
    protected $fieldset;

    public function setUp(): void
    {
        parent::setUp();

        $services = $this->getApplication()->getServiceManager();
        $formElementManager = $services->get('FormElementManager');

        $this->fieldset = $formElementManager->get(SiteSettingsFieldset::class);
    }

    public function testFieldsetHasCorrectLabel(): void
    {
        $this->assertEquals('👍', $this->fieldset->getLabel());
    }

    public function testFieldsetHasIdAttribute(): void
    {
        $this->assertEquals('like', $this->fieldset->getAttribute('id'));
    }

    public function testFieldsetHasAllowDislikeElement(): void
    {
        $this->assertTrue($this->fieldset->has('🖒_allow_dislike'));
        $element = $this->fieldset->get('🖒_allow_dislike');
        $this->assertEquals('Allow 👎', $element->getLabel());

        $valueOptions = $element->getValueOptions();
        $this->assertArrayHasKey('', $valueOptions);
        $this->assertArrayHasKey('1', $valueOptions);
        $this->assertArrayHasKey('0', $valueOptions);
        $this->assertEquals('Use global setting', $valueOptions['']);
    }

    public function testFieldsetHasShowCountLikeElement(): void
    {
        $this->assertTrue($this->fieldset->has('🖒_show_count_like'));
        $element = $this->fieldset->get('🖒_show_count_like');
        $this->assertEquals('Show count of ❤️', $element->getLabel());

        $valueOptions = $element->getValueOptions();
        $this->assertArrayHasKey('', $valueOptions);
        $this->assertEquals('Use global setting', $valueOptions['']);
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
        $this->assertArrayHasKey('', $valueOptions);
        $this->assertArrayHasKey('unicode', $valueOptions);
        $this->assertArrayHasKey('fa', $valueOptions);
        $this->assertEquals('Use global setting', $valueOptions['']);
    }

    public function testFieldsetHasIconShapeElement(): void
    {
        $this->assertTrue($this->fieldset->has('🖒_icon_shape'));
        $element = $this->fieldset->get('🖒_icon_shape');
        $this->assertEquals('Icon shape', $element->getLabel());

        $valueOptions = $element->getValueOptions();
        $this->assertArrayHasKey('', $valueOptions);
        $this->assertArrayHasKey('heart', $valueOptions);
        $this->assertArrayHasKey('thumb', $valueOptions);
        $this->assertArrayHasKey('reverse', $valueOptions);
        $this->assertArrayHasKey('thumb-reverse', $valueOptions);
        $this->assertArrayHasKey('reverse-thumb', $valueOptions);
        $this->assertEquals('Use global setting', $valueOptions['']);
    }

    public function testSiteSettingsDoNotHaveResourcesElement(): void
    {
        // Resources element is only in global settings, not site settings.
        $this->assertFalse($this->fieldset->has('🖒_resources'));
    }

    public function testSiteSettingsDoNotHaveAllowPublicViewElement(): void
    {
        // Allow public view is only in global settings, not site settings.
        $this->assertFalse($this->fieldset->has('🖒_allow_public_view'));
    }

    public function testFieldsetCanBeAttachedToForm(): void
    {
        $form = new Form();
        $form->add($this->fieldset, ['name' => 'like_site_settings']);

        $this->assertTrue($form->has('like_site_settings'));
    }

    public function testAllElementsHaveGlobalSettingOption(): void
    {
        $elements = [
            '🖒_allow_dislike',
            '🖒_show_count_like',
            '🖒_show_count_dislike',
            '🖒_icon_type',
            '🖒_icon_shape',
        ];

        foreach ($elements as $name) {
            $element = $this->fieldset->get($name);
            $valueOptions = $element->getValueOptions();
            $this->assertArrayHasKey('', $valueOptions, "Element $name should have empty key for global setting option");
            $this->assertEquals('Use global setting', $valueOptions[''], "Element $name should have 'Use global setting' as default option");
        }
    }
}
