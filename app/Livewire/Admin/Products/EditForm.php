<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditForm extends Component
{
    use WithFileUploads;

    public Product $product;

    public string $product_name = '';

    public string $product_description = '';

    public string $price = '';

    public string $stock = '0';

    public array $existingImages = [];

    public array $removedImages = [];

    public array $new_images = [];

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->product_name = $product->product_name;
        $this->product_description = (string) $product->product_description;
        $this->price = (string) $product->price;
        $this->stock = (string) $product->stock;
        $this->existingImages = $product->product_image ?? [];
    }

    public function removeExistingImage(int $index): void
    {
        if (! isset($this->existingImages[$index])) {
            return;
        }

        unset($this->existingImages[$index]);
        $this->existingImages = array_values($this->existingImages);
        $this->removedImages[] = $index;
    }

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

        $images = $this->existingImages;

        foreach ($this->new_images as $image) {
            $imageData = base64_encode(file_get_contents($image->getRealPath()));
            $mime = $image->getClientMimeType();
            $images[] = 'data:' . $mime . ';base64,' . $imageData;
        }

        $this->product->update([
            'product_name' => $validated['product_name'],
            'product_description' => $validated['product_description'] ?? null,
            'product_image' => array_values($images),
            'price' => $validated['price'],
            'stock' => $validated['stock'],
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function render()
    {
        return view('livewire.admin.products.edit-form');
    }
}