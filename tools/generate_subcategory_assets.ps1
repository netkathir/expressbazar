$ErrorActionPreference = 'Stop'

$items = @(
    @{ Id = 46; Name = 'Baby Food'; Group = 'baby' },
    @{ Id = 47; Name = 'Baby Bath & Shampoo'; Group = 'baby' },
    @{ Id = 48; Name = 'Diapers & Wipes'; Group = 'baby' },
    @{ Id = 49; Name = 'Baby Lotion & Cream'; Group = 'baby' },
    @{ Id = 50; Name = 'Baby Powder'; Group = 'baby' },
    @{ Id = 51; Name = 'Feeding Bottles'; Group = 'baby' },
    @{ Id = 52; Name = 'Baby Accessories'; Group = 'baby' },
    @{ Id = 53; Name = 'Baby Health & Safety'; Group = 'baby' },
    @{ Id = 54; Name = 'Floor Cleaners'; Group = 'cleaning' },
    @{ Id = 55; Name = 'Bathroom Cleaners'; Group = 'cleaning' },
    @{ Id = 56; Name = 'Kitchen Cleaners'; Group = 'cleaning' },
    @{ Id = 57; Name = 'Dishwash Liquids'; Group = 'cleaning' },
    @{ Id = 58; Name = 'Scrub Pads & Brushes'; Group = 'cleaning' },
    @{ Id = 59; Name = 'Laundry Detergents'; Group = 'cleaning' },
    @{ Id = 60; Name = 'Fabric Conditioners'; Group = 'cleaning' },
    @{ Id = 61; Name = 'Air Fresheners'; Group = 'cleaning' },
    @{ Id = 62; Name = 'Cleaning Tools'; Group = 'cleaning' },
    @{ Id = 63; Name = 'Chocolate Cookies'; Group = 'bakery' },
    @{ Id = 64; Name = 'Butter Cookies'; Group = 'bakery' },
    @{ Id = 65; Name = 'Healthy Cookies'; Group = 'bakery' },
    @{ Id = 66; Name = 'Cream Biscuits'; Group = 'bakery' },
    @{ Id = 67; Name = 'Sugar-Free Cookies'; Group = 'bakery' },
    @{ Id = 68; Name = 'Oats Cookies'; Group = 'bakery' },
    @{ Id = 69; Name = 'Dry Fruit Cookies'; Group = 'bakery' },
    @{ Id = 70; Name = 'Kids Cookies'; Group = 'bakery' },
    @{ Id = 71; Name = 'Milk'; Group = 'dairy' },
    @{ Id = 72; Name = 'Curd & Yogurt'; Group = 'dairy' },
    @{ Id = 73; Name = 'Butter & Cheese'; Group = 'dairy' },
    @{ Id = 74; Name = 'Paneer'; Group = 'dairy' },
    @{ Id = 75; Name = 'Fresh Bread'; Group = 'bakery' },
    @{ Id = 76; Name = 'Buns & Pav'; Group = 'bakery' },
    @{ Id = 77; Name = 'Brown Bread'; Group = 'bakery' },
    @{ Id = 78; Name = 'Eggs'; Group = 'dairy' },
    @{ Id = 79; Name = 'Dairy Beverages'; Group = 'dairy' },
    @{ Id = 80; Name = 'Frozen Vegetables'; Group = 'frozen' },
    @{ Id = 81; Name = 'Frozen Snacks'; Group = 'frozen' },
    @{ Id = 82; Name = 'Frozen Peas & Corn'; Group = 'frozen' },
    @{ Id = 83; Name = 'Ready to Cook'; Group = 'frozen' },
    @{ Id = 84; Name = 'Frozen French Fries'; Group = 'frozen' },
    @{ Id = 85; Name = 'Frozen Paratha'; Group = 'frozen' },
    @{ Id = 86; Name = 'Frozen Seafood'; Group = 'frozen' },
    @{ Id = 87; Name = 'Frozen Meat'; Group = 'frozen' },
    @{ Id = 88; Name = 'Fresh Fruits'; Group = 'produce' },
    @{ Id = 89; Name = 'Fresh Vegetables'; Group = 'produce' },
    @{ Id = 90; Name = 'Leafy Vegetables'; Group = 'produce' },
    @{ Id = 91; Name = 'Exotic Fruits'; Group = 'produce' },
    @{ Id = 92; Name = 'Exotic Vegetables'; Group = 'produce' },
    @{ Id = 93; Name = 'Organic Vegetables'; Group = 'produce' },
    @{ Id = 94; Name = 'Herbs & Seasonings'; Group = 'produce' },
    @{ Id = 95; Name = 'Cut & Packed Fruits'; Group = 'produce' },
    @{ Id = 96; Name = 'Hair Oil'; Group = 'hair' },
    @{ Id = 97; Name = 'Shampoo'; Group = 'hair' },
    @{ Id = 98; Name = 'Conditioner'; Group = 'hair' },
    @{ Id = 99; Name = 'Hair Serum'; Group = 'hair' },
    @{ Id = 100; Name = 'Hair Color'; Group = 'hair' },
    @{ Id = 101; Name = 'Hair Gel & Wax'; Group = 'hair' },
    @{ Id = 102; Name = 'Hair Masks'; Group = 'hair' },
    @{ Id = 103; Name = 'Anti-Dandruff Products'; Group = 'hair' },
    @{ Id = 104; Name = 'Family Packs'; Group = 'icecream' },
    @{ Id = 105; Name = 'Cups & Tubs'; Group = 'icecream' },
    @{ Id = 106; Name = 'Cones'; Group = 'icecream' },
    @{ Id = 107; Name = 'Ice Cream Bars'; Group = 'icecream' },
    @{ Id = 108; Name = 'Kulfi'; Group = 'icecream' },
    @{ Id = 109; Name = 'Gelato'; Group = 'icecream' },
    @{ Id = 110; Name = 'Sugar-Free Ice Cream'; Group = 'icecream' },
    @{ Id = 111; Name = 'Kids Ice Cream'; Group = 'icecream' },
    @{ Id = 112; Name = 'Kitchen Tools'; Group = 'home' },
    @{ Id = 113; Name = 'Storage Containers'; Group = 'home' },
    @{ Id = 114; Name = 'Cookware'; Group = 'home' },
    @{ Id = 115; Name = 'Dinner Sets'; Group = 'home' },
    @{ Id = 116; Name = 'Water Bottles'; Group = 'home' },
    @{ Id = 117; Name = 'Home Utility'; Group = 'home' },
    @{ Id = 118; Name = 'Disposable Products'; Group = 'home' },
    @{ Id = 119; Name = 'Cleaning Accessories'; Group = 'cleaning' },
    @{ Id = 120; Name = 'Almonds'; Group = 'nuts' },
    @{ Id = 121; Name = 'Cashews'; Group = 'nuts' },
    @{ Id = 122; Name = 'Pistachios'; Group = 'nuts' },
    @{ Id = 123; Name = 'Walnuts'; Group = 'nuts' },
    @{ Id = 124; Name = 'Mixed Nuts'; Group = 'nuts' },
    @{ Id = 125; Name = 'Raisins & Dry Fruits'; Group = 'nuts' },
    @{ Id = 126; Name = 'Roasted Nuts'; Group = 'nuts' },
    @{ Id = 127; Name = 'Salted Nuts'; Group = 'nuts' },
    @{ Id = 128; Name = 'Combo Offers'; Group = 'offers' },
    @{ Id = 129; Name = 'Buy 1 Get 1'; Group = 'offers' },
    @{ Id = 130; Name = 'Discount Deals'; Group = 'offers' },
    @{ Id = 131; Name = 'Festival Offers'; Group = 'offers' },
    @{ Id = 132; Name = 'Clearance Sale'; Group = 'offers' },
    @{ Id = 133; Name = 'Daily Essentials Offers'; Group = 'offers' },
    @{ Id = 134; Name = 'Weekend Specials'; Group = 'offers' },
    @{ Id = 135; Name = 'Top Savings'; Group = 'offers' },
    @{ Id = 136; Name = 'Bath Soap'; Group = 'personal' },
    @{ Id = 137; Name = 'Body Wash'; Group = 'personal' },
    @{ Id = 138; Name = 'Face Wash'; Group = 'personal' },
    @{ Id = 139; Name = 'Skin Care'; Group = 'personal' },
    @{ Id = 140; Name = 'Oral Care'; Group = 'personal' },
    @{ Id = 141; Name = 'Deodorants'; Group = 'personal' },
    @{ Id = 142; Name = 'Feminine Hygiene'; Group = 'personal' },
    @{ Id = 143; Name = 'Grooming Products'; Group = 'personal' },
    @{ Id = 144; Name = 'Basmati Rice'; Group = 'grains' },
    @{ Id = 145; Name = 'Raw Rice'; Group = 'grains' },
    @{ Id = 146; Name = 'Millets'; Group = 'grains' },
    @{ Id = 147; Name = 'Wheat'; Group = 'grains' },
    @{ Id = 148; Name = 'Flour'; Group = 'grains' },
    @{ Id = 149; Name = 'Dals & Pulses'; Group = 'grains' },
    @{ Id = 150; Name = 'Poha & Vermicelli'; Group = 'grains' },
    @{ Id = 151; Name = 'Organic Grains'; Group = 'grains' },
    @{ Id = 152; Name = 'Chips & Namkeen'; Group = 'snacks' },
    @{ Id = 153; Name = 'Chocolates'; Group = 'snacks' },
    @{ Id = 154; Name = 'Soft Drinks'; Group = 'drinks' },
    @{ Id = 155; Name = 'Juices'; Group = 'drinks' },
    @{ Id = 156; Name = 'Tea & Coffee'; Group = 'drinks' },
    @{ Id = 157; Name = 'Energy Drinks'; Group = 'drinks' },
    @{ Id = 158; Name = 'Instant Drinks'; Group = 'drinks' },
    @{ Id = 159; Name = 'Healthy Snacks'; Group = 'snacks' }
)

