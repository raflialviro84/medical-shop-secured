@extends('layouts.app')

@section('title', $product->product_name)

@section('content')
<div class="bg-white">
    <div class="max-w-2xl mx-auto py-8 px-4 sm:py-12 sm:px-6 lg:max-w-7xl lg:px-8">
        <div class="lg:grid lg:grid-cols-2 lg:gap-x-8 lg:items-start">
            <!-- Image gallery -->
            <div class="flex flex-col">
                @php
                    $images = $product->product_image??[];
                    $firstImage = $images[0] ?? null;
                @endphp
                
                <div class="w-full aspect-w-1 aspect-h-1 bg-gray-200 rounded-lg overflow-hidden">
                    @if($firstImage)
                        <img src="{{ $firstImage }}" alt="{{ $product->product_name }}" class="w-full h-full object-center object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gray-100">
                            <svg class="h-16 w-16 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    @endif
                </div>
                
                @if(count($images) > 1)
                    <div class="mt-4 grid grid-cols-4 gap-2">
                        @foreach(array_slice($images, 0, 4) as $index => $image)
                            <div class="relative aspect-w-1 aspect-h-1 rounded-lg overflow-hidden bg-gray-100">
                                <img src="{{ $image }}" alt="{{ $product->product_name }} image {{ $index + 1 }}" class="w-full h-full object-center object-cover">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product info -->
            <div class="mt-10 px-4 sm:px-0 sm:mt-16 lg:mt-0">
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">{{ $product->product_name }}</h1>
                
                <div class="mt-3">
                    <h2 class="sr-only">Product information</h2>
                    <p class="text-3xl text-gray-900">${{ number_format($product->price, 2) }}</p>
                </div>

                <div class="mt-3">
                    @if($product->stock > 0)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            In Stock ({{ $product->stock }})
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Out of Stock
                        </span>
                    @endif
                </div>

                <div class="mt-6">
                    <h3 class="sr-only">Description</h3>
                    <div class="text-base text-gray-700 space-y-6">
                        <p>{{ $product->product_description }}</p>
                    </div>
                </div>

                @auth
                    @if($product->stock > 0)
                        <div class="mt-8">
                            <livewire:add-to-cart :product="$product" />
                        </div>
                    @endif
                @else
                    <div class="mt-8">
                        <a href="{{ route('login') }}" class="w-full bg-indigo-600 border border-transparent rounded-md py-3 px-8 flex items-center justify-center text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Login to Purchase
                        </a>
                    </div>
                @endauth

                <section class="mt-12">
                    <div class="border-t border-gray-200 pt-8">
                        <h2 class="text-lg font-medium text-gray-900">Product Details</h2>
                        <div class="mt-4 prose prose-sm text-gray-500">
                            <ul role="list">
                                <li>High-quality medical product</li>
                                <li>Manufactured following strict quality standards</li>
                                <li>Properly stored and handled</li>
                                <li>Fast shipping available</li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
