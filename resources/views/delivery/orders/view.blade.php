@extends('layouts.deliverylayout')

@section('content')
    @parent
    @include('partials.orders.view', ['orders' => $orders])
@endsection