<?php ?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home | Travel</title>
  <!-- Police d'ecriture -->
  <link href="https://fonts.googleapis.com/css?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    integrity="sha512-papm6HkA+2QwQJQwQJQwQJQwQJQwQJQwQJQwQJQwQJQwQJQwQJQwQJQwQJQwQJQwQJQwQJQwQJQwQJQwQJQw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- <link rel="stylesheet" href="./css/style.css"> -->
  <link rel="stylesheet" href="./css/Home.css">
  <link rel="stylesheet" href="./css/loader.css">
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

  <header class="header">
    <nav class="top-nav">
      <div class="nav-links">
        <a href="./Home.php" class="nav-item active">Commencer</a>
        <a href="#" class="nav-item">Bus</a>
       <a href="#" class="nav-item">Train</a>
        <a href="#" class="nav-item">Avion</a>
        <a href="#" class="nav-item">Visiter</a>
      </div>
      <div class="search-icon" id="searchIcon">
        <input type="text" class="search-input" placeholder="Rechercher" />
        <i class="fa-solid fa-magnifying-glass" id="searchBtn"></i>
      </div>
    </nav> 

    <section class="main-slider-container">
      <div id="main-slider-bg" class="slider-item active" style="background-image: url('./img/plage.jfif');"></div>
      <div class="slider-bg-overlay"></div>
      <div class="title-etv">
        <p class="hero-subtitle">Agence de voyage</p>
      </div>
      <div class="slider-caption">
        <h1>Explorez le monde, vivez l'inoubliable !!</h1>
        <p>Découvrez les destinations de vos rêves,
          ressentez l'excitation de l'inconnu et laissez-vous émerveiller par des paysages grandioses. Votre aventure
          commence ici.</p>
      </div>
      <a href="#" class="buton-token">Obtenez votre ticket</a>
      <div class="thumbnails-overlay">
        
        <div class="thumbnail active" data-index="11">
          <img src="./img/tour-eiffel.jpg" alt="plage" />
          <p class="thumbnail-title">Paris</p>
        </div>
        <div class="thumbnail" data-index="12">
          <img src="./img/liberter.jpg" alt="liberter" />
          <p class="thumbnail-title">New York</p>
        </div>
        <div class="thumbnail" data-index="13">
          <img src="./img/bruxelle.jpg" alt="bruxelle" />
          <p class="thumbnail-title">Bruxelle</p>
        </div>
        <div class="thumbnail" data-index="14">
          <img src="./img/shenzen.jpg" alt="shenzen" />
          <p class="thumbnail-title">Shenzen</p>
        </div>
        <div class="thumbnail" data-index="15">
          <img src="./img/londre.jpg" alt="londre" />
          <p class="thumbnail-title">Londre</p>
        </div>
        <div class="thumbnail" data-index="16">
          <img src="./img/madrid.jpg" alt="madrid" />
          <p class="thumbnail-title">Madrid</p>
        </div>
        <div class="thumbnail" data-index="17">
          <img src="./img/barcelone.jpg" alt="barcelone" />
          <p class="thumbnail-title">Barcelone</p>
        </div>
        <div class="thumbnail " data-index="0">
          <img src="./img/chicago.jpg" alt="chicago" />
          <p class="thumbnail-title">Chicago</p>
        </div>
        <div class="thumbnail " data-index="0">
          <img src="./img/marseille.jpg" alt="marseille" />
          <p class="thumbnail-title">Marseille</p>
        </div>
        <div class="thumbnail" data-index="0">
          <img src="./img/milan.jpg" alt="milan" />
          <p class="thumbnail-title">Milan</p>
        </div>
        <div class="thumbnail" data-index="0">
          <img src="./img/hawaii.jpg" alt="hawaii" />
          <p class="thumbnail-title">Hawaii</p>
        </div>
        <div class="thumbnail" data-index="0">
          <img src="./img/ile-caiman.jpg" alt="ile-caiman" />
          <p class="thumbnail-title">Ile-Caimans</p>
        </div>
        <div class="thumbnail" data-index="12">
          <img src="./img/maurice.jpg" alt="Ile-maurice" />
          <p class="thumbnail-title">Ile-Maurice</p>
        </div>
        <div class="thumbnail" data-index="0">
          <img src="./img/lagos.jpg" alt="Lagos" />
          <p class="thumbnail-title">Lagos</p>
        </div>
         <div class="thumbnail" data-index="0">
          <img src="./img/casablanca.jpg" alt="casablanca" />
          <p class="thumbnail-title">Casablanca</p>
        </div>
         <div class="thumbnail" data-index="0">
          <img src="./img/douala.jpg" alt="Douala" />
          <p class="thumbnail-title">Douala</p>
        </div>
         <div class="thumbnail" data-index="0">
          <img src="./img/yaounde.jpg" alt="yaounde" />
          <p class="thumbnail-title">Yaounde</p>
        </div>
         <div class="thumbnail" data-index="0">
          <img src="./img/buea.jpg" alt="buea" />
          <p class="thumbnail-title">Buea</p>
        </div>
         <div class="thumbnail" data-index="0">
          <img src="./img/bafang.jpg" alt="bafang" />
          <p class="thumbnail-title">Bafang</p>
        </div>
         <div class="thumbnail" data-index="0">
          <img src="./img/kribi.jpg" alt="kribi" />
          <p class="thumbnail-title">Kribi</p>
        </div>
         <div class="thumbnail" data-index="0">
          <img src="./img/limbe.jpg" alt="limbe" />
          <p class="thumbnail-title">Limbe</p>
        </div>
         <div class="thumbnail" data-index="0">
          <img src="./img/abidjan.jpg" alt="abidjan" />
          <p class="thumbnail-title">Abidjan</p>
        </div>
        <div class="thumbnail " data-index="0">
          <img src="./img/voyage-bus.jpg" alt="voyage-bus" />
          <p class="thumbnail-title">Let's Go</p>
        </div>
        <div class="thumbnail" data-index="2">
          <img src="./img/voyage-all.jpg" alt="voyage" />
        </div>
        <div class="thumbnail" data-index="3">
          <img src="./img/voyage-avion.jpg" alt="voyage-avion" />
        </div>
        <div class="thumbnail" data-index="4">
          <img src="./img/plage-2.jpg" alt="plage" />
        </div>
        <div class="thumbnail" data-index="5">
          <img src="./img/eau.jpg" alt="plage secondaire" />
        </div>
        <div class="thumbnail" data-index="6">
          <img src="./img/pays.jpg" alt="pays" />
        </div>
        <div class="thumbnail" data-index="7">
          <img src="./img/word.jpg" alt="world" />
        </div>
        <div class="thumbnail" data-index="8">
          <img src="./img/globe.jpg" alt="globe" />
        </div>
        <div class="thumbnail" data-index="9">
          <img src="./img/plage-coco.jfif" alt="plage + coco" />
        </div>
        <div class="thumbnail" data-index="10">
          <img src="./img/plage.jfif" alt="plage" />
        </div>
      </div>
    </section>
  </header>

  <script>
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

    // Search functionality
    document.addEventListener('DOMContentLoaded', function () {
      const searchBtn = document.getElementById('searchBtn');
      const searchIcon = document.getElementById('searchIcon');
      const navLinks = document.querySelector('.nav-links');
      let searchOpen = false;
      searchBtn.addEventListener('click', function (e) {
        e.preventDefault();
        searchOpen = !searchOpen;
        if (searchOpen) {
          searchIcon.classList.add('active');
          navLinks.classList.add('hide');
          searchIcon.querySelector('.search-input').focus();
        } else {
          searchIcon.classList.remove('active');
          navLinks.classList.remove('hide');
        }
      });
      searchIcon.querySelector('.search-input').addEventListener('blur', function () {
        setTimeout(function () {
          searchIcon.classList.remove('active');
          navLinks.classList.remove('hide');
          searchOpen = false;
        }, 200);
      });
    });
  </script>
  <script src="./js/Home.js"></script>
</body>

</html>
