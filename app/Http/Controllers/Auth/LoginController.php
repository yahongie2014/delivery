<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session ;
use Illuminate\Support\Facades\Auth;
use App\Language;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {

        $this->validate($request, [
            $this->username() => 'required|string',
            'password' => 'required|string',
            'login_type' => 'required|in:' . ADMIN . ',' . PROVIDER . ',' . DRIVER
        ]);
    }
    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        Session::put('login_type',$request->login_type);

        switch ($request->login_type){
            case 1:
                $this->redirectTo = '/admin';
                break;
            case 2:
                $this->redirectTo = '/provider';
                break;
            case 3:
                $this->redirectTo = '/delivery';
                break;
            default:
                $this->redirectTo = '/admins';
                break;
        }

        // Get System active Languages
        $languages = Language::where('status',LANGUAGE_ACTIVE)->get();

        // Save system languages in session for farther select
        Session::put('systemLanguages',$languages);

        // get user language symbol
        $userLanguage = Language::find(Auth::user()->language_id);

        // save user language in the session
        Session::put('userLanguage',$userLanguage);

        Session::put('userLanguageName',$userLanguage->name);


        // Save system languages in session for farther select
        Session::put('systemLanguages',$languages);

        //dd(Session::get('systemLanguages'));
        //dd("redirect()->intended(" . $this->redirectPath() .")");
        return $this->authenticated($request, $this->guard()->user())
            ?: redirect($this->redirectPath());//->intended($this->redirectPath());
    }

}
