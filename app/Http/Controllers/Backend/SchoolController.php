<?php

namespace App\Http\Controllers\Backend;

use App\Http\Requests\SchoolRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\School;
use App\Models\State;
use Illuminate\Http\Request;
use Response;
use View;

class SchoolController extends BackendController
{
    private $thisModule = [
        // @var: Module properties
        'longModuleName'  => 'Colleges',
        'shortModuleName' => 'College',
        'viewDir'         => 'modules.schools',
        'controller'      => 'schools',
    ];

    public function __construct()
    {
        View::share([
            'moduleProperties' => $this->thisModule,
        ]);
    }

    public function index()
    {
        $schools = School::get();
        return backend_view($this->thisModule['viewDir'] . '.index', compact('schools'));
    }

    public function edit(Request $request, School $record)
    {
        $record->state_id = $record->city->state_id;

        $states = State::listStates(Country::DEFAULT_COUNTRY_ID);
        $cities = City::listCities($record->state_id);

        return backend_view($this->thisModule['viewDir'] . '.edit', compact('record', 'states', 'cities'));
    }

    public function create(Request $request)
    {
        $states = State::listStates(Country::DEFAULT_COUNTRY_ID);

        if (old('state')) {
            $cities = City::listCities(old('state'));
        } else {
            $cities = [];
        }

        return backend_view($this->thisModule['viewDir'] . '.create', compact('states', 'cities'));
    }

    public function save(SchoolRequest $request)
    {
        if (School::create($request->all())) {
            return redirect(route('schools.index'))->with('alert-success', 'College has been created!');
        }

        return redirect()->back()->with('alert-danger', 'College could not be created!');
    }

    public function update(SchoolRequest $request, School $record)
    {
        if ($record->update($request->all())) {
            return redirect(route('schools.index'))->with('alert-success', 'College has been updated!');
        }

        return redirect()->back()->with('alert-danger', 'College could not be updated!');
    }

    public function delete(School $record)
    {
        if ($record->delete()) {
            return redirect(route('schools.index'))->with('alert-success', 'College has been deleted!');
        }

        return redirect()->back()->with('alert-danger', 'College could not be deleted!');
    }

    public function findSchool(Request $request)
    {
        $response = [];
        $term     = $request->get('term');

        if (!$term) {
            return Response::json([]);
        }

        $schools = School::search($term)->limit(15)->get();

        if (count($schools)) {
            foreach ($schools as $school) {
                $response[] = ['id' => $school->name, 'text' => $school->name];
            }
        }

        return Response::json($response);
    }
}
