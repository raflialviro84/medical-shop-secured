<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index()
    {
        $products = Product::paginate(12);

        return view('products.index', compact('products'));
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        $relatedProducts = Product::where('id', '!=', $product->id)
            ->where('stock', '>', 0)
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'relatedProducts'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'product_description' => 'nullable|string',
            'new_images' => 'nullable|array',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $images = [];

        // Process new images
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $image) {
                // Convert image to base64
                $imageData = base64_encode(file_get_contents($image->getRealPath()));
                $mime = $image->getClientMimeType();
                $base64Image = 'data:' . $mime . ';base64,' . $imageData;

                // Add to images array
                $images[] = $base64Image;
            }
        }

        Product::create([
            'product_name' => $validated['product_name'],
            'product_description' => $validated['product_description'],
            'product_image' => $images,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'product_description' => 'nullable|string',
            'existing_images' => 'nullable|array',
            'existing_images.*' => 'nullable|string',
            'removed_images' => 'nullable|array',
            'removed_images.*' => 'nullable|integer',
            'new_images' => 'nullable|array',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);
        // dd($validated);

        // Process images
        $images = [];

        // Get existing images
        $images = $product->product_image ?? [];

        // Remove images that were marked for deletion
        if ($request->has('removed_images')) {
            foreach ($request->removed_images as $index) {
                if (isset($images[$index])) {
                    unset($images[$index]);
                }
                Log::info('Removed image at index: ' . $index);
            }
            // Re-index the array
            $images = array_values($images);
        }

        // Add new images
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $image) {
                // Convert image to base64
                $imageData = base64_encode(file_get_contents($image->getRealPath()));
                $mime = $image->getClientMimeType();
                $base64Image = 'data:' . $mime . ';base64,' . $imageData;

                // Add to images array
                $images[] = $base64Image;
            }
        }

        // Update product with processed data
        $product->update([
            'product_name' => $validated['product_name'],
            'product_description' => $validated['product_description'],
            'product_image' => $images,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
