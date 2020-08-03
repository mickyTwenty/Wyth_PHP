@extends( 'backend.layouts.app' )

@section('title', 'Edit Settings')

@section('CSSLibraries')
    <link href="{{ backend_asset('plugins/select2/select2.min.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/colorbox/colorbox.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
    <script src="{{ backend_asset('plugins/select2/select2.full.min.js') }}"></script>
    <script src="{{ backend_asset('plugins/colorbox/jquery.colorbox-min.js') }}"></script>
@endsection

@section('inlineJS')
    <script type="text/javascript">
        $(document).ready(function() {
            $("#reference_source").select2({
                tags: true,
                tokenSeparators: [',']
            })
        })
    </script>
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>Edit Settings
      </h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @include( 'backend.layouts.notification_message' )

        <div class="row">
          <div class="col-md-12">
            <div class="box">
              <div class="box-header with-border">
                <h3 class="box-title">Edit Mode</h3>
              </div>
              <div class="box-body">
                  {!! Form::open(['method' => 'POST', 'url' => route('backend.settings')]) !!}
                    <div class="row">
                        <div class="col-sm-6 form-group{{ $errors->has('cancellation_fee') ? ' has-error' : '' }}">
                            {!! Form::label('cancellation_fee', 'Cancellation Fee') !!}
                            {!! Form::text('cancellation_fee', $allInOneConfigs['cancellation_fee'], ['class' => 'form-control float-field']) !!}
                            @if ($errors->has('cancellation_fee'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('cancellation_fee') }}</strong>
                                </span>
                            @else
                                <span class="help-block">
                                    <strong><i>Value should be in percentage (%)</i></strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 form-group{{ $errors->has('transaction_fee') ? ' has-error' : '' }}">
                            {!! Form::label('transaction_fee', 'Transaction Fee') !!}
                            {!! Form::text('transaction_fee', $allInOneConfigs['transaction_fee'], ['class' => 'form-control float-field']) !!}
                            @if ($errors->has('transaction_fee'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('transaction_fee') }}</strong>
                                </span>
                            @else
                                <span class="help-block">
                                    <strong><i>Value should be in currency (e.g $)</i></strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 form-group{{ $errors->has('transaction_fee_local') ? ' has-error' : '' }}">
                            {!! Form::label('transaction_fee_local', 'Transaction Fee Local') !!}
                            {!! Form::text('transaction_fee_local', $allInOneConfigs['transaction_fee_local'], ['class' => 'form-control float-field']) !!}
                            @if ($errors->has('transaction_fee_local'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('transaction_fee_local') }}</strong>
                                </span>
                            @else
                                <span class="help-block">
                                    <strong><i>Value should be in currency (e.g $)</i></strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 form-group{{ $errors->has('local_max_distance') ? ' has-error' : '' }}">
                            {!! Form::label('local_max_distance', 'Local Trip Max Distance (Meters)') !!}
                            {!! Form::text('local_max_distance', $allInOneConfigs['local_max_distance'], ['class' => 'form-control float-field']) !!}
                            @if ($errors->has('local_max_distance'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('local_max_distance') }}</strong>
                                </span>
                            @else
                                <span class="help-block">
                                    <strong><i>Value should be in meters (e.g 1 mi = 1609.34 meters)</i></strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 form-group{{ $errors->has('min_estimate') ? ' has-error' : '' }}">
                            {!! Form::label('min_estimate', 'Minimum Estimate') !!}
                            {!! Form::text('min_estimate', $allInOneConfigs['min_estimate'], ['class' => 'form-control float-field']) !!}
                            @if ($errors->has('min_estimate'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('min_estimate') }}</strong>
                                </span>
                            @else
                                <span class="help-block">
                                    <strong><i>Value should be in currency (e.g $)</i></strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 form-group{{ $errors->has('max_estimate') ? ' has-error' : '' }}">
                            {!! Form::label('max_estimate', 'Maximum Estimate') !!}
                            {!! Form::text('max_estimate', $allInOneConfigs['max_estimate'], ['class' => 'form-control float-field']) !!}
                            @if ($errors->has('max_estimate'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('max_estimate') }}</strong>
                                </span>
                            @else
                                <span class="help-block">
                                    <strong><i>Value should be in currency (e.g $)</i></strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 form-group{{ $errors->has('ride_cancellation_count') ? ' has-error' : '' }}">
                            {!! Form::label('ride_cancellation_count', 'Cancellation count to penalize') !!}
                            {!! Form::number('ride_cancellation_count', $allInOneConfigs['ride_cancellation_count'], ['class' => 'form-control float-field']) !!}
                            @if ($errors->has('ride_cancellation_count'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('ride_cancellation_count') }}</strong>
                                </span>
                            @else
                                <span class="help-block">
                                    <strong><i>Value should be in numbers (e.g 2)</i></strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 form-group{{ $errors->has('ride_cancellation_penalty') ? ' has-error' : '' }}">
                            {!! Form::label('ride_cancellation_penalty', 'Penalize percentage') !!}
                            {!! Form::text('ride_cancellation_penalty', $allInOneConfigs['ride_cancellation_penalty'], ['class' => 'form-control float-field']) !!}
                            @if ($errors->has('ride_cancellation_penalty'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('ride_cancellation_penalty') }}</strong>
                                </span>
                            @else
                                <span class="help-block">
                                    <strong><i>Value should be in percentage (%)</i></strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12 form-group{{ $errors->has('reference_source') ? ' has-error' : '' }}">
                            {!! Form::label('reference_source', 'How did you hear about us?') !!}
                            {!! Form::select('reference_source[]', $allInOneConfigs['reference_source'], $allInOneConfigs['reference_source'], ['class' => 'form-control', 'multiple' => 'multiple', 'id' => 'reference_source']) !!}
                            @if ($errors->has('reference_source'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('reference_source') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="pull-left">
                        {!! Form::submit('Update', ['class' => 'btn btn-primary btn-flat']) !!}
                        <a href="{{ backend_url('dashboard') }}" type="button" class="btn btn-default btn-flat">Cancel</a>
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
