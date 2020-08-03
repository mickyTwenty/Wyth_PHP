@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
    <!-- DataTables CSS -->
    <link href="{{ backend_asset('plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/colorbox/colorbox.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
<!-- DataTables JavaScript -->
<script src="{{ backend_asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ backend_asset('plugins/datatables/dataTables.bootstrap.min.js') }}"></script>
<script src="{{ backend_asset('plugins/colorbox/jquery.colorbox-min.js') }}"></script>

<script type="text/javascript">
  /* Formatting function for row details - modify as you need */
  function format ( d ) {
      // `d` is the original data object for the row
      return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
          '<tr>'+
              '<th>Trip Name: </th>'+
              '<td> '+d.trip_name+' </td>'+
          '</tr>'+
          '<tr>'+
              '<th>Origin Title: </th>'+
              '<td> '+d.origin_title+' </td>'+
          '</tr>'+
          '<tr>'+
              '<th>Destination Title: </th>'+
              '<td> '+d.destination_title+' </td>'+
          '</tr>'+
      '</table>';
  }

  $(document).ready(function() {
      var table = $('.trips-table').DataTable({
          order: [[ 1, 'asc']],
          searching: false,
          serverSide: true,
          processing: true,
          ajax: {
            url: base_url + '/trips/listing/data',
            data: function(data) {
                data.status = jQuery('select[name=status]').val()
            },
        },
          columns: [
              {
                  "className":      'details-control',
                  "orderable":      false,
                  "data":           null,
                  "defaultContent": ''
              },
              {data: 'id'},
              {data: 'driver'},
              {data: 'is_roundtrip'},
              {data: 'status', searchable: false, orderable: false},
              {data: 'created_at', searchable: false},
              {data: 'action', searchable: false, orderable: false}
          ],
          buttons: ['csv', 'excel'],
          drawCallback: function(settings) {
              $(".cboxImages").colorbox({rel:'cboxImages', maxWidth:"100%", maxHeight:"100%"});
          }
      });

      appConfig.set('app.yajraDataTable', table)

      // Add event listener for opening and closing details
      $('.trips-table tbody').on('click', 'td.details-control', function () {
          var tr = $(this).closest('tr');
          var row = table.row( tr );

          if ( row.child.isShown() ) {
              // This row is already open - close it
              row.child.hide();
              tr.removeClass('shown');
          }
          else {
              // Open this row
              row.child( format(row.data()) ).show();
              tr.addClass('shown');
          }
      } );
  } );
</script>
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>{{ $moduleProperties['longModuleName'] }}
      {{-- <a href="{{ backend_url($moduleProperties['controller'].'/create') }}" type="button" class="pull-right btn btn-primary btn-flat">New {{ str_singular($moduleProperties['shortModuleName']) }}</a> --}}
      </h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @include( 'backend.layouts.notification_message' )

        <div class="box">
            <div class="box-header">
              <h3 class="box-title">All {{ str_plural($moduleProperties['longModuleName']) }}</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">

              <div class="filter-tools">
                 <div class="btn-group button-container pull-right">
                    <button type="button" class="btn btn-flat btn-default clear-data">
                    <i class="fa fa-refresh" aria-hidden="true"></i>
                        Clear Filter
                    </button>
                    <button type="button" class="btn btn-flat btn-default filter-data">
                    <i class="fa fa-filter" aria-hidden="true"></i> Filter
                    </button>
                 </div>
              </div>

              <div class="clearfix"></div>

              <div class="grid-filter">
                  <form id="filter-form pull-left">
                      <div class="row">
                        <div class="col-sm-3">
                            <label>Ride Status</label>
                            {!! Form::select('status', [
                                '' => 'Show All',
                                'completed' => 'Completed',
                                'in-process' => 'In-Process',
                                'upcoming' => 'Upcoming',
                                'canceled' => 'Canceled',
                            ], null, ['class' => 'form-control', 'id' => 'status']) !!}
                        </div>
                      </div>
                  </form>
              </div>

              <div class="clearfix"></div>

              <br>

              <table class="table table-bordered table-striped trips-table">
                <thead>
                <tr>
                  <th></th>
                  <th>Trip ID</th>
                  <th>Driver</th>
                  <th>Round Trip</th>
                  <th>Status</th>
                  <th>Date/Time</th>
                  <th>Action</th>
                </tr>
                </thead>
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
