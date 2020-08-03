<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use App\Classes\FireStoreHandler;
use App\Models\PassengerCard;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\TripRating;
use App\Models\TripRide;
use App\Models\TripRideOffer;
use App\Models\TripRideShared;
use App\Models\User;
use Carbon\Carbon;

Route::get('tmp-login/{user}', function(User $user) {
    echo \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
});

Route::get('push-test/{user}', function(\App\Models\User $user) {
    info('Sending test push to ' . $user->id);

    $user->createNotification(\App\Models\TripMember::TYPE_PASSENGER, 'Test Push', [
        'message' => 'Test push notification.',
        'type' => 'test',
    ])->notActionable()
    ->customPayload([
        'click_action' => 'test',
    ])->throwNotificationsVia('push')->build();
});

Route::get('/debug/run-cron', function () {
    Artisan::call('schedule::run');
});

Route::get('/debug/disburse-earning', function () {
    if (in_array(env('APP_ENV'), ['local', 'staging'])) {
        \App\Models\TripEarning::disburseEarning();

        return 'Driver earning withdrawal process ran successfully.';
    }
});

Route::get('/test', function () {

    // $me = User::find(96);
    // dd(array_pluck($me, []))
    // dd(collect($me->getMeta())->only(['city']));

    $decryptedBodyPayload = \App\Classes\RijndaelEncryption::decrypt('004e5acde9505fe1dcb977cf169ad49922eb69b90d705eae117580254e5a8245ff02b82a714736d34f458c61eaf3de0a7657872e1ad23c116db578792412cce0009bbf2d9db45c055cb37f8e3b20dd57cf5b480b5ba85564a286ef1a973dad13dcda4d8be23b7b23445d3f15e8b7a92eed998c1d4280cdc6f645ac018084244f4fdeea0babff935bdb4a338783497711ca0332f6a3edc6fff817121ce0308e4fd1ae43f8cd3175bfd4de3841b10ecb4261d22522b6c44a52debf2a7d4399dd1eeb949e1a39e6a273024b55d7a621e7ed0bc4c583f5e4e1a89be5c6861904af44ed0d51bdeea17c03a2cc22ea5cd2db3a87da7898fd8d334c3fb599201dfba22d');
    dd(json_decode($decryptedBodyPayload, true));

    // \App\Models\TripEarning::disburseEarning();
    return 'w0w';

    $account = 'acct_1DqJm0LDiU3NK5pv';
    \App\Helpers\StripeHelper::init();
    // dd(\Stripe\Balance::retrieve());

    \App\Helpers\StripeHelper::transferAmount($account, 47.25, null);
    dd(\App\Helpers\StripeHelper::requestPayout($account, 47.25));

    // $charge = \Stripe\Charge::create([
    //   "amount" => 10000,
    //   "currency" => "usd",
    //   "source" => "tok_bypassPending",
    //   "transfer_group" => "{ORDER10}",
    // ]);
    // dd($charge);


    try {

        $transfer = \Stripe\Transfer::create([
            "amount" => 7000,
            "currency" => "usd",
            "destination" => "acct_1DqjAoFYxbaATTF8",
            "transfer_group" => "{ORDER10}",
        ]);
        dd($transfer);
    } catch (\Exception $e) {
        // dd(starts_with($e->getMessage(), 'Insufficient funds in Stripe account'));
        dd($e->getMessage());
    }


    $bd = '03/04/1992';
    $carbon = \Carbon\Carbon::createFromFormat('m/d/Y H:i:s', $bd . ' 00:00:00');
    dd($carbon->format('Y'));
    dd(get_class_methods($carbon));

    // dd(get_class_methods(\Carbon\Carbon::now()));

    $account = \Stripe\Account::retrieve("acct_1DqJm0LDiU3NK5pv");
    dd($account);
    // $account->external_account = array(
    //     "object" => "bank_account",
    //     "country" => "US",
    //     "currency" => "usd",
    //     "account_holder_name" => 'Jane Austen',
    //     "account_holder_type" => 'individual',
    //     "routing_number" => "110000000",
    //     "account_number" => "000444444440"
    // );
    // $account->payout_schedule = [
    //     'delay_days' => 2,
    //     'interval' => 'weekly',
    //     'weekly_anchor' => 'monday',
    // ];

    // dd($account->legal_entity);
    // $account->legal_entity = [
    //     // 'address' => array(
    //     //     'city' => 'Maxico',
    //     //     'country' => 'US',
    //     //     "line1" => 'H65',
    //     //     "line2" => 'standfort street',
    //     //     "postal_code" => '90046',
    //     //     "state" => 'CA'
    //     // ),
    //     'dob' => [
    //         'day' => '10',
    //         'month' => '01',
    //         'year' => '1988'
    //     ],
    //     'first_name' => 'Test',
    //     'last_name' => 'Tester',
    //     // 'type' => 'sole_prop',
    //     'type' => 'individual',
    //     'ssn_last_4' => NULL,
    //     'personal_id_number' => NULL,
    //     // 'personal_id_number_provided' => false,
    //     // 'ssn_last_4_provided' => false,
    //     'business_tax_id_provided' => false,
    // ];
    // /*$account->tos_acceptance = [
    //     'date' => \Carbon\Carbon::now()->getTimestamp(),
    //     'ip' => '127.0.0.1',
    //     'user_agent' => request()->userAgent(),
    // ];*/
    $account->save();
    dd($account);

    $account = \Stripe\Account::create([
        "type" => "custom",
        "country" => "US",
        "email" => "bob@example.com",
        'external_account' => array(
            "object" => "bank_account",
            "country" => "US",
            "currency" => "usd",
            "account_holder_name" => 'Jane Austen',
            "account_holder_type" => 'individual',
            "routing_number" => "111000025",
            "account_number" => "000123456789"
        ),
        'legal_entity' => [
            'dob' => [
                'day' => '10',
                'month' => '01',
                'year' => '1988'
            ],
            'first_name' => 'Test',
            'last_name' => 'Tester',
            // 'type' => 'sole_prop',
            'type' => 'individual',
        ],
        'tos_acceptance' => array(
            'date' => \Carbon\Carbon::now()->getTimestamp(),
            'ip' => '127.0.0.1',
            'user_agent' => request()->userAgent(),
        )
    ]);
    dd($account);

    dd(Carbon::now()->addDays(8)->getTimestamp(), Carbon::now()->addDays(10)->getTimestamp());

    $ride = TripRide::find(741);
    $ride->cancelRideByDriver();

    return 'w0w';

    event(new \App\Events\TripCreatedByDriver(Trip::find(597)));
    return;

    $trip                 = Trip::find(597);
    $ride                 = $trip->getGoingRideOfTrip();
    $subscribedPassengers = (new \App\Models\RideSubscriber)->extractUserOfRouteSubscribers($trip, $ride);
    dd($subscribedPassengers);

    dd( $ride->members->pluck('user_id')->contains(194) );

    dd( collect($subscribedPassengers)->pluck('passenger_id')->unique()->values(), $ride->members->pluck('user_id') );

    // dd(\App\Models\RideSubscriber::driverListSubscribedRoutes());

    $user = User::find(190);
    dd($user && $user->delete());

    // dd(M_PI);

    $cLat  = '30.292530';
    $cLong = '-97.737604';

    return createPointBufferOptimised($cLat, $cLong, 80468, 20);

    $radius = 6378160;
    $distance = 80467.2;
    $bearing = 360 / 20;
    // dd($bearing);
    $new_latitude = rad2deg(asin(sin(deg2rad($cLat)) * cos($distance / $radius) + cos(deg2rad($cLat)) * sin($distance / $radius) * cos(deg2rad($bearing))));

    //  New longitude in degrees.
    $new_longitude = rad2deg(deg2rad($cLong) + atan2(sin(deg2rad($bearing)) * sin($distance / $radius) * cos(deg2rad($cLat)), cos($distance / $radius) - sin(deg2rad($cLat)) * sin(deg2rad($new_latitude))));
    return "LINESTRING($cLong $cLat, $new_longitude $new_latitude)";

    // dd(PI() == M_PI);
    return createPointBuffer($cLat, $cLong, 80468, 120);

    $buffer = createPointBuffer($cLat, $cLong, 80468, 8, false);
    $points = explode(',', $buffer);
    $points = array_map('trim', $points);

    foreach ($points as $point) {
        list($lng, $lat) = explode(' ', $point);
        echo (codexworldGetDistanceOpt($cLat, $cLong, $lat, $lng) * 0.621371) . "<br />";
    }

    dd(M_PI, $points);

    return $buffer;

    $tripRideShared = TripRideShared::first();
    // dd($tripRideShared->ride);
    Mail::to($tripRideShared->email)->send(new \App\Mail\ShareItinerary($tripRideShared));
    return 'www';


    if (!auth()->check()) {
        Auth::loginUsingId(170);
    } else {
        dd( user()->toArray() );
    }
    return 'wwww';



    /*$tripRideShared = \App\Models\TripRideShared::find('48cde041-8c0a-4225-80a9-eb070b009839');
    Mail::to($tripRideShared->email)->send(new \App\Mail\ShareItenraryTripStarted($tripRideShared));
    return 'ww0';

    $statesDB = \App\Models\State::whereCountryId(231)->get();
    $citiesDB = \App\Models\City::whereIn('state_id', $statesDB->pluck('id'))->get();
    return $citiesDB;
    return $states;*/

    $request = request();
    $request->merge([
        'sdf' => 'sdfdsfedu@sdf.sdf.sdfsdf.sd.sdfds.fds.fds.fdsf.sd.fsdf.ds'
    ]);
    dd( appValidations($request, ['sdf'=>'edu_email']) );

    $var = new stdClass;
$var->elementary = [];
$var->kg = [];

    return \App\Helpers\RESTAPIHelper::response(['child' => $var]);

    $trip = Trip::find(467);
    dd($trip->getEarningObject('earning'));

    dd($trip->rides->sum(function($ride) {
        dd($ride->earning->earning);
        return $ride->earnings->earning;
    }));

    $user = User::find(172);

    $transactions = $user->earnings;
    $transactions->load([
        'ride.trip'
    ]);
    return 'w';

    dd($user->earnings);

    $ride = TripRide::with('trip.driver.bankAccount')->find(713);
    dd($ride->members()->readyToFly()->sum('fare'));
    event(new \App\Events\TripEnded($ride, $ride->trip->driver));
    return 'w0w';

    dd($ride->hasEnded());
    // dd( collect($ride->trip->driver->bankAccount)->get('period', 'standard') );

    // return dump($ride->has('members', '>=', '2')->get());

    dump( $ride->transactions->pluck('amount') );
    dump( $ride->members()->readyToFly()->sum('fare') );

    $totalFare = $ride->members()->readyToFly()->sum('fare');
    $commission = calculatePercentage($totalFare, 100 - constants('global.ride.driver_earning'));
    $payoutCharges = collect($ride->trip->driver->bankAccount)->get('period', 'standard') == 'standard' ? '' : calculatePercentage($totalFare, constants('global.ride.payout_charges', 5));
    $earning = $ride->earning()->getRelated()->fill([
        'gross_amount' => $totalFare,
        'commission' => $commission,
        'payout_charges' => $payoutCharges,
        'earning' => $totalFare - $commission - $payoutCharges,
    ]);

    $earning->driver()->associate($ride->trip->driver);
    $earning->ride()->associate($ride);

    dd($earning);

    // dd(calculatePercentage(50, 0));

    /*$ride = TripRide::find(745);
    $members = [];

    event(new \App\Events\TripRated($ride, TripRating::find(44)));
    return 'w0w';*/

    $tripRideShared = \App\Models\TripRideShared::find('48cde041-8c0a-4225-80a9-eb070b009839');

    /*$mail = (new \App\Mail\ShareItenraryTripStarted($tripRideShared))->build();

    $mocked = new \ReflectionMethod($mail, 'buildView');
    $mocked->setAccessible(true);

    return $mocked->invoke($mail)['html'];*/

    // dd($tripRideShared);
    // dd(get_class_methods(new \App\Mail\ShareItenraryTripStarted($tripRideShared)));
    // dd((new \App\Mail\ShareItenraryTripStarted($tripRideShared))->send('email.'));
    Mail::to($tripRideShared->email)->send(new \App\Mail\ShareItenraryTripStarted($tripRideShared));
    return 'ww0';

    // $tripRideShared = App\Models\TripRideShared::find('f91ae802-d094-4f3f-bd56-ba2bec085136');
    // // dd($tripRideShared);
    // Mail::to($tripRideShared->email)->send(new \App\Mail\ShareItinerary($tripRideShared));
    // return 'w0w';

    $user = User::find(172);
    dd( $user->tripshared()->whereIn('trip_ride_id', [745])->get()->pluckMultiple(['trip_ride_id', 'first_name', 'last_name', 'email', 'mobile']) );

    $schools = <<<EOF
A.T. Still University of Health Sciences
Abilene Christian University
Abraham Lincoln University
Acacia University
Academy of Art University
Adams State University
Adelphi University
Adler University
Adventist University of Health Sciences
Air University
Air University Extension Course Program
Alabama A & M University
Alabama State University
Alaska Pacific University
Albany State University
Alcorn State University
Alderson Broaddus University
Alfred University
Alhambra Medical University
Allen University
Alliant International University
Allied American University
Alvernia University
Amberton University
America Evangelical University
American Business and Technology University
American Graduate University
American Health Science University
American InterContinental University
American Jewish University
American Language Academy at Lincoln Memorial University
American National University
American National University - Salem
American Pathways University
American Public University System
American Sentinel University
American Studies Program, Willamette University
American University
American University of Armenia
American University of Health Sciences
American University of Puerto Rico
Amridge University
Anaheim University
Anderson University
Andrews University
Andrews University/Dayton
Angelo State University
Antioch University
Apollos University
Appalachian State University
Arcadia University
Argosy University
Arirang University
Arizona Christian University
Arizona State University
Arkansas State University
Arkansas State University - Beebe
Arkansas State University - Newport
Arkansas State University Mid-South
Arkansas State University Mountain Home
Arkansas Tech University
Arkansas Valley Technical Institute of Arkansas Tech University
Arlington Baptist University
Armstrong State University
Army Logistics University
Asbury University
Ashford University
Ashland University
Aspen University
Athens State University
Atlantic University
Atlantic University College
Atlantic University of Chinese Medicine
Atlantis University
Auburn University Main Campus
Auburn University-Montgomery
Augsburg University
Augusta State University
Augusta University
Augustana University
Aurora University
Austin Peay State University
Ave Maria University
Avera McKennan Hospital & University Health Center
Averett University
Avila University
Azusa Pacific Online University
Azusa Pacific University
Babel University Professional School of Translation
Baker University
Baker University School of Nursing
Bakke Graduate University
Baldwin Wallace University
Ball State University
Baltimore Hebrew University Inc
Baptist University of the Americas
Barnes-Jewish Hospital, Washington University Med Center
Barry University
Baruch College of the City University of New York
Bastyr University
BAU International University
Bay Path University
Baylor University
Baylor University Medical Center
Beacon University
Belhaven University
Bellarmine University
Bellevue University
Belmont University
Bemidji State University
Benedictine University
Bentley University
Bergin University of Canine Studies
Bethany Global University
Bethany University
Bethel University
Bethesda University
Bethune - Cookman University
Beulah Heights University
Biola University
Black Hills State University
Bloomsburg University of Pennsylvania
Bluffton University
Bob Jones University
Boise State University
Borough of Manhattan Community College of the City University of New York
Boston University
Bowie State University
Bowling Green State University
Bradley University
Brandeis University
Brandman University
Brenau University
Brescia University
Briar Cliff University
Bridgewater State University
Brigham Young University
Brigham Young University - Hawaii
Brigham Young University - Idaho
Bristol University
Brite Divinity School of Texas Christian University
Broadview University
Broadview University - Layton
Broadview University - Orem
Bronx Community College of the City University of New York
Brooklyn College of the City University of New York
Brown University
Bryan University
Bryant University
Bucknell University
Buena Vista University
Butler University
Cabrini University
Cairn University
Caldwell University
California Arts University
California Baptist University
California Coast University
California Health Sciences University
California Intercontinental University
California International Business University
California Lutheran University
California Miramar University
California National University for Advanced Studies
California Northstate University
California Polytechnic State University-San Luis Obispo
California Southern University
California State Polytechnic University, Pomona
California State University - Bakersfield
California State University - Channel Islands
California State University - Chico
California State University - Dominguez Hills
California State University - East Bay
California State University - Fresno
California State University - Fullerton
California State University - Long Beach
California State University - Los Angeles
California State University - Monterey Bay
California State University - Northridge
California State University - Sacramento
California State University - San Bernardino
California State University - San Marcos
California State University - Stanislaus
California State University Maritime Academy
California University of Management and Sciences
California University of Management and Sciences Virginia
California University of Pennsylvania
Calvary University
Cameron University
Campbell University
Campbellsville University
Capella University
Capital University
Capitol Technology University
Cardean University
Cardinal Stritch University
Caribbean University
Carlos Albizu University
Carlow University
Carnegie Mellon University
Carroll University
Carson-Newman University
Case Western Reserve University
Castleton University
Catholic Distance University
Catholic University of America
Cedarville University
Centenary University
Central Connecticut State University
Central Methodist University
Central Michigan University
Central State University
Central Washington University
Chamberlain University
Chaminade University of Honolulu
Chancellor University
Chapman University
Charles R. Drew University of Medicine and Science
Charleston Southern University
Chatham University
Cheyney University of Pennsylvania
Chicago State University
Chowan University
Christian Brothers University
Christopher Newport University
Cincinnati Christian University
City College of New York of the City University of New York, The
City University of Seattle
City Vision University
Claflin University
Claremont Graduate University
Claremont Lincoln University
Clarion University of Pennsylvania
Clark Atlanta University
Clark University
Clarke University
Clarks Summit University
Clarkson University
Clayton  State University
Cleary University
Clemson University
Cleveland State University
Cleveland University-Kansas City
Coastal Carolina University
Coleman University
Colgate University
College of Staten Island of the City University of New York
Colorado Christian University
Colorado Heights University
Colorado Mesa University
Colorado State University
Colorado State University - Global Campus
Colorado State University - Pueblo
Colorado Technical University
Columbia Central University
Columbia International University
Columbia Southern University
Columbia University in the City of New York
Columbus State University
Concord University
Concordia University
Concordia University at Austin
Concordia University Chicago
Concordia University Irvine
Concordia University, St. Paul
Coppin State University
Corban University
Cornell University
Cornerstone University
Cossatot Community College of the University of Arkansas
Creighton University
Crescent Cosmetology University, Inc.
Cumberland University
Dakota State University
Dakota Wesleyan University
Dallas Baptist University
Davenport University
Defense Acquisition University
Delaware State University
Delaware Valley University
Delta State University
Denison University
DePaul University
DePauw University
Des Moines University - Osteopathic Medical Center
DeSales University
DeVry University
Dewey University - Hato Rey Campus
Dharma Realm Buddhist University
Dickinson State University
Dillard University
Divine Mercy University
Dixie State university
Doane University
Dominican University
Dominican University of California
Dongguk University - Los Angeles
Drake University
Drew University
Drexel University
Drury University
Duke University
Duluth Business University
Dunlap-Stone University
Duquesne University
E.B. Cape Center - Corporate University
East Carolina University
East Central University
East Stroudsburg University of Pennsylvania
East Tennessee State University
East Texas Baptist University
East-West University
Eastern Connecticut State University
Eastern Illinois University
Eastern Kentucky University
Eastern Mennonite University
Eastern Michigan University
Eastern New Mexico University
Eastern New Mexico University - Roswell
Eastern Oregon University
Eastern University
Eastern Washington University
EC-Council University
ECPI University
Edinboro University of Pennsylvania
EDP University of Puerto Rico
Elizabeth City State University
Ellis University
Elon University
Embry-Riddle Aeronautical University - Daytona Beach
Emory University
Emporia State University
English as a Second Language at Troy University
English as a Second Language International (ESLI) at the University of Minnesota Duluth
English as a Second Language International (ESLI) at the University of Wisconsin Superior
English as a Second Language International (ESLI) at West Texas A& M University
English as Second Language International (ESLI) at Sullivan University
ESL Program, University of North Carolina, Wilmington
ESLi at McNeese State University
ESLi at Southern Illinois University Edwardsville
ESLi at Texas A & M University Corpus Christi
ESLi at Western Kentucky University
EUCON International University
Evangel University
Evangelia University
Everest University Online - Tampa
Everglades University
Everglades University - Orlando
eVersity of the University of Arkansas System
Ezra University
Fairfield University
Fairleigh Dickinson University
Fairmont State University
Faith International University
Family of Faith Christian University
Faulkner University
Fayetteville State University
Felician University
Ferris State University
Fielding Graduate University
Finlandia University
Fisk University
Fitchburg State University
Five Branches University:  Graduate School of Traditional Chinese Medicine - San Jose, CA
Five Branches University:  Graduate School of Traditional Chinese Medicine - Santa Cruz, CA
Florida Agricultural and Mechanical University
Florida Atlantic University
Florida Gulf Coast University
Florida International University
Florida Memorial University
Florida National University
Florida Polytechnic University
Florida State University
FLS International at Tennessee Tech University
FLS International St. Peter's University
Fontbonne University
Fordham University
Fort Hays State University
Fort Valley State University
Framingham State University
Francis Marion University
Franciscan Missionaries of Our Lady University
Franciscan University of Steubenville
Franklin Pierce University
Franklin University
Freed-Hardeman University
Fresno Pacific University
Friends University
Frontier Nursing University
Frostburg State University
Full Sail University
Furman University
Future Generations University
Gallaudet University
Gannon University
Gardner-Webb University
Genesis University
George Fox University
George Mason University
George W. Truett Theological Seminary of Baylor University
George Washington University, The
Georgetown University
Georgia Central University
Georgia College and State University
Georgia Health Sciences University
Georgia Southern University
Georgia Southwestern State University
Georgia State University
Georgian Court University
Global University
Globe University
Golden Gate University
Golden State University
Gonzaga University
Governors State University
Grace Mission University
Grace Mission University Graduate School
Grace University
Graceland University
Graduate School and University Center of the City University of New York, The
Grambling State University
Grand Canyon University
Grand Valley State University
Grand View University
Grantham University
Greenville University
Gwynedd Mercy University
Hallmark University
Hallmark University-College of Aeronautics
Hamline University
Hampton University
Han University of Traditional Medicine
Hannibal-LaGrange University
Hardin-Simmons University
Harding University
Harris-Stowe State University
Harrisburg University of Science and Technology
Harrison Middleton University
Harvard University
Haskell Indian Nations University
Hawaii Pacific University
Heidelberg University
Helena College University of Montana
Henderson State University
Henley-Putnam University
Herguan University
Heritage Christian University
Heritage University
Herzing University
High Point University
Hodges University
Hofstra University
Hollins University
Holy Family University
Holy Names University
Hope International University
Horizon University
Hostos Community College of the City University of New York
Houston Baptist University
Howard Payne University
Howard University
Humboldt State University
Humphreys University
Hunter College of the City University of New York
Huntington University
Huntington University of Health Sciences
Husson University
Huston-Tillotson University
Idaho State University
IGlobal University
Illinois State University
Illinois Wesleyan University
Immaculata University
IMPAC University
Independence University
Indiana State University
Indiana University - Purdue University Indianapolis
Indiana University Bloomington
Indiana University East
Indiana University Kokomo
Indiana University Northwest
Indiana University of Pennsylvania
Indiana University South Bend
Indiana University Southeast
Indiana Wesleyan University
Inter American University of Puerto Rico Aguadilla
Inter American University of Puerto Rico Arecibo
Inter American University of Puerto Rico Barranquitas
Inter American University of Puerto Rico Bayamon
Inter American University of Puerto Rico Fajardo
Inter American University of Puerto Rico Guayama
Inter American University of Puerto Rico Metropolitan Campus
Inter American University of Puerto Rico Ponce
Inter American University of Puerto Rico San German
Inter American University of Puerto Rico School of Law
INTERLINK Language Center at Indiana State University
INTERLINK Language Center at Montana State University
INTERLINK Language Center at Seattle Pacific University
INTERLINK Language Center at St. Ambrose University
INTERLINK Language Center at University of North Carolina Greensboro
INTERLINK Language Center at Valparaiso University
International Reformed University and Seminary
International Technological University
Iowa State University of Science and Technology
Iowa Wesleyan University
Irish American University
Jackson State University
Jacksonville State University
Jacksonville University
James Madison University
John Brown University
John Carroll University
John F Kennedy University
John Jay College of Criminal Justice of the City University of New York
John Paul the Great Catholic University
John Wesley University
Johns Hopkins University
Johnson & Wales University
Johnson C Smith University
Johnson University
Joint Special Operations University
Jones International University
Jose Maria Vargas University
Judson University
Kansas City University of Medicine and Biosciences
Kansas State University
Kansas Wesleyan University
Kapiolani Community College/University of HI Community Colleges
Kean University
Keiser University
Kennesaw State University
Kent State University
Kentucky Christian University
Kentucky State University
Kernel University
Kettering University
King University
Kingsborough Community College of the City University of New York
Kingston University
Kona University
Kutztown University of Pennsylvania
La Salle University
La Sierra University
LaGuardia Community College of the City University of New York
Lake Superior State University
Lakeland University
Lamar University
Lambuth University
Lander University
Langston University
Lawrence Technological University
Lawrence University
Lebanese American University School of Pharmacy
Lee University
Lehigh University
Lehman College of the City University of New York
Lenoir-Rhyne University
Lesley University
LeTourneau University
Lewis University
Liberty University
Life University
Lincoln Christian University
Lincoln Memorial University
Lincoln University
Lindenwood University
Lipscomb University
Lock Haven University of Pennsylvania
Logan University
Loma Linda University
Long Island University
Longwood University
Los Angeles Pacific University
Louisiana State University and A&M College
Louisiana State University at Alexandria
Louisiana State University at Eunice
Louisiana State University at Shreveport
Louisiana Tech University
Lourdes University
Loyola Marymount University
Loyola University Maryland
Loyola University New Orleans
Loyola University of Chicago
Lubbock Christian University
Lynn University
Madonna University
Maharishi University of Management
Malone University
Manchester University
Mansfield University of Pennsylvania
Maranatha Baptist University
Marian University
Marine Corps University
Marquette University
Mars Hill University
Marshall B. Ketchum University
Marshall University
Marshall University School of Pharmacy
Martin University
Mary Baldwin University
Marycrest International University
Maryland University of Integrative Health
Marylhurst University
Marymount California University
Marymount College of Fordham University
Marymount University
Maryville University of Saint Louis
Marywood University
Mayville State University
McKendree University
McMurry University
McNeese State University
MCPHS University
Medgar Evers College of the City University of New York
Medical University of South Carolina
Mercer University
Mercy College of Nursing and Health Sciences Southwest Baptist University
Mercyhurst University
Meridian University
Merit University
Merrell University of Beauty Arts & Sciences
Merrell University of Beauty Arts and Science
Methodist University
Metropolitan State University
Metropolitan State University of Denver
Miami International University of Art and Design
Miami University
Michigan State University
Michigan Technological University
Mid-America Christian University
Mid-Atlantic Christian University
Mid-Continent University
MidAmerica Nazarene University
Middle Georgia State University
Middle Tennessee State University
Midland University
Midway University
Midwest University
Midwestern State University
Midwestern University
Millennia Atlantic University
Millersville University of Pennsylvania
Millikin University
Minnesota State University - Mankato
Minnesota State University - Moorhead
Minot State University
Misericordia University
Mississippi State University
Mississippi University for Women
Mississippi Valley State University
Missouri Baptist University
Missouri Southern State University
Missouri State University
Missouri State University - West Plains
Missouri University of Science and Technology
Missouri Western State University
Monmouth University
Montana State University - Billings
Montana State University - Bozeman
Montana State University - Great Falls College of Technology
Montana State University - Northern
Montana Tech of the University of Montana
Montclair State University
Morehead State University
Morgan State University
Morrison University
Mount Mary University
Mount Mercy University
Mount Saint Mary's University
Mount St Mary's University
Mount St. Joseph University
Mount Vernon Nazarene University
Mountain State University
Multnomah University
Murray State University
Muskingum University
Naropa University
National American University - Rapid City
National Defense University
National Intelligence University
National Louis University
National Technological University
National University
National University College - Bayamon
National University of Health Sciences
National University of Natural Medicine
Nations University
Navajo Technical University
Nebraska Wesleyan University
Neumann University
New Charter University
New Jersey City University
New Mexico Highlands University
New Mexico State University
New Mexico State University - Carlsbad
New Mexico State University - Dona Ana Community College
New Mexico State University at Alamogordo
New York City College of Technology of the City University of New York
New York University
Newman University
Niagara University
Nicholls State University
Nine Star University of Health Sciences
Nobel University
Norfolk State University
Norfolk State University Virginia Beach Higher Education Cen
North American University
North Carolina A & T State University
North Carolina Central University
North Carolina State University
North Central University
North Dakota State University
North Georgia College & State University
North Greenville University
North Park University
Northcentral University
Northeast Ohio Medical University
Northeastern Illinois University
Northeastern State University
Northeastern University
Northern Arizona University
Northern Illinois University
Northern Kentucky University
Northern Michigan University
Northern State University
Northland International University
NorthShore University HealthSystem - Evanston Hospital
Northwest Christian University
Northwest Missouri State University
Northwest Nazarene University
Northwest University
Northwestern Health Sciences University
Northwestern Oklahoma State University
Northwestern Polytechnic University
Northwestern State University of Louisiana
Northwestern University
Northwood University
Norwich University
Notre Dame de Namur University
Notre Dame of Maryland University
Nova Southeastern University
Oakland City University
Oakland University
Oakwood University
Oglethorpe University
Ohio Christian University
Ohio Dominican University
Ohio Northern University
Ohio State University
Ohio State University Agricultural Technical Institute
Ohio State University College of Optometry
Ohio University - Main Campus
Ohio Valley University
Ohio Wesleyan University
Oikos University
Oklahoma Baptist University
Oklahoma Christian University
Oklahoma City University
Oklahoma Panhandle State University
Oklahoma State University
Oklahoma State University - Oklahoma City
Oklahoma State University Institute of Technology - Okmulgee
Oklahoma University Medical Center
Oklahoma Wesleyan University
Old Dominion University
Olivet Nazarene University
Olivet University
Oral Roberts University
Oregon Health & Science University
Oregon State University
Ottawa University
Otterbein University
Ouachita Baptist University
Our Lady of the Lake University
Pace University
Pacific Islands University
Pacific Lutheran University
Pacific Northwest University of Health Sciences
Pacific Rim Christian University
Pacific States University
Pacific University
Palm Beach Atlantic University - West Palm Beach
Palmer Theological Seminary of Eastern University
Palo Alto University
Park University
Parker University
Patten University
Pennsylvania State University
Pennsylvania State University Dickinson Law
Pepperdine University
Pfeiffer University
Philadelphia University
Phillips Community College of the University of Arkansas
Phillips Graduate University
Piedmont International University
Pinchot University
Pittsburg State University
Plymouth State University
Point Loma Nazarene University
Point Park University
Point University
Polytechnic Institute of New York University
Pontifical Catholic University of Puerto Rico
Portland Seminary of George Fox University
Portland State University
Post University
Prairie View A & M University
Princeton University
Purdue University
Purdue University - North Central
Purdue University Fort Wayne
Purdue University Global
Purdue University Northwest
Queens College of the City University of New York
Queens University of Charlotte
Queensborough Community College of the City University of New York
Quincy University
Quinnipiac University
Radford University
Radiological Technologies University VT
Rainstar University
Randall University
Realtor University
Reformed University
Regent University
Regis University
Reinhardt University
Resurrection University
Rice University
Richmont Graduate University
Rider University
Rivier University
Robert Morris University
Robert Morris University - Illinois
Rockefeller University
Rockford University
Rockhurst University
Rocky Mountain University of Health Professions
Rocky Vista University
Rocky Vista University College of Ostepathic Medicine
Roger Williams University
Roger Williams University School of Law
Rogers State University
Roosevelt University
Rosalind Franklin University of Medicine and Science
Roseman University of Health Sciences
Rowan University
Rush University
Rutgers, The State University of New Jersey
Sacred Heart University
Saginaw Valley State University
Saint Ambrose University
Saint Augustine's University
Saint Cloud State University
Saint Francis University
Saint Gregory's University
Saint John's University
Saint Joseph's University
Saint Leo University
Saint Louis University
Saint Martin's University
Saint Mary's Seminary & University
Saint Mary's University of Minnesota
Saint Peter's University
Saint Thomas University
Saint Xavier University
Salem State University
Salem University
Salisbury University
Salus University
Salve Regina University
Sam Houston State University
Samford University
Samra University of Oriental Medicine
Samuel Merritt University
San Diego Global Knowledge University
San Diego State University
San Francisco State University
San Ignacio University
San Jose State University
Santa Clara University
Santa Fe University of Art and Design
Sarasota University
Savannah State University
Saybrook University
Schiller International University
Scholars Cosmetology University
Schreiner University
Schwan's University
Seattle Pacific Seminary of Seattle Pacific University
Seattle Pacific University
Seattle University
Security University
Selma University
Seton Hall University
Seton Hill University
Shaw University
Shawnee State University
Shenandoah University
Shepherd University
Sherrill's University of Barber and Cosmetology
Shiloh University
Shippensburg University of Pennsylvania
Shorter University
SI TANKA UNIVERSITY-HURON CAMPUS
Siena Heights University
Sierra States University
Silicon Valley University
Simpson University
Sinte Gleska University
Slippery Rock University of Pennsylvania
Sofia University
Soka University of America
Sonoma State University
South Baylo University
South Carolina State University
South Dakota State University
South University
Southeast Missouri State University
Southeastern Louisiana University
Southeastern Oklahoma State University
Southeastern University
Southern Adventist University
Southern Arkansas University Main Campus
Southern Arkansas University Tech
Southern California University of Health Sciences
Southern California University SOMA
Southern Connecticut State University
Southern Illinois University Carbondale
Southern Illinois University Edwardsville
Southern Methodist University
Southern Nazarene University
Southern Nevada University of Cosmetology
Southern New Hampshire University
Southern Oregon University
Southern Polytechnic State University
Southern States University - Newport Beach
Southern States University - San Diego
Southern University and A & M College
Southern University at New Orleans
Southern University at Shreveport
Southern Utah University
Southern Virginia University
Southern Wesleyan University
Southwest Baptist University
Southwest Minnesota State University
Southwest University
Southwest University at El Paso
Southwest University of Visual Arts
Southwestern Adventist University
Southwestern Assemblies of God University
Southwestern Christian University
Southwestern Oklahoma State University
Southwestern University
Spalding University
Spring Arbor University
St. Bonaventure University
St. Catherine University
St. Edward's University
St. John's University
St. Lawrence University
St. Luke University
St. Mary's University
St. Patrick's Seminary and University
Stanford University
Stella and Charles Guttman Community College of the City University of New York, The
Stephen F Austin State University
Stetson University
Stevenson University
Stockton University
Stratford University
Strayer University
Suffolk University
Sul Ross State University
Sullivan University
SUNY Upstate Medical University
Susquehanna University
Syracuse University
Tarleton State University
Taylor University
Teacher Education University
Teachers College of Columbia University
Temple University
Tennessee State University
Tennessee Technological University
Tennessee Temple University
Tennessee Wesleyan University
Texas A&M International University
Texas A&M University
Texas A&M University - Central Texas
Texas A&M University - Commerce
Texas A&M University - Corpus Christi
Texas A&M University - Kingsville
Texas A&M University - Texarkana
Texas A&M University School of Law
Texas Christian University
Texas Health and Science University
Texas Intensive English Program (TIEP) at Lamar University
Texas Lutheran University
Texas Southern University
Texas State University
Texas Tech University
Texas Tech University Health Sciences Center
Texas Wesleyan University
Texas Woman's University
The King's University
The Master's University and Seminary
The National Hispanic University
The University of Aesthetics
The University of Aesthetics & Cosmetology
The University of Alabama
The University of Findlay
The University of Montana
The University of Montana - Western
The University of Tampa
The University of Tennessee - Chattanooga
The University of Tennessee - Knoxville
The University of Tennessee - Martin
The University of Texas at Arlington
The University of Texas at Brownsville and Texas Southmost College
The University of Texas at Dallas
The University of Texas at El Paso
The University of Texas at San Antonio
The University of Texas Health Science Center at Tyler
The University of Texas Medical Branch
The University of Texas of the Permian Basin
The University of Texas Rio Grande Valley
The University of Texas School of Health Professions
The University of the Arts
The University of the South
The University of Virginia's College at Wise
The University of West Florida
The University of West Los Angeles
Theological University of the Caribbean
Thomas Edison State University
Thomas Jefferson University
Thomas University
Tiffin University
Touro University California
Touro University Worldwide
Towson University
Transylvania University
Travel University International
Trevecca Nazarene University
Tricoci University of Beauty Culture
Tricoci University of Beauty Culture LLC
Tricoci University of Beauty Culture, LLC
Tricoci University of Beauty Culture, LLC - Highland
Trident University International
Trine University
Trinity International University
Trinity University
Trinity Washington University
Troy University
Truett McConnell University
Truman State University
Tufts University
Tufts University School of Medicine
Tulane University
Tuskegee University
U-Haul University
UCAS University of Cosmetology Arts & Sciences
Uniformed Services University of the Health Sciences
Union Institute & University
Union University
Union University of California
United States University
University Academy of Hair Design
University Language Institute
University of Advancing Technology
University of Akron
University of Akron - Wayne College
University of Alabama at Birmingham
University of Alabama at Huntsville
University of Alaska Anchorage
University of Alaska Fairbanks
University of Alaska Southeast
University of Antelope Valley
University of Arizona
University of Arkansas - Pulaski Technical College
University of Arkansas at Little Rock
University of Arkansas at Monticello
University of Arkansas at Pine Bluff
University of Arkansas Community College-Batesville
University of Arkansas Community College-Hope
University of Arkansas Community College-Morrilton
University of Arkansas for Medical Sciences
University of Arkansas Rich Mountain
University of Arkansas, Fayetteville
University of Arkansas-Fort Smith
University of Atlanta
University of Baltimore
University of Bridgeport
University of Bridgeport/Fones School of Dental Hygiene
University of California - Davis
University of California - Los Angeles
University of California - San Diego
University of California Hastings College of Law
University of California, Berkeley
University of California, Davis
University of California, Irvine
University of California, Merced
University of California, Riverside
University of California, San Francisco
University of California, Santa Barbara
University of California, Santa Cruz
University of Central Arkansas
University of Central Florida
University of Central Missouri
University of Central Oklahoma
University of Charleston
University of Chicago
University of Cincinnati
University of Cincinnati Blue Ash College
University of Cincinnati Clermont College
University of Colorado at Boulder
University of Colorado at Colorado Springs
University of Colorado Denver
University of Connecticut
University of Cosmetology - Sullivan
University of Cosmetology Arts & Sciences - McAllen
University of Cosmetology Arts & Sciences - San Antonio
University of Dallas
University of Dayton
University of Delaware
University of Denver
University of Detroit Mercy
University of Dubuque
University of East West Medicine
University of Evansville
University of Fairfax
University of Florida
University of Fort Lauderdale
University of Georgia
University of Great Falls
University of Guam
University of Hartford
University of Hawaii - West Oahu
University of Hawaii at Hilo
University of Hawaii at Manoa
University of Hawaii Maui College
University of Holy Cross
University of Houston
University of Houston - Clear Lake
University of Houston - Downtown
University of Houston - Victoria
University of Idaho
University of Illinois at Chicago
University of Illinois at Springfield
University of Illinois at Urbana-Champaign
University of Indianapolis
University of Iowa
University of Jamestown
University of Kansas
University of Kentucky
University of La Verne
University of La Verne College of Law
University of Louisiana at Lafayette
University of Louisiana at Monroe
University of Louisville
University of Maine
University of Maine at Augusta
University of Maine at Farmington
University of Maine at Fort Kent
University of Maine at Machias
University of Maine at Presque Isle
University of Management and Technology
University of Mary
University of Mary Hardin-Baylor
University of Mary Washington
University of Maryland - Baltimore
University of Maryland - Baltimore County
University of Maryland - College Park
University of Maryland - Eastern Shore
University of Maryland - University College
University of Maryland Center for Environmental Sciences
University of Massachusetts - Lowell
University of Massachusetts Amherst
University of Massachusetts Boston
University of Massachusetts Dartmouth
University of Massachusetts Worcester/University of Massachusetts Medical School
University of Memphis
University of Miami
University of Michigan - Ann Arbor
University of Michigan - Dearborn
University of Michigan - Flint
University of Michigan Health Systems
University of Minnesota - Crookston
University of Minnesota - Duluth
University of Minnesota - Morris
University of Minnesota - Twin Cities
University of Minnesota Medical Center - Fairview
University of Mississippi
University of Missouri - Columbia
University of Missouri - Kansas City
University of Missouri - St Louis
University of Mobile
University of Montevallo
University of Mount Olive
University of Mount Union
University of Nebraska - Lincoln
University of Nebraska - Nebraska College of Technical Agriculture
University of Nebraska at Kearney
University of Nebraska at Omaha
University of Nebraska Medical Center
University of Nevada - Las Vegas
University of Nevada - Reno
University of New England
University of New Hampshire
University of New Haven
University of New Mexico
University of New Orleans
University of North Alabama
University of North America
University of North Carolina at Asheville
University of North Carolina at Chapel Hill
University of North Carolina at Charlotte
University of North Carolina at Greensboro
University of North Carolina at Pembroke
University of North Carolina at Wilmington
University of North Dakota
University of North Florida
University of North Georgia
University of North Texas
University of North Texas at Dallas
University of North Texas Health Science Center at Fort Worth
University of Northern Colorado
University of Northern Iowa
University of Northern Virginia
University of Northwestern - St. Paul
University of Northwestern Ohio
University of Notre Dame
University of Oklahoma
University of Oregon
University of PA Health System
University of Pennsylvania
University of Philosophical Research
University of Phoenix
University of Pikeville
University of Pittsburgh - Bradford
University of Pittsburgh - Johnstown
University of Pittsburgh - Main Campus
University of Pittsburgh - Titusville
University of Portland
University of Puerto Rico - Aguadilla
University of Puerto Rico - Arecibo
University of Puerto Rico - Bayamon
University of Puerto Rico - Carolina
University of Puerto Rico - Cayey
University of Puerto Rico - Humacao
University of Puerto Rico - Mayaguez
University of Puerto Rico - Medical Sciences Campus
University of Puerto Rico - Rio Piedras Campus
University of Puerto Rico - Utuado
University of Puget Sound
University of Redlands
University of Rhode Island
University of Richmond
University of Rio Grande/Rio Grande Community College
University of Rochester
University of Saint Francis - Fort Wayne
University of Saint Joseph
University of Saint Mary
University of Saint Mary of the Lake Mundelein Seminary
University of San Diego
University of San Francisco
University of Science and Arts of Oklahoma
University of Scranton
University of Sioux Falls
University of South Alabama
University of South Carolina - Aiken
University of South Carolina - Beaufort
University of South Carolina - Columbia
University of South Carolina Upstate
University of South Dakota
University of South Florida
University of South Los Angeles
University of Southern California
University of Southern Indiana
University of Southern Maine
University of Southern Mississippi
University of Southernmost Florida
University of Southernmost Florida - Coral Gables Campus
University of Spa & Cosmetology Arts
University of St. Augustine for Health Sciences
University of St. Francis
University of St. Thomas
University of Texas at Austin
University of Texas at Tyler
University of Texas Health Science Center Houston
University of Texas Health Science San Antonio
University of Texas Southwestern Medical Center
University of the Cumberlands
University of the District of Columbia
University of the District of Columbia David A Clarke School of Law
University of the Incarnate Word
University of the Ozarks
University of the Pacific
University of the People
University of the Potomac
University of the Rockies
University of the Sacred Heart
University of the Sciences
University of the Southwest
University of the Virgin Islands
University of the West
University of Toledo
University of Tulsa
University of Utah
University of Valley Forge
University of Vermont
University of Virginia
University of Washington
University of West Alabama
University of West Georgia
University of Western States
University of Wisconsin - Eau Claire
University of Wisconsin - Green Bay
University of Wisconsin - La Crosse
University of Wisconsin - Madison
University of Wisconsin - Milwaukee
University of Wisconsin - Oshkosh
University of Wisconsin - Parkside
University of Wisconsin - Platteville
University of Wisconsin - River Falls
University of Wisconsin - Stevens Point
University of Wisconsin - Stout
University of Wisconsin - Superior
University of Wisconsin - Whitewater
University of Wisconsin Colleges
University of Wyoming
Upper Iowa University
Urbana University
USA Language Center at San Diego University of Integrative Studies
Utah State University
Utah Valley University
Valdosta State University
Valley City State University
Valparaiso University
Vanderbilt University
Vanguard University of Southern California
Venice Community Adult School & Skills Ctr. - University CAS
Veritas International University
Victory University
Villanova University
Vincennes University
Virginia Christian University
Virginia Commonwealth University
Virginia International University
Virginia Polytechnic Institute and State University
Virginia State University
Virginia Tech University Falls Church
Virginia Union University
Virginia University of Integrative Medicine
Virginia University of Lynchburg
Viterbo University
Wake Forest University
Walden University
Waldorf University
Walla Walla University
Walsh University
Warner University
Washburn University of Topeka
Washington Adventist University
Washington and Lee University
Washington State University
Washington University in St Louis
Washington University of Virginia
Wayland Baptist University
Wayne State University
Waynesburg University
Webber International University
Weber State University
Webster University
Wesleyan University
West Chester University of Pennsylvania
West Coast University
West Liberty University
West Texas A&M University
West Virginia State University
West Virginia University
West Virginia University at Parkersburg
Westcliff University
Western Carolina University
Western Connecticut State University
Western Covenant University
Western Governors University
Western Illinois University
Western International University
Western Kentucky University
Western Michigan University
Western Michigan University Homer Stryker M.D. School of Medicine
Western Michigan University Thomas M. Cooley Law School
Western New England University
Western New Mexico University
Western Oregon University
Western State Colorado University
Western State University - College of Law
Western University of Health Sciences
Western Washington University
Westfield State University
Wheeling Jesuit University
Whitworth University
Wichita State University
Wichita State University Campus of Applied Sciences and Technology
Widener University
Wilberforce University
Wilkes University
Willamette University
William Carey University
William Howard Taft University
William Jessup University
William Paterson University of New Jersey
William Peace University
William Penn University
William Woods University
Williams Baptist University
Wilmington University
Wingate University
Winona State University
Winston-Salem State University
Winthrop University
Wittenberg University
Woodbury University
Worcester State University
World Mission University
Wright Graduate University for the Realization of Human Potential
Wright State University
Xavier University
Xavier University of Louisiana
Yale University
Yeshiva University
Yo San University of Traditional Chinese Medicine
York College of the City University of New York
Yorktown University, Inc.
Youngstown State University
Yuin University
EOF;

    $schools = explode("\n", $schools);

    $schoolsDB = \App\Models\School::whereIn('name', $schools)->pluck('name');
    dd($schoolsDB);

    $user = User::find(195);
    $user->setMeta('test123', 'test123_new2' . mt_rand(1111,9999), 'application');
    $user->save();

    $user->setMeta([
        'arraytest' => '1212' . mt_rand(1111,9999),
        'arraytest2' => '1212' . mt_rand(1111,9999),
        'arraytest3' => '1212' . mt_rand(1111,9999),
    ]);
    $user->save();
    return 'w0w';

    $trip = Trip::find(468);

    $driver             = $trip->driver;
    $currentCancelRides = intval($driver->getMetaMulti(\App\Models\UserMeta::GROUPING_DRIVER)->get('canceled_trips', 0));

    $driver->setMeta(['canceled_trips' => $currentCancelRides + 1], \App\Models\UserMeta::GROUPING_DRIVER);
    $driver->save();

    dd($trip->affectDriverRating());

    $trip = Trip::all()->first();
    $driver = $trip->driver;
    intval($driver->getMetaMulti(\App\Models\UserMeta::GROUPING_DRIVER)->get('canceled_trips', 0));
    /*$driver->setMeta([
        'canceled_trips' => intval($driver->getMetaMulti(\App\Models\UserMeta::GROUPING_DRIVER)->get('canceled_trips', 0)) + 1
    ], \App\Models\UserMeta::GROUPING_DRIVER);*/
    // dd($driver);
    // $driver->save();
    return 'w0w';

    $avgRating = \App\Models\TripRating::where([
                'ratee_id'   => '170',
                'ratee_type' => TripMember::TYPE_DRIVER,
            ])->get();
    dd($avgRating->pluck('rating'));

    function avgRating($ratings)
    {
        return array_sum($ratings) / count($ratings);
    }

    $ratings = [
        // 4.0,
        4.5,
        4.5,
        4.5,
        4.5,
        4.5,
        // 0.9,
    ];

    // dd(avgRating($ratings));

    // dd( sprintf('%.2f', avgRating($ratings)) );
    // dd( array_sum($ratings) - (4.70 * count($ratings)) );

    /*$ratings[] = avgRating($ratings) / 2;
    dd( sprintf('%.2f', avgRating($ratings)) );*/

    $deductPercentage = 11;
    $penalizeRatingValue = (avgRating($ratings) * (100-(isset($deductPercentage) ? $deductPercentage : 0)) / 100);
    // dd( sprintf('%.2f', $penalizeRatingValue) );

    $newRating = ($penalizeRatingValue * (count($ratings)+1)) - array_sum($ratings);

    $ratings[] = $newRating;
    dd($penalizeRatingValue, $newRating, avgRating($ratings));


    $goingTrip = TripRide::find(670);
    dd($goingTrip->intimateDriverAboutFailedPayment(User::find(195)));

    $user = User::find(195);
    $trip_ids = TripRide::whereIn('trip_id', Trip::whereUserId($user->id)->pluck('id'))->pluck('id');

    $results = \App\Models\Transaction::whereIn('trip_ride_id', $trip_ids)->groupBy('trip_ride_id')->addSelect(DB::raw('*, SUM(amount - refunded_amount) as earning'))->paginate(10);
    dd($results);

    $goingTrip = TripRide::find(670);
    $goingTrip->trip->resetTripData();
    return 'w0w';
    // dd($goingTrip->getMeta());
    $sdf = $goingTrip->getMeta()->mapWithKeys(function($value, $key) {
        return substr($key, 0, 11) == 'preference_' ? [substr($key, 11) => !!$value] : [];
    })->map(function($value, $key) {
        return [
            'identifier' => $key,
            'checked' => $value,
        ];
    })->values();

    dd(json_encode($sdf));

    /*$file = app_path('../database/data/cars.json');
    $cars = json_decode(file_get_contents($file));

    foreach ($cars as $key => $value) {
        $make = \App\Models\CarMake::create([
            'label' => $value->title,
        ]);
        // dd($make->carmodel());

        foreach ($value->models as $key => $model) {

            $modelTitle = ltrim($model->title, ' - ');
            // dd($model->title, $modelTitle);

            try {
                $make->carmodel()->create([
                    'label' => $modelTitle,
                ]);
            } catch (Exception $e) {}
        }
        // exit;
    }*/

    return \App\Helpers\RESTAPIHelper::response([
        'make' => [
            [
                'name' => 'honda',
                'models' => [
                        'civic',
                        'city',
                ]
            ],
            [
                'name' => 'toyota',
                'models' => [
                        'vitz',
                ]
            ]
        ],
    ]);

    return (generatePreferencesResponse(\App\Models\RidePreference::getPreferences()));

    dd(Carbon\Carbon::now()->format('U'));
    dd(Carbon\Carbon::createFromTimestamp(Carbon\Carbon::now()->format('U'))->format('Y-m-d 00:00:00'));

    dd(doubleval(TripMember::find(248)->getEntireTripFareByMember()));

    dd(\App\Models\Coupon::getNetAmountToCharge(2, 100));

    dd(calculatePercentage(10, TripMember::REFUND_CURFEW_PERCENTAGE));

    $ride = TripRide::find(404);
    $time = $ride->trip->expected_start_time;
    $c = \Carbon\Carbon::parse($time)->subDays(15);
    $c = \Carbon\Carbon::now()->addMinutes(1440);
    // dd( \Carbon\Carbon::HOURS_PER_DAY * \Carbon\Carbon::MINUTES_PER_HOUR * \Carbon\Carbon::SECONDS_PER_MINUTE );
    if ( \Carbon\Carbon::now()->diffInSeconds($c, false) <= 86400 ) {
        dd('Refund fee');
    }

    dd('Refund w/o fee');

    dd(App\Models\Setting::updateSettingArray([
        [
            'config_value' => 125,
            'config_key' => 'core.rate_per_mile',
        ],
        [
            'config_value' => '30%',
            'config_key' => 'setting.application.cancellation_fee',
        ]
    ]));

    dd(App\Models\TripRideShared::find('000d1623-c78c-483b-aa1d-f8ab736ec2a2'));

    $d = App\Models\TripRideShared::create([
        'id' => Ramsey\Uuid\Uuid::uuid4(),
        'trip_ride_id' => 370,
        'user_id' => 170,
    ]);
    dd($d);

    /*$notif = app('App\Models\Notification');
    $notif->throwNotificationsVia('push');
    dd( $notif->throwNotifications( App\Models\Notification::find(162) ) );*/

    User::find(170)->createNotification('driver', 'Offer expired', [
        'message' => 'Your offer has been expired for trip ',
        'type' => 'offer_contradicted',
    ])->customPayload([
        'click_action' => 'offer_contradicted',
        'trip_id' => 170,
    ])->throwNotificationsVia('push')->build();
    dd(1);

    $request = request();
    $request->merge([
        '_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjk2LCJpc3MiOiJodHRwOi8vMTkyLjE2OC4xNjguMTE0L3NlYXR1cy9wdWJsaWMvYXBpL3YxL2xvZ2luIiwiaWF0IjoxNTEzNjEwNjMzLCJleHAiOjE1NDUxNDY2MzMsIm5iZiI6MTUxMzYxMDYzMywianRpIjoieHBqV1l2RHF2Ym9SeHhucyJ9.ghy2Vl4EgR2VsStFuk8kUbQBBIGxsfWVZdihKiy50Ic',
        'user_id' => 170,
        'user_type' => 'driver',
        'payload' => '{ "data":
   { "data_title": "Trip Invitation",
     "data_message": "P23 P23 has invited you to a trip as a driver",
     "data_action": "driver_invitation",
     "date": "1517598000000",
     "destination_geo": "24.8784932,67.0641994",
     "destination_text": "Malir Cantonment, Karachi, Pakistan",
     "inviter_id": "10042",
     "inviter_name": "P23 P23",
     "origin_geo": "24.8784932,67.0641994",
     "origin_text": "Dilkusha Forum, 7 Tariq Rd, Karachi, Pakistan",
     "time_range": "1" },
  "notification":
   { "title": "Trip Invitation",
     "body": "P23 P23 has invited you to a trip as a driver",
     "click_action": "driver_invitation",
     "sound": "default",
     "badge": "3" } }',
    ]);
    // $inject = app('App\Http\Requests\Api\SearchRideRequest');
    $inject = $request;
    // dd($request->all());
    return app('App\Http\Controllers\Api\WebserviceController')->firebaseNotification($inject);

    dd(1);

    App\Classes\FirebaseHandler::update('/notifications/170', [
        'data' => [
            'data_title' => 'offer test 3',
            'data_message' => 'offer test',
            'data_action' => 'trip_offer',
            'trip_id' => '118',
            'driver_id' => '195',
            'passenger_id' => '193',
            'sender' => 'qq@qq.qq',
        ],
        'notification' => [
            'title' => 'offer test 2',
            'body' => 'offer test',
            'click_action' => 'trip_offer',
            'sound' => 'default',
        ],
    ]);
    dd(1);

    App\Classes\PushNotification::sendToUserConditionally(10015, [
        'content' => [
            'title' => 'Offer expired',
            'message' => 'Your offer has been expired for trip xyz',
            'action' => 'offer_contradicted',
        ],
        'data' => [
            'trip_id' => 115,
        ],
    ]);
    dd(1);

    $passengers = 194;
    $ride       = TripRide::find(351);

    event(new App\Events\OfferAcceptedByPassenger($ride, User::find($passengers), 193, TripRideOffer::find(125)));

    // $ride->changeTimeRangeOfRide(collect(TripRideOffer::find(116)), User::find($passengers));
    return 'ww0';

    $user = User::find(177);
    // dd($user->metaDataMulti);
    // $user->setMeta('rating', 4.01, 'driver');
    // $user->save();
    // unset($user->school_name);
    // $user->unsetMeta('school_name');
    dd($user->getMetaMulti('profile'));
    dd($user->getMetaMulti('driver', ['school_name', 'postal_code', 'rating']));

    /*dd(polylineDecode('uwzvCy_jxKQIGBWASIKQAWJWTMR?LFLLBTAR`@^zB|AlDbC|CrBF?@AF?LFDNDFbL`I|B|AbCtAnNpHrIxEbE`C`SpK`J~ExBlAR^RZL`@Vh@j@j@z@JbFJ~A?LMPGFc@'));

    $ride = TripRide::find(66);
    $confirmed = $ride->members->filter(function($row){ return $row->isReadyToFly(); });
    // dd( $ride->members );
    dd( $confirmed );

    $pref = '[
      {
        "id": 2,
        "title": "Gender",
        "identifier": "gender_02",
        "var_type": "string",
        "options": [
          {
            "label": "Male",
            "value": "Male",
            "ride_preference_id": 2,
            "checked": true
          },
          {
            "label": "Female",
            "value": "Female",
            "ride_preference_id": 2,
            "checked": false
          },
          {
            "label": "Other",
            "value": "Other",
            "ride_preference_id": 2,
            "checked": true
          }
        ]
      },
      {
        "id": 1,
        "title": "Smoking",
        "identifier": "smoking_01",
        "var_type": "boolean",
        "options": [
          {
            "label": "Yes",
            "value": "1",
            "ride_preference_id": 1,
            "checked": true
          },
          {
            "label": "No",
            "value": "0",
            "ride_preference_id": 1,
            "checked": true
          }
        ]
      }
    ]';

    $preferences = extractSelectedPreferences(json_decode($pref));
    $preferences = filterPreferencesToSearchFor(json_decode($pref));
    dd($preferences);*/

    // 86 = SM, 88 = Bahadurabad
    $request = request();
    $request->merge([
        '_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjk2LCJpc3MiOiJodHRwOi8vMTkyLjE2OC4xNjguMTE0L3NlYXR1cy9wdWJsaWMvYXBpL3YxL2xvZ2luIiwiaWF0IjoxNTEzNjEwNjMzLCJleHAiOjE1NDUxNDY2MzMsIm5iZiI6MTUxMzYxMDYzMywianRpIjoieHBqV1l2RHF2Ym9SeHhucyJ9.ghy2Vl4EgR2VsStFuk8kUbQBBIGxsfWVZdihKiy50Ic',
        'destination_latitude' => '24.8624948',
        'destination_longitude' => '67.0551319',
        'origin_latitude' => '24.8820309',
        'origin_longitude' => '67.0670115',
        'expected_start_date' => '12/20/2017',
        'time_range' => '1',
        'is_roundtrip' => '1',
        'invited_members' => '1',
        'rating' => '0',
    ]);
    $inject = app('App\Http\Requests\Api\SearchRideRequest');
    // dd($request->all());
    return app('App\Http\Controllers\Api\RideController')->searchRide($inject, (new App\Models\Trip));

    /*App\Classes\PushNotification::sendToUserConditionally(96, [
        'content' => [
            'title' => 'title',
            'message' => 'message',
            'action' => 'new_offer',
        ],
        'data' => [
            'trip_id' => '123',
        ],
    ]);*/

    // dd((new App\Classes\PHPFireStore\FireStoreTimestamp( Carbon\Carbon::now() ))->parseValue());
    /*$d = new DateTime('2017-12-13T13:06:26.366Z');
    dd($d->format('Y-m-d\TG:i:s.z\Z'));
    dd(Carbon\Carbon::now() instanceof DateTime);*/
    // dd( App\Classes\FireStoreHandler::getDocument('groups/66/chat', '64xWXUfI059MrBRP8hX3') );

    // dd(JWTAuth::fromUser(User::find(170)));

    /*$r=App\Classes\FireStoreHandler::addDocument("groups/66/offers_96_151/offers", null, [
        'trip_id'      => strval(1),
    ]);
    dd($r);*/

    /*dd( App\Classes\FireStoreHandler::deleteDocument('groups/tt/offers_61_62', 'removed') );

    dd(App\Classes\FireStoreHandler::overwriteCollection(true)->updateDocument('groups/tt/offers_61_62', 'removed', [
        'test' => '',
    ]));*/

    /*$ride = App\Models\TripRide::find(66);
    event(new App\Events\TerminateOfferUponTimeChange([
        [
            'ride' => $ride,
            'passenger_id' => 151,
            'group_id' => '7461b1be7a4ae79954e83b3f6a64907a',
        ]
    ]));
    return 'w0w';*/

    $ride = App\Models\TripRide::find(66);
    /*FireStoreHandler::addDocument('groups/'.$ride->id.'/offers_96_170', null, [
        'delete' => true,
    ]);*/
    // $ride->addPassengers([151], $member);
    $me = User::find(170);
    $driverId = 96;

    event(new App\Events\OfferMadeByPassenger($ride, $me, User::find($driverId), [170]));
    return 'w0w';

    /*$request = request();
    $request->merge([
        'trip_id' => 33,
        'user_id' => 170,
        '_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjk2LCJpc3MiOiJodHRwOi8vMTkyLjE2OC4xNjguMTE0L3NlYXR1cy9wdWJsaWMvYXBpL3YxL2xvZ2luIiwiaWF0IjoxNTA5MDA0MDI4LCJleHAiOjE1NDA1NDAwMjgsIm5iZiI6MTUwOTAwNDAyOCwianRpIjoiR1Nrckg4Y0c0QjV5eUx2TSJ9.onCbgNeiHK-GuqlkI6qNWh3aZJg9u_img6eNTahyCF8',
    ]);
    // dd($request->all());
    return app('App\Http\Controllers\Api\RideController')->driverMakeOffer($request);*/

    $ride      = App\Models\TripRide::with('trip')->find(55);
    $r = $ride->members()->memberId( 170 )->first();
    dd($r->isConfirmed());
    return 'w0';
    $groupMembers = $ride->offers()->groupId('offer-123')->get();
    $groupMembersUserIds = $groupMembers->map(function($row) {
        return $row->extractUserIdByUserType(App\Models\TripMember::TYPE_PASSENGER);
    });
    // dd($groupMembersUserIds);

    // We won't add passenger to the list if they're already in the list
    // So we need to filter the list of users first
    $existingMembers = $ride->members()->pluck('user_id');

    // Now we've ids of those users which are not the passengers for this trip
    $toAddNewPassengers = $groupMembersUserIds->diff($existingMembers->intersect($groupMembersUserIds));

    // return '0w';
    dd($toAddNewPassengers);

    dd(App\Models\TripMember::generateUniqueGroupId($ride->trip, [1,2,3]));
    $passenger = User::find(170);
    $driver = User::find(96);

    $r = $ride->offers()->hasAnyOfferByDriver($driver->id)->first();
    $r = $ride->offers()->hasAnyOfferByPassenger($passenger->id)->first();

    // return 'w0w';
    return $r;

    $passengers = new App\Classes\PHPFireStore\FireStoreArray;
    foreach ([170,96] as $user) {
        $passengers->add( new App\Classes\PHPFireStore\FireStoreReference('users/'.$user) );
    }
    // $passengers->add('w0w');
    // $passengers->add(654);
    // dd($passengers->getData());

    // dd(App\Classes\FireStoreHandler::getDocument('groups', '1234'));
    $r = App\Classes\FireStoreHandler::overwriteCollection(false)->updateDocument('groups', '123', [
        'passengers' => [170, 96],
    ]);

    dd($r);

    /*dd(bifurcateCoOrdinates([
        'latitude' => '24.88428678502543',
        'longitude' => '67.05677032470703'
    ], [
        'latitude' => '24.881795021312',
        'longitude' => '67.060510090419'
    ]));

    $trip = App\Models\Trip::find(39);
    $trip->attachPassengers( explode(constants('api.separator'), '') );
    dd($trip);*/

    $polyline = 'cazvC}nixKuDgCWUC@G?MGCQFMLENSnGsLrByDjFcKn@wACQ@SFOJMLEL?n@iA^_A`B}EhIsWhA}Cf@qAN]h@y@zAeBpAmAvEiDvGiFn@{@~F{ERQFWCg@}C{G]o@g@sAqByDuIoPeDqGyDwHeAeBcCwCs@w@k@_Ae@y@a@gAc@aB{@gDa@kBuAaG{@sEkAsI{@wDi@iBgBuE{DiKqDoIeEsJaBgDoIiQyB_FyDkJgAoCyAgEsBmHqDoOSyAQeAi@}BgA}FSoAYsEKsDKuHG{HOoGIcRBcBn@kYXwLDkEA_GKeh@AkMC}RDmGDuHAwEG_HIiLG_AA}@FcBPcB\aBBe@CYQg@OWkEiBcFsBqLeE}JwCmAYu@I{@CeAHmB\aBp@OHSkAe@sC}AsJgC{OuBgMgAsHAiAFaA^iCJi@Bg@C_@G[Ye@q@YSMc@K{FsA_MaD{LyCiA]DUxErADQEAkJ{BsDaAzAaIj@yCeDq@CA\gB\_B';
    $beforeRoutes = polylineDecode($polyline);

    /*$routes = [
        $beforeRoutes[83],
        $beforeRoutes[84],
        $beforeRoutes[85],
        $beforeRoutes[86],
        $beforeRoutes[87],
        $beforeRoutes[88],
    ];
    echo(polylineEncode($routes));exit;*/

    // dd($beforeRoutes);
    $routes = [];
    foreach ($beforeRoutes as $key => $value) {
        if ( !isset($beforeRoutes[$key+1]) ) {
            $routes = array_merge($routes, [$value]);
            break;
        }

        $expanded = bifurcateCoOrdinates($value, $beforeRoutes[$key+1]);

        $routes = array_merge($routes, $expanded);
    }
    echo(polylineEncode(($routes)));exit;

    dd($routes);

    $request = request();

    $pref = '[
      {
        "id": 2,
        "title": "Gender",
        "identifier": "gender_02",
        "var_type": "string",
        "options": [
          {
            "label": "Male",
            "value": "Male",
            "ride_preference_id": 2,
            "checked": true
          },
          {
            "label": "Female",
            "value": "Female",
            "ride_preference_id": 2,
            "checked": true
          },
          {
            "label": "Other",
            "value": "Other",
            "ride_preference_id": 2
          }
        ]
      },
      {
        "id": 1,
        "title": "Smoking",
        "identifier": "smoking_01",
        "var_type": "boolean",
        "options": [
          {
            "label": "Yes",
            "value": "1",
            "ride_preference_id": 1,
            "checked": true
          },
          {
            "label": "No",
            "value": "0",
            "ride_preference_id": 1
          }
        ]
      }
    ]';

    $preferences = extractSelectedPreferences(json_decode($pref));
    dd($preferences);
    $request->merge([
        'preferences'=>$pref
    ]);
    dd($request->get('preferences'));

    // $rides = App\Models\Trip::searchRides();
    // dd($rides->toSql());

    /*if ( count($preferences) ) {
        $rides = App\Models\TripRide::meta()->select('trip_rides.*')->where(function($query) use($preferences) {
            foreach ($preferences as $key => $options) {
                if ( is_array($options) && count($options) > 0 ) {
                    dd(array_map('removeQuotes', $options));
                    $query
                        ->where('trip_ride_meta.key', 'preference_' . $key)
                        ->whereIn('trip_ride_meta.value', $options);
                }
            }
        });
    }

    $rides = $rides->get();

    dd( DB::query()->whereIn('sdfds', ['sdff', '33242'])->getBindings() );*/

    $rides = (new App\Models\Trip)->searchTripsByRequest( $request );

    /*$result = [];
    foreach ($rides as $ride) {
        $result[] = [
            'driver' => [
                'user_id' => $ride->driver->id,
                'full_name' => $ride->driver->full_name,
                'profile_picture' => $ride->driver->profile_picture_auto,
            ],
            'trip_name' => $ride->trip_name,
            'trip_id' => $ride->id,
            'expected_distance' => $ride->expected_distance,
        ];
    }*/

    // dd( $result );

    return var_export($rides);

    dd($rides);

    /*function generateUpToDateMimeArray($url){
        // file_put_contents('mimes.cache', file_get_contents($url));exit;
     $s=array();
     $result=[];
     foreach(@explode("\n",@file_get_contents($url))as $x)
     if(isset($x[0])&&$x[0]!=='#'&&preg_match_all('#([^\s]+)#',$x,$out)&&isset($out[1])&&($c=count($out[1]))>1) {
         for($i=1;$i<$c;$i++) {
            $result[$out[1][$i]] = $out[1][0];
         }
     }
     // $s[]='&nbsp;&nbsp;&nbsp;\''.$out[1][$i].'\' => \''.$out[1][0].'\'';
     return $result;
    }

    // dd(generateUpToDateMimeArray('http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types'));
    dd(generateUpToDateMimeArray('mimes.cache'));*/

});

