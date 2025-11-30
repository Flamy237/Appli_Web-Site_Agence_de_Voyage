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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="./css/Homes.css">
    <link rel="stylesheet" href="./css/loader.css">
    <title>Flex Travel | Réservez vos billets facilement</title>

    <style>
        /* Styles spécifiques pour la section hero */
        .hero-banner {
            position: relative;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        .hero-background-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0,
                        0.8));
            z-index: -1;
        }

        .hero-content {
            z-index: 1;
            max-width: 900px;
            padding: 0 20px;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 10px;
            font-weight: 600;
            color: #ffcc00;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero-description {
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        @media screen and (max-width: 750px) {
            .hero-banner {
                /* height: 100vh !important; */
                margin-top: 1vh !important;
            }

            .hero-subtitle {
                font-size: 1rem !important;
                margin-bottom: 1rem !important;
            }

            .hero-title {
                font-size: 1.5rem !important;
                /* margin-bottom: 15px; */
                margin-bottom: 1.5rem;
                padding-right: 28% !important;
                padding-left: 28% !important;
            }

            .hero-description {
                font-size: 0.95rem !important;
                margin-bottom: 20px !important;
                padding-left: 30% !important;
                padding-right: 30% !important;
            }

            .hero-button {
                padding: 10px 30px !important;
                font-size: 1rem !important;
            }

        }

        .hero-button {
            background-color: #e50914;
            color: white;
            padding: 15px 40px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: background-color 0.3s ease;
        }

        .hero-button:hover {
            background-color: #ff0000;
        }

        .hero-logo-hbo {
            margin-top: 50px;
            max-width: 150px;
            opacity: 0.8;
        }

        .swiper {
            position: absolute;
            width: 300%;
            height: 33.33%;
            left: 0;
            z-index: -3;
        }

        .banner-slide-1 {
            top: 0;
        }

        .banner-slide-2 {
            top: 33.33%;
        }

        .banner-slide-3 {
            top: 66.66%;
        }

        .swiper-wrapper {
            display: flex;
            height: 100%;
        }

        .swiper-slide {
            width: auto;
            height: 100%;
            flex-shrink: 0;
        }

        .swiper-slide img {
            height: 100%;
            width: auto;
            display: block;
        }

        /*animations de défilement infini */
        @keyframes slideRightToLeft {
            0% {
                transform: translateX(0%);
            }

            100% {
                transform: translateX(-100vw);
            }
        }

        @keyframes slideLeftToRight {
            0% {
                transform: translateX(-100vw);
            }

            100% {
                transform: translateX(0%);
            }
        }

        .banner-slide-1 .swiper-wrapper,
        .banner-slide-3 .swiper-wrapper {
            animation: slideRightToLeft 40s linear infinite;
        }

        .banner-slide-2 .swiper-wrapper {
            animation: slideLeftToRight 40s linear infinite;
        }

        .patenaire-logo {
            overflow: hidden;
            width: 100%;
            background: #000;
            padding: 10px 0;
        }

        .slider-track {
            display: flex;
            width: max-content;
            animation: scroll 30s linear infinite;
        }

        .slider-track img {
            height: 60px;
            margin: 0 20px;
            object-fit: contain;
            filter: brightness(1.1);
        }

        @keyframes scroll {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .gradient-text2 {
            background: linear-gradient(to right, #dddedf, #adadad, #5b626d);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .gradient-text {
            background: linear-gradient(135deg, var(--accent-blue),
                    var(--primary-blue));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-premium {
            background: linear-gradient(135deg, var(--primary-blue),
                    var(--secondary-blue));
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue),
                    var(--primary-blue));
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-blue),
                    var(--accent-blue));
        }

        .hover-scale {
            transition: transform 0.3s ease-in-out;
        }

        .hover-scale:hover {
            transform: scale(1.03);
        }

        .glow {
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.6), 0 0 30px rgba(0, 123, 255, 0.4);
        }

        .channel-preview {
            transition: all 0.3s ease-in-out;
        }

        .channel-preview:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        .hero-banner {
            position: relative;
            height: 400px;
            overflow: hidden;
            /* Empêche le débordement */
        }

        @media (min-width: 768px) {
            .hero-banner {
                height: 600px;
            }
        }
    </style>
