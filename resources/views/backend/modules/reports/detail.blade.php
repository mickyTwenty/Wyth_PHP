@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
    <!-- DataTables CSS -->
    <link href="{{ backend_asset('plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet">

    <style type="text/css">
        .dl-horizontal dt{
            min-width: 250px;
        }
        dd {
            margin-left: 260px !important;
        }
        dd.normal-indent{
            margin-left: 180px !important;
        }
    </style>
@endsection

@section('JSLibraries')
    <!-- DataTables JavaScript -->
    <script src="{{ backend_asset('plugins/daterangepicker/moment.min.js') }}"></script>
    <script src="{{ backend_asset('plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script src="https://www.gstatic.com/firebasejs/4.2.0/firebase.js"></script>

<script type="text/javascript">
    /*var config = {
      apiKey: "AIzaSyBfQBYgCO9PFhq7baJfospBSLYwtoq3e74",
      databaseURL: "{{ env('FIREBASE_DATABASE_URL') }}"
    };
    firebase.initializeApp(config);

    firebase.database().ref('/system').on('value', function(snapshot) {
      var userObject = snapshot.val()

      $('#total_messages').text( userObject.hasOwnProperty('total_messages') ? userObject['total_messages'] : 0 )
    });

    var userRef = firebase.database().ref('/system').on('child_changed', function(snapshot, key) {
        var userObject = snapshot.val()

        $('#total_messages').text( userObject.hasOwnProperty('total_messages') ? userObject['total_messages'] : 0 )
    });*/

    $(document).on('appConfig.initialized', function(handler, appConfig) {
    @if (request()->has('startDate'))
        appConfig.get('app.daterangepicker')['#dateRangePicker1'].data('daterangepicker').setStartDate('{{ Carbon\Carbon::createFromFormat('Y-m-d', request()->input('startDate'))->format('m/d/Y') }}');
        appConfig.get('app.daterangepicker')['#dateRangePicker1'].data('daterangepicker').setEndDate('{{ Carbon\Carbon::createFromFormat('Y-m-d', request()->input('endDate'))->format('m/d/Y') }}');
    @endif
    })

    $('.date-range-picker').on('apply.daterangepicker', function(ev, picker) {
        $('#startDate').val( picker.startDate.format('YYYY-MM-DD') );
        $('#endDate').val( picker.endDate.format('YYYY-MM-DD') );
        $('form#dateFilter').submit();
    })
</script>
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
              <h3 class="box-title">Date Filter</h3>
            </div>
            <div class="box-body">
            @if (property_exists($record, 'filtered'))
                <div class="pull-right">
                    <a href="{{ backend_url('reports') }}" class="btn btn-md btn-danger pull-right">Reset</a>
                </div>
            @endif
                <div class="input-group col-sm-3 col-lg-3 col-xs-12">
                  <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                  </div>
                  <input type="text" class="form-control col-sm-6 col-lg-6 pull-right date-range-picker" id="dateRangePicker1">
                </div>
            </div>
        </div>

        <form method="POST" id="dateFilter">
            {{ csrf_field() }}
            <input type="hidden" name="startDate" id="startDate">
            <input type="hidden" name="endDate" id="endDate">
        </form>

        <div class="box">
            <div class="box-header">
              <h3 class="box-title">Analytics Reports
              @if ( request()->has('startDate') )
                  <small>(Showing from <strong>{{ Carbon\Carbon::createFromFormat('Y-m-d', request()->input('startDate'))->format('m/d/Y') }}</strong> to <strong>{{ Carbon\Carbon::createFromFormat('Y-m-d', request()->input('endDate'))->format('m/d/Y') }}</strong>)</small>
              @endif
              </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <dl class="dl-horizontal viewModule">

                <dt>Business User Revenue</dt>
                <dd>{{ prefixCurrency($record->total_earning) }}</dd>

                <dt>Business User Revenue Pending</dt>
                <dd>{{ prefixCurrency($record->pending_earned) }}</dd>

                <dt>ChitChat Revenue</dt>
                <dd>{{ prefixCurrency($record->commission_earned) }}</dd>

                {{-- <dt>Total Amount Paid To Users</dt>
                <dd>{{ prefixCurrency($record->paid_to_user) }}</dd> --}}

                <dt>Total Business Users</dt>
                <dd>{{ $record->business }}</dd>

                <dt>Total Verified Users</dt>
                <dd>{{ $record->verified }}</dd>

                <dt>Total Normal Users</dt>
                <dd>{{ $record->normal }}</dd>

                <dt>Credits Purchased</dt>
                <dd>{{ $record->credit_purchased . ' (' . prefixCurrency($record->credit_purchased_amount) . ')' }}</dd>

                <dt>Message Sent/Received</dt>
                <dd>{{ $record->messages }}</dd>

                <dt>Unrespond Messages</dt>
                <dd>{{ $record->unresponded_messages }}</dd>

              </dl>
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
