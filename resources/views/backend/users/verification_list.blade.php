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
    appConfig.set('dt.aoColumnDefs.aTargets', [0, -1]);
    appConfig.set('dt.order', [[ 8, 'desc']]);
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
              <h3 class="box-title">User Verifications Request</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <table class="table table-bordered table-striped datatable">
                <thead>
                <tr>
                  <th width="80">Picture</th>
                  <th>Email</th>
                  <th>Facebook</th>
                  <th>Twitter</th>
                  <th>Instagram</th>
                  <th>Website</th>
                  <th>Bio</th>
                  <th>Approved</th>
                  <th width="130">Date Requested</th>
                  <th width="50">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach( $records as $record )

                {{-- @if ($record->verification->is_approved === 1)
                    @continue
                @endif --}}

                    <tr>
                        <td class="text-center">{!! '<a href="'.$record->profile_picture_auto.'" class="cboxImages">' . Html::image($record->profile_picture_path, null, ['class' => 'img-responsive', 'style' => 'max-width: 50px;max-height: 50px']) . '</a>' !!}</td>
                        <td>{{ $record->email }}</td>
                        <td>{{ $record->verification->facebook_url ? HTML::link($record->verification->facebook_url, 'Facebook', ['target' => '_blank']) : '-' }}</td>
                        <td>{{ $record->verification->twitter_url ? HTML::link($record->verification->twitter_url, 'Twitter', ['target' => '_blank']) : '-' }}</td>
                        <td>{{ $record->verification->instagram_url ? HTML::link($record->verification->instagram_url, 'Instagram', ['target' => '_blank']) : '-' }}</td>
                        <td>{{ $record->verification->website_url ? HTML::link($record->verification->website_url, 'Website', ['target' => '_blank']) : '-' }}</td>
                        <td>{{ $record->verification->information }}</td>
                        <td>{!! $record->verification->is_approved_text_formatted !!}</td>
                        <td>{{ $record->verification->created_at->format( constants('back.theme.modules.datetime_format') ) }}</td>
                        <td>
                        @if ($record->verification->is_approved === 0)
                            <a class="btn btn-xs btn-success delete" type="button" title="Approve" data-toggle="modal" data-target="#approveModal{{ $record->id }}">
                                <i class="fa fa-check"></i>
                            </a>

                            <div id="approveModal{{ $record->id }}" class="modal fade" role="dialog">
                                <div class="modal-dialog">
                                <div class="modal-content">
                                <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Confirm Approval</h4>
                                </div>
                                <div class="modal-body">
                                <p>Are you sure you want to approve this request?</p>
                                </div>
                                <div class="modal-footer">
                                <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Close</button>
                                {!! Form::model($record, ['method' => 'post', 'url' => 'backend/'.$moduleProperties['controller'].'/'.$record->id, 'class' =>'form-inline form-delete']) !!}
                                {!! Form::hidden('id', $record->verification->id) !!}
                                {!! Form::hidden('action', 'approve') !!}
                                {!! Form::submit('Yes', ['class' => 'btn btn-success btn-flat']) !!}
                                {!! Form::close() !!}
                                </div>
                                </div>
                                </div>
                            </div>

                            <a class="btn btn-xs btn-danger delete" type="button" title="Reject" data-toggle="modal" data-target="#rejectModal{{ $record->id }}">
                                <i class="fa fa-times"></i>
                            </a>

                            <div id="rejectModal{{ $record->id }}" class="modal fade" role="dialog">
                                <div class="modal-dialog">
                                <div class="modal-content">
                                <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Confirm Rejection</h4>
                                </div>
                                <div class="modal-body">
                                <p>Are you sure you want to reject this request?</p>
                                </div>
                                <div class="modal-footer">
                                <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Close</button>
                                {!! Form::model($record, ['method' => 'post', 'url' => 'backend/'.$moduleProperties['controller'].'/'.$record->id, 'class' =>'form-inline form-delete']) !!}
                                {!! Form::hidden('id', $record->verification->id) !!}
                                {!! Form::hidden('action', 'reject') !!}
                                {!! Form::submit('Yes', ['class' => 'btn btn-success btn-flat']) !!}
                                {!! Form::close() !!}
                                </div>
                                </div>
                                </div>
                            </div>
                        @endif
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