// Development Routes [START]
Route::get('/debug/schools-import', function() {
    set_time_limit(3600);
    ignore_user_abort(true);

    $states = <<<JSON
{
    "AL": "Alabama",
    "AK": "Alaska",
    "AS": "American Samoa",
    "AZ": "Arizona",
    "AR": "Arkansas",
    "CA": "California",
    "CO": "Colorado",
    "CT": "Connecticut",
    "DE": "Delaware",
    "DC": "District Of Columbia",
    "FM": "Federated States Of Micronesia",
    "FL": "Florida",
    "GA": "Georgia",
    "GU": "Guam",
    "HI": "Hawaii",
    "ID": "Idaho",
    "IL": "Illinois",
    "IN": "Indiana",
    "IA": "Iowa",
    "KS": "Kansas",
    "KY": "Kentucky",
    "LA": "Louisiana",
    "ME": "Maine",
    "MH": "Marshall Islands",
    "MD": "Maryland",
    "MA": "Massachusetts",
    "MI": "Michigan",
    "MN": "Minnesota",
    "MS": "Mississippi",
    "MO": "Missouri",
    "MT": "Montana",
    "NE": "Nebraska",
    "NV": "Nevada",
    "NH": "New Hampshire",
    "NJ": "New Jersey",
    "NM": "New Mexico",
    "NY": "New York",
    "NC": "North Carolina",
    "ND": "North Dakota",
    "MP": "Northern Mariana Islands",
    "OH": "Ohio",
    "OK": "Oklahoma",
    "OR": "Oregon",
    "PW": "Palau",
    "PA": "Pennsylvania",
    "PR": "Puerto Rico",
    "RI": "Rhode Island",
    "SC": "South Carolina",
    "SD": "South Dakota",
    "TN": "Tennessee",
    "TX": "Texas",
    "UT": "Utah",
    "VT": "Vermont",
    "VI": "Virgin Islands",
    "VA": "Virginia",
    "WA": "Washington",
    "WV": "West Virginia",
    "WI": "Wisconsin",
    "WY": "Wyoming"
}
JSON;

    $cities = "Montgomery,Normal,Birmingham,Huntsville,Tuscaloosa,Alexander City,Athens,Auburn University,Phenix City,Selma,Enterprise,Bay Minette,Rainbow City,Gadsden,Dothan,Hanceville,Florence,Deatsville,Jacksonville,Brewton,Tanner,Marion,Livingston,Andalusia,Fairfield,Mobile,Montevallo,Muscle Shoals,Rainsville,Phil Campbell,Monroeville,Elmhurst,Evergreen,Boaz,Tuskegee,Talladega,Troy,Daphne,Sumiton,Anchorage,Fairbanks,Juneau,Seward,Valdez,Sitka,Phoenix,Glendale,Flagstaff,Tucson,Tempe,Yuma,Scottsdale,Coolidge,Lake Havasu City,Douglas,Sierra Vista,Mesa,Thatcher,Avondale,Kingman,Tsaile,Holbrook,Prescott,Safford,Little Rock,Arkadelphia,Russellville,Batesville,North Little Rock,Fort Smith,Fayetteville,Pine Bluff,Beebe,State University,Monticello,Pocahontas,Conway,Blytheville,De Queen,Paragould,Forrest City,Hot Springs,Searcy,Siloam Springs,Springdale,Mountain Home,West Memphis,Harrison,Malvern,Melbourne,Clarksville,Morrilton,Helena,El Dorado,Hope,Mena,Walnut Ridge,Magnolia,Camden,Los Angeles,San Francisco,Oakland,Huntington Beach,Alameda,Santa Maria,Berkeley,West Covina,Covina,Sacramento,Hayward,Lancaster,Pasadena,San Diego,Vista,Azusa,Bakersfield,Barstow,National City,Scotts Valley,Anaheim,La Mirada,Long Beach,Oroville,Chatsworth,Aptos,Riverside,Thousand Oaks,San Luis Obispo,Turlock,San Bernardino,Pomona,Chico,Carson,Fresno,Fullerton,Northridge,Davis,Irvine,La Jolla,Santa Barbara,Santa Cruz,Palo Alto,Vacaville,Modesto,Santa Ana,La Mesa,Valencia,Vallejo,Redwood City,Santa Clarita,Garden Grove,Van Nuys,San Jose,Norwalk,Ridgecrest,Rancho Cucamonga,Orange,Santee,Glendora,Claremont,Visalia,Clovis,Fountain Valley,Wilmington,Sonora,Tarzana,Morgan Hill,Compton,Panorama City,San Pablo,Yucaipa,El Cajon,Cypress,Cupertino,Hemet,Big Pine,Blue Lake,Palm Desert,Tracy,Pleasant Hill,San Rafael,Rosemead,Monterey Park,Santa Clara,Torrance,Hollywood,Burbank,Moreno Valley,Santa Monica,Santa Rosa,Quincy,Los Altos Hills,Oceanside,Eureka,Vislia,Gilroy,Carlsbad,Ontario,San Pedro,Salinas,Daly City,Carpinteria,Arcata,Stockton,Imperial,Newport Beach,Reedley,San Dimas,La Verne,Laguna Beach,South Lake Tahoe,Susanville,Loma Linda,Whittier,Woodland Hills,Valley Glen,Sylmar,Pittsburg,Tulare,Kentfield,Rancho Palos Verdes,Ukiah,Atherton,Merced,Oxnard,North Hollywood,Monterey,Sunnyvale,Moorpark,Walnut,San Jacinto,Napa,Reseda,Gardena,Alhambra,Emeryville,Granada Hills,Mission Hills,Fremont,Belmont,Menlo Park,Costa Mesa,Angwin,Pacoima,Blythe,San Marcos,Concord,Burlingame,Malibu,Porterville,El Monte,Redlands,Upland,Corona,Mission Viejo,Camarillo,San Anselmo,French Camp,Rocklin,San Mateo,Redding,Weed,San Bruno,Moraga,Rohnert Park,Chula Vista,Taft,Yuba City,Santa Paula,Lake Forest,Ventura,Victorville,Minneapolis,Coalinga,Culver City,Inglewood,Saratoga,Escondido,Denver,Marysville,Grand Junction,Alamosa,Greeley,Littleton,Colorado Springs,Boulder,Aurora,Broomfield,Lakewood,Glenwood Springs,Rangely,Golden,Fort Collins,Delta,Durango,Westminster,Lamar,Fort  Morgan,Sterling,La Junta,Thornton,Pueblo,Mancos,Trinidad,Gunnison,New Haven,Danbury,Enfield,Monsey,Branford,Southington,Bridgeport,New Britain,Newington,New London,Storrs,East Hartford,East Windsor,Willimantic,North Haven,Hartford,West Hartford,Cromwell,Manchester,Waterbury,Middletown,Norwich,Milford,Somers,West Haven,Winsted,Hamden,Meriden,Stratford,Danielson,Stamford,New York,Farmington,Bath,Lewes,Georgetown,Dover,Newark,New Castle,Washington,Daytona Beach,Fort Lauderdale,Coconut Creek,Graceville,Miami Shores,Panama City,Boca Raton,Bradenton,Starke,Cocoa,Winter Park,Ocala,Kissimmee,Orlando,Port Charlotte,Marianna,Clearwater,Naples,Saint Petersburg,Fort Myers,Miami,Tallahassee,Saint Augustine,Vero Beach,Temple Terrace,Miramar,Tampa,Pensacola,Key West,Miami Gardens,Gainesville,Lakeland,Fort Pierce,Dania Beach,Hobe Sound,Pinellas Park,South Daytona,Hialeah,Lake City,Eustis,Leesburg,Lithonia,Margate,Miami Lakes,Coral Gables,Orange Park,Lake Worth,Madison,Niceville,West Palm Beach,New Port Richey,Winter Haven,St. Petersburg,Milton,Boynton Beach,Oakland Park,Sarasota,St Augustine,Saint Leo,Sanford,Palatka,Avon Park,DeLand,Live Oak,Miami Beach,Perry,Trinity,Lake Wales,Chipley,Babson Park,Winter Garden,Davie,Inverness,Tifton,Decatur,Chamblee,Albany,Cuthbert,Savannah,Atlanta,Conyers,East Point,Augusta,Bainbridge,Forest Park,Fitzgerald,Mount Berry,Mount Vernon,Brunswick,Waco,Morrow,Columbus,Rome,Lookout Mountain,Dalton,Stone Mountain,Swainsboro,Franklin Springs,Fort Valley,Americus,Oakwood,Milledgeville,Statesboro,Barnesville,Griffin,Lilburn,Lawrenceville,Warner Robins,Kennesaw,Lagrange,Marietta,Macon,Cochran,Moultrie,Dahlonega,Clarkesville,Jasper,Demorest,Waleska,Thomasville,Toccoa Falls,Cleveland,Valdosta,Waycross,Carrollton,Young Harris,Honolulu,Hilo,Aiea,Lihue,Pearl City,Kahului,Kapolei,Kaneohe,Boise,Rexburg,Idaho Falls,Pocatello,Moscow,Caldwell,Lewiston,Twin Falls,Coeur D'alene,Nampa,Chubbuck,Chicago,Edwardsville,Godfrey,Rock Island,Belleville,Richmond,Moline,Carlinville,Peoria,Bourbonnais,Oak Lawn,Elgin,Oak Forest,Galesburg,East Moline,Downers Grove,River Forest,Danville,East Saint Louis,Oakbrook Terrace,Crystal Lake,Du Quoin,Glen Ellyn,Charleston,Hines,Bedford Park,Evanston,University Park,Canton,Greenville,Winnetka,West Dundee,Sycamore,Oswego,Skokie,Freeport,North Chicago,Lisle,Champaign,Bloomington,East Peoria,Oglesby,Carterville,Joliet,Kankakee,Centralia,Malta,La Salle,Grayslake,Mattoon,Romeoville,Lincoln,Springfield,Melrose Park,Macomb,Lebanon,Monmouth,Clarendon Hills,Palos Hills,Morrison,Cicero,Lombard,Niles,Naperville,Dekalb,Des Plaines,Chicago Heights,Elsah,Ina,Rockford,Dixon,Ullin,Mundelein,Harrisburg,Shelbyville,Carbondale,South Holland,Litchfield,Palos Heights,Deerfield,River Grove,Sugar Grove,Wheaton,Palatine,Donaldson,Anderson,Muncie,Mishawaka,Indianapolis,Whiting,Hobart,Fort Wayne,Highland,Greencastle,Schererville,Valparaiso,Evansville,Franklin,Vincennes,Goshen,Winona Lake,Noblesville,Hanover,Terre Haute,Notre Dame,Huntington,Kokomo,South Bend,Gary,New Albany,Knox,Lafayette,North Manchester,Elkhart,Merrillville,Jeffersonville,Kalamazoo,Oakland City,Greenfield,Hammond,Westville,Rensselaer,Saint Mary-Of-The-Woods,Saint Meinrad,Angola,Crawfordsville,Waterloo,Des Moines,Sioux City,Storm Lake,Burlington,Dubuque,Cedar Rapids,Pella,Marshalltown,Denison,Ankeny,Epworth,Sioux Center,Davenport,Lamoni,Grinnell,Mt. Pleasant,Ottumwa,Fort Dodge,Estherville,Ames,Mount Pleasant,Council Bluffs,Iowa City,Cedar Falls,Johnston,Mason City,Decorah,Orange City,Calmar,Sheldon,Indianola,West Burlington,Creston,Keokuk,Fayette,Forest City,Waverly,Oskaloosa,Salina,Iola,Manhattan,Baldwin City,Great Bend,Atchison,Lindsborg,North Newton,Topeka,Kansas City,Shawnee,McPherson,Concordia,Coffeyville,Colby,Arkansas City,Dodge City,Emporia,Hays,Haviland,Wichita,Fort Scott,Garden City,Lawrence,Hesston,Hutchinson,Independence,Overland Park,Parsons,Liberal,Olathe,Chanute,Beloit,Goodland,Ottawa,Pratt,Leavenworth,Winfield,Hillsboro,Pippa Passes,Wilmore,Ashland,Nicholasville,Louisville,Berea,Bowling Green,Owensboro,Campbellsville,Lexington,Pineville,Middlesboro,Williamsburg,Elizabethtown,Murray,Hyden,Glasgow,Hazard,Henderson,Hopkinsville,Jackson,Frankfort,Grayson,LONDON,Columbia,Madisonville,Maysville,Mayfield,Midway,Morehead,Covington,Highland Heights,Mount Sterling,Pikeville,Prestonsburg,Saint Catharine,Somerset,Cumberland,Crestview Hills,Barbourville,Paducah,Alexandria,Shreveport,Bastrop,Baton Rouge,Bossier City,New Orleans,West Monroe,Lake Charles,Denham Springs,Chalmette,Grambling,Metairie,Harvey,Kenner,Eunice,Lake Providence,Gonzales,Opelousas,Ruston,Thibodaux,Monroe,Natchitoches,St. Benedict,Slidell,Houma,Schriever,Bogalusa,Morgan City,South Portland,Bar Harbor,Bangor,Auburn,Waterville,Arundel,Fort Kent,Machias,Orono,Castine,Presque Isle,Portland,Biddeford,Standish,Westbrook,Unity,Calais,Waldorf,Frederick,Gaithersburg,Arnold,Hagerstown,Baltimore,Bowie,Laurel,North East,La Plata,Wye Mills,Takoma Park,Salisbury,Frostburg,McHenry,Bel Air,Silver Spring,Reisterstown,Adelphi,College Park,Princess Anne,Towson,Rockville,Emmitsburg,New Carrollton,Largo,Timonium,Saint Mary's City,Annapolis,Bethesda,Stevenson,Greenbelt,Chestertown,Cambridge,Boston,Amherst,Newton Centre,Paxton,Marlborough,Worcester,South Lancaster,Wellesley,Longmeadow,Waltham,Pittsfield,Brookline,Chestnut Hill,Bridgewater,Fall River,Brockton,West Barnstable,Woburn,Bedford,Beverly,Fitchburg,Framingham,Wenham,South Hamilton,Turners Falls,Holyoke,West Springfield,New Bedford,Newton,Lowell,Swampscott,Woods Hole,Wellesley Hills,Buzzards Bay,Chelsea,South Hadley,Gardner,Watertown,Brighton,Dudley,North Adams,Danvers,Haverhill,Chicopee,Weston,Charlestown,Roxbury Crossing,Salem,West Boylston,Great Barrington,Northampton,North Dartmouth,Easton,Taunton,Medford,Westfield,Norton,Williamstown,Adrian,Albion,Hillsdale,Southfield,Alma,Alpena,Berrien Springs,Grand Rapids,Flint,Escanaba,Bay City,Farmington Hills,Ann Arbor,Bloomfield Hills,Detroit,Royal Oak,University Center,Riverview,Southgate,Ypsilanti,Big Rapids,Centreville,Flint Township,Ironwood,Wyoming,Allendale,Lansing,Dearborn,Holland,Howell,Portage,Battle Creek,Roscommon,Benton Harbor,Sault Ste Marie,Warren,Livonia,Marquette,Rochester Hills,East Lansing,Houghton,Sidney,Muskegon,Petoskey,Traverse City,Midland,Rochester,Olivet,Waterford,Madison Heights,Orchard Lake,Port Huron,Dowagiac,Spring Arbor,Plainwell,Hancock,Saint Joseph,Saint Clair Shores,Westland,Ferndale,Scottville,Anoka,Coon Rapids,Austin,Bemidji,Mankato,St. Paul,Brainerd,Northfield,Moorhead,Rosemount,New Ulm,Duluth,Dultuh,Fergus Falls,Woodbury,Granite Falls,Saint Peter,Saint Paul,Brooklyn Park,Hibbing,Inver Grove Heights,North Mankato,Blaine,Virginia,St. Louis Park,Crookston,Roseville,Morris,Richfield,Saint Cloud,Thief River Falls,Eagan,Owatonna,Pine City,International Falls,Willmar,Collegeville,Winona,Saint Bonifacius,Marshall,New Brighton,Plymouth,Ely,White Bear Lake,Alcorn State,Blue Mountain,Carthage,Gulfport,Clarksdale,Wesson,Tupelo,Scooba,Ripley,West Point,Raymond,Goodman,Fulton,Ellisville,Kosciusko,Meridian,University,Itta Bena,Clinton,Perkinston,Mississippi State,Booneville,Senatobia,Poplarville,Holly Springs,Summit,Hattiesburg,Tougaloo,Corinth,Saint Peters,Maryland Heights,St. Louis,Blue Springs,Boonville,Cape Girardeau,Moberly,Warrensburg,Chillicothe,Conception,Nevada,Neosho,Union,Saint Louis,Hannibal,Kennett,Kirksville,Camdenton,Jefferson City,St. Charles,Linn,Chesterfield,Excelsior Springs,Fenton,Park Hills,Florissant,Joplin,Rolla,Maryville,Point Lookout,Parkville,Poplar Bluff,Sedalia,Sikeston,St Louis,Bolivar,West Plains,Trenton,Eldon,Liberty,Bozeman,Browning,Butte,Billings,Great Falls,Glendive,Lame Deer,Kalispell,Harlem,Poplar,Crow Agency,Miles City,Missoula,Havre,Pablo,Dillon,Bellevue,Omaha,Grand Island,Chadron,Blair,Crete,Hastings,Kearney,Beatrice,North Platte,Papillion,Macy,Norfolk,Peru,Curtis,Wayne,Scottsbluff,York,Las Vegas,Sparks,Reno,Elko,Incline Village,Carson City,Hudson,Nashua,Laconia,Somersworth,Rindge,Keene,Warner,Henniker,West Lebanon,Durham,Berlin,Portsmouth,Merrimack,Chester,Hackensack,Mendham,Mays Landing,Bayonne,Paramus,West Paterson,Bloomfield,Brick,Lincroft,Pemberton,Blackwood,Hackettstown,Jersey City,Parsippany,Iselin,Clifton,Randolph,Vineland,Erial,Elizabeth,Plainfield,South Plainfield,Englewood,Teaneck,Lodi,Paterson,Glassboro,Sewell,Cherry Hill,Ramsey,Piscataway,Voorhees,Edison,West Long Branch,Montclair,Nutley,New Brunswick,West New York,Toms River,Gloucester,Westwood,Princeton,Morristown,Mahwah,Perth Amboy,Hawthorne,Carneys Point,South Orange,Egg Harbor Township,North Branch,Delran,Hoboken,Galloway,Adelphia,Teterboro,Ewing,Cranford,Ridgewood,Alamogordo,Albuquerque,Roswell,Crownpoint,Portales,Santa Fe,Las Cruces,Hobbs,Socorro,El Rito,Tucumcari,Silver City,Flushing,Queensbury,Alfred,Youngsville,Long Island City,Elmira,Brooklyn,Annandale-On-Hudson,Forest Hills,Bethpage,Levittown,Bronx,Binghamton,Valley Stream,Buffalo,Nanuet,Fresh Meadows,Cazenovia,Riverhead,Niagara Falls,East Aurora,Potsdam,Plattsburgh,Yonkers,Hamilton,West Babylon,Astoria,Bronxville,Ithaca,Corning,Syracuse,Hyde Park,Staten Island,Queens,Bayside,Jamaica,Orangeburg,Oakdale,Poughkeepsie,Schenectady,Canandaigua,Dix Hills,Johnstown,Batavia,Far Rockaway,Oneonta,Herkimer,Hamburg,Geneva,Hempstead,New Rochelle,Amityville,Jamestown,Melville,Keuka Park,Brookville,Riverdale,Purchase,Tonawanda,Tarrytown,Dobbs Ferry,Rockville Centre,Valhalla,Utica,Newburgh,Mineola,Suffern,Seneca Falls,Sanborn,Niagara University,Peekskill,Saranac Lake,Old Westbury,Nyack,Cortland Manor,Olean,Paul Smiths,West Seneca,Johnson City,St. Bonaventure,Brooklyn Heights,Sparkill,Loudonville,Saratoga Springs,Crestwood,Hornell,Loch Sheldrake,Delhi,Cobleskill,Farmingdale,Morrisville,Stony Brook,Brockport,Cortland,Fredonia,Geneseo,New Paltz,Dryden,Stone Ridge,Kings Point,West  Point,Northport,Glen Cove,White Plains,New  York,Pottersville,Richmond Hill,South Fallsburg,Elizabeth City,Polkton,Boone,Asheville,Wilson,Charlotte,Greensboro,Dublin,Flat Rock,Brevard,Supply,Buies Creek,Morehead City,Hickory,Murfreesboro,Shelby,New Bern,Davidson,Tarboro,Elon,Winston Salem,Boiling Springs,Dallas,Weldon,Clyde,Dunn,High Point,Spindale,Kenansville,Smithfield,Banner Elk,Kinston,Louisburg,Mars Hill,Williamston,Spruce Pine,Raleigh,Statesville,Montreat,Mount Olive,Rocky Mount,Chapel Hill,Winston-Salem,Grantsboro,Pembroke,Misenheimer,Roxboro,Winterville,Asheboro,Hamlet,Ahoskie,Lumberton,Wentworth,Pinehurst,Whiteville,Sylva,Albemarle,Wake Forest,Dobson,Graham,Murphy,Swannanoa,Goldsboro,Morganton,Wilkesboro,North Wilkesboro,Wingate,Cullowhee,Bismarck,Dickinson,New Town,Grand Forks,Minot,Fargo,Devils Lake,Fort Totten,Mayville,Wahpeton,Bottineau,Williston,Fort Yates,Ellendale,Belcourt,Valley City,Cincinnati,Wright-Patterson AFB,Akron,Orrville,Lima,Jefferson,Concord Township,St. Clairsville,Bluffton,Rio Grande,Zanesville,Dayton,Huber Heights,Cedarville,Wilberforce,St. Martin,Youngstown,Blue Ash,Circleville,Wheeling,Steubenville,Beachwood,Lisbon,Toledo,Miamisburg,Defiance,Granville,Seven Hills,Piqua,Milan,Willoughby,Findlay,Tiffin,Hiram,Nelsonville,University Heights,Austintown,Kent,Gambier,Kettering,Painesville,Kirtland,Chesapeake,Sheffield Village,Elyria,Sylvania,Mansfield,Medina,Delaware,Oxford,Clayton,Alliance,New Concord,Cuyahoga Falls,Lorain,Archbold,Rootstown,Oberlin,Ada,Wooster,Canal Winchester,East Liverpool,Westerville,Perrysburg,Brecksville,Maumee,Sandusky,Wickliffe,Centerville,Gallipolis,North Canton,South Point,Urbana,Pepper Pike,Oklahoma City,Muskogee,Bartlesville,Bethany,Broken Arrow,Lawton,Poteau,Edmond,Claremore,Wilburton,El Reno,Enid,Moore,Norman,Langston,Tishomingo,Tulsa,Tahlequah,Tonkawa,Alva,Goodwell,Stillwater,Okmulgee,Midwest City,Chickasha,Seminole,Durant,Weatherford,Altus,Woodward,Pendleton,Bend,Oregon City,La Grande,Eugene,Newberg,Grants Pass,McMinnville,Marylhurst,Clackamas,Saint Benedict,Gresham,Lake Oswego,Klamath Falls,Corvallis,Forest Grove,Happy Valley,Tigard,Roseburg,Wilsonville,Coos Bay,Willow Grove,Hazleton,Bryn Athyn,Philadelphia,Reading,Lester,Pittsburgh,Meadville,Allentown,Center Valley,Altoona,Ambler,Bryn Mawr,Erdenheim,Kittanning,Lower Burrell,Exton,Clarks Summit,Monaca,Glenside,Hatfield,Bloomsburg,Bradford,Coatesville,Lewisburg,Newtown,Feasterville,Butler,Radnor,California,Lansdale,Summerdale,Cheyney,New Kensington,Clarion,Mechanicsburg,Media,Doylestown,Carlisle,Monessen,DuBois,East Stroudsburg,King of Prussia,Saint Davids,Edinboro,Pottsville,Whitehall,Moosic,Pottstown,Erie,Myerstown,Beaver Falls,Gettysburg,Upper Darby,Norristown,Grove City,Gwynedd Valley,Haverford,Immaculata,Indiana,Wilkes Barre,Scranton,Huntingdon,La Plume,Kutztown,North Wales,Annville,Schnecksville,Wyomissing,Bethlehem,Lincoln University,Lock Haven,Nanticoke,Williamsport,Jenkintown,Grantham,Millersville,Blue Bell,Cresson,Aston,McKees Rock,Elkins Park,Hermitage,Bristol,Du Bois,Ebensburg,Langhorne,Titusville,West Mifflin,Punxsutawney,West Reading,Wyncote,Broomall,Moon Township,Rosemont,Loretto,Latrobe,Frackville,Greensburg,Sewickley,Sharon,Shippensburg,Slippery Rock,Wynnewood,State College,South Canaan,Stroudsburg,Selinsgrove,Swarthmore,Falls Creek,Ambridge,Phoenixville,Sharon Hill,Villanova,Waynesburg,West Chester,New Wilmington,Youngwood,Wilkes-Barre,Chambersburg,Providence,East Greenwich,Pawtucket,Warwick,Kingston,Newport,North Providence,Graniteville,Beaufort,Central,Greenwood,Cheraw,Clemson,Rock Hill,Hartsville,Cayce,Spartanburg,Denmark,Due West,North Augusta,Gaffney,West Columbia,Sumter,Newberry,Tigerville,Aiken,Kingstree,Sioux Falls,Rapid City,Spearfish,Mitchell,HURON,Yankton,Aberdeen,Kyle,Brookings,Mission,Agency Village,Vermillion,Nashville,Memphis,McKenzie,Chattanooga,Dickson,Dyersburg,Elizabethton,Gallatin,Harriman,Hohenwald,Jacksboro,Knoxville,Harrogate,Pulaski,Cookeville,Germantown,Milligan College,Tullahoma,Newbern,Paris,Crump,Sewanee,Crossville,Collegedale,Martin,Blountville,Greeneville,Eagle Pass,Abilene,Alvin,Amarillo,Garland,San Angelo,Lubbock,Odessa,Lufkin,Arlington,Grand Prairie,Houston,Sherman,Beaumont,San Antonio,College Station,Beeville,Brenham,Lake Jackson,Farmers Branch,Killeen,Bryan,Cisco,Victoria,Clarendon,Kerrville,Corpus Christi,Irving,Tyler,Texarkana,Commerce,Mesquite,El Paso,Fort Worth,Borger,Galveston,New Waverly,Big Spring,Brownwood,Sunland Park,Hawkins,Kilgore,Port Arthur,Laredo,Baytown,Longview,Texas City,Belton,Wichita Falls,Corsicana,The Woodlands,Denton,Edinburg,Brownsville,Prairie View,Ranger,Seguin,Levelland,Weslaco,McAllen,Uvalde,Waxahachie,Nacogdoches,Terrell,Alpine,Stephenville,Temple,Kingsville,Richardson,Forth Worth,Harlingen,Sweetwater,Vernon,Plainview,Canyon,Snyder,Wharton,Provo,Logan,Laie,West Jordan,Salt Lake City,Price,Midvale,Kaysville,Saint George,Lindon,Cedar City,West Valley City,Ogden,Ephraim,Roosevelt,Orem,Bennington,Castleton,Montpelier,Poultney,Johnson,Lyndonville,Marlboro,Middlebury,South Burlington,Colchester,Brattleboro,Rutland,Craftsbury Common,South Royalton,Randolph Center,Roanoke,Newport News,Virginia Beach,Weyers Cave,Bluefield,Lynchburg,Front Royal,Suffolk,Clifton Forge,Emory,Harrisonburg,Melfa,Ferrum,Fairfax,Locust Grove,Hampden-Sydney,Hampton,Farmville,Staunton,Fredericksburg,Big Stone Gap,Annandale,Martinsville,Colonial Heights,Charlottesville,Falls Church,Radford,Glenns,Petersburg,Winchester,Buena Vista,Alberta,Richlands,Sweet Briar,Wise,Abingdon,Blacksburg,Fishersville,Wytheville,Wenatchee,Seattle,Bellingham,Moses Lake,Renton,Ellensburg,Richland,Vancouver,Everett,Pasco,Cheney,Lynnwood,Olympia,Sunnyside,Spokane,Shoreline,Toppenish,Kenmore,Mountlake Terrace,Tacoma,Kirkland,Port Hadlock,Bremerton,Yakima,Port Angeles,Lacey,Walla Walla,College Place,Pullman,Philippi,South Williamson,Mt. Hope,Glen Dale,Beckley,Dunbar,Clarksburg,Elkins,Fairmont,Glenville,Martinsburg,Stollings,Welch,Morgantown,Parkersburg,Cross Lanes,Vienna,Shepherdstown,Mount Gay,Buckhannon,Institute,West Liberty,Milwaukee,Green Bay,Janesville,Waukesha,Kenosha,Mequon,Sun Prairie,Appleton,Eau Claire,La Crosse,Fond du Lac,Oshkosh,Wisconsin Rapids,Nashotah,Rhinelander,Wausau,Ripon,Hales Corners,Marshfield,De Pere,Manitowoc,Saint Francis,Fennimore,Neenah,Pewaukee,Whitewater,Shell Lake,Depere,Menomonie,Superior,Platteville,River Falls,Stevens Point,Casper,Riverton,Torrington,Cheyenne,Powell,Sheridan,Rock Springs,Laramie,Pago Pago,Mangilao,Saipan,Arecibo,Bayamon,Santurce,Mayaguez,Guaynabo,San Juan,Ponce,Trujillo Alto,Cupey,Carolina,Caguas,Humacao,Manati,Gurabo,Guayama,San German,Aguadilla,Barranquitas,Mercedita,Fajardo,Hato Rey,Cayey,Utuado,Rio Piedras,Pohnpei,Koror,Charlotte Amalie,Stanford,West Lafayette,Scottsboro,Clarkston,Wahiawa,Itasca,Ponca City,Drumright,Sapulpa,Round Rock,Barrytown,Lapeer,Putney,Oak Park,Plano,Whitesburg,Uniontown,Hurst,Thomaston,Villa Park,Lake Milton,Forty Fort,Mulberry,North Richland Hills,Duncan,Wadley,Mount Prospect,Taylor,Eolia,Cottleville,Federal Way,Montebello,Leroy,Green,Fort Cobb,Mexico,Camp Hill,Gladstone,Berwyn,Ardmore,Xenia,Yukon,Clearfield,McAlester,Waynesville,Wolcott,Box Elder,Selden,Livermore,Jesup,Acworth,Mercer,Salida,Liverpool,Bentonville,Madera,Canonsburg,Keyser,Martinez,Vidalia,Malden,Andover,Pleasant Gap,Gardendale,Downey,Rancho Cordova,City of Industry,Cerritos,Simi Valley,Smyrna,Blue Island,Bourne,Deptford,Oakhurst,Mentor,Lyndhurst,Oregon,Omega,Atoka,Hugo,Idabel,Spiro,Talihina,Arroyo,Majuro,South Houston,Conroe,Manassas,Charles Town,Poulsbo,Poway,Verona,Brimley,Cloquet,Temecula,Spirit Lake,Loveland,Gretna,Essex Junction,Lynwood,Fontana,Pearl,Racine,Miami Spings,North Plainfield,Hauppauge,New Philadelphia,PERKASIE,Fairview Park,Pompano Beach,Middleburg Heights,Grand Blanc,San Juan Capistrano,Joshua Tree,Monrovia,Aliso Viejo,Colton,South Gate,Ozark,Coeburn,Spring Valley,Juana Diaz,Stigler,Westport,Oak Brook,Bricktown,Kenilworth,Groveport,Canfield,Bellefontaine,Boardman,Lucasville,Fairview,Mooresville,Sugar Hill,North Charleston,Kernersville,Seaside,Valley View,Tewksbury,Euclid,Oak Hill,Keshena,Cass Lake,Coldwater,Morris Plains,Seekonk,Nederland,Irwindale,Baldwin Park,Altamonte Springs,Red Bud,Saint Robert,Euless,Scotch Plains,Uniondale,Painted Post,Port Ewen,West Nyack,Syosset,Westbury,Van Wert,Burns Flat,Pryor,Choctaw,Wetumka,Reynoldsville,Lewistown,Oil City,Willow Street,Towanda,Shippenville,Aguada,Glens Falls,Eleanor,Saint Albans,Sandy,Isabela,Sandersville,Wells,Afton,The Dalles,Tillamook,Sallisaw,Stilwell,Vega Baja,Houghton Lake,Raymore,Bountiful,Petaluma,Fort McNair,Laguna Hills,Sandy Springs,North Chesterfield,Selmer,Schaumburg,Sicklerville,Folcroft,Midwest,Struthers,Williamsville,Ludlow,Highland Springs,Ilion,Cape Coral,Yorktown Heights,Huntington Park,Douglasville,Kansas,Grundy,Signal Hill,Fort Walton Beach,Smithville,Waldoboro,Winnebago,Wadesboro,Linthicum,West Bloomfield,Myrtle Beach,Barrow,Mahnomen,Silver  Spring,Hillcrest Heights,Sunset Hills,Cold Spring Harbor,Lemoyne,Norwood,Chandler,Foley,Warr Acres,Walnut Creek,Stanton,Pembroke Pines,Pharr,Maitland,Quantico,Moorefield,MCKINNEY,Puyallup,Bothell,Fern Park,Palm Harbor,Catskill,Gatesville,Southlake,Gold River,Irwin,Somers Point,Midfield,Egg Harbor,Mill Creek,Wentzville,Eastman,Transfer,Beverly Hills,Duarte,Paso Robles,Pompano,Lauderhill,Lithia Springs,Falmouth,Haskell,Saddle Brook,Rego Park,North Lima,Duncanville,Needham,Plymouth Meeting,Hinton,Pico Rivera,Bridgton,Yellow Springs,Broadview Heights,Norcross,Lenexa,Sells,Sullivan,Parma Heights,Bartonville,Ridgway,Santa Fe Springs,Brea,Olney,Sunbury,Redmond,Groton,Pelham,North Olmsted,Stow,Folsom,West  Palm Beach,Russell,Lawrenceburg,Suffield,Gastonia,Redondo Beach,Saginaw,Moca,Centennial,Point Pleasant,Saint Charles,Cresco,Casa Grande,South Charleston,Ipswich,Westboro,Taos,USAF Academy,LaPuente,Capital Heights,Calipatria,Purcellville,Anamosa,Siler City,Fall Church,Grafton,Ione,Fort Leavenworth,Delano,Diamond Springs,Carona,Avenal,Represa,North Highlands,La Puente,El Centro,Chino,Leesport,New Berlin,Lihue, Kauai,Hanford,Kailua-Kona,Mountain View,Sun Valley,Pacific Grove,Paramount,Soledad,San Quentin,Ft. Worth,Rowland Heights,Imperial Beach,Carmichael,Prescott Valley,Capitola,Chowchilla,South San Francisco,Tehachapi,Terminal Island,Sun City,Herrin,Ave Maria,Campbell,Crescent City,Corcoran,Wasco,Norco,Waipahu, Ohau,Watsonville,Ft. Lauderdale,Woodland,Ft. Pierce,Port St. Lucie,Lake Park,West Melbourne,Jonesboro,Burley,Culpeper,Dagsboro,Lackland AFB,West St. Paul,Horn Lake,Castro Valley,Shingle Springs,Indian Harbour Beach,Celebration,Lutherville,Interlochen,Shelocta,Cleveland,,Stratham,Corozal,Coamo,Morovis,Plainsboro,Clarence,Galena,Addison,Milwaukee,,Herndon,Frisco,Ashburn,Bridgeton,Woodinville,Cape May Court House,Albuqueque,Catano,Houston,,New York City,Lewisville,East Orange,East Cleveland,Sheppard AFB,D'Iberville,Ocean Springs,Crystal Springs,Cherokee,Pisgah Forest,Quakertown,Bamberg,New Cumberland,Redstone Arsenal,Colfax,Cathedral City,Delray Beach,Patrick Air Force Base,Fort Sam Houston,Fort Lee,Ft. Lee,Fredericksburg,,Ft. Belvoir,Sussex,Ft. Benning,Ft. George G. Meade,Ft. Meade,Aberdeen Proving Ground,Prestonburg,Morganfield,St. Cloud,Lawndale,North Las Vegas,Manhasset,Succasunna,Turnersville,Woonsocket,Maxwell AFB-Gunter Annex,Ft. Eustis,Orange Beach,Washington Navy Yard,WILLINGBORO,Dyer,Woodstown,Evans,Matthews,Jordanville,Denville,Garberville,Bala Cynwyd,Palmer,Wallingford,Highpoint,Beaver,Willows,Warminster,Ormand Beach,Apple Valley,San Sebastian,Cruz Bay,Shelton,Carroll,Bethel,West Sacramento,Branson,Claymont,St. George,Ansonia,Tyngsboro,Middleton,Billerica,South Easton,Waldo,Baker,Glen Burnie,Ballston Spa,Abbottstown,Spokane Valley,Los Angelas,Alton,Grapevine,Palm Springs,Carville,Port Hueneme,Dahlgren,Garner,Westampton,Harpers Ferry,Miami Springs,Lehi,New City,Temple City,Caquas,Tooele,Dimock,Reeds Spring,Raytown,Hayti,Town and Country,Warrenton,Crossett,West Park,Adamsville,Clare,Mason,Crystal City,San Lorenzo,Norton Shores,Niantic,Clinton Township,Brentwood,Kahului, Maui,Beaverton,Wahiawa, Oahu,Kaulua, Oahu,Merritt Island,Mandeville,Hialeah Gardens,Cranbury,Maxwell AFB,Kelseyville,Hunt Valley,Cleveland Heights,Lauderdale Lakes,Plantation,Jamison,Marlin,Lake Havasu,Mustang,Sayre,Downington,Maple Heights,Warrington,San Fernando,Patton,Osprey,East Meadow,Washington Cross,Greenwood Village,Hallendale,Roxbury,Dorchester,Gallup,Sacaton,Kayenta,Shiprock,Havre de Grace,N. Las Vegas,Atascadero,Sherman Oaks,Commerce City,Chattahoochee,Macclenny,Lake Villa,Carol Stream,Northbrook,East Chicago,Devens,Leeds,Westborough,Andrews AFB,Catonsville,Sykesville,Northville,Center City,Whitfield,Biloxi,Butner,Ancora,West Trenton,Wales,Wauwatosa,Queens Village,Pleasantville,West Brentwood,Bellerose,Vinita,Hershey,Mount Gretna,Fort Meade,Loudon,Bonita Springs,Peshtigo,Gloucester Point,Moundville,Kennewick,Westlake Village,Getzville,Rumford,South Paris,West Hills,Tucker,Studio City,Wilbur by the Sea,Oldsmar,Glendale Heights,Brookfield,Palmdale,Reston,Ormond Beach,Elk Grove Village,Methuen,Punta Gorda,Snellville,Arcadia,Arlington Heights,Sellersville,Rutherfordton,Somerville,White City,Cary,Seymour,North Miami,North Andover,Anza,Soldotna,Deerfield Beach,Artesia,DeFuniak Springs,San Leandro,Hoover,Elk Grove,Upton,Wethersfield,Bridgeview,Libertyville,Brooklyn Center,Langley AFB,USAF Acacemy,Bolling AFB,Camp Pendleton,Camp LeJeune,Barksdale AFB,Scott AFB,Offutt AFB,Travis AFB,Eglin AFB,Long Branch,Great Lakes,Nellis AFB,Port Jefferson,Ft. Sill,Venice,Fort Benning,Fort Carson,Fort Gordon,Fort Hood,Fort Jackson,Fort Campbell,Mare Island,Abington,New Hyde Park,Lake Elmo,Canoga Park,Ladysmith,Walterboro,Palm Coast,Guthrie,Immokalee,Pearl Harbor,Greenacres,Silsbee,Belleview,Ranch Cucamonga,Spanish Fork,Blue Ridge Summit,Collierville,Sturtevant,Corrales,Latham,Shawnee Mission,Doraville,Worthington,Harrisonville,Ravena,Platte City,Piketon,Plain City,Sevierville,Elmendorf AFB,Pleasanton,Woodland Park,American Fork,Coachella,Calexico,Lemoore,Union City,Sanger,Cambria,San Benitio,Layton,Taylorsville,The Villages,Twinsburg,Elkhorn,Mellville,Spencer,Bear,Guilford,Baraga,Bartlett,Weaverville,South Sioux City,Chinle,Borrk Park,Milwaukie,Blackfoot,New Preston,La Quinta,Edina,Kailua Kona,Casselberry,Pontiac,Payson,Brownsburg,St. Clair,Dana Point,Kalispel,South Ogden,Woodridge,Stafford,Spring Hill,Vega Alta,Cordova,DeSoto,North Randall,Lake Mary,Webster,Onalaska,Fairview Heights,Whitestone Queens,Fort Mohave,Yucca Valley,Park Rapids,Royal Palm Beach,Charlton,Freeland,Larkspur,Port Richey,Scarborough,Kew Gardens,Columbia Station,Nemo,Waupun,Santa Teresa,Ellicottville,Derby,Doral,Lander,Togus,Gurnee,Arvada,Pine Brook,Belmar,Albertville,Cushing,Cranston,Bellaire,Post Falls,Maite,Shorewood,Elmwood Park,Waukegan,Vernal,Citrus Heights,Islandia,Mount Laurel,South El Monte,Longmont,Luneburg,MacDill AFB,Churchville,Huntersville,Parker,Olanta,Union Grove,Sterling Heights,West Des Moines,Pontotoc,Idaho Fall,Monett,Hoffman Estate,North Miami Beach,Spring Lake,Delbarton,Royal,Mt. Vernon,Hamilton Square,Fair Lawn,Ionia,Spanish Fort,Ocean Township,St. Ann,Antioch,Red Wing,Haltom,Hadley,Bay Pines,Roosevelt Island,Lyons,Neptune,Jerome,Hicksville,Maywood,Westmont,Landover,South Jordan,Puxico,Kalona,Clermont,Coralville,Fountain Hills,Elmsford,Lompco,Fair Oaks,Stockbridge,Larned,White River Junction,Fort Lewis,Fort Huachuca,Webb City,Heber City,Cedar Park,Riviera Beach,Minden,Mount Clemens,North St. Petersburg,Shannon,St. Peter,Manteca,Woodbridge,Southborough,St. Joseph,Luverne,Calumet,Copiague,Tuba City,Zuni,Calumet City,Oklahoma,Hingham,La Palma,Mayfield Heights,Dunedin,Kentwood,Piedmont,JB Ft. Sam Houston,Canyon Country,Stone Park,Mebane,Bloomingon,Eatontown,West Frankfort,Placentia,McLean,Tannersville,Steven Point,Arkon,Shelby Township,Roslindale,Dearborn Heights,West Roxbury,Coral Springs,Goodlettsville,McDonough,Kamuela,St. Croix,Naranja,Anacostia Annex,Woodside,Hockessin,Aventura,Alabaster,Waianae,Albertson,Mars,New Tazewell,Palisades Park,Winnsboro,Encino,Little Canada,Ketchikan,Jackson Heights,Penns Creek,Gering,Spring,Eastlake,Hendersonville,Park Ridge,Glenmoore,McClean,Rogers,Essington,Patchogue,Rio Grande City,Allston,Westlake,Rocky Hill,Rolling Meadows,South Hackensack,Glenview,East Rutherford,Cuddebackville,Fort Belvoir,Montrose,Fort Bragg,Freehold,Tinley Park,Willowbrook,Yardley,Bloomingdale,Mechanicsville,Matteson,Bossier,San Andreas,Beltsville,Los Angelos,Carmel,Huntingdon Valley,Airway Heights,Lincolnwood,Tysons Corner,Worchester,Richton Park,Nottingham,New Boston,Cumming,Old Hickory,Berkley,Broken Bow,West Hollywood,Wynne,Brooksville,Sebring,Sinking Spring,Brandenton,South Miami,Antlers,Owasso,Sand Springs,Lampasas,West Seattle,Hilliard,Universal City,Draper,Los Alamitos,Leisure City,East Brunswick,Dolton,New Windsor,Cheektoaga,Leicester,Patchouge,Jonesville,Jenks,Heath,New Iberia,Iron Mountain,Mart,Kailua,Swansea,Banning,Lee?s Summit,Ft. Gordon,Wheat Ridge,Bessemer,Rutherford,Holly Hill,Leawood,Keyport,Washington DC,Chantilly,Mattydale,Weimer";
    $cities = explode(',', $cities);

    // if (null === session()->put('ftell', 0)) {
    //     return 'ftell set';
    // }

    $states = json_decode($states, true);
    // dd(count($states));
    $statesDB = \App\Models\State::whereCountryId(231)->whereIn('name', array_values($states))->get();
    $statesNameOnly = $statesDB->pluck('name', 'id');
    // $statesNameOnly->contains( $states['OH'] )

    $citiesDB = \App\Models\City::whereIn('state_id', $statesDB->pluck('id'))->whereIn('name', $cities)->get();
    $citiesNameOnly = $citiesDB->groupBy('state_id');

    unset($cities);
    unset($statesDB);
    unset($citiesDB);

    $schoolsFiles = 'C:\\Users\\ahsaan\\AppData\\Roaming\\Skype\\My Skype Received Files\\UniversityList(final).csv';
    $handle = fopen($schoolsFiles, 'rb');
    fseek($handle, session()->get('ftell'));
    $row = 1;
    $cache = [];
    while ( ($data = fgetcsv($handle) ) !== FALSE ) {

        if (!isset($data[4])) {
            continue;
        }

        # Skip Heading
        if ( $data[0] == 'Institution_ID' )
            continue;

        // dd($data);

        list(, $school, $address, $city, $state) = $data;

        if ( array_key_exists($state, $states) && FALSE !== $stateId = $statesNameOnly->search( $states[$state] ) ) {
            // State found, now find city
            $cityObject = $citiesNameOnly[$stateId]->where('name', $city)->first();
            $cityObject = $cityObject ?: $citiesNameOnly[$stateId]->first();

            if (in_array(md5($cityObject->id . $school), $cache)) {
                continue;
            }

            \App\Models\School::firstOrCreate([
                'city_id' => $cityObject->id,
                'name' => $school
            ]);
            // dd($newSchool);

            $cache[] = md5($cityObject->id . $school);
        }

        // dd($data, $state);

        // dd($school, $city, $state, ftell($handle));
        session()->put('ftell', ftell($handle));
        $row++;

        // if($newSchool) {
        //     // break;
        // }

        if($row>2000) {
            break;
        }
    }

    return 'w0w';
});