</head>

<body>
<!-- Loading Animation -->
<div id="loaderContainer" class="loader-container">
  <div class="loader">
    <div class="loader-dot"></div>
  </div>
  <div class="loader-text">
    Chargement<span class="dot"></span><span class="dot"></span><span class="dot"></span>
  </div>
  <div class="progress-bar">
    <div class="progress-fill"></div>
  </div>
  <div class="loader-info">
    <p>Préparez-vous pour un voyage inoubliable</p>
    <p class="status" id="loaderStatus">Connexion à la base de données...</p>
  </div>
</div>

<?php include(__DIR__ . '/../app/views/layout/header.php'); ?>


    <!-- <nav class="navbar">
        <div class="nav-container">
            <a href="#hero" class="logo" data-aos="zoom-in" data-aos-duration="2000">
                <h1>Flex Travel</h1>
            </a>
            <div class="links" id="nav-links">
                <a href="#hero" data-aos="fade-down" data-aos-duration="1200" data-aos-delay="100">Accueil</a>
                <a href="#" class="" data-aos="fade-down" data-aos-duration="1200"
                    data-aos-delay="200">Acheter un billet</a>
                <a href="#destinations" data-aos="fade-down" data-aos-duration="1200"
                    data-aos-delay="300">Destinations</a>
                <a href="#about" data-aos="fade-down" data-aos-duration="1200" data-aos-delay="400">À Propos</a>
                <a href="#faq" data-aos="fade-down" data-aos-duration="1200" data-aos-delay="500">FAQ</a>
                <a href="contact.html" data-aos="fade-down" data-aos-duration="1200" data-aos-delay="600">Contacts</a>
            </div>
            <div class="menu-icon" id="menu-Icon">
                <i class="fa-solid fa-bars"></i>
            </div>
        </div>
    </nav> -->

    <!-- <section id="hero" class="hero-banner">
        <div class="swiper banner-slide-1 top-slide">...</div>
        <div class="swiper banner-slide-2 middle-slide" dir="rtl">...</div>
        <div class="swiper banner-slide-3 bottom-slide">...</div>
        <div class="hero-overlay"></div>
        <div class="text-center hero-content">
            <p class="hero-subtitle" data-aos="fade-up" data-aos-duration="1000">VOYAGEZ PARTOUT, SIMPLEMENT</p>
            <h1 class="hero-title" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="200">Achetez vos billets de voyage<br> en quelques clics avec **Flex Travel**.</h1>
            <p class="hero-description" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="400">Réservez facilement vos billets de bus, train ou avion pour toutes vos destinations préférées. Profitez de tarifs avantageux, d’un service rapide et sécurisé, et partez à la découverte du monde sans stress !</p>
            <a href="#" class="hero-button" data-aos="zoom-in" data-aos-duration="1500" data-aos-delay="600">RÉSERVEZ VOTRE BILLET</a>

            <div class="partenaire-logo" data-aos="fade-up" data-aos-duration="1500" data-aos-delay="800">
                <p>Nos partenaires de confiance :</p>
                <div class="slider-track">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/fox.png" alt="Partenaire 1">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/disney.webp" alt="Partenaire 2">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/netflix.webp" alt="Partenaire 3">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/prime-video.webp" alt="Partenaire 4">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/fi.webp" alt="Partenaire 5">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/bein.webp" alt="Partenaire 6">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/videoland.webp" alt="Partenaire 7">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/viaplay.webp" alt="Partenaire 8">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/apple-tv-icon.webp" alt="Partenaire 9">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/film.webp" alt="Partenaire 10">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/fox.png" alt="Partenaire 1">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/disney.webp" alt="Partenaire 2">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/netflix.webp" alt="Partenaire 3">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/prime-video.webp" alt="Partenaire 4">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/fi.webp" alt="Partenaire 5">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/bein.webp" alt="Partenaire 6">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/videoland.webp" alt="Partenaire 7">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/viaplay.webp" alt="Partenaire 8">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/apple-tv-icon.webp" alt="Partenaire 9">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/disney.webp" alt="Partenaire 2">
                </div>
            </div>

        </div>
    </section> -->

    <section id="hero" class="hero-banner">
        <div class="swiper banner-slide-1 top-slide">
            <div class="swiper-wrapper">
                <div class="swiper-slide"><img decoding="async" src="./img/abidjan.jpg" alt="Abidjan"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/bafang.jpg" alt="Bafang"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/barcelone.jpg" alt="Barcelone"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/bruxelle.jpg" alt="Bruxelle"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/buea.jpg" alt="Buea"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/casablanca.jpg" alt="Casablanca"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/chicago.jpg" alt="Chicago"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/douala.jpg" alt="Douala"></div>
            </div>
        </div>

        <div class="swiper banner-slide-2 middle-slide" dir="rtl">
            <div class="swiper-wrapper">
                <div class="swiper-slide"><img decoding="async" src="./img/eau.jpg" alt="Slider"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/globe.jpg" alt="globe"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/hawaii.jpg" alt="Hawaii"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/ile-caiman.jpg" alt="Ile-Caimans"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/kribi.jpg" alt="Kribi"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/lagos.jpg" alt="Lagos"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/liberter.jpg" alt="liberter"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/limbe.jpg" alt="Limbe"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/londre.jpg" alt="Londre">
                </div>
                <div class="swiper-slide"><img decoding="async" src="./img/madrid.jpg" alt="Madrid"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/marseille.jpg" alt="Marseille"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/maurice.jpg" alt="Ile-Maurice"></div>
            </div>
        </div>

        <div class="swiper banner-slide-3 bottom-slide">
            <div class="swiper-wrapper">
                <div class="swiper-slide"><img decoding="async" src="./img/milan.jpg" alt="Milan"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/pays.jpg" alt="Slider"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/plage-2.jpg" alt="Slider"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/plage-coco.jfif" alt="Slider"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/plage.jfif" alt="Slider"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/shenzen.jpg" alt="Shenzen"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/tour-eiffel.jpg" alt="Paris"></div>
                <div class="swiper-slide"><img decoding="async" src="./img/voyage-all.jpg" alt="Slider"></div>
            </div>
        </div>

        <div class="hero-overlay"></div>
        <div class="text-center px-4 py-10 sm:py-12 md:py-16 lg:py-20 max-w-4xl mx-auto">
            <p class="hero-subtitle" data-aos="fade-up" data-aos-duration="1000">VOYAGEZ PARTOUT, SIMPLEMENT</p>
            <h1 class="hero-title" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="200">Achetez vos billets
                de voyage
                <br> en quelques clics avec Flex Travel.
            </h1>
            <p class="hero-description" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="400">Réservez
                facilement vos billets de bus,
                train ou avion pour toutes vos destinations préférées. Profitez de tarifs avantageux,
                d’un service rapide et sécurisé, et partez à la découverte du monde sans stress !</p>
            <a href="./shop-e.html" class="hero-button" data-aos="zoom-in" data-aos-duration="1500"
                data-aos-delay="600">RÉSERVEZ VOTRE BILLET</a>
            <!-- <div class="partenaire-logo">
                <div class="slider-track">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/fox.png" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/disney.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/netflix.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/prime-video.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/fi.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/bein.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/videoland.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/viaplay.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/apple-tv-icon.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/film.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/fox.png" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/disney.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/netflix.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/prime-video.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/fi.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/bein.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/videoland.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/viaplay.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/apple-tv-icon.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                    <img src="https://tellystudio.com/wp-content/uploads/2025/01/disney.webp" alt="HBO Logo"
                        class="hero-logo-hbo mx-auto">
                </div>
            </div> -->

        </div>
    </section>

    <section id="destinations" class="explore section-padding">
        <div class="section-header">
            <h2 data-aos="fade-up" data-aos-duration="1200">Réservez votre prochaine destination 🌎</h2>
            <p class="sub-heading" data-aos="flip-up" data-aos-duration="1200" data-aos-delay="200">Flex Travel vous
                permet d’acheter vos billets de voyage pour toutes vos destinations préférées, en toute sécurité et à
                des tarifs avantageux. Découvrez le monde avec nous !</p>
        </div>
        <div class="places">
            <div class="card" data-aos="fade-right" data-aos-duration="1200" data-aos-delay="400">
                <img src="./img/mini1.jpg" alt="Paris">
                <div class="main-card">
                    <h3>Paris</h3>
                    <span>France</span>
                    <a href="#" class="btn-card">Réserver <i class="fa fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="card" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="600">
                <img src="./img/mini5.jpg" alt="Casablanca">
                <div class="main-card">
                    <h3>Casablanca</h3>
                    <span>Maroc</span>
                    <a href="#" class="btn-card">Réserver <i class="fa fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="card" data-aos="fade-left" data-aos-duration="1200" data-aos-delay="400">
                <img src="./img/mini2.jpg" alt="New York">
                <div class="main-card">
                    <h3>New York</h3>
                    <span>États-Unis</span>
                    <a href="#" class="btn-card">Réserver <i class="fa fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="more" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="800">
            <a href="#">Voir plus de destinations <i class="fa fa-chevron-right"></i></a>
        </div>
    </section>

    <section class="gallery section-padding">
        <div class="section-header">
            <h2 data-aos="fade-up" data-aos-duration="1200">Galerie de Voyages 📸</h2>
        </div>
        <div class="gallery-container">
            <div class="image-gallery">
                <div class="gallery-col">
                    <img src="./img/per1.jpg" alt="Voyage 1" data-aos="zoom-in-right" data-aos-duration="1200"
                        data-aos-delay="200">
                </div>
                <div class="gallery-col">
                    <img src="./img/per2.jpg" alt="Voyage 2" data-aos="zoom-in-down" data-aos-duration="1200"
                        data-aos-delay="400">
                    <img src="./img/per3.jpg" alt="Voyage 3" data-aos="zoom-in-up" data-aos-duration="1200"
                        data-aos-delay="200">
                </div>
            </div>

            <div class="gallery-content">
                <h3 class="section-title" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="400">
                    Découvrez nos plus belles destinations
                </h3>
                <p class="section-subtitle" data-aos="fade-left" data-aos-duration="1200" data-aos-delay="600">
                    Trouvez et réservez votre billet pour la destination de vos rêves. Profitez d’offres exclusives et
                    partez à l’aventure avec Flex Travel !
                </p>
                <a href="#" class="btn btn-secondary" data-aos="zoom-in" data-aos-duration="1200"
                    data-aos-delay="800">Voir tout</a>
            </div>
        </div>
    </section>

    <section id="about" class="about-section section-padding">
        <div class="about-content">
            <h2 data-aos="fade-right" data-aos-duration="1200">À Propos de Flex Travel 🚀</h2>
            <p data-aos="fade-right" data-aos-duration="1200" data-aos-delay="200">
                **Flex Travel** est né de la passion du voyage et de la volonté de rendre la réservation de billets
                **simple, rapide et accessible** à tous. Nous croyons que l'aventure commence dès la planification.
            </p>
            <p data-aos="fade-right" data-aos-duration="1200" data-aos-delay="400">
                Notre plateforme agrège les meilleures offres de bus, train et avion, vous garantissant des tarifs
                compétitifs et un choix optimal. Que vous partiez pour une escapade de week-end ou une grande aventure
                autour du monde, nous sommes votre partenaire de voyage idéal.
            </p>
            <div class="about-stats">
                <div class="stat-item" data-aos="zoom-in" data-aos-duration="1000" data-aos-delay="500">
                    <i class="fas fa-ticket-alt"></i>
                    <span>10M+ Billets Vendus</span>
                </div>
                <div class="stat-item" data-aos="zoom-in" data-aos-duration="1000" data-aos-delay="600">
                    <i class="fas fa-globe"></i>
                    <span>200+ Destinations</span>
                </div>
                <div class="stat-item" data-aos="zoom-in" data-aos-duration="1000" data-aos-delay="700">
                    <i class="fas fa-users"></i>
                    <span>50K+ Clients Satisfaits</span>
                </div>
            </div>
        </div>
        <div class="about-image" data-aos="fade-left" data-aos-duration="1200" data-aos-delay="200">
            <img src="./img/voyage-all.jpg" alt="Équipe Flex Travel en voyage">
        </div>
    </section>

    <section id="faq" class="faq-section section-padding">
        <div class="section-header">
            <h2 data-aos="fade-up" data-aos-duration="1200">Questions Fréquentes (FAQ) ❓</h2>
            <p class="sub-heading" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="200">Trouvez rapidement
                les réponses à vos questions les plus courantes.</p>
        </div>
        <div class="faq-container">
            <div class="faq-item" data-aos="fade-right" data-aos-duration="1000" data-aos-delay="300">
                <input type="checkbox" id="faq1" class="faq-toggle">
                <label for="faq1" class="faq-question">Comment puis-je réserver un billet ? <i
                        class="fa-solid fa-chevron-down"></i></label>
                <div class="faq-answer">
                    La réservation se fait en 3 étapes simples : 1. Recherchez votre destination sur la page d'accueil.
                    2. Choisissez le billet et l'horaire qui vous conviennent. 3. Payez de manière sécurisée en ligne.
                </div>
            </div>
            <div class="faq-item" data-aos="fade-right" data-aos-duration="1000" data-aos-delay="400">
                <input type="checkbox" id="faq2" class="faq-toggle">
                <label for="faq2" class="faq-question">Quels sont les modes de paiement acceptés ? <i
                        class="fa-solid fa-chevron-down"></i></label>
                <div class="faq-answer">
                    Nous acceptons les cartes de crédit/débit (Visa, MasterCard), PayPal, et certains paiements mobiles
                    locaux pour votre confort.
                </div>
            </div>
            <div class="faq-item" data-aos="fade-right" data-aos-duration="1000" data-aos-delay="500">
                <input type="checkbox" id="faq3" class="faq-toggle">
                <label for="faq3" class="faq-question">Puis-je modifier ou annuler ma réservation ? <i
                        class="fa-solid fa-chevron-down"></i></label>
                <div class="faq-answer">
                    La possibilité de modification ou d'annulation dépend des conditions de la compagnie de transport
                    choisie. Veuillez consulter notre section "Conditions Générales" ou contacter le support.
                </div>
            </div>
        </div>
    </section>

    <section class="subscribe section-padding">
        <div class="overlay-subscribe"></div>
        <div class="subscribe-container">
            <div class="subscribe-content">
                <h2 class="subscribe-title" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="200">
                    Abonnez-vous pour recevoir nos offres spéciales 🎁</h2>
                <p class="section-subtitle" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="200">Recevez les
                    meilleures offres de billets de voyage, des promotions exclusives et des conseils pour voyager moins
                    cher. Inscrivez-vous à notre newsletter !</p>
            </div>
            <div class="subscribe-form" data-aos="zoom-in" data-aos-duration="1200" data-aos-delay="400">
                <form action="">
                    <input type="email" placeholder="Votre email ici" required>
                    <button class="btn btn-primary" type="submit">S'abonner</button>
                </form>
            </div>
        </div>
    </section>


    <footer class="footer">
        <div class="footer-container">
            <div class="footer-col" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="100">
                <h3>Flex Travel</h3>
                <p>Achetez vos billets de voyage facilement et partez à la découverte du monde avec Flex Travel.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-col" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="200">
                <h4>Support</h4>
                <p><a href="#faq">FAQ</a></p>
                <p><a href="#">Conditions Générales</a></p>
                <p><a href="#">Politique de Confidentialité</a></p>
                <p><a href="contact.html">Contact</a></p>
            </div>
            <div class="footer-col" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="300">
                <h4>Informations</h4>
                <p> <span>Adresse :</span> Akwa, Boulevard de la Republique</p>
                <p> <span>Email :</span> info@flextravel.cm</p>
                <p> <span>Phone :</span> +237 584 523 654</p>
            </div>
        </div>
        <div class="footer-bar" data-aos="zoom-in" data-aos-duration="1200" data-aos-delay="400">
            copyright &copy; 2025 || **Flex Travel** - Tous droits réservés
        </div>
    </footer>

    <script src="./js/main.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script> AOS.init(); </script>
    <script>
        // JS simple pour le menu mobile
        const menuIcon = document.getElementById('menu-Icon');
        const navLinks = document.getElementById('nav-links');
        if (menuIcon && navLinks) {
            menuIcon.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                const icon = menuIcon.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-bars');
                    icon.classList.toggle('fa-xmark');
                }
            });
        }
    </script>

    <!-- Modal billet type selector -->
    <?php include __DIR__ . '/modal-billet.html'; ?>
    <script>
    // Accessible modal control & wiring
    (function(){
      const modal = document.getElementById('modal-billet');
      const focusableSelector = '.billet-type-btn, #close-modal-billet';
      let lastFocused = null;

      function showBilletModal() {
                if (!modal) return;
                lastFocused = document.activeElement;
                // make visible for older simple modal markup (some versions use inline style)
                try { modal.style.display = 'flex'; } catch (e) {}
                modal.setAttribute('aria-hidden','false');
                document.body.style.overflow = 'hidden';
      }

      function hideBilletModal() {
                if (!modal) return;
                // hide both by aria and inline style (compat with simple modal file)
                modal.setAttribute('aria-hidden','true');
                try { modal.style.display = 'none'; } catch (e) {}
                document.body.style.overflow = '';
                if (lastFocused) lastFocused.focus();
      }

      // expose to global so modal script can call if needed
      window.showBilletModal = showBilletModal;
      window.hideBilletModal = hideBilletModal;

      document.addEventListener('DOMContentLoaded', function() {
        // Attach modal open to all "Acheter un billet" and "RÉSERVEZ VOTRE BILLET" buttons
        document.querySelectorAll('a,button').forEach(function(el) {
            try {
              const txt = el.textContent || '';
              if (txt.trim().toLowerCase().includes('acheter un billet') || txt.trim().toLowerCase().includes('réservez votre billet')) {
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    showBilletModal();
                });
              }
            } catch (err) { /* ignore non-text nodes */ }
        });

        // Close modal wiring
        const closeBtn = document.getElementById('close-modal-billet');
        if (closeBtn) closeBtn.addEventListener('click', hideBilletModal);

        const modalEl = document.getElementById('modal-billet');
        if (modalEl) {
          modalEl.addEventListener('click', function(e) { if (e.target === modalEl) hideBilletModal(); });
        }

        // Redirect on billet type select
        // compute project-root aware base so redirects work from localhost
        const _projBase = window.location.origin + '/projet-agence-de-voyage';
        document.querySelectorAll('.billet-type-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var type = btn.getAttribute('data-type');
                if (type === 'bus') window.location.href = _projBase + '/app/views/bus-page.php';
                else if (type === 'train') window.location.href = _projBase + '/app/views/train-page.php';
                else if (type === 'bateau') window.location.href = _projBase + '/app/views/bateau-page.php';
            });
        });
      });
    })();

    // Loader Management
    const loaderContainer = document.getElementById('loaderContainer');
    const loaderStatus = document.getElementById('loaderStatus');

    // Simulate loading steps
    const loadingSteps = [
      'Connexion à la base de données...',
      'Chargement des trajets...',
      'Préparation de l\'interface...',
      'Finalisation...'
    ];

    let currentStep = 0;

    function updateLoaderStatus() {
      if (currentStep < loadingSteps.length) {
        loaderStatus.textContent = loadingSteps[currentStep];
        currentStep++;
        setTimeout(updateLoaderStatus, 800);
      }
    }

    // Start loading animation
    updateLoaderStatus();

    // Hide loader when page is fully loaded
    window.addEventListener('load', function() {
      setTimeout(function() {
        loaderContainer.classList.add('hidden');
      }, 1000);
    });

    // Optional: Hide loader after timeout (in case load event doesn't fire)
    setTimeout(function() {
      if (!loaderContainer.classList.contains('hidden')) {
        loaderContainer.classList.add('hidden');
      }
    }, 5000);
    </script>
</body>

</html>