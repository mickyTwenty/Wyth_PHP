<?php

function renameKeyAsValue(array $array)
{
    return array_combine($array, $array);
}

/**
 * Function to split name into first_name & last_name
 * @param  string $name
 * @return array
 */
function str_split_name($name)
{
    $name = trim($name);
    $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
    $first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
    return array($first_name, $last_name);
}

function calculatePercentage($totalAmount, $percentage, $returnDecimal=2)
{
    $value = ($totalAmount * $percentage / 100);

    return (string) ($returnDecimal ? number_format($value, $returnDecimal, '.', '') : $value);
}

function calculateAverageByArray(array $values)
{
    return array_sum($values) / count($values);
}

function valid_email($value)
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Usage: if ( ($validators = appValidations($request, $rules)) !== null )
 *
 * @param  \Illuminate\Http\Request $request
 * @param  array                    $rules
 * @param  array                    $validatorsMessage
 * @param  array                    $messages
 * @return void|json
 */
function appValidations(\Illuminate\Http\Request $request, array $rules, array $validatorsMessage = array(), array $messages = array())
{
    $validator = Validator::make($request->all(), $rules, $validatorsMessage, $messages);

    if ($validator->fails()) {
        return \App\Helpers\RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
    }
}

function format_currency($value, $returnDecimal=2)
{
    return number_format($value, $returnDecimal);
}

function prefixCurrency($value, $returnDecimal=2)
{
    return '$' . format_currency($value, $returnDecimal);
    // return App\Models\Setting::extract('app.config.credit_currency') . ' ' . format_currency($value, $returnDecimal);
}

function distanceText($distance)
{
    $miles           = $distance / 1609.34;
    $metersOverMiles = $distance % 1609.34;

    if ($miles > 0) {
        $totalDistance = sprintf("%d miles %d meters", $miles, $metersOverMiles);
    } else {
        $totalDistance = sprintf("%d meters", $metersOverMiles);
    }

    return $totalDistance;
}

