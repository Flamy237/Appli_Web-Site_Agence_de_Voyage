<?php
require_once __DIR__ . '/../app/models/Database.php';

$pdo = null;
try {
    $pdo = getPDOConnection();
} catch (Exception $e) {
    $error = "Impossible de se connecter à la base de données: " . $e->getMessage();
}

$errors = [];
$success = null;

// Edit mode: load existing classe when ?id= is present
$editClasse = null;
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($editId > 0 && $pdo) {
    $stmt = $pdo->prepare('SELECT id_classe, nom_classe, coefficient_prix, description FROM classe WHERE id_classe = :id');
    $stmt->execute([':id' => $editId]);
    $editClasse = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Handle POST for create/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Deletion request
    if (isset($_POST['delete_id']) && $pdo) {
        $delId = (int)$_POST['delete_id'];
        if ($delId > 0) {
            try {
                $s = $pdo->prepare('DELETE FROM classe WHERE id_classe = :id');
                $s->execute([':id' => $delId]);
                $success = 'Classe supprimée avec succès.';
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors[] = 'Impossible de supprimer : des billets utilisent cette classe.';
                } else {
                    $errors[] = 'Erreur lors de la suppression : ' . $e->getMessage();
                }
            }
        }
    }

    $id_classe = isset($_POST['id_classe']) ? (int)$_POST['id_classe'] : 0;
    $nom_classe = trim($_POST['nom_classe'] ?? '');
    $coefficient_prix = isset($_POST['coefficient_prix']) ? (float)$_POST['coefficient_prix'] : 1.0;
    $description = trim($_POST['description'] ?? '');

    // validation
    if ($nom_classe === '') $errors[] = 'Le nom de la classe est requis.';
    if ($coefficient_prix <= 0) $errors[] = 'Le coefficient de prix doit être positif.';
    if ($coefficient_prix > 5) $errors[] = 'Le coefficient de prix ne peut pas dépasser 5.';

    if (empty($errors) && $pdo) {
        try {
            if ($id_classe > 0) {
                // Update: ensure unique nom_classe except current
                $stmt = $pdo->prepare('SELECT id_classe FROM classe WHERE nom_classe = :nom AND id_classe != :id LIMIT 1');
                $stmt->execute([':nom' => $nom_classe, ':id' => $id_classe]);
                if ($stmt->fetch()) {
                    $errors[] = 'Une classe avec ce nom existe déjà.';
                } else {
                    $sql = 'UPDATE classe SET nom_classe = :nom, coefficient_prix = :coef, description = :desc WHERE id_classe = :id';
                    $s = $pdo->prepare($sql);
                    $s->execute([':nom' => $nom_classe, ':coef' => $coefficient_prix, ':desc' => $description, ':id' => $id_classe]);
                    $success = 'Classe mise à jour avec succès.';
                }
            } else {
                // Insert
                $sql = 'INSERT INTO classe (nom_classe, coefficient_prix, description) VALUES (:nom, :coef, :desc)';
                $s = $pdo->prepare($sql);
                $s->execute([':nom' => $nom_classe, ':coef' => $coefficient_prix, ':desc' => $description]);
                $success = 'Classe ajoutée avec succès.';
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'Conflit d\'unicité : cette classe existe déjà.';
            } else {
                $errors[] = 'Erreur lors de l\'enregistrement : ' . $e->getMessage();
            }
        }
    }

    // reload edit data if updating
    if (!empty($id_classe) && $pdo) {
        $stmt = $pdo->prepare('SELECT id_classe, nom_classe, coefficient_prix, description FROM classe WHERE id_classe = :id');
        $stmt->execute([':id' => $id_classe]);
        $editClasse = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

// Fetch all classes for listing
$classes = [];
if ($pdo) {
    $stmt = $pdo->query('SELECT id_classe, nom_classe, coefficient_prix, description FROM classe ORDER BY nom_classe');
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get DB info for badge
$db_name = defined('DB_NAME') ? DB_NAME : 'Unknown';
$table_name = 'classe';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les classes de voyage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-color: #e2e8f0;
            --text-muted: #94a3b8;
            --border-color: #475569;
            --accent-color: #3b82f6;
        }

        [data-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --bg-tertiary: #e2e8f0;
            --text-color: #0f172a;
            --text-muted: #64748b;
            --border-color: #cbd5e1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-color);
            line-height: 1.6;
        }

        .wrap {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h2 {
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge-db {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .theme-toggle {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            background: var(--accent-color);
            color: white;
        }

        .error {
            display: none;
            background: rgba(255, 107, 107, 0.15);
            border-left: 4px solid #ff6b6b;
            color: #ff6b6b;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success {
            background: rgba(81, 207, 102, 0.15);
            border-left: 4px solid #51cf66;
            color: #51cf66;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            font-weight: 600;
        }

        input, textarea, select {
            width: 100%;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--accent-color);
            color: white;
            width: 100%;
            justify-content: center;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-color);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--bg-tertiary);
            border-radius: 8px;
            margin-bottom: 12px;
            border-left: 4px solid var(--accent-color);
            gap: 15px;
        }

        .list-item-info {
            flex: 1;
        }

        .list-item-name {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .list-item-meta {
            font-size: 13px;
            color: var(--text-muted);
        }

        .list-item-actions {
            display: flex;
            gap: 10px;
        }

        .action-link {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            background: rgba(59, 130, 246, 0.1);
        }

        .action-link:hover {
            color: #60a5fa;
            background: rgba(59, 130, 246, 0.2);
        }

        .delete-btn {
            padding: 6px 12px;
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .delete-btn:hover {
            background: rgba(255, 107, 107, 0.2);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 36px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--accent-color);
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            transform: translateX(-4px);
        }

        @media (max-width: 768px) {
            .wrap {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <!-- Formulaire -->
        <div class="card">
            <a href="admin-dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Retour au dashboard
            </a>

            <div class="header">
                <h2>
                    <i class="fas fa-layer-group"></i>
                    <?php echo $editClasse ? 'Modifier' : 'Ajouter'; ?> une classe
                </h2>
                <button id="theme-toggle" class="theme-toggle" title="Changer le thème">
                    <i class="fas fa-sun"></i>
                </button>
            </div>

            <div class="badge-db">
                <i class="fas fa-database"></i>
                <?php echo htmlspecialchars($db_name); ?>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <strong>Erreurs:</strong><br>
                    <?php foreach ($errors as $err): ?>
                        • <?php echo htmlspecialchars($err); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form id="classeForm" method="POST">
                <input type="hidden" name="id_classe" value="<?php echo $editClasse ? (int)$editClasse['id_classe'] : ''; ?>">

                <div class="form-group">
                    <label for="nom_classe">Nom de la classe *</label>
                    <input type="text" id="nom_classe" name="nom_classe" placeholder="Ex: Standard, VIP, Business..." 
                           value="<?php echo $editClasse ? htmlspecialchars($editClasse['nom_classe']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="coefficient_prix">Coefficient de prix *</label>
                    <input type="number" id="coefficient_prix" name="coefficient_prix" placeholder="Ex: 1.00 pour standard, 1.50 pour VIP" 
                           min="0.1" max="5" step="0.01" 
                           value="<?php echo $editClasse ? (float)$editClasse['coefficient_prix'] : '1.00'; ?>" required>
                    <small style="color: var(--text-muted); margin-top: 6px; display: block;">
                        1.00 = prix standard | 1.50 = 50% plus cher
                    </small>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Décrivez les avantages et caractéristiques de cette classe..."><?php echo $editClasse ? htmlspecialchars($editClasse['description']) : ''; ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas <?php echo $editClasse ? 'fa-save' : 'fa-plus'; ?>"></i>
                    <?php echo $editClasse ? 'Mettre à jour' : 'Ajouter la classe'; ?>
                </button>
            </form>
        </div>

        <!-- Liste -->
        <div class="card">
            <div class="header">
                <h2>
                    <i class="fas fa-list"></i>
                    Classes existantes
                </h2>
            </div>

            <?php if (count($classes) > 0): ?>
                <div style="max-height: 600px; overflow-y: auto;">
                    <?php foreach ($classes as $c): ?>
                        <div class="list-item">
                            <div class="list-item-info">
                                <div class="list-item-name">
                                    <?php echo htmlspecialchars($c['nom_classe']); ?>
                                </div>
                                <div class="list-item-meta">
                                    Coefficient: <strong><?php echo number_format((float)$c['coefficient_prix'], 2); ?>x</strong>
                                    <?php if ($c['description']): ?>
                                        <br><?php echo htmlspecialchars(mb_substr($c['description'], 0, 50)); ?>...
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="list-item-actions">
                                <a class="action-link" href="?id=<?php echo (int)$c['id_classe']; ?>" title="Modifier">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <button type="button" class="delete-btn delete-trigger" data-delete-id="<?php echo (int)$c['id_classe']; ?>" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p><strong>Aucune classe trouvée</strong></p>
                    <p>Ajoutez une classe pour commencer.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; flex-direction: column; align-items: center; justify-content: center;">
        <div style="background: var(--bg-secondary); border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); width: 90%; max-width: 400px; animation: slideDown 0.3s ease;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: var(--text-color);">Confirmer la suppression</h3>
                <button type="button" id="modalClose" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-color);">&times;</button>
            </div>
            <div style="padding: 20px; color: var(--text-color);">
                <p>Êtes-vous sûr de vouloir supprimer <strong id="classeName"></strong> ?</p>
                <p style="color: #ff6b6b; font-size: 14px; margin-top: 10px;">⚠️ Cette action est irréversible.</p>
            </div>
            <div style="padding: 15px; border-top: 1px solid var(--border-color); display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" id="modalCancel" class="btn btn-secondary">Annuler</button>
                <button type="button" id="modalConfirm" class="btn" style="background: #ff6b6b; color: white;">Supprimer</button>
            </div>
        </div>
    </div>

    <style>
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <script>
        // Theme toggle
        const themeToggle = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;

        function updateTheme(theme) {
            htmlElement.setAttribute('data-theme', theme);
            themeToggle.innerHTML = `<i class="fas ${theme === 'dark' ? 'fa-sun' : 'fa-moon'}"></i>`;
            localStorage.setItem('theme', theme);
        }

        const savedTheme = localStorage.getItem('theme') || 'dark';
        updateTheme(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            updateTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });

        // Modal functionality
        const deleteModal = document.getElementById('deleteModal');
        const modalClose = document.getElementById('modalClose');
        const modalCancel = document.getElementById('modalCancel');
        const modalConfirm = document.getElementById('modalConfirm');
        let deleteClasseId = null;

        function closeModal() {
            deleteModal.style.display = 'none';
            deleteClasseId = null;
        }

        modalClose.addEventListener('click', closeModal);
        modalCancel.addEventListener('click', closeModal);

        window.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                closeModal();
            }
        });

        // Delete trigger buttons
        document.querySelectorAll('.delete-trigger').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-delete-id');
                const row = this.closest('.list-item');
                const classeInfo = row.querySelector('.list-item-name').textContent || 'cette classe';
                
                deleteClasseId = id;
                document.getElementById('classeName').textContent = classeInfo;
                deleteModal.style.display = 'flex';
            });
        });

        // Confirm delete
        modalConfirm.addEventListener('click', function() {
            if (deleteClasseId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_id" value="' + deleteClasseId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        });

        // Form validation
        document.getElementById('classeForm').addEventListener('submit', function(e) {
            const errorDiv = document.querySelector('.error');
            errorDiv.style.display = 'none';
            errorDiv.innerHTML = '';

            const nomClasse = document.querySelector('input[name="nom_classe"]').value.trim();
            const coefficient = document.querySelector('input[name="coefficient_prix"]').value;

            let hasError = false;

            if (!nomClasse) {
                errorDiv.innerHTML = '<strong>Erreur:</strong><br>• Le nom de la classe ne peut pas être vide.';
                hasError = true;
            } else if (!coefficient || coefficient <= 0 || coefficient > 5) {
                errorDiv.innerHTML = '<strong>Erreur:</strong><br>• Le coefficient doit être entre 0.1 et 5.';
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
                errorDiv.style.display = 'block';
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ En cours...';
        });
    </script>
</body>
</html>
