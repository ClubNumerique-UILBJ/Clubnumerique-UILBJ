<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require 'config.php';

// Vérification de la connexion admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
  
// Connexion à la base de données
$host = 'mysql-clubnumerique-uilbj.alwaysdata.net'; // adapte selon ton hôte AlwaysData
$dbname = 'clubnumerique-uilbj_minitube_db';
$user = '420028';
$password = 'CNUIL2025';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Variables pour messages d’erreur/succès
$message = '';
$error = '';

// Traitement des actions POST : ajout, modification, suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage des données
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? '';
        $url = trim($_POST['url'] ?? '');
        $auteur = trim($_POST['auteur'] ?? '');
        $categorie = trim($_POST['categorie'] ?? '');

        // Validation simple
        if (!$titre || !$description || !in_array($type, ['video', 'image']) || !$url) {
            $error = "Tous les champs obligatoires doivent être remplis correctement.";
        } else {
            if ($action === 'add') {
                // Insertion
                $stmt = $pdo->prepare("INSERT INTO tutos (titre, description, type, url, auteur, categorie) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$titre, $description, $type, $url, $auteur, $categorie])) {
                    $message = "Tuto ajouté avec succès.";
                } else {
                    $error = "Erreur lors de l'ajout du tuto.";
                }
            } elseif ($action === 'edit' && $id > 0) {
                // Mise à jour
                $stmt = $pdo->prepare("UPDATE tutos SET titre = ?, description = ?, type = ?, url = ?, auteur = ?, categorie = ? WHERE id = ?");
                if ($stmt->execute([$titre, $description, $type, $url, $auteur, $categorie, $id])) {
                    $message = "Tuto modifié avec succès.";
                } else {
                    $error = "Erreur lors de la modification du tuto.";
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM tutos WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = "Tuto supprimé avec succès.";
            } else {
                $error = "Erreur lors de la suppression du tuto.";
            }
        } else {
            $error = "ID invalide pour la suppression.";
        }
    }
}

// Pagination
$perPage = 10; // nombre de tutos par page en admin (modifiable)
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Récupérer le nombre total de tutos
$totalStmt = $pdo->query("SELECT COUNT(*) FROM tutos");
$totalTutos = $totalStmt->fetchColumn();
$totalPages = ceil($totalTutos / $perPage);

// Récupérer les tutos de la page courante
$stmt = $pdo->prepare("SELECT * FROM tutos ORDER BY date_publication DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$tutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pour modifier un tuto : récupérer les données si demandé
$editTuto = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmtEdit = $pdo->prepare("SELECT * FROM tutos WHERE id = ?");
    $stmtEdit->execute([$editId]);
    $editTuto = $stmtEdit->fetch(PDO::FETCH_ASSOC);
}

require 'config.php';
session_start();
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Admin - Gestion des tutos Minitube</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        h1 { color: #388E3C; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        a.button, button { background: #388E3C; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        a.button:hover, button:hover { background: #2e7d32; }
        form { margin-bottom: 2rem; background: #f9f9f9; padding: 1rem; border-radius: 8px; }
        label { display: block; margin-top: 0.5rem; }
        input[type=text], textarea, select { width: 100%; padding: 6px; margin-top: 4px; border-radius: 4px; border: 1px solid #ccc; }
        textarea { resize: vertical; height: 80px; }
        .message { padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 1rem; }
        .error { padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 1rem; }
        .pagination { margin-bottom: 2rem; }
        .pagination a {
            margin: 0 3px; padding: 6px 10px; background: #388E3C; color: white; border-radius: 4px; text-decoration: none;
        }
        .pagination a.current {
            background: #2e7d32; font-weight: bold;
        }
    </style>
</head>
<body>

<h1>Administration des tutos</h1>

<p>Connecté en tant que <strong><?=htmlspecialchars($_SESSION['admin_username'])?></strong> - <a href="logout.php">Déconnexion</a></p>

<?php if ($message): ?>
    <div class="message"><?=htmlspecialchars($message)?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="error"><?=htmlspecialchars($error)?></div>
<?php endif; ?>

<!-- Formulaire ajout/modification -->
<form method="post" action="">
    <h2><?= $editTuto ? "Modifier un tuto" : "Ajouter un nouveau tuto" ?></h2>
    <input type="hidden" name="action" value="<?= $editTuto ? "edit" : "add" ?>">
    <?php if ($editTuto): ?>
        <input type="hidden" name="id" value="<?= (int)$editTuto['id'] ?>">
    <?php endif; ?>

    <label for="titre">Titre *</label>
    <input type="text" id="titre" name="titre" required value="<?= htmlspecialchars($editTuto['titre'] ?? '') ?>">

    <label for="description">Description *</label>
    <textarea id="description" name="description" required><?= htmlspecialchars($editTuto['description'] ?? '') ?></textarea>

    <label for="type">Type *</label>
    <select id="type" name="type" required>
        <option value="">-- Choisir --</option>
        <option value="video" <?= (isset($editTuto['type']) && $editTuto['type'] === 'video') ? 'selected' : '' ?>>Vidéo</option>
        <option value="image" <?= (isset($editTuto['type']) && $editTuto['type'] === 'image') ? 'selected' : '' ?>>Image</option>
    </select>

    <label for="url">URL / Chemin du fichier *</label>
    <input type="text" id="url" name="url" required value="<?= htmlspecialchars($editTuto['url'] ?? '') ?>">

    <label for="auteur">Auteur</label>
    <input type="text" id="auteur" name="auteur" value="<?= htmlspecialchars($editTuto['auteur'] ?? '') ?>">

    <label for="categorie">Catégorie / Tags</label>
    <input type="text" id="categorie" name="categorie" value="<?= htmlspecialchars($editTuto['categorie'] ?? '') ?>">

    <button type="submit"><?= $editTuto ? "Modifier" : "Ajouter" ?></button>
    <?php if ($editTuto): ?>
        <a href="admin.php" class="button" style="background:#777; margin-left:10px;">Annuler</a>
    <?php endif; ?>
</form>

<!-- Liste des tutos -->
<h2>Liste des tutos (<?= $totalTutos ?>)</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Type</th>
            <th>Auteur</th>
            <th>Catégorie</th>
            <th>Date publication</th>
            <th>Likes</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($tutos)): ?>
            <tr><td colspan="8" style="text-align:center;">Aucun tuto trouvé.</td></tr>
        <?php else: ?>
            <?php foreach ($tutos as $tuto): ?>
                <tr>
                    <td><?= (int)$tuto['id'] ?></td>
                    <td><?= htmlspecialchars($tuto['titre']) ?></td>
                    <td><?= htmlspecialchars($tuto['type']) ?></td>
                    <td><?= htmlspecialchars($tuto['auteur']) ?></td>
                    <td><?= htmlspecialchars($tuto['categorie']) ?></td>
                    <td><?= htmlspecialchars($tuto['date_publication']) ?></td>
                    <td><?= (int)$tuto['likes'] ?></td>
                    <td>
                        <a href="?edit=<?= (int)$tuto['id'] ?>" class="button">Modifier</a>
                        <form method="post" action="" style="display:inline;" onsubmit="return confirm('Confirmer la suppression ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$tuto['id'] ?>">
                            <button type="submit" style="background:#c0392b;">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination -->
<div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?page=<?= $p ?>" class="<?= ($p === $page) ? 'current' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
</div>

</body>
</html>

