<?php
session_start();

// Database connection
$db = new PDO('mysql:host=localhost;dbname=outfit_style;charset=utf8', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Create uploads directory if it doesn't exist
$uploadDir = dirname(__DIR__) . '/images/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Get the single product
$product = $db->query("SELECT * FROM products LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Get product colors and sizes if product exists
if ($product) {
    $product_colors = $db->prepare("SELECT * FROM product_colors WHERE product_id = ?");
    $product_colors->execute([$product['id']]);
    $product_colors = $product_colors->fetchAll(PDO::FETCH_ASSOC);
    
    $product_sizes = $db->prepare("SELECT size FROM product_sizes WHERE product_id = ? ORDER BY size");
    $product_sizes->execute([$product['id']]);
    $product_sizes = $product_sizes->fetchAll(PDO::FETCH_COLUMN);
} else {
    $product_colors = [];
    $product_sizes = [];
}

// Handle product update
if (isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    
    try {
        if ($product) {
            // Update existing product
            $stmt = $db->prepare("UPDATE products SET name = ?, price = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $price, $description, $product['id']]);
            $product_id = $product['id'];
            $success_message = "Produit mis à jour avec succès!";
        } else {
            // Insert new product (only one allowed)
            $stmt = $db->prepare("INSERT INTO products (name, price, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $price, $description]);
            $product_id = $db->lastInsertId();
            $success_message = "Produit ajouté avec succès!";
        }
        
        // Handle colors
        if (isset($_POST['color_name'])) {
            // First delete all existing colors for this product
            $db->prepare("DELETE FROM product_colors WHERE product_id = ?")->execute([$product_id]);
            
            foreach ($_POST['color_name'] as $index => $color_name) {
                $color_hex = $_POST['color_hex'][$index];
                $image_path = 'images/default_product.jpg'; // Default image
                
                // Handle file upload
                if (!empty($_FILES['color_images']['tmp_name'][$index])) {
                    $fileExt = pathinfo($_FILES['color_images']['name'][$index], PATHINFO_EXTENSION);
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array(strtolower($fileExt), $allowedExtensions)) {
                        $fileName = uniqid() . '_' . preg_replace('/[^a-z0-9]/', '', strtolower($color_hex)) . '.' . $fileExt;
                        $filePath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['color_images']['tmp_name'][$index], $filePath)) {
                            $image_path = 'images/' . $fileName;
                        }
                    }
                } elseif (!empty($_POST['existing_color_image'][$index])) {
                    $image_path = $_POST['existing_color_image'][$index];
                }
                
                // Insert color
                $stmt = $db->prepare("
                    INSERT INTO product_colors (product_id, color_name, color_hex, image_path)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$product_id, $color_name, $color_hex, $image_path]);
            }
        }
        
        // Handle sizes
        if (isset($_POST['sizes'])) {
            // First delete all existing sizes for this product
            $db->prepare("DELETE FROM product_sizes WHERE product_id = ?")->execute([$product_id]);
            
            $sizes = explode(',', $_POST['sizes']);
            foreach ($sizes as $size) {
                $size = trim($size);
                if (!empty($size)) {
                    $stmt = $db->prepare("INSERT INTO product_sizes (product_id, size) VALUES (?, ?)");
                    $stmt->execute([$product_id, $size]);
                }
            }
        }
        
        // Refresh product data
        $product = $db->query("SELECT * FROM products LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            $product_colors = $db->prepare("SELECT * FROM product_colors WHERE product_id = ?");
            $product_colors->execute([$product['id']]);
            $product_colors = $product_colors->fetchAll(PDO::FETCH_ASSOC);
            
            $product_sizes = $db->prepare("SELECT size FROM product_sizes WHERE product_id = ? ORDER BY size");
            $product_sizes->execute([$product['id']]);
            $product_sizes = $product_sizes->fetchAll(PDO::FETCH_COLUMN);
        }
        
    } catch (PDOException $e) {
        $error_message = "Erreur: " . $e->getMessage();
    }
}

// Handle product reset
if (isset($_POST['reset_product'])) {
    try {
        // Get product ID first
        $product = $db->query("SELECT id FROM products LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            // Delete associated colors and sizes
            $db->prepare("DELETE FROM product_colors WHERE product_id = ?")->execute([$product['id']]);
            $db->prepare("DELETE FROM product_sizes WHERE product_id = ?")->execute([$product['id']]);
            // Delete product
            $db->prepare("DELETE FROM products WHERE id = ?")->execute([$product['id']]);
        }
        $success_message = "Produit réinitialisé avec succès!";
        $product = null;
        $product_colors = [];
        $product_sizes = [];
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la réinitialisation: " . $e->getMessage();
    }
}

