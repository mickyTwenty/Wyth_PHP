<?php

namespace App\Http\Controllers\Backend;

use App\Http\Requests\CouponRequest;
use App\Models\Coupon;
use Illuminate\Http\Request;
use View;
use Yajra\Datatables\Datatables;

class PromoCodeController extends BackendController
{
    private $thisModule = [
        // @var: Module properties
        'longModuleName'  => 'Promo Codes',
        'shortModuleName' => 'Promo Code',
        'viewDir'         => 'modules.promo-codes',
        'controller'      => 'promo-codes',

        // @array: Undeletable record id(s)
        'undeleteable'    => [],
        'uneditable'      => [],
    ];

    public function __construct()
    {
        View::share([
            'moduleProperties' => $this->thisModule,
        ]);
    }

    public function index()
    {
        return backend_view($this->thisModule['viewDir'] . '.index');
    }

    public function data(Datatables $datatables)
    {
        $eloquent = Coupon::query();

        return $datatables->eloquent($eloquent)
            ->filter(function ($query) {
                if (request()->has('search.value')) {
                    $query->where('code', 'LIKE', '%' . request('search.value') . '%');
                }
            }, false)
            ->order(function ($query) {
                $column = data_get(request()->input('columns'), request()->input('order.0.column'), []);
                $query->orderBy($column['data'], request()->input('order.0.dir'));
            })
            ->editColumn('code', function ($record) {
                return $record->code;
            })
            ->editColumn('discount_type', function ($record) {
                return $record->discount_type_text;
            })
            ->editColumn('value', function ($record) {
                return $record->value;
            })
            ->editColumn('available_from', function ($record) {
                return $record->available_from;
            })
            ->editColumn('available_till', function ($record) {
                return $record->available_till;
            })
            ->addColumn('action', function ($record) {
                return backend_view($this->thisModule['viewDir'] . '.action', compact('record') + [
                    'edit' => false,
                ]);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function edit(Request $request, Coupon $record)
    {
        $discountTypes = [Coupon::TYPE_AMOUNT => 'Amount', Coupon::TYPE_PERCENTAGE => 'Percentage'];
        return backend_view($this->thisModule['viewDir'] . '.edit', compact('record', 'discountTypes'));
    }

    public function create(Request $request)
    {
        $discountTypes = [Coupon::TYPE_AMOUNT => 'Amount', Coupon::TYPE_PERCENTAGE => 'Percentage'];
        return backend_view($this->thisModule['viewDir'] . '.create', compact('discountTypes'));
    }

    public function save(CouponRequest $request)
    {
        if (Coupon::create($request->all())) {
            return redirect(route('promo-codes.index'))->with('alert-success', 'Promo code has been created!');
        }

        return redirect()->back()->with('alert-danger', 'Promo code could not be created!');
    }

    public function update(CouponRequest $request, Coupon $record)
    {
        if ($record->update($request->all())) {
            return redirect(route('promo-codes.index'))->with('alert-success', 'Promo code has been updated!');
        }

        return redirect()->back()->with('alert-danger', 'Promo code could not be updated!');
    }

    public function delete(Coupon $record)
    {
        if ($record->delete()) {
            return redirect(route('promo-codes.index'))->with('alert-success', 'Promo code has been deleted!');
        }

        return redirect()->back()->with('alert-danger', 'Promo code could not be deleted!');
    }
}
