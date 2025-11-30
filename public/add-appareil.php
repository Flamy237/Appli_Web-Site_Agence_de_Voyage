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

// Load transports (moyen_transport) for select
$transports = [];
if ($pdo) {
    $stmt = $pdo->query('SELECT id_transport, type, nom_compagnie, numero FROM moyen_transport ORDER BY type, nom_compagnie');
    $transports = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Edit mode: load existing appareil when ?id= is present
$editAppareil = null;
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($editId > 0 && $pdo) {
    $stmt = $pdo->prepare('SELECT id_appareil, id_transport, numero_identification, capacite_totale FROM appareil WHERE id_appareil = :id');
    $stmt->execute([':id' => $editId]);
    $editAppareil = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Handle POST for create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Deletion request
    if (isset($_POST['delete_id']) && $pdo) {
        $delId = (int)$_POST['delete_id'];
        if ($delId > 0) {
            try {
                $s = $pdo->prepare('DELETE FROM appareil WHERE id_appareil = :id');
                $s->execute([':id' => $delId]);
                $success = 'Appareil supprimé.';
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors[] = 'Impossible de supprimer : des trajets ou dépendances existent pour cet appareil.';
                } else {
                    $errors[] = 'Erreur lors de la suppression : ' . $e->getMessage();
                }
            }
        }
    }

    $id_appareil = isset($_POST['id_appareil']) ? (int)$_POST['id_appareil'] : 0;
    $id_transport = isset($_POST['id_transport']) ? (int)$_POST['id_transport'] : 0;
    $numero_identification = trim($_POST['numero_identification'] ?? '');
    $capacite_totale = isset($_POST['capacite_totale']) ? (int)$_POST['capacite_totale'] : 0;

    // validation
    if ($id_transport <= 0) $errors[] = 'Veuillez choisir un moyen de transport.';
    if ($numero_identification === '') $errors[] = 'Le numéro/identifiant de l\'appareil est requis.';
    if ($capacite_totale <= 0) $errors[] = 'La capacité totale doit être un nombre positif.';

    if (empty($errors) && $pdo) {
        try {
            if ($id_appareil > 0) {
                // Update: ensure unique numero_identification except current
                $stmt = $pdo->prepare('SELECT id_appareil FROM appareil WHERE numero_identification = :num AND id_appareil != :id LIMIT 1');
                $stmt->execute([':num' => $numero_identification, ':id' => $id_appareil]);
                if ($stmt->fetch()) {
                    $errors[] = 'Un appareil avec ce numéro existe déjà.';
                } else {
                    $sql = 'UPDATE appareil SET id_transport = :id_transport, numero_identification = :num, capacite_totale = :cap WHERE id_appareil = :id';
                    $s = $pdo->prepare($sql);
                    $s->execute([':id_transport' => $id_transport, ':num' => $numero_identification, ':cap' => $capacite_totale, ':id' => $id_appareil]);
                    $success = 'Appareil mis à jour avec succès.';
                }
            } else {
                // Insert
                $sql = 'INSERT INTO appareil (id_transport, numero_identification, capacite_totale) VALUES (:id_transport, :num, :cap)';
                $s = $pdo->prepare($sql);
                $s->execute([':id_transport' => $id_transport, ':num' => $numero_identification, ':cap' => $capacite_totale]);
                $success = 'Appareil ajouté avec succès.';
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'Conflit d\'unicité : cet identifiant existe déjà.';
            } else {
                $errors[] = 'Erreur lors de l\'enregistrement : ' . $e->getMessage();
            }
        }
    }

    // reload edit data if updating
    if (!empty($id_appareil) && $pdo) {
        $stmt = $pdo->prepare('SELECT id_appareil, id_transport, numero_identification, capacite_totale FROM appareil WHERE id_appareil = :id');
        $stmt->execute([':id' => $id_appareil]);
        $editAppareil = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

// Fetch all appareils for listing
$appareils = [];
if ($pdo) {
    $stmt = $pdo->query('SELECT a.id_appareil, a.id_transport, a.numero_identification, a.capacite_totale, mt.type, mt.nom_compagnie FROM appareil a JOIN moyen_transport mt ON a.id_transport = mt.id_transport ORDER BY mt.type, a.numero_identification');
    $appareils = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!doctype html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ajouter / Modifier un appareil | Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <?php $cssBase = rtrim(dirname($_SERVER['PHP_SELF']), '/\\'); ?>
    <!-- Diagnostic: computed CSS href -->
    <!-- CSS href: <?php echo $cssBase; ?>/css/style.css -->
    <link rel="stylesheet" href="<?php echo $cssBase; ?>/css/style.css">
    <!-- Fallbacks: try relative and absolute paths too (helps when vhost differs) -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="/<?php echo trim($_SERVER['SCRIPT_NAME'], '/'); ?>../css/style.css">
    <!-- Tiny inline test style (will confirm PHP executed) -->
    <style>
        /* debug: body highlight should appear if PHP and styles are working */
        body { --debug-bg: unset; }
        @media print { body { --debug-bg: unset; } }
    </style>
    <style>
        :root { --font-family: 'Poppins', sans-serif; }
        html,body { height:100%; }
        body {
            font-family: var(--font-family);
            background: #0f172a;
            color: #e2e8f0;
            margin: 0;
            padding: 40px;
        }

        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 24px;
            align-items: start;
        }

        .card {
            background: rgba(255,255,255,0.03);
            padding: 22px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.04);
        }

        header.site { grid-column: 1 / -1; }
        header.site h1 { margin:0; font-size:22px; }

        label { display:block; margin-bottom:6px; font-weight:600; }

        input[type=text], input[type=number], select {
            width:100%;
            padding:12px;
            border-radius:8px;
            border:1px solid rgba(255,255,255,0.06);
            background: rgba(255,255,255,0.02);
            color: inherit;
            box-sizing: border-box;
        }

        .btn {
            background: linear-gradient(90deg,#7c3aed,#06b6d4);
            color:#fff;
            padding:12px 16px;
            border-radius:10px;
            border:none;
            cursor:pointer;
            display:inline-flex;align-items:center;gap:8px;
        }

        .error { background:#4c1f1f; color:#ffdede; padding:12px; border-radius:8px; margin-bottom:12px; }
        .success { background:#10372d; color:#c8f7df; padding:12px; border-radius:8px; margin-bottom:12px; }

        .list-transport { margin-top:10px; }

        .transport-item {
            padding:10px;
            border-radius:8px;
            background: rgba(255,255,255,0.02);
            display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:8px;
        }

        .small { font-size:13px; color:#94a3b8; }

        /* Badges */
        .badge { display:inline-flex; align-items:center; gap:8px; padding:6px 12px; border-radius:999px; background:linear-gradient(90deg, rgba(124,58,237,0.12), rgba(6,182,212,0.06)); color:#e6eef6; font-size:13px; border:1px solid rgba(255,255,255,0.06); box-shadow:0 6px 18px rgba(2,6,23,0.28); }
        .badge i { width:20px; text-align:center; }
        .badge__meta { display:inline-flex; flex-direction:column; line-height:1; }
        .badge__label { font-size:10px; text-transform:uppercase; opacity:0.9; }
        .badge__value { font-size:13px; font-weight:700; }

        .actions { display:flex; gap:8px; }
        .muted { color: #94a3b8; font-size:13px }

        /* icon in list */
        .ti-icon { width:40px; height:40px; display:flex; align-items:center; justify-content:center; background: rgba(255,255,255,0.02); border-radius:8px; color:#7c3aed; font-size:18px; }
        .action-link { color: #cbd5e1; text-decoration:none; display:inline-flex; gap:6px; align-items:center; }
        .action-link i { font-size:14px }
        .delete-btn { background:transparent; border:none; color:#ff6b6b; cursor:pointer; padding:6px; border-radius:6px; }
        .delete-btn i { font-size:14px }
        .action-link:hover, .delete-btn:hover { transform:translateY(-2px); }

        @media (max-width:880px) {
            .wrap { grid-template-columns: 1fr; }
            header.site .card { flex-direction:column; gap:12px; align-items:flex-start; }
            .badge { padding:6px 8px; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/inc/sidebar.php'; ?>
<div class="wrap">
    <header class="site">
        <div class="card" style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <h1><i class="fas fa-cogs" style="color:#7c3aed"></i> Gérer les appareils</h1>
                <div class="small">Ajoute ou modifie un appareil (train, avion, bus...).</div>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
                <div style="display:flex;gap:8px;align-items:center">
                    <span class="badge">Table: <strong>appareil</strong></span>
                    <span class="badge">DB: <strong><?php echo htmlentities(DB_NAME); ?></strong></span>
                </div>
                <button id="theme-toggle" title="Changer de thème" style="width:44px;height:44px;border-radius:999px;border:1px solid rgba(255,255,255,0.06);background:transparent;color:inherit"><i class="fas fa-moon"></i></button>
            </div>
        </div>
    </header>

    <main class="card">
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlentities($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="error"><strong>Erreurs :</strong><ul><?php foreach ($errors as $e): ?><li><?php echo htmlentities($e); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <?php if ($success): ?><div class="success"><?php echo htmlentities($success); ?></div><?php endif; ?>

        <form method="post" id="appareilForm">
            <input type="hidden" name="id_appareil" value="<?php echo isset($editAppareil['id_appareil']) ? (int)$editAppareil['id_appareil'] : 0; ?>">
            <div style="display:grid;gap:14px;">
                <div>
                    <label for="id_transport">Type / Compagnie</label>
                    <select name="id_transport" id="id_transport" required>
                        <option value="">— Choisir un moyen de transport —</option>
                        <?php foreach ($transports as $t): ?>
                            <option value="<?php echo (int)$t['id_transport']; ?>" <?php echo (isset($editAppareil['id_transport']) && $editAppareil['id_transport'] == $t['id_transport']) ? 'selected' : ''; ?>><?php echo htmlentities($t['type'] . ' — ' . $t['nom_compagnie']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="numero_identification">Numéro / Identification</label>
                    <input type="text" id="numero_identification" name="numero_identification" placeholder="Ex: Rame TGV 45, Airbus A320" value="<?php echo isset($editAppareil['numero_identification']) ? htmlentities($editAppareil['numero_identification']) : ''; ?>" required />
                </div>

                <div>
                    <label for="capacite_totale">Capacité totale</label>
                    <input type="number" id="capacite_totale" name="capacite_totale" min="1" value="<?php echo isset($editAppareil['capacite_totale']) ? (int)$editAppareil['capacite_totale'] : ''; ?>" required />
                </div>

                <div style="display:flex;justify-content:flex-end;margin-top:6px">
                    <button class="btn" type="submit"><i class="fas fa-save"></i> <?php echo $editAppareil ? 'Mettre à jour' : 'Enregistrer'; ?></button>
                </div>
            </div>
        </form>
    </main>

    <aside>
        <div class="card">
            <h3 style="margin-top:0">Appareils existants</h3>
            <div class="list-transport">
                <?php if (empty($appareils)): ?>
                    <div class="small">Aucun enregistrement trouvé.</div>
                <?php else: ?>
                    <?php foreach ($appareils as $a): ?>
                        <?php
                            $typeLower = strtolower($a['type'] ?? '');
                            $iconClass = 'fa-cogs';
                            if (strpos($typeLower, 'train') !== false) $iconClass = 'fa-train';
                            else if (strpos($typeLower, 'avion') !== false || strpos($typeLower, 'air') !== false) $iconClass = 'fa-plane';
                            else if (strpos($typeLower, 'bus') !== false) $iconClass = 'fa-bus';
                        ?>
                        <div class="transport-item">
                            <div style="display:flex;gap:12px;align-items:center">
                                <div class="ti-icon"><i class="fas <?php echo $iconClass; ?>" aria-hidden="true"></i></div>
                                <div>
                                    <div style="font-weight:700"><?php echo htmlentities($a['numero_identification'] . ' — ' . $a['type']); ?></div>
                                    <div class="small">Compagnie: <?php echo htmlentities($a['nom_compagnie']); ?> — Capacité: <?php echo (int)$a['capacite_totale']; ?></div>
                                </div>
                            </div>
                            <div class="actions">
                                <a class="small action-link" href="?id=<?php echo (int)$a['id_appareil']; ?>"><i class="fas fa-edit"></i> Modifier</a>
                                <button type="button" class="delete-btn delete-trigger" data-delete-id="<?php echo (int)$a['id_appareil']; ?>" title="Supprimer"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </aside>

</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; flex-direction: column; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: var(--bg-secondary); border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); width: 90%; max-width: 400px; animation: slideDown 0.3s ease;">
        <div class="modal-header" style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; color: var(--text-color);">Confirmer la suppression</h3>
            <button type="button" id="modalClose" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-color);">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px; color: var(--text-color);">
            <p>Êtes-vous sûr de vouloir supprimer <strong id="appareilName"></strong> ?</p>
            <p style="color: #ff6b6b; font-size: 14px;">⚠️ Cette action est irréversible.</p>
        </div>
        <div class="modal-footer" style="padding: 15px; border-top: 1px solid var(--border-color); display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" id="modalCancel" class="btn btn-secondary" style="background: var(--bg-primary); color: var(--text-color); border: 1px solid var(--border-color);">Annuler</button>
            <button type="button" id="modalConfirm" class="btn btn-danger" style="background: #ff6b6b; color: white;">Supprimer</button>
        </div>
    </div>
</div>

<style>
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal { display: none !important; }
.modal.active { display: flex !important; }
</style>

<script>
    // theme toggle (re-using same logic as other pages)
    const themeToggle = document.getElementById('theme-toggle');
    function updateTheme(theme){ document.documentElement.setAttribute('data-theme', theme); themeToggle.innerHTML = `<i class="fas ${theme === 'dark' ? 'fa-moon' : 'fa-sun'}"></i>`; localStorage.setItem('theme', theme); }
    const savedTheme = localStorage.getItem('theme') || 'dark'; updateTheme(savedTheme);
    themeToggle.addEventListener('click', ()=>{ const cur = document.documentElement.getAttribute('data-theme'); updateTheme(cur === 'dark' ? 'light' : 'dark'); });

    // Modal functionality
    const deleteModal = document.getElementById('deleteModal');
    const modalClose = document.getElementById('modalClose');
    const modalCancel = document.getElementById('modalCancel');
    const modalConfirm = document.getElementById('modalConfirm');
    let deleteAppareilId = null;

    // Close modal
    function closeModal() {
        deleteModal.style.display = 'none';
        deleteAppareilId = null;
    }

    modalClose.addEventListener('click', closeModal);
    modalCancel.addEventListener('click', closeModal);

    // Click outside modal to close
    window.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            closeModal();
        }
    });

    // Delete trigger buttons
    document.querySelectorAll('.delete-trigger').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-delete-id');
            const row = this.closest('li');
            const appareilInfo = row.querySelector('span') ? row.querySelector('span').textContent : 'cet appareil';
            
            deleteAppareilId = id;
            document.getElementById('appareilName').textContent = appareilInfo;
            deleteModal.style.display = 'flex';
        });
    });

    // Confirm delete
    modalConfirm.addEventListener('click', function() {
        if (deleteAppareilId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="delete_id" value="' + deleteAppareilId + '">';
            document.body.appendChild(form);
            form.submit();
        }
    });

    // Form validation
    document.getElementById('appareilForm').addEventListener('submit', function(e) {
        const errorDiv = document.querySelector('.error');
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';

        const idTransport = document.querySelector('select[name="id_transport"]').value;
        const numeroId = document.querySelector('input[name="numero_identification"]').value.trim();
        const capacite = document.querySelector('input[name="capacite_totale"]').value;

        let hasError = false;

        if (!idTransport || idTransport === '0') {
            errorDiv.textContent = 'Veuillez sélectionner un type de transport.';
            hasError = true;
        } else if (!numeroId) {
            errorDiv.textContent = 'Le numéro d\'identification ne peut pas être vide.';
            hasError = true;
        } else if (!capacite || capacite <= 0) {
            errorDiv.textContent = 'La capacité doit être un nombre positif.';
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
            errorDiv.style.display = 'block';
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }

        // Disable submit button during request
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'En cours...';
    });
</script>
</body>
</html>
