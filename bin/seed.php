<?php

use Bazaar\Core\Config;
use Bazaar\Core\Database;
use Bazaar\Repositories\AttributeRepository;
use Bazaar\Repositories\CategoryRepository;
use Bazaar\Repositories\CouponRepository;
use Bazaar\Repositories\OrderRepository;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\ReviewRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Repositories\VariantRepository;
use Bazaar\Support\Role;

require dirname(__DIR__) . '/vendor/autoload.php';

Config::load(basePath('.env'));

$db = Database::instance();
$users = new UserRepository($db);
$categories = new CategoryRepository($db);
$products = new ProductRepository($db);
$attributes = new AttributeRepository($db);
$variants = new VariantRepository($db);
$coupons = new CouponRepository($db);
$orders = new OrderRepository($db);
$reviews = new ReviewRepository($db);

if ($products->count() > 0) {
    echo "Database already seeded. Delete database/bazaar.sqlite to reseed.\n";
    exit(0);
}

if (!$users->emailExists('admin@bazaar.test')) {
    $users->create('admin@bazaar.test', password_hash('adminpass123', PASSWORD_DEFAULT), 'Store Admin', true);
}
if (!$users->emailExists('customer@bazaar.test')) {
    $users->create('customer@bazaar.test', password_hash('password123', PASSWORD_DEFAULT), 'Sample Customer', false);
}
if (!$users->emailExists('manager@bazaar.test')) {
    $users->create('manager@bazaar.test', password_hash('managerpass123', PASSWORD_DEFAULT), 'Store Manager', false, Role::manager);
}

$catalog = [
    'Coffee and Tea' => [
        'description' => 'Small-batch roasts and loose leaf worth slowing down for.',
        'products' => [
            ['Ethiopia Yirgacheffe Whole Bean', 1850, 40, 'Bright, floral single origin with notes of jasmine and stone fruit. 250g bag, roasted to order.'],
            ['Cold Brew Concentrate', 1400, 26, 'Steeped 18 hours for a smooth, low-acid concentrate. Just add water or milk. 500ml bottle.'],
            ['Cast Iron Stovetop Kettle', 5900, 8, 'A 1.2L enameled kettle that whistles softly and pours precisely. Built to outlive its owner.'],
            ['Sencha Green Tea Tin', 1600, 3, 'Grassy, vegetal first-flush sencha in a resealable tin. 100g of everyday calm.'],
        ],
    ],
    'Kitchen' => [
        'description' => 'Honest tools that earn their place on the counter.',
        'products' => [
            ['Walnut Cutting Board', 4800, 14, 'End-grain walnut board that is gentle on knives and ages beautifully. 40 x 28cm.'],
            ['Chef Knife 8 inch', 8900, 6, 'Full-tang high-carbon steel with a comfortable pakkawood handle. Ships razor sharp.'],
            ['Linen Apron', 3200, 22, 'Heavyweight stonewashed linen with an adjustable neck and deep front pocket.'],
            ['Ceramic Mixing Bowl Set', 3900, 0, 'Nesting set of three glazed stoneware bowls. Oven, microwave and dishwasher safe.'],
        ],
    ],
    'Desk and Paper' => [
        'description' => 'Stationery for people who still like the feel of a good pen.',
        'products' => [
            ['Dot Grid Notebook A5', 1200, 60, 'Lay-flat binding, 160 pages of 90gsm dot grid paper that shrugs off fountain pen ink.'],
            ['Brass Fountain Pen', 6400, 11, 'A solid brass pen that develops a patina with use. Medium nib, converter included.'],
            ['Desk Organizer Tray', 2600, 18, 'Powder-coated steel tray to corral pens, cards and the small chaos of a desk.'],
        ],
    ],
    'Home' => [
        'description' => 'Quiet upgrades for the rooms you actually live in.',
        'products' => [
            ['Wool Throw Blanket', 7200, 9, 'Lambswool throw woven in a soft herringbone. Warm without the weight. 130 x 180cm.'],
            ['Soy Candle Cedar and Moss', 2200, 35, 'Hand-poured soy wax with a wood wick and a 50 hour burn. Grounding cedar and green moss.'],
            ['Stoneware Planter', 2900, 4, 'A matte-glazed planter with a drainage tray, sized for a mid-century leafy friend.'],
        ],
    ],
];

