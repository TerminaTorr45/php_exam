<?php
session_start();
include 'includes/auth.php';

$mysqli = new mysqli("localhost", "root", "root", "php_exam_db");
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
<h2>Récapitulatif de la commande</h2>
<p><strong>Adresse :</strong> <?= htmlspecialchars($invoice['billing_address']) ?>, <?= htmlspecialchars($invoice['billing_city']) ?> <?= htmlspecialchars($invoice['billing_postal_code']) ?></p>
<p><strong>Total :</strong> <?= number_format($invoice['amount'], 2) ?> €</p>

<h3>Articles achetés :</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Nom</th>
        <th>Prix</th>
        <th>Quantité</th>
        <th>Total</th>
    </tr>
    <?php while ($item = $items_result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= number_format($item['price'], 2) ?> €</td>
            <td><?= $item['quantity'] ?></td>
            <td><?= number_format($item['price'] * $item['quantity'], 2) ?> €</td>
        </tr>
    <?php endwhile; ?>
</table>

<!-- Bouton retour à l'accueil -->
<br>
<a href="/index.php">
    <button type="button">Retour à l'accueil</button>
</a>
