/**
 * Dynamic Image Loading
 * Hotel Corintel
 *
 * Loads images from the database via API
 * Falls back to default images if API is unavailable
 */

(function() {
  'use strict';

  const ImageLoader = {
    cache: {},

    /**
     * Fetch images for a section
     */
    async fetchImages(section) {
      if (this.cache[section]) {
        return this.cache[section];
      }

      try {
        const response = await fetch(`/api/images.php?section=${section}`);
        if (!response.ok) throw new Error('API error');

        const data = await response.json();
        this.cache[section] = data.images;
        return data.images;
      } catch (error) {
        console.warn('Could not load images from API:', error);
        return null;
      }
    },

    /**
     * Get image by slot name
     */
    getImageBySlot(images, slot) {
      if (!images) return null;
      return images.find(img => img.slot === slot);
    },

    /**
     * Get image by position
     */
    getImageByPosition(images, position) {
      if (!images) return null;
      return images.find(img => img.position === position);
    },

    /**
     * Apply image to element
     */
    applyImage(element, image) {
      if (!element || !image) return;

      if (element.tagName === 'IMG') {
        element.src = image.filename;
        if (image.alt) element.alt = image.alt;
      } else {
        // Background image
        element.style.backgroundImage = `url('${image.filename}')`;
      }
    },

    /**
     * Load images for current page
     */
    async loadPageImages() {
      const section = document.body.dataset.imageSection;
      if (!section) return;

      const images = await this.fetchImages(section);
      if (!images) return;

      // Apply to elements with data-image-slot or data-image-position
      document.querySelectorAll('[data-image-slot]').forEach(el => {
        const slot = el.dataset.imageSlot;
        const image = this.getImageBySlot(images, slot);
        if (image) this.applyImage(el, image);
      });

      document.querySelectorAll('[data-image-position]').forEach(el => {
        const position = parseInt(el.dataset.imagePosition);
        const image = this.getImageByPosition(images, position);
        if (image) this.applyImage(el, image);
      });
    }
  };

  // Auto-load on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ImageLoader.loadPageImages());
  } else {
    ImageLoader.loadPageImages();
  }

  // Expose globally
  window.ImageLoader = ImageLoader;
})();
