<?php
// Page avion avec header inclus
// DOIT être la toute première chose exécutée sur la page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../models/Database.php';

$type_transport = 'Avion';
$id_transport_value = 0;
try {
    $pdoTmp = getPDOConnection();
    $st = $pdoTmp->prepare('SELECT id_transport FROM moyen_transport WHERE LOWER(type) = ? LIMIT 1');
    $st->execute([strtolower($type_transport)]);
    $r = $st->fetch();
    if ($r) $id_transport_value = intval($r['id_transport']);
} catch (Exception $e) {
    error_log('Impossible de récupérer id_transport pour avion: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="/PROJET-AGENCE-DE-VOYAGE/public/css/style.css">

    <title>Flex Travel | Réservation de vol</title>

    <style>
        body {
            background-color: var(--couleur-primaire);
            min-height: 100vh;
            padding-top: 10vh;
            /* Espace pour la barre de navigation fixed */
            overflow-x: hidden;
            position: relative;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/layout/header.php'; ?>

    <div class="air-main-container">
        <section class="air-gauche">
            <div class="air-content">
                <!-- <h1 data-aos="fade-right" data-aos-duration="2000" data-aos-delay="500">Assurez vos Voyages<br> En un
                    <span>Clic</span></h1> -->

                <form action="" id="air-bookingform" data-aos="fade-left" data-aos-duration="2000" data-aos-delay="200">
                    <!-- Hidden transport identifiers (populated from DB) -->
                    <input type="hidden" name="id_transport" id="air-id-transport" value="<?php echo $id_transport_value; ?>">
                    <input type="hidden" name="type_transport" id="air-type-transport" value="Avion">
                    <div class="air-triptype">
                        <label for="air-return-trip">
                            <input type="radio" name="triptype" value="return" id="air-return-trip" checked>
                            Aller-Retour
                        </label>
                        <label for="air-oneway-trip">
                            <input type="radio" name="triptype" value="oneway" id="air-oneway-trip"> Aller Simple
                        </label>
                    </div>

                    <div class="air-input-line">
                        <div class="air-form-group">
                            <label for="air-depart">Départ</label>
                            <select name="depart" id="air-depart" required>
                                <option value="" disabled selected>Ville de Départ</option>
                                <option value="USA">USA</option>
                                <option value="CAMEROUN">CAMEROUN</option>
                            </select>
                        </div>
                        <div class="air-form-group">
                            <label for="air-arrive">Arrivée</label>
                            <select name="arrive" id="air-arrive" required>
                                <option value="" disabled selected>Ville d'Arrivée</option>
                                <option value="USA">USA</option>
                                <option value="CAMEROUN">CAMEROUN</option>
                            </select>
                        </div>
                    </div>

                    <div class="air-input-line">
                        <div class="air-form-group">
                            <label for="air-dateD">Date de Départ</label>
                            <input type="date" id="air-dateD" name="dateD" required>
                        </div>
                        <div class="air-form-group air-dateR-wrapper" id="air-return-date-wrapper">
                            <label for="air-dateR">Date de Retour</label>
                            <input type="date" id="air-dateR" name="dateR" required>
                        </div>
                    </div>

                    <div class="air-input-line">
                        <div class="air-form-group">
                            <label for="air-passengers">Passagers</label>
                            <input type="number" id="air-passengers" name="passengers" required min="1"
                                placeholder="Nb. Passagers">
                        </div>
                        <div class="air-form-group">
                            <label for="air-class">Classe de Vol</label>
                            <input type="text" id="air-class" name="class" list="air-classOptions" required
                                placeholder="Économie, Affaires...">
                            <datalist id="air-classOptions">
                                <option value="Économie">
                                <option value="Affaires">
                                <option value="Première Classe">
                            </datalist>
                        </div>
                    </div>

                    <button type="submit" class="air-buy-btn" data-aos="zoom-in" data-aos-duration="1000"
                        data-aos-delay="500">RECHERCHER VOLS</button>
                </form>
            </div>

            <div class="air-slider">
                <div class="air-slider-header">
                    <h2>Destinations Populaires</h2>
                    <div class="air-slider-button">
                        <button id="air-prev" class="air-slider-btn">
                            <i class="fa-solid fa-angle-left"></i>
                        </button>
                        <button id="air-next" class="air-slider-btn">
                            <i class="fa-solid fa-angle-right"></i>
                        </button>
                    </div>
                </div>
                <div class="air-slider-container">
                    <div class="air-slider-image">
                        <div class="air-slide" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="100">
                            <img src="/projet-agence-de-voyage/public/img/place8.jpg" alt="Mumbai">
                            <div class="air-info">
                                <p class="air-sub-location">Mumbai</p>
                                <p class="air-location"> INDE</p>
                                <div class="air-price">
                                    <span class="air-slide-icon"><i class="fa-solid fa-plane-departure"></i></span>
                                    <span>1400 XAF</span>
                                </div>
                            </div>
                        </div>
                    </div>
                     <div class="air-slider-image">
                        <div class="air-slide" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="100">
                            <img src="/projet-agence-de-voyage/public/img/place8.jpg" alt="Mumbai">
                            <div class="air-info">
                                <p class="air-sub-location">Mumbai</p>
                                <p class="air-location"> INDE</p>
                                <div class="air-price">
                                    <span class="air-slide-icon"><i class="fa-solid fa-plane-departure"></i></span>
                                    <span>1400 XAF</span>
                                </div>
                            </div>
                        </div>
                    </div>
                     <div class="air-slider-image">
                        <div class="air-slide" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="100">
                            <img src="/projet-agence-de-voyage/public/img/place8.jpg" alt="Mumbai">
                            <div class="air-info">
                                <p class="air-sub-location">Mumbai</p>
                                <p class="air-location"> INDE</p>
                                <div class="air-price">
                                    <span class="air-slide-icon"><i class="fa-solid fa-plane-departure"></i></span>
                                    <span>1400 XAF</span>
                                </div>
                            </div>
                        </div>
                    </div>
                     <div class="air-slider-image">
                        <div class="air-slide" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="100">
                            <img src="/projet-agence-de-voyage/public/img/place8.jpg" alt="Mumbai">
                            <div class="air-info">
                                <p class="air-sub-location">Mumbai</p>
                                <p class="air-location"> INDE</p>
                                <div class="air-price">
                                    <span class="air-slide-icon"><i class="fa-solid fa-plane-departure"></i></span>
                                    <span>1400 XAF</span>
                                </div>
                            </div>
                        </div>
                    </div>
                     <div class="air-slider-image">
                        <div class="air-slide" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="100">
                            <img src="/projet-agence-de-voyage/public/img/place8.jpg" alt="Mumbai">
                            <div class="air-info">
                                <p class="air-sub-location">Mumbai</p>
                                <p class="air-location"> INDE</p>
                                <div class="air-price">
                                    <span class="air-slide-icon"><i class="fa-solid fa-plane-departure"></i></span>
                                    <span>1400 XAF</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="air-droite" data-aos="fade-up-left" data-aos-duration="2000" data-aos-delay="800">
            <img src="/projet-agence-de-voyage/public/img/avi1.png" alt="Avion en vol" id="air-flying-plane">
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const returnTrip = document.getElementById('air-return-trip');
            const onewayTrip = document.getElementById('air-oneway-trip');
            const returnDateWrapper = document.getElementById('air-return-date-wrapper');
            const dateRInput = document.getElementById('air-dateR');

            function toggleReturnDate() {
                if (onewayTrip.checked) {
                    returnDateWrapper.style.display = 'none';
                    dateRInput.removeAttribute('required');
                } else {
                    returnDateWrapper.style.display = 'block';
                    dateRInput.setAttribute('required', 'required');
                }
            }

            returnTrip.addEventListener('change', toggleReturnDate);
            onewayTrip.addEventListener('change', toggleReturnDate);

            // Initial call
            toggleReturnDate();
        });
    </script>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>  AOS.init();</script>
    <script src="/../../public/js/main.js"></script>
</body>

</html>