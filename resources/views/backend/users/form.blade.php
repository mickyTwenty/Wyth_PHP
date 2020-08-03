<div class="row">
    <div class="col-sm-6 form-group{{ $errors->has('first_name') ? ' has-error' : '' }}">
        {!! Form::label('first_name', 'First Name') !!}
        {!! Form::text('first_name', null, ['class' => 'form-control']) !!}
        @if ($errors->has('first_name'))
            <span class="help-block">
                <strong>{{ $errors->first('first_name') }}</strong>
            </span>
        @endif
    </div>
    
    <div class="col-sm-6 form-group{{ $errors->has('last_name') ? ' has-error' : '' }}">
        {!! Form::label('last_name', 'Last Name') !!}
        {!! Form::text('last_name', null, ['class' => 'form-control']) !!}
        @if ($errors->has('last_name'))
            <span class="help-block">
                <strong>{{ $errors->first('last_name') }}</strong>
            </span>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-sm-6 form-group{{ $errors->has('email') ? ' has-error' : '' }}">
        {!! Form::label('email', 'Email') !!}
        {!! Form::text('email', null, ['class' => 'form-control']) !!}
        @if ($errors->has('email'))
            <span class="help-block">
                <strong>{{ $errors->first('email') }}</strong>
            </span>
        @endif
    </div>
    
    <div class="col-sm-6 form-group{{ $errors->has('password') ? ' has-error' : '' }}">
        {!! Form::label('password', 'Password') !!}
        {!! Form::password('password', ['class' => 'form-control']) !!}
        <span class="help-block">
        @if ($errors->has('password'))
            <strong>{{ $errors->first('password') }}</strong><br />
        @endif
        @if ( isset($record) )
            <strong>Note:</strong> Keep it blank if you do not want to change it.
        @endif
        </span>
    </div>
</div>

<div class="row">
    <div class="col-sm-6 form-group{{ $errors->has('mobile') ? ' has-error' : '' }}">
        {!! Form::label('mobile', 'Mobile') !!}
        {!! Form::text('mobile', null, ['class' => 'form-control']) !!}
        @if ($errors->has('mobile'))
            <span class="help-block">
                <strong>{{ $errors->first('mobile') }}</strong>
            </span>
        @endif
    </div>
        
    <div class="col-md-6 form-group{{ $errors->has('status') ? ' has-error' : '' }}">
        {!! Form::label('status', 'Status') !!}
        {!! Form::select('status', statusArrayHelper(), null, ['class' => 'form-control']) !!}
        @if ($errors->has('status'))
            <span class="help-block">
                <strong>{{ $errors->first('status') }}</strong>
            </span>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group{{ $errors->has('profile_picture') ? ' has-error' : '' }}">
            {!! Form::label('profile_picture', 'Profile Picture') !!}
            {!! Form::file('profile_picture') !!}
            @if ($errors->has('profile_picture'))
                <span class="help-block">
                    <strong>{{ $errors->first('profile_picture') }}</strong>
                </span>
            @endif
        </div>
        <div class="col-md-6">
        @if ( isset($record) && is_file('frontend/images/attorneys/'.$record->profile_picture) )
            {!! '<a href="'.frontend_asset('images/attorneys/' . $record->profile_picture).'" class="cboxImages">' . Html::image('frontend/images/attorneys/' . $record->profile_picture, null, ['class' => 'img-responsive pad']) . '</a>' !!}
            <div class="pad">
                <label class="label-control">{!! Form::checkbox('remove_profile_picture') !!} Remove current picture? (if set)</label>
            </div>
        @endif
        </div>
    </div>
</div>

<div class="pull-left">
    {!! Form::submit('Save', ['class' => 'btn btn-primary btn-flat']) !!}
    <a href="{{ backend_url($moduleProperties['controller']) }}" type="button" class="btn btn-default btn-flat">Cancel</a>
</div>

@section('inlineJS')
@endsection