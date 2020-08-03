@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
    <!-- DataTables CSS -->
    <link href="{{ backend_asset('plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/colorbox/colorbox.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/chosen/chosen.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/select2/select2.min.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
<!-- DataTables JavaScript -->
<script src="{{ backend_asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ backend_asset('plugins/datatables/dataTables.bootstrap.min.js') }}"></script>
<script src="{{ backend_asset('plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ backend_asset('plugins/colorbox/jquery.colorbox-min.js') }}"></script>
<script src="{{ backend_asset('plugins/chosen/chosen.jquery.min.js') }}"></script>
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>{{ $moduleProperties['longModuleName'] }}</h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @include( 'backend.layouts.notification_message' )

        <div class="box">
            <div class="box-header">
              <h3 class="box-title">Select drivers or passengers to send push notification!</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">

              {!! Form::open(['url' => 'backend/'.$moduleProperties['controller'].'/push-notification']) !!}

                <div class="row">
                    <div class="col-sm-12 form-group">
                        {!! Form::label('driver_ids[]', 'Drivers') !!}
                        {!! Form::select('driver_ids[]', $drivers, null, ['class' => 'form-control chosen', 'multiple']) !!}
                    </div>

                    <div class="col-sm-12 form-group">
                        {!! Form::label('passenger_ids[]', 'Passengers') !!}
                        {!! Form::select('passenger_ids[]', $passengers, null, ['class' => 'form-control chosen', 'multiple']) !!}
                    </div>

                    <div class="col-sm-12 form-group{{ $errors->has('message') ? ' has-error' : '' }}">
                        {!! Form::label('message', 'Message') !!}
                        {!! Form::textarea('message', null, ['class' => 'form-control']) !!}
                        @if ($errors->has('message'))
                            <span class="help-block">
                                <strong>{{ $errors->first('message') }}</strong>
                            </span>
                        @endif
                    </div>
                    
                </div>

                <div class="pull-left">
                    {!! Form::submit('Send', ['class' => 'btn btn-primary btn-flat']) !!}
                </div>
              {!! Form::close() !!}

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
@endsection