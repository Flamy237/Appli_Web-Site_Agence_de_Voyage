<!-- Header réutilisable (header.php) -->
<?php
// Start session if not started - header needs session to display user info
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Base path from project root to public assets/pages. Adjust if you deploy to a different path.
$base_path = '/projet-agence-de-voyage/public/';

// Determine login status from session
$is_logged_in = false;
$user_prenom = '';
$user_nom_initial = '';
if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
  $is_logged_in = true;
  $user_prenom = !empty($_SESSION['user']['prenom']) ? $_SESSION['user']['prenom'] : (
    !empty($_SESSION['user']['email']) ? explode('@', $_SESSION['user']['email'])[0] : 'Utilisateur'
  );
  $user_nom_initial = strtoupper(substr($user_prenom, 0, 1));
}
?>
<?php
// Derive a display name that is user-friendly (prefer prenom, else use email local-part prettified)
$display_name = '';
if ($is_logged_in) {
  // Prefer last name (nom) for the greeting as requested; fall back to prenom then email
  if (!empty($_SESSION['user']['nom']) && !empty($_SESSION['user']['prenom'])) {
    $display_name = trim($_SESSION['user']['nom'] . ' ' . $_SESSION['user']['prenom']);
  } elseif (!empty($_SESSION['user']['nom'])) {
    $display_name = $_SESSION['user']['nom'];
  } elseif (!empty($_SESSION['user']['prenom'])) {
    $display_name = $_SESSION['user']['prenom'];
  } elseif (!empty($_SESSION['user']['email'])) {
    // prettify email local-part
    $local = explode('@', $_SESSION['user']['email'])[0];
    $local = preg_replace('/[._]/', ' ', $local);
    $display_name = ucwords($local);
  } else {
    $display_name = 'Utilisateur';
  }
  // Trim and protect length
  $display_name = trim($display_name);
  if (strlen($display_name) > 24) {
    $display_name = substr($display_name, 0, 21) . '...';
  }
  // derive initial from display name (safer than earlier variable)
  $user_nom_initial = strtoupper(substr($display_name, 0, 1));
}
?>

