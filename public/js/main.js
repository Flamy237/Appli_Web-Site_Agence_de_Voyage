// Script javascript principale


/**********************************************************************************************************************
================================================ Script de avion-page.css (Refonte complète)  ====================================
**********************************************************************************************************************/

document.addEventListener('DOMContentLoaded', () => {
    const menuIcon = document.getElementById('menu-Icon');
    const navbar = document.querySelector('.navbar');

    // Gestion du menu hamburger (mobile)
    menuIcon.addEventListener('click', () => {
        // Basculer la classe 'active' pour gérer l'affichage en CSS (media queries)
        navbar.classList.toggle('active');
    });

    // Gestion des boutons radio Aller-Retour / Aller Simple
    const returnTripRadio = document.getElementById('return-trip');
    const onewayTripRadio = document.getElementById('oneway-trip');
    const returnDateWrapper = document.getElementById('return-date-wrapper');
    const returnDateInput = document.getElementById('dateR');

    const toggleReturnDate = () => {
        if (returnTripRadio.checked) {
            // Aller-Retour: Afficher le champ de date de retour et le rendre requis
            returnDateWrapper.style.display = 'block';
            returnDateInput.setAttribute('required', 'required');
        } else {
            // Aller Simple: Masquer le champ de date de retour et supprimer l'attribut requis
            returnDateWrapper.style.display = 'none';
            returnDateInput.removeAttribute('required');
        }
    };

    // Écouter les changements sur les boutons radio
    returnTripRadio.addEventListener('change', toggleReturnDate);
    onewayTripRadio.addEventListener('change', toggleReturnDate);

    // Initialiser l'état au chargement de la page (par défaut: Aller-Retour)
    toggleReturnDate();


    // Gestion du Slider
    const sliderImage = document.querySelector('.slider-image');
    const slides = document.querySelectorAll('.slide');
    const prevbtn = document.getElementById('prev');
    const nextbtn = document.getElementById('next');

    let currentIndex = 0;

    // Fonction pour calculer la largeur d'une slide, y compris l'espace (gap)
    const getSlideWidth = () => slides.length > 0 ? slides[0].offsetWidth + 20 : 0; // 20px de gap défini dans le CSS

    const moveSlider = () => {
        const slideWidth = getSlideWidth();
        sliderImage.style.transform = `translateX(-${currentIndex * slideWidth}px)`;
    };

    // Redimensionner le slider si la fenêtre change
    window.addEventListener('resize', moveSlider);

    nextbtn.addEventListener('click', () => {
        // Limiter le mouvement pour ne pas afficher l'espace vide à la fin
        if (currentIndex < slides.length - 1) {
            currentIndex = (currentIndex + 1);
        } else {
            currentIndex = 0; // Boucler au début (optionnel)
        }
        moveSlider();
    });

    prevbtn.addEventListener('click', () => {
        if (currentIndex > 0) {
            currentIndex = (currentIndex - 1);
        } else {
            currentIndex = slides.length - 1; // Boucler à la fin (optionnel)
        }
        moveSlider();
    });

});