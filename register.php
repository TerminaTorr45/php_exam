<?php
session_start();
$mysqli = new mysqli("localhost", "root", "root", "php_exam_db");

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
            $_SESSION['user_id'] = $mysqli->insert_id;
            $_SESSION['username'] = $username;
            header("Location: home.php");
            exit();
        } else {
            $message = "Erreur lors de l'inscription.";
        }
    }
}
?>

<h2>Inscription</h2>
<?php if ($message) echo "<p style='color:red;'>$message</p>"; ?>
<form method="POST" action="register.php">
    <input type="text" name="username" placeholder="Nom d'utilisateur" required><br>
    <input type="email" name="email" placeholder="Adresse email" required><br>
    <input type="password" name="password" placeholder="Mot de passe" required><br>
    <button type="submit">S'inscrire</button>
</form>
