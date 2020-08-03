<?php

namespace App\Http\Controllers\Backend;

use App\Models\FAQ;
use Illuminate\Http\Request;
use View;

class FAQController extends BackendController
{
    private $thisModule = [
        // @var: Module properties
        'longModuleName'  => 'Questions',
        'shortModuleName' => 'Question',
        'viewDir'         => 'modules.faqs',
        'controller'      => 'faqs',
    ];

    public function __construct()
    {
        View::share([
            'moduleProperties' => $this->thisModule,
        ]);
    }

    public function index()
    {
        $faqs = FAQ::get();
        return backend_view($this->thisModule['viewDir'] . '.index', compact('faqs'));
    }

    public function edit(Request $request, FAQ $record)
    {
        return backend_view($this->thisModule['viewDir'] . '.edit', compact('record'));
    }

    public function create(Request $request)
    {
        return backend_view($this->thisModule['viewDir'] . '.create');
    }

    public function save(Request $request)
    {
        $this->validate($request, [
            'title'   => 'required',
            'content' => 'required',
            'type'    => 'required',
        ]);

        if (FAQ::create($request->all())) {
            return redirect(route('faqs.index'))->with('alert-success', 'Question has been created!');
        }

        return redirect()->back()->with('alert-danger', 'Question could not be created!');
    }

    public function update(Request $request, FAQ $record)
    {
        $this->validate($request, [
            'title'   => 'required',
            'content' => 'required',
            'type'    => 'required',
        ]);

        if ($record->update($request->all())) {
            return redirect(route('faqs.index'))->with('alert-success', 'Question has been updated!');
        }

        return redirect()->back()->with('alert-danger', 'Question could not be updated!');
    }

    public function delete(FAQ $record)
    {
        if ($record->delete()) {
            return redirect(route('faqs.index'))->with('alert-success', 'Question has been deleted!');
        }

        return redirect()->back()->with('alert-danger', 'Question could not be deleted!');
    }
}
