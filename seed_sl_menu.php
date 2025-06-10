<?php
require 'Core/Database.php';

use Core\Database;

$db = new Database();
$conn = $db->connect();

// Clear existing tables to ensure clean slate
$conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
$conn->exec("TRUNCATE TABLE order_items;");
$conn->exec("TRUNCATE TABLE orders;");
$conn->exec("TRUNCATE TABLE menu_items;");
$conn->exec("TRUNCATE TABLE feedback;");
$conn->exec("TRUNCATE TABLE payments;");
$conn->exec("SET FOREIGN_KEY_CHECKS = 1;");

$menuItems = [
    // FOODS
    [
        'name' => 'Chicken Kottu Roti',
        'category' => 'Foods',
        'description' => 'A popular Sri Lankan street food made with chopped flatbread, vegetables, eggs, and spicy chicken curry.',
        'price' => 8.50,
        'image_url' => '/images/kottu_roti_1781941103129.png',
        'rating' => 4.9
    ],
    [
        'name' => 'Egg Hoppers',
        'category' => 'Foods',
        'description' => 'Crispy bowl-shaped fermented rice flour pancakes with a perfectly cooked egg in the center. Served with lunu miris.',
        'price' => 5.00,
        'image_url' => '/images/hoppers_1781941124210.png',
        'rating' => 4.8
    ],
    [
        'name' => 'Rice and Curry',
        'category' => 'Foods',
        'description' => 'The staple Sri Lankan meal. A hearty serving of rice accompanied by rich dhal, chicken curry, and fresh pol sambol.',
        'price' => 10.00,
        'image_url' => '/images/rice_and_curry_1781942642810.png',
        'rating' => 5.0
    ],
    [
        'name' => 'String Hoppers',
        'category' => 'Foods',
        'description' => 'Delicate nests of steamed rice flour noodles, perfect for soaking up spicy curries and creamy coconut gravy.',
        'price' => 6.50,
        'image_url' => '/images/string_hoppers_1781942657182.png',
        'rating' => 4.7
    ],
    [
        'name' => 'Pol Roti',
        'category' => 'Foods',
        'description' => 'Rustic coconut flatbread with diced onions and green chilies. Deliciously crispy and filling.',
        'price' => 4.50,
        'image_url' => null, // Placeholder
        'rating' => 4.6
    ],

    // DRINKS
    [
        'name' => 'Ceylon Tea',
        'category' => 'Drinks',
        'description' => 'World-renowned Sri Lankan black tea, served hot with a dash of milk and sugar.',
        'price' => 2.50,
        'image_url' => '/images/ceylon_tea_1781941167879.png',
        'rating' => 4.9
    ],
    [
        'name' => 'Falooda',
        'category' => 'Drinks',
        'description' => 'A sweet and refreshing rose syrup milk beverage with basil seeds, vermicelli, and vanilla ice cream.',
        'price' => 4.50,
        'image_url' => '/images/falooda_1781942670346.png',
        'rating' => 4.8
    ],
    [
        'name' => 'King Coconut Water (Thambili)',
        'category' => 'Drinks',
        'description' => 'Freshly cut sweet King Coconut from the tropical shores of Sri Lanka. Highly hydrating.',
        'price' => 3.00,
        'image_url' => null,
        'rating' => 4.7
    ],

    // DESSERTS
    [
        'name' => 'Watalappan',
        'category' => 'Desserts',
        'description' => 'A rich, steamed coconut custard pudding sweetened with kithul jaggery and spiced with cardamom and nutmeg.',
        'price' => 5.50,
        'image_url' => '/images/watalappan_1781941149307.png',
        'rating' => 5.0
    ],
    [
        'name' => 'Buffalo Curd with Treacle',
        'category' => 'Desserts',
        'description' => 'Thick, creamy buffalo milk curd drizzled with sweet kithul palm syrup (treacle).',
        'price' => 4.50,
        'image_url' => null,
        'rating' => 4.9
    ]
];

$stmt = $conn->prepare("INSERT INTO menu_items (name, category, description, price, image_url, rating) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($menuItems as $item) {
    $stmt->execute([
        $item['name'],
        $item['category'],
        $item['description'],
        $item['price'],
        $item['image_url'],
        $item['rating']
    ]);
}

echo "Successfully reseeded the database with an authentic Sri Lankan menu!\n";
