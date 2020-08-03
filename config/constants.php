<?php

return [

    // Project related constants
    'global' => [
        'site' => [
            'name' => 'WYTH',
            'tinyName' => 'WYTH',
            'version' => '1.0', // For internal code comparison (if any)
        ],

        'ride' => [
            'point_buffer' => 80468, // 50 miles (value in meter),
            'previous_search' => 1000, // 50 miles (value in meter),

            'driver_earning' => '75', // in percentage
            'payout_charges' => '5', // in percentage (expedited for)
        ],

        'encryption' => [
            'key' => '57238004e784498bbc2f8bf984565090',
        ],
    ],

    'states' => [
        'Alabama'                        => 'AL',
        'Alaska'                         => 'AK',
        'Arizona'                        => 'AZ',
        'Arkansas'                       => 'AR',
        'California'                     => 'CA',
        'Colorado'                       => 'CO',
        'Connecticut'                    => 'CT',
        'Delaware'                       => 'DE',
        'Florida'                        => 'FL',
        'Georgia'                        => 'GA',
        'Hawaii'                         => 'HI',
        'Idaho'                          => 'ID',
        'Illinois'                       => 'IL',
        'Indiana'                        => 'IN',
        'Iowa'                           => 'IA',
        'Kansas'                         => 'KS',
        'Kentucky'                       => 'KY',
        'Louisiana'                      => 'LA',
        'Maine'                          => 'ME',
        'Maryland'                       => 'MD',
        'Massachusetts'                  => 'MA',
        'Michigan'                       => 'MI',
        'Minnesota'                      => 'MN',
        'Mississippi'                    => 'MS',
        'Missouri'                       => 'MO',
        'Montana'                        => 'MT',
        'Nebraska'                       => 'NE',
        'Nevada'                         => 'NV',
        'New Hampshire'                  => 'NH',
        'New Jersey'                     => 'NJ',
        'New Mexico'                     => 'NM',
        'New York'                       => 'NY',
        'North Carolina'                 => 'NC',
        'North Dakota'                   => 'ND',
        'Ohio'                           => 'OH',
        'Oklahoma'                       => 'OK',
        'Oregon'                         => 'OR',
        'Pennsylvania'                   => 'PA',
        'Rhode Island'                   => 'RI',
        'South Carolina'                 => 'SC',
        'South Dakota'                   => 'SD',
        'Tennessee'                      => 'TN',
        'Texas'                          => 'TX',
        'Utah'                           => 'UT',
        'Vermont'                        => 'VT',
        'Virginia'                       => 'VA',
        'Washington'                     => 'WA',
        'West Virginia'                  => 'WV',
        'Wisconsin'                      => 'WI',
        'Wyoming'                        => 'WY',
        'American Samoa'                 => 'AS',
        'District of Columbia'           => 'DC',
        'Federated States of Micronesia' => 'FM',
        'Guam'                           => 'GU',
        'Marshall Islands'               => 'MH',
        'Northern Mariana Islands'       => 'MP',
        'Palau'                          => 'PW',
        'Puerto Rico'                    => 'PR',
        'Virgin Islands'                 => 'VI',
    ],

    // Related to web-services
    'api' => [
        'config' => [
            'allowSingleDeviceLogin' => false,
            'sendHiddenLogoutPush' => false,

            'defaultPaginationLimit' => 20,
        ],

        'separator' => '-,-',

        'global' => [
            'formats' => [
                'date' => 'm/d/Y',
                'time' => 'H:i',
                'datetime' => 'j M, Y H:i',
            ],
        ],
    ],

    // Related to backend

    // Directory Constants
    'back' => [

        'theme' => [
            'configuration' => [
                'show_navigation_messages' => false,
                'show_navigation_notifications' => false,
                'show_navigation_flags' => false,
            ],

            'modules' => [
                'date_format' => 'j F, Y',
                'datetime_format' => 'j M Y, h:i:s A',
                'time_format' => 'h:i:s A',

                'tiny_loader' => 'backend/assets/dist/img/tiny-loader.gif',
            ],
        ],

        'sidebar' => [
            'menu' => [
                [
                    'label' => 'Dashboard',
                    'path' => '/dashboard',
                    'icon' => 'fa fa-dashboard',
                ],
                'users' => [
                    'label' => 'Users Management',
                    'path' => '/users',
                    'regexPath' => '%(/users(/edit/\d+|/index)?)|(/user-stats(/detail/\d+|/index)?)%',
                    'icon' => 'fa fa-users',
                    'submenu' => [
                        [
                            'label' => 'All Users',
                            'path' => '/users/index',
                            'icon' => 'fa fa-users',
                            'regexPath' => '%/users(/index|/detail/\d+|/purchases/\d+)?|(/user-stats(/detail/\d+|/index)?)$%',
                        ],
                        [
                            'label' => 'Push Notification',
                            'path' => '/users/push-notification',
                            'icon' => 'fa fa-paper-plane',
                            'regexPath' => '%/users(/push-notification)$%',
                        ],
                    ],
                ],
                'trips' => [
                    'label' => 'Trips',
                    'path' => '/trips',
                    'regexPath' => '%(/trips(/payments|/canceled)?)%',
                    'icon' => 'fa fa-cab',
                    'submenu' => [
                        [
                            'label' => 'Trip Payments',
                            'path' => '/trips/payments',
                            'regexPath' => '%(/trips/payments(/detail/\d+)?)$%',
                            'icon' => 'fa fa-dollar',
                        ],
                        [
                            'label' => 'Canceled Trips',
                            'path' => '/trips/canceled',
                            'regexPath' => '%(/trips/canceled)$%',
                            'icon' => 'fa fa-ban',
                        ],
                        [
                            'label' => 'Trips Listing',
                            'path' => '/trips/listing',
                            'regexPath' => '%(/trips/listing)$%',
                            'icon' => 'fa fa-list-alt',
                        ],
                        [
                            'label' => 'Hot Destinations',
                            'path' => '/trips/hot-destinations',
                            'regexPath' => '%(/trips/hot-destinations)$%',
                            'icon' => 'fa fa-map-marker',
                        ],
                    ],
                ],
                'coupons' => [
                    'label' => 'Promo Codes',
                    'path' => '/promo-codes/index',
                    'regexPath' => '%/promo-codes(/index|/create|/edit/\d+)?$%',
                    'icon' => 'fa fa-money',
                ],
                'reviews' => [
                    'label' => 'Reviews & Ratings',
                    'path' => '/reviews/index',
                    'regexPath' => '%(/reviews/index)$%',
                    'icon' => 'fa fa-star',
                ],
                'reports' => [
                    'label' => 'Reports & Analytics',
                    'path' => '/reports',
                    'regexPath' => '%(/reports(/dashboard|/car/statistics|/popular/driver|/driver/earning)?)%',
                    'icon' => 'fa fa-bar-chart',
                    'submenu' => [
                        [
                            'label' => 'Dashboard',
                            'path' => '/reports/dashboard',
                            'regexPath' => '%(/reports/dashboard)$%',
                            'icon' => 'fa fa-dashboard',
                        ],
                        [
                            'label' => 'Car Statistics',
                            'path' => '/reports/car/statistics',
                            'regexPath' => '%(/reports/car/statistics)$%',
                            'icon' => 'fa fa-taxi',
                        ],
                        [
                            'label' => 'Popular Driver',
                            'path' => '/reports/popular/driver',
                            'regexPath' => '%(/reports/popular/driver)$%',
                            'icon' => 'fa fa-user',
                        ],
                        [
                            'label' => 'Driver Earning',
                            'path' => '/reports/driver/earning',
                            'regexPath' => '%(/reports/driver/earning)$%',
                            'icon' => 'fa fa-dollar',
                        ]
                    ],
                ],
                'settings' => [
                    'label' => 'SYSTEM SETTINGS',
                    'type' => 'heading',
                ],
                'editsettings' => [
                    'label' => 'Settings',
                    'path' => '/system/edit-settings',
                    'regexPath' => '%(/system/edit-settings)%',
                    'icon' => 'fa fa-cog',
                ],
                'schools' => [
                    'label' => 'Colleges',
                    'path' => '/schools/index',
                    'regexPath' => '%/schools(/index|/create|/edit/\d+)?$%',
                    'icon' => 'fa fa-building',
                ],
                'faqs' => [
                    'label' => 'FAQs',
                    'path' => '/faqs/index',
                    'regexPath' => '%/faqs(/index|/create|/edit/\d+)?$%',
                    'icon' => 'fa fa-question-circle',
                ],
                // 'agreement' => [
                //     'label' => 'User Agreement',
                //     'path' => '/system/user-agreement',
                //     'regexPath' => '%/system/user-agreement$%',
                //     'icon' => 'fa fa-file-word-o',
                // ],
                'editprofile' => [
                    'label' => 'Profile Setting',
                    'path' => '/system/edit-profile',
                    'regexPath' => '%(/system/edit-profile)%',
                    'icon' => 'fa fa-pencil-square-o',
                ],

                // Dummy Menu
                // Key will help to identify whether to display this menu on specific role based user or not (optional)
                'dummyEntry' => [
                    'label' => 'Dummy Entry',
                    'path' => '/dummy-entry',
                    'regexPath' => '%(/dummy-entry(/edit/\d+|/create)?)%',
                    'icon' => 'fa fa-users',
                    'submenu' => [
                        [
                            'label' => 'View All',
                            'path' => '/dummy-entry/index',
                            'icon' => 'fa fa-users',
                        ],
                        [
                            'label' => 'Create',
                            'path' => '/dummy-entry/create',
                            'icon' => 'fa fa-users',
                            'regexPath' => false,
                        ],
                    ],
                    'populate' => false,
                ],
            ],
        ],
    ],

    // Related to frontend
    'front' => [
        'dir' => [
            'profilePicPath'    =>  'frontend/images/profile/',
            'userDocumentsPath' =>  'frontend/users/documents/',
        ],

        'default' => [
            'profilePic'        =>  'default.jpg',
        ],
    ],

];
