@extends( 'backend.layouts.app' )

@section('title', $moduleProperties['longModuleName'])

@section('CSSLibraries')
    <!-- DataTables CSS -->
    <link href="{{ backend_asset('plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
    <!-- DataTables JavaScript -->
    <script src="{{ backend_asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ backend_asset('plugins/datatables/dataTables.bootstrap.min.js') }}"></script>
    <script src="https://www.gstatic.com/firebasejs/4.2.0/firebase.js"></script>

<script type="text/javascript">
    var config = {
      apiKey: "AIzaSyBfQBYgCO9PFhq7baJfospBSLYwtoq3e74",
      databaseURL: "{{ env('FIREBASE_DATABASE_URL') }}"
    };
    firebase.initializeApp(config);

    firebase.database().ref('/users/{{ $record->prefix_uid }}').on('value', function(snapshot) {
      var userObject = snapshot.val()

      $('#message-sent').text( userObject.hasOwnProperty('messages_sent') ? userObject['messages_sent'] : 0 )
      $('#message-received').text( userObject.hasOwnProperty('messages_received') ? userObject['messages_received'] : 0 )
    });

    var userRef = firebase.database().ref('/users').on('child_changed', function(snapshot, key) {
      if ( snapshot.key === '{{ $record->prefix_uid }}' ) {
        var userObject = snapshot.val()

        $('#message-sent').text( userObject.hasOwnProperty('messages_sent') ? userObject['messages_sent'] : 0 )
        $('#message-received').text( userObject.hasOwnProperty('messages_received') ? userObject['messages_received'] : 0 )
        $('#credits').text( userObject.hasOwnProperty('credits') ? userObject['credits'] : 0 )
      }
    });
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
              <h3 class="box-title">Showing <span style="font-weight: bold;">{{ $record->full_name }}</span> stats</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <dl class="dl-horizontal viewModule">
                <dt>Messages Sent</dt>
                <dd id="message-sent">{{ Html::image( constants('back.theme.modules.tiny_loader')) }}</dd>

                <dt>Messages Received</dt>
                <dd id="message-received">{{ Html::image( constants('back.theme.modules.tiny_loader')) }}</dd>

                <dt>Available Credits</dt>
                <dd id="credits">{{ $record->credits }}</dd>

                <dt>User Total Earning</dt>
                <dd>{{ prefixCurrency($earnedCommission->sum('user_earned')) }}</dd>

                <dt>User Pending Earned</dt>
                <dd>{{ prefixCurrency($pendingCommission->sum('user_earned')) }}</dd>

                <dt>Commission Earned</dt>
                <dd>{{ prefixCurrency($earnedCommission->sum('commission_earned')) }}</dd>

                <dt>Paid To User</dt>
                <dd>{{ prefixCurrency($paidCommission->sum('user_earned')) }}</dd>

                <br />
                <dd>
                      <a href="javascript:void(0)" onclick="window.history.go(-1);" class="btn btn-primary" type="button">Go back</a>
                </dd>

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
