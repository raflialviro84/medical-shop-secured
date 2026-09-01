@extends('layouts.app')

@section('title', 'My Transactions')

@section('header')
    <h1 class="text-3xl font-bold text-gray-900">
        My Transactions
    </h1>
@endsection

@section('content')
<livewire:transactions.index-page />
@endsection
