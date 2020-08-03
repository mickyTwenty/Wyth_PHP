@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Reset Password</div>

                <div class="panel-body">

                    @if (session('status'))
                        <div class="alert alert-success" style="margin-bottom: 0px;">
                            {{ session('status') }}
                        </div>
                    @else
                        <div class="alert alert-warning" style="margin-bottom: 0px;">
                            Your sessoin has been timed-out.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
