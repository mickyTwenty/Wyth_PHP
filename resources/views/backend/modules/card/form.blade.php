<div class="row">
    <div class="col-md-6 form-group{{ $errors->has('card_number') ? ' has-error' : '' }}">
        {!! Form::label('card_number', 'Card Number') !!}
        {!! Form::number('card_number', null, ['class' => 'form-control', 'required' => 'required']) !!}
        @if ($errors->has('card_number'))
            <span class="help-block">
                <strong>{{ $errors->first('card_number') }}</strong>
            </span>
        @endif
    </div>
    
    <div class="col-md-6 form-group{{ $errors->has('value_riyal') ? ' has-error' : '' }}">
        {!! Form::label('value_riyal', 'Amount Riyal') !!}
        {!! Form::select('value_riyal', constants('global.card_group'), null, ['class' => 'form-control', 'required' => 'required']) !!}
        @if ($errors->has('value_riyal'))
            <span class="help-block">
                <strong>{{ $errors->first('value_riyal') }}</strong>
            </span>
        @endif
    </div>
</div>

<div class="row">    
    <div class="col-md-6 form-group{{ $errors->has('network_id') ? ' has-error' : '' }}">
        {!! Form::label('network_id', 'Select Network') !!}
        {!! Form::select('network_id', $networks, null, ['class' => 'form-control', 'required' => 'required']) !!}
        @if ($errors->has('network_id'))
            <span class="help-block">
                <strong>{{ $errors->first('network_id') }}</strong>
            </span>
        @endif
    </div>
</div>

<div class="pull-left">
    {!! Form::submit('Save', ['class' => 'btn btn-primary btn-flat']) !!}
    <a href="{{ backend_url($moduleProperties['controller']) }}" type="button" class="btn btn-default btn-flat">Cancel</a>
</div>