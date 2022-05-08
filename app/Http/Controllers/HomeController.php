<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session ;
use App\Jobs\SendSMSMessages;

use App\Language;
use App\User;
use App\Order;
use Validator;
use App;
class HomeController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Get Order Statuses Localized
        parent::__construct();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $loginType = Session::get('login_type');

        $homePage = "";

        if($loginType == ADMIN){
            $homePage = "/admin";
        }elseif($loginType == PROVIDER){
            $homePage = "/provider";
        }else{
            $homePage = "/delivery";
        }

        return redirect($homePage);
    }

    public function admin()
    {
        return view('admin.index')
            ->with([
                'orderStatuses' => $this->orderStatuses,
                'userRoute' => '/admin',
            ]);
    }

    public function provider(){
        return view('provider.index')->with([
            'orderStatuses' => $this->orderStatuses,
            'userRoute' => '/provider',
        ]);
    }

    public function delivery(){
        return view('delivery.index')->with([
            'orderStatuses' => $this->orderStatuses,
            'userRoute' => '/delivery',
        ]);
    }

    public function setLanguage($language_id){


        $validator = Validator::make(
            ['language_id' => $language_id],
            [
                'language_id' => 'required|integer|exists:languages,id',

            ]
        )->validate();

        // Get selected language
        $language = Language::find($language_id);

        // save user language in the session
        Session::put('userLanguage',$language);
        Session::put('userLanguageName',$language->name);

        // update the authenticated user language
        $user = Auth::user();
        $user->language_id = $language_id;

        $user->save();
        return redirect()->back();
    }

    public function notAdmin(){
        return view('errors.notprivileged')
            ->with([
                'missingPrivilege' => __("general.Not_admin"),
                'homePage' => $this->getUserHome()
            ]);
    }

    public function notDelivery(){
        return view('errors.notprivileged')->with([
            'missingPrivilege' => __("general.Not_provider"),
            'homePage' => $this->getUserHome()
        ]);
    }

    public function notProvider(){
        return view('errors.notprivileged')->with([
            'missingPrivilege' => __("general.Not_delivery"),
            'homePage' => $this->getUserHome()
        ]);
    }

    private function getUserHome(){
        $loginType = Session::get('login_type');

        $homePage = "";

        if($loginType == ADMIN){
            $homePage = "/admin";
        }elseif($loginType == PROVIDER){
            $homePage = "/provider";
        }else{
            $homePage = "/delivery";
        }

        return $homePage;
    }

    public function passwordReset(Request $request){
        Validator::make(
            $request->all(),
            [
                'email' => 'required|email|exists:users,email',
            ]
        )->validate();

        $newPassword = rand(100000,999999);

        $user = User::where('email',$request->email)->first();

        $user->password = bcrypt($newPassword);

        if($user->save())
            dispatch(new SendSMSMessages($user->phone , "New Password : " . "\n" . $newPassword));

        return redirect('login');
    }
}
