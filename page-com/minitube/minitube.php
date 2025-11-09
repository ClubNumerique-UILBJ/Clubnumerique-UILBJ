<?php
session_start();
require 'config.php';

// ==========================================
// GESTION DES LIKES
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_tuto_id'])) {
    $tuto_id = intval($_POST['like_tuto_id']);
    $cookie_name = "liked_tuto_$tuto_id";

    if (!isset($_COOKIE[$cookie_name])) {
        $stmt = $pdo->prepare("UPDATE tutos SET likes = likes + 1 WHERE id = ?");
        $stmt->execute([$tuto_id]);
        setcookie($cookie_name, '1', time() + 365*24*3600, "/");
    }
    
    $redirect = $_SERVER['PHP_SELF'] . '?' . http_build_query([
        'page' => $_GET['page'] ?? 1,
        'search' => $_GET['search'] ?? '',
        'category' => $_GET['category'] ?? '',
        'type' => $_GET['type'] ?? '',
        'sort' => $_GET['sort'] ?? ''
    ]);
    header("Location: $redirect");
    exit;
}

// ==========================================
// GESTION DES COMMENTAIRES
// ==========================================
$comment_message = '';
$comment_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_tuto_id'])) {
    $tuto_id = intval($_POST['comment_tuto_id']);
    $auteur = trim($_POST['auteur'] ?? 'Anonyme');
    $contenu = trim($_POST['contenu'] ?? '');

    if ($contenu !== '') {
        $stmt = $pdo->prepare("INSERT INTO comments (tuto_id, auteur, contenu, modere) VALUES (?, ?, ?, 0)");
        $stmt->execute([$tuto_id, $auteur ?: 'Anonyme', $contenu]);
        $comment_message = "Commentaire envoyé ! Il sera visible après modération.";
    } else {
        $comment_error = "Le commentaire ne peut pas être vide.";
    }
}

