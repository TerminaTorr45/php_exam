<?php
session_start();

// Connexion à la base de données
$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$view_user_id = isset($_GET['id']) ? intval($_GET['id']) : $current_user_id;

// Récupération des infos utilisateur
$stmt = $mysqli->prepare("SELECT id, username, email, COALESCE(balance, 0) as balance FROM User WHERE id = ?");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Utilisateur non trouvé.");
}

// ✅ Mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $view_user_id === $current_user_id) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    if ($password) {
        $update = $mysqli->prepare("UPDATE User SET username = ?, email = ?, password = ? WHERE id = ?");
        $update->bind_param("sssi", $username, $email, $password, $current_user_id);
    } else {
        $update = $mysqli->prepare("UPDATE User SET username = ?, email = ? WHERE id = ?");
        $update->bind_param("ssi", $username, $email, $current_user_id);
    }

    if ($update->execute()) {
        $success_message = "Mise à jour réussie.";
        $user['username'] = $username;
        $user['email'] = $email;
    } else {
        $error_message = "Erreur lors de la mise à jour.";
    }
}

// ✅ Ajout d'argent au solde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_funds']) && $view_user_id === $current_user_id) {
    $amount = floatval($_POST['amount']);
    if ($amount > 0) {
        $update = $mysqli->prepare("UPDATE User SET balance = COALESCE(balance, 0) + ? WHERE id = ?");
        $update->bind_param("di", $amount, $current_user_id);
        if ($update->execute()) {
            // Mettre à jour les informations de l'utilisateur
            $stmt = $mysqli->prepare("SELECT id, username, email, COALESCE(balance, 0) as balance FROM User WHERE id = ?");
            $stmt->bind_param("i", $view_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $success_message = "Argent ajouté avec succès.";
        } else {
            $error_message = "Erreur lors de l'ajout d'argent.";
        }
    } else {
        $error_message = "Montant invalide.";
    }
}

// Récupération des articles
$articles_stmt = $mysqli->prepare("SELECT * FROM Article WHERE author_id = ? ORDER BY published_at DESC");
$articles_stmt->bind_param("i", $view_user_id);
$articles_stmt->execute();
$articles_result = $articles_stmt->get_result();

// Récupération des factures
$invoices_stmt = $mysqli->prepare("
    SELECT 
        id,
        amount,
        billing_address,
        billing_city,
        billing_postal_code,
        COALESCE(transaction_date, NOW()) as transaction_date
    FROM Invoice 
    WHERE user_id = ? 
    ORDER BY transaction_date DESC
");
$invoices_stmt->bind_param("i", $view_user_id);
$invoices_stmt->execute();
$invoices_result = $invoices_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Compte - SNEAKER MARKET</title>
    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .header {
            background-color: #000;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header a {
            color: white;
            text-decoration: none;
            margin-left: 1rem;
            border: 1px solid white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }

        .header a:hover {
            background-color: white;
            color: black;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        h2, h3 {
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"] {
            width: 100%;
            padding: 0.6rem;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background-color: #000;
            color: white;
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background-color: #333;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }

        ul {
            list-style: disc inside;
            padding-left: 0;
        }

        ul li {
            padding: 0.5rem 0;
        }
    </style>
</head>
<body>
<header class="header">
    <h1><a href="home.php">🏠 SNEAKER MARKET</a></h1>
    <div>
        <a href="home.php">Accueil</a>
        <a href="logout.php">Se déconnecter</a>
    </div>
</header>

<div class="container">
    <h2>Compte de <?php echo htmlspecialchars($user['username']); ?></h2>

    <?php if (isset($success_message)): ?>
        <div class="message success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="message error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if ($view_user_id === $current_user_id): ?>
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                <input type="password" id="password" name="password">
            </div>
            <button type="submit">Mettre à jour</button>
        </form>
    <?php else: ?>
        <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <?php endif; ?>

    <h3>Solde : <?php echo number_format($user['balance'] ?? 0, 2, ',', ' '); ?> €</h3>
    
    <?php if ($view_user_id === $current_user_id): ?>
        <form method="POST" style="margin-bottom: 2rem;">
            <input type="hidden" name="add_funds" value="1">
            <div class="form-group">
                <label for="amount">Ajouter de l'argent</label>
                <input type="number" step="0.01" min="0.01" name="amount" id="amount" required>
            </div>
            <button type="submit">Ajouter</button>
        </form>
        <?php endif; ?>
        
        <h3>Articles publiés</h3>
    <ul>
        <?php while ($article = $articles_result->fetch_assoc()): ?>
            <li>
                <strong><?php echo htmlspecialchars($article['name']); ?></strong> —
                <?php echo date("d/m/Y H:i", strtotime($article['published_at'])); ?>
            </li>
        <?php endwhile; ?>
        <?php if ($articles_result->num_rows === 0): ?>
            <li>Aucun article publié.</li>
        <?php endif; ?>
    </ul>

    <h3>Historique des factures</h3>
    <ul>
        <?php if ($invoices_result->num_rows > 0): ?>
            <?php while ($invoice = $invoices_result->fetch_assoc()): ?>
                <li>
                    <strong><?php echo number_format($invoice['amount'], 2, ',', ' '); ?> €</strong> —
                    <?php echo htmlspecialchars($invoice['billing_address']); ?>,
                    <?php echo htmlspecialchars($invoice['billing_city']); ?>
                    <?php echo htmlspecialchars($invoice['billing_postal_code']); ?> —
                    le <?php echo date("d/m/Y H:i", strtotime($invoice['transaction_date'])); ?>
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li>Aucune facture trouvée.</li>
        <?php endif; ?>
    </ul>
</div>
</body>
</html>
