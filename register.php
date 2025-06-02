<?php
session_start();
require_once 'includes/mail_config.php';

$mysqli = new mysqli("localhost", "root", "", "php_exam_db");

if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $mysqli->real_escape_string($_POST['username']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // sécurité

    // Vérifie si email ou username existent déjà
    $check = $mysqli->query("SELECT id FROM User WHERE username='$username' OR email='$email'");
    if ($check->num_rows > 0) {
        $message = "Nom d'utilisateur ou email déjà utilisé.";
    } else {
        $query = "INSERT INTO User (username, email, password) VALUES ('$username', '$email', '$password')";
        if ($mysqli->query($query)) {
            // Envoi de l'email de bienvenue
            $subject = "Bienvenue sur SNEAKER MARKET !";
            $body = "
                <h2>Bienvenue " . htmlspecialchars($username) . " !</h2>
                <p>Merci de vous être inscrit sur SNEAKER MARKET.</p>
                <p>Vous pouvez dès maintenant :</p>
                <ul>
                    <li>Parcourir notre catalogue de sneakers</li>
                    <li>Ajouter des articles à vos favoris</li>
                    <li>Passer des commandes</li>
                    <li>Gérer votre profil</li>
                </ul>
                <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
                <p>À bientôt sur SNEAKER MARKET !</p>
            ";
            
            if (sendMail($email, $subject, $body)) {
                $_SESSION['user_id'] = $mysqli->insert_id;
                $_SESSION['username'] = $username;
                header("Location: home.php");
                exit();
            } else {
                $message = "Compte créé mais erreur lors de l'envoi de l'email de bienvenue.";
            }
        } else {
            $message = "Erreur lors de l'inscription.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }

        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            font-weight: 700;
        }

        .error-message {
            background: #ffebee;
            color: #d32f2f;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        input {
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        input:focus {
            border-color:rgb(19, 19, 19);
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        button {
            background: black;
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        button:hover {
            transform: translateY(-2px);
        }

        button:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 1rem;
        }

        .login-link a {
            color: rgb(44, 44, 44);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: rgb(59, 181, 48);
        }

        @media (max-width: 480px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Inscription</h2>
        <?php if ($message) echo "<div class='error-message'>$message</div>"; ?>
        <form method="POST" action="register.php">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="email" name="email" placeholder="Adresse email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">S'inscrire</button>
        </form>
        <div class="login-link">
            <a href="login.php">Déjà inscrit ? Connectez-vous ici</a>
        </div>
    </div>
</body>
</html>
