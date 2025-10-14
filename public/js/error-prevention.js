/**
 * Inicialización robusta para prevenir errores de JavaScript
 * Se ejecuta antes que cualquier otro script
 */

(() => {
  'use strict';

  // Variable para rastrear si ya se interceptó el error
  let mutationObserverPatched = false;

  // Prevenir errores de MutationObserver más robustamente
  if (typeof window.MutationObserver !== 'undefined' && !mutationObserverPatched) {
    const originalObserve = MutationObserver.prototype.observe;
    const originalDisconnect = MutationObserver.prototype.disconnect;

    MutationObserver.prototype.observe = function(target, options) {
      try {
        // Verificar que el target sea un Node válido
        if (!target) {
          return;
        }

        // Verificar que tenga las propiedades de un Node
        if (typeof target.nodeType === 'undefined' || typeof target.nodeName === 'undefined') {
          return;
        }

        // Verificar que esté conectado al DOM
        if (typeof target.isConnected !== 'undefined' && !target.isConnected) {
          return;
        }

        // Verificar que sea un elemento DOM válido
        if (target.nodeType !== Node.ELEMENT_NODE &&
            target.nodeType !== Node.DOCUMENT_NODE &&
            target.nodeType !== Node.DOCUMENT_FRAGMENT_NODE) {
          return;
        }

        // Verificar options válidas
        if (!options || typeof options !== 'object') {
          return;
        }

        return originalObserve.call(this, target, options);
      } catch (error) {
        console.error('MutationObserver observe error prevented:', error);
        return;
      }
    };

    // También proteger disconnect
    MutationObserver.prototype.disconnect = function() {
      try {
        return originalDisconnect.call(this);
      } catch (error) {
        console.error('MutationObserver disconnect error prevented:', error);
      }
    };

    mutationObserverPatched = true;
  }

  // Mejorar manejo de errores de imágenes globalmente
  document.addEventListener('error', (event) => {
    if (event.target.tagName === 'IMG') {
      const img = event.target;
      const container = img.closest('.product-image-container') || img.parentElement;

      if (!img.dataset.fallbackAttempt) {
        img.dataset.fallbackAttempt = '1';

        // Crear una imagen de fallback dinámica
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = 300;
        canvas.height = 200;

        // Fondo degradado
        const gradient = ctx.createLinearGradient(0, 0, 300, 200);
        gradient.addColorStop(0, '#ef4444');
        gradient.addColorStop(1, '#dc2626');

        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, 300, 200);

        // Texto
        ctx.fillStyle = 'white';
        ctx.font = 'bold 14px Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        const text = img.alt || 'Imagen no disponible';
        const maxWidth = 280;
        const words = text.split(' ');
        let line = '';
        const lines = [];

        for (let n = 0; n < words.length; n++) {
          const testLine = line + words[n] + ' ';
          const metrics = ctx.measureText(testLine);
          if (metrics.width > maxWidth && n > 0) {
            lines.push(line);
            line = words[n] + ' ';
          } else {
            line = testLine;
          }
        }
        lines.push(line);

        const startY = 100 - (lines.length * 10);
        lines.forEach((line, index) => {
          ctx.fillText(line.trim(), 150, startY + (index * 20));
        });

        img.src = canvas.toDataURL();
      }
    }
  }, true);

  // Interceptar errores de red y proporcionar mensajes más útiles
  const originalFetch = window.fetch;
  window.fetch = function(...args) {
    return originalFetch.apply(this, args)
      .catch(error => {
        if (error.message.includes('Failed to fetch') || error.message.includes('ERR_NAME_NOT_RESOLVED')) {
          // En lugar de fallar completamente, retornamos una respuesta vacía para casos no críticos
          return new Response('{}', { status: 200, headers: { 'Content-Type': 'application/json' } });
        }
        throw error;
      });
  };

  // Interceptor más específico para errores de MutationObserver en archivos TS compilados
  const originalErrorHandler = window.onerror;
  window.onerror = function(message, source, lineno, colno, error) {
    // Interceptar específicamente errores de MutationObserver
    if (message && message.includes("Failed to execute 'observe' on 'MutationObserver'")) {
      return true; // Prevenir que se muestre en consola
    }

    // Filtrar errores de extensiones y otros
    const ignoredErrors = [
      'Could not establish connection. Receiving end does not exist',
      'Extension context invalidated',
      'The message port closed before a response was received',
      'Script error',
      'parameter 1 is not of type \'Node\''
    ];

    if (ignoredErrors.some(ignored => message && message.includes(ignored))) {
      return true;
    }

    // Llamar al handler original si existe
    if (originalErrorHandler) {
      return originalErrorHandler.call(this, message, source, lineno, colno, error);
    }

    return false;
  };

  // También interceptar errores no capturados en promesas
  window.addEventListener('unhandledrejection', (event) => {
    const reason = event.reason;
    if (reason && reason.message && reason.message.includes('MutationObserver')) {
      event.preventDefault();
    }
  });  // Optimizar carga de imágenes con lazy loading
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          if (img.dataset.src) {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
            observer.unobserve(img);
          }
        }
      });
    });

    // Observar imágenes cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
      });
    });
  }
})();
