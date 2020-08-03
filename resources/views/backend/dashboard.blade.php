@extends( 'backend.layouts.app' )

@section('title', 'Dashboard')

@section('inlineJS')
@endsection

@section('JSLibraries')
{{--     <script src="https://www.gstatic.com/firebasejs/4.2.0/firebase.js"></script>

<script type="text/javascript">
    var config = {
      apiKey: "AIzaSyBfQBYgCO9PFhq7baJfospBSLYwtoq3e74",
      databaseURL: "{{ env('FIREBASE_DATABASE_URL') }}"
    };
    firebase.initializeApp(config);

    firebase.database().ref('/system').on('value', function(snapshot) {
      var userObject = snapshot.val()

      $('#total_messages').text( userObject.hasOwnProperty('total_messages') ? userObject['total_messages'] : 0 )
    });

    var userRef = firebase.database().ref('/system').on('child_changed', function(snapshot, key) {
        var userObject = snapshot.val()

        $('#total_messages').text( userObject.hasOwnProperty('total_messages') ? userObject['total_messages'] : 0 )
    });
</script> --}}
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>Dashboard</h1>
      <ol class="breadcrumb">
        <li class="active"><a href=""><i class="fa fa-dashboard"></i> Dashboard</a></li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">

    <div class="row">

        <div class="col-lg-4 col-md-6">
            <div class="panel panel-red">
                <div class="panel-heading">
                    <div class="row">
                        <a class="dashboard_link" href="{{ URL::to('backend/users/index') }}">
                            <div class="col-xs-3">
                                <i class="fa fa-users fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div>Total Users</div>
                                <div class="huge">{{ $stats->total_users }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="panel panel-red">
                <div class="panel-heading">
                    <div class="row">
                        <a class="dashboard_link" href="{{ URL::to('backend/users/index?role_id=3') }}">
                            <div class="col-xs-3">
                                <i class="fa fa-cab fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div>Total Drivers</div>
                                <div class="huge">{{ $stats->total_drivers }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="panel panel-red">
                <div class="panel-heading">
                    <div class="row">
                        <a class="dashboard_link" href="{{ URL::to('backend/users/index?role_id=2') }}">
                            <div class="col-xs-3">
                                <i class="fa fa-user fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div>Total Passengers</div>
                                <div class="huge">{{ $stats->total_passengers }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="panel panel-red">
                <div class="panel-heading">
                    <div class="row">
                        <a class="dashboard_link" href="{{ URL::to('backend/users/index?email_verification=1') }}">
                            <div class="col-xs-3">
                                <i class="fa fa-check fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div>Total Verified Users</div>
                                <div class="huge">{{ $stats->total_verified_users }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
@endsection