// Handle order status update
if (isset($_POST['update_order_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        $success_message = "Statut de commande mis à jour avec succès!";
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la mise à jour du statut: " . $e->getMessage();
    }
}

// Get stats for dashboard
$total_orders = $db->query("SELECT COALESCE(COUNT(*), 0) FROM orders")->fetchColumn();
$total_revenue = $db->query("SELECT COALESCE(SUM(total_price - delivery_price), 0) FROM orders")->fetchColumn();
$pending_orders = $db->query("SELECT COALESCE(COUNT(*), 0) FROM orders WHERE status = 'pending'")->fetchColumn();

// Get recent orders
$recent_orders = $db->query("
    SELECT o.*, 
           p.name as product_name,
           (SELECT image_path FROM product_colors WHERE product_id = p.id AND color_name = o.color LIMIT 1) as product_image
    FROM orders o
    JOIN products p ON o.product_id = p.id
    ORDER BY o.order_date DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord | Outfit Style</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: rgb(0, 0, 0);
            --secondary: #34495e;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --text: #333;
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        header {
            background-color: var(--primary);
            color: white;
            padding: 15px 0;
            box-shadow: var(--shadow);
            width: 100%;
            position: fixed;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 0 20px;
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
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }
        
        .dashboard-container {
            display: flex;
            flex: 1;
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
            margin-top: 60px;
        }
        
        .sidebar {
            width: 250px;
            background: var(--primary);
            color: white;
            padding: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: fixed;
            height: calc(100vh - 60px);
            overflow-y: auto;
            top: 60px;
        }
        
        .sidebar-nav {
            margin-top: 20px;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--light);
            text-decoration: none;
            border-radius: var(--border-radius);
            margin-bottom: 5px;
            transition: var(--transition);
        }
        
        .sidebar-link:hover, .sidebar-link.active {
            background: var(--secondary);
            color: white;
        }
        
        .sidebar-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
            overflow-x: auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 30px;
            margin-bottom: 10px;
            color: var(--accent);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #777;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            color: var(--dark);
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            width: 100%;
            overflow-x: auto;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--secondary);
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .product-image-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .btn {
            padding: 8px 15px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn:hover {
            background: #c0392b;
        }
        
        .btn-secondary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #2c3e50;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
        }
        
        .color-swatch {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
            border: 1px solid #ddd;
        }
        
        .color-row {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            align-items: center;
            padding: 10px;
            background: #f9f9f9;
            border-radius: var(--border-radius);
            flex-wrap: wrap;
        }
        
        .color-row > * {
            flex: 1;
            min-width: 120px;
        }
        
        .color-row input[type="color"] {
            width: 40px;
            height: 40px;
            padding: 2px;
            cursor: pointer;
        }
        
        .color-image-preview {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: #f5f5f5;
        }
        
        .add-color-btn {
            margin-bottom: 15px;
        }
        
        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .tab-container {
            margin-top: 20px;
        }
        
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            overflow-x: auto;
        }
        
        .tab-button {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #777;
            border-bottom: 3px solid transparent;
            transition: var(--transition);
            white-space: nowrap;
        }
        
        .tab-button.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        input[type="file"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            background: white;
        }
        
        .product-preview {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .product-image-main {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: var(--border-radius);
            border: 1px solid #ddd;
        }
        
        .product-info {
            flex: 1;
            min-width: 300px;
        }
        
        .product-info h2 {
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .product-info p {
            margin-bottom: 5px;
        }
        
        .image-preview-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-form select {
            padding: 5px;
            border-radius: var(--border-radius);
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
        }
        
        /* Mobile Styles */
        @media (max-width: 992px) {
            .menu-toggle {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                top: 60px;
                left: 0;
                z-index: 1000;
                height: calc(100vh - 60px);
                width: 250px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            
            .dashboard-container {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .color-row > * {
                min-width: 100%;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .product-preview {
                flex-direction: column;
            }
            
            .product-image-main {
                width: 100%;
                height: auto;
                max-height: 300px;
            }
        }
        
        @media (max-width: 768px) {
            .header-content {
                padding: 0 15px;
            }
            
            .card {
                padding: 15px;
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 14px;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 576px) {
            .sidebar-link {
                padding: 10px;
            }
            
            .tab-buttons {
                flex-wrap: wrap;
            }
            
            .tab-button {
                padding: 8px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <a href="../index.php" class="logo">OUTFIT <span>STYLE</span></a>
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>
    
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-nav">
                <a href="dashboard.php" class="sidebar-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de bord</span>
                </a>
                <a href="#orders" class="sidebar-link" onclick="showTab('orders')">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Commandes</span>
                </a>
                <a href="#products" class="sidebar-link" onclick="showTab('products')">
                    <i class="fas fa-tshirt"></i>
                    <span>Produits</span>
                </a>
                <a href="logout.php" class="sidebar-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </div>
        
        <div class="main-content">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?= $error_message ?></div>
            <?php endif; ?>
            
            <div id="dashboard" class="tab-content active">
                <h1>Tableau de bord</h1>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Commandes totales</h3>
                        <p><?= $total_orders ?></p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-euro-sign"></i>
                        <h3>Revenu total</h3>
                        <p><?= number_format($total_revenue, 2, ',', ' ') ?> DA</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <h3>Commandes en attente</h3>
                        <p><?= $pending_orders ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Dernières commandes</h2>
                        <a href="#orders" class="btn" onclick="showTab('orders')">Voir tout</a>
                    </div>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Produit</th>
                                    <th>Image</th>
                                    <th>Détails</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($order['product_name']) ?></td>
                                    <td>
                                        <?php if (!empty($order['product_image'])): ?>
                                            <img src="../<?= $order['product_image'] ?>" 
                                                 alt="<?= $order['product_name'] ?>" 
                                                 class="product-image-thumb">
                                        <?php else: ?>
                                            <img src="../images/default_product.jpg" class="product-image-thumb">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= ucfirst($order['color']) ?><br>
                                        Taille: <?= $order['size'] ?><br>
                                        <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?>
                                    </td>
                                    <td><?= number_format($order['total_price'] - $order['delivery_price'], 2, ',', ' ') ?> DA</td>
                                    <td>
                                        <span class="status status-<?= $order['status'] ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div id="orders" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Toutes les commandes</h2>
                    </div>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Produit</th>
                                    <th>Image</th>
                                    <th>Détails</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $all_orders = $db->query("
                                    SELECT o.*, p.name as product_name,
                                           (SELECT image_path FROM product_colors WHERE product_id = p.id AND color_name = o.color LIMIT 1) as product_image
                                    FROM orders o
                                    JOIN products p ON o.product_id = p.id
                                    ORDER BY o.order_date DESC
                                ")->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($all_orders as $order): 
                                ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($order['customer_name']) ?><br>
                                        <?= htmlspecialchars($order['customer_phone']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($order['product_name']) ?></td>
                                    <td>
                                        <?php if (!empty($order['product_image'])): ?>
                                            <img src="../<?= $order['product_image'] ?>" 
                                                 alt="<?= $order['product_name'] ?>" 
                                                 class="product-image-thumb">
                                        <?php else: ?>
                                            <img src="../images/default_product.jpg" class="product-image-thumb">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= ucfirst($order['color']) ?><br>
                                        Taille: <?= $order['size'] ?><br>
                                        Quantité: <?= $order['quantity'] ?>
                                    </td>
                                    <td><?= number_format($order['total_price'] - $order['delivery_price'], 2, ',', ' ') ?> DA</td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></td>
                                    <td>
                                        <form method="POST" class="status-form">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <select name="status" onchange="this.form.submit()">
                                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>En attente</option>
                                                <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>En traitement</option>
                                                <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Expédié</option>
                                                <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Livré</option>
                                            </select>
                                            <input type="hidden" name="update_order_status" value="1">
                                        </form>
                                    </td>
                                   
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div id="products" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Gestion du Produit</h2>
                    </div>
                    
                    <?php if ($product): ?>
                        <div class="product-preview">
                            <?php if (!empty($product_colors[0]['image_path'])): ?>
                                <img src="../<?= $product_colors[0]['image_path'] ?>" class="product-image-main" alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php else: ?>
                                <img src="../images/default_product.jpg" class="product-image-main">
                            <?php endif; ?>
                            <div class="product-info">
                                <h2><?= htmlspecialchars($product['name']) ?></h2>
                                <p><strong>Prix:</strong> <?= number_format($product['price'], 2, ',', ' ') ?> DA</p>
                                <p><strong>Description:</strong> <?= htmlspecialchars($product['description']) ?></p>
                                <?php if ($product_colors): ?>
                                    <p><strong>Couleurs:</strong> 
                                        <?php foreach ($product_colors as $color): ?>
                                            <span class="color-swatch" style="background-color: <?= $color['color_hex'] ?>;"></span>
                                            <?= htmlspecialchars($color['color_name']) ?>
                                        <?php endforeach; ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($product_sizes): ?>
                                    <p><strong>Tailles:</strong> <?= implode(', ', $product_sizes) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <form method="POST" id="productForm" enctype="multipart/form-data">
                            <input type="hidden" name="update_product" value="1">
                            
                            <div class="form-group">
                                <label for="name">Nom du produit</label>
                                <input type="text" id="name" name="name" value="<?= $product ? htmlspecialchars($product['name']) : '' ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Prix (DA)</label>
                                <input type="number" id="price" name="price" step="0.01" value="<?= $product ? $product['price'] : '' ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" required><?= $product ? htmlspecialchars($product['description']) : '' ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Couleurs disponibles</label>
                                <button type="button" class="btn btn-small add-color-btn" onclick="addColorField()">
                                    <i class="fas fa-plus"></i> Ajouter une couleur
                                </button>
                                <div id="color-fields">
                                    <?php if ($product && $product_colors): ?>
                                        <?php foreach ($product_colors as $color): ?>
                                            <div class="color-row">
                                                <input type="text" name="color_name[]" placeholder="Nom de la couleur" value="<?= htmlspecialchars($color['color_name']) ?>" required>
                                                <input type="color" name="color_hex[]" value="<?= $color['color_hex'] ?>" required>
                                                <div class="image-preview-container">
                                                    <?php if (!empty($color['image_path'])): ?>
                                                        <img src="../<?= $color['image_path'] ?>" class="color-image-preview">
                                                        <input type="hidden" name="existing_color_image[]" value="<?= $color['image_path'] ?>">
                                                    <?php else: ?>
                                                        <img src="../images/default_product.jpg" class="color-image-preview">
                                                    <?php endif; ?>
                                                    <input type="file" name="color_images[]" accept="image/*" class="color-image-upload">
                                                </div>
                                                <button type="button" class="btn btn-small btn-danger" onclick="this.parentNode.remove()">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="sizes">Tailles disponibles (séparées par des virgules)</label>
                                <input type="text" id="sizes" name="sizes" value="<?= $product ? implode(', ', $product_sizes) : '' ?>" required>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn">
                                    <i class="fas fa-save"></i> <?= $product ? 'Mettre à jour' : 'Ajouter' ?>
                                </button>
                                <?php if ($product): ?>
                                    <button type="submit" name="reset_product" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser ce produit?')">
                                        <i class="fas fa-trash"></i> Réinitialiser
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
        
        // Show/hide tabs
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');
            
            // Update active state in sidebar
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.classList.remove('active');
            });
            
            if (tabId === 'dashboard') {
                document.querySelector('.sidebar-link[href="dashboard.php"]').classList.add('active');
            } else {
                document.querySelector(`.sidebar-link[href="#${tabId}"]`).classList.add('active');
            }
            
            // Close sidebar on mobile after selection
            if (window.innerWidth < 992) {
                toggleSidebar();
            }
        }
        
        // Add color field
        function addColorField() {
            const colorFields = document.getElementById('color-fields');
            const colorRow = document.createElement('div');
            colorRow.className = 'color-row';
            colorRow.innerHTML = `
                <input type="text" name="color_name[]" placeholder="Nom de la couleur" required>
                <input type="color" name="color_hex[]" value="#000000" required>
                <div class="image-preview-container">
                    <img src="../images/default_product.jpg" class="color-image-preview">
                    <input type="file" name="color_images[]" accept="image/*" required class="color-image-upload">
                </div>
                <button type="button" class="btn btn-small btn-danger" onclick="this.parentNode.remove()">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            `;
            colorFields.appendChild(colorRow);
        }
        
        // Image preview for new uploads
        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('change', function(e) {
                if (e.target && e.target.matches('.color-image-upload')) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const previewImg = e.target.closest('.image-preview-container').querySelector('img');
                            previewImg.src = event.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });
        });
        
        // View order details
        function viewOrderDetails(orderId) {
            alert(`Détails de la commande #${orderId} - À implémenter avec une modale`);
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth < 992 && 
                !sidebar.contains(event.target) && 
                event.target !== menuToggle && 
                !menuToggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>