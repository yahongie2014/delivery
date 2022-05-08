@extends('layouts.deliverylayout')

@section('PageHeader')
{{__("general.Order")}}
@endsection

@section('PageLocation')
@parent

<li>
    <a href="{{url('/delivery/orders')}}">
        {{__("general.Orders")}}
    </a>
</li>
@endsection

@section('content')
@parent
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default card-view">
            <div class="panel-wrapper collapse in">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-sm-12 col-xs-12" style="text-align: center">

                            <div class="form-wrap">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label mb-10"></label>
                                        <p class="text-primary mb-10">{{__("general.".StaticArray::$orderStatus[$order->status])}}</code></p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                @foreach($order->possible_steps as $possibleStep)

                                    <a href="{{url('/delivery/orders/status/' . $order->id . '/' . $possibleStep)}}">
                                        <button class="btn  btn-{{StaticArray::$orderDeliverySteps[$possibleStep]['color']}} btn-rounded">
                                            {{__("general.".StaticArray::$orderDeliverySteps[$possibleStep]['name'])}}
                                        </button>
                                    </a>

                                @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('partials.orders.show', ['order' => $order , 'userType' => DRIVER])
@endsection