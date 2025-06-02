<?php
session_start();
include '../includes/auth.php';

$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion: " . $mysqli->connect_error);
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['invoice_id'])) {
    header("Location: /");
    exit;
}

$user_id = $_SESSION['user_id'];
$invoice_id = $_SESSION['invoice_id'];
unset($_SESSION['invoice_id']); // pour éviter rechargement

// Infos facture
$invoice_result = $mysqli->query("
    SELECT * FROM Invoice WHERE id = $invoice_id AND user_id = $user_id
");
$invoice = $invoice_result->fetch_assoc();

// Articles commandés (on utilise les infos juste avant vidage panier)
$items_result = $mysqli->query("
    SELECT A.name, A.price, C.quantity
    FROM CartArchive C
    JOIN Article A ON C.article_id = A.id
    WHERE C.invoice_id = $invoice_id
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récapitulatif de commande</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/order_summary.css">
</head>
<body>
    <div class="container">
        <div class="order-header">
            <h1>Récapitulatif de la commande</h1>
            <div class="order-status success">Commande confirmée</div>
        </div>

        <div class="order-details">
            <div class="shipping-info">
                <h2>Adresse de livraison</h2>
                <div class="info-card">
                    <p><?= htmlspecialchars($invoice['billing_address']) ?></p>
                    <p><?= htmlspecialchars($invoice['billing_city']) ?> <?= htmlspecialchars($invoice['billing_postal_code']) ?></p>
                </div>
            </div>

            <div class="order-summary">
                <h2>Détails de la commande</h2>
                <div class="info-card">
                    <div class="summary-row">
                        <span>Numéro de commande</span>
                        <span>#<?= $invoice_id ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span class="price"><?= number_format($invoice['amount'], 2) ?> €</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="items-section">
            <h2>Articles commandés</h2>
            <div class="items-grid">
                <?php while ($item = $items_result->fetch_assoc()): ?>
                    <div class="item-card">
                        <div class="item-details">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <div class="item-meta">
                                <span class="quantity">Quantité: <?= $item['quantity'] ?></span>
                                <span class="price"><?= number_format($item['price'], 2) ?> €</span>
                            </div>
                            <div class="item-total">
                                Total: <?= number_format($item['price'] * $item['quantity'], 2) ?> €
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="action-buttons">
            <a href="/index.php" class="btn-primary">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>
