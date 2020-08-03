<?php

namespace App\Models;

use App\Events\TripRideRouteCreated;
use Illuminate\Database\Eloquent\Model;

class TripRideRoute extends Model
{
    /**
     * This is the most important feature of this project's search
     * because, trip search will result the listing with in the specified
     * radius of each stepped point.
     */
    const RADIUS_BUFFER = 80468; // 50 miles (value in meter)

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'stepped_route',
        'route_polygon',
        'actual_route_polygon',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $events = [
        'created' => TripRideRouteCreated::class,
    ];

    public function setUpdatedAt($value)
    {
        return $this;
    }

    public function getUpdatedAtColumn()
    {
        return null;
    }

    public static function createRawPolygonsOfRoute(array $coordinates, $radiusBuffer)
    {
        # code...
    }

    public static function optimizeRoute(array $coordinates)
    {
        $routes = [];
        foreach ($coordinates as $key => $value) {
            if ( !isset($coordinates[$key+1]) ) {
                $routes = array_merge($routes, [$value]);
                break;
            }

            $expanded = bifurcateCoOrdinates($value, $coordinates[$key+1], self::RADIUS_BUFFER);

            $routes = array_merge($routes, $expanded);
        }

        return $routes;
    }
}
