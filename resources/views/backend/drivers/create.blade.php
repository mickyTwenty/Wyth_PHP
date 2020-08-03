@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
    <link href="{{ backend_asset('plugins/select2/select2.min.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/chosen/chosen.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/datepicker/datepicker3.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
    <script src="{{ backend_asset('plugins/select2/select2.full.min.js') }}"></script>
    <script src="{{ backend_asset('plugins/chosen/chosen.jquery.min.js') }}"></script>
    <script src="{{ backend_asset('plugins/datepicker/bootstrap-datepicker.js') }}"></script>

    @if (!old('city'))
      <script type="text/javascript">
          jQuery(document).ready(function($) {
              $('#ddl_states').trigger('change');
              $('#ddl_states').trigger('chosen:updated');
          });
      </script>
    @endif
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>{{ $moduleProperties['longModuleName'] }}
      <small>Add new {{ str_singular($moduleProperties['shortModuleName']) }}</small>
      </h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @include( 'backend.layouts.notification_message' )

        <div class="row">
          <div class="col-md-12">
            <div class="box">
              <div class="box-header with-border">  
                <h3 class="box-title">Create Mode</h3>
              </div>
              <div class="box-body">
                  {!! Form::open(['url' => 'backend/users/create/driver', 'files' => 'true']) !!}
                    @include( 'backend/' . $moduleProperties['viewDir'] . '.form' )
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