@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
  <link href="{{ backend_asset('plugins/chosen/chosen.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
  <script src="{{ backend_asset('plugins/chosen/chosen.jquery.min.js') }}"></script>

  @if (!old('city'))
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // $('#ddl_states').trigger('change');
            // $('#ddl_states').trigger('chosen:updated');
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
      <small>Edit {{ str_singular($moduleProperties['shortModuleName']) }}</small>
      </h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @include( 'backend.layouts.notification_message' )

        <div class="row">
          <div class="col-md-12">
            <div class="box">
              <div class="box-body">
                  {!! Form::model($record, ['method' => 'POST', 'url' => 'backend/'.$moduleProperties['controller'].'/'.$record->id]) !!}
                    {!! Form::hidden('id', $record->id) !!}
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