$themes = @{
    baby = @{ A = '#F8D7DA'; B = '#FFE8A3'; C = '#B86B75'; Icon = 'M96 170c35-44 77-44 112 0M107 140c20-21 39-31 61-31s41 10 61 31M132 180c11 9 24 14 36 14s25-5 36-14' }
    cleaning = @{ A = '#D7F3F0'; B = '#9ADBE8'; C = '#1C7885'; Icon = 'M108 86h100l-12 84a32 32 0 0 1-32 28h-28a32 32 0 0 1-32-28l-12-84ZM124 86l8-26h44l8 26M126 126h84M132 158h72' }
    bakery = @{ A = '#FBE2B8'; B = '#F5B66E'; C = '#8A4B16'; Icon = 'M86 154c0-36 35-66 78-66s78 30 78 66v26H86v-26ZM120 118c8 10 18 16 30 18M168 104c10 12 22 20 38 22' }
    dairy = @{ A = '#E6F0FF'; B = '#B9D7FF'; C = '#2366A8'; Icon = 'M120 90h72l16 38v74H104v-74l16-38ZM128 90V64h56v26M104 132h104M132 162h40' }
    frozen = @{ A = '#DDF7FF'; B = '#BFE7F6'; C = '#26708D'; Icon = 'M154 72v128M100 100l108 72M208 100l-108 72M122 86l32 26 32-26M122 186l32-26 32 26' }
    produce = @{ A = '#D9F4C7'; B = '#8ED66D'; C = '#246B2E'; Icon = 'M162 196c-43-8-72-38-72-78 46-3 75 17 87 56M160 190c-4-53 24-91 77-101 8 50-17 88-77 101M158 112c0-20 12-36 31-44' }
    hair = @{ A = '#F2D7FF'; B = '#D4A5F3'; C = '#6D3C8D'; Icon = 'M112 82h96v126h-96zM132 82V58h56v24M132 112h56M132 142h56M132 172h36' }
    icecream = @{ A = '#FFE0EC'; B = '#FFC7A8'; C = '#B94F69'; Icon = 'M118 102a48 48 0 0 1 96 0c0 17-9 32-22 41l-46 74-46-74c-13-9-22-24-22-41ZM112 146h104' }
    home = @{ A = '#E9E3D5'; B = '#CBBDA0'; C = '#66563A'; Icon = 'M92 140l62-58 62 58v74H110v-74M136 214v-48h44v48M116 116V82h34' }
    nuts = @{ A = '#F6E2C5'; B = '#D69B5F'; C = '#7A4B23'; Icon = 'M112 158c0-38 24-74 52-74s52 36 52 74-24 60-52 60-52-22-52-60ZM144 112c15 22 15 56 0 86M174 112c15 22 15 56 0 86' }
    offers = @{ A = '#FFE8A8'; B = '#FFB85C'; C = '#8F4B00'; Icon = 'M100 104h104v104H100zM100 132h104M152 104v104M126 98c-15-22 9-36 28-10M174 98c15-22-9-36-28-10' }
    personal = @{ A = '#E8E1FF'; B = '#BFC5FF'; C = '#4C539E'; Icon = 'M116 94h92v126h-92zM132 94V66h56v28M140 126h44M140 156h44M140 186h28' }
    grains = @{ A = '#FFF1BE'; B = '#DDBB62'; C = '#75570F'; Icon = 'M154 72v144M118 100c28 4 44 20 36 54-28-4-44-20-36-54ZM190 100c-28 4-44 20-36 54 28-4 44-20 36-54ZM118 156c28 4 44 20 36 54-28-4-44-20-36-54ZM190 156c-28 4-44 20-36 54 28-4 44-20 36-54Z' }
    snacks = @{ A = '#FFE2CA'; B = '#F69E70'; C = '#90411E'; Icon = 'M108 82h100l18 136H90l18-136ZM124 112h84M130 150h72M136 186h60' }
    drinks = @{ A = '#DFF8E9'; B = '#8ADCB5'; C = '#1D704E'; Icon = 'M118 82h90l-16 136h-58L118 82ZM130 118h76M154 82l20-34h48' }
}

