<?php
session_start();
include 'includes/auth.php';

$mysqli = new mysqli("localhost", "root", "ton_mot_de_passe", "php_exam_db");
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

    // Obtenir le total du panier
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
        echo "<p>Solde insuffisant pour valider la commande.</p>";
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

        // Mettre à jour le stock
        $cart_items = $mysqli->query("
            SELECT article_id, quantity FROM Cart WHERE user_id = $user_id
        ");
        while ($item = $cart_items->fetch_assoc()) {
            $mysqli->query("
                UPDATE Stock SET quantity = quantity - {$item['quantity']}
                WHERE article_id = {$item['article_id']}
            ");
        }

        // Vider le panier
        $mysqli->query("DELETE FROM Cart WHERE user_id = $user_id");

        echo "<p>Commande validée et facture générée avec succès.</p>";
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

<h2>Confirmation de la commande</h2>
<p>Total du panier : <strong><?= number_format($total, 2) ?> €</strong></p>

<form method="POST">
    <label>Adresse de facturation :</label><br>
    <input type="text" name="billing_address" required><br>

    <label>Ville :</label><br>
    <input type="text" name="billing_city" required><br>

    <label>Code postal :</label><br>
    <input type="text" name="billing_postal_code" required><br><br>

    <button type="submit">Valider la commande</button>
</form>