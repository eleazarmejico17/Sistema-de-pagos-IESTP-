(function () {
  const state = {
    locked: false,
    scrollY: 0,
    modal: null,
  };

  function lockScroll() {
    if (state.locked) return;
    state.scrollY = window.scrollY || window.pageYOffset || 0;

    document.body.dataset.scrollLockY = String(state.scrollY);
    document.body.style.position = 'fixed';
    document.body.style.top = `-${state.scrollY}px`;
    document.body.style.left = '0';
    document.body.style.right = '0';
    document.body.style.width = '100%';
    document.body.style.overflow = 'hidden';

    // También bloquear scroll en html (para navegadores WebKit)
    document.documentElement.style.overflow = 'hidden';

    state.locked = true;
  }

  function unlockScroll() {
    if (!state.locked) return;

    const y = parseInt(document.body.dataset.scrollLockY || '0', 10) || 0;

    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.left = '';
    document.body.style.right = '';
    document.body.style.width = '';
    document.body.style.overflow = '';
    delete document.body.dataset.scrollLockY;

    // Restaurar scroll en html también
    document.documentElement.style.overflow = '';

    window.scrollTo(0, y);
    state.locked = false;
  }

  function open(modalEl) {
    if (!modalEl) return;
    state.modal = modalEl;
    modalEl.classList.remove('hidden');
    lockScroll();
  }

  function close(modalEl) {
    const el = modalEl || state.modal;
    if (!el) return;
    el.classList.add('hidden');
    unlockScroll();
  }

  function bind(modalElOrSelector) {
    const modal =
      typeof modalElOrSelector === 'string'
        ? document.querySelector(modalElOrSelector)
        : modalElOrSelector;

    if (!modal) return;

    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        close(modal);
      }
    });

    modal.querySelectorAll('[data-modal-close]').forEach((btn) => {
      btn.addEventListener('click', () => close(modal));
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (!state.modal) return;
    if (state.modal.classList.contains('hidden')) return;
    close(state.modal);
  });

  window.RegistrarPagoModal = {
    lockScroll,
    unlockScroll,
    open,
    close,
    bind,
  };

  document.addEventListener('DOMContentLoaded', () => {
    bind('#modalPago');
  });
})();
