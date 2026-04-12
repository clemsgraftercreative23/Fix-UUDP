/**
 * Travel reimbursement: tab switch without full page reload + sessionStorage draft per tab.
 * Depends on jQuery (layout) and maskMoney (re-init on swapped pane).
 */
(function ($) {
  'use strict';

  function storageKey(mainId, travelId) {
    return 'rtTravelPaneDraft:v1:' + mainId + ':' + travelId;
  }

  /** Allow data-travel-id="0" (halaman new item); hindari cek truthy saja. */
  function hasReimbursementPaneIds(mainId, travelId) {
    if (mainId === undefined || mainId === null || mainId === '') return false;
    if (travelId === undefined || travelId === null || travelId === '') return false;
    return true;
  }

  /**
   * Baca id dari atribut DOM, bukan jQuery .data() — .data() di-cache dan tidak
   * ikut berubah setelah .attr('data-travel-id', ...), sehingga tab kedua/ketiga
   * bisa dianggap "sudah aktif" dan klik diabaikan.
   */
  function readMainIdAttr($pane) {
    const v = $pane.attr('data-main-id');
    return v === undefined ? '' : String(v);
  }

  function readTravelIdAttr($pane) {
    const v = $pane.attr('data-travel-id');
    if (v === undefined || v === null) return '';
    return String(v);
  }

  function collectPaneFields($pane) {
    const data = {};
    const counts = {};
    $pane.find('input, select, textarea').each(function () {
      const el = this;
      if (!el.name) return;
      if (el.type === 'file' || el.type === 'submit' || el.type === 'button') return;
      const n = el.name;
      counts[n] = (counts[n] || 0) + 1;
      const key = n + '\u0000' + counts[n];
      if (el.type === 'checkbox') {
        data[key] = el.checked ? '1' : '';
      } else {
        data[key] = el.value;
      }
    });
    return data;
  }

  function applyPaneFields($pane, data) {
    if (!data || typeof data !== 'object') return;
    const counts = {};
    $pane.find('input, select, textarea').each(function () {
      const el = this;
      if (!el.name) return;
      if (el.type === 'file' || el.type === 'submit' || el.type === 'button') return;
      const n = el.name;
      counts[n] = (counts[n] || 0) + 1;
      const key = n + '\u0000' + counts[n];
      if (data[key] === undefined) return;
      if (el.type === 'checkbox') {
        el.checked = data[key] === '1' || data[key] === true;
      } else {
        $(el).val(String(data[key]));
      }
    });
  }

  function persistCurrentPane($pane) {
    const mainId = readMainIdAttr($pane);
    const travelId = readTravelIdAttr($pane);
    if (!hasReimbursementPaneIds(mainId, travelId)) return;
    try {
      sessionStorage.setItem(storageKey(mainId, travelId), JSON.stringify(collectPaneFields($pane)));
    } catch (e) { /* quota / private mode */ }
  }

  function restorePane($pane) {
    const mainId = readMainIdAttr($pane);
    const travelId = readTravelIdAttr($pane);
    if (!hasReimbursementPaneIds(mainId, travelId)) return;
    try {
      const raw = sessionStorage.getItem(storageKey(mainId, travelId));
      if (!raw) return;
      applyPaneFields($pane, JSON.parse(raw));
    } catch (e) { /* ignore */ }
  }

  function updateFormTravelAction($form, newTravelId) {
    let action = $form.attr('action') || '';
    if (!action) return;
    action = action.split('?')[0];
    $form.attr('action', action.replace(/\/(\d+)(\/?)$/, '/' + newTravelId + '$2'));
  }

  let saveTimer;
  function schedulePersist() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () {
      const $p = $('#rt-travel-item-pane');
      if ($p.length) persistCurrentPane($p);
    }, 400);
  }

  function partialUrl(url) {
    const base = url.split('#')[0];
    const sep = base.indexOf('?') >= 0 ? '&' : '?';
    return base + sep + 'rt_partial=1';
  }

  function initMaskMoney($pane) {
    if (!$pane || !$pane.length || !$.fn.maskMoney) return;
    try {
      $pane.find('.currency').maskMoney('destroy');
    } catch (e) { /* not initialized */ }
    $pane.find('.currency').maskMoney({
      thousands: '.',
      decimal: ',',
      allowZero: true,
      allowNegative: true,
      precision: 0
    });
    $pane.find('.currency').maskMoney('mask');
  }

  window.rtInitTravelItemPane = function ($pane) {
    if (!$pane || !$pane.length) return;
    initMaskMoney($pane);
  };

  function afterPaneHydrated($pane) {
    if (typeof window.rtCalculateTimeDifference === 'function') {
      window.rtCalculateTimeDifference();
    }
    if (typeof window.rtTotalNominalTravel === 'function') {
      window.rtTotalNominalTravel();
    }
  }

  $(function () {
    const $pane0 = $('#rt-travel-item-pane');
    if (!$pane0.length) return;

    restorePane($pane0);
    window.rtInitTravelItemPane($pane0);
    afterPaneHydrated($pane0);

    $(document).on('input change', '#rt-travel-item-pane input, #rt-travel-item-pane select, #rt-travel-item-pane textarea', function (e) {
      const t = e.target;
      if (t.type === 'file') return;
      schedulePersist();
    });

    $(document).on('click', '#rt-travel-item-pane a.travel-item-link[data-rt-tab="1"]', function (e) {
      if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) return;
      e.preventDefault();
      const url = this.getAttribute('href');
      if (!url) return;
      const newTravelId = String($(this).attr('data-travel-id') || '');
      const $pane = $('#rt-travel-item-pane');
      const currentId = readTravelIdAttr($pane);

      persistCurrentPane($pane);

      if (newTravelId === currentId) return;

      const $form = $pane.closest('form');
      $pane.addClass('rt-pane-loading');

      $.ajax({
        url: partialUrl(url),
        type: 'GET',
        headers: { 'X-RT-Partial': '1', 'X-Requested-With': 'XMLHttpRequest' },
        success: function (html) {
          $pane.html(html);
          $pane.attr('data-travel-id', newTravelId);
          if ($form.length) {
            updateFormTravelAction($form, newTravelId);
          }
          $pane.find('.travel-item-link').removeClass('active');
          $pane.find('.travel-item-link[data-travel-id="' + newTravelId + '"]').addClass('active');
          restorePane($pane);
          window.rtInitTravelItemPane($pane);
          afterPaneHydrated($pane);
          if (window.history && window.history.pushState) {
            window.history.pushState({ rtTravelTab: true }, '', url.split('?')[0]);
          }
          $(document).trigger('rtTravelTabLoaded', [$pane, newTravelId]);
        },
        error: function () {
          window.location.href = url.split('?')[0];
        },
        complete: function () {
          $pane.removeClass('rt-pane-loading');
        }
      });
    });

    window.addEventListener('popstate', function () {
      if (window.location.pathname.indexOf('reimbursement-travel/add-item') !== -1) {
        window.location.reload();
      }
    });

    $('form').on('submit', function () {
      const $p = $('#rt-travel-item-pane');
      if (!$p.length) return;
      try {
        const mainId = readMainIdAttr($p);
        const travelId = readTravelIdAttr($p);
        if (hasReimbursementPaneIds(mainId, travelId)) {
          sessionStorage.removeItem(storageKey(mainId, travelId));
        }
      } catch (err) { /* ignore */ }
    });
  });
})(jQuery);