Route::get('/debug/driver-ride-flow', function() {
    $request = request();
    $request->merge([
        '_token'       => JWTAuth::fromUser(User::find(170)),
        'desired_gender' => '3',
        'destination_latitude' => '24.872243',
        'destination_longitude' => '67.0602855',
        'destination_title' => 'Tariq Rd, PECHS, Karachi, Karachi City, Sindh, Pakistan',
        'expected_distance' => '10180.0',
        'expected_distance_format' => '06.160 miles',
        'expected_duration' => '29 m',
        'expected_start_date' => '1533798120000',
        // 'invited_members' => '',
        'is_enabled_booknow' => '0',
        'is_roundtrip' => '0',
        'max_estimates' => '1.58',
        'min_estimates' => '0.79',
        'origin_latitude' => '24.9187053',
        'origin_longitude' => '67.1296087',
        'origin_title' => 'Block 14 Gulistan-e-Johar, Karachi, Karachi City, Sindh, Pakistan',
        'preferences' => '[
  {
    "option" : "Yes",
    "title" : "Smoking",
    "id" : 1,
    "checked" : false,
    "identifier" : "smoking_01"
  }
]',
        'seats_total' => '1',
        'stepped_route' => 'k|awC}dvxK~@SRKVWzCxCz@_A|CbDh@k@BBzB|B`BsBRUxA~AdJtJ`BlBlI|MnIjN|EtIjChEz@|AzArDzAtDjE|JLJRBd@K~FwEbBmA^Ud@S\UdAy@rA{@ZMf@Gt@Gl@FFF@HIRy@rAIZCtCG|CS~B]lCSvBCh@DlAF`AR~@fDbMpA`GnAhFj@rAzAlCnAtBf@dAl@lCpBxKlAvGnAtFx@tEdB`PJ`Ab@dANj@Jt@H|@FvA@lADrAv@~GXpB`C~KdErRp@~BtB~HX`ALf@b@x@Ld@p@xBn@|Bl@|BFf@JfC\zI~@jTR|FD@N^N\HFDBTBr@AxAK`C[l@Mn@Wd@UTOPf@`AxCXp@z@~Ab@n@r@n@t@f@z@d@f@N@ABAF?FBHN?D?@~BzApFtDbEtCpAx@JIJCLAVDJFLPD\ANADlAz@xCvBjFrDTL@ADADAH@HFDJ?LfD|B~G~EvBtAjBdAbNlH~F`D',
        'time_range' => '4',
        'trip_name' => '',
    ]);
});

