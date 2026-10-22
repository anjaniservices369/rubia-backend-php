<?php 
use yii\helpers\Html;?>

<aside class="main-sidebar">

	<section class="sidebar">

		<!-- Sidebar user panel -->
		<div class="user-panel">
			<div class="pull-left image">
                <?=\Yii::$app->user->identity->profileImage( [ "class" => "img-circle","alt" => "User Image" ] )?>
            </div>
			<div class="pull-left info">
				<p><?= \Yii::$app->user->identity->full_name ?></p>
			</div>
            <div class="pull-right">
                                <?=Html::a ( '<i class="fa fa-power-off"></i>',[ '/user/logout' ], [ 'data-method' => 'post','class' => 'btn btn-default btn-flat','title'=>'LOGOUT' ] )?>
                            </div>
		</div>


        <?php
         if(\Yii::$app->user->identity->role_id==\app\models\User::ROLE_ADMIN){
        echo dmstr\widgets\Menu::widget([
            'options' => [
                'class' => 'sidebar-menu tree',
                'data-widget' => 'tree'
            ],
            'items' => [
                [
                    'label' => 'Menu',
                    'options' => [
                        'class' => 'header'
                    ]
                ],
                [
                    'label' => \yii::t('app', 'Users'),
                    'icon' => 'users',
                    'url' => '#',
                    'items' => [
                        [
                            'label' => \yii::t('app', 'All'),
                            'icon' => 'users',
                            'url' => [
                                '/user'
                            ]
                        ],
                        [
                            'label' => \yii::t('app', 'Users'),
                            'icon' => 'handshake-o',
                            'url' => [
                                '/user/all-user'
                            ]
                        ],
                        [
                            'label' => \yii::t('app', 'Merchants'),
                            'icon' => 'handshake-o',
                            'url' => [
                                '/user/merchant'
                            ]
                        ],
                        [
                            'label' => \yii::t('app', 'Delivery Boys'),
                            'icon' => 'file-code-o',
                            'url' => [
                                '/user/delivery-boy'
                            ]
                        ],
                        [
                            'label' => \yii::t('app', 'Managers'),
                            'icon' => 'file-code-o',
                            'url' => [
                                '/user/manager'
                            ]
                        ],
                       
                    ]
                ],
               
                // [
                //     'label' => 'Media',
                //     'icon' => 'picture-o',
                //     'url' => [
                //         '/media/'
                //     ]
                // ],
                [
                    'label' => 'Restaurant Timings',
                    'icon' => 'clock',
                    'url' => [
                        '/restaurant-timings'
                    ]
                ],
                [
                    'label' => 'Banner',
                    'icon' => 'picture-o',
                    'url' => [
                        '/banner'
                    ]
                ],
                [
                    'label' => \yii::t('app','City'),
                    'icon' => 'bold',
                    'url' => [
                        '/city'
                    ]
                ],
                [
                    'label' => \yii::t('app', 'Restaurant Module'),
                    'icon' => 'share',
                    'url' => '#',
                    'items' => [
                        [
                            'label' => \yii::t('app', 'Cuisine'),
                            'icon' => 'server',
                            'url' => [
                                '/category'
                            ]
                        ],
                        [
                            'label' => \yii::t('app', 'Restaurant'),
                            'icon' => 'handshake-o',
                            'url' => [
                                '/restaurant'
                            ]
                        ],
                        [
                            'label' => \yii::t('app', 'Menu'),
                            'icon' => 'file-code-o',
                            'url' => [
                                '/menu'
                            ]
                        ],
                        [
                            'label' => \yii::t('app', 'Menu Items'),
                            'icon' => 'bold',
                            'url' => [
                                '/menu-items'
                            ]
                        ],
                       

                    ]
                ],
               /* [
                    'label' => \yii::t('app', 'Delivery boy Orders'),
                    'icon' => 'bold',
                    'url' => [
                        '/driver-assigned'
                    ]
                ],*/

                [
                    'label' => \yii::t('app', 'Delivery boy Orders'),
                    'icon' => 'share',
                    'url' => '#',
                    'items' => [
                        [
                            'label' => \yii::t('app', 'Restaurant Orders'),
                            'icon' => 'server',
                            'url' => [
                                '/driver-assigned/restaurant-order'
                            ]
                        ],
                      
                       
                     
                    ]
                ],
               
                [
                    'label' => \yii::t('app', 'Restaurant Orders'),
                    'icon' => 'share',
                    'url' => '#',
                    'items' => [
                        [
                            'label' => \yii::t('app', 'All Orders'),
                            'icon' => 'server',
                            'url' => [
                                '/order'
                            ]
                        ],
                      
                        [
                            'label' => \yii::t('app', 'New Orders'),
                            'icon' => 'handshake-o',
                            'url' => [
                                '/order/new-orders'
                            ]
                        ],
                        [
                            'label' => \yii::t('app', 'On the way orders'),
                            'icon' => 'bold',
                            'url' => [
                                '/order/on-the-way-orders'
                            ]
                        ],
                      
                        [
                            'label' => \yii::t('app', 'Orders history'),
                            'icon' => 'bold',
                            'url' => [
                                '/order/order-history'
                            ]
                        ],
                        [
                            'label' => \yii::t('app', 'Cancelled Order '),
                            'icon' => 'file-code-o',
                            'url' => [
                                '/order/cancelled-orders'
                            ]
                        ],
                       


                    ]
                ],
              
               
                [
                    'label' => 'Add Restaurant Payments',
                    'icon' => 'ticket',
                    'url' => [
                        '/cashback-transaction/add-payment'
                    ]
                ],
               
                
                [
                    'label' => 'Coupons',
                    'icon' => 'ticket',
                    'url' => [
                        '/coupon'
                    ]
                ],
                


                [
                    'label' => 'Settings',
                    'icon' => 'fas fa-cogs',
                    'url' => [
                        '/setting/cms'
                    ]
                ],
                [
                    'label' => 'Admin Earning',
                    'icon' => 'fas fa-cogs',
                    'url' => [
                        '/comissions/admin-earning'
                    ]
                ],
                [
                    'label' => 'Merchant Earning',
                    'icon' => 'fas fa-cogs',
                    'url' => [
                        '/comissions/index'
                    ]
                ],
                [
                    'label' => \yii::t('app', 'Transactions'),
                    'icon' => 'share',
                    'url' => '#',
                    'items' => [
                        [
                            'label' => \yii::t('app', 'Signup Bonus'),
                            'icon' => 'server',
                            'url' => [
                                '/cashback-transaction/signup-bonus'
                            ]
                        ],
                      
                        [
                            'label' => \yii::t('app', 'Reffral Bonus'),
                            'icon' => 'handshake-o',
                            'url' => [
                                '/cashback-transaction/referral-bonus'
                            ]
                        ],
                        [
                            'label' => \yii::t('app', 'Paid Cashback'),
                            'icon' => 'handshake-o',
                            'url' => [
                                '/cashback-transaction/pending-cashback'
                            ]
                        ],
                        [
                            'label' => \yii::t('app', 'ApprovedCashback'),
                            'icon' => 'handshake-o',
                            'url' => [
                                '/cashback-transaction/approved-cashback'
                            ]
                        ],
                     
                    ]
                ],
               /* [
                    'label' => 'Pages',
                    'icon' => 'fas fa-cogs',
                    'url' => [
                        '/page'
                    ]
                ],*/
                // [
                //     'label' => \yii::t('app', 'Order'),
                //     'icon' => 'gift',
                //     'url' => '#',
                //     'items' => [
                //         [
                //             'label' => \yii::t('app', 'Order'),
                //             'icon' => 'first-order',
                //             'url' => [
                //                 '/order'
                //             ]
                //         ]
                    
                //     ]
                // ],
//
//                [
//                    'label' => \yii::t('app', 'Page'),
//                    'icon' => 'first-order',
//                    'url' => [
//                        '/page'
//                    ]
//                ]
           
            ]
        ])?>

        <?php } else if(\Yii::$app->user->identity->role_id==\app\models\User::ROLE_MERCHANT){?>


       <?php
             echo dmstr\widgets\Menu::widget([
                 'options' => [
                     'class' => 'sidebar-menu tree',
                     'data-widget' => 'tree'
                 ],
                 'items' => [
                     [
                         'label' => 'Menu',
                         'options' => [
                             'class' => 'header'
                         ]
                     ],
                    
                     [
                         'label' => \yii::t('app', 'Restaurant'),
                         'icon' => 'share',
                         'url' => '#',
                         'items' => [
                                 [
                                 'label' => \yii::t('app', 'Restaurant List'),
                                 'icon' => 'handshake-o',
                                 'url' => [
                                     '/restaurant'
                                 ]
                             ],
                             [
                                 'label' => \yii::t('app', 'Menu'),
                                 'icon' => 'file-code-o',
                                 'url' => [
                                     '/menu'
                                 ]
                             ],
                             [
                                 'label' => \yii::t('app', 'Menu Items'),
                                 'icon' => 'bold',
                                 'url' => [
                                     '/menu-items'
                                 ]
                             ],
                         		
                         		// [
                         		// 		'label' => \yii::t('app', 'Addon Types'),
                         		// 		'icon' => 'check',
                         		// 		'url' => [
                         		// 				'/addon-types'
                         		// 		]
                         		// ],


                         ]
                     ],
                   
                    //  [
                    //      'label' => \yii::t('app', 'Order'),
                    //      'icon' => 'gift',
                    //      'url' => '#',
                    //      'items' => [
                    //          [
                    //              'label' => \yii::t('app', 'Order'),
                    //              'icon' => 'first-order',
                    //              'url' => [
                    //                  '/order'
                    //              ]
                    //          ]

                    //      ]
                    //  ],
                    [
                        'label' => \yii::t('app', 'Delivery boy Orders'),
                        'icon' => 'bold',
                        'url' => [
                            '/driver-assigned'
                        ]
                    ],
                    [
                        'label' => \yii::t('app', 'Orders'),
                        'icon' => 'share',
                        'url' => '#',
                        'items' => [
                            [
                                'label' => \yii::t('app', 'Order'),
                                'icon' => 'server',
                                'url' => [
                                    '/order'
                                ]
                            ],
                            [
                                'label' => \yii::t('app', 'New Orders'),
                                'icon' => 'handshake-o',
                                'url' => [
                                    '/order/new-orders'
                                ]
                            ],
                            [
                                'label' => \yii::t('app', 'Cancelled Order '),
                                'icon' => 'file-code-o',
                                'url' => [
                                    '/order/cancelled-orders'
                                ]
                            ],
                            [
                                'label' => \yii::t('app', 'Orders history'),
                                'icon' => 'bold',
                                'url' => [
                                    '/order/order-history'
                                ]
                            ],
                            [
                                'label' => \yii::t('app', 'On the way orders'),
                                'icon' => 'bold',
                                'url' => [
                                    '/order/on-the-way-orders'
                                ]
                            ],
    
    
                        ]
                        ],
                        [
                            'label' => 'Merchant Earning',
                            'icon' => 'fas fa-cogs',
                            'url' => [
                                '/comissions/index'
                            ]
                        ],


                 ]
             ])


        ?>





        <?php }?>

    </section>

</aside>
