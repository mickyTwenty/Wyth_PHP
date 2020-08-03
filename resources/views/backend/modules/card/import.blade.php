@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
<link href="{{ backend_asset('plugins/icon-picker/icon-picker.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
<script src="{{ backend_asset('plugins/icon-picker/icon-picker.min.js') }}"></script>
<!-- CK Editor -->
<script src="https://cdn.ckeditor.com/4.5.7/standard/ckeditor.js"></script>
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>{{ $moduleProperties['longModuleName'] }}
      <small>Import {{ ($moduleProperties['longModuleName']) }}</small>
      <div class="pull-right f14"><a href="{{backend_public('uploads/Sample_Recharge.xlsx')}}">Download Sample File</a></div>
      </h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @include( 'backend.layouts.notification_message' )

        <div class="row">
          <div class="col-md-12">
            <div class="box">
              <div class="box-header with-border">  
                <h3 class="box-title">Import Cards</h3>
              </div>
              <div class="box-body">
                  {!! Form::open(['url' => 'backend/'.$moduleProperties['controller'].'/import', 'files' => 'true']) !!}
                    <div class="row">
                      <div class="col-md-6 form-group{{ $errors->has('import') ? ' has-error' : '' }}">
                          {!! Form::label('import', 'Select excel file') !!}
                          {!! Form::file('import') !!}
                          @if ($errors->has('import'))
                              <span class="help-block">
                                  <strong>{{ $errors->first('import') }}</strong>
                              </span>
                          @endif
                      </div>
                    </div>
                    <br />

                  <div class="pull-left">
                      {!! Form::submit('Upload', ['class' => 'btn btn-primary btn-flat']) !!}
                      <a href="{{ backend_url($moduleProperties['controller']) }}" type="button" class="btn btn-default btn-flat">Cancel</a>
                  </div>
                  {!! Form::close() !!}
              </div>
            </div>
          </div>
        </div>

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

@endsection