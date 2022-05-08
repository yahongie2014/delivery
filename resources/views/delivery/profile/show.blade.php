@extends('layouts.deliverylayout')

@section('PageHeader')
{{__("general.Profile")}}
@endsection

@section('PageLocation')
@parent

<li>
    <a href="#">
        {{__("general.Profile")}}
    </a>
</li>
@endsection

@section('content')
@parent
@include('partials.user.show', ['user' => $user , 'loginType' => DRIVER ])
@endsection