Route::get('/debug/rest-api', function() {

    $request = request();
    $request->merge([
        '_token'       => JWTAuth::fromUser(User::find(170)),
        'page' => '1',
        'limit' => '10',

        'city' => '42595',
        'device_token' => 'dEwJYyb6fMQ:APA91bHoQeFhTI_gobW2m3yW6069A9AdsB40l3YoxqHO2UJrWQco6P_it4L_LVJatEbyORa-OGQ91sC32qFn6pMItGLHQmnVQSMphYoz1lTu-mutPF5ToS9y4D-qDyKIOFglL8iYEMcw',
        'driving_license_no' => '',
        'email' => 'testfb'.mt_rand(1111,9999).'@mailinator.com',
        'facebook_token' => 'EAAEN5Hg6Jk0BAAxJZC9ZAU6SAcfCCCyWPSOCJ7fmZARlJuZAdMZBX6yVnxaE2YqCukHeJ1YzGn1eqeOkzGpoARLjpQUh4tuL1C0LkVz7dY2EtYnJenRTZBtqsAGcjYFsdO4DRrkQgiU3wJM73uNlNQd0PwIH3ZAe9ax4mNZB809lsI6cZACB2CWq152hFIWMigYw4CgAv9ZCJHKSyK4aXmm1etVZBF3bufGDwxZBEOYBYd3VdQZDZD',
        'first_name' => 'John Den',
        'gender' => 'Male',
        'graduation_year' => '2018',
        'insurance_company' => '',
        'insurance_no' => '',
        'last_name' => 'Fb',
        'password' => '123456',
        'phone' => '832564' . mt_rand(1111,9999),
        'policy_effective_date' => '',
        'policy_expiry_date' => '',
        'postal_code' => '2585',
        'reference_source' => 'Facebook',
        'school_name' => 'Willow Bend Cosmetic Surgery Center',
        'state' => '3919',
        'student_organization' => '',
        'user_type' => 'normal',
        'vehicle_id_number' => '',
        'vehicle_make' => '',
        'vehicle_model' => '',
        'vehicle_year' => '',
        'email' => 'po@mailinator.com',
        'password' => 'popopo',
        'origin_latitude' => '24.871807100',
        'origin_longitude' => '67.060006800',
        'destination_latitude' => '24.859551300',
        'destination_longitude' => '67.030076300',
    ]);
    // $inject = app('App\Http\Requests\Api\UserRegisterRequest');
    if ( isset($inject) ) {
        $request = $inject;
    }
    // dd($request->all());
    $json = app('App\Http\Controllers\Api\RideController')->driverListSubscribedRoutes($request, 745);

    // return 'debug';
    return $json;
});

