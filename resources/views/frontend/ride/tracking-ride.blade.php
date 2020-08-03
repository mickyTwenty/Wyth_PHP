<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{{ constants('global.site.name') }} Ride Tracker</title>

  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

    <style type="text/css">
    html, body {
      margin: 0;
      padding: 0;
      background-color: #fff;
      color: #636b6f;
      font-family: 'Raleway', sans-serif;
      font-weight: 100;
      height: 100vh;
    }
    #map_canvas {
      position: absolute;
      top: 0;
      bottom: 0;
      width: 100%;
    }
    .full-height {
        height: 100vh;
    }
    .flex-center {
        align-items: center;
        display: flex;
        justify-content: center;
    }
    .infoMsg {
      font-size: 40px;
      text-align: center;
    }
    .m-b-md {
      margin-bottom: 30px;
    }

    .wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99;
        background: #fff;
        border-radius: 10px;
    }

    .box {
      width: 500px;
      margin: 0 auto;
      padding: 25px;
      border: dotted 3px #000;
      border-radius: 10px;
      max-height: 600px;
      overflow-y: auto;
    }
    .blockedOverlay {
      z-index: 90;
      height: 100%;
      position: absolute;
      width: 100%;
      background: #fff0;
    }
    h1 {
      margin-bottom: 20px;
      margin-top: 0px;
    }

    ul {
      margin: 0;
      padding: 0;
    }

    ul li + li {
      margin-top: 20px;
    }

    ul li {
      list-style: none;
    }

    h3 {
      color: #636b6f;
      margin: 0;
    }

    li span {
      font-weight: 600;
      font-family: 'Raleway', sans-serif;
      color: #000;
    }
    </style>

    <script type="text/javascript">
      var config = {
        firebase: {
          apiKey: "AIzaSyA5idXUGBTyBrDOwGgdPjCMjWp3fsj7PP8",
          authDomain: "wherr2-app-20904.firebaseapp.com",
          databaseURL: "https://wherr2-app-20904.firebaseio.com",
          projectId: "wherr2-app-20904",
          storageBucket: "wherr2-app-20904.appspot.com",
          messagingSenderId: "155249036426"
        },
        ride: {
          id: '{{ $rideId }}',
          userId: '{{ $userId }}',
          label: {
            text: '{{ $user->full_name }}',
            fontSize: "16px",
            fontWeight: "bold"
          },
          showDetails: {{ null === $showDetails ? 'false' : var_export($showDetails, true) }}
        }
      }
    </script>

</head>
<body>
  <div class="full-height flex-center">

      <div id="welcome" class="infoMsg m-b-md">
      @if (!$showDetails)
        Loading..
      @endif
      </div>
      <div id="map_canvas" style="display:none;@if($showDetails) -webkit-filter: blur(3px);filter: blur(3px); @endif"></div>
      <div id="hold_message" class="infoMsg m-b-md" style="display:none;">Please wait for the trip to start.</div>
    @if ($showDetails)
      <div class="blockedOverlay"></div>
      <div class="wrapper">
        <div class="box">
          <h1>Trip Detail</h1>
          <ul>
            <li>
              <h3>Driver</h3>
              <span>{{ $ride->trip->driver->full_name }}</span>
            </li>
            <li>
              <h3>Trip Name</h3>
              <span>{{ $ride->trip->trip_name }}</span>
            </li>
            <li>
              <h3>Origin</h3>
              <span>{{ $ride->origin_title }}</span>
            </li>
            <li>
              <h3>Destination</h3>
              <span>{{ $ride->destination_title }}</span>
            </li>
          @if ($tripMember)
            <li>
              <h3>Ride Duration</h3>
              <span>{{ Carbon\Carbon::parse($tripMember->dropped_at)->diffForHumans($tripMember->picked_at, true) }}</span>
            </li>
          @endif
          </ul>
        </div>
      </div>
    @endif
  </div>

  <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_API_KEY') }}"></script>
  <script src="https://www.gstatic.com/firebasejs/4.9.0/firebase.js"></script>
  <script src="https://www.gstatic.com/firebasejs/4.9.0/firebase-firestore.js"></script>
  <script src="{{ frontend_asset('js/markerAnimate.js') }}"></script>
  <script src="{{ frontend_asset('js/SlidingMarker.js') }}"></script>
  <script src="{{ frontend_asset('js/track.js') }}"></script>

</body>
</html>
