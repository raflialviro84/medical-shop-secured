<div class="bg-white">
    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="border-b border-gray-200 pb-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Transaction #{{ substr($transaction->id, 0, 8) }}</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Placed on {{ $transaction->created_at->format('F j, Y, g:i a') }}
                    </p>
                </div>
                <div>
                    @if($transaction->status == 'pending')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                    @elseif($transaction->status == 'paid')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Paid</span>
                    @elseif($transaction->status == 'shipped')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Shipped</span>
                    @elseif($transaction->status == 'done')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Done</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-8">
            <div class="flow-root">
                <ul role="list" class="-my-6 divide-y divide-gray-200">
                    @foreach($transaction->details as $detail)
                        <li class="py-6 flex">
                            <div class="flex-shrink-0 w-24 h-24 border border-gray-200 rounded-md overflow-hidden">
                                @php
                                    $images = $detail->product->product_image ?? [];
                                    $firstImage = $images[0] ?? null;
                                @endphp

                                @if($firstImage)
                                    <img src="{{ $firstImage }}" alt="{{ $detail->product->product_name }}" class="w-full h-full object-center object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                        <svg class="h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <div class="ml-4 flex-1 flex flex-col">
                                <div>
                                    <div class="flex justify-between text-base font-medium text-gray-900">
                                        <h3>
                                            <a href="{{ route('products.show', $detail->product) }}">{{ $detail->product->product_name }}</a>
                                        </h3>
                                        <p class="ml-4">${{ number_format($detail->price * $detail->amount, 2) }}</p>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">Price: ${{ number_format($detail->price, 2) }} each</p>
                                </div>
                                <div class="flex-1 flex items-end justify-between text-sm">
                                    <p class="text-gray-500">Qty {{ $detail->amount }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-200 py-6 mt-6">
            <div class="flex justify-between text-base font-medium text-gray-900">
                <p>Subtotal</p>
                <p>${{ number_format($transaction->total_price, 2) }}</p>
            </div>
            <p class="mt-0.5 text-sm text-gray-500">Shipping and taxes included.</p>
        </div>

        <div class="mt-8">
            @if($transaction->status == 'pending')
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">This transaction is pending payment. Please complete your payment to proceed.</p>
                        </div>
                    </div>
                </div>
                <button type="button" wire:click="pay" class="w-full flex justify-center items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Pay Now
                </button>
            @elseif($transaction->status == 'shipped')
                <div class="bg-purple-50 border-l-4 border-purple-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-purple-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-5h2.038A2.968 2.968 0 0115 8.5a2.968 2.968 0 01-1.962-1.5H5V5a1 1 0 011-1h7.5a1 1 0 01.982.741A2.972 2.972 0 0115 4a2.972 2.972 0 01-.518.741A1 1 0 0115.5 5h2a1 1 0 011 1v4.53a1 1 0 01-.352.76L15 13.991v1.51a1 1 0 01-1 1h-.05a2.5 2.5 0 01-4.9 0H3a1 1 0 01-1-1V5a1 1 0 011-1h3z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-purple-700">Your order has been shipped. Once you receive it, please mark it as done.</p>
                        </div>
                    </div>
                </div>
                <button type="button" wire:click="markAsDone" class="w-full flex justify-center items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Mark as Received
                </button>
            @elseif($transaction->status == 'paid')
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">Payment received. Your order is being processed and will be shipped soon.</p>
                        </div>
                    </div>
                </div>
            @elseif($transaction->status == 'done')
                <div class="bg-green-50 border-l-4 border-green-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">This transaction has been completed. Thank you for your purchase!</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-6">
            <a href="{{ route('transactions.invoice', $transaction) }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Download Invoice
            </a>
        </div>
    </div>
</div>