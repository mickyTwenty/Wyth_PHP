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
              <h3 class="box-title">User purchases</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">

                <div class="container-fluid">
                    <table class="table table-responsive table-bordered">
                        <thead>
                            <tr>
                                <th>Credit</th>
                                <th>In-App Earning</th>
                                <th>Purchase Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (($purchases = $record->purchases) && $purchases->count())
                                @foreach ($purchases as $element)
                                    {!! '<tr>
                                    <td>'.$element->credits.'</td>
                                    <td>'.prefixCurrency(calculatePercentage($element->amount, 70)).'</td>
                                    <td>'.$element->created_at->format(constants('back.theme.modules.datetime_format')).'</td>
                                </tr>' !!}
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3">This user haven't made any purchase yet.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

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
