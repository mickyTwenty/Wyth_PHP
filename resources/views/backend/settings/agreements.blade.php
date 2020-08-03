@extends( 'backend.layouts.app' )

@section('title', 'Agreement Editor')

@section('CSSLibraries')
  <link href="{{ backend_asset('plugins/chosen/chosen.css') }}" rel="stylesheet">
  <link href="{{ backend_asset('plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
  <script src="{{ backend_asset('plugins/chosen/chosen.jquery.min.js') }}"></script>
  <script src="{{ backend_asset('plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js') }}"></script>
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>User Agreement
      <small>Edit Agreement</small>
      </h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @include( 'backend.layouts.notification_message' )

        <div class="row">
          <div class="col-md-12">
            <div class="box">
              <div class="box-body">
                  {!! Form::model($allInOneConfigs, ['method' => 'POST', 'url' => route('backend.system.agreement')]) !!}
                    <div class="row">
                        <div class="col-md-12 form-group{{ $errors->has('user_agreement_driver') ? ' has-error' : '' }}">
                            {!! Form::label('user_agreement_driver', 'Driver\'s Agreement') !!}
                            {!! Form::textarea('user_agreement_driver', null, ['class' => 'form-control']) !!}
                            @if ($errors->has('user_agreement_driver'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('user_agreement_driver') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 form-group{{ $errors->has('user_agreement_passenger') ? ' has-error' : '' }}">
                            {!! Form::label('user_agreement_passenger', 'Passenger\'s Agreement') !!}
                            {!! Form::textarea('user_agreement_passenger', null, ['class' => 'form-control']) !!}
                            @if ($errors->has('user_agreement_passenger'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('user_agreement_passenger') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="pull-left">
                        {!! Form::submit('Save', ['class' => 'btn btn-primary btn-flat']) !!}
                        <a href="{{ route('backend.dashboard') }}" type="button" class="btn btn-default btn-flat">Cancel</a>
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

@section('inlineJS')
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#user_agreement_driver').wysihtml5();
            $('#user_agreement_passenger').wysihtml5();
        });
    </script>
@endsection
