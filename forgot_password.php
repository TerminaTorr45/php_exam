<?php
session_start();
require_once 'includes/mail_config.php';

$mysqli = new mysqli("localhost", "root", "", "php_exam_db");
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Vérifier si l'email existe
    $stmt = $mysqli->prepare("SELECT id, username FROM User WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Générer un token unique
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Sauvegarder le token dans la base de données
        $stmt = $mysqli->prepare("INSERT INTO password_resets (user_id, token, expiry) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user['id'], $token, $expiry);
        $stmt->execute();
        
        // Envoyer l'email
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
        $subject = "Réinitialisation de votre mot de passe - SNEAKER MARKET";
        $body = "
            <h2>Bonjour " . htmlspecialchars($user['username']) . ",</h2>
            <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
            <p>Cliquez sur le lien suivant pour réinitialiser votre mot de passe :</p>
            <p><a href='" . $reset_link . "'>Réinitialiser mon mot de passe</a></p>
            <p>Ce lien expirera dans 1 heure.</p>
            <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
        ";
        
        if (sendMail($email, $subject, $body)) {
            $message = "Un email de réinitialisation a été envoyé à votre adresse email.";
        } else {
            $error = "Une erreur est survenue lors de l'envoi de l'email.";
        }
    } else {
        $error = "Aucun compte n'est associé à cette adresse email.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe oublié - SNEAKER MARKET</title>
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

        input[type="email"] {
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
        <h1>Mot de passe oublié</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <button type="submit">Envoyer le lien de réinitialisation</button>
        </form>
        
        <a href="login.php" class="back-link">Retour à la connexion</a>
    </div>
</body>
</html> 