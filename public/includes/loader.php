<!-- Loading Screen Component -->
<div id="loaderContainer" class="loader-container">
  <!-- Main Loader Animation -->
  <div class="loader">
    <div class="loader-dot"></div>
  </div>

  <!-- Loading Text with Animated Dots -->
  <div class="loader-text">
    Chargement<span class="dot"></span><span class="dot"></span><span class="dot"></span>
  </div>

  <!-- Progress Bar -->
  <div class="progress-bar">
    <div class="progress-fill"></div>
  </div>

  <!-- Loading Info -->
  <div class="loader-info">
    <p>Préparez-vous pour un voyage inoubliable</p>
    <p class="status" id="loaderStatus">Connexion à la base de données...</p>
  </div>
</div>

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
</script>
