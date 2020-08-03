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
              '<th>Feedback: </th>'+
              '<td> '+d.feedback+' </td>'+
          '</tr>'+
      '</table>';
  }

  $(document).ready(function() {
      var table = $('.reviews-table').DataTable({
          order: [[ 1, 'asc']],
          searching: false,
          serverSide: true,
          processing: true,
          ajax: base_url + '/reviews/data',
          columns: [
              {
                  "className":      'details-control',
                  "orderable":      false,
                  "data":           null,
                  "defaultContent": ''
              },
              {data: 'id'},
              {data: 'rater'},
              {data: 'rater_type'},
              {data: 'ratee'},
              {data: 'ratee_type'},
              {data: 'rating'},
              {data: 'is_approved'},
              {data: 'created_at', searchable: false},
              {data: 'action', orderable: false, searchable: false}
          ],
          buttons: ['csv', 'excel'],
          drawCallback: function(settings) {
              $(".cboxImages").colorbox({rel:'cboxImages', maxWidth:"100%", maxHeight:"100%"});
          }
      });

      // Add event listener for opening and closing details
      $('.reviews-table tbody').on('click', 'td.details-control', function () {
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
              <table class="table table-bordered table-striped reviews-table">
                <thead>
                <tr>
                  <th></th>
                  <th>ID</th>
                  <th>Rater</th>
                  <th>Rater Type</th>
                  <th>Ratee</th>
                  <th>Ratee Type</th>
                  <th>Rating</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th width="50">Action</th>
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
