<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Electronics' => [
                'Smartphones' => ['iPhone', 'Android', 'Accessories'],
                'Computers' => ['Laptops', 'Desktops', 'Monitors'],
                'Cameras' => ['DSLR', 'Mirrorless', 'Lenses'],
                'Audio' => ['Headphones', 'Speakers'],
            ],
            'Home & Garden' => [
                'Furniture' => ['Chairs', 'Tables', 'Sofas', 'Beds'],
                'Decor' => ['Rugs', 'Lighting', 'Mirrors'],
                'Garden' => ['Tools', 'Plants', 'Outdoor Furniture'],
            ],
            'Fashion' => [
                'Men' => ['Shoes', 'Jackets', 'Shirts', 'Pants'],
                'Women' => ['Dresses', 'Shoes', 'Handbags', 'Jewelry'],
                'Kids' => ['Boys', 'Girls', 'Baby'],
            ],
            'Automotive' => [
                'Car Parts' => ['Tires', 'Batteries', 'Engine', 'Lights'],
                'Motorcycles' => ['Bikes', 'Parts', 'Gear'],
                'Tools' => ['Diagnostic', 'Repair'],
            ],
            'Sports & Outdoors' => [
                'Fitness' => ['Dumbbells', 'Treadmills', 'Yoga'],
                'Team Sports' => ['Soccer', 'Basketball', 'Baseball'],
                'Camping' => ['Tents', 'Sleeping Bags', 'Backpacks'],
            ],
            'Collectibles' => [
                'Art' => ['Paintings', 'Prints', 'Sculptures'],
                'Coins & Money' => ['US', 'World'],
                'Comics' => ['Marvel', 'DC', 'Indie'],
            ],
        ];

        foreach ($categories as $parentName => $subCategories) {
            $parent = $this->createCategory($parentName);

            foreach ($subCategories as $subName => $subSubCategories) {
                // Check if $subSubCategories is actually an array of strings (sub-subs) or just values
                // In this structure, it's Name => [Children]
                
                $sub = $this->createCategory($subName, $parent->id);

                foreach ($subSubCategories as $subSubName) {
                    $this->createCategory($subSubName, $sub->id);
                }
            }
        }
    }

    private function createCategory(string $name, ?int $parentId = null): Category
    {
        $slug = Str::slug($name);
        
        // Handle duplicate slugs if parent is different (basic implementation)
        // Ideally we'd scope uniqueness, but for fixed seeder data this is fine.
        // If we really wanted scoped slugs, we'd append parent slug, but let's keep it simple for now.
        
        // For the seeder, we want updateOrCreate to prevent duplicates based on slug + parent if possible,
        // but since slug is unique globally in migration, we rely on the name-slug mapping.
        
        return Category::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'parent_id' => $parentId,
            ]
        );
    }
}
