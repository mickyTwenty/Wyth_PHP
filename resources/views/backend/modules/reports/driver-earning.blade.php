@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
    <link href="{{ backend_asset('plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/chosen/chosen.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
    <script src="{{ backend_asset('plugins/daterangepicker/moment.min.js') }}"></script>
    <script src="{{ backend_asset('plugins/chosen/chosen.jquery.min.js') }}"></script>
    <script src="{{ backend_asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ backend_asset('plugins/datatables/dataTables.bootstrap.min.js') }}"></script>
    <script src="{{ backend_asset('plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.1/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/vfs_fonts.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.5.1/js/buttons.html5.min.js"></script>

    <script type="text/javascript">
      jQuery(document).ready(function($) {
        appConfig.set( 'dt.aoColumnDefs.aTargets', [] );

        $('.earnings-table').DataTable( {
            "fnDrawCallback": function() {
                var earningTable = $('.earnings-table').DataTable();
                if (earningTable.data().length === 0) {
                 earningTable.buttons().disable();
               } else {
                earningTable.buttons().enable();
              }

              $('button.disabled').attr('disabled', 'disabled');
            },
            dom: 'Bfrtip',
            buttons: [
              { 
                extend: 'csvHtml5',
                footer: true,
                text: 'Export as CSV'
              }
            ]
        } );
      });

      $(document).on('appConfig.initialized', function(handler, appConfig) {
      @if (request()->has('startDate'))
          appConfig.get('app.daterangepicker')['#dateRangePicker1'].data('daterangepicker').setStartDate('{{ Carbon\Carbon::createFromFormat('Y-m-d', request()->input('startDate'))->format('m/d/Y') }}');
          appConfig.get('app.daterangepicker')['#dateRangePicker1'].data('daterangepicker').setEndDate('{{ Carbon\Carbon::createFromFormat('Y-m-d', request()->input('endDate'))->format('m/d/Y') }}');
      @endif
      })

      $('.date-range-picker').on('apply.daterangepicker', function(ev, picker) {
          $('#startDate').val( picker.startDate.format('YYYY-MM-DD') );
          $('#endDate').val( picker.endDate.format('YYYY-MM-DD') );
          $('form#dateFilter').submit();
      })
    </script>
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
                    <form method="POST" id="passengerFilter">
                        {{ csrf_field() }}
                        <div class="col-md-4">
                          {!! Form::select('driver_id', ['' => 'Select Driver'] + $drivers, null, ['class' => 'form-control chosen']) !!}
                        </div>

                        <div class="col-md-4">
                          <div class="input-group">
                            <div class="input-group-addon">
                              <i class="fa fa-calendar"></i>
                            </div>
                            <input type="text" class="form-control col-sm-6 col-lg-6 pull-right date-range-picker" id="dateRangePicker1">
                          </div>

                          <input type="hidden" name="startDate" id="startDate">
                          <input type="hidden" name="endDate" id="endDate">
                        </div>
                        
                        <div class="col-md-4">
                            <button type="submit" name="filter" value="filter" class="btn btn-default">Filter</button>
                            <a href="{{ route('backend.reports.driver.earning') }}" class="btn btn-danger">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="box">
            <div class="box-header">
              <h3 class="box-title">Driver Earnings</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <table class="table table-bordered table-striped earnings-table">
                <thead>
                <tr>
                  <th>Trip ID</th>
                  <th>Driver</th>
                  <th>Earning</th>
                  <th>Date/Time</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($earnings as $earning)
                      <tr>
                        <td>{{ $earning->id }}</td>
                        <td>{{ $earning->name }}</td>
                        <td>{{ prefixCurrency($earning->earned_by_driver) }}</td>
                        <td>{{ \Carbon\Carbon::parse($earning->ended_at)->format(constants('back.theme.modules.datetime_format')) }}</td>
                      </tr>
                    @endforeach
                </tbody>
                @if (count($earnings))
                  <tfoot align="right">
                      <tr><th>Total Earning</th><th></th><th>{{ prefixCurrency($totalEarning) }}</th><th></th></tr>
                  </tfoot>
                @endif
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
            // $('select[name=driver_id]').on('change', function(event) {
            //     $('#passengerFilter')[0].submit();
            // });
        });
    </script>
@endsection