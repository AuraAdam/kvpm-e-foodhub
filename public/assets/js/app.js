/*
 * KVPM E-FoodHub — interface behaviour.
 * Progressive enhancement only: every form and link works without JavaScript.
 * Business rules (prices, stock, permissions) are always enforced on the server.
 */
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  /* ---------------------------------------------------------- Mobile drawer */
  var drawer = $('#drawer');
  var lastFocus = null;
  function openDrawer() {
    if (!drawer) return;
    lastFocus = document.activeElement;
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    var first = $('a, button', drawer.querySelector('.drawer__panel'));
    if (first) first.focus();
    document.body.style.overflow = 'hidden';
  }
  function closeDrawer() {
    if (!drawer) return;
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (lastFocus) lastFocus.focus();
  }
  $$('[data-drawer-open]').forEach(function (b) { b.addEventListener('click', openDrawer); });
  $$('[data-drawer-close]').forEach(function (b) { b.addEventListener('click', closeDrawer); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && drawer && drawer.classList.contains('is-open')) closeDrawer();
  });

  /* Close the account menu when clicking elsewhere */
  document.addEventListener('click', function (e) {
    $$('details.menu[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
  });

  /* ---------------------------------------------------------- Flash dismiss */
  $$('[data-dismiss]').forEach(function (b) {
    b.addEventListener('click', function () { var a = b.closest('.alert'); if (a) a.remove(); });
  });

  /* ---------------------------------------------------------- Confirmation dialog
   * <form data-confirm="Message" data-confirm-ok="Delete" data-confirm-tone="danger">
   */
  var dialog = $('#confirm-dialog');
  var pendingForm = null;
  if (dialog && typeof dialog.showModal === 'function') {
    $$('form[data-confirm]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (form.dataset.confirmed === '1') return;
        e.preventDefault();
        pendingForm = form;
        $('#confirm-title', dialog).textContent = form.dataset.confirmTitle || 'Sahkan tindakan';
        $('#confirm-message', dialog).textContent = form.dataset.confirm;
        var ok = $('#confirm-ok', dialog);
        ok.textContent = form.dataset.confirmOk || 'Ya, teruskan';
        ok.className = 'btn ' + (form.dataset.confirmTone === 'danger' ? 'btn--danger-solid' : 'btn--primary');
        dialog.showModal();
        ok.focus();
      });
    });
    $('#confirm-ok', dialog).addEventListener('click', function () {
      dialog.close();
      if (pendingForm) {
        pendingForm.dataset.confirmed = '1';
        if (typeof pendingForm.requestSubmit === 'function') pendingForm.requestSubmit(); else pendingForm.submit();
      }
    });
    $('#confirm-cancel', dialog).addEventListener('click', function () { dialog.close(); pendingForm = null; });
  } else {
    // Old browsers: fall back to the native confirm box.
    $$('form[data-confirm]').forEach(function (form) {
      form.addEventListener('submit', function (e) { if (!window.confirm(form.dataset.confirm)) e.preventDefault(); });
    });
  }

  /* ---------------------------------------------------------- Prevent double submission */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (e.defaultPrevented || form.hasAttribute('data-no-lock')) return;
    if (form.dataset.submitting === '1') { e.preventDefault(); return; }
    form.dataset.submitting = '1';
    var btn = e.submitter || form.querySelector('[type=submit]');
    if (btn && btn.tagName === 'BUTTON') {
      // keep the clicked button's name/value, which disabling would drop
      if (btn.name) {
        var hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = btn.name; hidden.value = btn.value;
        form.appendChild(hidden);
      }
      btn.classList.add('is-loading');
      btn.setAttribute('aria-busy', 'true');
      setTimeout(function () { btn.disabled = true; }, 0);
    }
    // Re-enable if the user navigates back to this page.
    window.addEventListener('pageshow', function () { form.dataset.submitting = ''; if (btn) { btn.disabled = false; btn.classList.remove('is-loading'); } }, { once: true });
  });

  /* ---------------------------------------------------------- Quantity steppers */
  $$('.qty').forEach(function (q) {
    var input = $('input', q);
    var step = function (d) {
      var min = parseInt(input.min || '0', 10), max = parseInt(input.max || '999', 10);
      var v = (parseInt(input.value, 10) || 0) + d;
      input.value = Math.max(min, Math.min(max, v));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    $$('[data-step]', q).forEach(function (b) {
      b.addEventListener('click', function () { step(parseInt(b.dataset.step, 10)); });
    });
  });
  // Cart: auto-submit the quantity form when the value changes
  $$('form[data-autosubmit]').forEach(function (form) {
    var timer;
    $$('input', form).forEach(function (i) {
      i.addEventListener('change', function () {
        clearTimeout(timer);
        timer = setTimeout(function () { if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit(); }, 450);
      });
    });
  });

  /* ---------------------------------------------------------- Toast */
  var toastTimer;
  function toast(message, isError, link) {
    var t = $('#toast');
    if (!t) return;
    t.innerHTML = '';
    var span = document.createElement('span');
    span.textContent = message;
    t.appendChild(span);
    if (link) {
      var a = document.createElement('a');
      a.href = link.href; a.textContent = link.text;
      t.appendChild(a);
    }
    t.className = 'toast is-visible' + (isError ? ' toast--error' : '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.className = 'toast'; }, 3200);
  }

  /* ---------------------------------------------------------- Add to cart without leaving the page */
  $$('form[data-add-to-cart]').forEach(function (form) {
    form.setAttribute('data-no-lock', '');
    form.addEventListener('submit', function (e) {
      if (!window.fetch) return;
      e.preventDefault();
      var btn = form.querySelector('button');
      if (btn.disabled) return;
      btn.disabled = true;
      fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      }).then(function (r) {
        if (r.redirected || (r.headers.get('content-type') || '').indexOf('json') === -1) {
          window.location.href = r.url; // e.g. sent to the login page
          return null;
        }
        return r.json();
      }).then(function (data) {
        if (!data) return;
        if (data.ok) {
          btn.classList.add('is-done');
          setTimeout(function () { btn.classList.remove('is-done'); }, 1200);
          var badge = $('#cart-count');
          if (badge) { badge.textContent = data.cart_count; badge.hidden = data.cart_count < 1; }
          toast(data.message, false, { href: document.body.dataset.cartUrl, text: 'Lihat troli' });
        } else {
          toast(data.message, true);
        }
      }).catch(function () {
        toast('Tidak dapat menyambung. Cuba lagi.', true);
      }).then(function () { btn.disabled = false; });
    });
  });

  /* ---------------------------------------------------------- Product gallery */
  var mainImg = $('#gallery-main');
  $$('[data-gallery-thumb]').forEach(function (b) {
    b.addEventListener('click', function () {
      mainImg.src = b.dataset.full;
      $$('[data-gallery-thumb]').forEach(function (x) { x.classList.remove('is-active'); x.setAttribute('aria-pressed', 'false'); });
      b.classList.add('is-active');
      b.setAttribute('aria-pressed', 'true');
    });
  });

  /* ---------------------------------------------------------- Image loading shimmer */
  $$('.img-wrap img').forEach(function (img) {
    var done = function () { img.parentElement.classList.add('is-loaded'); };
    if (img.complete) done(); else { img.addEventListener('load', done); img.addEventListener('error', done); }
  });

  /* ---------------------------------------------------------- Password show/hide */
  $$('[data-toggle-password]').forEach(function (b) {
    b.addEventListener('click', function () {
      var input = b.parentElement.querySelector('input');
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      b.setAttribute('aria-label', show ? 'Sembunyikan kata laluan' : 'Tunjuk kata laluan');
      b.setAttribute('aria-pressed', show ? 'true' : 'false');
    });
  });

  /* ---------------------------------------------------------- Upload previews (client-side only; the server re-validates) */
  $$('input[type=file][data-preview]').forEach(function (input) {
    var target = document.getElementById(input.dataset.preview);
    var max = parseInt(input.dataset.maxBytes || '0', 10);
    input.addEventListener('change', function () {
      if (!target) return;
      target.innerHTML = '';
      var tooBig = [];
      Array.prototype.forEach.call(input.files, function (file) {
        if (max && file.size > max) { tooBig.push(file.name); return; }
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return;
        var img = document.createElement('img');
        img.alt = 'Pratonton ' + file.name;
        img.src = URL.createObjectURL(file);
        target.appendChild(img);
      });
      var msg = input.parentElement.querySelector('.field-error[data-client]');
      if (msg) msg.remove();
      if (tooBig.length) {
        var err = document.createElement('p');
        err.className = 'field-error'; err.setAttribute('data-client', '');
        err.textContent = 'Terlalu besar: ' + tooBig.join(', ');
        input.parentElement.appendChild(err);
      }
    });
  });

  /* ---------------------------------------------------------- Catalogue filters toggle (mobile) */
  var ft = $('[data-filters-toggle]');
  if (ft) {
    ft.addEventListener('click', function () {
      var panel = document.getElementById(ft.getAttribute('aria-controls'));
      var open = panel.classList.toggle('is-open');
      ft.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
  /* Print buttons (shown only when JS can run them) */
  $$('[data-print]').forEach(function (b) {
    b.hidden = false;
    b.addEventListener('click', function () { window.print(); });
  });

  /* Sort select submits its form */
  $$('select[data-autosubmit]').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
  });
})();