Route::get('/debug/query-debugger', function() {
    $query = <<<LOG
    select `trip_rides`.*, `trips`.*, `users`.`first_name` from `trip_rides` inner join `trips` on `trips`.`id` = `trip_rides`.`trip_id` left join `users` on `users`.`id` = `trips`.`user_id` where exists (select * from `trips` where `trip_rides`.`trip_id` = `trips`.`id` and exists (select * from `users` where `trips`.`user_id` = `users`.`id`) and exists (select * from `users` where `trips`.`initiated_by` = `users`.`id`)) and `trip_rides`.`canceled_at` is not null order by `users`.`first_name` desc limit 10 offset 0 - a:0:

LOG;

    $splitPosition = strrpos($query, ' - ', -1);
    $bindings      = substr($query, $splitPosition+3);
    $query         = trim(substr($query, 0, $splitPosition));
    $bindings      = unserialize($bindings);

    $replace = [];
    foreach ($bindings as $value) {
        if ( is_object($value) ) {
            $value = $value->__toString();
        }

        $replace[] = gettype($value) === 'string' ? "'".removeQuotes($value)."'" : $value;
    }

    foreach ($replace as $newValue) {
        $query = preg_replace('/'.preg_quote('?', '/').'/', $newValue, $query, 1);
    }

    return '<pre>'.$query.'</pre>';
});

