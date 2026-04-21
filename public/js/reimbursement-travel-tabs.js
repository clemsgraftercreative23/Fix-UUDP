/**
 * Travel reimbursement: AJAX tabs + form draft (localStorage v2 + legacy v1).
 * Tab bar: localStorage mirrors DOM + drafts; full re-render only after AJAX tab partial — not on initial load (keeps server tabs) nor on debounced draft save.
 * Depends on jQuery, maskMoney; optional window.rtTravelAppendDetailRow from Blade.
 */
(function ($) {
  'use strict';

  var STORAGE_V1_PREFIX = 'rtTravelPaneDraft:v1:';
  var STORAGE_V2_PREFIX = 'rtTravelForm:v2:';
  var LEGACY_TABBAR_REGISTRY_PREFIX = 'rtTravelTabBar:v1:';
  var ITEMS_STATE_PREFIX = 'rtTravelItemsState:v2:';
  /** localStorage travel segment for add-new-item pane (data-travel-id="0"); not a real DB id. */
  var NEW_ITEM_DRAFT_KEY = 'new';

  function storageKeyV1(mainId, travelId) {
    return STORAGE_V1_PREFIX + mainId + ':' + travelId;
  }

  function storageKeyV2(mainId, travelId) {
    return STORAGE_V2_PREFIX + mainId + ':' + travelId;
  }

  function isValidTravelTabId(tid) {
    if (tid === undefined || tid === null || tid === '') return false;
    const n = parseInt(String(tid), 10);
    return String(tid) === String(n) && n > 0;
  }

  /** Tab "item baru" (belum simpan DB) pada halaman add-new-item — id sintetis di state/localStorage. */
  function isDraftNewTravelItemId(tid) {
    return String(tid || '') === NEW_ITEM_DRAFT_KEY;
  }

  /** Windows / beberapa browser mengosongkan `File.type`; tetap anggap gambar dari ekstensi. */
  function inferImageMimeFromFileName(name) {
    const ext = String(name || '').split('.').pop().toLowerCase();
    const map = {
      png: 'image/png',
      jpg: 'image/jpeg',
      jpeg: 'image/jpeg',
      gif: 'image/gif',
      webp: 'image/webp',
      bmp: 'image/bmp',
      svg: 'image/svg+xml'
    };
    return map[ext] || '';
  }

  function isLikelyImageFile(file) {
    if (!file) return false;
    const t = (file.type || '').trim();
    if (t && /^image\//i.test(t)) return true;
    return !!inferImageMimeFromFileName(file.name);
  }

  /**
   * Baca file ke data URL untuk draft; file terlalu besar membuat attr/localStorage bermasalah
   * dan bisa memicu error upload di beberapa browser.
   */
  var RT_MAX_EVIDENCE_READ_BYTES = 6 * 1024 * 1024;

  function readMainIdAttr($pane) {
    const v = $pane.attr('data-main-id');
    return v === undefined ? '' : String(v);
  }

  function readTravelIdAttr($pane) {
    const v = $pane.attr('data-travel-id');
    if (v === undefined || v === null) return '';
    return String(v);
  }

  /** v2 draft key segment: real travel id, or "new" for add-new-item autosave. */
  function resolveDraftTravelId($pane) {
    if (!$pane || !$pane.length) return '';
    const tid = readTravelIdAttr($pane);
    if (isValidTravelTabId(tid)) return tid;
    if ($pane.attr('data-rt-new-item') === '1' && readMainIdAttr($pane)) return NEW_ITEM_DRAFT_KEY;
    return '';
  }

  /** Legacy flat map (sessionStorage v1). */
  function collectPaneFieldsFlat($pane) {
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

  function applyPaneFieldsFlat($pane, data) {
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

  function collectHeader($pane) {
    const header = {};
    const headerCounts = {};
    $pane.find('input, select, textarea').each(function () {
      const el = this;
      if (!el.name) return;
      if (el.type === 'file' || el.type === 'submit' || el.type === 'button') return;
      if ($(el).closest('tr.fieldGroupDetail').length) return;
      const n = el.name;
      headerCounts[n] = (headerCounts[n] || 0) + 1;
      const key = n + '\u0000' + headerCounts[n];
      header[key] = el.type === 'checkbox' ? (el.checked ? '1' : '') : el.value;
    });
    return header;
  }

  function applyHeader($pane, header) {
    if (!header || typeof header !== 'object') return;
    const counts = {};
    $pane.find('input, select, textarea').each(function () {
      const el = this;
      if (!el.name) return;
      if (el.type === 'file' || el.type === 'submit' || el.type === 'button') return;
      if ($(el).closest('tr.fieldGroupDetail').length) return;
      const n = el.name;
      counts[n] = (counts[n] || 0) + 1;
      const key = n + '\u0000' + counts[n];
      if (header[key] === undefined) return;
      if (el.type === 'checkbox') {
        el.checked = header[key] === '1' || header[key] === true;
      } else {
        $(el).val(String(header[key]));
      }
    });
  }

  function collectRows($pane) {
    const rows = [];
    $pane.find('tbody tr.fieldGroupDetail').each(function () {
      const $tr = $(this);
      let tempFile = null;
      try {
        const rawTemp = $tr.attr('data-rt-temp-file') || '';
        if (rawTemp) {
          const parsed = JSON.parse(rawTemp);
          if (parsed && typeof parsed === 'object' && parsed.data_url) {
            tempFile = parsed;
          }
        }
      } catch (e) { /* ignore */ }
      rows.push({
        id_detail: ($tr.find('input[name="id_detail[]"]').val() || '').trim(),
        cost_type_id: ($tr.find('select[name="cost_type_id[]"]').val() || '').trim(),
        destination: ($tr.find('input[name="destination[]"]').val() || '').trim(),
        currency: ($tr.find('select[name="currency[]"]').val() || '').trim(),
        amount: ($tr.find('input[name="amount[]"]').val() || '').trim(),
        idr_rate: ($tr.find('input[name="idr_rate[]"]').val() || '').trim(),
        tax: ($tr.find('input[name="tax[]"]').val() || '').trim(),
        payment_type: ($tr.find('select[name="payment_type[]"]').val() || '').trim(),
        temp_file: tempFile
      });
    });
    return rows;
  }

  function applyRow($tr, r) {
    if (!$tr.length || !r) return;
    $tr.find('input[name="id_detail[]"]').val(r.id_detail || '');
    $tr.find('select[name="cost_type_id[]"]').val(r.cost_type_id || '');
    $tr.find('input[name="destination[]"]').val(r.destination || '');
    $tr.find('select[name="currency[]"]').val(r.currency || '');
    $tr.find('input[name="amount[]"]').val(r.amount || '');
    $tr.find('input[name="idr_rate[]"]').val(r.idr_rate || '');
    $tr.find('input[name="tax[]"]').val(r.tax || '');
    $tr.find('select[name="payment_type[]"]').val(r.payment_type || '');
    if (r.temp_file && r.temp_file.data_url) {
      try {
        $tr.attr('data-rt-temp-file', JSON.stringify(r.temp_file));
      } catch (e) { /* ignore */ }
    } else {
      $tr.removeAttr('data-rt-temp-file');
    }
  }

  function dataUrlToFile(dataUrl, fileName, mimeType) {
    try {
      if (!dataUrl || dataUrl.indexOf('data:') !== 0) return null;
      const arr = dataUrl.split(',');
      if (arr.length < 2) return null;
      const mime = mimeType || ((arr[0].match(/:(.*?);/) || [])[1] || 'application/octet-stream');
      const bstr = atob(arr[1]);
      let n = bstr.length;
      const u8arr = new Uint8Array(n);
      while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
      }
      const fname = fileName || ('draft_' + Date.now() + '.bin');
      return new File([u8arr], fname, { type: mime });
    } catch (e) {
      return null;
    }
  }

  function restoreTempFileForRow($tr, rowState) {
    if (!$tr || !$tr.length || !rowState || !rowState.temp_file || !rowState.temp_file.data_url) return;

    const tf = rowState.temp_file;
    const mimeHint = (tf.file_type || '').trim() || inferImageMimeFromFileName(tf.file_name) || 'application/octet-stream';
    const file = dataUrlToFile(tf.data_url, tf.file_name || 'draft_upload', mimeHint);
    if (!file) return;

    const dataTransfer = new DataTransfer();
    try {
      dataTransfer.items.add(file);
    } catch (eAdd) {
      return;
    }

    const targetInput = (tf.source === 'camera')
      ? $tr.find('input.camera-input[type="file"]').first()
      : $tr.find('input.file-input[type="file"]').first();

    if (targetInput.length) {
      try {
        targetInput[0].files = dataTransfer.files;
      } catch (eFiles) {
        /* Firefox/dll: kadang menolak FileList sintetis — preview dari data_url tetap dipasang di bawah. */
      }
    }

    const $preview = $tr.find('[id^="preview_"]').first();
    if ($preview.length) {
      $preview.empty();
      const previewMime = (tf.file_type || '').trim() || inferImageMimeFromFileName(tf.file_name);
      if (previewMime.indexOf('image/') === 0) {
        const $img = $('<img>')
          .attr('src', tf.data_url)
          .attr('data-preview-src', tf.data_url)
          .addClass('preview-thumbnail')
          .css({
            maxWidth: '75px',
            maxHeight: '75px',
            border: '2px solid #28a745',
            borderRadius: '5px',
            marginTop: '5px',
            cursor: 'pointer'
          });
        $preview.append($img);
      }
    }
  }

  function reconcileDetailRows($pane, needCount) {
    var maxG = typeof window.rtTravelDetailMaxGroup === 'number' ? window.rtTravelDetailMaxGroup : 10;
    var have = $pane.find('tbody tr.fieldGroupDetail').length;
    var guard = 0;
    while (have < needCount && have < maxG && guard < 20) {
      guard++;
      if (typeof window.rtTravelAppendDetailRow !== 'function') break;
      if (!window.rtTravelAppendDetailRow({ silent: true })) break;
      have = $pane.find('tbody tr.fieldGroupDetail').length;
    }
  }

  function collectStateV2($pane) {
    return {
      v: 2,
      savedAt: Date.now(),
      header: collectHeader($pane),
      rows: collectRows($pane)
    };
  }

  function persistCurrentPane($pane) {
    const mainId = readMainIdAttr($pane);
    const draftTid = resolveDraftTravelId($pane);
    if (mainId && draftTid) {
      try {
        const state = collectStateV2($pane);
        localStorage.setItem(storageKeyV2(mainId, draftTid), JSON.stringify(state));
        try {
          sessionStorage.removeItem(storageKeyV1(mainId, draftTid));
        } catch (e2) { /* ignore */ }
      } catch (e) { /* quota */ }
    }
    // Do not call renderTravelTabsFromState here: debounced persist would repeatedly wipe/rebuild the tab bar
    // and empty merge (href/regex edge cases) could remove all tabs before save. Labels still update from drafts:
    if (mainId && $pane && $pane.length) {
      refreshTravelTabLabelsFromV2Drafts($pane, mainId);
    }
  }

  /**
   * Mirror Transaction Date into the tab label immediately (no save).
   * Prefer matching tab by data-travel-id on the pane; fallback to .active travel tab or "New Item".
   */
  function syncActiveTravelTabDateFromInput($pane) {
    if (!$pane || !$pane.length) return;
    const raw = ($pane.find('input[name="date"]').first().val() || '').trim();
    const tid = readTravelIdAttr($pane);
    if (raw && tid !== '') {
      const tabId = tid === '0' ? NEW_ITEM_DRAFT_KEY : tid;
      const $byTravel = $pane.find('.travel-item-link[data-travel-id="' + tabId + '"] span.item-1').first();
      if ($byTravel.length) {
        $byTravel.text(raw);
        return;
      }
    }
    const $activeItem = $pane.find('.travel-item-link.active span.item-1').first();
    if ($activeItem.length && raw) {
      $activeItem.text(raw);
      return;
    }
    const $newTab = $pane.find('a.nav-link.active span.item-new').first();
    if ($newTab.length) {
      $newTab.text(raw || 'New Item');
    }
  }

  function restorePaneFull($pane) {
    const mainId = readMainIdAttr($pane);
    const draftTid = resolveDraftTravelId($pane);
    if (!mainId || !draftTid) {
      syncActiveTravelTabDateFromInput($pane);
      return;
    }

    try {
      const rawV2 = localStorage.getItem(storageKeyV2(mainId, draftTid));
      if (rawV2) {
        const state = JSON.parse(rawV2);
        if (state && state.v === 2 && Array.isArray(state.rows)) {
          reconcileDetailRows($pane, state.rows.length);
          if (state.header) applyHeader($pane, state.header);
          const $rows = $pane.find('tbody tr.fieldGroupDetail');
          state.rows.forEach(function (r, idx) {
            const $row = $rows.eq(idx);
            applyRow($row, r);
            restoreTempFileForRow($row, r);
          });
          syncActiveTravelTabDateFromInput($pane);
          return;
        }
      }
    } catch (e) { /* fall through */ }

    try {
      const rawV1 = sessionStorage.getItem(storageKeyV1(mainId, draftTid));
      if (rawV1) {
        applyPaneFieldsFlat($pane, JSON.parse(rawV1));
      }
    } catch (e2) { /* ignore */ }
    syncActiveTravelTabDateFromInput($pane);
  }

  function clearStorageForPane($pane) {
    const mainId = readMainIdAttr($pane);
    const draftTid = resolveDraftTravelId($pane);
    if (!mainId || !draftTid) return;
    try {
      localStorage.removeItem(storageKeyV2(mainId, draftTid));
      sessionStorage.removeItem(storageKeyV1(mainId, draftTid));
    } catch (e) { /* ignore */ }
  }

  function clearStorageForNewItem(mainId) {
    if (!mainId) return;
    try {
      localStorage.removeItem(storageKeyV2(mainId, NEW_ITEM_DRAFT_KEY));
      sessionStorage.removeItem(storageKeyV1(mainId, NEW_ITEM_DRAFT_KEY));
    } catch (e) { /* ignore */ }
    try {
      const items = readTravelItemsState(mainId).filter(function (it) {
        return it && !isDraftNewTravelItemId(it.id);
      });
      writeTravelItemsState(mainId, items);
    } catch (e2) { /* ignore */ }
  }

  function updateFormTravelAction($form, newTravelId) {
    let action = $form.attr('action') || '';
    if (!action) return;
    action = action.split('?')[0];
    $form.attr('action', action.replace(/\/(\d+)(\/?)$/, '/' + newTravelId + '$2'));
  }

  var saveTimer;
  function schedulePersist() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () {
      const $p = $('#rt-travel-item-pane');
      if ($p.length) persistCurrentPane($p);
    }, 350);
  }

  function partialUrl(url) {
    const base = url.split('#')[0];
    const sep = base.indexOf('?') >= 0 ? '&' : '?';
    return base + sep + 'rt_partial=1';
  }

  function escapeHtmlRt(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function transactionDateFromV2Header(header) {
    if (!header || typeof header !== 'object') return '';
    const k = 'date\u00001';
    if (header[k] !== undefined && header[k] !== '') return String(header[k]);
    return '';
  }

  /** Update visible tab dates from v2 localStorage without rebuilding the tab strip (safe before save). */
  function syncTravelItemsStateFromDom($pane, mainId) {
    if (!mainId || !$pane || !$pane.length) return;
    try {
      writeTravelItemsState(mainId, buildMergedTravelTabItems(mainId, $pane));
    } catch (e) { /* ignore */ }
  }

  function refreshTravelTabLabelsFromV2Drafts($pane, mainId) {
    if (!mainId || !$pane || !$pane.length) return;
    const prefix = STORAGE_V2_PREFIX + mainId + ':';
    $pane.find('.travel-item-link[data-travel-id]').each(function () {
      const tid = String($(this).attr('data-travel-id') || '');
      if (!isValidTravelTabId(tid) && !isDraftNewTravelItemId(tid)) return;
      try {
        const raw = localStorage.getItem(prefix + tid);
        if (!raw) return;
        const st = JSON.parse(raw);
        const d = transactionDateFromV2Header(st.header);
        if (d) $(this).find('span.item-1').first().text(d);
      } catch (e) { /* ignore */ }
    });
  }

  /** Pisahkan `#fragment` agar segmen path terakhir bisa diganti dengan aman. */
  function splitUrlBaseAndHash(href) {
    const s = String(href || '');
    const i = s.indexOf('#');
    if (i < 0) {
      return { base: s, hash: '' };
    }
    return { base: s.slice(0, i), hash: s.slice(i) };
  }

  /**
   * Ganti segmen angka terakhir di path (mis. travel id pada add-item/.../TRAVEL).
   * Normalisasi trailing slash + hash; callback replace menghindari ambiguitas `$2` di String.replace.
   */
  function replaceLastPathId(href, newId) {
    const parts = splitUrlBaseAndHash(href);
    let base = parts.base.replace(/\/+$/, '');
    const replaced = base.replace(/\/(\d+)(\?.*)?$/, function (_m, _digits, query) {
      return '/' + newId + (query || '');
    });
    return replaced + parts.hash;
  }

  /**
   * URL hapus tab: .../delete-item/{id_main}/{id_travel} — selalu ganti id_travel (bukan hanya "digit terakhir di string").
   * Mencegah semua tab memakai href yang sama bila replaceLastPathId tidak match (mis. trailing slash / hash).
   */
  function replaceTravelDeleteTabHref(href, newTravelId) {
    const parts = splitUrlBaseAndHash(href);
    const base = parts.base.replace(/\/+$/, '');
    const m = base.match(/^(.*\/delete-item\/\d+)\/(\d+)((?:\?.*)?)$/);
    let out;
    if (m) {
      out = m[1] + '/' + newTravelId + (m[3] || '');
    } else {
      out = base.replace(/\/(\d+)(\?.*)?$/, function (_m, _digits, query) {
        return '/' + newTravelId + (query || '');
      });
    }
    return out + parts.hash;
  }

  /**
   * Partial load URL when href cannot be derived from state/DOM (never drop a tab for missing href).
   * Prefer data-rt-href-prefix; else replace last path segment; else rebuild from pathname pattern.
   */
  function travelTabPartialFallbackHref(mainId, travelId, hrefPrefixRaw) {
    const mid = String(mainId || '');
    const tid = String(travelId || '');
    if (!isValidTravelTabId(tid) || !mid) return '';
    const raw = String(hrefPrefixRaw || '').trim();
    if (raw) {
      return raw.replace(/\/?$/, '/') + tid;
    }
    const path = String(window.location.pathname || '').replace(/\/+$/, '');
    const replaced = replaceLastPathId(path, tid);
    if (replaced && replaced !== path) {
      return replaced;
    }
    const m = path.match(/^(.*\/reimbursement-travel\/)(?:add-item(?:-overseas)?)\/\d+\/\d+$/i);
    if (m && m[1]) {
      return m[1] + 'add-item/' + mid + '/' + tid;
    }
    return '';
  }

  function tabItemUrlAttr($el) {
    if (!$el || !$el.length) return '';
    return String($el.attr('data-rt-item-url') || $el.attr('href') || '');
  }

  function buildTravelTabItemUrlFromSample($sampleEl, travelId) {
    if ($sampleEl && $sampleEl.length) {
      return replaceLastPathId(tabItemUrlAttr($sampleEl), travelId);
    }
    return '';
  }

  function itemsStateKey(mainId) {
    return ITEMS_STATE_PREFIX + mainId;
  }

  function readTravelItemsState(mainId) {
    if (!mainId) return [];
    try {
      let raw = localStorage.getItem(itemsStateKey(mainId));
      if (!raw) {
        raw = localStorage.getItem(LEGACY_TABBAR_REGISTRY_PREFIX + mainId);
        if (raw) {
          const old = JSON.parse(raw);
          if (Array.isArray(old)) {
            const items = sortTravelTabItems(
              old
                .map(function (e) {
                  if (!e || !e.id) return null;
                  return { id: String(e.id), date: e.label || String(e.id), href: e.href || '' };
                })
                .filter(function (e) {
                  return e && (isValidTravelTabId(e.id) || isDraftNewTravelItemId(e.id));
                })
            );
            writeTravelItemsState(mainId, items);
            try {
              localStorage.removeItem(LEGACY_TABBAR_REGISTRY_PREFIX + mainId);
            } catch (x) { /* ignore */ }
            return items;
          }
        }
        return [];
      }
      const o = JSON.parse(raw);
      if (o && Array.isArray(o.items)) {
        return sortTravelTabItems(
          o.items.filter(function (e) {
            return e && (isValidTravelTabId(e.id) || isDraftNewTravelItemId(e.id));
          })
        );
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  function writeTravelItemsState(mainId, items) {
    if (!mainId) return;
    try {
      localStorage.setItem(itemsStateKey(mainId), JSON.stringify({ v: 2, items: items }));
    } catch (e) { /* quota */ }
  }

  /** Nilai numerik untuk urut tanggal (utamakan YYYY-MM-DD; fallback Date.parse untuk label lain). */
  function travelTabDateSortKey(dateStr) {
    const s = String(dateStr || '').trim();
    if (!s) return 0;
    const iso = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (iso) {
      const t = Date.UTC(parseInt(iso[1], 10), parseInt(iso[2], 10) - 1, parseInt(iso[3], 10));
      return isNaN(t) ? 0 : t;
    }
    const p = Date.parse(s);
    return isNaN(p) ? 0 : p;
  }

  /** Urut tab: tanggal paling awal → akhir; tie-break by travel id. Tidak memutasi array asal. */
  function sortTravelTabItems(arr) {
    if (!Array.isArray(arr) || !arr.length) {
      return arr || [];
    }
    const copy = arr.slice();
    copy.sort(function (a, b) {
      const ka = travelTabDateSortKey(a && a.date);
      const kb = travelTabDateSortKey(b && b.date);
      if (ka !== kb) return ka - kb;
      const idKey = function (id) {
        const s = String(id);
        if (isDraftNewTravelItemId(s)) return 9007199254740992;
        const n = parseInt(s, 10);
        return isNaN(n) ? 0 : n;
      };
      return idKey(a && a.id) - idKey(b && b.id);
    });
    return copy;
  }

  /** Resolve travel id from tab control; supports data-rt-item-url / legacy href (add-item paths). */
  function travelIdFromTabLink($a) {
    let id = String($a.attr('data-travel-id') || '');
    if (isDraftNewTravelItemId(id)) return NEW_ITEM_DRAFT_KEY;
    if (isValidTravelTabId(id)) return id;
    const href = tabItemUrlAttr($a);
    const m = href.match(/add-item(?:-overseas)?\/(\d+)\/(\d+)(?:\/|$|\?|#)/i);
    if (m && isValidTravelTabId(m[2])) return m[2];
    return '';
  }

  /** ID travel yang benar-benar ada di DOM (isi server / partial AJAX) — dipakai untuk buang tab hantu dari localStorage. */
  function collectDomTravelTabIds($pane) {
    const ids = {};
    if (!$pane || !$pane.length) return ids;
    $pane.find('.travel-item-link').each(function () {
      const $btn = $(this);
      if ($btn.attr('data-rt-tab') !== '1' && !$btn.attr('data-travel-id')) return;
      const tid = travelIdFromTabLink($btn);
      if (isValidTravelTabId(tid)) ids[String(tid)] = true;
    });
    return ids;
  }

  /**
   * Single source of truth for tab list: union of persisted items, DOM tabs, and v2 draft keys (never drop an id).
   * Draft v2 / state lama tidak boleh menambah tab untuk reimbursement_travel yang sudah tidak ada (hindari hapus → ModelNotFound).
   */
  function buildMergedTravelTabItems(mainId, $pane) {
    const idSet = {};
    function rememberId(id) {
      const sid = String(id || '');
      if (isDraftNewTravelItemId(sid)) {
        if ($pane && $pane.length && $pane.attr('data-rt-new-item') === '1') {
          idSet[sid] = true;
        }
        return;
      }
      if (isValidTravelTabId(sid)) idSet[sid] = true;
    }

    const domTravelIds = collectDomTravelTabIds($pane);
    const persisted = readTravelItemsState(mainId);
    if ($pane && $pane.length && $pane.attr('data-rt-new-item') === '1') {
      idSet[NEW_ITEM_DRAFT_KEY] = true;
    }

    persisted.forEach(function (it) {
      if (!it) return;
      if (isDraftNewTravelItemId(it.id)) {
        rememberId(it.id);
        return;
      }
      if (isValidTravelTabId(it.id) && domTravelIds[String(it.id)]) rememberId(it.id);
    });

    if ($pane && $pane.length) {
      $pane.find('.travel-item-link').each(function () {
        const $btn = $(this);
        if ($btn.attr('data-rt-tab') !== '1' && !$btn.attr('data-travel-id')) return;
        rememberId(travelIdFromTabLink($btn));
      });
    }

    const v2Prefix = STORAGE_V2_PREFIX + mainId + ':';
    try {
      for (let i = 0; i < localStorage.length; i++) {
        const k = localStorage.key(i);
        if (!k || k.indexOf(v2Prefix) !== 0) continue;
        const seg = k.slice(v2Prefix.length);
        if (isDraftNewTravelItemId(seg)) {
          rememberId(seg);
          continue;
        }
        if (isValidTravelTabId(seg) && domTravelIds[String(seg)]) rememberId(seg);
      }
    } catch (e2) { /* ignore */ }

    const fromPersist = {};
    persisted.forEach(function (it) {
      if (it && (isValidTravelTabId(it.id) || isDraftNewTravelItemId(it.id))) {
        fromPersist[String(it.id)] = {
          id: String(it.id),
          date: it.date || String(it.id),
          href: it.href || ''
        };
      }
    });

    const items = Object.keys(idSet).map(function (id) {
      const base = fromPersist[id] || { id: id, date: id, href: '' };
      let date = base.date || id;
      let href = base.href || '';

      if ($pane && $pane.length) {
        const $el = $pane.find('.travel-item-link[data-travel-id="' + id + '"]').first();
        if ($el.length) {
          const domDate = $el.find('span.item-1').first().text().trim();
          if (domDate) date = domDate;
          const h = tabItemUrlAttr($el);
          if (h) href = h;
        }
      }

      try {
        const raw = localStorage.getItem(v2Prefix + id);
        if (raw) {
          const st = JSON.parse(raw);
          const dlabel = transactionDateFromV2Header(st.header) || '';
          if (dlabel) date = dlabel;
        }
      } catch (e1) { /* ignore */ }

      return { id: id, date: date || id, href: href };
    });

    return sortTravelTabItems(items);
  }

  /**
   * Rebuild all travel tabs from merged state (UI is a projection of state, not incremental DOM patches).
   */
  function renderTravelTabsFromState($pane, mainId, activeTravelId) {
    if (!mainId || !$pane || !$pane.length) return;
    const $ul = $pane.find('.nav-tabs').first();
    if (!$ul.length) return;

    const domTravelTabCount = $pane.find('.travel-item-link[data-rt-tab="1"], .travel-item-link[data-travel-id]').length;
    const domIds = {};
    $pane.find('.travel-item-link[data-rt-tab="1"], .travel-item-link[data-travel-id]').each(function () {
      const tid = travelIdFromTabLink($(this));
      if (isValidTravelTabId(tid) || isDraftNewTravelItemId(tid)) domIds[tid] = true;
    });

    const items = buildMergedTravelTabItems(mainId, $pane);
    const hasDraftNewTab = items.some(function (it) {
      return it && isDraftNewTravelItemId(it.id);
    });

    if (domTravelTabCount > 0 && items.length === 0) {
      return;
    }

    const $addLi = $pane.find('#action_button_item').closest('li.nav-item').detach();
    const $newItemLi = $pane
      .find('a.nav-link')
      .filter(function () {
        return (this.getAttribute('href') || '') === '#reimburse-form';
      })
      .closest('li.nav-item')
      .detach();
    let delTemplate = '';
    const $delRef = $pane.find('a.tab-close-link').first();
    if ($delRef.length) {
      delTemplate = $delRef.attr('href') || '';
    }
    const showDelete = $addLi.length > 0;

    writeTravelItemsState(mainId, items);

    $ul.find('li.nav-item').remove();

    let sampleHref = '';
    for (let j = 0; j < items.length; j++) {
      if (items[j].href && !isDraftNewTravelItemId(items[j].id)) {
        sampleHref = items[j].href;
        break;
      }
    }

    const hrefPrefixRaw = String($pane.attr('data-rt-href-prefix') || '').trim();
    const hrefPrefix = hrefPrefixRaw ? hrefPrefixRaw.replace(/\/?$/, '/') : '';
    const dataRtNewItemUrl = String($pane.attr('data-rt-new-item-url') || '').trim();

    items.forEach(function (item) {
      const isNewTab = isDraftNewTravelItemId(item.id);
      let href = item.href;
      if (isNewTab) {
        href = dataRtNewItemUrl;
      }
      if (!isNewTab && !href && sampleHref) {
        href = replaceLastPathId(sampleHref, item.id);
      }
      if (!isNewTab && !href && hrefPrefix && isValidTravelTabId(item.id)) {
        href = hrefPrefix + item.id;
      }
      if (!isNewTab && !href && mainId) {
        href = String(window.location.pathname || '').replace(/\/[^/]+$/, '/' + item.id);
      }
      // Jangan drop tab: selalu ada URL partial jika id & main valid (hindari tab hilang saat href kosong di state).
      if (!isNewTab && !href && isValidTravelTabId(item.id) && hrefPrefixRaw) {
        href = String(hrefPrefixRaw).replace(/\/?$/, '/') + item.id;
      }
      if (!isNewTab && !href && isValidTravelTabId(item.id)) {
        href = travelTabPartialFallbackHref(mainId, item.id, hrefPrefixRaw);
      }
      if (!href) return;

      let delHtml = '';
      if (showDelete && isNewTab) {
        delHtml = '<a class="tab-close-link js-rt-remove-new-item" href="#" title="Hapus New Item">x</a>';
      } else if (!isNewTab && showDelete && delTemplate) {
        const dh = replaceTravelDeleteTabHref(delTemplate, item.id);
        delHtml =
          '<a class="tab-close-link" href="' +
          escapeHtmlRt(dh) +
          '" onclick="return confirm(\'Hapus tab ini dan semua datanya?\')">x</a>';
      }
      const label = isNewTab ? (item.date && !isDraftNewTravelItemId(item.date) ? item.date : 'New Item') : item.date || item.id;
      const isActive = isNewTab
        ? String(activeTravelId) === '0' || String(activeTravelId) === NEW_ITEM_DRAFT_KEY
        : String(item.id) === String(activeTravelId);
      const liHtml =
        '<li class="nav-item"><div class="travel-tab">' +
        '<button type="button" class="nav-link travel-item-link' +
        (isActive ? ' active' : '') +
        '" data-rt-item-url="' +
        escapeHtmlRt(href) +
        '" data-rt-tab="1" data-travel-id="' +
        escapeHtmlRt(item.id) +
        '"><span class="item-1">' +
        escapeHtmlRt(label) +
        '</span></button>' +
        delHtml +
        '</div></li>';
      $ul.append(liHtml);
    });
    if ($newItemLi.length && !hasDraftNewTab) {
      const $a = $newItemLi.find('a.nav-link').first();
      const onNewItemPlaceholder = !isValidTravelTabId(activeTravelId);
      if (onNewItemPlaceholder) {
        $ul.find('.travel-item-link[data-rt-tab="1"]').removeClass('active');
        $a.addClass('active');
      } else {
        $a.removeClass('active');
      }
      $ul.append($newItemLi);
    }
    if ($addLi.length) {
      $ul.append($addLi);
    }
  }

  /**
   * Load another travel item via partial HTML; tab bar is re-rendered from merged state after inject.
   * @param {boolean} updateUrl — when true, sync address bar with replaceState (no extra history entries).
   */
  function loadTravelItemTabPartial($pane, url, newTravelId, updateUrl) {
    const $form = $pane.closest('form');
    const mainId = readMainIdAttr($pane);
    $pane.addClass('rt-pane-loading');
    $.ajax({
      url: partialUrl(url),
      type: 'GET',
      cache: false,
      headers: { 'X-RT-Partial': '1', 'X-Requested-With': 'XMLHttpRequest' },
      success: function (html) {
        $pane.html(html);
        $pane.attr('data-travel-id', newTravelId);
        if ($form.length) {
          updateFormTravelAction($form, newTravelId);
        }
        refreshTravelTabLabelsFromV2Drafts($pane, mainId);
        renderTravelTabsFromState($pane, mainId, newTravelId);
        restorePaneFull($pane);
        window.rtInitTravelItemPane($pane);
        afterPaneHydrated($pane);
        if (updateUrl !== false && window.history && window.history.replaceState) {
          window.history.replaceState(window.history.state, '', url.split('?')[0]);
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

  /** Sembunyikan teks "upload file" jika sudah ada preview / file terpilih (termasuk setelah partial AJAX). */
  function rtTravelSyncFileUploadWarning($pane) {
    if (!$pane || !$pane.length) {
      $pane = $('#rt-travel-item-pane');
    }
    if (!$pane || !$pane.length) return;
    var $warn = $pane.find('.warning-upload');
    if (!$warn.length) return;
    var hasPreview =
      $pane.find('[id^="preview_"] img').length > 0 ||
      $pane.find('[id^="preview_"] a').length > 0;
    var hasPending = false;
    $pane.find('input.file-input[type="file"], input.camera-input[type="file"]').each(function () {
      if (this.files && this.files.length) {
        hasPending = true;
        return false;
      }
    });
    if (hasPreview || hasPending) {
      $warn.hide();
    }
  }

  window.rtTravelSyncFileUploadWarning = rtTravelSyncFileUploadWarning;

  function afterPaneHydrated($pane) {
    if (typeof window.rtCalculateTimeDifference === 'function') {
      window.rtCalculateTimeDifference();
    }
    if (typeof window.rtTotalNominalTravel === 'function') {
      window.rtTotalNominalTravel();
    }
    rtTravelSyncFileUploadWarning($pane);
  }

  $(function () {
    const $pane0 = $('#rt-travel-item-pane');
    if (!$pane0.length) return;

    restorePaneFull($pane0);
    const main0 = readMainIdAttr($pane0);
    syncTravelItemsStateFromDom($pane0, main0);
    refreshTravelTabLabelsFromV2Drafts($pane0, main0);
    syncActiveTravelTabDateFromInput($pane0);
    renderTravelTabsFromState($pane0, main0, readTravelIdAttr($pane0));
    window.rtInitTravelItemPane($pane0);
    afterPaneHydrated($pane0);

    $(document).on('input change', '#rt-travel-item-pane input, #rt-travel-item-pane select, #rt-travel-item-pane textarea', function (e) {
      const t = e.target;
      if (t.type === 'file') return;
      schedulePersist();
    });

    $(document).on('change', '#rt-travel-item-pane input.file-input[type="file"], #rt-travel-item-pane input.camera-input[type="file"]', function () {
      const $input = $(this);
      const $row = $input.closest('tr.fieldGroupDetail');
      if (!$row.length) return;

      const file = (this.files && this.files.length) ? this.files[0] : null;
      if (!file) {
        $row.removeAttr('data-rt-temp-file');
        schedulePersist();
        return;
      }

      if (!isLikelyImageFile(file)) {
        const minimal = {
          source: $input.hasClass('camera-input') ? 'camera' : 'file',
          file_name: file.name || 'upload',
          file_type: file.type || 'application/octet-stream',
          data_url: ''
        };
        try {
          $row.attr('data-rt-temp-file', JSON.stringify(minimal));
        } catch (e0) { /* ignore */ }
        schedulePersist();
        return;
      }

      if (typeof file.size === 'number' && file.size > RT_MAX_EVIDENCE_READ_BYTES) {
        $row.removeAttr('data-rt-temp-file');
        schedulePersist();
        return;
      }

      const reader = new FileReader();
      reader.onerror = function () {
        try {
          $row.removeAttr('data-rt-temp-file');
        } catch (eR) { /* ignore */ }
        schedulePersist();
      };
      reader.onload = function (ev) {
        const inferred = (file.type || '').trim() || inferImageMimeFromFileName(file.name) || 'image/jpeg';
        const payload = {
          source: $input.hasClass('camera-input') ? 'camera' : 'file',
          file_name: file.name || 'upload',
          file_type: inferred,
          data_url: ev && ev.target ? (ev.target.result || '') : ''
        };
        try {
          $row.attr('data-rt-temp-file', JSON.stringify(payload));
        } catch (e1) { /* quota / attr too long */ }
        schedulePersist();
      };
      reader.readAsDataURL(file);
    });

    $(document).on('input change', '#rt-travel-item-pane input[name="date"]', function () {
      syncActiveTravelTabDateFromInput($('#rt-travel-item-pane'));
    });

    $(document).on('click', '#rt-travel-item-pane .travel-item-link[data-rt-tab="1"]', function (e) {
      if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) return;
      e.preventDefault();
      const newTravelId = String($(this).attr('data-travel-id') || '');
      const $pane = $('#rt-travel-item-pane');
      const currentId = readTravelIdAttr($pane);

      if (isDraftNewTravelItemId(newTravelId)) {
        persistCurrentPane($pane);
        const back = String($pane.attr('data-rt-new-item-url') || '').trim();
        if (back) {
          window.location.href = back.split('#')[0];
        }
        return;
      }

      const url = this.getAttribute('data-rt-item-url') || this.getAttribute('href');
      if (!url) return;
      if (!isValidTravelTabId(newTravelId)) return;

      persistCurrentPane($pane);

      if (newTravelId === currentId) return;

      loadTravelItemTabPartial($pane, url, newTravelId, true);
    });

    $(document).on('click', '#rt-travel-item-pane .js-rt-remove-new-item', function (e) {
      e.preventDefault();
      const $pane = $('#rt-travel-item-pane');
      if (!$pane.length) return;

      const mainId = readMainIdAttr($pane);
      clearStorageForNewItem(mainId);

      const back = String($pane.attr('data-rt-new-item-url') || '').trim();
      const isOnNewPane = $pane.attr('data-rt-new-item') === '1' || !isValidTravelTabId(readTravelIdAttr($pane));

      if (isOnNewPane && back) {
        window.location.href = back.split('#')[0];
        return;
      }

      renderTravelTabsFromState($pane, mainId, readTravelIdAttr($pane));
    });

    window.addEventListener('popstate', function () {
      const path = window.location.pathname;
      if (path.indexOf('reimbursement-travel/add-item') === -1) return;
      const m = path.match(/\/add-item\/(\d+)\/(\d+)(?:\/|$)/);
      if (!m) {
        window.location.reload();
        return;
      }
      const mainFromUrl = m[1];
      const travelFromUrl = m[2];
      if (!isValidTravelTabId(travelFromUrl)) {
        window.location.replace(path.replace(/\/[^/]+$/, ''));
        return;
      }
      const $pane = $('#rt-travel-item-pane');
      if (!$pane.length) return;
      if (String(readMainIdAttr($pane)) !== String(mainFromUrl)) {
        window.location.reload();
        return;
      }
      if (String(readTravelIdAttr($pane)) === String(travelFromUrl)) return;
      persistCurrentPane($pane);
      const baseUrl = path + (window.location.search || '');
      loadTravelItemTabPartial($pane, baseUrl.split('?')[0], travelFromUrl, false);
    });

    $('form').on('submit', function () {
      const $p = $('#rt-travel-item-pane');
      if (!$p.length) return;
      clearStorageForPane($p);
      const mid = readMainIdAttr($p);
      if (mid) {
        try {
          localStorage.removeItem(itemsStateKey(mid));
        } catch (e) { /* ignore */ }
      }
    });
  });
})(jQuery);
