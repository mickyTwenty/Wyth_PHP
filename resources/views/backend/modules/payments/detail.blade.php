@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
@endsection

@section('JSLibraries')
    <script src="https://www.gstatic.com/firebasejs/4.2.0/firebase.js"></script>
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
              <h3 class="box-title">{{ $moduleProperties['shortModuleName'] }} Detail</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <dl class="dl-horizontal viewModule">

                <dt>Trip Name</dt>
                <dd>{{ $record->trip_name }}</dd>

                <dt>Origin</dt>
                <dd>{{ $record->origin_title }}</dd>

                <dt>Destination</dt>
                <dd>{{ $record->destination_title }}</dd>

                <dt>Driver</dt>
                <dd>{{ $record->driver->full_name }}</dd>

                <dt>Initiated By</dt>
                <dd>{{ $record->passenger->full_name }}</dd>

                <dt>Initiator Type</dt>
                <dd>{{ ucfirst($record->initiated_type) }}</dd>

                <dt>Estimates</dt>
                <dd>{{ $record->estimates }}</dd>

                <dt>Driver's Payout</dt>
                <dd>{{ ucfirst($record->payout_type) }}</dd>

                <dt>Driver's Earning</dt>
                <dd>{{ prefixCurrency( $record->getEarningObject('earning') ) }}</dd>

                <dt>{{ constants('global.site.name') }}'s Earning</dt>
                <dd>{{ prefixCurrency( $record->getEarningObject('commission') ) }}</dd>

                <dt>Paid to Driver</dt>
                <dd>{!! $record->isDriverPaid ? '<span class="label label-success">Yes</span>' : '<span class="label label-danger">No</span>' !!}</dd>

                <dt>Round Trip</dt>
                <dd>{!! $record->round_trip_status !!}</dd>

                <dt>Date/Time</dt>
                <dd>{{ $record->created_at->format(constants('back.theme.modules.datetime_format')) }}</dd>
              </dl>

              @if (count($transactions))
                <div class="box-header">
                  <h3 class="box-title">Transactions</h3>
                </div>

                <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Passenger</th>
                        <th>Stripe Charge ID</th>
                        <th>Amount</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($transactions as $transaction)
                        <tr>
                          <td>{{ $transaction->user->full_name }}</td>
                          <td><span class="label label-success">{{ $transaction->stripe_charge_id }}</span></td>
                          <td>{{ prefixCurrency($transaction->amount) }}</td>
                          <td>{{ $transaction->created_at->format(constants('back.theme.modules.datetime_format')) }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                  <br>
              @endif

              @if (count($refundedTransactions))
                <div class="box-header">
                  <h3 class="box-title">Refunded Transactions</h3>
                </div>

                <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Passenger</th>
                        <th>Stripe Charge ID</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Refunded Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($refundedTransactions as $transaction)
                        <tr>
                          <td>{{ $transaction->user->full_name }}</td>
                          <td><span class="label label-success">{{ $transaction->stripe_charge_id }}</span></td>
                          <td>{{ prefixCurrency($transaction->amount) }}</td>
                          <td>{{ $transaction->created_at->format(constants('back.theme.modules.datetime_format')) }}</td>
                          <td>{{ $transaction->refunded_at->format(constants('back.theme.modules.datetime_format')) }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                  <br>
              @endif

              <div class="row">
                <div class="col-md-12">
                      <a href="javascript:void(0)" onclick="window.history.go(-1);" class="btn btn-primary" type="button">Go back</a>
                </div>
              </div>
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
