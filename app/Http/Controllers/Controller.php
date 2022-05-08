<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use App\Country;
use App\Category;
use App\Admin;
use Log;
use App\UserFireBaseToken;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $orderStatuses;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Get Order Statuses Localized
        $this->orderStatuses = \StaticArray::$orderStatus;
    }
    public function test(){
        //$this->getCountries(2);

//        $countries = Country::with(
//            ['language' => function ($q) {
//                $q->where('language_id',1);
//                $q->select('country_language.name');
//            }]
//        )->get();
//        dd($countries->toArray());
//        dd($this->getCountries(2)->toArray());
        //$this->sendNotificationsToUser('Test',7);
        //$this->sendSms('00201270701662','Hi Test');
        dd("Fuck You");
    }

    public function localizeSytemActiveCategories($categories){
        foreach($categories as $category){
            $categoryTranslation = $category->language()->where('language_id',Auth::user()->language_id)->select('category_language.name')->first();
            if($categoryTranslation)
                $category->name = $categoryTranslation->name;
        }

        return $categories;
    }

    public function localizeSystemActiveCountries($countries){
        foreach($countries as $country){
            $countryTranslation = $country->language()->where('language_id',Auth::user()->language_id)->select('country_language.name')->first();
            if($countryTranslation)
                $country->name = $countryTranslation->name;
        }

        return $countries;
    }

    public function localizeSystemActiveCities($cities){
        foreach($cities as $city){
            $cityTranslation = $city->language()->where('language_id',Auth::user()->language_id)->select('city_language.name')->first();
            if($cityTranslation)
                $city->name = $cityTranslation->name;
        }

        return $cities;
    }

    public function localizeServiceTypes($serviceTypes){
        foreach ($serviceTypes as $serviceType){

            // Get serviceType translation to user language
            $serviceTypeTranslation = $serviceType->language()->where('language_id',Auth::user()->language_id)->select('service_type_language.name')->first();

            // if translation present change service name to the retrived one
            if($serviceTypeTranslation)
                $serviceType->name = $serviceTypeTranslation->name;

            // Get service price for the user country
            $serviceTypePrice = $serviceType->country()->where('country_id',Auth::user()->country_id)->select('services_types_price.price')->first();

            if($serviceTypePrice) // if there is price for this country set it
                $serviceType->price = $serviceTypePrice->price;
            else
                $serviceType->price = 0.00;
        }

        return $serviceTypes;
    }

    public function localizePaymentTypes($paymentTypes){
        foreach ($paymentTypes as $paymentType){

            // Get paymentType translation to user language
            $paymentTypeTranslation = $paymentType->language()->where('language_id',Auth::user()->language_id)->select('payment_type_language.name')->first();

            // if translation present change payment type name to the retrived one
            if($paymentTypeTranslation)
                $paymentType->name = $paymentTypeTranslation->name;

            // Get paymentType price for the user country
            $paymentTypePrice = $paymentType->country()->where('country_id',Auth::user()->country_id)->select('payment_types_prices.price')->first();

            if($paymentTypePrice) // if there is price for this country set it
                $paymentType->price = $paymentTypePrice->price;
            else                  // else set price to 0.00
                $paymentType->price = 0.00;
        }

        return $paymentTypes;
    }

    public function getCountries($language_id , $country_id = null){
        if($country_id)
            $countries = Country::where('id',$country_id);
        else
            $countries = Country::all();

        foreach ($countries as $country){
            $country->language_id = $language_id;
            $country->name = $country->translated;
        }

        return $countries;
    }

    protected function getPhoneWithCode($phone,$country_id){
        $countryCode = Country::where('id', $country_id)->first();

        if ($countryCode)
            $phone = $countryCode->code . $phone;

        return $phone;
    }

    protected function sendSms($phone ,$message)
    {
        $data = array(
            "Username" => "966593930003",
            "Password" => "75627",
            "Tagname" => "Delivery",
            "RecepientNumber" => $phone,
            "VariableList" => "[Name]",
            "ReplacementList" => "Ahmed,9000",
            "Message" => $message,
            "SendDateTime" => 0,
            " EnableDR" => true
        );
        $data_string = json_encode($data);

        $ch = curl_init('http://api.yamamah.com/SendSMS');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        if ($result === FALSE) {
            Log::error('Curl failed: ' . curl_error($ch));
        }
        else{
            //echo $result;
            Log::info($result);
        }

        return true;

    }

    public function sendNotificationsToUser($msg,$user = 0 ,$withAdmin = true , $clickAction = ""){
        $url = "https://fcm.googleapis.com/fcm/send";

        $usersToNotify = [];

        if($user != 0){
            $usersToNotify[] = $user;
        }
        // Notify Admins if wanted
        if($withAdmin){
            $admins = Admin::where('status' , ADMIN )->pluck('user_id')->toArray();
            $usersToNotify = array_merge($usersToNotify,$admins);
            //dd($usersToNotify);
        }

        $userTokens = UserFireBaseToken::whereIn('user_id',$usersToNotify)->pluck('firebase_token')->toArray();

       /* WebNotification::create([
            'user_id' => $user,
            'title' => $msgTitle,
            'body' => $msg,

        ]);*/

        //dd($userTokens);
        if(count($userTokens) > 0) {
            $fields = array(
                'registration_ids' => $userTokens,
                'notification' => [
                    "title" => "Delivery App",
                    "body" => $msg,
                    "icon" => asset('dist/img/logo.png'),
                    "click_action" => url($clickAction)
                ]
            );

            $headers = array(
                'Authorization: key=' . GOOGLE_API_KEY,
                'Content-Type: application/json'
            );
            // Open connection
            $ch = curl_init();

            // Set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Disabling SSL Certificate support temporarly
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

            // Execute post
            $result = curl_exec($ch);
                   //dd($result);
            if ($result === FALSE) {
                //die('Curl failed: ' . curl_error($ch));
                Log::error('Curl failed: ' . curl_error($ch));
            } else {
                //echo $result;
                Log::info($result);
            }

            // Close connection
            curl_close($ch);
        }
        return true;
    }
}
