<?php

use app\models\User;
use app\modules\admin\models\RideRequest;
use app\modules\admin\models\Restaurant;
use yii\helpers\Url;
?>
<style>
  hr {
    margin-top: 1rem;
    margin-bottom: 1rem;
    border: 0;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
  }
</style>
<script src="https://kit.fontawesome.com/c939d0e917.js"></script>
<script>
  $(function() {
    $('.modal').modal();
    $('#modal3').modal('open');
    $('#modal3').modal('close');
  });
</script>
<div id="modal3" class="modal bottom-sheet">
  <div class="modal-content">
    <h4>Live Drivers</h4>
    <div class="row">
      <div class="col xl6 m6 s12">
        <div class="drivers_blk">
          <ul class="collection" id="order_assign">


          </ul>
        </div>
      </div>
      <div class="col xl6 m6 s12">

      </div>
      <div class="col xl6 m6 s12">
        <div class="drivers_lv_lcn_blk">
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$locations = [];
$orders = RideRequest::find()
  ->Where(['status' => RideRequest::STATUS_RIDE_BOOKED])
  ->andWhere(['or', 'status' => RideRequest::STATUS_CANCEL_BY_DRIVER])
  ->andWhere(['like', 'updated_on', date('Y-m-d')])->orderBy([
    'id' => SORT_DESC
  ])->all();
if (!empty($orders)) {
  foreach ($orders as $f) {
    // var_dump($f->restaurant);exit;

    $lat = $f->lat;
    $lng = $f->lng;
    $locations[] = array(
      'lat' => floatval($lat),
      'lng' => floatval($lng),
      'type' => 'order',
      'id' => $f->id,
      'contact_phone' => $f->delivery_contact_no,
      'name' => $f->delivery_name,
      'contact_phone' => $f->delivery_contact_no,
      'delivery_time' => $f->delivery_time,

      'address' => $f->delivery_address,

      'restaurant_name' => $f->restaurant['name'],
      'restaurant_lat' => $f->restaurant['lat'],
      'restaurant_lng' => $f->restaurant['long'],
      'restaurant_address' => $f->restaurant['street'] . " " . $f->restaurant['city'] . " " . $f->restaurant['state'],

    );
    //  'lat' => $f->lat, 'lng' => $f->lang
  }
}

$locations = json_encode($locations, true);
$allDrivers = [];
$allOrders = [];
$drivers = User::find()->Where(['user_role' => 'Driver'])->andWhere(['online_status' => User::ONLINE])->all();
// var_dump($drivers);exit;
if (!empty($drivers)) {
  foreach ($drivers as $f) {

    $driver_count = RideRequest::find()
      ->Where(['driver_id' => $f->id])
      ->andWhere(
        [
          'and',
          ['!=', 'status', RideRequest::STATUS_RIDE_COMPLETED],
          ['!=', 'status', RideRequest::STATUS_CANCEL_BY_DRIVER],


        ]
      )

      ->andWhere(['like', 'created_on', date('Y-m-d')])
      ->distinct()
      ->count();

    $lat = $f->lat;
    $lng = $f->lng;
    $allDrivers[] = array(
      'lat' => floatval($lat),
      'lng' => floatval($lng),
      'type' => 'driver',
      'id' => $f->id,
      'name' => $f->first_name,
      'phone' => $f->contact_no,
      'orders_count' => $driver_count,
      // 'orders'=>$driver_orders,




    );
  }
}


?>

<div class="section" id="faq">
  <div class="row">
    <div class="col s3">

      <div class="card" style="
    height: 500px;
    overflow: scroll;
">
        <div class="card-content">
          <h4 class="card-title">Live Rides</h4>

          <div class="stores-list-container">
            <div class="stores-list" id="stores-list">

            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="col s12 m6 l6 card" id="new_orders" style="
height: 500px;
overflow: hidden;
overflow-y: scroll;
">
      <div id="map" style="height:500px;" class="col s12"></div>


    </div>

    <div class="col s3">



      <div class="card" style="
