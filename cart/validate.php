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
        SELECT COALESCE(SUM(A.price * C.quantity), 0) AS total
        FROM Cart C
        JOIN Article A ON C.article_id = A.id
        WHERE C.user_id = $user_id
    ");
    $row = $result->fetch_assoc();
    $total = $row['total'];

    // Vérifier le solde
    $balance_result = $mysqli->query("SELECT balance FROM User WHERE id = $user_id");
    $user = $balance_result->fetch_assoc();

    if (!$total || $total <= 0) {
        $error_message = "Le panier est vide ou le total est invalide.";
    } else if ($user['balance'] < $total) {
        $error_message = "Solde insuffisant pour valider la commande.";
    } else {
        // Débiter le solde
        $update_balance = $mysqli->prepare("UPDATE User SET balance = balance - ? WHERE id = ?");
        $update_balance->bind_param("di", $total, $user_id);
        $update_balance->execute();
        $update_balance->close();

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

        // Récupérer les informations de l'utilisateur et de la commande pour l'email
        $user_info = $mysqli->query("SELECT email, username FROM User WHERE id = $user_id")->fetch_assoc();
        
        // Récupérer les articles de la commande
        $order_items = $mysqli->query("
            SELECT A.name, A.price, C.quantity
            FROM CartArchive C
            JOIN Article A ON C.article_id = A.id
            WHERE C.invoice_id = $invoice_id
        ");

        // Préparer le contenu de l'email
        $items_html = "";
        while ($item = $order_items->fetch_assoc()) {
            $items_html .= "
                <div style='background: #f5f5f5; padding: 10px; margin: 5px 0; border-radius: 5px;'>
                    <p style='margin: 5px 0;'><strong>" . htmlspecialchars($item['name']) . "</strong></p>
                    <p style='margin: 5px 0;'>Quantité: " . $item['quantity'] . "</p>
                    <p style='margin: 5px 0;'>Prix unitaire: " . number_format($item['price'], 2) . " €</p>
                    <p style='margin: 5px 0;'>Total: " . number_format($item['price'] * $item['quantity'], 2) . " €</p>
                </div>";
        }

        // Envoyer l'email de confirmation
        $subject = "Confirmation de votre commande - SNEAKER MARKET";
        $body = "
            <h2>Bonjour " . htmlspecialchars($user_info['username']) . ",</h2>
            <p>Nous vous confirmons votre commande #" . $invoice_id . ".</p>
            
            <h3>Détails de la commande :</h3>
            " . $items_html . "
            
            <div style='margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;'>
                <p><strong>Adresse de livraison :</strong></p>
                <p>" . htmlspecialchars($address) . "</p>
                <p>" . htmlspecialchars($city) . " " . htmlspecialchars($postal_code) . "</p>
            </div>
            
            <div style='margin-top: 20px;'>
                <p><strong>Total de la commande : " . number_format($total, 2) . " €</strong></p>
            </div>
            
            <p style='margin-top: 20px;'>Merci de votre confiance !</p>
            <p>L'équipe SNEAKER MARKET</p>
        ";

        require_once '../includes/mail_config.php';
        sendMail($user_info['email'], $subject, $body);

        // Rediriger vers la page de récapitulatif
        header("Location: order_summary.php");
        exit;
    }
}

// Récupérer le total actuel
$result = $mysqli->query("
    SELECT COALESCE(SUM(A.price * C.quantity), 0) AS total
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