// ==========================================
// PARAMÈTRES DE RECHERCHE ET FILTRES
// ==========================================
$search = trim($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$type_filter = $_GET['type'] ?? '';
$sort_order = $_GET['sort'] ?? 'recent';

// ==========================================
// CONSTRUCTION DE LA REQUÊTE SQL
// ==========================================
$where_conditions = [];
$params = [];

if ($search !== '') {
    $where_conditions[] = "(titre LIKE ? OR description LIKE ? OR auteur LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($category_filter !== '') {
    $where_conditions[] = "categorie = ?";
    $params[] = $category_filter;
}

if ($type_filter !== '') {
    $where_conditions[] = "type = ?";
    $params[] = $type_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$order_clause = match($sort_order) {
    'popular' => 'ORDER BY likes DESC, date_publication DESC',
    'oldest' => 'ORDER BY date_publication ASC',
    default => 'ORDER BY date_publication DESC'
};

// ==========================================
// PAGINATION
// ==========================================
$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM tutos $where_clause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalTutos = $countStmt->fetchColumn();
$totalPages = ceil($totalTutos / $perPage);

$sql = "SELECT * FROM tutos $where_clause $order_clause LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$tutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des catégories
$categoriesStmt = $pdo->query("SELECT DISTINCT categorie FROM tutos ORDER BY categorie");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

// ==========================================
// FONCTIONS
// ==========================================


function getComments(PDO $pdo, int $tuto_id) {
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE tuto_id = ? AND modere = 1 ORDER BY date_comment DESC LIMIT 20");
    $stmt->execute([$tuto_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCommentCount(PDO $pdo, int $tuto_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE tuto_id = ? AND modere = 1");
    $stmt->execute([$tuto_id]);
    return $stmt->fetchColumn();
}

function getInitials($name) {
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return "À l'instant";
    if ($diff < 3600) return floor($diff / 60) . " min";
    if ($diff < 86400) return floor($diff / 3600) . " h";
    if ($diff < 604800) return floor($diff / 86400) . " j";
    
    return date('d/m/Y', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Minitube – Club Numérique UIL-BJ</title>
    <meta name="description" content="Feed de contenus numériques et tutoriels vidéos du Club Numérique UIL-BJ" />
    <link rel="stylesheet" href="minitube-style.css" />
</head>
<body>

<!-- HEADER -->
<header class="menu-cadre">
    <a href="index.html" class="logo-club" aria-label="Accueil">
        <img src="Images/logo-club.webp" loading="lazy" alt="Logo Club" width="48" height="48" />
    </a>

    <input type="checkbox" id="menu-toggle" class="menu-toggle" />
    <label for="menu-toggle" class="menu-icon" aria-label="Menu">☰</label>

    <nav class="menu-nav" role="navigation">
        <ul>
            <li><a href="index.html">Accueil</a></li>
            <li><a href="page-act/act.html">Activités</a></li>
            <li><a href="page-membres/membres.html">Membres</a></li>
            <li><a href="page-com/com.html">Communication</a></li>
        </ul>
    </nav>

    <a href="https://uil-universite.com" class="logo-universite" target="_blank" rel="noopener noreferrer">
        <img src="Images/logo-universite.webp" loading="lazy" alt="Logo Université" width="48" height="48" />
    </a>
</header>

<!-- MAIN CONTENT -->
<main class="main-container">
    
    <!-- Header Page -->
    <div class="page-header">
        <h1>🎬 Minitube</h1>
        <p class="page-subtitle">Découvrez les derniers contenus du Club Numérique</p>
    </div>

    <!-- Messages -->
    <?php if (!empty($comment_message)): ?>
        <div class="message success">✓ <?= htmlspecialchars($comment_message) ?></div>
    <?php endif; ?>
    <?php if (!empty($comment_error)): ?>
        <div class="message error">✗ <?= htmlspecialchars($comment_error) ?></div>
    <?php endif; ?>

    <!-- Recherche et Filtres -->
    <section class="filter-section">
        <form method="get" action="" class="search-bar">
            <input 
                type="text" 
                name="search" 
                class="search-input" 
                placeholder="🔍 Rechercher..." 
                value="<?= htmlspecialchars($search) ?>"
            />
            <button type="submit" class="search-btn">Rechercher</button>
        </form>

        <div class="filter-options">
            <form method="get" style="display: contents;">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>" />
                
                <select name="category" class="filter-select" onchange="this.form.submit()">
                    <option value="">Toutes catégories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category_filter === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="type" class="filter-select" onchange="this.form.submit()">
                    <option value="">Tous types</option>
                    <option value="video" <?= $type_filter === 'video' ? 'selected' : '' ?>>📹 Vidéos</option>
                    <option value="image" <?= $type_filter === 'image' ? 'selected' : '' ?>>🖼️ Images</option>
                </select>

                <select name="sort" class="filter-select" onchange="this.form.submit()">
                    <option value="recent" <?= $sort_order === 'recent' ? 'selected' : '' ?>>🕐 Récents</option>
                    <option value="popular" <?= $sort_order === 'popular' ? 'selected' : '' ?>>🔥 Populaires</option>
                    <option value="oldest" <?= $sort_order === 'oldest' ? 'selected' : '' ?>>📅 Anciens</option>
                </select>

                <input type="hidden" name="page" value="1" />
            </form>

            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="reset-btn">Réinitialiser</a>
        </div>

        <div class="results-count">
            <?= $totalTutos ?> résultat<?= $totalTutos > 1 ? 's' : '' ?>
        </div>
    </section>

    <!-- Feed de posts -->
    <?php if (empty($tutos)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔍</div>
            <h2>Aucun contenu trouvé</h2>
            <p>Essayez de modifier vos filtres ou <a href="<?= $_SERVER['PHP_SELF'] ?>">réinitialisez la recherche</a>.</p>
        </div>
    <?php else: ?>
        <div class="posts-feed">
            <?php foreach ($tutos as $tuto): ?>
                <article class="post-card" id="post-<?= $tuto['id'] ?>">
                    
                    <!-- En-tête -->
                    <div class="post-header">
                        <div class="post-author-info">
                            <div class="author-avatar">
                                <?= getInitials($tuto['auteur']) ?>
                            </div>
                            <div class="author-details">
                                <div class="author-name"><?= htmlspecialchars($tuto['auteur']) ?></div>
                                <div class="post-date"><?= timeAgo($tuto['date_publication']) ?></div>
                            </div>
                        </div>
                        <div class="post-category"><?= htmlspecialchars($tuto['categorie']) ?></div>
                    </div>

                    <!-- Titre -->
                    <div class="post-title">
                        <h2><?= htmlspecialchars($tuto['titre']) ?></h2>
                    </div>

                    <!-- Description -->
                    <?php if (!empty($tuto['description'])): ?>
                        <div class="post-description">
                            <?= nl2br(htmlspecialchars($tuto['description'])) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Média -->
                    <div class="post-media <?= $tuto['type'] === 'video' ? 'video' : 'image' ?>">
                        <?php
                        if ($tuto['type'] === 'video') {
                            echo embedVideo($tuto['url']);
                        } else {
                            echo '<img src="' . htmlspecialchars($tuto['url']) . '" alt="' . htmlspecialchars($tuto['titre']) . '" loading="lazy">';
                        }
                        ?>
                    </div>

                    <!-- Actions -->
                    <div class="post-actions">
                        <?php
                        $likeCookie = "liked_tuto_" . $tuto['id'];
                        $hasLiked = isset($_COOKIE[$likeCookie]);
                        $commentCount = getCommentCount($pdo, (int)$tuto['id']);
                        ?>
                        
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="like_tuto_id" value="<?= (int)$tuto['id'] ?>">
                            <button type="submit" class="action-btn <?= $hasLiked ? 'liked' : '' ?>" <?= $hasLiked ? 'disabled' : '' ?>>
                                <?= $hasLiked ? '✅' : '👍' ?>
                                <span class="count"><?= (int)$tuto['likes'] ?></span>
                            </button>
                        </form>

                        <div class="action-btn" onclick="toggleComments(<?= $tuto['id'] ?>)" style="cursor: pointer;">
                            💬 <span class="count"><?= $commentCount ?></span>
                        </div>
                    </div>

                    <!-- Commentaires -->
                    <section class="comments-section" id="comments-<?= $tuto['id'] ?>" style="display: none;">
                        <div class="comments-list">
                            <?php
                            $comments = getComments($pdo, (int)$tuto['id']);
                            if (empty($comments)) {
                                echo '<p class="no-comments">Soyez le premier à commenter 🎉</p>';
                            } else {
                                foreach ($comments as $comment) {
                                    echo '<div class="comment">';
                                    echo '<div class="comment-header">';
                                    echo '<span class="comment-author">' . htmlspecialchars($comment['auteur']) . '</span>';
                                    echo '<span class="comment-date">' . timeAgo($comment['date_comment']) . '</span>';
                                    echo '</div>';
                                    echo '<p class="comment-text">' . nl2br(htmlspecialchars($comment['contenu'])) . '</p>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>

                        <!-- Formulaire -->
                        <form method="post" class="comment-form">
                            <input type="hidden" name="comment_tuto_id" value="<?= (int)$tuto['id'] ?>">
                            <input 
                                type="text" 
                                name="auteur" 
                                placeholder="Votre nom (optionnel)" 
                                maxlength="100" 
                            />
                            <textarea 
                                name="contenu" 
                                placeholder="Écrivez un commentaire..." 
                                required 
                                maxlength="1000"
                            ></textarea>
                            <button type="submit">Publier</button>
                        </form>
                    </section>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $query_params = [
                'search' => $search,
                'category' => $category_filter,
                'type' => $type_filter,
                'sort' => $sort_order
            ];
            
            if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($query_params, ['page' => $page - 1])) ?>">← Précédent</a>
            <?php endif; ?>

            <?php
            $range = 2;
            $start = max(1, $page - $range);
            $end = min($totalPages, $page + $range);

            if ($start > 1): ?>
                <a href="?<?= http_build_query(array_merge($query_params, ['page' => 1])) ?>">1</a>
                <?php if ($start > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $start; $p <= $end; $p++): ?>
                <a href="?<?= http_build_query(array_merge($query_params, ['page' => $p])) ?>" 
                   class="<?= ($p === $page) ? 'current' : '' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="?<?= http_build_query(array_merge($query_params, ['page' => $totalPages])) ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($query_params, ['page' => $page + 1])) ?>">Suivant →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<!-- FOOTER -->
<footer class="footer">
    <p>© 2025 Club Numérique UIL-BJ — Université Internationale de Libreville</p>
    <p>Développé par <a href="https://www.linkedin.com/in/michel-ulcede-edou" target="_blank">EDOU Michel Ulcède</a></p>
</footer>

<script>
// Toggle commentaires
function toggleComments(postId) {
    const commentsSection = document.getElementById('comments-' + postId);
    if (commentsSection.style.display === 'none') {
        commentsSection.style.display = 'block';
    } else {
        commentsSection.style.display = 'none';
    }
}

// Auto-expand textarea
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('.comment-form textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
});
</script>

</body>
</html>