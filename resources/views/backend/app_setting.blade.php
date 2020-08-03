@extends( 'backend.layouts.app' )

@section('title', 'System Setting')

@section('content')

        <div id="page-wrapper">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">System Setting</h1>
                    </div>
                    <!-- /.col-lg-12 -->
                </div>
                <!-- /.row -->

                @if ( $errors->count() )
                <div class="alert alert-danger alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    There was an error while saving your form, please review below.
                </div>
                @endif

                @include( 'backend.layouts.notification_message' )

                <div class="row">
                    <div class="col-lg-6">
                        {!! Form::model($setting, ['method' => 'POST', 'class' => '', 'role' => 'form']) !!}
                            <div class="form-group{{ $errors->has('tutorial_video') ? ' has-error' : '' }}">
                                {{ Form::label('tutorial_video', 'Video Tutorial (URL)', ['class'=>'control-label']) }}
                                {{ Form::text('tutorial_video', null, ['class' => 'form-control', 'required']) }}
                                @if ( $errors->has('tutorial_video') )
                                    <p class="help-block">{{ $errors->first('tutorial_video') }}</p>
                                @endif
                            </div>

                            <div class="form-group{{ $errors->has('guide_book') ? ' has-error' : '' }}">
                                {{ Form::label('guide_book', 'Guide Book (URL)', ['class'=>'control-label']) }}
                                {{ Form::text('guide_book', old('guide_book'), ['class' => 'form-control', 'required']) }}
                                @if ( $errors->has('guide_book') )
                                    <p class="help-block">{{ $errors->first('guide_book') }}</p>
                                @endif
                            </div>

                            {{ Form::submit('Save', ['class' => 'btn btn-primary']) }}
                            {{ Html::link( backend_url('/dashboard'), 'Cancel', ['class' => 'btn btn-default']) }}
                        {!! Form::close() !!}
                    </div>
                </div>

        </div>
        <!-- /#page-wrapper -->

@endsection