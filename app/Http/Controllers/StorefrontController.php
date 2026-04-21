<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        return view('storefront.home', $this->storefrontData([
            'pageTitle' => 'ExpressBazar | Quick grocery, daily essentials, and fresh deals',
            'pageDescription' => 'A Zepto-inspired commerce experience for groceries, essentials, and fast delivery.',
            'activeNav' => 'home',
        ]));
    }

    public function category(string $slug): View
    {
        $categories = collect($this->categories());
        $category = $categories->firstWhere('slug', $slug) ?? $categories->first();

        $products = collect($this->products())
            ->where('categorySlug', $category['slug'])
            ->values()
            ->all();

        return view('storefront.category', $this->storefrontData([
            'pageTitle' => $category['name'] . ' | ExpressBazar',
            'pageDescription' => $category['description'],
            'activeNav' => 'categories',
            'category' => $category,
            'products' => $products,
        ]));
    }

    public function product(string $slug): View
    {
        $product = collect($this->products())->firstWhere('slug', $slug) ?? $this->products()[0];
        $related = collect($this->products())
            ->where('categorySlug', $product['categorySlug'])
            ->where('slug', '!=', $product['slug'])
            ->take(4)
            ->values()
            ->all();

        return view('storefront.product', $this->storefrontData([
            'pageTitle' => $product['name'] . ' | ExpressBazar',
            'pageDescription' => $product['description'],
            'activeNav' => 'products',
            'product' => $product,
            'relatedProducts' => $related,
        ]));
    }

    public function cart(): View
    {
        return view('storefront.cart', $this->storefrontData([
            'pageTitle' => 'Your Cart | ExpressBazar',
            'pageDescription' => 'Review your cart and continue to checkout.',
            'activeNav' => 'cart',
            'cartItems' => $this->cartItems(),
            'orderSummary' => [
                'subtotal' => 1584,
                'deliveryFee' => 0,
                'handlingFee' => 0,
                'discount' => 145,
                'total' => 1439,
            ],
        ]));
    }

    public function checkout(): View
    {
        return view('storefront.checkout', $this->storefrontData([
            'pageTitle' => 'Checkout | ExpressBazar',
            'pageDescription' => 'Enter address, choose delivery slot, and place your order.',
            'activeNav' => 'checkout',
            'cartItems' => $this->cartItems(),
            'orderSummary' => [
                'subtotal' => 1584,
                'deliveryFee' => 0,
                'handlingFee' => 0,
                'discount' => 145,
                'total' => 1439,
            ],
        ]));
    }

    private function storefrontData(array $overrides = []): array
    {
        return array_merge([
            'brandName' => config('app.name', 'ExpressBazar'),
            'location' => 'Hyderabad, Telangana',
            'cartCount' => 4,
            'navItems' => [
                ['label' => 'All', 'slug' => 'all'],
                ['label' => 'Cafe', 'slug' => 'cafe'],
                ['label' => 'Home', 'slug' => 'home'],
                ['label' => 'Toys', 'slug' => 'toys'],
                ['label' => 'Fresh', 'slug' => 'fresh'],
                ['label' => 'Electronics', 'slug' => 'electronics'],
                ['label' => 'Mobiles', 'slug' => 'mobiles'],
                ['label' => 'Beauty', 'slug' => 'beauty'],
                ['label' => 'Fashion', 'slug' => 'fashion'],
            ],
            'benefits' => [
                ['title' => '0 fees, every day', 'text' => 'Keep the experience simple with no handling surprises on the essentials route.'],
                ['title' => 'Fast delivery slots', 'text' => 'Clear promise bands for 10 to 20 minute delivery windows and scheduled checkout.'],
                ['title' => 'Fresh and curated', 'text' => 'Focus on daily needs, top repeat buys, and local relevance that feels retail-first.'],
            ],
            'categoryCards' => $this->categories(),
            'collections' => [
                ['title' => 'Daily groceries', 'subtitle' => 'Staples, rice, flour, cooking oil, and quick restocks.', 'accent' => 'from-[#f4e9ff] to-[#ece1ff]'],
                ['title' => 'Household care', 'subtitle' => 'Cleaning, laundry, kitchen, and home essentials.', 'accent' => 'from-[#edf8ff] to-[#dfeeff]'],
                ['title' => 'Fresh picks', 'subtitle' => 'Produce, dairy, bread, and chilled favorites.', 'accent' => 'from-[#f1fff2] to-[#ddf7df]'],
            ],
            'featuredProducts' => array_slice($this->products(), 0, 8),
            'moreProducts' => array_slice($this->products(), 8, 4),
            'promoBanners' => [
                [
                    'title' => 'All new ExpressBazar experience',
                    'text' => 'Build the same quick-commerce feeling with transparent prices, fast re-ordering, and a clean browsing flow.',
                ],
                [
                    'title' => 'Fast repeat buying',
                    'text' => 'Surface previously purchased items, suggested add-ons, and one-tap cart updates for power users.',
                ],
            ],
            'howItWorks' => [
                ['title' => 'Search or browse', 'text' => 'Use the top search bar and category rail to find what you need quickly.'],
                ['title' => 'Add to cart', 'text' => 'Product cards include prices, offers, ratings, and clear add actions.'],
                ['title' => 'Checkout fast', 'text' => 'Finish with address, delivery slot, and payment options in one smooth flow.'],
            ],
            'footerLinks' => [
                'About ExpressBazar',
                'Delivery areas',
                'Customer support',
                'Orders & returns',
                'Privacy policy',
                'Terms of use',
            ],
            'pageTitle' => config('app.name', 'ExpressBazar'),
            'pageDescription' => 'Zepto-inspired ecommerce storefront.',
            'activeNav' => 'home',
        ], $overrides);
    }

    private function categories(): array
    {
        return [
            ['name' => 'Fruits & Vegetables', 'slug' => 'fruits-vegetables', 'description' => 'Fresh produce, seasonal fruits, and everyday greens.', 'color' => '#19a55b'],
            ['name' => 'Dairy, Bread & Eggs', 'slug' => 'dairy-bread-eggs', 'description' => 'Milk, curd, bread, eggs, and breakfast staples.', 'color' => '#5fa9f8'],
            ['name' => 'Atta, Rice & Dals', 'slug' => 'atta-rice-dals', 'description' => 'Rice, flour, pulses, and cooking staples.', 'color' => '#8f5cff'],
            ['name' => 'Breakfast & Snacks', 'slug' => 'breakfast-snacks', 'description' => 'Quick bites, cereals, spreads, and snack packs.', 'color' => '#ff8d2f'],
            ['name' => 'Packaged Food', 'slug' => 'packaged-food', 'description' => 'Ready-to-cook, ready-to-eat, and pantry stockups.', 'color' => '#ef4b83'],
            ['name' => 'Beverages', 'slug' => 'beverages', 'description' => 'Soft drinks, juices, coffee, tea, and energy drinks.', 'color' => '#23b5a8'],
            ['name' => 'Household', 'slug' => 'household', 'description' => 'Cleaning, laundry, kitchen and everyday home care.', 'color' => '#4c7df0'],
            ['name' => 'Personal Care', 'slug' => 'personal-care', 'description' => 'Hair care, skin care, grooming, and bath essentials.', 'color' => '#af57ff'],
        ];
    }

    private function products(): array
    {
        return [
            $this->productCard('Daily Good Sona Masoori Raw Rice', 'atta-rice-dals', 69, 100, 4.8, '1 kg pack', '31% OFF', '#1f8f5c', '#eafbf1'),
            $this->productCard('India Gate Jeera Rice Short Grain', 'atta-rice-dals', 226, 320, 4.9, '1 kg pack', '29% OFF', '#0c7fb5', '#e8f7ff'),
            $this->productCard('Daawat Rozana Super Basmati Rice', 'atta-rice-dals', 75, 100, 4.6, '1 kg pack', '25% OFF', '#7b4de8', '#f1e9ff'),
            $this->productCard('Fortune Sona Masoori Regular', 'atta-rice-dals', 342, 450, 4.6, '5 kg pack', '24% OFF', '#ef7f27', '#fff1e4'),
            $this->productCard('Parachute Advanced Men Hair Cream', 'personal-care', 82, 100, 4.5, '100 g', '18% OFF', '#3743bf', '#ecedff'),
            $this->productCard('L\'Oreal Paris Hyaluron Moisture 72H', 'personal-care', 238, 345, 4.7, '180 ml', '31% OFF', '#e05bc0', '#ffe9f8'),
            $this->productCard('Streax Professional Vitaglaze Hair Serum', 'personal-care', 171, 180, 4.8, '100 ml', '5% OFF', '#11a4a7', '#e7fffb'),
            $this->productCard('Dove Intense Repair Conditioner', 'personal-care', 194, 285, 4.6, '180 ml', '31% OFF', '#8c5dd9', '#f4edff'),
            $this->productCard('Amul Taaza Toned Milk', 'dairy-bread-eggs', 58, 62, 4.7, '1 L', '6% OFF', '#f7b500', '#fff7dd'),
            $this->productCard('Farm Fresh White Bread', 'dairy-bread-eggs', 42, 50, 4.6, '400 g', '16% OFF', '#f14f75', '#fff0f5'),
            $this->productCard('Farm Eggs Large Pack', 'dairy-bread-eggs', 92, 120, 4.8, '12 pieces', '23% OFF', '#c28652', '#fff2e7'),
            $this->productCard('Fresh Bananas Bunch', 'fruits-vegetables', 36, 48, 4.7, '1 dozen', '25% OFF', '#34b86b', '#effbf2'),
            $this->productCard('Kurkure Masala Munch Pack', 'breakfast-snacks', 19, 20, 4.5, '75 g', '5% OFF', '#ff8c32', '#fff3e7'),
            $this->productCard('Makhana Crunch Mix', 'breakfast-snacks', 124, 160, 4.6, '200 g', '22% OFF', '#b95de9', '#f9ecff'),
            $this->productCard('Cold Coffee Bottle', 'beverages', 89, 110, 4.5, '250 ml', '19% OFF', '#2d6ff3', '#e8f0ff'),
            $this->productCard('Fresh Lemon Soda', 'beverages', 39, 45, 4.4, '300 ml', '13% OFF', '#11aeb1', '#e8fffd'),
        ];
    }

    private function cartItems(): array
    {
        return [
            ['name' => 'Daily Good Sona Masoori Raw Rice', 'price' => 69, 'qty' => 2, 'unit' => '1 kg', 'image' => $this->art('Rice', '#1f8f5c', '#ecfff4')],
            ['name' => 'Amul Taaza Toned Milk', 'price' => 58, 'qty' => 3, 'unit' => '1 L', 'image' => $this->art('Milk', '#0c7fb5', '#eef8ff')],
            ['name' => 'Parachute Advanced Men Hair Cream', 'price' => 82, 'qty' => 1, 'unit' => '100 g', 'image' => $this->art('Care', '#3743bf', '#f0f2ff')],
        ];
    }

    private function productCard(
        string $name,
        string $categorySlug,
        int $price,
        int $mrp,
        float $rating,
        string $unit,
        string $deal,
        string $accent,
        string $background
    ): array {
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'categorySlug' => $categorySlug,
            'price' => $price,
            'mrp' => $mrp,
            'rating' => $rating,
            'unit' => $unit,
            'deal' => $deal,
            'description' => 'A quick-commerce style product detail page with pricing, highlights, and related recommendations.',
            'accent' => $accent,
            'background' => $background,
            'image' => $this->art($name, $accent, $background),
        ];
    }

    private function art(string $label, string $accent, string $background): string
    {
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 320"><defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop offset="0%%" stop-color="%s"/><stop offset="100%%" stop-color="%s"/></linearGradient></defs><rect width="360" height="320" rx="36" fill="url(#g)"/><circle cx="278" cy="74" r="46" fill="%s" opacity=".25"/><circle cx="70" cy="258" r="58" fill="%s" opacity=".25"/><rect x="86" y="62" width="188" height="196" rx="28" fill="#fff" opacity=".86"/><rect x="118" y="92" width="124" height="74" rx="16" fill="%s" opacity=".16"/><text x="180" y="126" text-anchor="middle" font-family="Arial, sans-serif" font-size="34" font-weight="700" fill="%s">%s</text><text x="180" y="176" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" font-weight="700" fill="%s">Quick commerce</text><text x="180" y="202" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" fill="%s">style product art</text></svg>',
            $accent,
            $background,
            $background,
            $accent,
            $background,
            $accent,
            Str::before($label, ' '),
            $accent,
            $accent
        );

        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
    }
}