height: 500px;
overflow: scroll;
">
        <div class="card-content">
          <h4 class="card-title">Live Drivers</h4>

          <div class="stores-list-container">
            <div class="drivers-list" id="drivers-list">

            </div>
          </div>
        </div>
      </div>

    </div>


    <script>
      var map;
      var markers = [];
      var coords = [];
      var foundStores = [];
      var foundDrivers = [];

      var infoWindow;

      $(function() {
        $('#main').addClass('main-full');
        $('#collapse_side_nav').removeClass();
        $('#collapse_side_nav').addClass('sidenav-main nav-collapsible sidenav-light sidenav-active-square nav-collapsed');
      });

      function initMap() {
        var losAngeles = {
          lat: 34.063380,
          lng: -118.358080
        };
        map = new google.maps.Map(document.getElementById("map"), {
          center: losAngeles,
          zoom: 14,
          mapTypeId: "roadmap",
        });
        infoWindow = new google.maps.InfoWindow();
        searchStores();
      }

      function searchStores() {
        // alert('hello');
        var locations = <?php echo $locations; ?>;

        foundStores = locations;


        displayStores(foundStores);
        showStoresMarkers(foundStores);
        setOnClickListener();
      }

      function setOnClickListener() {
        var storeElements = document.querySelectorAll('.store-container');
        storeElements.forEach(function(element, index) {
          element.addEventListener('click', function() {
            console.log(markers[index]);
            console.log(index);
            new google.maps.event.trigger(markers[index], "click");
          })
        })
      }

      function displayStores(foundStores) {
        var storesHtml = '';
        for (var [index, store] of foundStores.entries()) {
          var address = store['address'];
          var phone = store['contact_phone'];
          var name = store['name'];
          var orderId = store['id'];
          var restaurant_name = store['restaurant_name'];
          var restaurant_lat = store['restaurant_lat'];
          var restaurant_lng = store['restaurant_lng'];
          var restaurant_address = store['restaurant_address'];
          storesHtml += `

            <div class="chat-user animate fadeUp delay-1">
                            <div class="chat-header user-section ">
                              <div class="row valign-wrapper">

                                <div class="col s12">

                                  <a href="https://rushwheelz.com/admin/order/view?id=${orderId}"><p class="m-0 blue-grey-text text-darken-4 font-weight-700">
                                  Order id #  ${orderId}</p></a>
                                  <span class="new badge store-container" data-badge-caption="View on Map"></span>

                                  <p class="m-0 chat-text ">${address}</p>
                                  <div class="card-action">
                                            <a class="waves-effect waves-light btn modal-trigger mb-2" href="#modal3" data-id="${orderId}" onclick="assign_order(${orderId})">Assign Driver</a>                                                <!-- <a href="" class="waves-effect waves-light btn gradient-45deg-red-pink">Assign Order</a> -->
                                            </div>
                                </div>
                              </div>
                            </div>

                         </div>

                         <hr/>
    `
          // document.querySelector('.stores-list').innerHTML = storesHtml;
          document.getElementById("stores-list").innerHTML = storesHtml;
        }
      }

      function showStoresMarkers(stores) {
        var bounds = new google.maps.LatLngBounds();
        for (var [index, store] of stores.entries()) {
          var latlng = new google.maps.LatLng(
            store["lat"],
            store["lng"]);
          var name = store['name'];
          var address = store['address'];
          var openStatusText = "Open text";
          var phoneNumber = store['contact_no'];
          var restaurant_name = store['restaurant_name'];
          var restaurant_lat = store['restaurant_lat'];
          var restaurant_lng = store['restaurant_lng'];
          var restaurant_address = store['restaurant_address'];

          bounds.extend(latlng);
          createMarker(latlng, name, address, openStatusText, phoneNumber, index + 1,
            restaurant_name,
            restaurant_address,

          )
        }
        map.fitBounds(bounds);

      }

      function createMarker(latlng, name, address, openStatusText, phoneNumber, index, restaurant_name, restaurant_address) {
        var html = `
                <div class="chat-user animate fadeUp delay-1">
                            <div class="chat-header user-section ">
                              <div class="row valign-wrapper">
                                <div class="col s2 media-image online pe-0">
                                  <img src="https://img.icons8.com/external-flaticons-lineal-color-flat-icons/64/000000/external-restaurant-wayfinding-flaticons-lineal-color-flat-icons.png" alt="" class="circle z-depth-2 responsive-img">
                                </div>
                                <div class="col s10">
                                <p class="m-0 blue-grey-text text-darken-4 font-weight-300">Pickup Address: </p>
                                 <hr/>
                                  <p class="m-0 blue-grey-text text-darken-4 font-weight-700">${restaurant_name}</p>
                                  <p class="m-0 info-text">${restaurant_address}</p>
                                </div>
                              </div>
                            </div>

                         </div>
                         </br>
                         <hr/>
                         <div class="chat-user animate fadeUp delay-1">
                         <div class="user-section">
                              <div class="row valign-wrapper">
                                <div class="col s2 media-image online pe-0">
                                  <img src="https://img.icons8.com/cute-clipart/64/000000/home.png" alt="" class="circle z-depth-2 responsive-img">
                                </div>
                                <div class="col s10">
                                 <p class="m-0 blue-grey-text text-darken-4 font-weight-300">Delivery Address: </p>
                                 <hr/>
                                  <p class="m-0 blue-grey-text text-darken-4 font-weight-700">${name}</p>
                                  <p class="m-0 info-text">${address}</p>
                                </div>
                              </div>
                            </div>
                            </div>
  `
        var marker = new google.maps.Marker({
          map: map,
          position: latlng,
          label: index.toString(),
          icon: "https://img.icons8.com/external-flaticons-lineal-color-flat-icons/32/000000/external-order-bakery-flaticons-lineal-color-flat-icons-2.png",

        });
        google.maps.event.addListener(marker, 'click', function() {
          infoWindow.setContent(html);
          infoWindow.open(map, marker);
        });
        markers.push(marker);


      }

      // counter for online cars...
      var cars_count = 0;

      // markers array to store all the markers, so that we could remove marker when any car goes offline and its data will be remove from realtime database...
      var new_markers = [];
      // var map,marker;
      var speed = 50; // km/h

      // This Function will create a car icon with angle and add/display that marker on the map
      function AddCar(data) {

        var icon = { // car icon
          path: 'M270 400 c0 -5 10 -10 23 -11 17 -1 15 -3 -10 -10 -18 -5 -35 -14 -38 -19 -3 -6 -49 -10 -101 -10 -56 0 -94 -4 -94 -10 0 -5 8 -10 18 -10 10 0 35 -9 57 -20 22 -11 45 -20 52 -20 16 0 16 -5 1 -29 -12 -19 -12 -19 -36 0 -34 26 -78 24 -107 -6 -51 -50 -17 -135 54 -135 41 0 63 17 80 64 10 25 15 27 39 20 15 -4 38 -8 52 -8 21 -1 25 4 28 32 2 17 -1 32 -7 32 -5 0 -16 3 -25 6 -11 4 -16 1 -16 -10 0 -18 -8 -20 -29 -7 -20 13 5 55 27 45 37 -15 59 -15 71 0 18 22 26 20 39 -8 9 -19 8 -27 -4 -36 -8 -7 -14 -30 -14 -50 0 -72 85 -106 135 -55 52 51 17 135 -56 135 -21 0 -39 2 -39 4 0 2 -3 11 -6 20 -5 13 1 16 25 16 17 0 31 5 31 10 0 6 -17 10 -38 10 -34 0 -39 3 -51 35 -9 27 -18 35 -37 35 -13 0 -24 -4 -24 -10z m-145 -154 c19 -13 18 -15 -15 -23 -20 -5 -35 -16 -35 -24 0 -14 13 -15 53 -3 13 4 22 2 22 -5 0 -18 -39 -51 -60 -51 -26 0 -60 34 -60 60 0 25 34 60 58 60 10 0 27 -6 37 -14z m325 -6 c11 -11 20 -29 20 -40 0 -26 -34 -60 -60 -60 -41 0 -73 54 -52 87 6 9 13 4 26 -17 18 -32 30 -37 42 -18 4 7 -2 18 -14 26 -12 8 -22 21 -22 28 0 21 37 17 60 -6z',
          scale: 0.1,
          fillColor: "#ff7121", //<-- Car Color, you can change it
          fillOpacity: 1,
          strokeWeight: 1,
          anchor: new google.maps.Point(0, 5),
          rotation: data.val().angle //<-- Car angle
        };

        var uluru = {
          lat: parseFloat(data.val().lat),
          lng: parseFloat(data.val().lng)
        };
        // alert(uluru);
        var marker = new google.maps.Marker({
          position: uluru,
          icon: icon,
          map: map,
          title: 'RushWheelz Driver'
        });
        var orderId = [];
        var str = '';
        new_markers.push(marker);
        // if(data.val().id){
        $.ajax({
          type: "get",
          dataType: "JSON",
          url: "<?php echo Url::base() ?>/admin/users/get-driver?id=" + data.val().id,


          success: function(response) {
            var json = JSON.parse(JSON.stringify(response));
            console.log(data.val().id);
            console.log(json.model);
            $.each(json.model, function(key, value) {

              // orderId.push(value.id);
              // console.log(orderId);
              //   str += "<a href='<?php echo Url::base() ?>/admin/order/view?id="+value.id+"' target='_blank'><li>Order Id# "+value.id+"</li>"; 
              // $('ul#orders.firstHeading').append('<li>'+value.id+'</li>');
              str = `
     <a target='_blank' href='https://www.google.com/maps?z=15&daddr=${value.lat},${value.lng}'>Calculate route</a>
                <div class="chat-user animate">
                            <div class="chat-header user-section ">
                            <span class="new badge " data-id="363" data-badge-caption="${value.order_status}"></span>

                              <div class="row valign-wrapper">
                                <div class="col s12">
                                <p class="m-0 blue-grey-text text-darken-4 font-weight-700">Order id # ${value.id}</p>

                                <p class="m-0 blue-grey-text text-darken-4 font-weight-300">Pickup Address: </p>
                               
                                 <p class="m-0 blue-grey-text text-darken-4 font-weight-700">${value.restaurant_details.restaurant_name}</p>
                                  <p class="m-0 info-text">${value.restaurant_details.restaurant_address}</p>
                                 
                                </div>
                              </div>
                            </div>

                         </div>
                         </br>
                       
                         <div class="chat-user ">
                         <div class="user-section">
                              <div class="row valign-wrapper">
                               
                                <div class="col s12">
                                 <p class="m-0 blue-grey-text text-darken-4 font-weight-300">Delivery Address: </p>
                                
                                
                                 <p class="m-0 blue-grey-text text-darken-4 font-weight-700">${value.delivery_address}</p>
                                  <p class="m-0 info-text">${value.delivery_contact_no}</p>
                                </div>
                              </div>
                            </div>
                            </div>
                            <hr/>
  `
            });
            var contentString = '<div id="content">' +
              '<div id="siteNotice">' +
              '</div>' +

              '<h5 id="firstHeading" class="firstHeading">' + json.user.first_name + '</h5>' +
              '<h4 id="firstHeading" class="firstHeading">' + json.user.contact_no + '</h4>' +

              '<ul id="orders" class="firstHeading">' + str + '</ul>' +






              '</div>' +
              '</div>';
            var infowindow = new google.maps.InfoWindow({
              content: contentString,
              maxWidth: 400
            });

            marker.addListener('click', function() {

              // console.log(marker);
              infowindow.open(map, marker);
              map.setZoom(15);

              // map.setCenter(uluru);
            });

          }


        });
        // }

        // add marker in the markers array...



        new_markers[data.key] = marker; // add marker in the markers array...
        // document.getElementById("cars").innerHTML = cars_count;
      }

      // Live drivers: poll the backend (users.lat/lng, maintained by the API update-lat-lng call).
      var knownCars = {};
      function pollDriverLocations() {
        $.getJSON("<?php echo Url::to(['ride-request/driver-locations']) ?>", function(rows) {
          var seen = {};
          rows.forEach(function(r) {
            var snap = { key: String(r.id), val: function() { return { id: r.id, lat: r.lat, lng: r.lng, angle: 0 }; } };
            seen[snap.key] = true;
            if (knownCars[snap.key]) {
              new_markers[snap.key].setMap(null);
              AddCar(snap);
            } else {
              cars_count++;
              AddCar(snap);
              searchDrivers();
            }
            knownCars[snap.key] = true;
          });
          Object.keys(knownCars).forEach(function(k) {
            if (!seen[k]) {
              new_markers[k].setMap(null);
              delete knownCars[k];
              cars_count--;
            }
          });
          var el = document.getElementById("cars");
          if (el) { el.innerHTML = cars_count; }
        });
      }
      pollDriverLocations();
      setInterval(pollDriverLocations, 10000);


      function searchDrivers() {
        // alert('hello');
        var drivers = <?php echo json_encode($allDrivers); ?>;

        foundDrivers = drivers;


        displayDrivers(foundDrivers);
        //   showStoresMarkers(foundStores);
        setOnClickDriverListener();
      }

      function displayDrivers(foundDrivers) {
        var storesHtml = '';
        for (var [index, store] of foundDrivers.entries()) {
          // var address = store['address'];
          // var phone = store['contact_phone'];
          var name = store['name'];
          var phone = store['contact_no'];
          var orders_count = store['orders_count'];
          var orderId = store['id'];
          var orders = store['orders'];
          // var restaurant_name = store['restaurant_name'];
          // var restaurant_lat = store['restaurant_lat'];
          // var restaurant_lng = store['restaurant_lng'];
          // var restaurant_address = store['restaurant_address'];

          storesHtml += `

            <div class="chat-user animate fadeUp delay-1">
                            <div class="chat-header user-section ">
                              <div class="row valign-wrapper">

                                <div class="col s12">
                                 <div class="col s8">
                                  <p class="m-0 blue-grey-text text-darken-4 font-weight-700">
                                    ${name}</p>

                                </div>

                                <span class="new badge driver-container"  data-id="${orderId}" data-badge-caption="View on Map"></span>

                                  </div>
                                </div>
                                <ul class="list-unstyled">

</ul>
                                <div class="row">
                                <div class="col s12">
                                <p class="m-0 blue-grey-text text-darken-4 font-weight-700">
                               On going Orders :  ${orders_count}</p>
                               </div>
                               </div>



                         </div>

                         <hr/>
    `
          // document.querySelector('.stores-list').innerHTML = storesHtml;
          document.getElementById("drivers-list").innerHTML = storesHtml;
        }
      }
      $(document).on('click', '.driver-container', function(e) {
        // alert("For button"+$(this).data('id'));
        var driverId = $(this).data('id');
        new google.maps.event.trigger(new_markers[driverId], "click");

      });

      function setOnClickDriverListener() {

        var storeElements = document.querySelectorAll('.driver-container');
        storeElements.forEach(function(element, index) {
          element.addEventListener('click', function() {
            console.log(index);
            console.log(new_markers);
            new google.maps.event.trigger(new_markers[index], "click");

          })
        })
      }

      function assign_order(id) {

        $.ajax({
          type: "GET",
          url: "<?= Url::toRoute(['order/get-delivery-boy']) ?>",
          data: {
            id: id
          },
          cache: false,

          success: function(data) {


            if (data) {
              $('#order_assign').replaceWith($('#order_assign').html(getdeliveryboy(data, id)));
            } else {

            }
          }
        });
      }

      function getdeliveryboy(data, id) {

        html = '';
        $.each(data.delivery_boy, function(index, value) {
          //  html+='<option value="'+value["id"]+'">'+value["full_name"]+'</option>';

          html += '<li class="collection-item avatar">';
          html += '<img src="../../../app-assets/images/avatar/avatar-7.png" alt="" class="circle">';
          html += '<span class="title">' + value["first_name"] + ' ' + value["middle_name"] + ' ' + value["last_name"] + ' </span></br>';
          html += +value["contact_no"] + '</p>';
          html += '<a  data-order-id = ' + id + '  data-driver-id =' + value["id"] + ' class="secondary-content waves-effect waves-light  btn gradient-45deg-amber-amber box-shadow-none border-round me-1 mb-1">Assign</a>';
          html += '</li>';
        });

        return html;
      }
    </script>

    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= \app\modules\geo\services\GoogleKeys::browserKey() ?>&callback=initMap">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script>
      $(document).on('click', '.secondary-content', function() {
        $(document).ajaxStart(function() {
          $("#wait").css("display", "none");
        });
        var order_id = $(this).attr("data-order-id");
        var driver_id = $(this).attr("data-driver-id");
        // alert(order_id);
        $.ajax({
          type: "get",
          dataType: "JSON",
          url: "<?php echo Url::base() ?>/api/user/assign-order?id=" + order_id + "&driver_id=" + driver_id,
          success: function(response) {
            var json = JSON.parse(JSON.stringify(response));
            console.log(json.status);
            if (json.status == 'NOK') {
              swal("sorry", "Driver Not Assigned", "error");
              $('#modal3').modal('close');
              //alert('NOK');
            } else {
              swal("", "DriverAssigned", "success")
              //alert('OK');
              $('#modal3').modal('close');
            }


          }
        });
        $(document).ajaxComplete(function() {
          $("#wait").css("display", "none");
        });
        //   $("#txt").load("demo_ajax_load.asp");

      });
      $(document).on('click', '.secondary-content', function() {
        $(document).ajaxStart(function() {
          $("#wait").css("display", "none");
        });
        var order_id = $(this).attr("data-order-id");
        var driver_id = $(this).attr("data-driver-id");
        // alert(order_id);
        $.ajax({
          type: "get",
          dataType: "JSON",
          url: "<?php echo Url::base() ?>/api/user/assign-order?id=" + order_id + "&driver_id=" + driver_id,
          success: function(response) {
            var json = JSON.parse(JSON.stringify(response));
            console.log(json.status);
            if (json.status == 'NOK') {
              swal("sorry", "Driver Not Assigned", "error");
              $('#modal3').modal('close');
              //alert('NOK');
            } else {
              swal("", "DriverAssigned", "success")
              //alert('OK');
              $('#modal3').modal('close');
            }


          }
        });
        $(document).ajaxComplete(function() {
          $("#wait").css("display", "none");
        });
        //   $("#txt").load("demo_ajax_load.asp");

      });

      $(function() {
        $('aside').addClass('sidebar-mini');
      });
    </script>
