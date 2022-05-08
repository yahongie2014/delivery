<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Jobs\SendNotification;

use App\Delivery;

use Validator;
class DeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $deliveries = Delivery::with('user');

        if($request->has('delivery')) {
            $deliveryName = $request->delivery;
            $deliveries = $deliveries->whereHas('user',function($q) use($deliveryName){
                $q->where('name' , 'LIKE' , '%' . $deliveryName . '%');
            });
        }

        if($request->has('deliveryStatus')){
            if($request->deliveryStatus != "")
                $deliveries = $deliveries->where('status',$request->deliveryStatus);
        }

        if($request->has('deliveryAvailable')){
            if($request->deliveryAvailable != "")
                $deliveries = $deliveries->where('available',$request->deliveryAvailable);
        }

        if($request->has('vehicle_id')){
            $deliveries = $deliveries->where('vehicle_id' , 'LIKE' , '%' . $request->vehicle_id . '%');
        }

        if($request->has('license_id')){
            $deliveries = $deliveries->where('license_id' , 'LIKE' , '%' . $request->license_id . '%');
        }

        $deliveries = $deliveries->orderBy('id','desc')->paginate(10);

        if(Request()->expectsJson()){
            return response() ->json(['status' => true , 'result' => $deliveries , 'recordsTotal' => $deliveries->total() , 'recordsFiltered' => $deliveries->total() , 'drow' => Request()->input('draw')]);
        }

        return view('admin.deliveries.index');
    }

    public function adminDeliveryActivation($delivery_id){
        $delivery = Delivery::find($delivery_id);

        if(!$delivery)
            return redirect()->back()->with(['messageDanger' => __("general.delivery dose not exists")]);
        else{
            if($delivery->status == DELIVERY_ACTIVE) {
                $delivery->status = DELIVERY_INACTIVE;
                $msg = __("general.yourDriverAccountDeActivated");
            }else {
                $delivery->status = DELIVERY_ACTIVE;
                $msg = __("general.yourDriverAccountActivated");

            }


            if($delivery->save()){
                //$this->sendNotificationsToUser($msg ,$delivery->user_id , false );
                dispatch(new SendNotification($msg ,$delivery->user_id , false));
                return redirect()->back()->with(['messageSuccess' => __("general.delivery status changed")]);
            }else{
                return redirect()->back()->with(['messageDanger' => __("general.delivery status could not be changed")]);
            }
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
        $delivery = Delivery::where('user_id',Auth::user()->id)->first(['id']);

        Validator::make(
            $request->all(),
            [
                'delivery_id' => 'required|exists:deliverers,id|integer|in:' . $delivery->id ,
                'vehicle_id' => ['required' , 'max:15' , Rule::unique('deliverers','vehicle_id')->ignore($id)],
                'license_id' => ['required' , 'max:15' , Rule::unique('deliverers','license_id')->ignore($id)],
                'deliveryAvailability' => 'sometimes|required|in:' . DELIVERY_AVAILABLE
            ]
        )->validate();

        $delivery->vehicle_id = trim($request->vehicle_id);
        $delivery->license_id = trim($request->license_id);

        if($request->has('deliveryAvailability'))
            $delivery->available = DELIVERY_AVAILABLE;
        else
            $delivery->available = DELIVERY_UNAVAILABLE ;

        if($delivery->save()){
            return redirect()->back()->with(['messageSuccess' => __("general.Driver data updated successfully")]);
        }else{
            return redirect()->back()->with(['messageDanger' => __("general.Problem updating the driver")]);
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
