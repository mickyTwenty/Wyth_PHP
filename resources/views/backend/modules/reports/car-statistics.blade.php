@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
    <link href="{{ backend_asset('plugins/chosen/chosen.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
    <script src="{{ backend_asset('plugins/daterangepicker/moment.min.js') }}"></script>
    <script src="{{ backend_asset('plugins/chosen/chosen.jquery.min.js') }}"></script>
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>{{ $moduleProperties['longModuleName'] }}
      </h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @include( 'backend.layouts.notification_message' )

        <div class="box">
            <div class="box-header">
              <h3 class="box-title">Driver Filter</h3>
            </div>
            <div class="box-body">
                <div class="row">
                  <div class="col-md-4">
                    <form method="POST" id="driverFilter">
                        {{ csrf_field() }}
                          <label>Drivers</label>
                          {!! Form::select('driver_id', $drivers, null, ['class' => 'form-control chosen']) !!}
                    </form>
                  </div>
                </div>
            </div>
        </div>


        <div class="box">
            <div class="box-header">
              <h3 class="box-title">Car Statistics</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
            @if ($driver->id)
              <dl class="dl-horizontal viewModule">

                <dt>Driver ID</dt>
                <dd>{{ $driver->id }}</dd>

                <dt>Driver Name</dt>
                <dd>{{ $driver->full_name }}</dd>

                <dt>Ride Days</dt>
                <dd>{{ $rideDays }}</dd>

                <dt>Total Mileage</dt>
                <dd>{{ $distance }}</dd>

                <dt>Driver Earning</dt>
                <dd>{{ $driver_earning }}</dd>

              </dl>
            @else
                <p>No driver found!</p>
            @endif
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->

        @include( 'backend.layouts.modal' )

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
@endsection

@section('inlineJS')
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('select[name=driver_id]').on('change', function(event) {
                $('#driverFilter')[0].submit();
            });
        });
    </script>
@endsection
