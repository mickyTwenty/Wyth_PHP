@extends( 'backend.layouts.app' )

@section('title', 'Edit Profile')

@section('CSSLibraries')
    <link href="{{ backend_asset('plugins/select2/select2.min.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/colorbox/colorbox.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
    <script src="{{ backend_asset('plugins/select2/select2.full.min.js') }}"></script>
    <script src="{{ backend_asset('plugins/colorbox/jquery.colorbox-min.js') }}"></script>
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>Edit Profile
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
                  {!! Form::model($record, ['method' => 'POST', 'url' => route('backend.profile.setting'), 'files' => 'true']) !!}
                    @include( 'backend/settings/profile_form' )
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
