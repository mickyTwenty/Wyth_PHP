@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
    <!-- DataTables CSS -->
    <link href="{{ backend_asset('plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/colorbox/colorbox.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/chosen/chosen.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/select2/select2.min.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
<!-- DataTables JavaScript -->
<script src="{{ backend_asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ backend_asset('plugins/datatables/dataTables.bootstrap.min.js') }}"></script>
<script src="{{ backend_asset('plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ backend_asset('plugins/colorbox/jquery.colorbox-min.js') }}"></script>
<script src="{{ backend_asset('plugins/chosen/chosen.jquery.min.js') }}"></script>

<script type="text/javascript">
  $(function(){
    appConfig.set('yajrabox.ajax', base_url + '/users/data');
    appConfig.set('yajrabox.ajax.data', function(data) {
      data.state              = jQuery('select[name=state]').val();
      data.city               = jQuery('select[name=city]').val();
      data.gender             = jQuery('select[name=gender]').val();
      data.age                = jQuery('select[name=age]').val();
      data.role_id            = jQuery('select[name=role_id]').val();
      data.school_name        = jQuery('select[name=school_name]').val();
      data.email_verification = jQuery('select[name=email_verification]').val();
    });
    appConfig.set('yajrabox.columns', [
        {data: 'profile_picture', orderable: false, searchable: false},
        {data: 'first_name'},
        {data: 'last_name'},
        {data: 'email'},
        {data: 'role_id'},
        {data: 'created_at'},
        {data: 'active'},
        {data: 'action', orderable: false, searchable: false}
    ]);
  })
</script>
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>{{ $moduleProperties['longModuleName'] }}
      <div class="pull-right">
        <a href="{{ backend_url($moduleProperties['controller'].'/create/passenger') }}" type="button" class="btn btn-primary btn-flat">New Passenger</a>
        <a href="{{ backend_url($moduleProperties['controller'].'/create/driver') }}" type="button" class="btn btn-primary btn-flat">New Driver</a>
      </div>
      </h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @include( 'backend.layouts.notification_message' )

        <div class="box">
            <div class="box-header">
              <h3 class="box-title">All {{ str_plural($moduleProperties['longModuleName']) }}</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <div class="filter-tools">
                 <div class="btn-group button-container pull-right">
                    <button type="button" class="btn btn-flat btn-default clear-data">
                    <i class="fa fa-refresh" aria-hidden="true"></i>
                        Clear Filter
                    </button>
                    <button type="button" class="btn btn-flat btn-default filter-data">
                    <i class="fa fa-filter" aria-hidden="true"></i> Filter
                    </button>
                 </div>
              </div>

              <div class="clearfix"></div>

              <div class="grid-filter">
                  <form id="filter-form">
                      <div class="row">
                        <div class="col-sm-3">
                            <label>State</label>
                            {!! Form::select('state', $states, null, ['class' => 'form-control chosen', 'id' => 'ddl_states']) !!}
                        </div>
                        <div class="col-sm-3">
                            <label>City</label>
                            {!! Form::select('city', $cities, null, ['class' => 'form-control chosen', 'id' => 'ddl_cities']) !!}
                        </div>
                        <div class="col-sm-3">
                            <label>Gender</label>
                            {!! Form::select('gender', $genders, null, ['class' => 'form-control chosen']) !!}
                        </div>
                        <div class="col-sm-3">
                            <label>Age Range</label>
                            {!! Form::select('age', $ageRanges, null, ['class' => 'form-control chosen']) !!}
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-sm-3">
                            <label>User Type</label>
                            {!! Form::select('role_id', $userTypes, null, ['class' => 'form-control chosen']) !!}
                        </div>
                        <div class="col-sm-3">
                            <label>College</label>
                            {!! Form::select('school_name', [], null, ['class' => 'form-control', 'id' => 'school_name']) !!}
                        </div>
                        <div class="col-sm-3">
                            <label>Verified</label>
                            {!! Form::select('email_verification', ['1' => 'Yes', '0' => 'No'], null, ['class' => 'form-control']) !!}
                        </div>
                      </div>
                  </form>
              </div>

              <div class="clearfix"></div>

              <br>

              <table class="table table-bordered table-striped yajrabox">
                <thead>
                <tr>
                  <th width="80">Picture</th>
                  <th>First Name</th>
                  <th>Last Name</th>
                  <th>Email</th>
                  <th>Type</th>
                  <th width="130">Date Registered</th>
                  <th width="50">Status</th>
                  <th width="120">Action</th>
                </tr>
                </thead>
              </table>
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->

        @include( 'backend.layouts.modal' )

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
@endsection

@section('inlineJS')
    <script type="text/javascript">
        var url='{!! URL::to('/') !!}';

        // Fetch cities
        $('#ddl_states').on('change', function() {
            $.getJSON(url + '/backend/cities/' + this.value, function(data) {
                var options = $("#ddl_cities");
                options.html('');
                  options.append($('<option></option>').val('').html('Select City'));
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

          $('.clear-data').on('click', function(event) {
            $('#school_name').val(null).trigger('change.select2');
          });
        });
    </script>
@endsection