foreach ($catalog as $categoryName => $group) {
    $categoryId = $categories->create($categoryName, slugify($categoryName), $group['description']);
    foreach ($group['products'] as [$name, $priceCents, $stock, $description]) {
        $products->create([
            'categoryId' => $categoryId,
            'name' => $name,
            'slug' => slugify($name),
            'description' => $description,
            'priceCents' => $priceCents,
            'stockQuantity' => $stock,
            'imageUrl' => '',
            'isActive' => true,
        ]);
    }
}

$sizeId = $attributes->create('Size', 'size');
$sizeValues = [
    'Small' => $attributes->createValue($sizeId, 'Small', 'small', 1),
    'Medium' => $attributes->createValue($sizeId, 'Medium', 'medium', 2),
    'Large' => $attributes->createValue($sizeId, 'Large', 'large', 3),
];
$colorId = $attributes->create('Color', 'color');
$colorValues = [
    'Charcoal' => $attributes->createValue($colorId, 'Charcoal', 'charcoal', 1),
    'Sand' => $attributes->createValue($colorId, 'Sand', 'sand', 2),
];

$apron = $products->findBySlug('linen-apron');
if ($apron !== null) {
    foreach (['Small' => 8, 'Medium' => 6, 'Large' => 3] as $size => $stock) {
        $variantId = $variants->create((int) $apron['id'], 'APRON-' . strtoupper(substr($size, 0, 1)), 3200, $stock);
        $variants->setOption($variantId, $sizeId, $sizeValues[$size]);
    }
}

$throw = $products->findBySlug('wool-throw-blanket');
if ($throw !== null) {
    foreach (['Charcoal' => 7200, 'Sand' => 7600] as $color => $priceCents) {
        $variantId = $variants->create((int) $throw['id'], 'THROW-' . strtoupper(substr($color, 0, 3)), $priceCents, 5);
        $variants->setOption($variantId, $colorId, $colorValues[$color]);
    }
}

$coupons->create([
    'code' => 'WELCOME10',
    'type' => 'percent',
    'value' => 10,
    'minSubtotalCents' => 2000,
    'maxRedemptions' => 100,
    'startsAt' => null,
    'expiresAt' => null,
    'isActive' => true,
]);
$coupons->create([
    'code' => 'SHIP5',
    'type' => 'fixed',
    'value' => 500,
    'minSubtotalCents' => 3000,
    'maxRedemptions' => null,
    'startsAt' => null,
    'expiresAt' => null,
    'isActive' => true,
]);

$customer = $users->findByEmail('customer@bazaar.test');
$candle = $products->findBySlug('soy-candle-cedar-and-moss');
if ($customer !== null && $candle !== null) {
    $orderId = $orders->create((int) $customer['id'], 4400, 'usd');
    $orders->addItem($orderId, (int) $candle['id'], $candle['name'], 2200, 2);
    $orders->setStatus($orderId, 'paid', 'paidAt');
    $products->decrementStock((int) $candle['id'], 2);

    $reviewId = $reviews->create((int) $candle['id'], (int) $customer['id'], 5, 'Warm and grounding', 'Burns evenly and the cedar scent fills the room without being overpowering.');
    $reviews->setStatus($reviewId, 'approved');
}

echo "Seed complete.\n";
echo "  Admin login:    admin@bazaar.test / adminpass123\n";
echo "  Manager login:  manager@bazaar.test / managerpass123\n";
echo "  Customer login: customer@bazaar.test / password123\n";
echo "  Products: " . $products->count() . " | Coupons: WELCOME10, SHIP5\n";
