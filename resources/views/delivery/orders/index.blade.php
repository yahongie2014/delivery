@extends('layouts.deliverylayout')

@section('PageHeader')

{{__("general.Orders")}}
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
@include('partials.orders.view', [ 'editRoute' => $editRoute , 'loginType' => DRIVER])
@endsection