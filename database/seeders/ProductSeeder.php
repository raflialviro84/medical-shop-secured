<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'product_name' => 'Paracetamol',
                'product_description' => 'Pain reliever and fever reducer. Used to treat many conditions such as headache, muscle aches, arthritis, backache, toothaches, colds, and fevers.',
                'price' => 5.99,
                'stock' => 100,
            ],
            [
                'product_name' => 'Ibuprofen',
                'product_description' => 'Nonsteroidal anti-inflammatory drug (NSAID). Used to relieve pain from various conditions such as headache, dental pain, menstrual cramps, muscle aches, or arthritis.',
                'price' => 7.99,
                'stock' => 85,
            ],
            [
                'product_name' => 'Amoxicillin',
                'product_description' => 'Antibiotic used to treat a number of bacterial infections. It is a first line treatment for middle ear infections and strep throat.',
                'price' => 12.50,
                'stock' => 50,
            ],
            [
                'product_name' => 'Omeprazole',
                'product_description' => 'Proton pump inhibitor used to treat certain stomach and esophagus problems (such as acid reflux, ulcers).',
                'price' => 15.75,
                'stock' => 65,
            ],
            [
                'product_name' => 'Loratadine',
                'product_description' => 'Antihistamine that reduces the effects of natural chemical histamine in the body. Used to treat sneezing, runny nose, watery eyes, hives, skin rash, itching, and other cold or allergy symptoms.',
                'price' => 8.25,
                'stock' => 75,
            ],
            [
                'product_name' => 'Vitamin C',
                'product_description' => 'Essential vitamin that helps the immune system, skin, and bones. Used to prevent and treat scurvy.',
                'price' => 9.99,
                'stock' => 120,
            ],
            [
                'product_name' => 'Vitamin D3',
                'product_description' => 'Essential vitamin that helps the body absorb calcium. Used to treat and prevent vitamin D deficiency.',
                'price' => 11.50,
                'stock' => 90,
            ],
            [
                'product_name' => 'Zinc',
                'product_description' => 'Essential mineral that helps the immune system and metabolism function. Used to treat and prevent zinc deficiency.',
                'price' => 7.25,
                'stock' => 80,
            ],
            [
                'product_name' => 'First Aid Kit',
                'product_description' => 'Basic first aid kit containing bandages, antiseptic wipes, gauze pads, medical tape, and more.',
                'price' => 25.99,
                'stock' => 40,
            ],
            [
                'product_name' => 'Digital Thermometer',
                'product_description' => 'Digital thermometer for measuring body temperature. Fast and accurate readings.',
                'price' => 15.99,
                'stock' => 30,
            ],
            [
                'product_name' => 'Blood Pressure Monitor',
                'product_description' => 'Digital blood pressure monitor for home use. Measures systolic and diastolic blood pressure and pulse rate.',
                'price' => 45.99,
                'stock' => 25,
            ],
            [
                'product_name' => 'Glucose Meter',
                'product_description' => 'Blood glucose meter for monitoring blood sugar levels. Includes test strips and lancets.',
                'price' => 35.50,
                'stock' => 20,
            ],
        ];

        foreach ($products as $product) {

            // Create the product with UUID and images
            Product::create([
                'id' => Str::uuid(),
                'product_name' => $product['product_name'],
                'product_description' => $product['product_description'],
                'price' => $product['price'],
                'stock' => $product['stock'],
            ]);
        }
    }
}
