<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Transformers\CityTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

use App\Language;
use App\Country;
use App\City;

use Validator;
class CityController extends Controller
{
    private $fractal;

    /**
     * @var UserTransformer
     */
    private $cityTransformer;

    public function __construct(Manager $fractal, CityTransformer $cityTransformer)
    {
        $this->fractal = $fractal;
        $this->cityTransformer = $cityTransformer;
    }

    /**
     * Display a listing of the resource.
     * @param $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        $loginType = session()->get('login_type');

        if(Auth::user())
            $language_id = Auth::user()->language_id;
        else
            $language_id = Language::where('default' , DEFAULT_LANGUAGE)->first(['id'])->id;

        $cities = City::with(['country','language' => function($q) use($language_id){
            $q->where('language_id',$language_id);
            $q->select('city_language.name');
        }]);

        if($request->has('country_id'))
            $cities = $cities->where('country_id',$request->country_id);

        if(!($loginType == ADMIN) ){
            if(!$request->has('country_id'))
                $cities = $cities->where('country_id',Auth::user()->country_id);

            $cities = $cities->where('status',CITY_ACTIVE);
        }

        $cities = $cities->get();

        if(Request()->expectsJson()){
            $cities = new Collection($cities, $this->cityTransformer);
            $cities = $this->fractal->createData($cities); // Transform data

            return response() ->json(['status' => true , 'result' => $cities->toArray() ]);
        }

        return view('admin.city.index')
            ->with([
                'cities' => $cities,
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

        return view('admin.city.create')->with([
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
                'status' => 'sometimes|required|integer|in:' . CITY_ACTIVE,

                'city_name' => ['required','max:190' ,
                    Rule::unique('cities','name')
                        ->where(function ($query) use($data) {
                            $query->where('country_id' , $data['country_id']);
                        })
                ],
                'language' => 'required|array',
                'country_id' => 'required|integer|exists:countries,id'
            ]
        )->validate();

        //dd($request->all());

        DB::beginTransaction();
            $city = new City();

            $city->name = e(trim($request->city_name));

            if($request->has('status'))
                $city->status = CITY_ACTIVE;
            else
                $city->status = CITY_INACTIVE;

            $city->country_id = $request->country_id;

            // get all languages
            $languages = Language::pluck('id');

            $typedLanguages = $request->language;

            //dd($languages);
            if($city->save()) {
                $cityLanguages = [];
                foreach ($languages as $language) {
                    if (isset($typedLanguages[$language]))
                        $cityLanguages[$language]['name'] = e(trim($typedLanguages[$language]));

                }

                $city->language()->attach($cityLanguages);
                DB::commit();
                return redirect('/admin/cities')->with([
                    'messageSuccess' => __("general.cityAddedSuccessfully")
                ]);
            }else{
                DB::rollBack();
                return redirect()->back()->with([
                    'messageDander' => __("general.errorAddingCity")
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

        $city = City::with(['language'])->where('id',$id)->first();


        if($city) {
            $cityLanguages = $city->language->mapWithKeys(function ($item) {
                return [$item['id'] => $item];
            });

            return view('admin.city.edit')->with([
                'city' => $city,
                'cityLanguages' => $cityLanguages,
                'languages' => Language::all(),
                'countries' => Country::all()
            ]);
        }
        else
            return redirect('/admin/countries')->with(['messageDanger' => __('general.cityNotFound') ]);
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
                'city_id' => 'required|integer|exists:cities,id',
                'country_id' => 'required|integer|exists:countries,id',
                'status' => 'sometimes|required|integer|in:' . CITY_ACTIVE,
                'city_name' => ['required','max:190' ,
                    Rule::unique('cities','name')
                        ->ignore($data['city_id'])

                        ->where(function ($query) use($data) {
                            $query->where('country_id' , $data['country_id']);
                        })
                ],
                'language' => 'required|array',
            ]
        )->validate();

        //dd($request->all());

        DB::beginTransaction();
        $city = City::find($data['city_id']);

        if($request->has('status'))
            $city->status = CITY_ACTIVE;
        else
            $city->status = CITY_INACTIVE;

        $city->name = e(trim($request->city_name));

        // get all languages
        $languages = Language::pluck('id');

        $typedLanguages = $request->language;

        //dd($languages);
        if($city->save()) {
            // Delete old relation


            $cityLanguages = [];
            foreach ($languages as $language) {
                if (isset($typedLanguages[$language]))
                    $cityLanguages[$language]['name'] = e(trim($typedLanguages[$language]));

            }

            $city->language()->detach();

            $city->language()->attach($cityLanguages);

            DB::commit();
            return redirect('/admin/cities')->with([
                'messageSuccess' => __("general.cityUpdatedSuccessfully")
            ]);
        }else{
            DB::rollBack();
            return redirect()->back()->with([
                'messageDander' => __("general.errorUpdateCity")
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
