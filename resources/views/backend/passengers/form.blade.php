<div class="col-sm-6">
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('first_name', 'has-error') }}">
                {!! Form::label('first_name', 'First Name *') !!}
                {!! Form::text('first_name', null, ['class' => 'form-control']) !!}
                {!! $errors->first('first_name', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('last_name', 'has-error') }}">
                {!! Form::label('last_name', 'Last Name *') !!}
                {!! Form::text('last_name', null, ['class' => 'form-control']) !!}
                {!! $errors->first('last_name', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('email', 'has-error') }}">
                {!! Form::label('email', 'Email *') !!}
                {!! Form::text('email', null, ['class' => 'form-control']) !!}
                {!! $errors->first('email', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('password', 'has-error') }}">
                {!! Form::label('password', 'Password') !!}
                {!! Form::password('password', ['class' => 'form-control']) !!}
                {!! $errors->first('password', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('phone', 'has-error') }}">
                {!! Form::label('phone', 'Phone *') !!}
                {!! Form::text('phone', null, ['class' => 'form-control']) !!}
                {!! $errors->first('phone', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('birth_date', 'has-error') }}">
                {!! Form::label('birth_date', 'Birth Date') !!}
                {!! Form::text('birth_date', null, ['class' => 'form-control datepicker', 'readonly' => 'readonly']) !!}
                {!! $errors->first('birth_date', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('gender', 'has-error') }}">
                {!! Form::label('gender', 'Gender *') !!}
                {!! Form::select('gender',
                 ['' => 'Please Select Gender','Male' => 'Male', 'Female' => 'Female'],
                 null,
                 ['class' => 'form-control chosen']) !!}
                {!! $errors->first('gender', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('profile_picture', 'has-error') }}">
                {!! Form::label('profile_picture', 'Profile Picture') !!}
                {!! Form::file('profile_picture', ['class' => 'form-control']) !!}
                {!! $errors->first('profile_picture', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                @if(isset($record) && $record->profile_picture_auto)
                    <img width="80" height="80" src='{!! $record->profile_picture_auto !!}' />
                @endif
            </div>
        </div>
    </div>
</div>
<div class="col-sm-6">
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('address', 'has-error') }}">
                {!! Form::label('address', 'Address *') !!}
                {!! Form::text('address', null, ['class' => 'form-control']) !!}
                {!! $errors->first('address', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('postal_code', 'has-error') }}">
                {!! Form::label('postal_code', 'Postal Code *') !!}
                {!! Form::text('postal_code', null, ['class' => 'form-control']) !!}
                {!! $errors->first('postal_code', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('state', 'has-error') }}">
                {!! Form::label('state', 'State *') !!}
                {!! Form::select('state', $states, null, ['class' => 'form-control chosen', 'id' => 'ddl_states']) !!}
                {!! $errors->first('state', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('city', 'has-error') }}">
                {!! Form::label('city', 'City *') !!}
                {!! Form::select('city', $cities, null, ['class' => 'form-control chosen', 'id' => 'ddl_cities']) !!}
                {!! $errors->first('city', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('school_name', 'has-error') }}">
                {!! Form::label('school_name', 'College Name *') !!}
                {!! Form::select('school_name', [], null, ['class' => 'form-control']) !!}
                {!! $errors->first('school_name', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('student_organization', 'has-error') }}">
                {!! Form::label('student_organization', 'Student Organization') !!}
                {!! Form::text('student_organization', null, ['class' => 'form-control']) !!}
                {!! $errors->first('student_organization', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group {{ $errors->first('graduation_year', 'has-error') }}">
                {!! Form::label('graduation_year', 'Graduation Year') !!}
                {!! Form::text('graduation_year', null, ['class' => 'form-control']) !!}
                {!! $errors->first('graduation_year', '<span class="help-block">:message</span> ') !!}
            </div>
        </div>
    </div>
</div>

<div class="col-md-12">
    {!! Form::submit('Save!',['class' => 'btn btn-primary']) !!}
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

        jQuery(document).ready(function($) {
            var schoolUrl = '{{ route('find.school') }}';

            $('#school_name').select2({
                placeholder: "Select College",
                minimumInputLength: 2,
                ajax: {
                    url: schoolUrl,
                    dataType: 'json',
                    delay: 500,
                    data: function (params) {
                        return {
                            term: $.trim(params.term)
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            });

            @if (old('school_name'))
                var schoolData = {
                    id: '{!! old('school_name') !!}',
                    text: '{!! old('school_name') !!}'
                };

                var newOption = new Option(schoolData.text, schoolData.id, false, false);
                $('#school_name').append(newOption).trigger('change');
            @endif

            @if (isset($record) && $record->school_name)
                var schoolData = {
                    id: '{{ $record->school_name }}',
                    text: '{{ $record->school_name }}'
                };

                var newOption = new Option(schoolData.text, schoolData.id, false, false);
                $('#school_name').append(newOption).trigger('change');
            @endif
        });
    </script>
@endsection