@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
    <!-- DataTables CSS -->
    <link href="{{ backend_asset('plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
<!-- DataTables JavaScript -->
<script src="{{ backend_asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ backend_asset('plugins/datatables/dataTables.bootstrap.min.js') }}"></script>
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
              <table class="table table-bordered table-striped datatable">
                <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Date/Time</th>
                  <th width="50">Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($schools as $school)
                      <tr>
                        <td>{{ $school->id }}</td>
                        <td>{{ $school->name }}</td>
                        <td>{{ $school->created_at->format(constants('back.theme.modules.datetime_format')) }}</td>
                        <td>
                          <a href="{{ backend_url($moduleProperties['controller'].'/edit/'.$school->id) }}" title="Edit" class="btn btn-xs btn-primary">
                            <i class="fa fa-pencil"></i>
                          </a>

                          <a class="btn btn-xs btn-danger delete" type="button" title="Delete" data-toggle="modal" data-target="#deleteModal{{ $school->id }}">
                              <i class="fa fa-trash"></i>
                          </a>

                          <div id="deleteModal{{ $school->id }}" class="modal fade" role="dialog">
                              <div class="modal-dialog">
                              <div class="modal-content">
                              <div class="modal-header">
                              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                              <h4 class="modal-title">Confirm Delete</h4>
                              </div>
                              <div class="modal-body">
                              <p>Are you sure you want to delete this record?</p>
                              </div>
                              <div class="modal-footer">
                              <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Close</button>
                              {!! Form::model($school, ['method' => 'delete', 'url' => 'backend/'.$moduleProperties['controller'].'/'.$school->id, 'class' =>'form-inline form-delete']) !!}
                              {!! Form::hidden('id', $school->id) !!}
                              {!! Form::submit('Yes', ['class' => 'btn btn-success btn-flat']) !!}
                              {!! Form::close() !!}
                              </div>
                              </div>
                              </div>
                          </div>
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
