<?php
session_start();
include 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

$user_id = $_SESSION['user_id'];
$message = "";

// Supprimer un article du panier
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $mysqli->prepare("DELETE FROM Cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Modifier la quantité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantities'])) {
    foreach ($_POST['quantities'] as $cart_id => $quantity) {
        $quantity = intval($quantity);
        if ($quantity > 0) {
            $stmt = $mysqli->prepare("UPDATE Cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    $message = "🛒 Quantités mises à jour.";
}

// Passer commande
if (isset($_POST['checkout'])) {
    // Total panier
    $total = 0;

    $cart_items = $mysqli->query("
        SELECT C.id AS cart_id, A.id AS article_id, A.price, C.quantity, S.quantity AS stock 
        FROM Cart C
        JOIN Article A ON C.article_id = A.id
        JOIN Stock S ON A.id = S.article_id
        WHERE C.user_id = $user_id
    ");

    $errors = [];
    $to_purchase = [];

    while ($item = $cart_items->fetch_assoc()) {
        if ($item['quantity'] > $item['stock']) {
            $errors[] = "Stock insuffisant pour l'article ID {$item['article_id']}";
        } else {
            $total += $item['price'] * $item['quantity'];
            $to_purchase[] = $item;
        }
    }

    // Vérification solde utilisateur
    $res = $mysqli->query("SELECT balance FROM User WHERE id = $user_id");
    $user = $res->fetch_assoc();
    $balance = $user['balance'];

    if (count($errors) > 0) {
        $message = implode("<br>", $errors);
    } elseif ($total > $balance) {
        $message = "❌ Solde insuffisant pour finaliser l'achat.";
    } else {
        // Mise à jour du stock + génération facture
        foreach ($to_purchase as $item) {
            $new_stock = $item['stock'] - $item['quantity'];
            $mysqli->query("UPDATE Stock SET quantity = $new_stock WHERE article_id = {$item['article_id']}");
        }

        // Retirer le montant du solde utilisateur
        $new_balance = $balance - $total;
        $mysqli->query("UPDATE User SET balance = $new_balance WHERE id = $user_id");

        // Générer la facture
        $stmt = $mysqli->prepare("INSERT INTO Invoice (user_id, amount, billing_address, billing_city, billing_postal_code) VALUES (?, ?, 'Adresse inconnue', 'Ville', '00000')");
        $stmt->bind_param("id", $user_id, $total);
        $stmt->execute();
        $stmt->close();

        // Vider le panier
        $mysqli->query("DELETE FROM Cart WHERE user_id = $user_id");

        $message = "✅ Commande passée avec succès ! Facture générée.";
    }
}

// Récupération des articles du panier
$result = $mysqli->query("
    SELECT C.id AS cart_id, A.name, A.price, A.image_url, C.quantity, S.quantity AS stock
    FROM Cart C
    JOIN Article A ON C.article_id = A.id
    JOIN Stock S ON A.id = S.article_id
    WHERE C.user_id = $user_id
");

$articles = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $articles[] = $row;
    $total += $row['price'] * $row['quantity'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Panier</title>
    <link rel="stylesheet" href="styles/cart.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="cart-title">🛒 Mon Panier</h1>
            <a href="home.php" class="back-link">⬅️ Retour à l'accueil</a>
        </div>

        <?php if ($message): ?>
            <div class="message">
                <strong><?= $message ?></strong>
            </div>
        <?php endif; ?>

        <?php if (count($articles) === 0): ?>
            <div class="empty-cart">
                <p>Votre panier est vide.</p>
            </div>
        <?php else: ?>
            <form method="POST">
                <table class="cart-table">
                    <tr>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>Prix</th>
                        <th>Quantité</th>
                        <th>Stock</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($articles as $item): ?>
                        <tr>
                            <td>
                                <?php if ($item['image_url']): ?>
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" class="product-image">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= number_format($item['price'], 2) ?> €</td>
                            <td>
                                <input type="number" class="quantity-input" name="quantities[<?= $item['cart_id'] ?>]" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>">
                            </td>
                            <td><?= $item['stock'] ?></td>
                            <td><?= number_format($item['price'] * $item['quantity'], 2) ?> €</td>
                            <td><a href="?delete=<?= $item['cart_id'] ?>" class="delete-btn">❌ Supprimer</a></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <div class="cart-summary">
                    <p class="total-amount">Total : <?= number_format($total, 2) ?> €</p>
                    <div class="action-buttons">
                        <button type="submit" class="update-btn">🆙 Mettre à jour</button>
                        <a href="/cart/validate.php" class="checkout-btn">💳 Commander</a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
