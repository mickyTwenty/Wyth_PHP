<div class="row">
    <div class="col-md-12 form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', 'College Name') !!}
        {!! Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) !!}
        @if ($errors->has('name'))
            <span class="help-block">
                <strong>{{ $errors->first('name') }}</strong>
            </span>
        @endif
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="form-group {{ $errors->first('state', 'has-error') }}">
            {!! Form::label('state', 'State *') !!}
            {!! Form::select('state_id', $states, null, ['class' => 'form-control chosen', 'id' => 'ddl_states']) !!}
            {!! $errors->first('state', '<span class="help-block">:message</span> ') !!}
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="form-group {{ $errors->first('city', 'has-error') }}">
            {!! Form::label('city', 'City *') !!}
            {!! Form::select('city_id', $cities, null, ['class' => 'form-control chosen', 'id' => 'ddl_cities']) !!}
            {!! $errors->first('city', '<span class="help-block">:message</span> ') !!}
        </div>
    </div>
</div>

<div class="pull-left">
    {!! Form::submit('Save', ['class' => 'btn btn-primary btn-flat']) !!}
    <a href="{{ backend_url($moduleProperties['controller']) }}" type="button" class="btn btn-default btn-flat">Cancel</a>
</div>

@section('inlineJS')
    <script type="text/javascript">
        var url='{!! URL::to('/') !!}';

        // Fetch cities
        $('#ddl_states').on('change', function() {
            $.getJSON(url + '/backend/cities/' + this.value, function(data) {
                var options = $("#ddl_cities");
                options.html('');
                $.each(data, function(key, val) {
                    options.append($('<option></option>').val(key).html(val));
                });

                options.trigger('chosen:updated')
            });
        })
    </script>
@endsection
