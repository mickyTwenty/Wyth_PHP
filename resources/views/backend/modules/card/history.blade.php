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
              <h3 class="box-title">Showing all data</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <dl class="dl-horizontal viewModule">
                <dt>Network</dt>
                <dd>{{ $record->network->label_en }}</dd>

                <dt>Card Number</dt>
                <dd>{{ $record->card_number }}</dd>

                <dt>Card Group</dt>
                <dd>{{ $record->value_riyal }}</dd>

                <dt>Date Created</dt>
                <dd>{{ $record->created_at->format( constants('back.theme.modules.datetime_format') ) }}</dd>

                <hr><h4>Sell Information</h4>

                @if ( isset($record->purchased) )

                <dt>Sold</dt>
                <dd>Yes</dd>

                <dt>Payment Status</dt>
                <dd>{!! $record->purchased->is_completed_text_formatted !!}</dd>

              @if ( $record->purchased->user->isDealer() )
                <dt>Is Dealer?</dt>
                <dd>Yes</dd>

                <dt>Dealer Name</dt>
                <dd>{{ $record->purchased->user->full_name }}</dd>

                <dt>Dealer Number</dt>
                <dd>{{ $record->purchased->user->mobile }}</dd>
              @else
                <dt>Is Dealer?</dt>
                <dd>No</dd>

                <dt>Customer Name</dt>
                <dd>{{ $record->purchased->user->full_name }}</dd>

                <dt>Customer Number</dt>
                <dd>{{ $record->purchased->user->mobile }}</dd>

                <dt>Used By Customer</dt>
                <dd>{!! $record->is_used_text_formatted !!}</dd>

                <dt>Mobile Used</dt>
                <dd>{{ $record->purchased->mobile }}</dd>

                <dt>Recharge Method</dt>
                <dd>{{ ucfirst($record->purchased->recharge_method) }}</dd>
              @endif

                <dt>Payment Method</dt>
                <dd>{{ ucfirst($record->purchased->payment_method) }}</dd>

                <dt>Payment Date</dt>
                <dd>{{ $record->purchased->created_at->format( constants('back.theme.modules.datetime_format') ) }}</dd>

                @else

                <dt>Sold</dt>
                <dd>No</dd>

                @endif

                @if ( $activity->count() )
                <hr><h4>Card Activity History</h4>

                <table class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>Action</th>
                      <th>Date Time</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach( $activity as $userActivity )
                    <tr>
                      <td>{{ $userActivity->translate_action }}</td>
                      <td>{{ $userActivity->created_at->format( constants('back.theme.modules.datetime_format') ) }}</td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
                @endif

                <br />
                <dd>
                      <a href="javascript:void(0)" onclick="window.history.go(-1);" class="btn btn-primary" type="button">Go back</a>
                  @if ( !in_array($record->id, array_get($moduleProperties, 'undeleteable', [])) )
                      <a class="btn btn-danger delete" type="button" title="Delete" data-toggle="modal" data-target="#deleteModal{{ $record->id }}">
                          <i class="fa fa-trash"></i>
                      </a>
                      
                      <div id="deleteModal{{ $record->id }}" class="modal fade" role="dialog">
                          <div class="modal-dialog">
                          <div class="modal-content">
                          <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                          <h4 class="modal-title">Confirm Delete</h4>
                          </div>
                          <div class="modal-body">
                          <p>Are you sure you want to delete this record?</p>
                          </div>
                          <div class="modal-footer">
                          <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Close</button>
                          {!! Form::model($record, ['method' => 'delete', 'url' => 'backend/'.$moduleProperties['controller'].'/'.$record->id, 'class' =>'form-inline form-delete']) !!}
                          {!! Form::hidden('id', $record->id) !!}
                          {!! Form::submit('Yes', ['class' => 'btn btn-success btn-flat']) !!}
                          {!! Form::close() !!}
                          </div>
                          </div>
                          </div>
                      </div>
                  @endif
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