<?php
session_start();
require_once 'includes/mail_config.php';

$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

$message = '';
$error = '';
$valid_token = false;
$user_id = null;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Vérifier si le token est valide et non expiré
    $stmt = $mysqli->prepare("
        SELECT user_id 
        FROM password_resets 
        WHERE token = ? AND expiry > NOW() AND used = 0
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $valid_token = true;
        $user_id = $result->fetch_assoc()['user_id'];
    } else {
        $error = "Le lien de réinitialisation est invalide ou a expiré.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Hasher le nouveau mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Mettre à jour le mot de passe
        $stmt = $mysqli->prepare("UPDATE User SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            // Marquer le token comme utilisé
            $stmt = $mysqli->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $message = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
        } else {
            $error = "Une erreur est survenue lors de la réinitialisation du mot de passe.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation du mot de passe - SNEAKER MARKET</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #000;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #666;
        }

        input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        button {
            width: 100%;
            padding: 0.8rem;
            background: #000;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #333;
        }

        .message {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 4px;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #666;
            text-decoration: none;
        }

        .back-link:hover {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Réinitialisation du mot de passe</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
            <a href="login.php" class="back-link">Retour à la connexion</a>
        <?php elseif ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
            <a href="forgot_password.php" class="back-link">Demander un nouveau lien</a>
        <?php elseif ($valid_token): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">Nouveau mot de passe</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                
                <button type="submit">Réinitialiser le mot de passe</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 