Route::get('/debug/queries-debugger', function() {

    $logFileName = env('APP_LOG') == 'daily' ? 'laravel-'.date('Y-m-d') . '.log' : 'laravel.log';
    $queries = storage_path('logs' . DIRECTORY_SEPARATOR . $logFileName);
    foreach (file($queries, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES) as $query) {
        if (0 !== strcasecmp(substr($query, 22, 19), 'local.DEBUG: select'))
            continue;

        // $query = substr($query, 35);
        $query = str_replace('local.DEBUG: ', 'local.DEBUG: <br />', $query);

        $splitPosition = strrpos($query, ' - ', -1);
        $bindings      = substr($query, $splitPosition+3);
        $query         = trim(substr($query, 0, $splitPosition));
        $bindings      = unserialize($bindings);

        $replace = [];
        foreach ($bindings as $value) {
            if ( is_object($value) ) {
                $value = $value->__toString();
            }

            $replace[] = gettype($value) === 'string' ? "'".removeQuotes($value)."'" : $value;
        }

        foreach ($replace as $newValue) {
            $query = preg_replace('/'.preg_quote('?', '/').'/', $newValue, $query, 1);
        }

        echo '<pre>'.$query.'</pre><hr>';
    }

    return '';
});

Route::get('/debug/log-to-curl', function() {
    $query = <<<LOG
    [2019-01-11 12:53:52] log.DEBUG: URL: http://192.168.168.114/seatus/public/api/v1/driver/bank-details/update
Method: POST
Input: Array
(
    [_token] => eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjE3MCwiaXNzIjoiaHR0cDovLzE5Mi4xNjguMTY4LjExNC9zZWF0dXMvcHVibGljL2FwaS92MS9sb2dpbiIsImlhdCI6MTU0NzExNTE5MywiZXhwIjoxNTc4NjUxMTkzLCJuYmYiOjE1NDcxMTUxOTMsImp0aSI6InhQb3BQbGhHa0JSNUEzTVoifQ.Sx9ITTmI7FRbA6lCMxsQFQUHHQDYCDRGg_J_3bxMDaA
    [body] => 004E5ACDE9505FE1DCB977CF169AD49922EB69B90D705EAE117580254E5A8245FF02B82A714736D34F458C61EAF3DE0A7657872E1AD23C116DB578792412CCE0045F94F7A468FC12454EC5EC08FA2B324DE4D738F6F2F89570731D15A4337DD4F16B82FF6A0716B62224A755102994ABAC7E34AA11F1A1F6233F5FED8BD5CE184504142BA809B6B6C16C3875C2ED2DA5C204160DBDE21EA11F4577284F006D5B3D2B7071790DA89E6C8FE5BC7D343E14407B2B4516896AFFBE88F4334F58853E7361C1908F2DE507AF970C4D90ED6BF735D30F7BACCB1A16D3C67BFA48AAE6C95438D9688980A3471F29F5B58F9FBA3C65CADC1CDD1D850D1E402CA82615BFA5B75492A22FBCCAFE4D4AFF87EE25C960
)

LOG;

    preg_match('%URL: (.*)%', $query, $url);
    preg_match('%Method: (.*)%', $query, $method);
    preg_match_all('%\[(.*?)\] => (.*)%', $query, $body, PREG_SET_ORDER);

    $result = [];
    foreach ($body as $key => $value) {
        $result[] = $value[1] . '=' . str_replace("'", "\'", $value[2]);
    }

    return "curl -X ".$method[1]." --header 'Content-Type: application/x-www-form-urlencoded' --header 'Accept: application/json' -d '".implode('&', $result)."' '".$url[1]."'";
});

