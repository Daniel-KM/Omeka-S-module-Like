<?php declare(strict_types=1);

namespace 🖒\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Form;

class QuickSearchForm extends Form
{
    public function init(): void
    {
        $this->setAttribute('method', 'get');
        $this->setAttribute('id', 'quick-search-form');

        // No csrf: see main search form.
        $this->remove('csrf');

        $this
            ->add([
                'name' => 'liked',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Status', // @translate
                    'label_attributes' => [
                        'style' => 'display: inline; margin-right: 1em;',
                    ],
                    'value_options' => [
                        '' => 'All', // @translate
                        '0' => 'Disliked', // @translate
                        '1' => 'Liked', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'liked',
                    'value' => '',
                ],
            ])
            ->add([
                'name' => 'resource_id',
                'type' => CommonElement\OptionalNumber::class,
                'options' => [
                    'label' => 'Resource by id', // @translate
                ],
                'attributes' => [
                    'id' => 'resource_id',
                ],
            ])
            ->add([
                'name' => 'owner_id',
                'type' => CommonElement\OptionalNumber::class,
                'options' => [
                    'label' => 'User by id', // @translate
                ],
                'attributes' => [
                    'id' => 'owner_id',
                ],
            ])
            ->add([
                'name' => 'submit',
                'type' => Element\Button::class,
                'options' => [
                    'label' => 'Search', // @translate
                ],
                'attributes' => [
                    'id' => 'submit',
                    'type' => 'submit',
                    'class' => 'button',
                ],
            ]);
    }
}
