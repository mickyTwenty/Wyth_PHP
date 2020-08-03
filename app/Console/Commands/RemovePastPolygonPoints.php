<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use DB;

class RemovePastPolygonPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-polygons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove past polygon points for optimized search results.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('Removing excessive geometrical points which is of no use.');

        // $fromStartTime = Carbon::now()->subDay()->format('Y-m-d H:i:s'); // CURRENT - 1 day

        // NOTE: Only retaining 1 days old polygon-points
        $query = "
        DELETE
          trp2.*
        FROM
          trip_ride_polygon AS trp2
          JOIN
            (SELECT
              trr.id AS trip_ride_route_id
            FROM
              trips AS t
              INNER JOIN trip_rides AS tr
                ON tr.trip_id = t.id
              INNER JOIN trip_ride_routes AS trr
                ON trr.trip_ride_id = tr.id
              INNER JOIN trip_ride_polygon AS trp
                ON trp.trip_ride_route_id = trr.id
            WHERE tr.start_time < UTC_TIMESTAMP - INTERVAL 2 DAY
            GROUP BY trr.id) t2
            ON t2.trip_ride_route_id = trp2.trip_ride_route_id;
        ";

        // $this->line($query);return;
        $result = DB::statement($query);

        $this->line( $result ? 'Deleted rows' : 'Error while executing command' );
    }
}
