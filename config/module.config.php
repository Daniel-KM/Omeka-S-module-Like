<?php declare(strict_types=1);

namespace 🖒;

return [
    'api_adapters' => [
        'invokables' => [
            'likes' => Api\Adapter\LikeAdapter::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            '🖒' => Service\ViewHelper\🖒Factory::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\SettingsFieldset::class => Form\SettingsFieldset::class,
            Form\SiteSettingsFieldset::class => Form\SiteSettingsFieldset::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\Admin\IndexController::class => Service\Controller\Admin\IndexControllerFactory::class,
            Controller\Site\IndexController::class => Service\Controller\Site\IndexControllerFactory::class,
            Controller\Site\GuestController::class => Service\Controller\Site\GuestControllerFactory::class,
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'like' => Site\Navigation\Link\Like::class,
        ],
    ],
    'resource_page_block_layouts' => [
        'invokables' => [
            '🖒Button' => Site\ResourcePageBlockLayout\🖒Button::class,
        ],
    ],
    'column_types' => [
        'factories' => [
            '🖒_count' => Service\ColumnType\🖒CountFactory::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Likes', // @translate
                'route' => 'admin/like',
                'controller' => Controller\Admin\IndexController::class,
                'action' => 'browse',
                'resource' => Controller\Admin\IndexController::class,
                'class' => 'o-icon- fa-thumbs-up',
                'admin_section' => 'users',
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'like' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/like[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => '🖒\Controller\Site',
                                'controller' => Controller\Site\IndexController::class,
                                'action' => 'toggle',
                            ],
                        ],
                    ],
                    // Guest integration: add routes under /guest/like.
                    'guest' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/guest',
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'like' => [
                                'type' => \Laminas\Router\Http\Segment::class,
                                'options' => [
                                    'route' => '/like[/:action]',
                                    'constraints' => [
                                        'action' => 'browse',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => '🖒\Controller\Site',
                                        'controller' => Controller\Site\GuestController::class,
                                        'action' => 'browse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'admin' => [
                'child_routes' => [
                    'like' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/like[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => '🖒\Controller\Admin',
                                'controller' => Controller\Admin\IndexController::class,
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'like-id' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/like/:id[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => '🖒\Controller\Admin',
                                'controller' => Controller\Admin\IndexController::class,
                                'action' => 'show',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => \Laminas\I18n\Translator\Loader\Gettext::class,
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'js_translate_strings' => [
        'You must be logged in to 🖒 resources.', // @translate
        'You cannot change your vote.', // @translate
        'An error occurred.', // @translate
        'An error occurred while processing your request.', // @translate
    ],
    '🖒' => [
        'settings' => [
            // Warning: mysql does not support to use two emojis in a id: 🖒_show_count_🖒 = 🖒_show_count_🖓.
            '🖒_resources' => [
                'items',
            ],
            '🖒_allow_dislike' => false,
            '🖒_show_count_like' => true,
            '🖒_show_count_dislike' => false,
            '🖒_icon_type' => 'unicode',
            '🖒_icon_shape' => 'heart',
            '🖒_allow_change_vote' => true,
            '🖒_allow_public_view' => true,
            '🖒_allow_anonymous' => false,
        ],
        'site_settings' => [
            '🖒_placement' => [],
            '🖒_allow_dislike' => '',
            '🖒_show_count_like' => '',
            '🖒_show_count_dislike' => '',
            '🖒_icon_type' => '',
            '🖒_icon_shape' => '',
            '🖒_allow_change_vote' => '',
            '🖒_allow_public_view' => '',
            '🖒_allow_anonymous' => '',
            '🖒_guest_widget_label' => '',
            '🖒_guest_link_label' => '',
            '🖒_guest_page_title' => '',
        ],
    ],
];
