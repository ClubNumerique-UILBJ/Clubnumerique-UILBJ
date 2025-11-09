<?php
// register.php
session_start();
require 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $message = 'Veuillez remplir tous les champs.';
    } else {
        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $message = 'Ce nom d\'utilisateur est déjà pris.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')");
            if ($stmt->execute([$username, $password_hash])) {
                $message = 'Inscription réussie. Vous pouvez maintenant vous connecter.';
                header('Refresh:2; url=login.php');
                exit;
            } else {
                $message = 'Erreur lors de l\'inscription.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Inscription Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin:0; padding:0; }
        .container { max-width: 400px; margin: 100px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
        input, button { width: 100%; padding: 10px; margin-top: 10px; border-radius: 4px; border: 1px solid #ccc; font-size: 1rem; }
        button { background: #388E3C; color: white; border: none; cursor: pointer; }
        button:hover { background: #2e7d32; }
        .message { margin-top: 10px; color: red; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h2>Inscription Admin</h2>
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post" action="">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required autofocus />
        <input type="password" name="password" placeholder="Mot de passe" required />
        <button type="submit">S'inscrire</button>
    </form>
</div>
</body>
</html>
