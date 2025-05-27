<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "php_exam_db");

if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $mysqli->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $result = $mysqli->query("SELECT id, password FROM User WHERE username='$username'");
    if ($result && $row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $username;
            header("Location: home.php");
            exit();
        } else {
            $message = "Mot de passe incorrect.";
        }
    } else {
        $message = "Utilisateur non trouvé.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
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

        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            margin: 1rem;
        }

        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            font-weight: 700;
        }

        .error-message {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.9rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        input {
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color:rgb(48, 48, 48);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        button {
            background: linear-gradient(135deg,rgb(75, 246, 59) 0%,rgb(30, 30, 30) 100%);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        button:active {
            transform: translateY(0);
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Connexion</h2>
        <?php if ($message) echo "<div class='error-message'>$message</div>"; ?>
        <form method="POST" action="login.php">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
        <div style="text-align: center; margin-top: 1rem;">
            <a href="register.php" style="text-decoration: none;">
                <button type="button" style="background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);">Créer un compte</button>
            </a>
        </div>
    </div>
</body>
</html>
