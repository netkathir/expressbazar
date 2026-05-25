<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StoreImage
{
    private const DEFAULT_CATEGORY_IMAGE = 'sample-assets/category/1560856145nLtLa.jpg';
    private const DEFAULT_PRODUCT_IMAGE = 'sample-assets/item/1560856518RMdFd.jpg';

    private const CATEGORY_IMAGES = [
        'baby car' => 'sample-assets/category/1561733454ZCS0m.jpg',
        'baby care' => 'sample-assets/category/1561733454ZCS0m.jpg',
        'baby food' => 'sample-assets/category/1561733454ZCS0m.jpg',
        'baby bath' => 'sample-assets/category/1561733454ZCS0m.jpg',
        'diapers wipes' => 'sample-assets/category/1561733454ZCS0m.jpg',
        'cleaning essentials' => 'sample-assets/category/15625748006IXJZ.png',
        'laundry care' => 'sample-assets/category/1562825923TzD9Q.jpg',
        'dishwash scrub' => 'sample-assets/category/15625748006IXJZ.png',
        'floor bath cleaners' => 'sample-assets/category/15625748006IXJZ.png',
        'cookie' => 'sample-assets/category/1561733928L2eHn.jpg',
        'biscuits cookies' => 'sample-assets/category/1561733928L2eHn.jpg',
        'frozen veg food' => 'sample-assets/category/1560859779RK4bY.jpg',
        'fruits vegetables' => 'sample-assets/category/1560856145nLtLa.jpg',
        'fruit vegetables' => 'sample-assets/category/1560856145nLtLa.jpg',
        'fresh fruits' => 'sample-assets/category/1560857551uiriM.jpg',
        'fresh vegetables' => 'sample-assets/category/1560859779RK4bY.jpg',
        'dairy bread eggs' => 'sample-assets/category/1560861603ePsNK.jpg',
        'milk curd' => 'sample-assets/category/15608618080l4QE.jpeg',
        'bread buns' => 'sample-assets/category/1560929944SMJxk.png',
        'eggs paneer' => 'sample-assets/category/1560935154Mm7OR.png',
        'rice grains' => 'sample-assets/category/1560935419PHbrl.jpg',
        'basmati rice' => 'sample-assets/category/1560943493CIInk.jpg',
        'staples pulses' => 'sample-assets/category/1561733454ZCS0m.jpg',
        'flour poha' => 'sample-assets/category/1561733492zQftZ.jpg',
        'snacks beverages' => 'sample-assets/category/1561733592dgkGo.png',
        'chips namkeen' => 'sample-assets/category/1561733656r26Pg.jpg',
        'soft drinks' => 'sample-assets/category/1561733849xc160.png',
        'hair care' => 'sample-assets/category/1560859779RK4bY.jpg',
        'hair serum' => 'sample-assets/category/1560859779RK4bY.jpg',
        'hair oil' => 'sample-assets/category/1560859779RK4bY.jpg',
        'shampoo conditioner' => 'sample-assets/category/1560859779RK4bY.jpg',
        'personal care' => 'sample-assets/category/1560929944SMJxk.png',
        'face wash' => 'sample-assets/category/1560929944SMJxk.png',
        'soaps body wash' => 'sample-assets/category/1560929944SMJxk.png',
        'oral care' => 'sample-assets/category/1560929944SMJxk.png',
        'kitchen home' => 'sample-assets/category/1561733849xc160.png',
        'cookware' => 'sample-assets/category/1561733849xc160.png',
        'storage containers' => 'sample-assets/category/1561733849xc160.png',
        'air fresheners' => 'sample-assets/category/1561733849xc160.png',
        'offer zone' => 'sample-assets/category/1560857551uiriM.jpg',
        'combo deals' => 'sample-assets/category/1560857551uiriM.jpg',
        'top offers' => 'sample-assets/category/1560857551uiriM.jpg',
        'new launches' => 'sample-assets/category/1560857551uiriM.jpg',
    ];

    private const CATEGORY_POOL = [
        'sample-assets/category/1560856145nLtLa.jpg',
        'sample-assets/category/1560857551uiriM.jpg',
        'sample-assets/category/1560861603ePsNK.jpg',
        'sample-assets/category/1560935419PHbrl.jpg',
        'sample-assets/category/1561733592dgkGo.png',
        'sample-assets/category/15625748006IXJZ.png',
        'sample-assets/category/1562825923TzD9Q.jpg',
    ];

    private const PRODUCT_POOL = [
        'sample-assets/item/1560856518RMdFd.jpg',
        'sample-assets/item/1560857145EFAwM.jpg',
        'sample-assets/item/1560858625tqYuA.jpg',
        'sample-assets/item/1560859214g936n.jpg',
        'sample-assets/item/1560860289I4Gx1.jpg',
        'sample-assets/item/1560860528DO7JJ.jpg',
        'sample-assets/item/1560860785fJY6e.jpg',
        'sample-assets/item/1560861150N3IXF.jpg',
        'sample-assets/item/1560862070wcUT4.jpg',
        'sample-assets/item/1560862566u21hJ.jpg',
        'sample-assets/item/1560862770uoLWX.jpg',
        'sample-assets/item/15609357569Erax.jpg',
        'sample-assets/item/1560935942MvVha.jpg',
        'sample-assets/item/15609361365kbbP.jpg',
        'sample-assets/item/1560936365e3F37.jpg',
        'sample-assets/item/1562826094FDO7O.jpg',
        'sample-assets/item/1563530383qrxzE.jpg',
        'sample-assets/item/1563530899vHm5j.jpg',
        'sample-assets/item/1563531160mqWuW.jpg',
        'sample-assets/item/1563531468JO8w5.jpg',
        'sample-assets/item/1563531957HW6N7.jpg',
        'sample-assets/item/1564030807U90zZ.jpg',
        'sample-assets/item/1564466264vpfs4.jpg',
        'sample-assets/item/1564473013eONmY.png',
    ];

    private const PRODUCT_IMAGES = [
        'apple' => 'sample-assets/item/1560856518RMdFd.jpg',
        'grapes' => 'sample-assets/item/1563531957HW6N7.jpg',
        'orange' => 'sample-assets/item/1563795544ZOqn3.jpg',
        'tomato' => 'sample-assets/item/1560857145EFAwM.jpg',
        'potato' => 'sample-assets/item/15637958910Asz3.jpg',
        'onion' => 'sample-assets/item/1563796090iBV5i.jpg',
        'milk' => 'sample-assets/item/1560858625tqYuA.jpg',
        'curd' => 'sample-assets/item/1564030807U90zZ.jpg',
        'bread' => 'sample-assets/item/1560859214g936n.jpg',
        'buns' => 'sample-assets/item/1564466264vpfs4.jpg',
        'eggs' => 'sample-assets/item/1564465975Pt3on.jpg',
        'paneer' => 'sample-assets/item/1564466125b29i3.jpg',
        'rice' => 'sample-assets/item/1560860289I4Gx1.jpg',
        'dal' => 'sample-assets/item/1560860528DO7JJ.jpg',
        'atta' => 'sample-assets/item/1564467878UbiOd.jpg',
        'poha' => 'sample-assets/item/1564468059CwTAJ.jpg',
        'chips' => 'sample-assets/item/1560860785fJY6e.jpg',
        'cookies' => 'sample-assets/item/1560861150N3IXF.jpg',
        'biscuits' => 'sample-assets/item/1564472795qjfRe.jpg',
        'juice' => 'sample-assets/item/1564469915XI3MH.jpg',
        'cola' => 'sample-assets/item/1564470663HmCuC.jpg',
        'laundry' => 'sample-assets/item/1560862070wcUT4.jpg',
        'detergent' => 'sample-assets/item/1564473013eONmY.png',
        'dishwash' => 'sample-assets/item/1560862566u21hJ.jpg',
        'scrub' => 'sample-assets/item/15609361365kbbP.jpg',
        'bathroom cleaner' => 'sample-assets/item/1564473449we8Af.jpg',
        'shampoo' => 'sample-assets/item/1560862770uoLWX.jpg',
        'hair oil' => 'sample-assets/item/15609357569Erax.jpg',
        'hair serum' => 'sample-assets/item/1560935942MvVha.jpg',
        'toothpaste' => 'sample-assets/item/1560935942MvVha.jpg',
        'toothbrush' => 'sample-assets/item/1563530383qrxzE.jpg',
        'soap' => 'sample-assets/item/15609361365kbbP.jpg',
        'body wash' => 'sample-assets/item/1562826094FDO7O.jpg',
        'face wash' => 'sample-assets/item/1560936365e3F37.jpg',
        'diapers' => 'sample-assets/item/1563531160mqWuW.jpg',
        'baby wipes' => 'sample-assets/item/1563531468JO8w5.jpg',
        'baby food' => 'sample-assets/item/1562826094FDO7O.jpg',
        'baby cereal' => 'sample-assets/item/1563530899vHm5j.jpg',
        'baby shampoo' => 'sample-assets/item/1560862770uoLWX.jpg',
        'pan' => 'sample-assets/item/1563530383qrxzE.jpg',
        'kadai' => 'sample-assets/item/1563531957HW6N7.jpg',
        'container' => 'sample-assets/item/1563530899vHm5j.jpg',
        'jar' => 'sample-assets/item/1563795544ZOqn3.jpg',
        'freshener' => 'sample-assets/item/15637958910Asz3.jpg',
        'combo' => 'sample-assets/item/1564030807U90zZ.jpg',
        'green tea' => 'sample-assets/item/1564465975Pt3on.jpg',
        'snack box' => 'sample-assets/item/1564468904sLHrg.jpg',
    ];

    public static function category(object|null $category): string
    {
        return asset(self::categoryPath($category));
    }

    public static function subcategory(object|null $subcategory): string
    {
        return asset(self::categoryPath($subcategory));
    }

    public static function product(object|null $product): string
    {
        return asset(self::productPath($product));
    }

    public static function productGallery(object|null $product): Collection
    {
        $preferredPath = self::preferredProductPath($product);

        if ($preferredPath && self::isSampleProduct($product)) {
            return collect(array_values(array_unique([
                $preferredPath,
                ...self::fallbackProductPaths($product, 2),
            ])))->take(2)->map(fn ($path) => (object) ['image_path' => $path]);
        }

        $paths = collect($product?->images ?? [])
            ->pluck('image_path')
            ->filter(fn ($path) => self::isUsable($path))
            ->values();

        if ($paths->isNotEmpty()) {
            return $paths->map(fn ($path) => (object) ['image_path' => self::cleanPath($path)]);
        }

        return collect(self::fallbackProductPaths($product, 2))
            ->map(fn ($path) => (object) ['image_path' => $path]);
    }

    public static function onError(string $type = 'product'): string
    {
        $fallback = $type === 'category' ? self::categoryFallback() : self::productFallback();

        return "this.onerror=null;this.src='{$fallback}';";
    }

    private static function categoryPath(object|null $category): string
    {
        $name = self::nameFor($category, ['category_name', 'subcategory_name', 'name']);
        $normalized = self::normalize($name);

        foreach (self::CATEGORY_IMAGES as $keyword => $path) {
            if (Str::contains($normalized, $keyword)) {
                return $path;
            }
        }

        if (self::isUsable($category?->image_path ?? null)) {
            return self::cleanPath($category->image_path);
        }

        return self::fallbackFromPool(self::CATEGORY_POOL, $name, (int) ($category?->id ?? 0));
    }

    private static function productPath(object|null $product): string
    {
        $preferredPath = self::preferredProductPath($product);

        if ($preferredPath && self::isSampleProduct($product)) {
            return $preferredPath;
        }

        $imagePath = collect($product?->images ?? [])
            ->pluck('image_path')
            ->first(fn ($path) => self::isUsable($path));

        if ($imagePath) {
            return self::cleanPath($imagePath);
        }

        if ($preferredPath && self::isSampleProduct($product)) {
            return $preferredPath;
        }

        return self::fallbackProductPaths($product, 1)[0];
    }

    private static function preferredProductPath(object|null $product): ?string
    {
        $name = self::normalize(implode(' ', array_filter([
            self::nameFor($product, ['product_name', 'name']),
            self::nameFor($product?->subcategory ?? null, ['subcategory_name', 'name']),
            self::nameFor($product?->category ?? null, ['category_name', 'name']),
        ])));

        foreach (self::PRODUCT_IMAGES as $keyword => $path) {
            if (Str::contains($name, $keyword)) {
                return $path;
            }
        }

        return null;
    }

    private static function categoryFallback(): string
    {
        return asset(self::DEFAULT_CATEGORY_IMAGE);
    }

    private static function productFallback(): string
    {
        return asset(self::DEFAULT_PRODUCT_IMAGE);
    }

    private static function fallbackProductPaths(object|null $product, int $count): array
    {
        $name = implode(' ', array_filter([
            self::nameFor($product, ['product_name', 'name']),
            self::nameFor($product?->subcategory ?? null, ['subcategory_name', 'name']),
            self::nameFor($product?->category ?? null, ['category_name', 'name']),
            self::nameFor($product?->vendor ?? null, ['vendor_name', 'name']),
            (string) ($product?->vendor_id ?? ''),
        ]));

        $start = self::poolIndex(self::PRODUCT_POOL, $name, (int) ($product?->id ?? 0));
        $paths = [];

        for ($offset = 0; $offset < $count; $offset++) {
            $paths[] = self::PRODUCT_POOL[($start + $offset) % count(self::PRODUCT_POOL)];
        }

        return array_values(array_unique($paths));
    }

    private static function isUsable(string|null $path): bool
    {
        if (! filled($path)) {
            return false;
        }

        $cleanPath = self::cleanPath($path);

        if (Str::contains($cleanPath, ['admin-theme/assets/images/product-', 'placeholder', 'default'])) {
            return false;
        }

        if (Str::startsWith($cleanPath, ['http://', 'https://'])) {
            return true;
        }

        return is_file(public_path($cleanPath));
    }

    private static function cleanPath(string $path): string
    {
        return ltrim($path, '/');
    }

    private static function nameFor(object|null $model, array $fields): string
    {
        foreach ($fields as $field) {
            if (filled($model?->{$field} ?? null)) {
                return (string) $model->{$field};
            }
        }

        return '';
    }

    private static function normalize(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private static function fallbackFromPool(array $pool, string $name, int $id): string
    {
        return $pool[self::poolIndex($pool, $name, $id)];
    }

    private static function poolIndex(array $pool, string $name, int $id): int
    {
        $hash = abs((int) crc32($name !== '' ? $name : (string) $id));

        return $hash % count($pool);
    }

    private static function isSampleProduct(object|null $product): bool
    {
        return Str::startsWith(self::nameFor($product, ['product_name', 'name']), 'Sample ');
    }
}
