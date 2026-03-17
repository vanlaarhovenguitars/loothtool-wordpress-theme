/**
 * Secret DOOM Easter Egg — Konami Code Trigger
 *
 * Enter the Konami Code (↑ ↑ ↓ ↓ ← → ← → B A) anywhere on the site
 * to launch DOOM (Shareware Episode 1) via js-dos.
 *
 * @package HelloElementorChild
 */
(function () {
  'use strict';

  var KONAMI = [
    'ArrowUp', 'ArrowUp',
    'ArrowDown', 'ArrowDown',
    'ArrowLeft', 'ArrowRight',
    'ArrowLeft', 'ArrowRight',
    'b', 'a'
  ];
  var progress = 0;

  // DOOM shareware bundle hosted on dos.zone CDN (freely distributable Episode 1)
  var JSDOS_JS  = 'https://js-dos.com/v7/build/js-dos.js';
  var JSDOS_CSS = 'https://js-dos.com/v7/build/js-dos.css';
  var WASM_URL  = 'https://js-dos.com/v7/build/wdosbox.js';
  var DOOM_BUNDLE = 'https://cdn.dos.zone/original/2X/2/2948cd4c-7a97-49aa-a3d4-65e2b2a4e0aa.jsdos';

  document.addEventListener('keydown', function (e) {
    var expected = KONAMI[progress];
    if (e.key === expected || e.key.toLowerCase() === expected) {
      progress++;
      if (progress === KONAMI.length) {
        progress = 0;
        launchDoom();
      }
    } else {
      progress = 0;
    }
  });

  function launchDoom() {
    // Prevent double-launch
    if (document.getElementById('lt-doom-overlay')) return;

    // Build overlay
    var overlay = document.createElement('div');
    overlay.id = 'lt-doom-overlay';
    overlay.innerHTML =
      '<div id="lt-doom-header">' +
        '<span id="lt-doom-title">DOOM — Episode 1: Knee-Deep in the Dead</span>' +
        '<button id="lt-doom-close" title="Exit DOOM (Esc)">&times;</button>' +
      '</div>' +
      '<div id="lt-doom-loading">' +
        '<div class="lt-doom-spinner"></div>' +
        '<p>Loading DOOM...</p>' +
        '<p class="lt-doom-hint">Shareware Episode 1 &mdash; id Software, 1993</p>' +
      '</div>' +
      '<div id="lt-doom-container"></div>';

    document.body.appendChild(overlay);

    // Prevent page scroll while DOOM is active
    document.body.style.overflow = 'hidden';

    // Close handler
    var closeBtn = document.getElementById('lt-doom-close');
    closeBtn.addEventListener('click', closeDoom);
    document.addEventListener('keydown', escHandler);

    // Load js-dos CSS
    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = JSDOS_CSS;
    document.head.appendChild(css);

    // Load js-dos JS then boot DOOM
    var script = document.createElement('script');
    script.src = JSDOS_JS;
    script.onload = function () {
      bootDoom();
    };
    script.onerror = function () {
      var loading = document.getElementById('lt-doom-loading');
      if (loading) {
        loading.innerHTML =
          '<p style="color:#ff4444">Failed to load DOOM engine.</p>' +
          '<p>Check your internet connection and try again.</p>';
      }
    };
    document.head.appendChild(script);
  }

  var doomInstance = null;

  function bootDoom() {
    var container = document.getElementById('lt-doom-container');
    var loading = document.getElementById('lt-doom-loading');
    if (!container || typeof Dos === 'undefined') return;

    // Configure wasm path
    emulators.pathPrefix = 'https://js-dos.com/v7/build/';

    Dos(container, {
      wdosboxUrl: WASM_URL
    }).run(DOOM_BUNDLE).then(function (ci) {
      doomInstance = ci;
      if (loading) loading.style.display = 'none';
    }).catch(function () {
      if (loading) {
        loading.innerHTML =
          '<p style="color:#ff4444">Failed to start DOOM.</p>' +
          '<p>The game bundle may be temporarily unavailable.</p>';
      }
    });
  }

  function escHandler(e) {
    // Only close on Escape when not in-game (js-dos captures most keys)
    // The close button is always available as fallback
    if (e.key === 'Escape' && document.getElementById('lt-doom-overlay')) {
      closeDoom();
    }
  }

  function closeDoom() {
    var overlay = document.getElementById('lt-doom-overlay');
    if (overlay) {
      // Cleanup js-dos instance
      if (doomInstance && typeof doomInstance.exit === 'function') {
        try { doomInstance.exit(); } catch (_) {}
        doomInstance = null;
      }
      overlay.remove();
      document.body.style.overflow = '';
      document.removeEventListener('keydown', escHandler);
    }
  }
})();
