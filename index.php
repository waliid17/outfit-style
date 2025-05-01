<?php
// Database configuration
$host = 'localhost';
$dbname = 'outfit_style';
$username = 'root';
$password = '';

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get product ID from URL or use default (1)
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Fetch product information
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found");
}

// Fetch color options
$stmt = $pdo->prepare("SELECT * FROM product_colors WHERE product_id = ?");
$stmt->execute([$productId]);
$colors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare colors array
$colorsArray = [];
foreach ($colors as $color) {
    $key = strtolower(str_replace(' ', '_', $color['color_name']));
    $colorsArray[$key] = [
        'name' => $color['color_name'],
        'hex' => $color['color_hex'],
        'image' => $color['image_path'] ?? 'images/default-product.jpg'
    ];
}

// Fetch available sizes
$stmt = $pdo->prepare("SELECT size FROM product_sizes WHERE product_id = ? ORDER BY size");
$stmt->execute([$productId]);
$sizes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Wilayas and their delivery prices (complete list of 58 wilayas)
$wilayas = [
    ['code' => 1, 'name' => 'ADRAR', 'domicile' => 1400, 'stopdesk' => 970],
    ['code' => 2, 'name' => 'CHLEF', 'domicile' => 850, 'stopdesk' => 520],
    ['code' => 3, 'name' => 'LAGHOUAT', 'domicile' => 950, 'stopdesk' => 620],
    ['code' => 4, 'name' => 'OUM EL BOUAGHI', 'domicile' => 850, 'stopdesk' => 520],
    ['code' => 5, 'name' => 'BATNA', 'domicile' => 900, 'stopdesk' => 520],
    ['code' => 6, 'name' => 'BEJAIA', 'domicile' => 800, 'stopdesk' => 520],
    ['code' => 7, 'name' => 'BISKRA', 'domicile' => 950, 'stopdesk' => 620],
    ['code' => 8, 'name' => 'BECHAR', 'domicile' => 1100, 'stopdesk' => 720],
    ['code' => 9, 'name' => 'BLIDA', 'domicile' => 600, 'stopdesk' => 470],
    ['code' => 10, 'name' => 'BOUIRA', 'domicile' => 700, 'stopdesk' => 520],
    ['code' => 11, 'name' => 'TAMANRASSET', 'domicile' => 1600, 'stopdesk' => 1120],
    ['code' => 12, 'name' => 'TEBESSA', 'domicile' => 900, 'stopdesk' => 570],
    ['code' => 13, 'name' => 'TLEMCEN', 'domicile' => 900, 'stopdesk' => 570],
    ['code' => 14, 'name' => 'TIARET', 'domicile' => 850, 'stopdesk' => 520],
    ['code' => 15, 'name' => 'TIZI OUZOU', 'domicile' => 750, 'stopdesk' => 520],
    ['code' => 16, 'name' => 'ALGER', 'domicile' => 500, 'stopdesk' => 370],
    ['code' => 17, 'name' => 'DJELFA', 'domicile' => 950, 'stopdesk' => 570],
    ['code' => 18, 'name' => 'JIJEL', 'domicile' => 900, 'stopdesk' => 520],
    ['code' => 19, 'name' => 'SETIF', 'domicile' => 800, 'stopdesk' => 520],
    ['code' => 20, 'name' => 'SAIDA', 'domicile' => 900, 'stopdesk' => 570],
    ['code' => 21, 'name' => 'SKIKDA', 'domicile' => 900, 'stopdesk' => 520],
    ['code' => 22, 'name' => 'SIDI BEL ABBES', 'domicile' => 900, 'stopdesk' => 520],
    ['code' => 23, 'name' => 'ANNABA', 'domicile' => 850, 'stopdesk' => 520],
    ['code' => 24, 'name' => 'GUELMA', 'domicile' => 900, 'stopdesk' => 520],
    ['code' => 25, 'name' => 'CONSTANTINE', 'domicile' => 800, 'stopdesk' => 520],
    ['code' => 26, 'name' => 'MEDEA', 'domicile' => 800, 'stopdesk' => 520],
    ['code' => 27, 'name' => 'MOSTAGANEM', 'domicile' => 900, 'stopdesk' => 520],
    ['code' => 28, 'name' => 'M\'SILA', 'domicile' => 850, 'stopdesk' => 570],
    ['code' => 29, 'name' => 'MASCARA', 'domicile' => 900, 'stopdesk' => 520],
    ['code' => 30, 'name' => 'OUARGLA', 'domicile' => 950, 'stopdesk' => 670],
    ['code' => 31, 'name' => 'ORAN', 'domicile' => 800, 'stopdesk' => 520],
    ['code' => 32, 'name' => 'EL BAYADH', 'domicile' => 1100, 'stopdesk' => 670],
    ['code' => 33, 'name' => 'ILLIZI', 'domicile' => null, 'stopdesk' => null],
    ['code' => 34, 'name' => 'BORDJ BOU ARRERIDJ', 'domicile' => 800, 'stopdesk' => 520],
    ['code' => 35, 'name' => 'BOUMERDES', 'domicile' => 700, 'stopdesk' => 520],
    ['code' => 36, 'name' => 'EL TARF', 'domicile' => 850, 'stopdesk' => 520],
    ['code' => 37, 'name' => 'TINDOUF', 'domicile' => null, 'stopdesk' => null],
    ['code' => 38, 'name' => 'TISSEMSILT', 'domicile' => 900, 'stopdesk' => null],
    ['code' => 39, 'name' => 'EL OUED', 'domicile' => 950, 'stopdesk' => 670],
    ['code' => 40, 'name' => 'KHENCHELA', 'domicile' => 900, 'stopdesk' => null],
    ['code' => 41, 'name' => 'SOUK AHRAS', 'domicile' => 900, 'stopdesk' => 520],
    ['code' => 42, 'name' => 'TIPAZA', 'domicile' => 700, 'stopdesk' => 520],
    ['code' => 43, 'name' => 'MILA', 'domicile' => 900, 'stopdesk' => 520],
    ['code' => 44, 'name' => 'AIN DEFLA', 'domicile' => 900, 'stopdesk' => 520],
    ['code' => 45, 'name' => 'NAAMA', 'domicile' => 1100, 'stopdesk' => 670],
    ['code' => 46, 'name' => 'AIN TEMOUCHENT', 'domicile' => 900, 'stopdesk' => 520],
    ['code' => 47, 'name' => 'GHARDAIA', 'domicile' => 950, 'stopdesk' => 620],
    ['code' => 48, 'name' => 'RELIZANE', 'domicile' => 900, 'stopdesk' => 520],
    ['code' => 49, 'name' => 'TIMIMOUN', 'domicile' => 1400, 'stopdesk' => null],
    ['code' => 50, 'name' => 'BORDJ BADJI MOKHTAR', 'domicile' => null, 'stopdesk' => null],
    ['code' => 51, 'name' => 'OULED DJELLAL', 'domicile' => 950, 'stopdesk' => 620],
    ['code' => 52, 'name' => 'BENI ABBES', 'domicile' => 1100, 'stopdesk' => 970],
    ['code' => 53, 'name' => 'IN SALAH', 'domicile' => 1600, 'stopdesk' => null],
    ['code' => 54, 'name' => 'IN GUEZZAM', 'domicile' => 1600, 'stopdesk' => null],
    ['code' => 55, 'name' => 'TOUGGOURT', 'domicile' => 950, 'stopdesk' => 670],
    ['code' => 56, 'name' => 'DJANET', 'domicile' => null, 'stopdesk' => null],
    ['code' => 57, 'name' => 'M\'GHAIR', 'domicile' => 950, 'stopdesk' => null],
    ['code' => 58, 'name' => 'EL MENIA', 'domicile' => 1000, 'stopdesk' => null]
];