function fcmNotification($token, $title=null, $body=null, $payload=array())
{
    if ( null === env('FCM_SERVER_KEY') ) {
        throw new Exception('FCM_SERVER_KEY is not set in environment file.');
    }

    $fields = array_merge($payload, [
        'to' => $token,
        'notification' => ($title || $body) ? [
            'title' => $title,
            'body' => $body,
        ] : null
    ]);

    $headers = [
        'Authorization: key=' . env('FCM_SERVER_KEY'),
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
    $result = curl_exec( $ch );
    curl_close( $ch );

    return $result;
}

/*
 * Project related helpers
 */
function generateGroupName($idOne, $idTwo, $prefix='u')
{
    $idOne = trim($idOne, $prefix);
    $idTwo = trim($idTwo, $prefix);

    $userIds = [$idOne, $idTwo];
    sort($userIds);

    return $prefix . implode('_' . $prefix, $userIds);
}

function generateRideShareId($tripId, $userId, $userType)
{
    $uuid5 = Ramsey\Uuid\Uuid::uuid5(Ramsey\Uuid\Uuid::NAMESPACE_DNS, "{$tripId}-{$userId}-{$userType}");

    return $uuid5->toString();
}

function createPointBuffer($latitude, $longitude, $distance, $points=10, $asText=true)
{
    $angle = 0;
    $radius = $distance / 111200;
    $buffer = '';

    do {
      $Nx = $longitude + $radius * cos($angle);
      $Ny = $latitude + $radius * SIN($angle);

      $buffer .= $Nx . ' ' . $Ny . ', ';
      if ( $angle == 0 ) {
        # reserve the first point collected to close the polygon
        $firstPoint = $Nx . ' ' . $Ny;
      }
      $angle = $angle + (2*PI()/$points); # increment angle to put 20 points around the center
    } while ($angle < (2*PI()));

    if ( $asText ) {
        $buffer = 'POLYGON((' . $buffer . $firstPoint . '))';
    } else {
        $buffer = $buffer . $firstPoint;
    }

    return $buffer;
}

/**
 * createPointBuffer is not creating accurate circle but Its meet our project requirement so we can use it.
 * For perfect radius use following function.
 *
 */
function createPointBufferOptimised($latitude, $longitude, $distance, $points=10, $asText=true)
{
    $bearing = 0; // Angle/Direction of co-ordinates
    $radius = 6378160; // Earth radius in meters
    $cords = [];

    do {
        $new_latitude = rad2deg(asin(sin(deg2rad($latitude)) * cos($distance / $radius) + cos(deg2rad($latitude)) * sin($distance / $radius) * cos(deg2rad($bearing))));
        $new_longitude = rad2deg(deg2rad($longitude) + atan2(sin(deg2rad($bearing)) * sin($distance / $radius) * cos(deg2rad($latitude)), cos($distance / $radius) - sin(deg2rad($latitude)) * sin(deg2rad($new_latitude))));

        $cords[] = $new_longitude . ' ' . $new_latitude;
        $bearing = $bearing + (360/$points);
    } while ($bearing < 361);

    $buffer = implode(', ', $cords);

    if ( $asText ) {
        $buffer = 'POLYGON((' . $buffer . '))';
    }

    return $buffer;
}

function createMultipolyWithBuffersFromPointArray(array $latitude, array $longitude, $distance, $points=10, $asText=true)
{
    $geometry = [];
    for ($i=0; $i < count($latitude); $i++) {
        $polygon = createPointBuffer($latitude[$i], $longitude[$i], $distance, $points, false);

        $geometry[] = '((' . $polygon . '))';
    }

    if ( $asText ) {
        $buffer = 'MULTIPOLYGON(' . implode(',', $geometry) . ')';
    } else {
        $buffer = implode('', $geometry);
    }

    return $buffer;
}

function createLineStringFromPointsArray(array $coordinates, $asText=true)
{
    $geometry = [];
    foreach ($coordinates as $key => $coordinate) {
        $geometry[] = $coordinate['longitude'] . ' ' . $coordinate['latitude'];
    }

    if ( $asText ) {
        $buffer = 'LINESTRING((' . implode(',', $geometry) . '))';
    } else {
        $buffer = implode(',', $geometry);
    }

    return $buffer;
}

/**
 * Decode a poly line
 *
 * @param  string $encodedPath Encoded polyline string
 * @return array
 */
function polylineDecode($encodedPath) {

    $len = strlen( $encodedPath ) -1;
    // For speed we preallocate to an upper bound on the final length, then
    // truncate the array before returning.
    $path = [];
    $index = 0;
    $lat = 0;
    $lng = 0;
    while( $index < $len) {
        $result = 1;
        $shift = 0;
        $b;
        do {
            $b = ord($encodedPath{$index++}) - 63 - 1;
            $result += $b << $shift;
            $shift += 5;
        } while ($b >= hexdec("0x1f"));

        $lat += ($result & 1) != 0 ? ~($result >> 1) : ($result >> 1);
        $result = 1;
        $shift = 0;
        do {
            $b = ord($encodedPath{$index++}) - 63 - 1;
            $result += $b << $shift;
            $shift += 5;
        } while ($b >= hexdec("0x1f"));
        $lng += ($result & 1) != 0 ? ~($result >> 1) : ($result >> 1);

        array_push($path, ['latitude' => $lat * 1e-5, 'longitude' => $lng * 1e-5]);
    }
    return $path;
}

/**
 * Encode to a polyline via associative array
 *
 * @param  array $path
 * @return string
 */
function polylineEncode($array) {

    $lastLat = 0;
    $lastLng = 0;
    $result = '';

    foreach( $array as $point ) {
        $lat = round( $point['latitude'] * 1e5);
        $lng = round( $point['longitude'] * 1e5);

        $dLat = $lat - $lastLat;
        $dLng = $lng - $lastLng;

        $result .= polylineEncodeHelper($dLat);
        $result .= polylineEncodeHelper($dLng);

        $lastLat = $lat;
        $lastLng = $lng;
    }
    return $result;
}

function polylineEncodeHelper($v)
{
    $v = $v < 0 ? ~($v << 1) : $v << 1;
    $result = '';
    while ($v >= 0x20) {
        $result.= chr((int) ((0x20 | ($v & 0x1f)) + 63));
        $v >>= 5;
    }

    $result.=chr((int) ($v + 63));
    return $result;
}

function listLatitudeLongitudeFromPoint($string)
{
    preg_match('%POINT\((.*?)\s(.*?)\)%', $string, $cords);

    return [[$cords[1]], [$cords[2]]];
}

/**
 * Optimized algorithm from http://www.codexworld.com
 *
 * @param float $latitudeFrom
 * @param float $longitudeFrom
 * @param float $latitudeTo
 * @param float $longitudeTo
 *
 * @return float [km]
 */
function codexworldGetDistanceOpt($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo)
{
    $rad = M_PI / 180;
    //Calculate distance from latitude and longitude
    $theta = $longitudeFrom - $longitudeTo;
    $dist = sin($latitudeFrom * $rad) * sin($latitudeTo * $rad) +  cos($latitudeFrom * $rad) * cos($latitudeTo * $rad) * cos($theta * $rad);

    return acos($dist) / $rad * 60 *  1.852;
}

function latLongDifferenceInMeters($latitude1, $longitude1, $latitude2, $longitude2) {
    if (($latitude1 == $latitude2) && ($longitude1 == $longitude2)) { return 0; } // distance is zero because they're the same point
    $p1 = deg2rad($latitude1);
    $p2 = deg2rad($latitude2);
    $dp = deg2rad($latitude2 - $latitude1);
    $dl = deg2rad($longitude2 - $longitude1);
    $a = (sin($dp/2) * sin($dp/2)) + (cos($p1) * cos($p2) * sin($dl/2) * sin($dl/2));
    $c = 2 * atan2(sqrt($a),sqrt(1-$a));
    $r = 6371008; // Earth's average radius, in meters
    $d = $r * $c;

    return $d; // distance, in meters
}

function bifurcateCoOrdinates($coordinates_1, $coordinates_2, $breakIntoXMeters = 500)
{
    $distance = latLongDifferenceInMeters(
        $coordinates_1['latitude'],
        $coordinates_1['longitude'],
        $coordinates_2['latitude'],
        $coordinates_2['longitude']
    );

    if ( $distance > 0 ) {
        $coordinates = [[
            'latitude'  => floatval($coordinates_1['latitude']),
            'longitude' => floatval($coordinates_1['longitude']),
        ]];
    } else {
        $coordinates = [];
    }

    if ( $distance > $breakIntoXMeters ) {

        # Calculations
        $iterations = ceil( $distance / $breakIntoXMeters );

        $coordinates_1['latitude']  = deg2rad($coordinates_1['latitude']);
        $coordinates_1['longitude'] = deg2rad($coordinates_1['longitude']);
        $coordinates_2['latitude']  = deg2rad($coordinates_2['latitude']);
        $coordinates_2['longitude'] = deg2rad($coordinates_2['longitude']);

        $degreesToAddInLatitude     = ($coordinates_2['latitude'] - $coordinates_1['latitude']) / $iterations;
        $degreesToAddInLongitude    = ($coordinates_2['longitude'] - $coordinates_1['longitude']) / $iterations;

        $i = 0;
        while ($i < $iterations - 1) {

            $coordinates_1['latitude'] += $degreesToAddInLatitude;
            $coordinates_1['longitude'] += $degreesToAddInLongitude;

            $coordinates[] = [
                'latitude'  => rad2deg($coordinates_1['latitude']),
                'longitude' => rad2deg($coordinates_1['longitude']),
            ];

            $i++;
        }
    }

    return $coordinates;
}

function generatePreferencesResponse($payload, $selectedPreferences = null)
{
    $result = [];

    try {
        foreach ($payload as $preference) {
            $array = [
                'id'         => $preference->id,
                'title'      => $preference->title,
                'identifier' => $preference->identifier,
                'option'     => $preference->options->first()->label,
                'checked'    => $selectedPreferences ? ($selectedPreferences->contains($preference->identifier) ? true : false) : false,
            ];

            $result[] = $array;
        }
    } catch (\Exception $e) {}

    return $result;
}

function extractSelectedPreferences(array $payload)
{
    $result = [];

    try {
        foreach ($payload as $preference) {
            if ( property_exists($preference, 'checked') && intval($preference->checked) === 1 ) {
                $result[ $preference->identifier ] = 1;
            }
        }
    } catch (\Exception $e) {}

    return $result;
}

function reversePreferencesToJSON($tripRide)
{
    return json_encode(
        $tripRide->getMeta()->mapWithKeys(function($value, $key) {
            return substr($key, 0, 11) == 'preference_' ? [substr($key, 11) => !!$value] : [];
        })->map(function($value, $key) {
            return [
                'identifier' => $key,
                'checked' => $value,
            ];
        })->values()
    );
}

/**
 * This is a customized helper used to identify which preference to search for in query, when all
 * preferences selected during ride creation will be same as select none preference during search
 * so we've to remove fromperference if we got all selected preferences by category.
 *
 * @param  array  $payload
 * @return array
 */
function filterPreferencesToSearchFor(array $payload)
{
    $result = [];

    try {
        foreach ($payload as $preference) {
            foreach ($preference->options as $option) {
                if ( property_exists($option, 'checked') && intval($option->checked) === 1 ) {
                    $result[ $preference->identifier ][] = $option->value;
                }
            }

            # Remove if all selected by category, so it implies as select-all == select-none
            if ( count($preference->options) === count($result[ $preference->identifier ]) ) {
                unset($result[ $preference->identifier ]);
            }
        }
    } catch (\Exception $e) {}

    return $result;
}

function removeQuotes($value)
{
    return str_replace(["'", '"'], [], $value);
}

function rideExpectedStartTime()
{
    return Carbon\Carbon::now()->startOfDay();
}

function transformGenderStringToInteger($string)
{
    switch (strtolower($string)) {
        case 'male':
            return 1;
            break;
        case 'female':
            return 2;
            break;
        default:
            return intval($string);
            break;
    }
}

// Determine whether value exist in combination or not?
function hasBitValue($total, $validate)
{
    return (($total & $validate) !== 0);
}

function getCityFromLatLng($lat, $lng)
{
    $city = null;

    try {
        $response = Jcf\Geocode\Facades\Geocode::make()->latLng($lat, $lng);

        if ($response)
        {
            $obj = $response->raw();
            foreach ($obj->address_components as $comp)
            {
                if (in_array('locality', $comp->types))
                {
                    $city = $comp->long_name;
                    break;
                }
            }
        }
    } catch (\Exception $e) {
        logger($e->getMessage());
    }

    return $city;
}

/*
 * Radius using lat and long
 * */
function distanceFormula($lat, $long)
{
    $lat  = floatval($lat);
    $long = floatval($long);

    $distanceFormula = "(3959 * acos( cos( radians($lat) ) * cos( radians( origin_latitude ) ) * ";
    $distanceFormula.= "cos( radians( origin_longitude ) - radians($long) ) + sin( radians($lat) ) *";
    $distanceFormula.= "sin( radians( origin_latitude ) ) ) )";

    return $distanceFormula;
}
