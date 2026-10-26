<?php

use app\modules\admin\models\base\Vehicals;
use app\modules\admin\models\User;
use yii\helpers\Html;
use yii\bootstrap5\Nav;
use app\modules\admin\widgets\Menu;
?>
<script>
    // Function to remove active state from other Pincode links
    function removeActiveStateFromOtherPincodes(clickedElementId) {
        const pincodeLinks = document.querySelectorAll('[id^="pincode-"]');
        pincodeLinks.forEach((link) => {
            if (link.id !== clickedElementId) {
                link.classList.remove('active');
            }
        });
    }

    // Attach click event to Pincode links
    const pincodeLinks = document.querySelectorAll('[id^="pincode-"]');
    pincodeLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            removeActiveStateFromOtherPincodes(event.target.id);
        });
    });
</script>

<!-- Main Sidebar Container -->
<aside class="app-sidebar shadow" data-bs-theme="dark">
	<!-- Brand Logo -->
	<a href="<?= Yii::$app->homeUrl ?>" class="brand-link">
		<img src="" style="display: block; margin: auto ;" class="img-fluid" width="70">
	</a>

	<!-- Sidebar -->
	<div class="sidebar-wrapper">
		<!-- Sidebar user panel (optional) -->
		<div class="user-panel mt-3 pb-3 mb-3 d-flex">
			<div class="image">
				<i class="fas fa-user-tie img-circle elevation-2"></i>
			</div>
			<div class="info flex-grow-1">
				<a href="#" class="d-block"><?= Yii::$app->name ?></a>
			</div>
			<div class="align-self-end">
				<?= Html::a(
        '<i class="fa fa-power-off"></i>',
        ['/site/logout'],
        [
            'title' => 'Sign Out',
            'data-method' => 'post',
            'class' => 'd-block',
            'style' => 'line-height: 2em;',
        ]
    ) ?>
              
              <?= Html::a(
    '<i class="fas fa-user-edit"></i> ', // Set your desired icon class here
    ['/admin/dashboard/update-profile', 'id' => Yii::$app->user->identity->id],
    [
        'class' => 'd-block', // Add any additional classes as needed
    ]
) ?>
              
              
			</div>
		</div>
		<!-- Sidebar Menu -->
		<nav class="mt-2">
			<?= Menu::widget([
       'options' => [
           'class' => 'nav sidebar-menu flex-column',
           'data-lte-toggle' => 'treeview',
           'role' => 'menu',
           'data-accordion' => 'false',
       ],
       'items' => [
           [
               'label' => 'Dashboard',
               'icon' => 'fas fa-tachometer-alt',
               'url' => ['/admin/dashboard'],
               'active' => 'dashboard' === Yii::$app->controller->id,
           ],
           [
               'label' => 'All services',
               'icon' => 'fas fa-sitemap',
               'url' => ['/admin/service-map/overview'],
               'visible' => empty(Yii::$app->params['serviceDashboard'])
                   && in_array(\Yii::$app->user->identity->user_role, [User::ROLE_ADMIN, User::ROLE_SUBADMIN], true),
           ],
           [
               'label' => 'Web Settings',
               'icon' => 'fas fa-cog',
               'url' => '#',
               'items' => [
                   [
                       'label' => 'Web Setting',
                       'url' => ['/admin/web-setting/cms'],
                       'options' => ['class' => 'bold'],
                   ],
               ],
               // Removed from the nav on every dashboard: its settings have moved to Integrations
               // (per-service pages plus the shared ones). The route/data are untouched, just unlinked.
               'visible' => false,
           ],
           [
               'label' => 'Dashboard Integrations',
               'icon' => 'fas fa-plug',
               'url' => ['/admin/integrations/index'],
               'active' => 'integrations' === Yii::$app->controller->id,
               // Deliberately kept on the main dashboard menu for a super admin even though every service
               // section below (Halting drivers, Auto/Bike/Car taxi) and the Customers section already carry
               // their own link to this same page: a super admin wants it here directly, not nested under
               // whichever service section happens to be open. Named "Dashboard Integrations" (rather than
               // the bare "Integrations" used everywhere else) so it reads as this distinct top-level entry.
               'visible' => \Yii::$app->user->identity->user_role == User::ROLE_ADMIN,
           ],
           [
               'label' => 'Manage Banners',
               'icon' => 'fas fa-image',
               'url' => '#',
               'items' => [
                   [
                       'label' => 'banner category',
                       'url' => ['/admin/banner-categorys'],
                       'options' => ['class' => 'bold'],
                   ],
 
                   [
                       'label' => 'Banners',
                       'url' => ['/admin/banner'],
                       'options' => ['class' => 'bold'],
                   ],
               ],
               // Assigned from the main super admin panel only, not from any individual service's own dashboard.
               'visible' => empty(\Yii::$app->params['serviceDashboard']) && empty(\Yii::$app->params['customerDashboard'])
                   && \Yii::$app->user->identity->user_role==User::ROLE_ADMIN,
           ],
           [
            'label' => 'Franchise',
            'icon' => 'fas fa-users',
            'url' => '#',
            'items' => [
                [
                    'label' => 'Franchises',
                    'url' => ['/admin/users/franchise'],
                    'options' => ['class' => 'bold'],
                ],
            ],
            'visible' => empty(\Yii::$app->params['customerDashboard']) && \Yii::$app->user->identity->user_role==User::ROLE_ADMIN,
        ],

           [
               'label' => 'Customers',
               'icon' => 'fas fa-users',
               'url' => '#',
               'items' => [
                   [
                       'label' => 'Customers-profile',
                       'url' => ['/admin/users'],
                       'options' => ['class' => 'bold'],
                   ],
                   [
                       'label' => 'Customer Map',
                       'url' => ['/admin/customer-map/index'],
                       'options' => ['class' => 'bold'],
                   ],
                   [
                       'label' => 'Cities',
                       // Same shared city list Halting drivers manages (CityController is not service-specific);
                       // added here too so a Customer app franchise/admin can see and manage cities without
                       // switching to another service's dashboard.
                       'url' => ['/admin/city'],
                       'options' => ['class' => 'bold'],
                   ],
                   [
                       'label' => 'Integrations',
                       // the Customer app holds the customers: its Google, SMS, push, e-mail and storage keys are the customers' own
                       'url' => ['/admin/integrations/index'],
                       'options' => ['class' => 'bold'],
                   ],
               ],
               // customers live in the Customer app service; the partner services (Halting drivers, taxis) hold their partners
               'visible' => (!empty(Yii::$app->params['customerDashboard']) || empty(Yii::$app->params['serviceDashboard'])) && \Yii::$app->user->identity->user_role==User::ROLE_ADMIN,
           ],


        [
            'label' => 'Services',
            'icon' => 'fas fa-taxi',
            'url' => ['/admin/services'],
            // Assigned from the main super admin panel only, not from any individual service's own dashboard.
            'visible' => empty(\Yii::$app->params['serviceDashboard']) && empty(\Yii::$app->params['customerDashboard'])
                && \Yii::$app->user->identity->user_role==User::ROLE_ADMIN,
        ],
        /// Driver Module
           [
            'label' => 'Halting drivers',
            'icon' => 'fas fa-users',
            'url' => '#',
            'items' => [
                [
                    'label' => 'Maps, operations and billing',
                    'icon' => 'fas fa-map-marked-alt',
                    'url' => ['/admin/service-map/index', 'module' => 1],
                ],

                [
                    'label' => 'Integrations',
                    'icon' => 'fas fa-plug',
                    'url' => ['/admin/integrations/index'],
                    'active' => 'integrations' === Yii::$app->controller->id,
                    'visible' => \Yii::$app->user->identity->user_role==User::ROLE_ADMIN,
                ],



                [
                    'label' => 'Drivers-profile',
                    'icon' => 'fas fa-users',
                    'url' => ['/admin/users/drivers'],
                ], 
     
     
     
                [
                    'label' => 'Vehicle Types',
                    'icon' => 'fas fa-car',
                    'url' => ['/admin/vehicle-types'],
                ],
     
                [
                    'label' => 'Coupon',
                    'icon' => 'fas fa-gift',
                    'url' => ['/admin/coupon'],
                    'active' => 'coupon' === Yii::$app->controller->id,
                ],
     
     
     
                [
                    'label' => 'Bookings',
                    'icon' => 'fas fa-truck-pickup',
                    'url' => '#',
                    'items' => [
                        [
                            'label' => 'Available Bookings',
                            'url' => ['/admin/booking'],
                            'options' => ['class' => 'bold'],
                        ],
     
     
                        [
                         'label' => 'Driver Accepted',
                         'url' => ['/admin/booking/driver-accepted'],
                         'options' => ['class' => 'bold'],
                     ],
     
                     [
                         'label' => 'Go For Pickup',
                         'url' => ['/admin/booking/go-for-pickup'],
                         'options' => ['class' => 'bold'],
                     ],
     
     
                     [
                         'label' => 'Arrived Location',
                         'url' => ['/admin/booking/arrived-location'],
                         'options' => ['class' => 'bold'],
                     ],
     
     
                     
     
                     [
                         'label' => 'Start Ride',
                         'url' => ['/admin/booking/start-ride'],
                         'options' => ['class' => 'bold'],
                     ],
      
     
                     [
                         'label' => 'Completed Ride',
                         'url' => ['/admin/booking/completed-ride'],
                         'options' => ['class' => 'bold'],
                     ],

                     [
                        'label' => 'Cancel Ride',
                        'url' => ['/admin/booking/cancel-ride'],
                        'options' => ['class' => 'bold'],
                    ],

                    [
                        'label' => 'Auto Canceled Bookings',
                        'url' => ['/admin/booking/auto-canceled-bookings'],
                        'options' => ['class' => 'bold'],
                    ],
                    
                    [
                        'label' => 'Driver Canceled Bookings',
                        'url' => ['/admin/booking/driver-cancel-duplicate/index'],
                        'options' => ['class' => 'bold'],
                    ],

                    [
                        'label' => 'Cancellation Charges',
                        'url' => ['/admin/cancel-ride-charges'],
                        'options' => ['class' => 'bold'],
                    ],

                    [
                        'label' => 'Payments',
                        'url' => ['/admin/payments'],
                        'options' => ['class' => 'bold'],
                    ],
     
     
                    ],
                ],
            
                [
                    'label' => 'City',
                    'icon' => 'fas fa-city',
                    'url' => ['/admin/city'],
                ],
     
                [
                    'label' => 'City rate card',
                    'icon' => 'fas fa-city',
                    'url' => ['/admin/city-rate-card'],
                  
                ],
             	// [
                 //   'label' => 'Rating and Reviews',
                 //   'icon' => 'fas fa-star',
                 //   'url' => ['/admin/driver-booking-ratings'],
             //   ],
     
      
     
                [
                 'label' => 'Plan',
                 'icon' => 'fas fa-city',
                 'url' => ['/admin/plans'],
                 'visible' => \Yii::$app->user->identity->user_role==User::ROLE_ADMIN
             ],

             [
                'label' => 'Active Subscriptions',
                'icon' => 'fas fa-certificate', // Use the "fa-certificate" icon for subscriptions
                'url' => ['/admin/driver-subscription/index'],
            ],
            [
                'label' => 'Inactive Subscriptions',
                'icon' => 'fas fa-certificate', // Use the "fa-certificate" icon for subscriptions
                'url' => ['/admin/driver-subscription-inactive/index'],
            ],
             [
                'label' => 'Driver Wallet',
                'icon' => 'fas fa-wallet', // Use the "fa-wallet" icon for the wallet
                'url' => ['/admin/driver-wallet/index'],
            ],

            [
                'label' => 'Driver Earnings',
                'icon' => 'fas fa-money-bill', // Use the "fa-money-bill" icon for Driver Earnings
                'url' => ['/admin/driver-earnings/index'],
            ],

            [
                'label' => 'Driver Referral Earnings',
                'icon' => 'fas fa-gift', // Use a gift icon
                'url' => ['/admin/driver-referral-earnings/index'],
            ],

            [
                'label' => 'Favourite Location',
                'icon' => 'fas fa-map-marker', // Use a map marker icon
                'url' => ['/admin/favourite-location/index'],
            ],



            // [
            //     'label' => 'Driver Canceled Bookings',
            //     'url' => ['/admin/driver-cancel-duplicate/index'],
            //     'options' => ['class' => 'bold'],
            // ],
            
            
       
     
     

            ],
            'visible' => (empty(\Yii::$app->params['serviceDashboard']) || (int) \Yii::$app->params['serviceDashboard'] === 1)
                && (\Yii::$app->user->identity->user_role==User::ROLE_ADMIN ? true : \Yii::$app->user->identity->vehicle_id==\app\modules\geo\services\ServiceModules::DRIVER),
        ],






        
        //// Taxi Module
        [
            'label' => 'Auto taxi',
            'icon' => 'fas fa-taxi',
            'url' => '#',
            'items' => [
                [
                    'label' => 'Maps, operations and billing',
                    'icon' => 'fas fa-map-marked-alt',
                    'url' => ['/admin/service-map/index', 'module' => 3],
                ],

                [
                    'label' => 'Integrations',
                    'icon' => 'fas fa-plug',
                    'url' => ['/admin/integrations/index'],
                    'active' => 'integrations' === Yii::$app->controller->id,
                    'visible' => \Yii::$app->user->identity->user_role==User::ROLE_ADMIN,
                ],



                [
                    'label' => 'Rider Details',
                    'icon' => 'fas fa-users',
                    'url' => ['/admin/driver-details','vehicle_id' => \app\modules\geo\services\ServiceModules::AUTO],
                    'id' => 'taxi-skipper-details',
                ], 
     



                [
                    'label' => 'Rides',
                    'icon' => 'fas fa-taxi',
                    'url' => '#',
                    'items' => [
                        [
                            'label' => 'All Rides',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-request','vehicle_id' =>  \app\modules\geo\services\ServiceModules::AUTO],
                            'id' => 'taxi-all-ride',
                        ],
      
                        [
                            'label' => 'New Rides',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-request/new-rides','vehicle_id' =>  \app\modules\geo\services\ServiceModules::AUTO],
                            'id' => 'taxi-new-ride',
                        ],

                        
                        [
                            'label' => 'On Going',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-request/on-going-rides','vehicle_id' =>  \app\modules\geo\services\ServiceModules::AUTO],
                            'id' => 'taxi-on-going-ride',
                        ],


                        
                        [
                            'label' => 'Completed Rides',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-request/completed-rides','vehicle_id' =>  \app\modules\geo\services\ServiceModules::AUTO],
                            'id' => 'taxi-on-complete-ride',
                        ],



                    ],
                ],
                [
                    'label' => 'Pincode',
                    'icon' => 'fas fa-city',
                    'url' => ['/admin/pincode','vehicle_id' =>  \app\modules\geo\services\ServiceModules::AUTO],
                    'id' => 'taxi-pincode',
                ],

                [
                    'label' => 'Ride Commission',
                    'icon' => 'fas fa-taxi',
                    'url' => ['/admin/ride-commision','vehicle_id' =>  \app\modules\geo\services\ServiceModules::AUTO],
                    'id' => 'car-taxi-commision',
                ], 


                [
                    'label' => 'Earning Management',
                    'icon' => 'fas fa-credit-card',
                    'url' => '#',
                    'items' => [
                        [
                            'label' => 'Ride Earnings',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-earnings','vehicle_id' =>  \app\modules\geo\services\ServiceModules::AUTO],
                            'id' => 'taxi-ride-earnings',
                        ],
                        
                        [
                            'label' => 'pending withdraw request',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/taxi-payout/pending-withdraw-request','vehicle_id' =>  \app\modules\geo\services\ServiceModules::AUTO],
                            'id' => 'taxi-pending-withdraw-request',
                        ],


                        [
                            'label' => 'approved withdraw request',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/taxi-payout/approved-withdraw-request','vehicle_id' =>  \app\modules\geo\services\ServiceModules::AUTO],
                            'id' => 'taxi-approved-withdraw-request',
                        ],



                        

                        [
                            'label' => 'rider amount',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/users/taxi-amount','vehicle_id' =>  \app\modules\geo\services\ServiceModules::AUTO],
                            'id' => 'taxi-taxi-amount',
                        ],


                        [
                            'label' => 'rider payout',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/taxi-payout','vehicle_id' =>  \app\modules\geo\services\ServiceModules::AUTO],
                            'id' => 'taxi-taxi-payout',
                        ],



                        
                        [
                            'label' => 'manual payout',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-earnings/manual-payout','vehicle_id' =>  \app\modules\geo\services\ServiceModules::AUTO],
                            'id' => 'taxi-ride-earnings-manual-payout',
                        ],









             


                 



                    ],
                ],







     
              
     
     
     
           
         
     
      
     
          
     
     
     

            ],
            'visible' => (empty(\Yii::$app->params['serviceDashboard']) || (int) \Yii::$app->params['serviceDashboard'] === 3)
                && (\Yii::$app->user->identity->user_role==User::ROLE_ADMIN ? true : \Yii::$app->user->identity->vehicle_id==\app\modules\geo\services\ServiceModules::AUTO),
        ],
        /// Bike Module
        [
            'label' => 'Bike taxi',
            'icon' => 'fas fa-motorcycle',
            'url' => '#',
            'items' => [
                [
                    'label' => 'Maps, operations and billing',
                    'icon' => 'fas fa-map-marked-alt',
                    'url' => ['/admin/service-map/index', 'module' => 4],
                ],

                [
                    'label' => 'Integrations',
                    'icon' => 'fas fa-plug',
                    'url' => ['/admin/integrations/index'],
                    'active' => 'integrations' === Yii::$app->controller->id,
                    'visible' => \Yii::$app->user->identity->user_role==User::ROLE_ADMIN,
                ],



                [
                    'label' => 'Rider Details',
                    'icon' => 'fas fa-users',
                    'url' => ['/admin/driver-details','vehicle_id' =>  \app\modules\geo\services\ServiceModules::BIKE],
                    'id' => 'bike-skipper-details',
                ], 
     



                [
                    'label' => 'Rides',
                    'icon' => 'fas fa-motorcycle',
                    'url' => '#',
                    'items' => [
                        [
                            'label' => 'All Rides',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-request','vehicle_id' => \app\modules\geo\services\ServiceModules::BIKE],
                            'id' => 'bike-ride',
                        ],
      
                        [
                            'label' => 'New Rides',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-request/new-rides','vehicle_id' => \app\modules\geo\services\ServiceModules::BIKE],
                            'id' => 'bike-new-ride',
                        ],

                        
                        [
                            'label' => 'On Going',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-request/on-going-rides','vehicle_id' => \app\modules\geo\services\ServiceModules::BIKE],
                            'id' => 'bike-on-going-ride',
                        ],


                        
                        [
                            'label' => 'Completed Rides',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-request/completed-rides','vehicle_id' => \app\modules\geo\services\ServiceModules::BIKE],
                            'id' => 'bike-complete-ride',
                        ],



                    ],
                ],
                [
                    'label' => 'Pincode',
                    'icon' => 'fas fa-city',
                    'url' => ['/admin/pincode','vehicle_id' => \app\modules\geo\services\ServiceModules::BIKE],
                    'id' => 'bike-pincode',
                ],

                [
                    'label' => 'Ride Commission',
                    'icon' => 'fas fa-motorcycle',
                    'url' => ['/admin/ride-commision','vehicle_id' => \app\modules\geo\services\ServiceModules::BIKE],
                    'id' => 'car-bike-commision',
                ], 


                [
                    'label' => 'Earning Management',
                    'icon' => 'fas fa-credit-card',
                    'url' => '#',
                    'items' => [
                        [
                            'label' => 'Ride Earnings',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-earnings','vehicle_id' => \app\modules\geo\services\ServiceModules::BIKE],
                            'id' => 'bike-ride-earnings',
                        ],
                        
                        [
                            'label' => 'pending withdraw request',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/taxi-payout/pending-withdraw-request','vehicle_id' => \app\modules\geo\services\ServiceModules::BIKE],
                            'id' => 'bike-pending-withdraw-request',
                        ],


                        [
                            'label' => 'approved withdraw request',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/taxi-payout/approved-withdraw-request','vehicle_id' => \app\modules\geo\services\ServiceModules::BIKE],
                            'id' => 'bike-approved-withdraw-request',
                        ],



                        

                        [
                            'label' => 'rider amount',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/users/taxi-amount','vehicle_id' => \app\modules\geo\services\ServiceModules::BIKE],
                            'id' => 'bike-taxi-amount',
                        ],


                        [
                            'label' => 'rider payout',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/taxi-payout','vehicle_id' => \app\modules\geo\services\ServiceModules::BIKE],
                            'id' => 'bike-taxi-payout',
                        ],



                        
                        [
                            'label' => 'manual payout',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-earnings/manual-payout','vehicle_id' => \app\modules\geo\services\ServiceModules::BIKE],
                            'id' => 'bike-ride-earnings-manual-payout',
                        ],









             


                 



                    ],
                ],

            ],
            'visible' => (empty(\Yii::$app->params['serviceDashboard']) || (int) \Yii::$app->params['serviceDashboard'] === 4)
                && (\Yii::$app->user->identity->user_role==User::ROLE_ADMIN ? true : \Yii::$app->user->identity->vehicle_id==\app\modules\geo\services\ServiceModules::BIKE),

        ],
        //// Car Module
        [
            'label' => 'Car taxi',
            'icon' => 'fas fa-taxi',
            'url' => '#',
            'items' => [
                [
                    'label' => 'Maps, operations and billing',
                    'icon' => 'fas fa-map-marked-alt',
                    'url' => ['/admin/service-map/index', 'module' => 2],
                ],

                [
                    'label' => 'Integrations',
                    'icon' => 'fas fa-plug',
                    'url' => ['/admin/integrations/index'],
                    'active' => 'integrations' === Yii::$app->controller->id,
                    'visible' => \Yii::$app->user->identity->user_role==User::ROLE_ADMIN,
                ],



                [
                    'label' => 'Rider Details',
                    'icon' => 'fas fa-users',
                    'url' => ['/admin/driver-details','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                    'id' => 'car-skipper-details',
                ], 
     



                [
                    'label' => 'Rides',
                    'icon' => 'fas fa-taxi',
                    'url' => '#',
                    'items' => [
                        [
                            'label' => 'All Rides',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-request','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                            'id' => 'car-ride',
                        ],
      
                        [
                            'label' => 'New Rides',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-request/new-rides','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                            'id' => 'car-new-ride',
                        ],

                        
                        [
                            'label' => 'On Going',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-request/on-going-rides','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                            'id' => 'car-on-going-ride',
                        ],


                        
                        [
                            'label' => 'Completed Rides',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-request/completed-rides','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                            'id' => 'car-complete-ride',
                        ],



                    ],
                ],
                [
                    'label' => 'Pincode',
                    'icon' => 'fas fa-city',
                    'url' => ['/admin/pincode','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                    'id' => 'car-pincode',
                ],

                [
                    'label' => 'Ride Commission',
                    'icon' => 'fas fa-taxi',
                    'url' => ['/admin/ride-commision','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                    'id' => 'car-ride-commision',
                ], 


                [
                    'label' => 'Earning Management',
                    'icon' => 'fas fa-credit-card',
                    'url' => '#',
                    'items' => [
                        [
                            'label' => 'Ride Earnings',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-earnings','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                            'id' => 'car-ride-earnings',
                        ],
                        
                        [
                            'label' => 'pending withdraw request',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/taxi-payout/pending-withdraw-request','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                            'id' => 'car-pending-withdraw-request',
                        ],


                        [
                            'label' => 'approved withdraw request',
                            'url' => ['/admin/taxi-payout/approved-withdraw-request'],
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-earnings','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                            'id' => 'car-ride-earnings',
                        ],



                        

                        [
                            'label' => 'rider amount',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/users/taxi-amount','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                            'id' => 'car-taxi-amount',
                        ],


                        [
                            'label' => 'rider payout',
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/taxi-payout','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                            'id' => 'car-taxi-payout',
                        ],



                        
                        [
                            'label' => 'manual payout',
                            'url' => ['/admin/ride-earnings/manual-payout'],
                            'options' => ['class' => 'bold'],
                            'url' => ['/admin/ride-earnings/manual-payout','vehicle_id' => \app\modules\geo\services\ServiceModules::TAXI],
                            'id' => 'car-ride-earnings-manual-payout',
                        ],









             


                 



                    ],
                ],








     
              
     
     
     
           
         
     
      
     
          
     
     
     

            ],
            'visible' => (empty(\Yii::$app->params['serviceDashboard']) || (int) \Yii::$app->params['serviceDashboard'] === 2)
                && (\Yii::$app->user->identity->user_role==User::ROLE_ADMIN ? true : \Yii::$app->user->identity->vehicle_id==\app\modules\geo\services\ServiceModules::TAXI),

        ],


































       ],
   ]) ?>
		</nav>
	</div>

</aside>