<?php declare(strict_types=1);

namespace 🖒\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
{
    protected $label = '👍';

    protected $elementGroups = [
        '🖒' => '👍',
        'themes_old' => 'Old themes', // @translate
    ];

    public function init(): void
    {
        $this
            ->setAttribute('id', 'like')
            ->setOption('element_groups', $this->elementGroups)

            ->add([
                'name' => '🖒_allow_dislike',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Allow 👎', // @translate
                    'value_options' => [
                        '' => 'Use global setting', // @translate
                        '1' => 'Yes', // @translate
                        '0' => 'No', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_allow_dislike',
                ],
            ])
            ->add([
                'name' => '🖒_show_count_like',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Show count of ❤️', // @translate
                    'value_options' => [
                        '' => 'Use global setting', // @translate
                        '1' => 'Yes', // @translate
                        '0' => 'No', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_show_count_like',
                ],
            ])
            ->add([
                'name' => '🖒_show_count_dislike',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Show count of 👎', // @translate
                    'value_options' => [
                        '' => 'Use global setting', // @translate
                        '1' => 'Yes', // @translate
                        '0' => 'No', // @translate
                    ],
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
                        '' => 'Use global setting', // @translate
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
                        '' => 'Use global setting', // @translate
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
                'name' => '🖒_allow_change_vote',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Allow users to change their vote', // @translate
                    'value_options' => [
                        '' => 'Use global setting', // @translate
                        '1' => 'Yes', // @translate
                        '0' => 'No', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_allow_change_vote',
                ],
            ])
            ->add([
                'name' => '🖒_allow_public_view',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Anonymous visitor can view counts', // @translate
                    'value_options' => [
                        '' => 'Use global setting', // @translate
                        '1' => 'Yes', // @translate
                        '0' => 'No', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_allow_public_view',
                ],
            ])
            ->add([
                'name' => '🖒_allow_anonymous',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Anonymous visitor can vote', // @translate
                    'value_options' => [
                        '' => 'Use global setting', // @translate
                        '1' => 'Yes', // @translate
                        '0' => 'No', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_allow_anonymous',
                ],
            ])

            // Guest integration settings.
            ->add([
                'name' => '🖒_guest_widget_label',
                'type' => \Laminas\Form\Element\Text::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Guest widget label', // @translate
                    'info' => 'Label shown in the guest dashboard widget. Default: "Likes".', // @translate
                ],
                'attributes' => [
                    'id' => '🖒_guest_widget_label',
                    'placeholder' => 'Likes', // @translate
                ],
            ])
            ->add([
                'name' => '🖒_guest_link_label',
                'type' => \Laminas\Form\Element\Text::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Guest link label', // @translate
                    'info' => 'Label for the link in the guest widget. Use %d for the count. Default: "My likes (%d)".', // @translate
                ],
                'attributes' => [
                    'id' => '🖒_guest_link_label',
                    'placeholder' => 'My likes (%d)', // @translate
                ],
            ])
            ->add([
                'name' => '🖒_guest_page_title',
                'type' => \Laminas\Form\Element\Text::class,
                'options' => [
                    'element_group' => '🖒',
                    'label' => 'Guest page title', // @translate
                    'info' => 'Title of the guest likes page. Default: "My Likes".', // @translate
                ],
                'attributes' => [
                    'id' => '🖒_guest_page_title',
                    'placeholder' => 'My Likes', // @translate
                ],
            ])

            ->add([
                'name' => '🖒_placement',
                'type' => CommonElement\OptionalMultiCheckbox::class,
                'options' => [
                    'element_group' => 'themes_old',
                    'label' => 'Like (old themes)', // @translate
                    'value_options' => [
                        'after/items' => 'Item show', // @translate
                        'after/media' => 'Media show', // @translate
                        'after/item_sets' => 'Item set show', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => '🖒_placement',
                    'required' => false,
                ],
            ])
        ;
    }
}
