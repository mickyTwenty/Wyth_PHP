<div class="row">
    <div class="col-md-12 form-group{{ $errors->has('title') ? ' has-error' : '' }}">
        {!! Form::label('title', 'Title') !!}
        {!! Form::text('title', null, ['class' => 'form-control']) !!}
        @if ($errors->has('title'))
            <span class="help-block">
                <strong>{{ $errors->first('title') }}</strong>
            </span>
        @endif
    </div>
</div>
<div class="row">
    <div class="col-md-12 form-group{{ $errors->has('content') ? ' has-error' : '' }}">
        {!! Form::label('content', 'Content') !!}
        {!! Form::textarea('content', null, ['class' => 'form-control']) !!}
        @if ($errors->has('content'))
            <span class="help-block">
                <strong>{{ $errors->first('content') }}</strong>
            </span>
        @endif
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="form-group {{ $errors->first('type', 'has-error') }}">
            {!! Form::label('type', 'Type') !!}
            {!! Form::select('type', ['Driver' => 'Driver', 'Passenger' => 'Passenger'], null, ['class' => 'form-control chosen']) !!}
            {!! $errors->first('type', '<span class="help-block">:message</span> ') !!}
        </div>
    </div>
</div>

<div class="pull-left">
    {!! Form::submit('Save', ['class' => 'btn btn-primary btn-flat']) !!}
    <a href="{{ backend_url($moduleProperties['controller']) }}" type="button" class="btn btn-default btn-flat">Cancel</a>
</div>

@section('inlineJS')
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#content').wysihtml5();
        });
    </script>
@endsection
