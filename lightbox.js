(function () {
    var lightboxContainer;
    var closeButton;
    var currentImage;
  
    function openLightbox(image) {
      currentImage = image;
      lightboxContainer.style.display = 'block';
      lightboxContainer.classList.add('open');
      document.addEventListener('keydown', handleKeyPress);
      document.addEventListener('click', handleClickOutside);
      updateLightboxImage();
    }
  
    function closeLightbox() {
      lightboxContainer.classList.remove('open');
      document.removeEventListener('keydown', handleKeyPress);
      document.removeEventListener('click', handleClickOutside);
      setTimeout(function () {
        lightboxContainer.style.display = 'none';
      }, 300); // Anpassa övergångstiden här (300 ms)
    }
  
    function handleKeyPress(event) {
      if (event.key === 'Escape') {
        closeLightbox();
      }
    }
  
    function handleClickOutside(event) {
      if (event.target === lightboxContainer) {
        closeLightbox();
      }
    }
  
    function updateLightboxImage() {
      var lightboxImage = lightboxContainer.querySelector('.lightbox-image');
      lightboxImage.src = currentImage.getAttribute('data-large-src');
      lightboxImage.alt = currentImage.alt;
    }
  
    function generateLightboxTemplate() {
      var lightboxTemplate = `
        <div class="lightbox-overlay">
          <div class="lightbox-content">
            <span class="lightbox-close">&times;</span>
            <img src="" alt="" class="lightbox-image">
          </div>
        </div>
      `;
  
      document.body.insertAdjacentHTML('beforeend', lightboxTemplate);
      setupLightbox();
    }
  
    function setupLightbox() {
      lightboxContainer = document.querySelector('.lightbox-overlay');
      closeButton = document.querySelector('.lightbox-close');
  
      var galleryImages = document.querySelectorAll('.crs-gallery-thumbnail img');
  
      galleryImages.forEach(function (image) {
        image.addEventListener('click', function (event) {
          event.preventDefault();
          openLightbox(event.target);
        });
      });
  
      closeButton.addEventListener('click', closeLightbox);
    }
  
    generateLightboxTemplate();
  })();
  