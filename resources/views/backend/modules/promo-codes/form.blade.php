<div class="row">
    <div class="col-md-12 form-group{{ $errors->has('code') ? ' has-error' : '' }}">
        {!! Form::label('code', 'Code') !!}
        {!! Form::text('code', null, ['class' => 'form-control', 'required' => 'required']) !!}
        @if ($errors->has('code'))
            <span class="help-block">
                <strong>{{ $errors->first('code') }}</strong>
            </span>
        @endif
    </div>
    <div class="col-md-12 form-group{{ $errors->has('discount_type') ? ' has-error' : '' }}">
        {!! Form::label('discount_type', 'Discount Type') !!}
        {!! Form::select('discount_type', $discountTypes, null, ['class' => 'form-control', 'required' => 'required']) !!}
        @if ($errors->has('discount_type'))
            <span class="help-block">
                <strong>{{ $errors->first('discount_type') }}</strong>
            </span>
        @endif
    </div>
    <div class="col-md-12 form-group{{ $errors->has('value') ? ' has-error' : '' }}">
        {!! Form::label('value', 'Value') !!}
        {!! Form::text('value', null, ['class' => 'form-control', 'required' => 'required']) !!}
        @if ($errors->has('value'))
            <span class="help-block">
                <strong>{{ $errors->first('value') }}</strong>
            </span>
        @endif
    </div>
    <div class="col-md-12 form-group{{ $errors->has('available_from') ? ' has-error' : '' }}">
        {!! Form::label('available_from', 'Available From') !!}
        {!! Form::text('available_from', null, ['class' => 'form-control datepicker', 'required' => 'required', 'readonly' => 'readonly']) !!}
        @if ($errors->has('available_from'))
            <span class="help-block">
                <strong>{{ $errors->first('available_from') }}</strong>
            </span>
        @endif
    </div>
    <div class="col-md-12 form-group{{ $errors->has('available_till') ? ' has-error' : '' }}">
        {!! Form::label('available_till', 'Available Till') !!}
        {!! Form::text('available_till', null, ['class' => 'form-control datepicker', 'required' => 'required', 'readonly' => 'readonly']) !!}
        @if ($errors->has('available_till'))
            <span class="help-block">
                <strong>{{ $errors->first('available_till') }}</strong>
            </span>
        @endif
    </div>
</div>

<div class="pull-left">
    {!! Form::submit('Save', ['class' => 'btn btn-primary btn-flat']) !!}
    <a href="{{ backend_url($moduleProperties['controller']) }}" type="button" class="btn btn-default btn-flat">Cancel</a>
</div>