function ConvertTo-Slug([string] $value) {
    $slug = $value.ToLowerInvariant() -replace '&', 'and'
    $slug = $slug -replace '[^a-z0-9]+', '-'
    return $slug.Trim('-')
}

function Escape-Sql([string] $value) {
    return $value.Replace("'", "''")
}

function Escape-Xml([string] $value) {
    return [System.Security.SecurityElement]::Escape($value)
}

$outputDir = Join-Path $PSScriptRoot '..\public\uploads\subcategories'
$sqlPath = Join-Path $PSScriptRoot '..\database\subcategory_image_paths.sql'
New-Item -ItemType Directory -Force -Path $outputDir | Out-Null
New-Item -ItemType Directory -Force -Path (Split-Path $sqlPath) | Out-Null

$sqlLines = New-Object System.Collections.Generic.List[string]
$sqlLines.Add('-- Live-safe subcategory image path patch.')
$sqlLines.Add('-- Upload public/uploads/subcategories/* to the same path on live before running this.')
$sqlLines.Add('-- It only fills blank image_path values and leaves existing custom images untouched.')
$sqlLines.Add('START TRANSACTION;')

foreach ($item in $items) {
    $theme = $themes[$item.Group]
    $slug = ConvertTo-Slug $item.Name
    $fileName = "$($item.Id)-$slug.svg"
    $relativePath = "uploads/subcategories/$fileName"
    $filePath = Join-Path $outputDir $fileName
    $title = Escape-Xml $item.Name

    $svg = @"
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="480" viewBox="0 0 320 240" role="img" aria-labelledby="title desc">
  <title id="title">$title</title>
  <desc id="desc">ExpressBazaar subcategory image for $title</desc>
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="$($theme.A)"/>
      <stop offset="1" stop-color="$($theme.B)"/>
    </linearGradient>
  </defs>
  <rect width="320" height="240" rx="28" fill="url(#bg)"/>
  <circle cx="270" cy="44" r="62" fill="#ffffff" opacity=".28"/>
  <circle cx="48" cy="214" r="70" fill="#ffffff" opacity=".2"/>
  <path d="$($theme.Icon)" fill="none" stroke="$($theme.C)" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>
  <rect x="24" y="24" width="88" height="26" rx="13" fill="#ffffff" opacity=".72"/>
  <text x="44" y="42" font-family="Arial, Helvetica, sans-serif" font-size="12" font-weight="700" fill="$($theme.C)">ExpressBazaar</text>
</svg>
"@
    Set-Content -Path $filePath -Value $svg -Encoding UTF8

    $sqlName = Escape-Sql $item.Name
    $sqlLines.Add("UPDATE `subcategories` SET `image_path` = '$relativePath' WHERE `id` = $($item.Id) AND `subcategory_name` = '$sqlName' AND (`image_path` IS NULL OR `image_path` = '');")
}

$sqlLines.Add('COMMIT;')
Set-Content -Path $sqlPath -Value $sqlLines -Encoding UTF8

Write-Host "Generated $($items.Count) subcategory SVG files in $outputDir"
Write-Host "Generated SQL patch at $sqlPath"
