@extends( 'backend.layouts.app' )

@section('title', 'Ticket System')

@section('CSSLibraries')
<!-- DataTables CSS -->
<link href="{{ backend_asset('plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
<!-- DataTables JavaScript -->
<script src="{{ backend_asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ backend_asset('plugins/datatables/dataTables.bootstrap.min.js') }}"></script>

<script type="text/javascript">
  $(function(){
    appConfig.set( 'dt.order', [[ 0, 'desc']] );
  })
</script>
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>Ticket System</h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @include( 'backend.layouts.notification_message' )

        <div class="box">
            <!-- /.box-header -->
            <div class="box-body">
              <table class="table table-bordered table-striped datatable">
                <thead>
                <tr>
                  <th width="20">ID</th>
                  <th>Case Type</th>
                  <th>User</th>
                  <th>Full Name</th>
                  {{-- <th>Email</th>
                  <th>Mobile</th>
                  <th>Card</th>
                  <th>Case Detail</th> --}}
                  <th width="50">Status</th>
                  <th width="140">Date Created</th>
                  <th width="50">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach( $records as $record )

                    <tr>
                        <td>{{ $record->id }}</td>
                        <td>{{ $record->case }}</td>
                        <td>{!! $record->user->full_name or '<i>(None)</i>' !!}</td>
                        <td>{{ $record->name }}</td>
                        {{-- <td>{{ $record->email }}</td>
                        <td>{{ $record->mobile }}</td>
                        <td>{{ $record->card }}</td>
                        <td>{{ str_limit($record->detail, 100) }}</td> --}}
                        <td align="center">{!! $record->status_text_formatted !!}</td>
                        <td>{{ $record->created_at->format( constants('back.theme.modules.datetime_format') ) }}</td>
                        <td>

                        @if ( in_array($record->status, [App\Models\Helpdesk::STATUS_PENDING, App\Models\Helpdesk::STATUS_RESPONDED]) )
                            <a class="btn btn-xs btn-success" type="button" title="Mark Resolve" data-toggle="modal" data-target="#resolveModal{{ $record->id }}">
                                <i class="fa fa-check"></i>
                            </a>

                            <div id="resolveModal{{ $record->id }}" class="modal fade" role="dialog">
                                <div class="modal-dialog">
                                <div class="modal-content">
                                <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Confirm Resolve</h4>
                                </div>
                                <div class="modal-body">
                                <p>Are you sure you want to mark this case resolved?</p>
                                </div>
                                <div class="modal-footer">
                                <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Close</button>
                                {!! Form::model($record, ['method' => 'put', 'url' => 'backend/'.$moduleProperties['controller'].'/resolve/'.$record->id, 'class' =>'form-inline']) !!}
                                {!! Form::submit('Yes', ['class' => 'btn btn-success btn-flat']) !!}
                                {!! Form::close() !!}
                                </div>
                                </div>
                                </div>
                            </div>
                        @endif

                            <a href="{{ backend_url(''.$moduleProperties['controller'].'/detail/'.$record->id) }}" title="Detail" class="btn btn-xs btn-primary view">
                                <i class="fa fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach

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