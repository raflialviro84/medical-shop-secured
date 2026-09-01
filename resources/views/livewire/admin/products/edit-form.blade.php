<div class="bg-white">
    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form wire:submit.prevent="save" class="space-y-8" enctype="multipart/form-data">
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Product Information</h3>
                    <p class="mt-1 text-sm text-gray-500">Update the product details.</p>
                </div>

                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6">
                        <label for="product_name" class="block text-sm font-medium text-gray-700">Product Name</label>
                        <input id="product_name" type="text" wire:model="product_name" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="col-span-6">
                        <label for="product_description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="product_description" wire:model="product_description" rows="4" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <label for="price" class="block text-sm font-medium text-gray-700">Price ($)</label>
                        <input id="price" type="number" wire:model="price" min="0.01" step="0.01" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <label for="stock" class="block text-sm font-medium text-gray-700">Stock</label>
                        <input id="stock" type="number" wire:model="stock" min="0" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Product Images</h3>
                    <p class="mt-1 text-sm text-gray-500">Remove existing images or add new ones.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Current Images</label>
                    <div class="mt-2 grid grid-cols-3 gap-4">
                        @forelse($existingImages as $index => $image)
                            <div class="relative">
                                <img src="{{ $image }}" alt="Product image {{ $index + 1 }}" class="h-24 w-24 object-cover rounded-md">
                                <button type="button" wire:click="removeExistingImage({{ $index }})" class="absolute top-0 right-0 text-red-600 hover:text-red-900 bg-white rounded-full p-1">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500">No images available</div>
                        @endforelse
                    </div>
                </div>

                <div>
                    <label for="new_images" class="block text-sm font-medium text-gray-700">Add New Images</label>
                    <input id="new_images" type="file" wire:model="new_images" multiple accept="image/*" class="mt-1 block w-full text-sm text-gray-500">
                </div>
            </div>

            <div class="pt-5 flex justify-end gap-3">
                <a href="{{ route('admin.products.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Save</button>
            </div>
        </form>
    </div>
</div>