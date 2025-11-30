<?php
session_start();
session_regenerate_id(true); // Sécurité supplémentaire

require_once __DIR__ . '/../app/models/Database.php';

$response = ['success' => false, 'message' => '', 'type' => ''];

// Générer un token CSRF si absent
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

// Traiter les requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Vérifier le token CSRF
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Token de sécurité invalide. Veuillez recharger la page.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    try {
        $pdo = getPDOConnection();
        
        if ($action === 'register') {
            // === INSCRIPTION ===
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
            $telephone = trim($_POST['telephone'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validation
            if (empty($nom) || strlen($nom) < 2) {
                $response['message'] = 'Le nom doit contenir au moins 2 caractères.';
            } elseif (empty($prenom) || strlen($prenom) < 2) {
                $response['message'] = 'Le prénom doit contenir au moins 2 caractères.';
            } elseif (!$email) {
                $response['message'] = 'L\'adresse email n\'est pas valide.';
            } elseif (strlen($password) < 8) {
                $response['message'] = 'Le mot de passe doit contenir au moins 8 caractères.';
            } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                $response['message'] = 'Le mot de passe doit contenir au moins une majuscule et un chiffre.';
            } elseif ($password !== $confirm_password) {
                $response['message'] = 'Les mots de passe ne correspondent pas.';
            } else {
                // Vérifier si l'email existe déjà
                $stmt = $pdo->prepare('SELECT id_utilisateur FROM utilisateur WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $response['message'] = 'Cet email est déjà utilisé.';
                } else {
                    // Hacher le mot de passe avec BCRYPT
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    
                    // Insérer l'utilisateur
                    $ins = $pdo->prepare('INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone) VALUES (?, ?, ?, ?, ?)');
                    $ins->execute([$nom, $prenom, $email, $hash, $telephone]);
                    
                    $userId = $pdo->lastInsertId();
                    
                    // Régénérer la session après enregistrement
                    session_regenerate_id(true);
                    
                    // Créer la session
                    $_SESSION['user'] = [
                        'id_utilisateur' => $userId,
                        'nom' => $nom,
                        'prenom' => $prenom,
                        'email' => $email
                    ];
                    
                    $response['success'] = true;
                    $response['message'] = 'Inscription réussie ! Redirection...';
                    $response['redirect'] = '/projet-agence-de-voyage/public/Home.php';
                }
            }
            
        } elseif ($action === 'login') {
            // === CONNEXION ===
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';
            
            if (!$email) {
                $response['message'] = 'L\'adresse email n\'est pas valide.';
            } elseif (empty($password)) {
                $response['message'] = 'Le mot de passe est requis.';
            } else {
                $stmt = $pdo->prepare('SELECT id_utilisateur, nom, prenom, email, mot_de_passe FROM utilisateur WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Debug: log hash details
                if ($user) {
                    error_log("User found - Hash length: " . strlen($user['mot_de_passe']));
                    $verified = password_verify($password, $user['mot_de_passe']);
                    error_log("Password verify result: " . ($verified ? "TRUE" : "FALSE"));
                } else {
                    error_log("User not found for email: " . $email);
                }
                
                if (!$user || !password_verify($password, $user['mot_de_passe'])) {
                    $response['message'] = 'Email ou mot de passe incorrect.';
                } else {
                    // Rehash si nécessaire
                    if (password_needs_rehash($user['mot_de_passe'], PASSWORD_BCRYPT, ['cost' => 12])) {
                        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                        $up = $pdo->prepare('UPDATE utilisateur SET mot_de_passe = ? WHERE id_utilisateur = ?');
                        $up->execute([$newHash, $user['id_utilisateur']]);
                    }
                    
                    // Régénérer la session après connexion réussie
                    session_regenerate_id(true);
                    
                    // Créer la session
                    $_SESSION['user'] = [
                        'id_utilisateur' => $user['id_utilisateur'],
                        'nom' => $user['nom'],
                        'prenom' => $user['prenom'],
                        'email' => $user['email']
                    ];
                    
                    $response['success'] = true;
                    $response['message'] = 'Connexion réussie ! Redirection...';
                    $response['redirect'] = '/projet-agence-de-voyage/public/Home.php';
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Auth error: " . $e->getMessage());
        $response['message'] = 'Erreur serveur. Veuillez réessayer.';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Si l'utilisateur est déjà connecté, rediriger
if (isset($_SESSION['user'])) {
    header('Location: /projet-agence-de-voyage/public/Home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion & Inscription - Flex Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/loader.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --primary: #3b82f6;
            --primary-dark: #1e40af;
            --secondary: #4ecdc4;
            --danger: #ff6b6b;
            --success: #51cf66;
            --dark: #0f172a;
            --darker: #0a0e27;
            --light: #e2e8f0;
            --lighter: #f1f5f9;
        }

        body {
            background: linear-gradient(135deg, var(--dark) 0%, var(--darker) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--light);
            overflow-x: hidden;
        }

        .auth-container {
            width: 100%;
            max-width: 950px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5),
                        0 0 50px rgba(59, 130, 246, 0.1);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Section Gauche - Illustration */
        .auth-left {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 40px;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -100px;
            right: -100px;
            animation: float 6s ease-in-out infinite;
        }

        .auth-left::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            bottom: -50px;
            left: -50px;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(20px); }
        }

        .auth-left-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .auth-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .auth-left h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .auth-left p {
            font-size: 15px;
            line-height: 1.6;
            opacity: 0.95;
        }

        .auth-features {
            margin-top: 40px;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            animation: slideInLeft 0.6s ease-out;
        }

        .feature i {
            font-size: 20px;
            min-width: 20px;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Section Droite - Formulaire */
        .auth-right {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .form-container {
            opacity: 1;
            transform: translateX(0);
            transition: all 0.4s ease;
        }

        .form-container.hidden {
            opacity: 0;
            transform: translateX(50px);
            position: absolute;
            pointer-events: none;
        }

        .form-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 40px;
            background: rgba(59, 130, 246, 0.1);
            padding: 8px;
            border-radius: 12px;
        }

        .toggle-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            color: var(--light);
            font-weight: 600;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s ease;
            position: relative;
        }

        .toggle-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.3);
        }

        .form-group {
            margin-bottom: 20px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--light);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 12px 15px;
            background: rgba(226, 232, 240, 0.05);
            border: 2px solid rgba(59, 130, 246, 0.2);
            color: var(--light);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="tel"]:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(59, 130, 246, 0.1);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            color: var(--primary);
            font-size: 18px;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus ~ .input-icon,
        input[type="email"]:focus ~ .input-icon,
        input[type="password"]:focus ~ .input-icon,
        input[type="tel"]:focus ~ .input-icon {
            transform: scale(1.2) rotate(10deg);
        }

        .input-group input {
            padding-right: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            transform: scale(1.2);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .error {
            display: none;
            background: rgba(255, 107, 107, 0.15);
            border: 2px solid var(--danger);
            color: #ff9999;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            animation: shake 0.4s ease;
        }

        .error.show {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .success {
            display: none;
            background: rgba(81, 207, 102, 0.15);
            border: 2px solid var(--success);
            color: #a3e9b5;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            animation: slideDown 0.4s ease;
        }

        .success.show {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .password-requirements {
            display: none;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 12px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 12px;
            color: var(--light);
        }

        .password-requirements.show {
            display: block;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0;
        }

        .requirement-icon {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            transition: all 0.3s ease;
        }

        .requirement.met .requirement-icon {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .btn.loading {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn.loading::after {
            content: '';
            display: inline-block;
            width: 14px;
            height: 14px;
            margin-left: 10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .form-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            color: var(--light);
        }

        .form-footer a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .form-footer a:hover {
            text-decoration: underline;
            transform: scale(1.05);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .auth-container {
                grid-template-columns: 1fr;
                margin: 20px;
            }

            .auth-left {
                display: none;
            }

            .auth-right {
                padding: 40px 30px;
            }

            .auth-left h1 {
                font-size: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Loader -->
    <div id="loaderContainer" class="loader-container" style="display: none;">
        <div class="loader">
            <div class="loader-dot"></div>
        </div>
        <div class="loader-text">
            Traitement<span class="dot"></span><span class="dot"></span><span class="dot"></span>
        </div>
    </div>

    <div class="auth-container">
        <!-- Section Gauche -->
        <div class="auth-left">
            <div class="auth-left-content">
                <div class="auth-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <h1>Flex Travel</h1>
                <p>Explorez le monde, vivez l'inoubliable</p>

                <div class="auth-features">
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Réservations rapides et faciles</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-lock"></i>
                        <span>Données sécurisées et protégées</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-dollar-sign"></i>
                        <span>Meilleurs prix garantis</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-headset"></i>
                        <span>Support client 24/7</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Droite -->
        <div class="auth-right">
            <!-- Toggle Connexion/Inscription -->
            <div class="form-toggle">
                <button class="toggle-btn active" id="loginToggle">Connexion</button>
                <button class="toggle-btn" id="registerToggle">Inscription</button>
            </div>

            <!-- Formulaire de Connexion -->
            <form id="loginForm" class="form-container">
                <input type="hidden" name="csrf_token" id="login_csrf" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="error" id="loginError">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="loginErrorMsg"></span>
                </div>
                <div class="success" id="loginSuccess">
                    <i class="fas fa-check-circle"></i>
                    <span>Connexion réussie !</span>
                </div>

                <div class="form-group">
                    <label for="login_email">Email</label>
                    <div class="input-group">
                        <input type="email" id="login_email" name="email" placeholder="votre@email.com" required>
                        <i class="input-icon fas fa-envelope"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="login_password">Mot de passe</label>
                    <div class="input-group">
                        <input type="password" id="login_password" name="password" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('login_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn">Se Connecter</button>

                <div class="form-footer">
                    Pas encore de compte ? <a href="#" onclick="switchForm('register'); return false;">S'inscrire</a>
                </div>
            </form>

            <!-- Formulaire d'Inscription -->
            <form id="registerForm" class="form-container hidden">
                <input type="hidden" name="csrf_token" id="register_csrf" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="error" id="registerError">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="registerErrorMsg"></span>
                </div>
                <div class="success" id="registerSuccess">
                    <i class="fas fa-check-circle"></i>
                    <span>Inscription réussie !</span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="register_nom">Nom</label>
                        <div class="input-group">
                            <input type="text" id="register_nom" name="nom" placeholder="Dupont" required>
                            <i class="input-icon fas fa-user"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="register_prenom">Prénom</label>
                        <div class="input-group">
                            <input type="text" id="register_prenom" name="prenom" placeholder="Jean" required>
                            <i class="input-icon fas fa-user"></i>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="register_email">Email</label>
                    <div class="input-group">
                        <input type="email" id="register_email" name="email" placeholder="votre@email.com" required>
                        <i class="input-icon fas fa-envelope"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="register_telephone">Téléphone (optionnel)</label>
                    <div class="input-group">
                        <input type="tel" id="register_telephone" name="telephone" placeholder="+237 6XX XXX XXX">
                        <i class="input-icon fas fa-phone"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="register_password">Mot de passe</label>
                    <div class="input-group">
                        <input type="password" id="register_password" name="password" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('register_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-requirements" id="passwordReqs">
                        <div class="requirement" id="req-length">
                            <span class="requirement-icon"></span>
                            <span>Au moins 8 caractères</span>
                        </div>
                        <div class="requirement" id="req-upper">
                            <span class="requirement-icon"></span>
                            <span>Au moins une majuscule</span>
                        </div>
                        <div class="requirement" id="req-number">
                            <span class="requirement-icon"></span>
                            <span>Au moins un chiffre</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="register_confirm">Confirmer le mot de passe</label>
                    <div class="input-group">
                        <input type="password" id="register_confirm" name="confirm_password" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('register_confirm')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn">S'Inscrire</button>

                <div class="form-footer">
                    Vous avez déjà un compte ? <a href="#" onclick="switchForm('login'); return false;">Se connecter</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const loginToggle = document.getElementById('loginToggle');
        const registerToggle = document.getElementById('registerToggle');
        const loaderContainer = document.getElementById('loaderContainer');
        const passwordField = document.getElementById('register_password');
        const passwordReqs = document.getElementById('passwordReqs');

        // Toggle entre formulaires
        function switchForm(form) {
            if (form === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                loginToggle.classList.add('active');
                registerToggle.classList.remove('active');
                clearErrors();
            } else {
                registerForm.classList.remove('hidden');
                loginForm.classList.add('hidden');
                registerToggle.classList.add('active');
                loginToggle.classList.remove('active');
                clearErrors();
                passwordReqs.classList.remove('show');
            }
        }

        loginToggle.addEventListener('click', () => switchForm('login'));
        registerToggle.addEventListener('click', () => switchForm('register'));

        // Afficher/Masquer mot de passe
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const isPassword = field.type === 'password';
            field.type = isPassword ? 'text' : 'password';
        }

        // Vérifier la force du mot de passe en temps réel
        function checkPasswordStrength(password) {
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password)
            };

            document.getElementById('req-length').classList.toggle('met', requirements.length);
            document.getElementById('req-upper').classList.toggle('met', requirements.uppercase);
            document.getElementById('req-number').classList.toggle('met', requirements.number);

            return Object.values(requirements).every(req => req);
        }

        passwordField.addEventListener('input', (e) => {
            if (e.target.value) {
                passwordReqs.classList.add('show');
                checkPasswordStrength(e.target.value);
            } else {
                passwordReqs.classList.remove('show');
            }
        });

        // Gérer les erreurs/succès
        function showError(formType, message) {
            const errorDiv = document.getElementById(formType + 'Error');
            const errorMsg = document.getElementById(formType + 'ErrorMsg');
            errorMsg.textContent = message;
            errorDiv.classList.add('show');
            document.getElementById(formType + 'Success').classList.remove('show');
        }

        function showSuccess(formType) {
            const successDiv = document.getElementById(formType + 'Success');
            successDiv.classList.add('show');
            document.getElementById(formType + 'Error').classList.remove('show');
        }

        function clearErrors() {
            document.getElementById('loginError').classList.remove('show');
            document.getElementById('registerError').classList.remove('show');
            document.getElementById('loginSuccess').classList.remove('show');
            document.getElementById('registerSuccess').classList.remove('show');
        }

        // Soumettre le formulaire de connexion
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const email = document.getElementById('login_email').value;
            const password = document.getElementById('login_password').value;
            const csrfToken = document.getElementById('login_csrf').value;

            if (!email || !password) {
                showError('login', 'Tous les champs sont requis.');
                return;
            }

            const btn = loginForm.querySelector('.btn');
            btn.classList.add('loading');
            loaderContainer.style.display = 'flex';

            try {
                const formData = new FormData();
                formData.append('action', 'login');
                formData.append('email', email);
                formData.append('password', password);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess('login');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    showError('login', data.message);
                    btn.classList.remove('loading');
                    loaderContainer.style.display = 'none';
                }
            } catch (error) {
                showError('login', 'Erreur de connexion au serveur.');
                btn.classList.remove('loading');
                loaderContainer.style.display = 'none';
            }
        });

        // Soumettre le formulaire d'inscription
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const nom = document.getElementById('register_nom').value;
            const prenom = document.getElementById('register_prenom').value;
            const email = document.getElementById('register_email').value;
            const telephone = document.getElementById('register_telephone').value;
            const password = document.getElementById('register_password').value;
            const confirmPassword = document.getElementById('register_confirm').value;
            const csrfToken = document.getElementById('register_csrf').value;

            if (!nom || !prenom || !email || !password || !confirmPassword) {
                showError('register', 'Tous les champs obligatoires doivent être remplis.');
                return;
            }

            if (password !== confirmPassword) {
                showError('register', 'Les mots de passe ne correspondent pas.');
                return;
            }

            // Vérifier la force du mot de passe
            if (!checkPasswordStrength(password)) {
                showError('register', 'Le mot de passe ne respecte pas les critères de sécurité.');
                return;
            }

            const btn = registerForm.querySelector('.btn');
            btn.classList.add('loading');
            loaderContainer.style.display = 'flex';

            try {
                const formData = new FormData();
                formData.append('action', 'register');
                formData.append('nom', nom);
                formData.append('prenom', prenom);
                formData.append('email', email);
                formData.append('telephone', telephone);
                formData.append('password', password);
                formData.append('confirm_password', confirmPassword);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess('register');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    showError('register', data.message);
                    btn.classList.remove('loading');
                    loaderContainer.style.display = 'none';
                }
            } catch (error) {
                showError('register', 'Erreur de connexion au serveur.');
                btn.classList.remove('loading');
                loaderContainer.style.display = 'none';
            }
        });
    </script>
</body>
</html>