// Handle form submission
$formSubmitted = false;
$formErrors = [];
$selectedColor = $colors ? strtolower(str_replace(' ', '_', $colors[0]['color_name'])) : 'noir';
$selectedWilaya = null;
$deliveryType = 'domicile';
$deliveryPrice = 0;
$totalPrice = $product['price'];
$orderId = null;
$wilayaName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedColor = $_POST['color'] ?? $selectedColor;
    $selectedSize = $_POST['size'] ?? '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $selectedWilaya = isset($_POST['wilaya']) ? (int)$_POST['wilaya'] : null;
    $deliveryType = $_POST['delivery_type'] ?? 'domicile';
    $customerName = $_POST['nom'] ?? '';
    $customerPhone = $_POST['telephone'] ?? '';
    $customerAddress = $_POST['adresse'] ?? '';
    
    // Validate form
    if (empty($selectedSize)) {
        $formErrors['size'] = "Veuillez sélectionner une taille";
    }
    
    if ($quantity < 1 || $quantity > 10) {
        $formErrors['quantity'] = "La quantité doit être entre 1 et 10";
    }
    
    if (empty($selectedWilaya)) {
        $formErrors['wilaya'] = "Veuillez sélectionner une wilaya";
    }
    
    if (empty($customerName)) {
        $formErrors['nom'] = "Veuillez entrer votre nom complet";
    }
    
    if (empty($customerPhone)) {
        $formErrors['telephone'] = "Veuillez entrer votre numéro de téléphone";
    }
    
    if (empty($customerAddress)) {
        $formErrors['adresse'] = "Veuillez entrer votre adresse complète";
    }

    if (empty($formErrors)) {
        // Calculate delivery price
        foreach ($wilayas as $wilaya) {
            if ($wilaya['code'] == $selectedWilaya) {
                $deliveryPriceKey = $deliveryType == 'domicile' ? 'domicile' : 'stopdesk';
                $deliveryPrice = $wilaya[$deliveryPriceKey] ?? 0;
                $wilayaName = $wilaya['name'];
                break;
            }
        }
        
        // Verify delivery is available
        if ($deliveryPrice <= 0) {
            $formErrors['delivery'] = "Le mode de livraison sélectionné n'est pas disponible pour cette wilaya";
        } else {
            // Calculate total price
            $totalPrice = ($product['price'] * $quantity) + $deliveryPrice;
            
            try {
                // Save order to database
                $stmt = $pdo->prepare("INSERT INTO orders (
                    product_id, product_name, product_price, color, size, quantity,
                    customer_name, customer_phone, customer_address,
                    wilaya_id, wilaya_name, delivery_type, delivery_price, total_price
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $productId,
                    $product['name'],
                    $product['price'],
                    $colorsArray[$selectedColor]['name'],
                    $selectedSize,
                    $quantity,
                    $customerName,
                    $customerPhone,
                    $customerAddress,
                    $selectedWilaya,
                    $wilayaName,
                    $deliveryType,
                    $deliveryPrice,
                    $totalPrice
                ]);
                
                $orderId = $pdo->lastInsertId();
                $formSubmitted = true;
                
            } catch (PDOException $e) {
                $formErrors['database'] = "Erreur lors de l'enregistrement de la commande: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Outfit Style</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #000000;
            --secondary: #34495e;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --text: #333333;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: var(--text);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            background-color: var(--primary);
            color: white;
            padding: 20px 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        
        .logo span {
            color: var(--accent);
        }
        
        .product-section {
            padding: 40px 0;
        }
        
        .product-container {
            display: flex;
            gap: 40px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .product-gallery {
            flex: 1;
            position: relative;
        }
        
        .product-image {
            width: 100%;
            height: auto;
            display: block;
            transition: var(--transition);
            border-radius: var(--border-radius) 0 0 var(--border-radius);
        }
        
        .product-details {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
        }
        
        .product-title {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .product-price {
            font-size: 24px;
            font-weight: bold;
            color: var(--accent);
            margin-bottom: 20px;
        }
        
        .product-description {
            margin-bottom: 30px;
            color: #666;
        }
        
        .option-group {
            margin-bottom: 25px;
        }
        
        .option-title {
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
            color: var(--dark);
        }
        
        .color-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .color-option {
            position: relative;
            cursor: pointer;
        }
        
        .color-radio {
            position: absolute;
            opacity: 0;
        }
        
        .color-swatch {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #ddd;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .color-radio:checked + .color-swatch {
            border-color: var(--accent);
            transform: scale(1.1);
        }
        
        .color-radio:focus + .color-swatch {
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.3);
        }
        
        .size-select, 
        .wilaya-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
            background-color: white;
        }
        
        .size-select:focus, 
        .wilaya-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.3);
        }
        
        .delivery-options {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        
        .delivery-option {
            flex: 1;
        }
        
        .delivery-radio {
            display: none;
        }
        
        .delivery-label {
            display: block;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background-color: white;
        }
        
        .delivery-radio:checked + .delivery-label {
            border-color: var(--accent);
            background-color: rgba(231, 76, 60, 0.1);
            font-weight: 600;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            background: var(--light);
            border: none;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-btn:hover {
            background: #ddd;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
        }
        
        .add-to-cart {
            width: 100%;
            padding: 15px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .add-to-cart:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .error-message {
            color: var(--accent);
            font-size: 14px;
            margin-top: 5px;
        }
        
        .success-message {
            background: #2ecc71;
            color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
            box-shadow: var(--shadow);
        }
        
        .price-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-top: 20px;
            border: 1px solid #eee;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .price-total {
            font-weight: bold;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            margin-top: 8px;
            font-size: 18px;
        }
        
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }
        
        .popup-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .popup-content {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            width: 100%;
            max-width: 500px;
            box-shadow: var(--shadow);
            transform: translateY(20px);
            transition: var(--transition);
        }
        
        .popup-overlay.active .popup-content {
            transform: translateY(0);
        }
        
        .popup-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--dark);
            text-align: center;
        }
        
        .popup-form-group {
            margin-bottom: 15px;
        }
        
        .popup-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .popup-form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }
        
        .popup-form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.3);
        }
        
        .popup-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .popup-button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .popup-confirm {
            background: var(--accent);
            color: white;
        }
        
        .popup-confirm:hover {
            background: #c0392b;
        }
        
        .popup-cancel {
            background: var(--light);
            color: var(--dark);
        }
        
        .popup-cancel:hover {
            background: #ddd;
        }
        
        @media (max-width: 768px) {
            .product-container {
                flex-direction: column;
            }
            
            .product-gallery {
                order: -1;
            }
            
            .product-image {
                border-radius: var(--border-radius) var(--border-radius) 0 0;
            }
            
            .popup-content {
                margin: 20px;
            }
            
            .delivery-options {
                flex-direction: column;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .pulse {
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .unavailable {
            opacity: 0.6;
            position: relative;
        }
        
        .unavailable::after {
            content: "Non disponible";
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            color: var(--accent);
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <a href="#" class="logo">OUTFIT <span>STYLE</span></a>
        </div>
    </header>
    
    <main class="container">
        <section class="product-section">
            <?php if ($formSubmitted): ?>
                <div class="success-message fade-in">
                    <h3>Commande réussie!</h3>
                    <p>Vos <?= htmlspecialchars($product['name']) ?> <?= htmlspecialchars($colorsArray[$selectedColor]['name']) ?> taille <?= htmlspecialchars($selectedSize) ?> ont été commandées.</p>
                    <p>Livraison <?= $deliveryType == 'domicile' ? 'à domicile' : 'au stop desk' ?> dans la wilaya <?= htmlspecialchars($wilayaName) ?>.</p>
                    <p>Total: <?= number_format($totalPrice, 2, ',', ' ') ?> DA</p>
                    <?php if ($orderId): ?>
                        <p>Votre numéro de commande est: <strong>#<?= $orderId ?></strong></p>
                    <?php endif; ?>
                    <p>Nous vous contacterons pour confirmer votre commande.</p>
                </div>
            <?php elseif (isset($formErrors['database'])): ?>
                <div class="error-message" style="padding: 20px; background: #ffecec; border-radius: var(--border-radius); margin-bottom: 20px; border: 1px solid #ffcccc;">
                    <h3>Erreur</h3>
                    <p><?= htmlspecialchars($formErrors['database']) ?></p>
                    <p>Veuillez réessayer ou nous contacter.</p>
                </div>
            <?php endif; ?>
            
            <div class="product-container">
                <div class="product-gallery">
                    <?php if (isset($colorsArray[$selectedColor]['image'])): ?>
                        <img id="productImage" src="<?= htmlspecialchars($colorsArray[$selectedColor]['image']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?> <?= htmlspecialchars($colorsArray[$selectedColor]['name']) ?>" 
                             class="product-image">
                    <?php else: ?>
                        <img id="productImage" src="images/default-product.jpg" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             class="product-image">
                    <?php endif; ?>
                </div>
                
                <div class="product-details">
                    <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="product-price"><?= number_format($product['price'], 2, ',', ' ') ?> DA</div>
                    <p class="product-description">
                        <?= htmlspecialchars($product['description'] ?? 'Nos claquettes premium allient confort et élégance. Fabriquées avec des matériaux de haute qualité pour un style impeccable.') ?>
                    </p>
                    
                    <form method="POST" id="orderForm">
                        <div class="option-group">
                            <span class="option-title">Couleur:</span>
                            <div class="color-options">
                                <?php foreach ($colorsArray as $colorKey => $color): ?>
                                    <label class="color-option">
                                        <input type="radio" name="color" value="<?= htmlspecialchars($colorKey) ?>" 
                                            <?= $colorKey === $selectedColor ? 'checked' : '' ?> 
                                            class="color-radio">
                                        <span class="color-swatch" 
                                              style="background-color: <?= htmlspecialchars($color['hex']) ?>;"
                                              data-image="<?= htmlspecialchars($color['image']) ?>">
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="option-group">
                            <label for="size" class="option-title">Taille:</label>
                            <select id="size" name="size" class="size-select" required>
                                <option value="">-- Choisissez votre taille --</option>
                                <?php foreach ($sizes as $size): ?>
                                    <option value="<?= htmlspecialchars($size) ?>" <?= isset($_POST['size']) && $_POST['size'] == $size ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($size) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($formErrors['size'])): ?>
                                <div class="error-message"><?= htmlspecialchars($formErrors['size']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="option-group">
                            <label for="quantity" class="option-title">Quantité:</label>
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn" id="decrement">-</button>
                                <input type="number" id="quantity" name="quantity" value="<?= htmlspecialchars($_POST['quantity'] ?? 1) ?>" min="1" max="10" class="quantity-input">
                                <button type="button" class="quantity-btn" id="increment">+</button>
                            </div>
                            <?php if (isset($formErrors['quantity'])): ?>
                                <div class="error-message"><?= htmlspecialchars($formErrors['quantity']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="option-group">
                            <label for="wilaya" class="option-title">Wilaya:</label>
                            <select id="wilaya" name="wilaya" class="wilaya-select" required>
                                <option value="">-- Sélectionnez votre wilaya --</option>
                                <?php foreach ($wilayas as $wilaya): ?>
                                    <option value="<?= htmlspecialchars($wilaya['code']) ?>" 
                                        <?= isset($_POST['wilaya']) && $_POST['wilaya'] == $wilaya['code'] ? 'selected' : '' ?>
                                        data-domicile="<?= htmlspecialchars($wilaya['domicile'] ?? '') ?>"
                                        data-stopdesk="<?= htmlspecialchars($wilaya['stopdesk'] ?? '') ?>">
                                        <?= htmlspecialchars($wilaya['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($formErrors['wilaya'])): ?>
                                <div class="error-message"><?= htmlspecialchars($formErrors['wilaya']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="option-group">
                            <span class="option-title">Mode de livraison:</span>
                            <div class="delivery-options">
                                <div class="delivery-option">
                                    <input type="radio" id="domicile" name="delivery_type" value="domicile" 
                                        <?= !isset($_POST['delivery_type']) || $_POST['delivery_type'] == 'domicile' ? 'checked' : '' ?> 
                                        class="delivery-radio">
                                    <label for="domicile" class="delivery-label">À domicile</label>
                                </div>
                                <div class="delivery-option">
                                    <input type="radio" id="stopdesk" name="delivery_type" value="stopdesk" 
                                        <?= isset($_POST['delivery_type']) && $_POST['delivery_type'] == 'stopdesk' ? 'checked' : '' ?> 
                                        class="delivery-radio">
                                    <label for="stopdesk" class="delivery-label">Stop Desk</label>
                                </div>
                            </div>
                            <?php if (isset($formErrors['delivery'])): ?>
                                <div class="error-message"><?= htmlspecialchars($formErrors['delivery']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="price-summary">
                            <div class="price-row">
                                <span>Prix du produit:</span>
                                <span id="productPriceDisplay"><?= number_format($product['price'], 2, ',', ' ') ?> DA</span>
                            </div>
                            <div class="price-row">
                                <span>Livraison:</span>
                                <span id="deliveryPriceDisplay">0 DA</span>
                            </div>
                            <div class="price-row price-total">
                                <span>Total:</span>
                                <span id="totalPriceDisplay"><?= number_format($product['price'], 2, ',', ' ') ?> DA</span>
                            </div>
                        </div>
                        
                        <button type="button" id="commanderBtn" class="add-to-cart pulse">
                            <i class="fas fa-shopping-cart"></i> Commander
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>
    
    <!-- Popup for customer information -->
    <div class="popup-overlay" id="customerInfoPopup">
        <div class="popup-content">
            <h3 class="popup-title">Informations de livraison</h3>
            <form id="customerInfoForm">
                <div class="popup-form-group">
                    <label for="nom">Nom complet:</label>
                    <input type="text" id="nom" name="nom" value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>" required>
                    <?php if (isset($formErrors['nom'])): ?>
                        <div class="error-message"><?= htmlspecialchars($formErrors['nom']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="popup-form-group">
                    <label for="telephone">Numéro de téléphone:</label>
                    <input type="tel" id="telephone" name="telephone" value="<?= isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : '' ?>" required>
                    <?php if (isset($formErrors['telephone'])): ?>
                        <div class="error-message"><?= htmlspecialchars($formErrors['telephone']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="popup-form-group">
                    <label for="adresse">Adresse complète:</label>
                    <input type="text" id="adresse" name="adresse" value="<?= isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : '' ?>" required>
                    <?php if (isset($formErrors['adresse'])): ?>
                        <div class="error-message"><?= htmlspecialchars($formErrors['adresse']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="popup-buttons">
                    <button type="button" class="popup-button popup-cancel" id="cancelOrder">Annuler</button>
                    <button type="submit" class="popup-button popup-confirm">Confirmer la commande</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Color selection - change image
            const colorRadios = document.querySelectorAll('.color-radio');
            const productImage = document.getElementById('productImage');
            
            colorRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        productImage.classList.add('fade-in');
                        setTimeout(() => {
                            const colorSwatch = this.nextElementSibling;
                            const imagePath = colorSwatch.getAttribute('data-image');
                            
                            if (imagePath) {
                                productImage.src = imagePath;
                                productImage.alt = productImage.alt.split(' - ')[0] + ' - ' + colorSwatch.style.backgroundColor;
                            }
                            productImage.classList.remove('fade-in');
                        }, 150);
                    }
                });
            });
            
            // Quantity controls
            const decrementBtn = document.getElementById('decrement');
            const incrementBtn = document.getElementById('increment');
            const quantityInput = document.getElementById('quantity');
            
            decrementBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (value > 1) {
                    quantityInput.value = value - 1;
                    updatePriceSummary();
                }
            });
            
            incrementBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (value < 10) {
                    quantityInput.value = value + 1;
                    updatePriceSummary();
                }
            });
            
            quantityInput.addEventListener('change', function() {
                let value = parseInt(this.value);
                if (isNaN(value) || value < 1) this.value = 1;
                if (value > 10) this.value = 10;
                updatePriceSummary();
            });
            
            // Wilaya and delivery type selection
            const wilayaSelect = document.getElementById('wilaya');
            const deliveryRadios = document.querySelectorAll('.delivery-radio');
            
            wilayaSelect.addEventListener('change', function() {
                updatePriceSummary();
                checkDeliveryAvailability();
            });
            
            deliveryRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    updatePriceSummary();
                    checkDeliveryAvailability();
                });
            });
            
            // Check if delivery is available for selected wilaya and type
            function checkDeliveryAvailability() {
                const selectedWilaya = wilayaSelect.value;
                if (!selectedWilaya) return;
                
                const selectedOption = wilayaSelect.options[wilayaSelect.selectedIndex];
                const deliveryType = document.querySelector('input[name="delivery_type"]:checked').value;
                const price = deliveryType === 'domicile' 
                    ? selectedOption.getAttribute('data-domicile') 
                    : selectedOption.getAttribute('data-stopdesk');
                
                // Disable unavailable options
                deliveryRadios.forEach(radio => {
                    const label = document.querySelector(`label[for="${radio.id}"]`);
                    const type = radio.value;
                    const price = type === 'domicile' 
                        ? selectedOption.getAttribute('data-domicile') 
                        : selectedOption.getAttribute('data-stopdesk');
                    
                    if (!price) {
                        radio.disabled = true;
                        label.classList.add('unavailable');
                    } else {
                        radio.disabled = false;
                        label.classList.remove('unavailable');
                    }
                });
            }
            
            // Update price summary
            function updatePriceSummary() {
                const quantity = parseInt(quantityInput.value) || 1;
                const productPrice = <?= $product['price'] ?>;
                const selectedWilaya = wilayaSelect.value;
                const deliveryType = document.querySelector('input[name="delivery_type"]:checked').value;
                
                let deliveryPrice = 0;
                
                if (selectedWilaya) {
                    const selectedOption = wilayaSelect.options[wilayaSelect.selectedIndex];
                    const domicilePrice = selectedOption.getAttribute('data-domicile') || '0';
                    const stopdeskPrice = selectedOption.getAttribute('data-stopdesk') || '0';
                    
                    deliveryPrice = deliveryType === 'domicile' ? parseInt(domicilePrice) : parseInt(stopdeskPrice);
                }
                
                const totalPrice = (productPrice * quantity) + deliveryPrice;
                
                document.getElementById('productPriceDisplay').textContent = (productPrice * quantity).toLocaleString('fr-FR') + ' DA';
                document.getElementById('deliveryPriceDisplay').textContent = deliveryPrice.toLocaleString('fr-FR') + ' DA';
                document.getElementById('totalPriceDisplay').textContent = totalPrice.toLocaleString('fr-FR') + ' DA';
            }
            
            // Popup handling
            const commanderBtn = document.getElementById('commanderBtn');
            const popupOverlay = document.getElementById('customerInfoPopup');
            const cancelOrderBtn = document.getElementById('cancelOrder');
            const customerInfoForm = document.getElementById('customerInfoForm');
            const orderForm = document.getElementById('orderForm');
            
            commanderBtn.addEventListener('click', function() {
                // First validate the product selection form
                const sizeSelect = document.getElementById('size');
                const wilayaSelect = document.getElementById('wilaya');
                
                if (!sizeSelect.value) {
                    alert('Veuillez sélectionner une taille');
                    return;
                }
                
                if (!wilayaSelect.value) {
                    alert('Veuillez sélectionner une wilaya');
                    return;
                }
                
                // Check if delivery is available for selected wilaya and type
                const selectedOption = wilayaSelect.options[wilayaSelect.selectedIndex];
                const deliveryType = document.querySelector('input[name="delivery_type"]:checked').value;
                const deliveryPrice = deliveryType === 'domicile' 
                    ? selectedOption.getAttribute('data-domicile') 
                    : selectedOption.getAttribute('data-stopdesk');
                
                if (!deliveryPrice) {
                    const deliveryTypeName = deliveryType === 'domicile' ? 'à domicile' : 'au stop desk';
                    alert(`La livraison ${deliveryTypeName} n'est pas disponible pour cette wilaya. Veuillez choisir un autre mode de livraison.`);
                    return;
                }
                
                // Show the popup
                popupOverlay.classList.add('active');
            });
            
            cancelOrderBtn.addEventListener('click', function() {
                popupOverlay.classList.remove('active');
            });
            
            customerInfoForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate customer info
                const nom = document.getElementById('nom').value.trim();
                const telephone = document.getElementById('telephone').value.trim();
                const adresse = document.getElementById('adresse').value.trim();
                
                if (!nom || !telephone || !adresse) {
                    alert('Veuillez remplir tous les champs obligatoires');
                    return;
                }
                
                // Create hidden inputs for customer data in the order form
                const customerData = new FormData(this);
                for (let [name, value] of customerData.entries()) {
                    let hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = name;
                    hiddenInput.value = value;
                    orderForm.appendChild(hiddenInput);
                }
                
                // Submit the main form
                orderForm.submit();
                
                // Close the popup
                popupOverlay.classList.remove('active');
            });
            
            // Add pulse animation to commander button on hover
            commanderBtn.addEventListener('mouseenter', function() {
                this.classList.add('pulse');
            });
            
            commanderBtn.addEventListener('mouseleave', function() {
                this.classList.remove('pulse');
            });
            
            // Initialize price summary and delivery availability
            updatePriceSummary();
            checkDeliveryAvailability();
        });
    </script>
</body>
</html>