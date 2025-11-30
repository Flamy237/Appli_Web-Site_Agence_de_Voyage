// Votre code JavaScript principal
document.addEventListener('DOMContentLoaded', () => {
    const thumbnailsContainer = document.querySelector('.thumbnails-overlay');
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainSliderBg = document.getElementById('main-slider-bg');
    
    
    const images = [
      './img/tour-eiffel.jpg',
        './img/liberter.jpg',
        './img/bruxelle.jpg',
        './img/shenzen.jpg',
        './img/londre.jpg',
        './img/madrid.jpg',
        './img/barcelone.jpg',
        './img/chicago.jpg',
        './img/marseille.jpg',
        './img/milan.jpg',
        './img/hawaii.jpg',
        './img/ile-caiman.jpg',
        './img/maurice.jpg',
         './img/lagos.jpg',
         './img/casablanca.jpg',
         './img/douala.jpg',
         './img/yaounde.jpg',
         './img/buea.jpg',
         './img/bafang.jpg',
         './img/kribi.jpg',
         './img/limbe.jpg',
         './img/abidjan.jpg',
        './img/voyage-bus.jpg',
        './img/voyage-all.jpg',
        './img/voyage-avion.jpg',
        './img/plage-2.jpg',
        './img/eau.jpg',
        './img/pays.jpg',
        './img/word.jpg',
        './img/globe.jpg',
        './img/plage-coco.jfif',
        './img/plage.jfif'
        
    ];
    
    let currentIndex = 0;
    const intervalTime = 5000;

    // --- NOUVELLE FONCTION POUR DÉFILER LE CONTENEUR ---
    function scrollThumbnailIntoView(index) {
        const activeThumbnail = thumbnails[index];
        if (activeThumbnail) {
            // Calcule le centre du conteneur
            const containerWidth = thumbnailsContainer.offsetWidth;
            // Calcule la position de la vignette par rapport au début du conteneur
            const thumbnailCenter = activeThumbnail.offsetLeft + activeThumbnail.offsetWidth / 2;
            
            // Nouvelle position de défilement pour centrer la miniature
            const scrollPosition = thumbnailCenter - containerWidth / 2;
            
            thumbnailsContainer.scrollTo({
                left: scrollPosition,
                behavior: 'smooth' // Animation de défilement douce
            });
        }
    }
    // ----------------------------------------------------

    function showSlide(index) {
        mainSliderBg.style.backgroundImage = `url('${images[index]}')`;
        thumbnails.forEach((thumb, i) => {
            thumb.classList.toggle('active', i === index);
        });
        
        // --- APPEL DE LA NOUVELLE FONCTION ---
        scrollThumbnailIntoView(index); 
        // ------------------------------------
    }

    function autoScroll() {
        currentIndex = (currentIndex + 1) % images.length;
        showSlide(currentIndex);
    }

    // Démarre le défilement automatique
    setInterval(autoScroll, intervalTime);

    // Gère le clic sur les miniatures
    thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener('click', () => {
            currentIndex = index;
            showSlide(currentIndex);
            // Assurez-vous que le timer se réinitialise après un clic utilisateur
            clearInterval(autoScrollInterval); 
            autoScrollInterval = setInterval(autoScroll, intervalTime);
        });
    });
    
    // Initialisation
    showSlide(currentIndex);
    
    // Pour pouvoir réinitialiser l'intervalle après un clic
    let autoScrollInterval = setInterval(autoScroll, intervalTime); 
});


const menuIcon = document.getElementById('menu-Icon');
 const links = document.querySelector('.links');

menuIcon.addEventListener('click', () =>{
    // if(links.style.right === '0px'){
    //     links.style.right = '-100%';
    // }else{
    //     links.style.right = '0px';
    // }
links.classList.toggle('active');
});
