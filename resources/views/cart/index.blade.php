@extends('layouts.app')

@section('title', 'Shopping Cart')

@section('header')
    <h1 class="text-3xl font-bold text-gray-900">
        Shopping Cart
    </h1>
@endsection

@section('content')
<livewire:cart-page />
@endsection
