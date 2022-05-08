<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session ;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use League\Fractal\Manager;
use App\Transformers\OrderTransformer;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Jobs\SendSMSMessages;
use App\Jobs\SendNotification;

use App\Category;
use App\Country;
use App\ServiceType;
use App\PaymentType;
use App\Order;
use App\Provider;
use App\Delivery;
use App\City;
use Illuminate\Http\Request;

use Validator;
use Carbon\Carbon;
use App;
class OrderController extends Controller
{

    /**
     * @var Manager
     */
    private $fractal;

    /**
     * @var UserTransformer
     */
    private $orderTransformer;

    protected $providerCancelableStatus = [ORDER_STATUS_NEW,ORDER_STATUS_DELIVERY_CANCELLED,ORDER_STATUS_DELIVERY_ASSIGNED,ORDER_STATUS_DELIVERY_ACCEPTED];

    protected $adminCancelableStatus = [ORDER_STATUS_NEW,ORDER_STATUS_DELIVERY_ASSIGNED,ORDER_STATUS_DELIVERY_ACCEPTED,ORDER_STATUS_DELIVERY_LOADING];

    protected $providerUpdatableOrderStatus = [ORDER_STATUS_NEW,ORDER_STATUS_DELIVERY_CANCELLED,ORDER_STATUS_DELIVERY_ASSIGNED,ORDER_STATUS_DELIVERY_ACCEPTED];

    protected $adminOrderAssignableStatus = [ORDER_STATUS_NEW,ORDER_STATUS_DELIVERY_ASSIGNED,ORDER_STATUS_DELIVERY_ACCEPTED , ORDER_STATUS_DELIVERY_CANCELLED];

