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
  $(function(){
    appConfig.set('yajrabox.ajax', base_url + '/promo-codes/data');
    appConfig.set('yajrabox.columns', [
        {data: 'code'},
        {data: 'discount_type', searchable: false},
        {data: 'value'},
        {data: 'available_from', orderable: false, searchable: false},
        {data: 'available_till'},
        {data: 'action', orderable: false, searchable: false}
    ]);
    appConfig.set('dt.order', [[ 0, 'desc']]);
  })
</script>
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>{{ $moduleProperties['longModuleName'] }}
      <a href="{{ backend_url($moduleProperties['controller'].'/create') }}" type="button" class="pull-right btn btn-primary btn-flat">New {{ str_singular($moduleProperties['shortModuleName']) }}</a>
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
              <table class="table table-bordered table-striped yajrabox">
                <thead>
                <tr>
                  <th>Code</th>
                  <th>Discount Type</th>
                  <th>Value</th>
                  <th>Available From</th>
                  <th>Available Till</th>
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
