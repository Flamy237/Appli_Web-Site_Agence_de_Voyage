<?php
// Reusable off-canvas sidebar include
// Place this file in public/inc/sidebar.php and include from pages in public/
?>

<!-- Sidebar include: HTML + minimal CSS + JS -->
<!-- Note: Bootstrap Icons are loaded by pages that include this file -->
<style>
    /* Off-canvas sidebar styles (scoped for include) */
    .offcanvas-sidebar {
        position: fixed;
        left: -320px;
        top: 0;
        height: 100vh;
        width: 280px;
        background: linear-gradient(135deg, #7c3aed 0%, #0b1220 100%);
        color: #fff;
        z-index: 115;
        box-shadow: 8px 0 24px rgba(2, 6, 23, 0.2);
        transition: left .28s ease;
        display: flex;
        flex-direction: column;
        padding: 28px 0;
        box-sizing: border-box
    }

    .offcanvas-sidebar.open {
        left: 0
    }

    .offcanvas-sidebar .offcanvas-inner {
        padding: 0 20px
    }

    .offcanvas-sidebar .avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 18px
    }

    .offcanvas-sidebar h2 {
        text-align: center;
        font-size: 20px;
        margin: 0 0 4px
    }

    .offcanvas-sidebar .email {
        text-align: center;
        font-size: 13px;
        margin-bottom: 20px;
        color: rgba(255, 255, 255, .85)
    }

    .offcanvas-sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0
    }

    .offcanvas-sidebar li {
        margin: 10px 0
    }

    .offcanvas-sidebar a {
        color: #fff;
        text-decoration: none;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-left: 4px solid transparent
    }

    .offcanvas-sidebar a.active {
        background: rgba(255, 255, 255, .08);
        border-left-color: #fff
    }

    .offcanvas-sidebar a:not(.active):hover {
        background: rgba(255, 255, 255, .05)
    }

    /* Hamburger button */
    #hamburgerBtn {
        position: fixed;
        left: 18px;
        top: 18px;
        z-index: 120;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 46px;
        height: 46px;
        border-radius: 8px;
        border: none;
        background: linear-gradient(90deg, #7c3aed, #06b6d4);
        color: #fff;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .25);
        cursor: pointer;
        transition: background .18s ease, transform .12s ease;
        font-size: 18px
    }

    #hamburgerBtn:active {
        transform: scale(.98)
    }

    #hamburgerBtn.open {
        background: linear-gradient(90deg, #ef4444, #dc2626);
        color: #fff
    }

    #hamburgerBtn i {
        font-size: 18px
    }

    /* overlay */
    #offcanvasOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .35);
        z-index: 110
    }
</style>

<button id="hamburgerBtn" aria-label="Ouvrir le menu" title="Menu"><i id="hamburgerIcon" class="fas fa-bars"
        aria-hidden="true"></i></button>

<div id="offcanvasSidebar" class="offcanvas-sidebar" aria-hidden="true">
    <div class="offcanvas-inner">
        <div class="avatar"><i class="bi bi-person-circle" style="font-size:48px;color:#7c3aed"></i></div>
        <h2>Admin</h2>
        <div class="email">admin@agence.com</div>
        <ul>
            <li><a href="./admin-dashboard.php" class="active"><i class="bi bi-house-door"></i> Home</a></li>
            <li><a href="./add-trajet.php"><i class="bi bi-plus-circle"></i> Ajouter trajet</a></li>
            <li><a href="./add-transport.php"><i class="bi bi-truck"></i> Moyen transport</a></li>
            <li><a href="./add-appareil.php"><i class="bi bi-gear"></i> Appareil</a></li>
            <li><a href="./all-tickets.php"><i class="bi bi-ticket-detailed"></i> Tous les billets</a></li>
            <li><a href="./add-classe.php"><i class="bi bi-award"></i> Classe voyage</a></li>
            <li><a href="#"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
        </ul>
    </div>
</div>

<div id="offcanvasOverlay"></div>

<script>
    (function () {
        const btn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('offcanvasSidebar');
        const overlay = document.getElementById('offcanvasOverlay');
        function openSidebar() {
            sidebar.classList.add('open');
            sidebar.setAttribute('aria-hidden', 'false');
            overlay.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebar.setAttribute('aria-hidden', 'true');
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        }
        function setBtnOpen(state) {
            if (state) {
                btn.classList.add('open');
                const ic = document.getElementById('hamburgerIcon'); if (ic) { ic.classList.remove('fa-bars'); ic.classList.add('fa-times'); }
            } else {
                btn.classList.remove('open');
                const ic = document.getElementById('hamburgerIcon'); if (ic) { ic.classList.remove('fa-times'); ic.classList.add('fa-bars'); }
            }
        }
        btn.addEventListener('click', function () {
            if (sidebar.classList.contains('open')) { closeSidebar(); setBtnOpen(false); } else { openSidebar(); setBtnOpen(true); }
        });
        overlay.addEventListener('click', function () { closeSidebar(); setBtnOpen(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeSidebar(); setBtnOpen(false); } });
    })();
</script>