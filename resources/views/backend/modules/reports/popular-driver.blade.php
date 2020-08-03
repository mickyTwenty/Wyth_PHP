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
              <h3 class="box-title">Passenger Filter</h3>
            </div>
            <div class="box-body">
                <div class="row">
                  <div class="col-md-4">
                    <form method="POST" id="passengerFilter">
                        {{ csrf_field() }}
                          <label>Passengers</label>
                          {!! Form::select('passenger_id', ['' => 'Select Passenger'] + $passengers, null, ['class' => 'form-control chosen']) !!}
                    </form>
                  </div>
                </div>
            </div>
        </div>

        <div class="box">
            <div class="box-header">
              <h3 class="box-title">Most Used Driver</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <table class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>Driver</th>
                    <th>No of Rides</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($tripStats as $stat)
                    <tr>
                      <td>{{ $stat['name'] }}</td>
                      <td>{{ $stat['number_of_uses'] }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="2">No record found!</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
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
            $('select[name=passenger_id]').on('change', function(event) {
                $('#passengerFilter')[0].submit();
            });
        });
    </script>
@endsection