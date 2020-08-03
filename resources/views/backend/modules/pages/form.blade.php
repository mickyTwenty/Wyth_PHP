<div class="row">
    <div class="col-md-12 form-group{{ $errors->has('title') ? ' has-error' : '' }}">
        {!! Form::label('title', 'Page Title') !!}
        {!! Form::text('title', null, ['class' => 'form-control', 'required' => 'required']) !!}
        @if ($errors->has('title'))
            <span class="help-block">
                <strong>{{ $errors->first('title') }}</strong>
            </span>
        @endif
    </div>

    <div class="col-md-12 form-group{{ $errors->has('content_en') ? ' has-error' : '' }}">
        {!! Form::label('content_en', 'Content (EN)') !!}
        {!! Form::textarea('content_en', null, ['class' => 'form-control', 'required' => 'required']) !!}
        @if ($errors->has('content_en'))
            <span class="help-block">
                <strong>{{ $errors->first('content_en') }}</strong>
            </span>
        @endif
    </div>

    <div class="col-md-12 form-group{{ $errors->has('content_ar') ? ' has-error' : '' }}">
        {!! Form::label('content_ar', 'Content (AR)') !!}
        {!! Form::textarea('content_ar', null, ['class' => 'form-control', 'required' => 'required']) !!}
        @if ($errors->has('content_ar'))
            <span class="help-block">
                <strong>{{ $errors->first('content_ar') }}</strong>
            </span>
        @endif
    </div>
</div>

<div class="pull-left">
    {!! Form::submit('Save', ['class' => 'btn btn-primary btn-flat']) !!}
    <a href="{{ backend_url($moduleProperties['controller']) }}" type="button" class="btn btn-default btn-flat">Cancel</a>
</div>

@section('inlineJS')
<script type="text/javascript">
    $(document).ready(function(){
        CKEDITOR.replace('content_en');
        CKEDITOR.replace('content_ar');
    });
</script>
@endsection