Route::get('/debug/firebase-user-update', function() {
    set_time_limit(500);
    $users = User::users()->get();
    foreach ($users as $me) {
        event(new App\Events\Api\JWTUserRegistration($me));
    }
});
Route::get('/debug/firestore-user-update/{id}', function($id='') {
    $users = User::users()->whereId($id)->get();
    foreach ($users as $me) {
        event(new App\Events\Api\JWTUserUpdate($me));
    }
});

Route::get('/debug/user-gender-update', function($id='') {
    $hasGender = App\Models\UserMeta::where('key', 'gender')->pluck('user_id');
    $totalUsers = App\Models\User::users()->pluck('id');
    // return 'w0w';
    dd($totalUsers->diff($hasGender));
    foreach ($totalUsers->diff($hasGender) as $key => $userId) {
        $user = App\Models\User::find($userId);
        $user->setMeta([
            'gender' => 'Male',
        ]);
        $user->save();
    }
});

Route::get('/debug/replicate-transactions', function($id='') {
    $trips = TripRide::with(['trip.driver' => function($query) {
        return $query->withTrashed();
    }])->ended()->get();

    foreach ($trips as $ride) {
        event(new \App\Events\TripEnded($ride, $ride->trip->driver));
    }
});

Route::get('validate-configuration-appmaisters', function() {
    $allGood              = true;
    $requiredSettingCount = 8;
    $requiredEnvironment  = [
        'FIRESTORE_API_KEY',
        'FIRESTORE_PROJECT_ID',
        'FIREBASE_SECRET_TOKEN',
        'FIREBASE_DATABASE_URL',
        'FCM_SERVER_KEY',
        'LOG_WEBSERVICE',
        'STRIPE_SECRET',
    ];

    foreach ($requiredEnvironment as $env) {
        if ( null === env($env) ) {
            $allGood = false;
            echo "$env environment does not exist.<br />";
        }
    }

    if ( App\Models\Setting::count() != $requiredSettingCount ) {
        $allGood = false;
        echo 'Setting does not meet the required entry, please verify';
        echo '<br />';
    }

    if ( false === $allGood ) {
        return '<br /><span style="color:red;font-weight:bold;">[x]</span> You donot meet the requirement, please adjust accordingly and re-run the test.';
    } else {
        return '<span style="color:green;font-weight:bold;">[✓]</span> You are good to go!';
    }

});

Route::group(['middleware' => 'backend.auth'], function () {
    Route::get('dev/reset-setting-cache', function() {
        Cache::forget('app.setting');
        return 'DONE!';
    });
});
// Development Routes [END]
