@extends( 'backend.layouts.app' )

@section('title', 'Hot Destinations')

@section('CSSLibraries')
    <!-- DataTables CSS -->
    <link href="{{ backend_asset('plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet">
    <link href="{{ backend_asset('plugins/colorbox/colorbox.css') }}" rel="stylesheet">
@endsection

@section('JSLibraries')
<!-- DataTables JavaScript -->
<script src="{{ backend_asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ backend_asset('plugins/datatables/dataTables.bootstrap.min.js') }}"></script>
<script src="{{ backend_asset('plugins/colorbox/jquery.colorbox-min.js') }}"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/6.1.1/highcharts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/6.1.1/highcharts-more.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/6.1.1/modules/exporting.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/6.1.1/modules/export-data.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/6.1.1/modules/no-data-to-display.js"></script>

<script type="text/javascript">
  
  Highcharts.chart('container', {

      chart: {
          type: 'bubble',
          plotBorderWidth: 1,
      },

      legend: {
          enabled: false
      },

      title: {
          text: 'Hot Destinations'
      },

      subtitle: {
          text: ''
      },

      xAxis: {
        visible: false
      },

      yAxis: {
        visible: false
      },

      lang: {
        noData: "No data to display"
      },
      noData: {
          style: {
              fontWeight: 'bold',
              fontSize: '15px',
              color: '#303030'
          }
      },

      tooltip: {
          useHTML: true,
          headerFormat: '<table>',
          pointFormat: '<tr><th colspan="2"><h3>{point.city}</h3></th></tr>' +
              '<tr><th>Total Trips:</th><td>{point.total}</td></tr>',
          footerFormat: '</table>',
          followPointer: true
      },

      plotOptions: {
          bubble: {
           minSize: '30',
           maxSize: '100'
          },
          series: {
              dataLabels: {
                  enabled: true,
                  format: '{point.name}'
              }
          }
      },

      series: [{
          data: [
              @if (count($records))
                @foreach ($records as $key => $record)
                  { y: {{ $record->total }}, z: {{ $record->total }}, total: {{ $record->total }}, name: '{{ $record->shortname }}', city: '{{ $record->destination_city }}' },
                @endforeach
              @endif
          ]
      }]

  });
</script>
@endsection

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>Trips</h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @include( 'backend.layouts.notification_message' )

        <div class="box">
            <div class="box-header"></div>
            <!-- /.box-header -->
            <div class="box-body">
              <div id="container" style="height: 400px; min-width: 310px; max-width: 100%; margin: 0 auto"></div>
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
