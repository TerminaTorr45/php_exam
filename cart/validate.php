<?php
session_start();
include '../includes/auth.php';

$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion: " . $mysqli->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = $_POST['billing_address'];
    $city = $_POST['billing_city'];
    $postal_code = $_POST['billing_postal_code'];

    // Total du panier
    $result = $mysqli->query("
        SELECT SUM(A.price * C.quantity) AS total
        FROM Cart C
        JOIN Article A ON C.article_id = A.id
        WHERE C.user_id = $user_id
    ");
    $row = $result->fetch_assoc();
    $total = $row['total'];

    // Vérifier le solde
    $balance_result = $mysqli->query("SELECT balance FROM User WHERE id = $user_id");
    $user = $balance_result->fetch_assoc();

    if ($user['balance'] < $total) {
        $error_message = "Solde insuffisant pour valider la commande.";
    } else {
        // Débiter le solde
        $mysqli->query("UPDATE User SET balance = balance - $total WHERE id = $user_id");

        // Créer une facture
        $stmt = $mysqli->prepare("
            INSERT INTO Invoice (user_id, amount, billing_address, billing_city, billing_postal_code)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("idsss", $user_id, $total, $address, $city, $postal_code);
        $stmt->execute();
        $invoice_id = $stmt->insert_id;

        // Mettre à jour le stock et archiver les articles
        $cart_items = $mysqli->query("
            SELECT article_id, quantity FROM Cart WHERE user_id = $user_id
        ");
        while ($item = $cart_items->fetch_assoc()) {
            // Mise à jour du stock
            $mysqli->query("
                UPDATE Stock SET quantity = quantity - {$item['quantity']}
                WHERE article_id = {$item['article_id']}
            ");

            // Archivage dans CartArchive
            $archive_stmt = $mysqli->prepare("
                INSERT INTO CartArchive (user_id, article_id, quantity, invoice_id)
                VALUES (?, ?, ?, ?)
            ");
            $archive_stmt->bind_param("iiii", $user_id, $item['article_id'], $item['quantity'], $invoice_id);
            $archive_stmt->execute();
        }

        // Vider le panier
        $mysqli->query("DELETE FROM Cart WHERE user_id = $user_id");

        // Stocker l'ID de la facture pour le récapitulatif
        $_SESSION['invoice_id'] = $invoice_id;

        // Rediriger vers la page de récapitulatif
        header("Location: order_summary.php");
        exit;
    }
}

// Récupérer le total actuel
$result = $mysqli->query("
    SELECT SUM(A.price * C.quantity) AS total
    FROM Cart C
    JOIN Article A ON C.article_id = A.id
    WHERE C.user_id = $user_id
");
$row = $result->fetch_assoc();
$total = $row['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation de commande</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Confirmation de la commande</h2>
    
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="total-display">
        <p>Total du panier : <strong><?= number_format($total, 2) ?> €</strong></p>
    </div>

    <form method="POST">
        <label>Adresse de facturation :</label>
        <input type="text" name="billing_address" required>

        <label>Ville :</label>
        <input type="text" name="billing_city" required>

        <label>Code postal :</label>
        <input type="text" name="billing_postal_code" required>

        <button type="submit">Valider la commande</button>
    </form>

    <div class="button-container">
        <a href="/index.php" class="home-button">Retour à l'accueil</a>
    </div>
</body>
</html>
