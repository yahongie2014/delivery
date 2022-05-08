<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Transformers\PaymentTypeTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use Illuminate\Support\Facades\Auth;

use App\Language;
use App\PaymentType;
use App\Country;

use Validator;

class PaymentTypeController extends Controller
{
    private $fractal;

    /**
     * @var UserTransformer
     */
    private $paymentTypeTransformer;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Manager $fractal, PaymentTypeTransformer $paymentTypeTransformer)
    {
        $this->fractal = $fractal;
        $this->paymentTypeTransformer = $paymentTypeTransformer;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $loginType = session()->get('login_type');

        if($loginType == ADMIN)
            $paymentTypes = PaymentType::all();
        else{
            $paymentTypes = PaymentType::with([
                'language' => function($q){
                    $q->where('language_id',Auth::user()->language_id);
                    $q->select('payment_type_language.name');
                },
                'country' => function($q){
                    $q->where('country_id',Auth::user()->country_id);
                    $q->select('payment_types_prices.price');
                }
            ])->where('status' , SERVICE_TYPE_ACTIVE)->get();

            $paymentTypes = new Collection($paymentTypes, $this->paymentTypeTransformer);


            $paymentTypes = $this->fractal->createData($paymentTypes); // Transform data
            return response() ->json(['status' => true , 'result' => $paymentTypes->toArray()]);
        }

        return view('admin.paymenttypes.index')
            ->with([
                'paymentTypes' => $paymentTypes,
            ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $languages = Language::all();
        $countries = Country::all();

        return view('admin.paymenttypes.create')->with([
            'languages' => $languages,
            'countries' => $countries,
        ]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $data = $request->all();
//dd($request->all());
        Validator::make(
            $data,
            [
                'status' => 'sometimes|required|integer|in:' . PAYMENT_TYPE_ACTIVE,
                'payment_type_name' => 'required|unique:payment_types,name|max:190',
                'language' => 'required|array',
                'country' => 'required|array',
            ]
        )->validate();

        //dd($request->all());

        DB::beginTransaction();
        $paymentType = new PaymentType();

        $paymentType->name = e(trim($request->payment_type_name));

        if($request->has('status'))
            $paymentType->status = PAYMENT_TYPE_ACTIVE;

        // get all languages
        $languages = Language::pluck('id');

        $typedLanguages = $request->language;

        // get all countries
        $countries = Country::pluck('id');

        $typedCountries = $request->country;

        //dd($languages);
        if($paymentType->save()) {
            $paymentTypeLanguages = [];
            foreach ($languages as $language) {
                if (isset($typedLanguages[$language]))
                    $paymentTypeLanguages[$language]['name'] = e(trim($typedLanguages[$language]));
            }
//dd($categoryLanguages);
            $paymentType->language()->attach($paymentTypeLanguages);

            $paymentTypeCountries = [];
            foreach ($countries as $country) {
                if (isset($typedCountries[$country]))
                    $paymentTypeCountries[$country]['price'] = floatval($typedCountries[$country]);

            }
//dd($categoryLanguages);
            $paymentType->country()->attach($paymentTypeCountries);

            DB::commit();
            return redirect('/admin/paytypes')->with([
                'messageSuccess' => __("general.paymentTypeAddedSuccessfully")
            ]);
        }else{
            DB::rollBack();
            return redirect()->back()->with([
                'messageDander' => __("general.errorAddingPaymentType")
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $paymentType = PaymentType::with(['language','country'])->where('id',$id)->first();

//dd($categoryLanguages->toArray());
        if($paymentType) {
            $paymentTypeLanguages = $paymentType->language->mapWithKeys(function ($item) {
                return [$item['id'] => $item];
            });

            $paymentTypeCountries = $paymentType->country->mapWithKeys(function ($item) {
                return [$item['id'] => $item];
            });

            return view('admin.paymenttypes.edit')->with([
                'paymentType' => $paymentType,
                'paymentTypeLanguages' => $paymentTypeLanguages,
                'paymentTypeCountries' => $paymentTypeCountries,
                'languages' => Language::all(),
                'countries' => Country::all()
            ]);
        } else
            return redirect('/admin/paytypes')->with(['messageDanger' => __('general.paymentTypeNotFound') ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $data = $request->all();
//dd($request->all());
        Validator::make(
            $data,
            [
                'payment_type_id' => 'required|integer|exists:payment_types,id',
                'status' => 'sometimes|required|integer|in:' . PAYMENT_TYPE_ACTIVE,
                'payment_type_name' => ['required','max:190' , Rule::unique('payment_types','name')->ignore($data['payment_type_id'])],
                'language' => 'required|array',
                'country' => 'required|array',
            ]
        )->validate();

        //dd($request->all());

        DB::beginTransaction();
        $paymentType = PaymentType::find($request->payment_type_id);

        $paymentType->name = e(trim($request->payment_type_name));

        if($request->has('status'))
            $paymentType->status = PAYMENT_TYPE_ACTIVE;
        else
            $paymentType->status = PAYMENT_TYPE_INACTIVE;

        // get all languages
        $languages = Language::pluck('id');

        $typedLanguages = $request->language;

        // get all countries
        $countries = Country::pluck('id');

        $typedCountries = $request->country;

        //dd($languages);
        if($paymentType->save()) {
            $paymentTypeLanguages = [];
            foreach ($languages as $language) {
                if (isset($typedLanguages[$language]))
                    $paymentTypeLanguages[$language]['name'] = e(trim($typedLanguages[$language]));
            }
//dd($categoryLanguages);
            $paymentType->language()->detach();
            $paymentType->language()->attach($paymentTypeLanguages);

            $paymentTypeCountries = [];
            foreach ($countries as $country) {
                if (isset($typedCountries[$country]))
                    $paymentTypeCountries[$country]['price'] = floatval($typedCountries[$country]);

            }
//dd($categoryLanguages);
            $paymentType->country()->detach();
            $paymentType->country()->attach($paymentTypeCountries);

            DB::commit();
            return redirect('/admin/paytypes')->with([
                'messageSuccess' => __("general.paymentTypeUpdatedSuccessfully")
            ]);
        }else{
            DB::rollBack();
            return redirect()->back()->with([
                'messageDander' => __("general.errorUpdatingPaymentType")
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
