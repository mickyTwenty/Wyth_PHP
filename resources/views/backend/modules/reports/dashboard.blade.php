@extends( 'backend.layouts.app' )

@section('title', 'Dashboard')

@section('inlineJS')
@endsection

@section('JSLibraries')
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>{{ $moduleProperties['shortModuleName'] }}</h1>
    </section>

    <!-- Main content -->
    <section class="content">

    <div class="row">

        <div class="col-lg-4 col-md-6">
            <div class="panel panel-red">
                <div class="panel-heading">
                    <div class="row">
                        <a class="dashboard_link" href="javascript:;" style="cursor: default;">
                            <div class="col-xs-3">
                                <i class="fa fa-taxi fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div>Total Booking Count</div>
                                <div class="huge">{{ $stats->bookingCount }}</div>
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
                        <a class="dashboard_link" href="javascript:;" style="cursor: default;">
                            <div class="col-xs-3">
                                <i class="fa fa-clock-o fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div>Total Booking Duration</div>
                                <div class="huge">{{ $stats->bookingDuration }}</div>
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
                        <a class="dashboard_link" href="javascript:;" style="cursor: default;">
                            <div class="col-xs-3">
                                <i class="fa fa-tachometer fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div>Total Distance</div>
                                <div class="huge">{{ $stats->totalDistance }}</div>
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
                        <a class="dashboard_link" href="javascript:;" style="cursor: default;">
                            <div class="col-xs-3">
                                <i class="fa fa-dollar fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div>Cost/Mile</div>
                                <div class="huge">{{ $stats->farePerMile }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- <div class="col-lg-4 col-md-6">
            <div class="panel panel-red">
                <div class="panel-heading">
                    <div class="row">
                        <a class="dashboard_link" href="javascript:void(0);">
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
                        <a class="dashboard_link" href="javascript:void(0);">
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
                        <a class="dashboard_link" href="javascript:void(0);">
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
        </div> --}}

    </div>

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
@endsection
