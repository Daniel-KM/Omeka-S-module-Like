<?php declare(strict_types=1);

namespace 🖒\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class SettingsFieldset extends Fieldset
{
    protected $label = '👍';

    protected $elementGroups = [
        '🖒' => '👍',
    ];

    public function init(): void
    {
        $this
            ->setAttribute('id', 'like')
            ->setOption('element_groups', $this->elementGroups)

            ->add([
                'name' => '🖒_resources',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Resources to enable likes on', // @translate
                    'value_options' => [
                        'items' => 'Items', // @translate
                        'item_sets' => 'Item sets', // @translate
                        'media' => 'Media', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_resources',
                ],
            ])
            ->add([
                'name' => '🖒_allow_dislike',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Allow 👎', // @translate
                ],
                'attributes' => [
                    'id' => '🖒_allow_dislike',
                ],
            ])
            ->add([
                'name' => '🖒_show_count_like',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Show count of ❤️', // @translate
                ],
                'attributes' => [
                    'id' => '🖒_show_count_like',
                ],
            ])
            ->add([
                'name' => '🖒_show_count_dislike',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Show count of 👎', // @translate
                ],
                'attributes' => [
                    'id' => '🖒_show_count_dislike',
                ],
            ])
            ->add([
                'name' => '🖒_icon_type',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Icon style', // @translate
                    'value_options' => [
                        'unicode' => 'Unicode (emoji)', // @translate
                        'fa' => 'Font Awesome', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_icon_type',
                ],
            ])
            ->add([
                'name' => '🖒_icon_shape',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Icon shape', // @translate
                    'value_options' => [
                        'heart' => 'Heart (❤️/💔)', // @translate
                        'thumb' => 'Thumb (👍/👎)', // @translate
                        'reverse' => 'Reversed thumb (🖒/🖓)', // @translate
                        'thumb-reverse' => 'Thumb / Reversed (👍/🖓)', // @translate
                        'reverse-thumb' => 'Reversed / Thumb (🖒/👎)', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_icon_shape',
                ],
            ])
            ->add([
                'name' => '🖒_allow_public_view',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Anonymous visitor can view counts', // @translate
                ],
                'attributes' => [
                    'id' => '🖒_allow_public_view',
                ],
            ])
        ;
    }
}