    protected $adminReusableOrderStatus = [ORDER_STATUS_NEW,ORDER_STATUS_DELIVERY_ASSIGNED,ORDER_STATUS_DELIVERY_ACCEPTED];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Manager $fractal, OrderTransformer $orderTransformer)
    {
        $this->fractal = $fractal;
        $this->orderTransformer = $orderTransformer;

        // Get Order Statuses Localized
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        // Validate search parameters if exists
        $validator = Validator::make(
            $request->all(),
            [
                'provider' => 'sometimes|nullable|string|max:190',
                'client_name' => 'sometimes|nullable|string|max:190',
                'client_phone' => 'sometimes|nullable|digits_between:1,15',
                'delivery' => 'sometimes|nullable|string|max:190',
                'required_at_from' => 'sometimes|nullable|date_format:Y-m-d H:i:s',
                'required_at_to' => 'sometimes|nullable|date_format:Y-m-d H:i:s',
            ]
        );

        /*if($validator->fails()){
            //dd($validator->errors()->first());
            if(Request()->expectsJson()){
                return response() ->json(['success' => false ,'error' => $validator->errors()->first()] , 422);
            }


        }*/

        $Orders = Order::with([
            'provider' => function($q)  {
                $q->with('user');
            } ,
            'delivery' => function ($q) {
                $q->with('user');
            },
            'city' => function($q){
                $q->with([
                    'language' => function ($q) {
                        $q->where('language_id',Auth::user()->language_id);
                        $q->select('city_language.name');
                    }
                ]);
            },
            'category' => function($q) {
                $q->with([
                    'language' => function ($q) {
                        $q->where('language_id',Auth::user()->language_id);
                        $q->select('category_language.name');
                    }
                ]);
            },
            'main_service_type' => function($q) {
                $q->with([
                    'language' => function ($q) {
                        $q->where('language_id',Auth::user()->language_id);
                        $q->select('service_type_language.name');
                    }
                ]);
            },
            'payment_type' => function($q) {
                $q->with([
                    'language' => function ($q) {
                        $q->where('language_id',Auth::user()->language_id);
                        $q->select('payment_type_language.name');
                    }
                ]);
            },
            'extra_service_type' => function($q) {
                $q->with([
                    'language' => function ($query) {
                        $query->where('language_id',Auth::user()->language_id);
                        $query->select('service_type_language.name');
                    }
                ]);
            }
        ]);


        if($request->has('provider')){
            $providerName = $request->provider;
            $Orders = $Orders->whereHas('provider.user',function($q) use($providerName){
                $q->where('name' , 'LIKE' , '%' . $providerName . '%');
            });
        }

        if($request->has('delivery')){
            $deliveryName = $request->delivery;
            $Orders = $Orders->whereHas('delivery.user',function($q) use($deliveryName){
                $q->where('name' , 'LIKE' , '%' . $deliveryName . '%');
            });
        }

        if($request->has('client_name')){
            $Orders = $Orders->where('client_name', 'LIKE' ,'%' . e(trim($request->client_name)) . '%');
        }

        if($request->has('client_phone')){
            $Orders = $Orders->where('client_phone', 'LIKE' ,'%' . e(trim($request->client_phone)) . '%');
        }

        if($request->has('required_at_from')){
            $Orders = $Orders->where('required_at', '>=' ,$request->required_at_from );
        }

        if($request->has('required_at_to')){
            $Orders = $Orders->where('required_at', '<=' ,$request->required_at_to );
        }

        if($request->has('category_id')){
            $Orders = $Orders->whereIn('category_id', $request->category_id );
        }

        if($request->has('main_service_id')){
            $Orders = $Orders->whereIn('main_service_id', $request->main_service_id );
        }

        if($request->has('payment_type_id')){
            $Orders = $Orders->whereIn('payment_type_id', $request->payment_type_id );
        }

        if($request->has('extra_service_id')){
            $extraServices = $request->extra_service_id;
            $Orders = $Orders->whereHas('extra_service_type',function($q) use($extraServices){
                $q->whereIn('service_type_id' , $extraServices);
            });
        }

        $selectedStatuses = [];
        if($request->has('order_status')){
            $Orders = $Orders->whereIn('status',$request->order_status);
            $selectedStatuses = $request->order_status;
        }
        $selectedWithLocation = -1;
        if($request->has('order_location')){
            $Orders = $Orders->where('user_updated',$request->order_location);
            $selectedWithLocation = $request->order_location;
        }

        // Get orders depending on user login
        $loginType = Session::get('login_type');

        // if logged as admin get all
        if($loginType == PROVIDER){ // if logged as provider get only where the user is vendor
            $provider = Provider::where('user_id',Auth::user()->id)->first(['id']);
            $Orders = $Orders->where('provider_id',$provider->id);
        }elseif ($loginType == DRIVER){ // if logged as delivery show orders where the users is delivery
            $Delivery = Delivery::where('user_id',Auth::user()->id)->first(['id']);
            $Orders = $Orders->where('delivery_id',$Delivery->id);
        }

        //dd($Orders->get()->toArray());

        $outputView = ltrim(Route::current()->action['prefix'],'/') . ".orders.index";
        $editRoute = Route::current()->action['prefix'] . "/orders/";

        $Orders = $Orders->orderBy('created_at','desc')->paginate(10);

        if(Request()->expectsJson()){
            $OrdersOutput = $Orders;
            $OrdersOutput = new Collection($OrdersOutput->items(), $this->orderTransformer);
            $OrdersOutput->setPaginator(new IlluminatePaginatorAdapter($Orders));

            $OrdersOutput = $this->fractal->createData($OrdersOutput); // Transform data

            return response()->json(['status' => true , 'result' => $OrdersOutput->toArray() , 'recordsTotal' => $Orders->total() , 'recordsFiltered' => $Orders->total() , 'draw' => Request()->input('draw') , 'editRoute' => $editRoute]);
        }

        // Get System active Categories
        $categories = Category::where('status',CATEGORY_ACTIVE)->get();

        // Get Categories Translated
        $categories = $this->localizeSytemActiveCategories($categories);

        // Get system active Main Services
        $servicesTypes = ServiceType::where('status',SERVICE_TYPE_ACTIVE)->get();

        // Get services types translation and prices for user language and country
        $servicesTypes = $this->localizeServiceTypes($servicesTypes);

        // Get System active payment Types
        $paymentTypes = PaymentType::where('status',PAYMENT_TYPE_ACTIVE)->get();

        // Get payment type prices and localization
        $paymentTypes = $this->localizePaymentTypes($paymentTypes);

        return view($outputView)
            ->with(
                [
                    'categories' => $categories,
                    'services' => $servicesTypes,
                    'paymentTypes' => $paymentTypes,
                    'editRoute' => $editRoute,
                    'orderStatuses' => $this->orderStatuses,
                    'selectedStatuses' => $selectedStatuses,
                    'selectedWithLocation' => $selectedWithLocation
                ]
            );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Check if the provider is active and can do orders
        /* to be Done */

        // Get user country data
        $country = Country::find(Auth::user()->country_id);

        // Get System active Categories
        $categories = Category::where('status',CATEGORY_ACTIVE)->get();

        // Get Categories Translated
        $categories = $this->localizeSytemActiveCategories($categories);

        // Get System active Cities
        $cities = City::where('status',CITY_ACTIVE)->where('country_id',Auth::user()->country_id)->get();

        // Get Categories Translated
        $cities = $this->localizeSystemActiveCities($cities);

        // Get system active Main Services
        $servicesTypes = ServiceType::where('status',SERVICE_TYPE_ACTIVE)->get();

        // Get services types translation and prices for user language and country
        $servicesTypes = $this->localizeServiceTypes($servicesTypes);

        // Get System active payment Types
        $paymentTypes = PaymentType::where('status',PAYMENT_TYPE_ACTIVE)->get();

        // Get payment type prices and localization
        $paymentTypes = $this->localizePaymentTypes($paymentTypes);

        return view('provider.orders.create')
            ->with([
                'categories' => $categories,
                'services' => $servicesTypes,
                'paymentTypes' => $paymentTypes,
                'country' => $country,
                'cities' => $cities
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

        // Get user Country data
        $country = Country::find(Auth::user()->country_id);

        // Get this user provider id
        $provider = Provider::with('payment_type_discounts')
            ->where('status',PROVIDER_ACTIVE)
            ->where('user_id',Auth::user()->id)
            ->first(['id' , 'user_id']);


        // validate order parameters
        $data = $request->all();
        $data['owner_provider'] = $provider ? $provider->id : null;
        $data['country_id'] = Auth::user()->country_id;
        $validator = Validator::make(
            $data,
            [
                'owner_provider' => 'required|integer',
                'client_name' => 'required|string|max:190',
                'client_phone' => 'required|digits_between:8,15',
                'client_address' => 'required|string',
                'category_id' => ['required','integer',Rule::exists('categories' , 'id')->where(function ($query){
                    $query->where('status', CATEGORY_ACTIVE);
                })],
                'city_id' => ['required','integer',Rule::exists('cities' , 'id')->where(function ($query) use($data) {
                    $query->where('status', CITY_ACTIVE);
                    $query->where('country_id',$data['country_id']);
                })],
                'required_at' => 'required|date_format:Y-m-d H:i:s|after:' . Carbon::now($country->time_zone)->toDateTimeString(),
                'main_service_id' => ['required','integer',Rule::exists('service_types' , 'id')->where(function ($query) {
                    $query->where('status', SERVICE_TYPE_ACTIVE);
                    $query->where('type',MAIN_SERVICE_TYPE);
                })],
                'extra_service_id' => 'array',
                'extra_service_id.*' => ['integer','distinct',Rule::exists('service_types' , 'id')->where(function ($query) {
                    $query->where('status', SERVICE_TYPE_ACTIVE);
                    $query->where('type',EXTRA_SERVICE_TYPE);
                })],
                'payment_type_id' => ['required','integer',Rule::exists('payment_types' , 'id')->where(function ($query) {
                    $query->where('status', PAYMENT_TYPE_ACTIVE);
                })],
                'order_price' => 'required|numeric',
                'details' => 'required|string',
                'order_lat' => 'nullable|numeric',
                'order_long' => 'nullable|numeric'
            ]
        )->validate();



        // Convert date from user Time Zone to UTC
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $request->required_at, $country->time_zone);
        $date->setTimezone('UTC');

        // Get payment type cost
        $selectedPaymentTypeInfo = PaymentType::with([
            'country' => function ($q) use ($country) {
                $q->where('country_id',$country->id);
                $q->select('payment_types_prices.price');
            },
            'provider_discount' => function($q) use($provider) {
                $q->where('provider_id',$provider->id);
                $q->select('provider_payment_discount.discount');

            }
        ])->where('id',$request->payment_type_id)->first();

        // Get main service cost at this time
        $selectedMainServiseTypeInfo = ServiceType::with(
            [
                'country'=> function ($q) use ($country) {
                    $q->where('country_id',$country->id);
                    $q->select('services_types_price.price');
                },
                'provider_discount' => function($q) use($provider){
                    $q->where('provider_id',$provider->id);
                    $q->select('provider_service_discount.discount');
                }
            ]
        )->where('id',$request->main_service_id)->where('type' , MAIN_SERVICE_TYPE)->first();


        // Get applied discount on order
        $totalDiscount = 0;

        // Get extra services prices
        $totalExtraServicesCost = 0;

        // Get extra service total discounts
        $totalExtraServicesDiscount = 0;

        if($request->has('extra_service_id'))
            $selectedExtraServiseTypeInfo = ServiceType::with(
                [
                    'country'=> function ($q) use ($country) {
                        $q->where('country_id',$country->id);
                        $q->select('services_types_price.price');
                    },
                    'provider_discount' => function($q) use($provider){
                        $q->where('provider_id',$provider->id);
                        $q->select('provider_service_discount.discount');
                    }
            ])->whereIn('id',$request->extra_service_id)->where('type' , EXTRA_SERVICE_TYPE)->get();
        else
            $selectedExtraServiseTypeInfo = [];

        // save extra services prices To be stored in many to manr relation
        $extraServices = [];

        if($selectedExtraServiseTypeInfo) {
            foreach($selectedExtraServiseTypeInfo as $extraService) {
                $extraServices[$extraService->id]['price'] = $extraService->country->first() ? $extraService->country->first()->price : 0;
                $extraServices[$extraService->id]['discount'] = 0;

                if($extraService->provider_discount->first() && $extraServices[$extraService->id]['price'] > 0)
                    $extraServices[$extraService->id]['discount'] =  ($extraService->provider_discount->first()->discount *  $extraServices[$extraService->id]['price']) / 100;

                $totalExtraServicesCost += $extraServices[$extraService->id]['price'];
                $totalExtraServicesDiscount += $extraServices[$extraService->id]['discount'];
            }
        }

        $totalDiscount += $totalExtraServicesDiscount;


        DB::beginTransaction();
            $order = new Order();

            // this user will be the provider for the Order
            $order->provider_id = $provider->id;
            $order->required_at = $date;

            // Client Data
            $order->client_name = e(trim($request->client_name));

            $providerCountryCode = Country::where('id' , User::find($provider->user_id)->country_id)->first(['code']);
            $order->client_phone = $providerCountryCode->code . e(ltrim($request->client_phone,"0"));
            $order->client_address = e(trim($request->client_address));

            if($request->has('order_lat'))
                $order->order_lat = trim($request->order_lat);
            if($request->has('order_long'))
                $order->order_long = trim($request->order_long);

            $order->details = e($request->details);

            $order->price = $request->order_price;

            $order->category_id = $request->category_id;

            $order->city_id = $request->city_id;

            $order->main_service_type_id = $request->main_service_id;

            $order->main_service_type_cost = $selectedMainServiseTypeInfo->country->first() ? floatval($selectedMainServiseTypeInfo->country->first()->price) : 0;

            if($selectedMainServiseTypeInfo->provider_discount->first() && $order->main_service_type_cost > 0){
                $order->main_service_type_discount = (floatval($selectedMainServiseTypeInfo->provider_discount->first()->discount) * floatval($order->main_service_type_cost)) / 100;
                $totalDiscount += $order->main_service_type_discount;
            }

            $order->extra_service_type_cost = $totalExtraServicesCost;

            $order->extra_services_type_discount = $totalExtraServicesDiscount;

            $order->payment_type_id = $request->payment_type_id;

            $order->payment_type_discount = 0;

            if($selectedPaymentTypeInfo->country->first())
                $order->payment_type_cost = $selectedPaymentTypeInfo->country->first()->price;
            else
                $order->payment_type_cost = 0;

            // if provider has payment type discount save it
            if($selectedPaymentTypeInfo->provider_discount->first() && $order->payment_type_cost > 0){
                $order->payment_type_discount = ($selectedPaymentTypeInfo->provider_discount->first()->discount * floatval($order->payment_type_cost)) / 100;
                $totalDiscount += $order->payment_type_discount;
            }

            $order->total_cost = $order->payment_type_cost + $order->extra_service_type_cost + $order->main_service_type_cost;
            $order->total_discount = $totalDiscount;

            $order->status = ORDER_STATUS_NEW;

            // Generate user verification code
            $order->user_verification = str_random(8);

            if($order->save()){
                // Attach extra services

                $order->extra_service_type()->attach($extraServices);

                DB::commit();

                $msg = __("general.newOrderCreted");
                /*$this->sendNotificationsToUser($msg ,0 , true ,"/admin/orders/" . $order->id);

                $this->sendSms($order->client_phone, __("general.pleaseReviewYourOrder") . "\n" . url('/user/order/' . $order->id . "/" . $order->user_verification));*/

                dispatch(new SendSMSMessages($order->client_phone , __("general.pleaseReviewYourOrder") . "\n" . url('/user/order/' . $order->id . "/" . $order->user_verification)));

                dispatch(new SendNotification($msg ,0 , true ,url("/admin/orders/" . $order->id)));

                if(Request()->expectsJson()){
                    return response()->json(['status' => true , 'order_id' => $order->id] , 201);
                }

                return redirect('/provider/orders')->with(['messageSuccess' => __("general.Order created successfully")]);
            }else{
                if(Request()->expectsJson()){
                    return response()->json(['status' => false ] , 422);
                }
                return redirect('/provider/orders')->with(['messageDander' => __("general.problem_create_order_failed")]);
            }


        //dd($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Set Validation upon user login type
        $loginType = Session::get('login_type');
//dd($loginType);
        if($loginType == PROVIDER){
            $provider = Provider::where('user_id',Auth::user()->id)->first(['id']);
            $roles = [
                'selected_order_id' => 'required|integer|exists:orders,id,provider_id,' . $provider->id,
            ];
        }elseif ($loginType == DRIVER){
            $delivery = Delivery::where('user_id',Auth::user()->id)->first(['id']);
            $roles = [
                'selected_order_id' => 'required|integer|exists:orders,id,delivery_id,' . $delivery->id,
            ];
        }else{
            $roles = [
                'selected_order_id' => 'required|integer|exists:orders,id',
            ];
        }

        $validator = Validator::make(
            ['selected_order_id' => $id],
            $roles
        )->validate();

        // Get the Order
        $order = Order::with([
            'provider' => function($q) { $q->with('user');} ,
            'delivery' => function($q) { $q->with('user');} ,
            'city' => function($q){
                $q->with([
                    'language' => function ($q) {
                        $q->where('language_id',Auth::user()->language_id);
                        $q->select('city_language.name');
                    }
                ]);
            },
            'category' => function($q) {
                    $q->with([
                        'language' => function ($q) {
                            $q->where('language_id',Auth::user()->language_id);
                            $q->select('category_language.name');
                        }
                    ]);
                },
            'main_service_type' => function($q) {
                $q->with([
                    'language' => function ($q) {
                        $q->where('language_id',Auth::user()->language_id);
                        $q->select('service_type_language.name');
                    }
                ]);
            },
            'payment_type' => function($q) {
                $q->with([
                    'language' => function ($q) {
                        $q->where('language_id',Auth::user()->language_id);
                        $q->select('payment_type_language.name');
                    }
                ]);
            },
            'extra_service_type' => function($q) {
                $q->with([
                    'language' => function ($query) {
                        $query->where('language_id',Auth::user()->language_id);
                        $query->select('service_type_language.name');
                    }
                ]);
            }
            ])->where('id',$id)->first();

        if(Request()->expectsJson()){

            $order = new Item($order, $this->orderTransformer);


            $order = $this->fractal->createData($order); // Transform data

            return response() ->json(['status' => true , 'result' => $order->toArray()]);
        }

        if($order->category->language[0]->name)
            $order->category->name = $order->category->language[0]->name;

        if($order->city->language[0]->name)
            $order->city->name = $order->city->language[0]->name;


        if($order->main_service_type->language[0]->name)
            $order->main_service_type->name = $order->main_service_type->language[0]->name;

        if($order->payment_type->language[0]->name)
            $order->payment_type->name = $order->payment_type->language[0]->name;

        foreach ($order->extra_service_type as $extraService){
            if($extraService->language[0]->name)
                $extraService->name = $extraService->language[0]->name;
        }


        // generate order time line
        $order->time_line = $this->GenerateOrderTimeLine($order);

        // Get provider country currency
        $order->currency = $order->provider->user->country->currency_symbol;

        // Get delivery possible next step
        $order->possible_steps = $this->getDeliveryPossibleOrderStatus($order->status);

        // check if provider can cancel this order
        $order->provider_can_cancel = in_array($order->status,$this->providerCancelableStatus) ? true : false;

        // Check if admin can refuse order
        $order->admin_can_cancel = in_array($order->status,$this->adminCancelableStatus) ? true : false;

        // check if the user is admin and order status is good to be assigned
        if($loginType == ADMIN && in_array($order->status, $this->adminOrderAssignableStatus ) && $order->user_updated == USER_UPDATED_ORDER && $order->order_lat && $order->order_long){
            // Array to hold available delivery drivers
            $deliveries = Delivery::with('user')
                ->where('status',DELIVERY_ACTIVE)
                ->where('available',DELIVERY_AVAILABLE)
                ->whereHas('user',function($q) use($order){
                    $q->where('city_id' , $order->city_id);
                })
                ->get();
        }else{
            $deliveries = __("general.Cannot assign delivery for this order");
        }

//        dd($order->toArray());

        $outputView = ltrim(Route::current()->action['prefix'],'/') . ".orders.show";

        return view($outputView)
            ->with(
                [
                    'order' => $order,
                    'availableDelivery' => $deliveries
                ]
            );
    }

    /**
     * Get Order Time line for show
     *
     * @param  int  $order
     * @return array
     */

    protected function GenerateOrderTimeLine($order){
        $timeLine = [
            'new_order' => [
                'name' => __("general.Provider") . " - " . $order->provider->user->name . " - " . __("general.createdTheOrder") ,
                'icon' => 'icon-flag',
                'time' => $order->created_at,
                'color' => 'success',
            ],
            'provider_cancel' => [
                'name' => __("general.Provider") . " - " . $order->provider->user->name . " - " . __("general.cancelledTheOrder") ,
                'icon' => 'icon-like',
                'time' => $order->updated_at,
                'color' => 'danger',
            ],
            'delivery_cancel' => [
                'name' => __("general.waitingToBeAssigned") ,
                'icon' => 'icon-like',
                'time' => $order->updated_at,
                'color' => 'warning',
            ],
            'user_refuse' => [
                'name' => __("general.ORDER_STATUS_DELIVERY_USER_REFUSE") ,
                'icon' => 'icon-like',
                'time' => $order->updated_at,
                'color' => 'danger',
            ],
            'admin_refuse' => [
                'name' => __("general.ORDER_STATUS_ADMIN_REFUSE") ,
                'icon' => 'icon-like',
                'time' => $order->updated_at,
                'color' => 'danger',
            ]
        ];

        if($order->delivery){
            $timeLine['delivery_assigned'] = [
                'name' => __("general.orderAssignedToDelivery") . " - " . $order->delivery->user->name  ,
                'icon' => 'icon-like',
                'time' => $order->assigned_at,
                'color' => 'primary',
            ];

            $timeLine['delivery_accept'] = [
                'name' => __("general.Delivery") . " - " . $order->delivery->user->name . " - " . __("general.acceptedOrder")  ,
                'icon' => 'icon-like',
                'time' => $order->updated_at,
                'color' => 'primary',
            ];

            $timeLine['delivery_loading'] = [
                'name' => __("general.Delivery") . " - " . $order->delivery->user->name . " - " . __("general.loadingOrder")  ,
                'icon' => 'icon-like',
                'time' => $order->loading_at,
                'color' => 'primary',
            ];

            $timeLine['delivery_confirm'] = [
                'name' => __("general.Delivery") . " - " . $order->delivery->user->name . " - " . __("general.confirmedOrder")  ,
                'icon' => 'icon-like',
                'time' => $order->delivered_at,
                'color' => 'primary',
            ];

        }

        $orderTimeLine = [];

        switch($order->status){
            case 0:
                $orderTimeLine = [$timeLine['new_order'],$timeLine['delivery_cancel']];
                break;
            case 1:
                $orderTimeLine = [$timeLine['new_order'] , $timeLine['provider_cancel']];
                break;
            case 2:
                $orderTimeLine = [$timeLine['new_order'] , $timeLine['delivery_cancel']];
                break;
            case 3:
                $orderTimeLine = [$timeLine['new_order'] , $timeLine['delivery_assigned']];
                break;
            case 4:
                $orderTimeLine = [$timeLine['new_order'] , $timeLine['delivery_assigned'] , $timeLine['delivery_accept']];
                break;
            case 5:
                $orderTimeLine = [$timeLine['new_order'] , $timeLine['delivery_assigned'] , $timeLine['delivery_accept'] , $timeLine['delivery_loading']];
                break;
            case 6:
                $orderTimeLine = [$timeLine['new_order'] , $timeLine['delivery_assigned'] , $timeLine['delivery_accept'] , $timeLine['delivery_loading'] , $timeLine['delivery_confirm']];
                break;
            case 7:
                $orderTimeLine = [$timeLine['new_order'] , $timeLine['delivery_assigned'] , $timeLine['delivery_accept'] , $timeLine['delivery_loading'] , $timeLine['user_refuse']];
                break;
            case 8:
                $orderTimeLine = [$timeLine['new_order'] , $timeLine['admin_refuse']];
                break;
        }

        return $orderTimeLine;
    }


    protected function getOrderData($order_id){
        $order = Order::with([
            'provider' => function($q) {
                $q->with('user');
            },
            'delivery' =>function($q) {
                $q->with('user');
            },
            'main_service_type' => function($q) {
                $q->with(['language' => function($localQuery) {
                    $localQuery->where('language_id',Auth::user()->language_id)->select('service_type_language.name');
                }]);
            },
            'extra_service_type' => function($q) {
                $q->with(['language' => function($localQuery) {
                    $localQuery->where('language_id',Auth::user()->language_id)->select('service_type_language.name');
                }]);
            },
            'payment_type' => function($q) {
                $q->with(['language' => function($localQuery) {
                    $localQuery->where('language_id',Auth::user()->language_id)->select('payment_type_language.price');
                }]);
            }
        ])->first();

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // Get this user provider
        $provider = Provider::where('user_id',Auth::user()->id)->first(['id']);

        $validator = Validator::make(
            ['selected_order_id' => $id],
            [
                'selected_order_id' => 'required|integer|exists:orders,id,provider_id,' . $provider->id,

            ]
        )->validate();

        // extra validation roles
        $extraValidationErrors = [];

        $order = Order::where('id',$id)
            ->with(['extra_service_type'])
            ->whereIn('status',$this->providerUpdatableOrderStatus)->first();

        if($order){
            // Get order's extra servise indexed by service id to query on view
            $order_extra_service_type = $order->extra_service_type->pluck('id')->toArray();

            // Get user country data
            $country = Country::find(Auth::user()->country_id);

            // parse client phone without country code
            $order->client_phone = preg_replace("/" . $country->code . "/","",$order->client_phone,1);

            // Get System active Categories
            $categories = Category::where('status',CATEGORY_ACTIVE)->get();

            // Get Categories Translated
            $categories = $this->localizeSytemActiveCategories($categories);

            // Get System active Cities
            $cities = City::where('status',CITY_ACTIVE)->where('country_id',Auth::user()->country_id)->get();

            // Get Categories Translated
            $cities = $this->localizeSystemActiveCities($cities);

            // Get system active Main Services
            $servicesTypes = ServiceType::where('status',SERVICE_TYPE_ACTIVE)->get();

            // Get services types translation and prices for user language and country
            $servicesTypes = $this->localizeServiceTypes($servicesTypes);

            // Get System active payment Types
            $paymentTypes = PaymentType::where('status',PAYMENT_TYPE_ACTIVE)->get();

            // Get payment type prices and localization
            $paymentTypes = $this->localizePaymentTypes($paymentTypes);

            return view('provider.orders.edit')
                ->with([
                    'categories' => $categories,
                    'services' => $servicesTypes,
                    'paymentTypes' => $paymentTypes,
                    'country' => $country,
                    'orderExtraService' => $order_extra_service_type,
                    'order' => $order,
                    'cities' => $cities
                ]);

//            dd($order->toArray());
        }else{
            return redirect()->back()->with(['messageDanger' => __("general.you cannot update this order")]);
        }

    }


    /*
     * Cancel the order by provider
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function providerCancelOrder(Request $request){
        // Get this user provider
        $provider = Provider::where('user_id',Auth::user()->id)->first(['id']);

        $validator = Validator::make(
            $request->all(),
            [
                'order_id' => 'required|integer|exists:orders,id,provider_id,' . $provider->id,
            ]
        )->validate();

        $order = Order::where('id',$request->order_id)
            ->whereIn('status',$this->providerCancelableStatus)->first();

        if($order){
            $order->status = ORDER_STATUS_PROVIDER_CANCELLED;

            if($order->save()){
                $msg = __("general.orderCanceledByProvider");

                $toDelivery = null;
                if($order->delivery_id)
                    $toDelivery = Delivery::where('id',$order->delivery_id)->first(['user_id']);

                if($toDelivery)
                    $toDelivery = $toDelivery->user_id;
                else
                    $toDelivery = 0;
                //dd($toDelivery);
                //$this->sendNotificationsToUser($msg ,$toDelivery , true );
                dispatch(new SendNotification($msg ,$toDelivery , true ));

                return redirect('/provider/orders')->with(['messageSuccess' => __("general.Order Cancelled")]);
            }else{
                return redirect()->back()->with(['messageDanger' => __("general.Cannot Cancel this Order Now Try again Later")]);
            }
        }else{
            return redirect()->back()->with(['messageDanger' => __("general.Cannot Cancel this Order")]);
        }
    }

    /*
     * Assign Delivery to order by admin
     * @param  int  $selected_order_id_to_assign
     * @param int $delivery_id
     * @return \Illuminate\Http\Response
     */
    public function adminAssignDeliveryToOrder(Request $request){
        $validator = Validator::make(
            $request->all(),
            [
                'selected_order_id_to_assign' => 'required|integer|exists:orders,id',
                'delivery_id' => 'required|integer|exists:deliverers,id'
            ]
        )->validate();

        $order = Order::where('id',$request->selected_order_id_to_assign)
            ->whereIn('status',$this->adminOrderAssignableStatus)
            ->first();

       // dd($order->toArray());
        $delivery = Delivery::with('user')
            ->where('id',$request->delivery_id)
            ->where('status',DELIVERY_ACTIVE)
            ->where('available',DELIVERY_AVAILABLE)
            ->first();

        if(!$delivery){
            return redirect()->back()->with(['messageDanger' => __("general.Delivery not available")]);
        }

        if($order){
            $order->status = ORDER_STATUS_DELIVERY_ASSIGNED;

            $order->delivery_id = $delivery->id;

            $order->assigned_at = Carbon::now()->toDateTimeString();

            if($order->save()){


                $msgDelivery = __("general.youHadBeenAssignedAnOrder");
                //$this->sendNotificationsToUser($msgDelivery ,$delivery->user_id ,false , "/delivery/orders/" . $order->id);
                //$this->sendSms($delivery->user->phone,__("general.youHadBeenAssignedAnOrder") . "\n" . url("/delivery/orders/" . $order->id));
                dispatch(new SendSMSMessages($delivery->user->phone,__("general.youHadBeenAssignedAnOrder") . "\n" . url('/user/order/' . $order->id . "/" . $order->user_verification . "/" . $order->delivery_id)));
                dispatch(new SendNotification($msgDelivery ,$delivery->user_id , false ,url("/delivery/orders/" . $order->id)));

                $toProvider = Provider::where('id',$order->provider_id)->first(['user_id']);

                $msgProvider = __("general.youOrderIsAssignedtToDelivery");
                //$this->sendNotificationsToUser($msgProvider ,$toProvider->user_id , false , "/provider/orders/" . $order->id);
                dispatch(new SendNotification($msgProvider ,$toProvider->user_id , false ,url("/provider/orders/" . $order->id)));

                return redirect('/admin/orders')->with(['messageSuccess' => __("general.Delivery assigned to order")]);
            }else{
                    return redirect()->back()->with(['messageDanger' => __("general.Cannot assign this Order Now Try again Later")]);
            }
        }else{
            return redirect()->back()->with(['messageDanger' => __("general.Cannot assign this Order")]);
        }
    }
    /*
    * Cancel the order by provider
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function adminRefuseOrder(Request $request){
        // Get this user provider
        //$provider = Provider::where('user_id',Auth::user()->id)->first(['id']);

        $validator = Validator::make(
            $request->all(),
            [
                'selected_order_id' => 'required|integer|exists:orders,id',
            ]
        )->validate();

        $order = Order::where('id',$request->selected_order_id)
            ->whereIn('status',$this->adminReusableOrderStatus)->first();

        //dd($order->toArray());
        if($order){
            $order->status = ORDER_STATUS_ADMIN_REFUSE;

            if($order->save()){
                $toProvider = Provider::where('id',$order->provider_id)->first(['user_id']);

                $msgProvider = __("general.youOrderRefused");

                //$this->sendNotificationsToUser($msgProvider ,$toProvider->user_id ,false , "/provider/orders/" . $order->id);
                dispatch(new SendNotification($msgProvider ,$toProvider->user_id , false ,url("/provider/orders/" . $order->id)));

                if($order->delivery_id){
                    $toDelivery = Delivery::where('id',$order->delivery_id)->first(['user_id']);

                    $msgDelivery = __("general.OrderRefused");

                    //$this->sendNotificationsToUser($msgDelivery ,$toDelivery->user_id , false , "/delivery/orders/" . $order->id);
                    dispatch(new SendNotification($msgDelivery ,$toDelivery->user_id , false ,url("/delivery/orders/" . $order->id)));
                }
                return redirect('/admin/orders')->with(['messageSuccess' => __("general.Order Refused")]);
            }else{
                return redirect()->back()->with(['messageDanger' => __("general.Cannot Refuse this Order Now Try again Later")]);
            }
        }else{
            return redirect()->back()->with(['messageDanger' => __("general.Cannot Refuse this Order")]);
        }
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
//dd($request->all());
        $provider = Provider::where('user_id',Auth::user()->id)->first(['id','user_id']);

        // Get user Country data
        $country = Country::find(Auth::user()->country_id);

        $order = Order::where('id',$id)
            ->whereIn('status',$this->providerUpdatableOrderStatus)->first();

        $validator = Validator::make(
            $request->all(),
            [
                'order_id' => 'required|integer|exists:orders,id,provider_id,' . $provider->id . '|in:' . $id,
                'client_name' => 'required|string|max:190',
                'client_phone' => 'required|digits_between:8,15',
                'client_address' => 'required|string',
                'category_id' => ['required','integer',Rule::exists('categories' , 'id')->where(function ($query){
                    $query->where('status', CATEGORY_ACTIVE);
                })],
                'city_id' => ['required','integer',Rule::exists('cities' , 'id')->where(function ($query) use($country) {
                    $query->where('status', CITY_ACTIVE);
                    $query->where('country_id',$country->id);
                })],
                'required_at' => 'required|date_format:Y-m-d H:i:s|after:' . Carbon::createFromFormat('Y-m-d H:i:s', $order->created_at, $country->time_zone),
                'main_service_id' => ['integer',Rule::exists('service_types' , 'id')->where(function ($query) {
                    $query->where('status', SERVICE_TYPE_ACTIVE);
                    $query->where('type',MAIN_SERVICE_TYPE);
                })],
                'extra_service_id' => 'array',
                'extra_service_id.*' => ['integer','distinct',Rule::exists('service_types' , 'id')->where(function ($query) {
                    $query->where('status', SERVICE_TYPE_ACTIVE);
                    $query->where('type',EXTRA_SERVICE_TYPE);
                })],
                'payment_type_id' => ['required','integer',Rule::exists('payment_types' , 'id')->where(function ($query) {
                    $query->where('status', PAYMENT_TYPE_ACTIVE);
                })],
                'order_price' => 'required|numeric',
                'details' => 'required|string',
                'order_lat' => 'nullable|numeric',
                'order_long' => 'nullable|numeric'
            ]
        )->validate();



        // Get payment type cost
        $selectedPaymentTypeInfo = PaymentType::with([
            'country' => function ($q) use ($country) {
                $q->where('country_id',$country->id);
                $q->select('payment_types_prices.price');
            },
            'provider_discount' => function($q) use($provider) {
                $q->where('provider_id',$provider->id);
                $q->select('provider_payment_discount.discount');

            }
        ])->where('id',$request->payment_type_id)->first();

        // Get main service cost at this time
        $selectedMainServiseTypeInfo = ServiceType::with(
            [
                'country'=> function ($q) use ($country) {
                    $q->where('country_id',$country->id);
                    $q->select('services_types_price.price');
                },
                'provider_discount' => function($q) use($provider){
                    $q->where('provider_id',$provider->id);
                    $q->select('provider_service_discount.discount');
                }
            ])->where('id',$request->main_service_id)->first();

        // Get applied discount on order
        $totalDiscount = 0;

        // Get extra services prices
        $totalExtraServicesCost = 0;

        // Get extra service total discounts
        $totalExtraServicesDiscount = 0;

        if($request->has('extra_service_id'))
            $selectedExtraServiseTypeInfo = ServiceType::with(
                [
                    'country'=> function ($q) use ($country) {
                        $q->where('country_id',$country->id);
                        $q->select('services_types_price.price');
                    },
                    'provider_discount' => function($q) use($provider){
                        $q->where('provider_id',$provider->id);
                        $q->select('provider_service_discount.discount');
                    }
                ])->whereIn('id',$request->extra_service_id)->where('type' , EXTRA_SERVICE_TYPE)->get();
        else
            $selectedExtraServiseTypeInfo = [];

        // save extra services prices To be stored in many to manr relation
        $extraServices = [];

        if($selectedExtraServiseTypeInfo) {
            foreach($selectedExtraServiseTypeInfo as $extraService) {
                $extraServices[$extraService->id]['price'] = $extraService->country->first() ? $extraService->country->first()->price : 0;
                $extraServices[$extraService->id]['discount'] = 0;

                if($extraService->provider_discount->first() && $extraServices[$extraService->id]['price'] > 0)
                    $extraServices[$extraService->id]['discount'] =  ($extraService->provider_discount->first()->discount *  $extraServices[$extraService->id]['price']) / 100;

                $totalExtraServicesCost += $extraServices[$extraService->id]['price'];
                $totalExtraServicesDiscount += $extraServices[$extraService->id]['discount'];
            }
        }

        $totalDiscount += $totalExtraServicesDiscount;

        // Convert date from user Time Zone to UTC
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $request->required_at, $country->time_zone);
        $date->setTimezone('UTC');

        DB::beginTransaction();


        if($order){
            $order->required_at = $date;

            // Client Data
            $order->client_name = e(trim($request->client_name));

            $providerCountryCode = Country::where('id' , User::find($provider->user_id)->country_id)->first(['code']);
            $order->client_phone = $providerCountryCode->code . e(ltrim($request->client_phone,"0"));

            $order->client_phone = e(trim($request->client_phone));
            $order->client_address = e($request->client_address);
            $order->city_id = intval($request->city_id);

            if($request->has('order_lat'))
                $order->order_lat = trim($request->order_lat);
            if($request->has('order_long'))
                $order->order_long = trim($request->order_long);

            $order->details = e($request->details);

            $order->price = floatval($request->order_price);

            $order->category_id = intval($request->category_id);

            $order->main_service_type_id = $request->main_service_id;

            $order->main_service_type_cost = $selectedMainServiseTypeInfo->country->first() ? floatval($selectedMainServiseTypeInfo->country->first()->price) : 0;

            if($selectedMainServiseTypeInfo->provider_discount->first() && $order->main_service_type_cost > 0){
                $order->main_service_type_discount = (floatval($selectedMainServiseTypeInfo->provider_discount->first()->discount) * floatval($order->main_service_type_cost)) / 100;
                $totalDiscount += $order->main_service_type_discount;
            }

            $order->extra_service_type_cost = $totalExtraServicesCost;

            $order->extra_services_type_discount = $totalExtraServicesDiscount;

            $order->payment_type_id = $request->payment_type_id;

            $order->payment_type_discount = 0;

            if($selectedPaymentTypeInfo->country->first())
                $order->payment_type_cost = $selectedPaymentTypeInfo->country->first()->price;
            else
                $order->payment_type_cost = 0;

            // if provider has payment type discount save it
            if($selectedPaymentTypeInfo->provider_discount->first() && $order->payment_type_cost > 0){
                $order->payment_type_discount = ($selectedPaymentTypeInfo->provider_discount->first()->discount * floatval($order->payment_type_cost)) / 100;
                $totalDiscount += $order->payment_type_discount;
            }

            $order->total_cost = $order->payment_type_cost + $order->extra_service_type_cost + $order->main_service_type_cost;
            $order->total_discount = $totalDiscount;

            if($order->save()){
                // Delete old extra services
                $order->extra_service_type()->detach();

                // Attach extra services
                $order->extra_service_type()->attach($extraServices);

                DB::commit();

                if(Request()->expectsJson()){
                    return response()->json(['status' => true , 'order_id' => $order->id] , 201);
                }
                return redirect('/provider/orders')->with(['messageSuccess' => __("general.Order updated successfully")]);
            }else{
                DB::rollBack();
                if(Request()->expectsJson()){
                    return response()->json(['status' => false , 'order_id' => $order->id] , 422);
                }
                return redirect()->back()->with(['messageDanger' => __("general.Cannot Update the selected order , Try again later")]);
            }
        }else{
            DB::rollBack();
            if(Request()->expectsJson()){
                return response()->json(['status' => true , 'false' => __("general.Cannot Update the selected order")] , 422);
            }
            return redirect('/provider/orders')->with(['messageDanger' => __("general.Cannot Update the selected order")]);
        }

        dd($request->all());

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
        // Get this user provider
        $provider = Provider::where('user_id',Auth::user()->id)->first(['id']);

        $validator = Validator::make(
            ['order_id' => $id],
            [
                'order_id' => 'required|integer|exists:orders,id,provider_id,' . $provider->id,
            ]
        )->validate();

        $order = Order::where('id',$id)
            ->whereIn('status',$this->providerCancelableStatus)->first();

        if($order){
            $order->status = ORDER_STATUS_PROVIDER_CANCELLED;

            if($order->save()){
                $msg = __("general.orderCanceledByProvider");

                $toDelivery = null;
                if($order->delivery_id)
                    $toDelivery = Delivery::where('id',$order->delivery_id)->first(['user_id']);

                if($toDelivery)
                    $toDelivery = $toDelivery->user_id;
                else
                    $toDelivery = 0;
                //dd($toDelivery);
                //$this->sendNotificationsToUser($msg ,$toDelivery , true );
                dispatch(new SendNotification($msg ,$toDelivery , false ,url("/delivery/orders/" . $order->id)));

                if(Request()->expectsJson()){
                    return response()->json(['status' => true , 'order_id' => $order->id] , 200);
                }
                return redirect('/provider/orders')->with(['messageSuccess' => __("general.Order Cancelled")]);
            }else{
                if(Request()->expectsJson()){
                    return response()->json(['status' => false , 'error' => __("general.Cannot Cancel this Order Now Try again Later")] , 422);
                }
                return redirect()->back()->with(['messageDanger' => __("general.Cannot Cancel this Order Now Try again Later")]);
            }
        }else{
            if(Request()->expectsJson()){
                return response()->json(['status' => false , 'error' => __("general.Cannot Cancel this Order")] , 422);
            }
            return redirect()->back()->with(['messageDanger' => __("general.Cannot Cancel this Order")]);
        }

    }

    /*
     * Delivery possible next step
     * @param  int  $currentOrderStatus
     * @return array of possible order status
     */

    protected function getDeliveryPossibleOrderStatus($currentOrderStatus){
        $possibleDeliveryStatus = [];
        switch ($currentOrderStatus){
            case ORDER_STATUS_NEW:

                break;
            case ORDER_STATUS_PROVIDER_CANCELLED :
                break;
            case ORDER_STATUS_DELIVERY_CANCELLED :
                break;
            case ORDER_STATUS_DELIVERY_ASSIGNED:
                $possibleDeliveryStatus = [ORDER_STATUS_DELIVERY_CANCELLED , ORDER_STATUS_DELIVERY_ACCEPTED];
                break;
            case ORDER_STATUS_DELIVERY_ACCEPTED :
                $possibleDeliveryStatus = [ORDER_STATUS_DELIVERY_CANCELLED , ORDER_STATUS_DELIVERY_LOADING];
                break;
            case ORDER_STATUS_DELIVERY_LOADING :
                $possibleDeliveryStatus = [ORDER_STATUS_DELIVERY_CANCELLED  , ORDER_STATUS_DELIVERY_CONFIRMED , ORDER_STATUS_DELIVERY_USER_REFUSE];
                break;
        }

        return $possibleDeliveryStatus;
    }

    /*
     * Delivery change order status
     * @param  int  $orderId
     * @param  int  $stepId
     * @return \Illuminate\Http\Response
     */

    public function deliveryNextStep($orderId,$stepId){
        // validate Order by id and is assigned to this delivery
        $delivery = Delivery::where('user_id',Auth::user()->id)->first(['id']);
        $roles = [
            'order_id' => 'required|integer|exists:orders,id,delivery_id,' . $delivery->id,
        ];

        $validator = Validator::make(
            ['order_id' => $orderId],
            $roles
        )->validate();

        // Get the selected Order
        $order = Order::find($orderId);

        // Get Delivery possible next step
        $possibleDeliverySteps = $this->getDeliveryPossibleOrderStatus($order->status);

        //validate next step is possible
        $validator = Validator::make(
            ['selected_step' => $stepId],
            [
                'selected_step' => 'required|integer|in:' . implode(",",$possibleDeliverySteps),
            ]
        )->validate();

        // update order status
        $order->status = $stepId;

        if($stepId == ORDER_STATUS_DELIVERY_CONFIRMED)
            $order->delivered_at = Carbon::now()->toDateTimeString();
        elseif ($stepId == ORDER_STATUS_DELIVERY_LOADING)
            $order->loading_at = Carbon::now()->toDateTimeString();


        if($order->save()){
            $toProvider = Provider::where('id',$order->provider_id)->first(['user_id']);

            //$msgProvider = __("general.yourOrderStatusChangedByDelivery");
            $msgProvider = __("general.". \StaticArray::$orderStatus[$stepId]) . __("general.clientOrderName") . $order->client_name;

            //$this->sendNotificationsToUser($msgProvider ,$toProvider->user_id , false ,'/provider/orders/' . $order->id);
            dispatch(new SendNotification($msgProvider ,$toProvider->user_id , false ,url('/provider/orders/' . $order->id)));

            $outputMsg = ['messageSuccess' => __('general.Order status updated')];
        }else{
            $outputMsg = ['messageDanger' => __('general.Order status cannot be updated now try again later')];
        }

        return redirect()->back()->with($outputMsg);
    }

    /*public function deliveryChangeOrderStatus($orderId , $status){
        $delivery = Delivery::where('user_id',Auth::user()->id)->first(['id']);


        $validator = Validator::make(
            ['order_id' => $orderId],
            [
                'order_id' => 'required|integer|exists:orders,id,delivery_id,' . $delivery->id,
            ]
        )->validate();

        $order = Order::where('id',$orderId)
            ->where('delivery_id',$delivery->id)->first();

        if($order){
            // get possible steps

        }else{

        }
    }*/

    public function userOrderShow($orderId,$verificationId,$delivery = null){
        App::setLocale('ar');
        $data = ['id' => $orderId , 'verification_id' => $verificationId , 'delivery' => $delivery];

        $validator = Validator::make(
            $data,
            [
                'verification_id' => 'required|string|size:8|exists:orders,user_verification',
                'delivery' => 'nullable|integer|exists:deliverers,id',
                'id' => [
                    'required',
                    'integer' ,
                    Rule::exists('orders')->where(function ($query) use($data) {
                        $query->where('user_verification', $data['verification_id']);

                        if($data['delivery'])
                            $query->where('delivery_id', $data['delivery']);
                    })
                ],
            ]
        );
        if($validator->fails()) {
            //dd($validator->errors());
            return redirect('notfound');
        }else{
            // Get the Order
            $order = Order::with([
                'provider' => function($q) { $q->with('user');} ,
                'delivery' => function($q) { $q->with('user');} ,
                'city' => function($q){
                    $q->with([
                        'language' => function ($q) {
                            $q->where('language_id',1);
                            $q->select('city_language.name');
                        }
                    ]);
                },
                'category' => function($q) {
                    $q->with([
                        'language' => function ($q) {
                            $q->where('language_id',1);
                            $q->select('category_language.name');
                        }
                    ]);
                },
                'main_service_type' => function($q) {
                    $q->with([
                        'language' => function ($q) {
                            $q->where('language_id',1);
                            $q->select('service_type_language.name');
                        }
                    ]);
                },
                'payment_type' => function($q) {
                    $q->with([
                        'language' => function ($q) {
                            $q->where('language_id',1);
                            $q->select('payment_type_language.name');
                        }
                    ]);
                },
                'extra_service_type' => function($q) {
                    $q->with([
                        'language' => function ($query) {
                            $query->where('language_id',1);
                            $query->select('service_type_language.name');
                        }
                    ]);
                }
            ])->where('id',$orderId)->first();

            //dd($order->toArray());

            if($order->category->language[0]->name)
                $order->category->name = $order->category->language[0]->name;

            if($order->city->language[0]->name)
                $order->city->name = $order->city->language[0]->name;


            if($order->main_service_type->language[0]->name)
                $order->main_service_type->name = $order->main_service_type->language[0]->name;

            if($order->payment_type->language[0]->name)
                $order->payment_type->name = $order->payment_type->language[0]->name;

            foreach ($order->extra_service_type as $extraService){
                if($extraService->language[0]->name)
                    $extraService->name = $extraService->language[0]->name;
            }


            // generate order time line
            $order->time_line = $this->GenerateOrderTimeLine($order);

            // Get provider country currency
            $order->currency = $order->provider->user->country->currency_symbol;


        }

        return view('user.orders.show')->with(
            [
                'order' => $order,
                'deliveryAccess' => $delivery
            ]
        );
    }

    public function userOrderUpdateLocation(Request $request,$orderId,$verificationId){
        App::setLocale('ar');
        $data = $request->all();
        $data['user_order_id'] = $orderId;
        $data['verification_id'] = $verificationId;
        //$data = ['id' => $orderId , 'verification_id' => $verificationId];

        $validator = Validator::make(
            $data,
            [
                'verification_id' => 'required|string|size:8|exists:orders,user_verification',
                'user_order_id' => [
                    'required',
                    'integer' ,
                    Rule::exists('orders','id')->where(function ($query) use($data) {
                        $query->where('user_verification', $data['verification_id']);
                        $query->where('user_updated',USER_NOT_UPDATED_ORDER);
                    })
                ],
                'order_lat' => 'required|numeric',
                'order_long' => 'required|numeric'
            ]
        )->validate();

        $order = Order::find($orderId);

        $order->order_lat = trim($request->order_lat);

        $order->order_long = trim($request->order_long);

        $order->user_updated = USER_UPDATED_ORDER;

        if($order->save())
            return redirect()->back()->with(['messageSuccess' => __("general.Order updated successfully")]);
        else
            return redirect()->back()->with(['messageDanger' => __("general.Cannot Update the selected order , Try again later")]);
    }

    public function userStatistics(){
        App::setLocale(Session::get('userLanguage.symbol'));

        // Get user Country data
        $country = Country::find(Auth::user()->country_id);


        // Get orders depending on user login
        $loginType = Session::get('login_type');

        // if logged as admin get all
        $provider = $delivery = null;

        if($loginType == PROVIDER){ // if logged as provider get only where the user is vendor
            $provider = Provider::where('user_id',Auth::user()->id)->first(['id'])->id;
        }elseif ($loginType == DRIVER){ // if logged as delivery show orders where the users is delivery
            $delivery = Delivery::where('user_id',Auth::user()->id)->first(['id'])->id;
        }

        //-------------- Get Orders status counts for Pie chart

        $ordersCountStatistics = Order::OfType($provider,$delivery)->groupBy('status')->select(DB::raw('count(*) as orders_count') , 'status')->get();

        // parse orders by status
        $orderByStatus = ['data' => [] , 'status' => []];


        foreach($ordersCountStatistics as $k => $order){
            $orderByStatus['data'][$k] = $order->orders_count;
            $orderByStatus['status'][$k] = __("general.".\StaticArray::$orderStatus[$order->status]);
            $orderByStatus['dataStatus'][$order->status] = $order->orders_count;
        }


        //------------------------------------------------------------------//

        //-------------- Get Orders count per(all , today , this month , this year)

        // Get total orders by day year Month
        $now = Carbon::now($country->time_zone);


        $ordersByDate['all'] = Order::OfType($provider,$delivery)->count();


        $ordersByDate['day'] = Order::OfType($provider,$delivery)->where(DB::raw("DATE(created_at)") , $now->format("Y-m-d"))->count();


        $ordersByDate['month'] = Order::OfType($provider,$delivery)->where(DB::raw("MONTH(created_at)") , $now->month)->count();


        $ordersByDate['year'] = Order::OfType($provider,$delivery)->where(DB::raw("YEAR(created_at)") , $now->year)->count();

        //------------------------------------------------------------------//

        //-------------- Get Orders Achievement per minute for (all , today , this month , this year)
        $orderAchievement['total'] = Order::OfType($provider,$delivery)
            ->where('status',ORDER_STATUS_DELIVERY_CONFIRMED)
            ->select(DB::raw('TIMESTAMPDIFF(MINUTE,assigned_at,delivered_at)  as minute_diff'))
            ->sum(DB::raw('TIMESTAMPDIFF(MINUTE,assigned_at,delivered_at)'));

        $orderAchievement['day'] = Order::OfType($provider,$delivery)
            ->where('status',ORDER_STATUS_DELIVERY_CONFIRMED)
            ->where(DB::raw("DATE(created_at)") , $now->format("Y-m-d"))
            ->select(DB::raw('TIMESTAMPDIFF(MINUTE,assigned_at,delivered_at)  as minute_diff'))
            ->sum(DB::raw('TIMESTAMPDIFF(MINUTE,assigned_at,delivered_at)'));

        $orderAchievement['month'] = Order::OfType($provider,$delivery)
            ->where('status',ORDER_STATUS_DELIVERY_CONFIRMED)
            ->where(DB::raw("MONTH(created_at)") , $now->month)
            ->select(DB::raw('TIMESTAMPDIFF(MINUTE,assigned_at,delivered_at)  as minute_diff'))
            ->sum(DB::raw('TIMESTAMPDIFF(MINUTE,assigned_at,delivered_at)'));

        $orderAchievement['year'] = Order::OfType($provider,$delivery)
            ->where('status',ORDER_STATUS_DELIVERY_CONFIRMED)
            ->where(DB::raw("YEAR(created_at)") , $now->year)
            ->select(DB::raw('TIMESTAMPDIFF(MINUTE,assigned_at,delivered_at)  as minute_diff'))
            ->sum(DB::raw('TIMESTAMPDIFF(MINUTE,assigned_at,delivered_at)'));
        //------------------------------------------------------------------//

        //-------------- Get Orders Count per this year month [For Line chart]
        $ordersPerMonth['yearMonths'] = [__("general.January"), __("general.February"), __("general.March"), __("general.April"), __("general.May"), __("general.June"), __("general.July") , __("general.August") , __("general.September") , __("general.October") , __("general.November") , __("general.December")];

        $ordersPerMonth['data'] = [];

        $ordersTempPerMonth = Order::OfType($provider,$delivery)
            ->select(DB::raw("MONTH(created_at) as month") , DB::raw("(COUNT(*)) as total_orders"))
            ->where(DB::raw("YEAR(created_at)") , $now->year)
            ->groupBy(DB::raw("MONTH(created_at)"))
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item['month'] => $item['total_orders']];
            });
        foreach($ordersPerMonth['yearMonths'] as $k => $month){
            $ordersPerMonth['data'][$k] = isset($ordersTempPerMonth[$k + 1]) ? $ordersTempPerMonth[$k + 1] : 0;
        }
        //------------------------------------------------------------------//

        //-------------- Get Orders Grouped by Location submitted
        $ordersWithLocation = Order::OfType($provider,$delivery)->groupBy('user_updated')->select(DB::raw('count(*) as orders_count') , 'user_updated')->get();

        return response()->json(['success' => 'true' , 'result' => ['orderStatusCount' => $orderByStatus , 'ordersCounts' => $ordersByDate , 'orderAchievement' => $orderAchievement , 'ordersPerMonth' => $ordersPerMonth , 'ordersWithLocationCount' => $ordersWithLocation]]);
    }
}
