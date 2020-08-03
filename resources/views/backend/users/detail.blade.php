@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
@endsection

@section('JSLibraries')
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>{{ $moduleProperties['longModuleName'] }}
      </h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @include( 'backend.layouts.notification_message' )

        <div class="box">
            <div class="box-header">
              <h3 class="box-title">User detail</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <dl class="dl-horizontal viewModule">

                <dt>Full Name</dt>
                <dd>{{ $record->full_name }}</dd>

                <dt>Account Type</dt>
                <dd>{{ ucfirst($record->user_role_key_web) }}</dd>

                <dt>Email</dt>
                <dd>{{ $record->email }}</dd>

                <dt>Phone</dt>
                <dd>{{ $record->phone }}</dd>

                <dt>Postal Code</dt>
                <dd>{{ isset($userMeta['postal_code']) ? $userMeta['postal_code'] : '' }}</dd>

                <dt>Address</dt>
                <dd>{{ $record->address }}</dd>

                <dt>City</dt>
                <dd>{{ $record->city_title }}</dd>

                <dt>State</dt>
                <dd>{{ $record->state_title }}</dd>

                <dt>Birth Date</dt>
                <dd>{{ isset($userMeta['birth_date']) ? $userMeta['birth_date'] : '' }}</dd>

                <dt>Gender</dt>
                <dd>{{ isset($userMeta['gender']) ? $userMeta['gender'] : '' }}</dd>

                <dt>College Name</dt>
                <dd>{{ isset($userMeta['school_name']) ? $userMeta['school_name'] : '' }}</dd>

                <dt>Student Organization</dt>
                <dd>{{ isset($userMeta['student_organization']) ? $userMeta['student_organization'] : '' }}</dd>

                <dt>Graduation Year</dt>
                <dd>{{ isset($userMeta['graduation_year']) ? $userMeta['graduation_year'] : '' }}</dd>

                @if ($record->isDriver())
                  {{-- <dt>Driving License No</dt>
                  <dd>{{ isset($userMeta['driving_license_no']) ? $userMeta['driving_license_no'] : '' }}</dd> --}}

                  <dt>Vehicle Type</dt>
                  <dd>{{ isset($userMeta['vehicle_type']) ? $userMeta['vehicle_type'] : '' }}</dd>

                @endif

                <dt>Email Verified</dt>
                <dd>{{ $record->email_verification == '1' ? 'Yes' : 'Pending' }}</dd>

                <dt>Display Picture</dt>
                <dd>{!! Html::image($record->profile_picture_auto, null, ['class' => 'img-responsive', 'style' => 'max-width: 100px;max-height: 100px']) !!}</dd>

                <dt>Date Registered</dt>
                <dd>{{ $record->created_at->format(constants('back.theme.modules.datetime_format')) }}</dd>

                <dt>Registered As</dt>
                <dd>{{ ($driverMeta->has('was_passenger') && $driverMeta->get('was_passenger')) ? 'Passenger' : 'Driver' }}</dd>

                @if($driverMeta->has('was_passenger'))
                <dt style="white-space: initial;">User Agreement Signed Date</dt>
                <dd>{{ Carbon\Carbon::parse($driverMeta->get('driver_upgrade_time'))->format(constants('back.theme.modules.datetime_format')) }}</dd>
                @endif

                <br />
                <dd>
                      <a href="{{ backend_url('users/index') }}" class="btn btn-primary" type="button">Go back</a>
                </dd>

              </dl>
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->

        <div class="box">
            <div class="box-header">
              <h3 class="box-title">Driver Bank Details</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <dl class="dl-horizontal viewModule">

            @if (!$record->bankAccount)
                <dd>Driver hasn't linked any bank account yet.</dd>
            @else

                {{-- <dt>Bank Name</dt>
                <dd>{{ $record->bankAccount->bank_name }}</dd> --}}

                <dt>Account Title</dt>
                <dd>{{ $record->bankAccount->account_title }}</dd>

                <dt>Account Number</dt>
                <dd>{{ $record->bankAccount->account_number }}</dd>

                <dt>Routing Number</dt>
                <dd>{{ $record->bankAccount->routing_number }}</dd>

                <dt>SSN Last 4 Digits</dt>
                <dd>{{ $record->bankAccount->ssn_last_4 }}</dd>

            @endif

              </dl>
            </div>
        </div>

        @include( 'backend.layouts.modal' )

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
@endsection
