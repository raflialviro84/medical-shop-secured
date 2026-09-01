@extends('layouts.app')

@section('title', 'Create Product')

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">
            Create Product
        </h1>
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Back to Products
        </a>
    </div>
@endsection

@section('content')
<livewire:admin.products.create-form />
@endsection
