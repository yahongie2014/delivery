<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Transformers\ServiceTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use Illuminate\Support\Facades\Auth;

use App\Language;
use App\ServiceType;
use App\Country;

use Validator;

class ServiceTypeController extends Controller
{
    private $fractal;

    /**
     * @var UserTransformer
     */
    private $serviceTransformer;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Manager $fractal, ServiceTransformer $serviceTransformer)
    {
        $this->fractal = $fractal;
        $this->serviceTransformer = $serviceTransformer;
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
            $services = ServiceType::all();
        else{
            $services = ServiceType::with([
                'language' => function($q){
                    $q->where('language_id',Auth::user()->language_id);
                    $q->select('service_type_language.name');
                },
                'country' => function($q){
                    $q->where('country_id',Auth::user()->country_id);
                    $q->select('services_types_price.price');
                }
            ])->where('status' , SERVICE_TYPE_ACTIVE)->get();

            $services = new Collection($services, $this->serviceTransformer);


            $services = $this->fractal->createData($services); // Transform data
            return response() ->json(['status' => true , 'result' => $services->toArray()]);
        }

        return view('admin.service.index')
            ->with([
                'services' => $services,
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

        return view('admin.service.create')->with([
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
                'status' => 'sometimes|required|integer|in:' . SERVICE_TYPE_ACTIVE,
                'service_type_name' => 'required|unique:service_types,name|max:190',
                'service_type_type' => 'required|integer|in:' . MAIN_SERVICE_TYPE . ',' . EXTRA_SERVICE_TYPE,
                'language' => 'required|array',
                'country' => 'required|array',
            ]
        )->validate();

        //dd($request->all());

        DB::beginTransaction();
        $serviceType = new ServiceType();

        $serviceType->name = e($request->service_type_name);

        if($request->has('status'))
            $serviceType->status = SERVICE_TYPE_ACTIVE;

        $serviceType->type = $request->service_type_type;

        // get all languages
        $languages = Language::pluck('id');

        $typedLanguages = $request->language;

        // get all countries
        $countries = Country::pluck('id');

        $typedCountries = $request->country;

        //dd($languages);
        if($serviceType->save()) {
            $serviceTypeLanguages = [];
            foreach ($languages as $language) {
                if (isset($typedLanguages[$language]))
                    $serviceTypeLanguages[$language]['name'] = e(trim($typedLanguages[$language]));
            }
//dd($categoryLanguages);
            $serviceType->language()->attach($serviceTypeLanguages);

            $serviceTypeCountries = [];
            foreach ($countries as $country) {
                if (isset($typedCountries[$country]))
                    $serviceTypeCountries[$country]['price'] = floatval($typedCountries[$country]);

            }
//dd($categoryLanguages);
            $serviceType->country()->attach($serviceTypeCountries);

            DB::commit();
            return redirect('/admin/services')->with([
                'messageSuccess' => __("general.serviceAddedSuccessfully")
            ]);
        }else{
            DB::rollBack();
            return redirect()->back()->with([
                'messageDander' => __("general.errorAddingService")
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
        $service = ServiceType::with(['language','country'])->where('id',$id)->first();

//dd($categoryLanguages->toArray());
        if($service) {
            $serviceLanguages = $service->language->mapWithKeys(function ($item) {
                return [$item['id'] => $item];
            });

            $serviceCountries = $service->country->mapWithKeys(function ($item) {
                return [$item['id'] => $item];
            });

            return view('admin.service.edit')->with([
                'service' => $service,
                'serviceLanguages' => $serviceLanguages,
                'serviceCountries' => $serviceCountries,
                'languages' => Language::all(),
                'countries' => Country::all()
            ]);
        } else
            return redirect('/admin/service')->with(['messageDanger' => __('general.serviceNotFound') ]);
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
        //
        $data = $request->all();
//dd($request->all());
        Validator::make(
            $data,
            [
                'service_type_id' => 'required|integer|exists:service_types,id',
                'status' => 'sometimes|required|integer|in:' . SERVICE_TYPE_ACTIVE,
                'service_type_name' => ['required','max:190' , Rule::unique('service_types','name')->ignore($data['service_type_id'])],
                /*'service_type_type' => 'required|in:' . MAIN_SERVICE_TYPE . ' , ' . EXTRA_SERVICE_TYPE,*/
                'language' => 'required|array',
                'country' => 'required|array',
            ]
        )->validate();

        //dd($request->all());

        DB::beginTransaction();
        $serviceType = ServiceType::find($request->service_type_id);

        $serviceType->name = e(trim($request->service_type_name));

        if($request->has('status'))
            $serviceType->status = SERVICE_TYPE_ACTIVE;
        else
            $serviceType->status = SERVICE_TYPE_INACTIVE;

        // get all languages
        $languages = Language::pluck('id');

        $typedLanguages = $request->language;

        // get all countries
        $countries = Country::pluck('id');

        $typedCountries = $request->country;

        //dd($languages);
        if($serviceType->save()) {
            $serviceTypeLanguages = [];
            foreach ($languages as $language) {
                if (isset($typedLanguages[$language]))
                    $serviceTypeLanguages[$language]['name'] = e(trim($typedLanguages[$language]));
            }
//dd($categoryLanguages);
            $serviceType->language()->detach();
            $serviceType->language()->attach($serviceTypeLanguages);

            $serviceCountries = [];
            foreach ($countries as $country) {
                if (isset($typedCountries[$country]))
                    $serviceCountries[$country]['price'] = floatval($typedCountries[$country]);

            }
//dd($categoryLanguages);
            $serviceType->country()->detach();
            $serviceType->country()->attach($serviceCountries);

            DB::commit();
            return redirect('/admin/services')->with([
                'messageSuccess' => __("general.serviceUpdatedSuccessfully")
            ]);
        }else{
            DB::rollBack();
            return redirect()->back()->with([
                'messageDander' => __("general.serviceUpdatingError")
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
