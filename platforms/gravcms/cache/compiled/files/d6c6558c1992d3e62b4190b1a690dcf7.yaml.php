<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledYamlFile',
    'filename' => '/var/www/html/platforms/gravcms/user/plugins/hax/blueprints.yaml',
    'modified' => 1547483380,
    'data' => [
        'name' => 'HAX',
        'version' => '1.1.2',
        'description' => 'HAX Headless authoring eXperience made available to GravCMS',
        'icon' => 'google',
        'author' => [
            'name' => 'Bryan Ollendyke',
            'email' => 'bto108@psu.edu',
            'url' => 'https://www.elmsln.org/'
        ],
        'homepage' => 'https://github.com/elmsln/grav-plugin-hax',
        'keywords' => 'webcomponents,hax,polymer,plugin',
        'bugs' => 'https://github.com/elmsln/grav-plugin-hax/issues',
        'license' => 'Apache 2.0',
        'dependencies' => [
            0 => 'admin',
            1 => 'atools',
            2 => 'webcomponents'
        ],
        'form' => [
            'validation' => 'strict',
            'fields' => [
                'enabled' => [
                    'type' => 'toggle',
                    'label' => 'Plugin Status',
                    'highlight' => 1,
                    'default' => 1,
                    'options' => [
                        1 => 'Enabled',
                        0 => 'Disabled'
                    ],
                    'validate' => [
                        'type' => 'bool'
                    ]
                ],
                'offset_left' => [
                    'type' => 'textfield',
                    'label' => 'Left offset',
                    'highlight' => 1,
                    'default' => 0,
                    'validate' => [
                        'type' => 'int'
                    ]
                ],
                'autoload_element_list' => [
                    'type' => 'textfield',
                    'label' => 'Elements to autoload (space separated)',
                    'highlight' => 1,
                    'default' => 'video-player wikipedia-query pdf-element lrn-table media-image',
                    'validate' => [
                        'type' => 'string'
                    ]
                ],
                'youtube_key' => [
                    'type' => 'textfield',
                    'label' => 'Youtube API Key',
                    'highlight' => 1,
                    'default' => '',
                    'validate' => [
                        'type' => 'string'
                    ]
                ],
                'memegenerator_key' => [
                    'type' => 'textfield',
                    'label' => 'Meme Generator API Key',
                    'highlight' => 1,
                    'default' => '',
                    'validate' => [
                        'type' => 'string'
                    ]
                ],
                'vimeo_key' => [
                    'type' => 'textfield',
                    'label' => 'Vimeo API Key',
                    'highlight' => 1,
                    'default' => '',
                    'validate' => [
                        'type' => 'string'
                    ]
                ],
                'giphy_key' => [
                    'type' => 'textfield',
                    'label' => 'Giphy API Key',
                    'highlight' => 1,
                    'default' => '',
                    'validate' => [
                        'type' => 'string'
                    ]
                ],
                'unsplash_key' => [
                    'type' => 'textfield',
                    'label' => 'Unsplash API Key',
                    'highlight' => 1,
                    'default' => '',
                    'validate' => [
                        'type' => 'string'
                    ]
                ],
                'flickr_key' => [
                    'type' => 'textfield',
                    'label' => 'Flickr API Key',
                    'highlight' => 1,
                    'default' => '',
                    'validate' => [
                        'type' => 'string'
                    ]
                ],
                'pixabay_key' => [
                    'type' => 'textfield',
                    'label' => 'Pixabay API Key',
                    'highlight' => 1,
                    'default' => '',
                    'validate' => [
                        'type' => 'string'
                    ]
                ]
            ]
        ]
    ]
];
