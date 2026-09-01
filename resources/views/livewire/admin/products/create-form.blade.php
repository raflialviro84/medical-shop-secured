<div class="bg-white">
    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form wire:submit.prevent="save" class="space-y-8" enctype="multipart/form-data">
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Product Information</h3>
                    <p class="mt-1 text-sm text-gray-500">Enter the product details.</p>
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
                    <p class="mt-1 text-sm text-gray-500">Upload one or more images for the product.</p>
                </div>

                <div>
                    <label for="new_images" class="block text-sm font-medium text-gray-700">Images</label>
                    <input id="new_images" type="file" wire:model="new_images" multiple accept="image/*" class="mt-1 block w-full text-sm text-gray-500">
                </div>
            </div>

            <div class="pt-5 flex justify-end gap-3">
                <a href="{{ route('admin.products.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Create</button>
            </div>
        </form>
    </div>
</div>