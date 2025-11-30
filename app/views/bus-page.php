<?php
// DOIT être la toute première chose exécutée sur la page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/PROJET-AGENCE-DE-VOYAGE/public/css/style.css">
    <title>Flex Travel - Réservation Bus</title>
    
    <style>
body {
    background-color: var(--couleur-primaire);
    min-height: 100vh;
    padding-top: 10vh; 
    overflow-x: hidden;

    position: relative;
}


    </style>

    <style>
        /* Page loader CSS (HUD-style neon segmented ring + vertical-line bar) */
        :root { --neon: #00f5ff; --neon-2: #00d0c7; --bg-dark: #000612; --seg-off: rgba(255,255,255,0.06); }
        #page-loader { position: fixed; inset: 0; background: radial-gradient(ellipse at center, rgba(0,0,0,0.96) 0%, rgba(0,0,0,0.99) 70%); display:flex; align-items:center; justify-content:center; z-index:99999; transition: opacity 0.6s ease, visibility 0.6s ease; }
        #page-loader.loaded { opacity:0; visibility:hidden; pointer-events:none; }
        .loader-inner { display:flex; gap:28px; align-items:center; color:var(--neon); }
        .loader-left{ width:160px; height:160px; position:relative; display:flex; align-items:center; justify-content:center }
        #ring-svg{ width:100%; height:100%; display:block }
        #ring-svg .seg{ fill: var(--seg-off); transition: fill 160ms ease, opacity 160ms ease; }
        #ring-svg .seg.on{ fill: var(--neon); filter: drop-shadow(0 0 10px var(--neon)); }
        .loader-perc{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:22px; color:#eaffff; text-shadow:0 0 10px rgba(0,245,255,0.25); font-family: 'Poppins', monospace; }
        .loader-right{ display:flex; flex-direction:column; gap:10px; align-items:flex-start }
        .bar-meta{ font-family: 'Poppins', monospace; font-weight:700; color:var(--neon); letter-spacing:2px; font-size:12px; text-transform:uppercase; text-shadow:0 0 8px rgba(0,245,255,0.12); }
        .bar-track{ display:flex; align-items:end; gap:6px; padding:8px; width:520px; max-width:66vw; height:56px; border-radius:8px; background: rgba(255,255,255,0.02); border:1px solid rgba(0,245,255,0.06); box-shadow:0 12px 40px rgba(0,245,255,0.03) inset }
        .bar-seg{ flex:0 0 8px; height:40%; background: rgba(255,255,255,0.06); border-radius:3px; transition: all 180ms ease; opacity:0.95 }
        .bar-seg.active{ height:100%; background: linear-gradient(180deg, var(--neon), var(--neon-2)); box-shadow:0 0 18px var(--neon); transform: translateY(0); animation: bar-pulse 1100ms ease-in-out infinite }
        @keyframes bar-pulse{ 0%{ transform: translateY(0) scaleY(1); } 50%{ transform: translateY(-6px) scaleY(1.03); } 100%{ transform: translateY(0) scaleY(1); } }
        .loading-text{ font-family: 'Poppins', monospace; color:var(--neon); font-weight:700; letter-spacing:1.5px; font-size:13px; margin-top:6px; }
        .loading-text{ text-shadow:0 0 6px rgba(0,245,255,0.12); animation: text-glow 1.8s linear infinite }
        @keyframes text-glow{ 0%{ text-shadow:0 0 6px rgba(0,245,255,0.06) } 50%{ text-shadow:0 0 18px rgba(0,245,255,0.6) } 100%{ text-shadow:0 0 6px rgba(0,245,255,0.06) } }
        @media (max-width:900px){ .loader-inner{ gap:16px } .bar-track{ width:320px } .loader-left{ width:120px;height:120px } }
    </style>
</head>
<body>

    <!-- Page Loader (HUD segmented ring + bar) -->
    <div id="page-loader" role="status" aria-live="polite">
        <div class="loader-inner">
            <div class="loader-left">
                <svg id="ring-svg" viewBox="0 0 100 100" aria-hidden="true"></svg>
                <div class="loader-perc" aria-hidden="false">0%</div>
            </div>
            <div class="loader-right">
                <div class="bar-meta">SYSTEM</div>
                <div id="bar-track" class="bar-track" aria-hidden="true"></div>
                <div class="loading-text">LOADING...</div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/layout/header.php'; ?>

<?php
// Récupérer dynamiquement l'id_transport pour le Bus
$type_transport = 'Bus';
$id_transport_value = 0;
try {
    require_once __DIR__ . '/../models/Database.php';
    $pdoTmp = getPDOConnection();
    $st = $pdoTmp->prepare('SELECT id_transport FROM moyen_transport WHERE LOWER(type) = ? LIMIT 1');
    $st->execute([strtolower($type_transport)]);
    $r = $st->fetch();
    if ($r) $id_transport_value = intval($r['id_transport']);
} catch (Exception $e) {
    error_log('Impossible de récupérer id_transport pour bus: ' . $e->getMessage());
}
?>

<div class="bus-left-section">
    <div class="bus-gauche">
        <div class="bus-content">
            <h1 data-aos="fade-right" data-aos-duration="2000" data-aos-delay="500">Welcome</h1>
            <h2 data-aos="fade-left" data-aos-duration="2000" data-aos-delay="1000">To <span>FLEX TRAVEL</span></h2>
            <P data-aos="fade-up" data-aos-duration="2000" data-aos-delay="500">Get Your Ticket Online,<br> Easily And Safely</P>
        </div>
    </div>

    <section class="bus-trio">
        <div class="bus-form-p" data-aos="fade-up" data-aos-duration="2000" data-aos-delay="100">
            <form action="" class="bus-form">
                <!-- Hidden transport identifiers (populated from DB) -->
                <input type="hidden" name="id_transport" id="bus-id-transport" value="<?php echo $id_transport_value; ?>">
                <input type="hidden" name="type_transport" id="bus-type-transport" value="Bus">

                <div class="bus-form-group" data-aos="fade-right" data-aos-duration="2000" data-aos-delay="200">
                    <label for="depart">Depart</label>
                    <input list="depart" name="departD" placeholder="Lieu de Départ">
                    <datalist id="depart">
                        <option value="YAOUNDE"></option>
                        <option value="EDEA"></option>
                        <option value="EBOLOWA"></option>
                        <option value="DOUALA"></option>
                    </datalist>
                </div>

                <div class="bus-form-group" data-aos="fade-left" data-aos-duration="2000" data-aos-delay="300">
                    <label for="arrive">Arrivée</label>
                    <input list="arrive" name="arriveA" placeholder="Lieu D'arrivée" >
                    <datalist id="arrive">
                        <option value="YAOUNDE"></option>
                        <option value="EDEA"></option>
                        <option value="EBOLOWA"></option>
                        <option value="DOUALA"></option>
                    </datalist>
                </div>

                <div class="bus-form-group" data-aos="fade-right" data-aos-duration="2000" data-aos-delay="400">
                    <label for="dateD">Date Départ</label>
                    <input type="date" id="dateD" name="dateD" min="2025-01-01">
                </div>

                <div class="bus-form-group" data-aos="fade-left" data-aos-duration="2000" data-aos-delay="500">
                    <label for="heureD">Heure Départ</label>
                    <input type="time" id="heureD" name="heureD" placeholder="Heure Départ">
                </div>

                <div class="bus-form-group" data-aos="fade-right" data-aos-duration="2000" data-aos-delay="600">
                    <label for="nbpassage">Nombre de Passagers</label>
                    <input list="nbpassage" name="nbpassage" placeholder="1">
                    <datalist id="nbpassage">
                        <option value="1"></option>
                        <option value="2"></option>
                        <option value="3"></option>
                    </datalist>
                </div>

                <div class="bus-form-group" data-aos="fade-left" data-aos-duration="2000" data-aos-delay="700">
                    <label for="classe">Classe</label>
                    <input list="classe" name="classe" placeholder="Votre Classe de Siège">
                    <datalist id="classe">
                        <option value="VIP"></option>
                        <option value="Standard"></option>
                    </datalist>
                </div>

                <div class="bus-form-button">
                    <button type="submit" class="bus-buy-btn" data-aos="zoom-in" data-aos-duration="2000" data-aos-delay="500">BUY TICKET</button>
                </div>

            </form>
        </div>
    </section>
</div>

<div class="bus-city"></div>
<div class="bus-ensemble">
    <div class="bus-car"><img src="/projet-agence-de-voyage/public/img/b1.png" alt="Bus en mouvement"></div>
    <div class="bus-roue">
        <img src="/projet-agence-de-voyage/public/img/roue1.png" alt="Roue arrière" class="bus-roue-arriere">
        <img src="/projet-agence-de-voyage/public/img/roue1.png" alt="Roue avant" class="bus-roue-avant">
    </div>
</div>
<div class="bus-display">
    <div class="bus-roadBit"></div>
    <div class="bus-roadnich"></div>
    <div class="bus-road"></div>
</div>
<script>
    (function(){
        const loader = document.getElementById('page-loader');
        const ringSvg = document.getElementById('ring-svg');
        const barTrack = document.getElementById('bar-track');
        const percEl = document.querySelector('.loader-perc');
        if(!loader || !ringSvg || !barTrack || !percEl) return;

        const NUM_SEGS = 36;
        const NUM_BARS = 40;
        const minDisplay = 1500; // ms minimal visible time

        const segs = [];
        const cx = 50, cy = 50, r = 36, w = 4, h = 10;
        for(let i=0;i<NUM_SEGS;i++){
            const g = document.createElementNS('http://www.w3.org/2000/svg','rect');
            const a = (i/NUM_SEGS)*Math.PI*2 - Math.PI/2;
            const x = cx + Math.cos(a)*(r);
            const y = cy + Math.sin(a)*(r);
            g.setAttribute('x', x - w/2);
            g.setAttribute('y', y - h/2);
            g.setAttribute('width', w);
            g.setAttribute('height', h);
            g.setAttribute('rx', 2);
            g.setAttribute('ry', 2);
            g.setAttribute('transform', `rotate(${a*180/Math.PI} ${x} ${y})`);
            g.classList.add('seg');
            ringSvg.appendChild(g);
            segs.push(g);
        }

        const bars = [];
        for(let i=0;i<NUM_BARS;i++){
            const d = document.createElement('div');
            d.className = 'bar-seg';
            barTrack.appendChild(d);
            bars.push(d);
        }

        function render(p){
            const segOn = Math.round((p/100)*(NUM_SEGS-1));
            segs.forEach((s, idx)=> s.classList.toggle('on', idx<=segOn));
            const barOn = Math.round((p/100)*(NUM_BARS-1));
            bars.forEach((b, idx)=> b.classList.toggle('active', idx<=barOn));
            percEl.textContent = Math.min(100, Math.round(p)) + '%';
        }

        // fake progress until load
        let pct = 0; render(0);
        let lastTick = Date.now();
        const incInterval = setInterval(()=>{
            const now = Date.now();
            const dt = now - lastTick;
            lastTick = now;
            if(pct < 90) pct += Math.random()*0.8 + 0.4; // slow climb
            render(pct);
            if(pct >= 90) { clearInterval(incInterval); }
        }, 80);

        let finalized = false;
        function finalizeAndHide(){
            if(finalized) return; finalized = true;
            const t0 = Date.now();
            const finish = ()=>{
                render(100);
                const elapsed = Date.now() - t0;
                const wait = Math.max(0, minDisplay - elapsed);
                setTimeout(()=>{ loader.classList.add('loaded'); setTimeout(()=> loader.remove(), 650); }, wait);
            };
            // ramp to 100
            let ramp = 0;
            const rampI = setInterval(()=>{ ramp += 6 + Math.random()*6; render(Math.min(100, pct + ramp)); if(pct + ramp >= 100){ clearInterval(rampI); finish(); } }, 36);
        }

        window.addEventListener('load', ()=> finalizeAndHide());
        // safety fallback
        setTimeout(()=> finalizeAndHide(), 6000);
    })();
</script>

<script src="l3.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>  AOS.init(); </script>
</body>
</html>