<?php

namespace App\Http\Controllers\Auth;


use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use App\Jobs\SendNotification;

use App\Provider;
use App\Delivery;
use App\Language;
use App\Country;
use App\City;
class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/login';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm()
    {
        // get system languages
        $availableLanguages = Language::where('status','1')->get();
        //dd($availableLanguages->toArray());

        // get system Countries
        $availableCountries = Country::where('status',1)->get();

        // get system Cities
        $availableCities = City::where('status',1)->get();

        return view('auth.register')
            ->with(['languages' => $availableLanguages , 'countries' => $availableCountries , 'cities' => $availableCities]);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        //$this->guard()->login($user);

        return $this->registered($request, $user)
            ?: redirect($this->redirectPath());
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        //dd($data);

        //get selected country code
        if($data['country_id'] && $data['phone']) {
            //$data['phone'] = $this->getPhoneWithCode(substr_replace($data['phone'],"",0,1),$data['country_id']);
            $data['phone'] = $this->getPhoneWithCode(ltrim($data['phone'],"0"),$data['country_id']);
        }
//dd($data);
        return Validator::make($data, [
            'name' => 'required|string|max:190',
            'email' => 'required|string|email|max:190|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|digits_between:8,15|unique:users,phone',
            'language_id' => 'required|exists:languages,id|numeric',
            'country_id' => 'required|exists:countries,id|numeric',
            'city_id' => 'required|exists:cities,id|numeric',
            'address' => 'required|string',
            'provider_member' => 'in:1|numeric',
            'driver_member' => 'in:1|required_without:provider_member|numeric'
        ]);
    }


    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $data['phone'] = $this->getPhoneWithCode($data['phone'],$data['country_id']);

        DB::beginTransaction();
        // create the new user Account
        $newUser = User::create([
            'name' => e(trim($data['name'])),
            'email' => e(trim($data['email'])),
            'password' => bcrypt($data['password']),
            'phone' => trim($data['phone']),
            'language_id' => $data['language_id'],
            'country_id' => $data['country_id'],
            'city_id' => $data['city_id'],
            'address' => e($data['address'])
        ]);

        // this is the msg body for notifications
        $adminNotification = __("general.newUserRegistration");

        // if new user account created
        if($newUser){

            // add Provider membership to user account
            if(isset($data['provider_member'])){
                Provider::create([
                    'user_id' => $newUser->id
                ]);

                $adminNotification .= "   " . __("general.registeredAsProvider");
            }

            // add Delivery membership to user account
            if(isset($data['driver_member'])){
                Delivery::create([
                    'user_id' => $newUser->id
                ]);

                $adminNotification .= "   " . __("general.registeredAsDriver");
            }

            //$this->sendNotificationsToUser($adminNotification ,0 );
            dispatch(new SendNotification($adminNotification ,0 , true));
            DB::commit();
        }else{
            DB::rollBack();
        }

        // Notify admin there is new user
        return $newUser;
    }
}
