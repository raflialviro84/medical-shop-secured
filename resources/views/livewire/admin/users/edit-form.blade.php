<div class="bg-white">
    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form wire:submit.prevent="save" class="space-y-8">
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">User Information</h3>
                    <p class="mt-1 text-sm text-gray-500">Update the user's personal information.</p>
                </div>

                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-3">
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input id="username" type="text" wire:model="username" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                        <input id="email" type="email" wire:model="email" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <label for="full_name" class="block text-sm font-medium text-gray-700">Full name</label>
                        <input id="full_name" type="text" wire:model="full_name" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                        <select id="role" wire:model="role" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-span-6">
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <input id="address" type="text" wire:model="address" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                        <input id="city" type="text" wire:model="city" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <label for="contact" class="block text-sm font-medium text-gray-700">Contact</label>
                        <input id="contact" type="text" wire:model="contact" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Password</h3>
                    <p class="mt-1 text-sm text-gray-500">Leave blank if you don't want to change the password.</p>
                </div>

                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-3">
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input id="password" type="password" wire:model="password" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input id="password_confirmation" type="password" wire:model="password_confirmation" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>
            </div>

            <div class="pt-5 flex justify-end gap-3">
                <a href="{{ route('admin.users.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Save</button>
            </div>
        </form>
    </div>
</div>