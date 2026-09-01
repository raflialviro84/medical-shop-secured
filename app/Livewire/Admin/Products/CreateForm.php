<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateForm extends Component
{
    use WithFileUploads;

    public string $product_name = '';

    public string $product_description = '';

    public string $price = '';

    public string $stock = '0';

    public array $new_images = [];

    public function save()
    {
        $validated = $this->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'product_description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'new_images' => ['nullable', 'array'],
            'new_images.*' => ['image', 'mimes:jpeg,png,jpg,gif', 'max:10240'],
        ]);

        $images = [];

        foreach ($this->new_images as $image) {
            $imageData = base64_encode(file_get_contents($image->getRealPath()));
            $mime = $image->getClientMimeType();
            $images[] = 'data:' . $mime . ';base64,' . $imageData;
        }

        Product::create([
            'product_name' => $validated['product_name'],
            'product_description' => $validated['product_description'] ?? null,
            'product_image' => $images,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function render()
    {
        return view('livewire.admin.products.create-form');
    }
}