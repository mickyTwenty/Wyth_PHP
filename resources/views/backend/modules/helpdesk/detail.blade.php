@extends( 'backend.layouts.app' )

@section('title', 'Consultancy Request')

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Ticket
        <small>Customer helpdesk</small>
      </h1>
      <div class="breadcrumb" style="top: 5px;">
        <div class="" style="margin-top: 6px; font-size: 18px;">Status: {!! $record->status_text_formatted !!}</div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">

      @include( 'backend.layouts.notification_message' )

      <div class="box box-success">
        <div class="box-header">
          <i class="fa fa-comments-o"></i>
          <h3 class="box-title">Ticket Detail</h3>
        </div>
        <div class="box-body chat" id="chat-box">
          <!-- chat item -->
          <div class="item">
            <img src="{{ asset( $record->user->pic ) }}" alt="{{ $record->user->full_name }}" class="offline">

            <p class="message">
              <a href="#" class="name">
                <small class="text-muted pull-right"><i class="fa fa-clock-o"></i> {{ $record->created_at->format('h:i a') }}</small>
                {{ $record->user->full_name }}
              </a>
              {{ $record->detail }}
            </p>
            <div class="attachment">
              <h4>Customer Details:</h4>

              <p style="font-style: normal;">
                Name: {{ $record->name }}<br />
                Mobile: {{ $record->mobile }}<br />
                Email: {{ $record->email }}<br />
                Card #: {{ $record->card }}<br />
                Case: {{ $record->case }}<br />
              </p>
            </div>
            <!-- /.attachment -->
          </div>
          <!-- /.item -->
        @foreach( $record->replies as $message )
          <!-- chat item -->
          <div class="item">
            <img src="{{ asset( $message->user->pic ) }}" alt="{{ $message->user->full_name }}" class="{{ $message->user->id == $record->user_id ? 'offline' : 'online' }}">

            <p class="message">
              <a href="#" class="name">
                <small class="text-muted pull-right"><i class="fa fa-clock-o"></i> {{ $message->created_at->format('h:i a') }}</small>
                {{ $message->user->full_name }}
              </a>
              {{ $message->message }}
            </p>
          </div>
          <!-- /.item -->
        @endforeach
        </div>
        <!-- /.chat -->
      @if ( $record->replies->count() == 0 )
        <div class="box-footer">
        {!! Form::open(['url' => 'backend/'.$moduleProperties['controller'].'/reply/'.$record->id]) !!}
          <div class="input-group">
            <input class="form-control" name="message" placeholder="Type message...">

            <div class="input-group-btn">
              <button type="submit" class="btn btn-success"><i class="fa fa-plus"></i></button>
            </div>
          </div>
        {!! Form::close() !!}
        </div>
      @endif
      </div>
      <!-- /.box (chat box) -->

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
@endsection