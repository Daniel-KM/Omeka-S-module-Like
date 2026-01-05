<?php declare(strict_types=1);

namespace 🖒\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
{
    protected $label = '🖒';

    protected $elementGroups = [
        '🖒' => '🖒',
    ];

    public function init(): void
    {
        $this
            ->setAttribute('id', 'like')
            ->setOption('element_groups', $this->elementGroups)

            ->add([
                'name' => '🖒_allow_🖓',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Allow 🖓', // @translate
                    'value_options' => [
                        '' => 'Use global setting', // @translate
                        '1' => 'Yes', // @translate
                        '0' => 'No', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_allow_🖓',
                ],
            ])
            ->add([
                'name' => '🖒_show_count_🖒',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Show count of 🖒', // @translate
                    'value_options' => [
                        '' => 'Use global setting', // @translate
                        '1' => 'Yes', // @translate
                        '0' => 'No', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_show_count_🖒',
                ],
            ])
            ->add([
                'name' => '🖒_show_count_🖓',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Show count of 🖓', // @translate
                    'value_options' => [
                        '' => 'Use global setting', // @translate
                        '1' => 'Yes', // @translate
                        '0' => 'No', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_show_count_🖓',
                ],
            ])
            ->add([
                'name' => '🖒_icon_type',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Icon style', // @translate
                    'value_options' => [
                        '' => 'Use global setting', // @translate
                        'unicode' => 'Unicode (emoji)', // @translate
                        'fa' => 'Font Awesome', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_icon_type',
                ],
            ])
        ;
    }
}