<!-- Small header styles scoped to the include to improve layout -->
<style>
  /* Layout tweaks for header: align logo left, links center, user actions right */
  .nav-container { display:flex; align-items:center; justify-content:space-between; gap:12px; }
  .nav-content-right { display:flex; align-items:center; gap:16px; }
  .nav-content-right .links { display:flex; gap:20px; align-items:center; }
  .nav-content-right .links a { text-decoration:none; color:inherit; padding:6px 8px; }
  .user-status { display:flex; align-items:center; gap:12px; }
  .user-profile-link { display:flex; align-items:center; gap:10px; text-decoration:none; color:inherit; }
  .user-avatar { width:36px; height:36px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; background: linear-gradient(135deg,#06b6d4,#3b82f6); color:#fff; font-weight:700; }
  .user-greeting { color: #374151; font-weight:600; font-size:0.95rem; }
  .logout-btn { color:#e0554F; text-decoration:none; display:flex; align-items:center; gap:8px; font-weight:600; }
  .logout-btn i { color: #e0554F; }
  .auth-btn { text-decoration:none; padding:8px 12px; border-radius:8px; border:1px solid rgba(0,0,0,0.06); background:transparent; color:inherit; }
  .auth-btn.login-btn { background: rgba(6,182,212,0.08); }
  @media (max-width:800px){ .nav-content-right{ position:absolute; right:0; top:60px; background:rgba(0,0,0,0.03); flex-direction:column; padding:12px; display:none; } .nav-content-right.active{ display:flex; } }
</style>
<head>
  <!-- Main site stylesheet (ensure header styles are present) -->
  <link rel="stylesheet" href="<?php echo $base_path; ?>css/style.css">
  <!-- Chat widget stylesheet -->
  <link rel="stylesheet" href="<?php echo $base_path; ?>chat/style.css">
</head>
<nav class="navbar">
    <div class="nav-container">
        <!-- Logo et Titre (à gauche) -->
        <a href="#hero" class="logo" data-aos="zoom-in" data-aos-duration="2000">
            <h1>Flex Travel</h1>
        </a>
        
        <!-- Nouveau conteneur pour les liens de navigation et actions utilisateur -->
        <div class="nav-content-right" id="nav-content-right">
            <!-- Liens de navigation principaux -->
            <div class="links" id="nav-links">
                <a href="<?php echo $base_path; ?>Home.php#hero" data-aos="fade-down" data-aos-duration="1200" data-aos-delay="100">Accueil</a>
                <a href="#" data-aos="fade-down" data-aos-duration="1200" data-aos-delay="200">Acheter un billet</a>
                <a href="<?php echo $base_path; ?>Home.php#destinations" data-aos="fade-down" data-aos-duration="1200" data-aos-delay="300">Destinations</a>
                <a href="<?php echo $base_path; ?>Home.php#about" data-aos="fade-down" data-aos-duration="1200" data-aos-delay="400">À Propos</a>
                <a href="<?php echo $base_path; ?>Home.php#faq" data-aos="fade-down" data-aos-duration="1200" data-aos-delay="500">FAQ</a>
                <a href="contact.html" data-aos="fade-down" data-aos-duration="1200" data-aos-delay="600">Contacts</a>
            </div>
            
            <!-- Statut et actions d'authentification -->
      <div class="user-status" id="user-status-links">
        <?php if ($is_logged_in): ?>
          <!-- Connected view: avatar (initial) + first name + logout -->
            <a href="<?php echo $base_path; ?>profile.php" class="user-profile-link" title="Accéder à mon profil">
            <div class="user-avatar"><?php echo htmlspecialchars($user_nom_initial); ?></div>
            <span class="user-greeting"><?php echo htmlspecialchars($display_name); ?></span>
          </a>
          <a href="<?php echo $base_path; ?>logout.php" class="logout-btn" title="Déconnexion">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
          </a>
        <?php else: ?>
          <!-- Logged-out view: login/register buttons -->
          <div class="auth-actions">
            <a href="<?php echo $base_path; ?>login.php" class="auth-btn login-btn">
              <i class="fa-solid fa-right-to-bracket"></i> Connexion
            </a>
            <a href="<?php echo $base_path; ?>register.php" class="auth-btn register-btn">
              <i class="fa-solid fa-user-plus"></i> Inscription
            </a>
          </div>
        <?php endif; ?>
      </div>
        </div>

        <!-- Icône de Menu Hamburger (reste en dehors de nav-content-right) -->
        <div class="menu-icon" id="menu-Icon">
            <i class="fa-solid fa-bars"></i>
        </div>
    </div>
</nav>

<!-- Chat widget root (intégré globalement) -->
<div id="chatbot-root" aria-live="polite">
  <button id="chat-toggle" aria-expanded="false" title="Open chat" aria-label="Open chat">
    <!-- Inline SVG chat icon (speech bubble with three dots) -->
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
      <rect x="1.5" y="3" width="21" height="14" rx="3.5" fill="currentColor" opacity="0.95" />
      <path d="M7 17l-2 3v-3H7z" fill="currentColor" opacity="0.95" />
      <circle cx="8.5" cy="10" r="0.9" fill="#fff" />
      <circle cx="12" cy="10" r="0.9" fill="#fff" />
      <circle cx="15.5" cy="10" r="0.9" fill="#fff" />
    </svg>
  </button>

  <aside id="chat-window" role="dialog" aria-hidden="true">
    <header id="chat-header">
      <strong id="chat-title">Travel Assistant</strong>
      <div id="lang-controls">
        <button class="lang-btn" data-lang="fr">FR</button>
        <button class="lang-btn" data-lang="en">EN</button>
      </div>
      <button id="chat-close" aria-label="Close chat">✕</button>
    </header>

    <main id="chat-body">
      <div id="messages"></div>
    </main>

    <footer id="chat-footer">
      <input type="text" id="chat-input" placeholder="Ask in English or French" />
      <button id="send-btn">Send</button>
    </footer>
  </aside>
</div>

<!-- Chat widget script -->
<script src="<?php echo $base_path; ?>chat/script.js"></script>

<script>
    // JS pour le menu mobile
    (function (){
      const menuIcon = document.getElementById('menu-Icon');
      // Nous ciblons maintenant le nouveau conteneur 'nav-content-right'
      const navContentRight = document.getElementById('nav-content-right');
      
      if(menuIcon && navContentRight){
        menuIcon.addEventListener('click', () => {
          // Toggle la classe 'active' sur le conteneur de droite
          navContentRight.classList.toggle('active');
          
          // Mise à jour de l'icône
          const icon = menuIcon.querySelector('i');
          icon.classList.toggle('fa-bars');
          icon.classList.toggle('fa-xmark');
        });
      }
      
      // Fermer le menu si un lien est cliqué
      const navLinks = document.getElementById('nav-links');
      const navLinksAnchors = navLinks ? navLinks.querySelectorAll('a') : [];
      navLinksAnchors.forEach(link => {
        link.addEventListener('click', () => {
          if (navContentRight.classList.contains('active')) {
            navContentRight.classList.remove('active');
            const icon = menuIcon.querySelector('i');
            icon.classList.remove('fa-xmark');
            icon.classList.add('fa-bars');
          }
        });
      });
    })();
</script>

<script>
  // Ensure main stylesheet is loaded so the navbar gets the expected styles
  (function(){
    try{
      // CHEMIN CORRIGÉ DANS LE SCRIPT JS
      var href = '<?php echo $base_path; ?>css/style.css'; 
      var found = Array.from(document.getElementsByTagName('link')).some(function(l){
        return l.href && l.href.indexOf('style.css') !== -1;
      });
      if(!found){
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        document.head.appendChild(link);
      }
    }catch(e){
      console.warn('Could not ensure stylesheet for navbar:', e);
    }
  })();
</script>
