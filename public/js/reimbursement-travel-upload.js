/**
 * Multi-attachment upload for travel reimbursement detail rows.
 * Appends files without clearing existing server-side attachments.
 */
window.TravelUpload = (function () {
  var MAX_WIDTH = 1280;
  var MAX_HEIGHT = 1280;
  var JPEG_QUALITY = 0.82;
  var PDF_ICON = 'https://cdn-icons-png.flaticon.com/512/337/337946.png';
  var ACCEPT_TYPES = 'image/*,.pdf,application/pdf';
  var PANE = '#rt-travel-item-pane';
  var OCR_SUBMIT_SELECTORS = ['#action_button', '#action_button_draft', '#action_button_submit', '#edit_finance', '#edit_owner'];
  var REFERENCE_ENDPOINT = '/reimbursement/check-evidence-reference';
  var REFERENCE_DEBOUNCE_MS = 500;
  var DATE_WINDOW_ENDPOINT = '/reimbursement/check-travel-date-window';
  var DATE_WINDOW_DEBOUNCE_MS = 400;

  function scaleDimensions(width, height) {
    var w = width;
    var h = height;

    if (w > MAX_WIDTH) {
      h = Math.round(h * (MAX_WIDTH / w));
      w = MAX_WIDTH;
    }
    if (h > MAX_HEIGHT) {
      w = Math.round(w * (MAX_HEIGHT / h));
      h = MAX_HEIGHT;
    }

    return { width: w, height: h };
  }

  function setFileOnInput(input, file) {
    if (!input || !file) {
      return;
    }

    var dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
  }

  function compressImageFile(file) {
    return new Promise(function (resolve) {
      if (!file || !file.type || file.type.indexOf('image/') !== 0) {
        resolve(file);
        return;
      }

      var reader = new FileReader();
      reader.onload = function (e) {
        var img = new Image();
        img.onload = function () {
          var dims = scaleDimensions(img.width, img.height);
          var canvas = document.createElement('canvas');
          canvas.width = dims.width;
          canvas.height = dims.height;

          var ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, dims.width, dims.height);

          canvas.toBlob(function (blob) {
            if (!blob) {
              resolve(file);
              return;
            }

            var baseName = (file.name || 'upload').replace(/\.[^.]+$/, '');
            resolve(new File([blob], baseName + '.jpg', {
              type: 'image/jpeg',
              lastModified: Date.now()
            }));
          }, 'image/jpeg', JPEG_QUALITY);
        };
        img.onerror = function () {
          resolve(file);
        };
        img.src = e.target.result;
      };
      reader.onerror = function () {
        resolve(file);
      };
      reader.readAsDataURL(file);
    });
  }

  function captureFromVideo(videoElement) {
    return new Promise(function (resolve, reject) {
      var vw = videoElement.videoWidth || 1280;
      var vh = videoElement.videoHeight || 720;
      var dims = scaleDimensions(vw, vh);
      var canvas = document.createElement('canvas');
      canvas.width = dims.width;
      canvas.height = dims.height;

      var ctx = canvas.getContext('2d');
      ctx.drawImage(videoElement, 0, 0, dims.width, dims.height);

      canvas.toBlob(function (blob) {
        if (!blob) {
          reject(new Error('Failed to capture image'));
          return;
        }

        resolve(new File([blob], 'capture.jpg', {
          type: 'image/jpeg',
          lastModified: Date.now()
        }));
      }, 'image/jpeg', JPEG_QUALITY);
    });
  }

  function getCameraConstraints() {
    return {
      video: {
        facingMode: { ideal: 'environment' },
        width: { ideal: 1280 },
        height: { ideal: 720 }
      }
    };
  }

  function getRowIndex(row) {
    var $row = $(row);
    var explicit = $row.attr('data-row-index');
    if (explicit !== undefined && explicit !== '') {
      return parseInt(explicit, 10);
    }
    var $tbody = $row.closest('tbody');
    return $tbody.find('tr.fieldGroupDetail').index($row);
  }

  /** Samakan indeks baris form untuk lampiran pending & keep_attachment_ids. */
  function syncDetailRowIndices($root) {
    $root = ($root && $root.length) ? $root : $(PANE);
    if (!$root.length) {
      return;
    }
    $root.find('tbody tr.fieldGroupDetail').each(function (idx) {
      var $row = $(this);
      $row.attr('data-row-index', String(idx));
      var $preview = $row.find('[id^="preview_"]').first();
      $preview.find('input.keep-attachment-present-marker').attr('name', 'keep_attachment_ids_present[' + idx + ']');
      $preview.find('input.keep-attachment-input').attr('name', 'keep_attachment_ids[' + idx + '][]');
      $row.find('.pending-attachment-input').attr('name', 'attachments[' + idx + '][]');
    });
  }

  function getPreviewDivFromRow(row) {
    return $(row).find('[id^="preview_"]').first();
  }

  function appendAttachmentInput(row, rowIndex, file) {
    var $row = $(row);
    var $container = $row.find('.attachment-inputs').first();
    if (!$container.length) {
      $container = $('<div class="attachment-inputs" style="display:none;"></div>');
      $row.find('.file-proof').first().append($container);
    }

    var uid = 'att_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);
    var $input = $('<input type="file" class="pending-attachment-input">')
      .attr('name', 'attachments[' + rowIndex + '][]')
      .attr('data-uid', uid);
    setFileOnInput($input[0], file);
    $container.append($input);
    return uid;
  }

  function ensurePreviewCardStyles() {
    if (document.getElementById('travel-preview-card-styles')) {
      return;
    }
    var style = document.createElement('style');
    style.id = 'travel-preview-card-styles';
    style.textContent =
      '.preview-card{position:relative;display:flex;flex-direction:column;align-items:center;' +
      'width:96px;box-sizing:border-box;margin-top:6px;padding:8px 6px 6px;gap:4px;' +
      'border:1px solid #d9d9d9;border-radius:8px;background:#fff;}' +
      '.preview-card-thumb{width:70px;height:70px;object-fit:cover;border:1px solid #28a745;' +
      'border-radius:6px;cursor:pointer;background:#f7f7f7;}' +
      '.preview-card-thumb-icon{object-fit:contain;border-color:#007bff;padding:8px;}' +
      '.preview-card-name{display:block;max-width:88px;font-size:11px;color:#495057;' +
      'text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}' +
      '.preview-card-error{font-size:11px;color:#c0392b;text-align:center;}' +
      '.preview-card-remove{position:absolute;top:-6px;right:-6px;width:18px;height:18px;padding:0;' +
      'line-height:16px;font-size:13px;border-radius:50%;border:1px solid #dc3545;background:#fff;' +
      'color:#dc3545;}' +
      '.preview-card-remove:hover{background:#dc3545;color:#fff;}';
    document.head.appendChild(style);
  }

  function isPdfFile(file) {
    if (!file) {
      return false;
    }
    if (file.type === 'application/pdf') {
      return true;
    }
    var name = (file.name || '').toLowerCase();
    return name.slice(-4) === '.pdf';
  }

  function renderFilePreview(file, uid) {
    return new Promise(function (resolve) {
      var $wrap = $('<div class="pending-attachment-item preview-card">')
        .attr('data-uid', uid);
      var $remove = $('<button type="button" class="btn remove-pending-attachment preview-card-remove">&times;</button>');

      if (file.type && file.type.indexOf('image/') === 0) {
        var reader = new FileReader();
        reader.onload = function (e) {
          $wrap.append($remove);
          $wrap.append(
            $('<img>').attr({
              src: e.target.result,
              'data-preview-src': e.target.result
            }).addClass('preview-thumbnail preview-card-thumb')
          );
          $wrap.append($('<span class="preview-card-name">').text(file.name || 'Gambar').attr('title', file.name || ''));
          resolve($wrap);
        };
        reader.readAsDataURL(file);
      } else if (isPdfFile(file)) {
        var fileURL = URL.createObjectURL(file);
        $wrap.append($remove);
        $wrap.append(
          $('<a>').attr({
            href: fileURL,
            target: '_blank',
            title: 'Lihat PDF'
          }).append(
            $('<img>').attr({
              src: PDF_ICON,
              alt: 'PDF File'
            }).addClass('preview-card-thumb preview-card-thumb-icon')
          )
        );
        $wrap.append($('<span class="preview-card-name">').text(file.name || 'PDF').attr('title', file.name || 'PDF'));
        resolve($wrap);
      } else {
        $wrap.append($remove);
        $wrap.append($('<span class="preview-card-error">').text('File tidak didukung'));
        resolve($wrap);
      }
    });
  }

  function removePendingPreview($item) {
    var $row = $item.closest('tr');
    var uid = $item.attr('data-uid');
    if (uid) {
      $row.find('.pending-attachment-input[data-uid="' + uid + '"]').remove();
    }
    $item.find('a[href^="blob:"]').each(function () {
      try {
        var href = $(this).attr('href');
        if (href) {
          URL.revokeObjectURL(href);
        }
      } catch (e) { /* ignore */ }
    });
    $item.remove();
    // The removed file may have been the one OCR flagged as a mismatch -- clear
    // its status/badge rather than leaving a stale block on an empty row.
    $row.removeAttr(window.ReimbursementOcrCheck ? window.ReimbursementOcrCheck.STATUS_ATTR : 'data-ocr-status');
    $row.find('.ocr-check-badge').remove();
    clearMessRelation($row);
    syncUploadWarning();
  }

  function applyOcrBlockState() {
    if (window.ReimbursementOcrCheck && window.ReimbursementOcrCheck.hasBlockingMismatch($(PANE))) {
      window.ReimbursementOcrCheck.toggleSubmitButtons(true, OCR_SUBMIT_SELECTORS);
    }
  }

  function syncUploadWarning() {
    $('#action_button, #action_button_draft, #action_button_submit, #edit_finance, #edit_owner').prop('disabled', false);
    applyOcrBlockState();
  }

  function enableSubmitButtons() {
    $('#action_button, #action_button_draft, #action_button_submit, #edit_finance, #edit_owner').prop('disabled', false);
    $(PANE).find('.warning-upload').hide();
    if (typeof window.rtTravelSyncFileUploadWarning === 'function') {
      window.rtTravelSyncFileUploadWarning($(PANE));
    }
    applyOcrBlockState();
  }

  function ocrCheckOptions($row, previewDiv, uid) {
    return {
      row: $row,
      badgeContainer: previewDiv,
      submitSelectors: OCR_SUBMIT_SELECTORS,
      formScope: $(PANE),
      excludeId: $(PANE).attr('data-main-id'),
      reimbursementType: 'travel',
      travelDate: $.trim($(PANE).find('input[name="date"]').first().val() || ''),
      onSameTripOffer: function (offer) {
        handleSameTripOffer($row, uid, previewDiv, offer);
      },
      onMessRelation: function (relation) {
        applyMessRelation($row, relation);
      }
    };
  }

  var SAME_TRIP_MODAL_ID = 'rtSameTripDuplicateModal';

  /** Built once, on first use, so no parent view needs its own markup for this. */
  function ensureSameTripModal() {
    if ($('#' + SAME_TRIP_MODAL_ID).length) {
      return;
    }
    if (!document.getElementById('rt-same-trip-modal-styles')) {
      var style = document.createElement('style');
      style.id = 'rt-same-trip-modal-styles';
      style.textContent =
        '#' + SAME_TRIP_MODAL_ID + ' .modal-content{border-radius:10px;border:none;overflow:hidden;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .modal-header{flex-direction:column;align-items:center;border-bottom:none;padding:24px 24px 0;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .rt-same-trip-icon{width:46px;height:46px;border-radius:50%;background:#eef2ff;' +
        'color:#3b5bdb;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:10px;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .modal-title{width:100%;text-align:center;font-weight:700;color:#2b3a55;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .modal-body{padding:14px 24px 4px;text-align:center;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .rt-same-trip-message{color:#495057;font-size:14px;margin-bottom:16px;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .rt-same-trip-details{text-align:left;background:#f8f9fb;border-radius:8px;' +
        'padding:12px 14px;margin-bottom:14px;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .rt-same-trip-details dl{display:grid;grid-template-columns:auto 1fr;' +
        'row-gap:6px;column-gap:14px;margin:0;font-size:13px;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .rt-same-trip-details dt{color:#7a8699;font-weight:600;white-space:nowrap;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .rt-same-trip-details dd{color:#2b3a55;margin:0;word-break:break-word;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .rt-same-trip-note{display:flex;gap:8px;text-align:left;background:#fff7e6;' +
        'border:1px solid #ffe1a8;border-radius:8px;padding:10px 12px;font-size:12px;color:#8a6416;margin-bottom:18px;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .rt-same-trip-note i{flex:none;margin-top:1px;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .modal-footer{border-top:none;padding:0 24px 24px;justify-content:center;gap:10px;}' +
        '#' + SAME_TRIP_MODAL_ID + ' .modal-footer .btn{min-width:120px;border-radius:6px;}';
      document.head.appendChild(style);
    }
    $('body').append(
      '<div class="modal fade" id="' + SAME_TRIP_MODAL_ID + '" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">' +
        '<div class="modal-dialog modal-dialog-centered" role="document">' +
          '<div class="modal-content">' +
            '<div class="modal-header">' +
              '<div class="rt-same-trip-icon"><i class="fa fa-link"></i></div>' +
              '<h5 class="modal-title">Legitimate Duplicate Terdeteksi</h5>' +
            '</div>' +
            '<div class="modal-body">' +
              '<p class="rt-same-trip-message"></p>' +
              '<div class="rt-same-trip-details">' +
                '<dl>' +
                  '<dt>No. Invoice</dt><dd class="rt-same-trip-no-invoice"></dd>' +
                  '<dt>Pemilik claim</dt><dd class="rt-same-trip-owner"></dd>' +
                  '<dt>Ticket UUDP</dt><dd class="rt-same-trip-ticket"></dd>' +
                  '<dt>Konteks</dt><dd>Legitimate duplicate — co-traveler pada perjalanan yang sama</dd>' +
                '</dl>' +
              '</div>' +
              '<div class="rt-same-trip-note">' +
                '<i class="fa fa-info-circle"></i>' +
                '<span>Jika disetujui, invoice ini HANYA dipakai untuk klaim Allowance Travel (bukan expense) ' +
                'dan otomatis tertaut ke claim pemilik di atas — persis mekanisme referensi yang sudah ada di produksi. ' +
                'Expense detail invoice tetap TIDAK diisi.</span>' +
              '</div>' +
            '</div>' +
            '<div class="modal-footer">' +
              '<button type="button" class="btn btn-light rt-same-trip-cancel">Batal</button>' +
              '<button type="button" class="btn btn-primary rt-same-trip-confirm">Ya, Lanjutkan</button>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }

  /**
   * Same-trip legitimate duplicate (co-traveler already claimed this exact
   * invoice as an expense): offer to drop this row's own upload and link it
   * to that claim for Allowance-only instead of hard-blocking. "Batal" and
   * "Ya, Lanjutkan" both remove the just-attached file -- the only
   * difference is whether the reference-invoice input gets populated so the
   * row resolves via resolveReferencedRowInvoice() on save instead.
   */
  function handleSameTripOffer($row, uid, previewDiv, offer) {
    ensureSameTripModal();
    var $modal = $('#' + SAME_TRIP_MODAL_ID);
    $modal.find('.rt-same-trip-message').text(offer.message);
    $modal.find('.rt-same-trip-no-invoice').text(offer.no_invoice || '-');
    $modal.find('.rt-same-trip-owner').text(offer.owner_name || '-');
    $modal.find('.rt-same-trip-ticket').text(offer.ticket_number || '-');

    function removeUploadedFile() {
      var $item = previewDiv.find('.pending-attachment-item[data-uid="' + uid + '"]');
      if ($item.length) {
        removePendingPreview($item);
      }
    }

    $modal.find('.rt-same-trip-confirm, .rt-same-trip-cancel').off('click.rtSameTrip');
    $modal.find('.rt-same-trip-confirm').on('click.rtSameTrip', function () {
      removeUploadedFile();
      var $refInput = $row.find('.reference-invoice-input').first();
      if ($refInput.length) {
        $refInput.val(offer.no_invoice);
        checkReferenceInvoice($refInput);
      }
      $modal.modal('hide');
    });
    $modal.find('.rt-same-trip-cancel').on('click.rtSameTrip', function () {
      removeUploadedFile();
      $modal.modal('hide');
    });

    $modal.modal('show');
  }

  var MESS_RELATION_ATTR = 'data-mess-relation-invoice';

  /**
   * Trip Type is one field per day, shared by every cost-line row in that
   * day's table -- so the lock isn't "this row's problem", it's "does ANY
   * row in this pane currently have an active Mess relation". Re-evaluated
   * from scratch every time a relation is applied or cleared, so removing
   * the one row that triggered the lock correctly unlocks the field again
   * even if other rows exist.
   */
  function syncMessLockState($scope) {
    $scope = ($scope && $scope.length) ? $scope : $(PANE);
    var $tripType = $scope.find('#trip_type_id').first();
    if (!$tripType.length) {
      return;
    }
    var $lockedRow = $scope.find('tbody tr.fieldGroupDetail[' + MESS_RELATION_ATTR + ']').first();
    var $note = $scope.find('.mess-relation-note');
    if (!$note.length) {
      $note = $('<div class="mess-relation-note date-block-feedback" style="margin-top:4px;"></div>');
      $tripType.after($note);
    }

    if ($lockedRow.length) {
      var tripTypeId = $lockedRow.attr('data-mess-relation-trip-type');
      if (tripTypeId) {
        $tripType.val(tripTypeId);
      }
      $tripType.prop('disabled', true);
      $note.attr('class', 'mess-relation-note date-block-feedback is-ok');
      $note.text('✔ Trip Type otomatis: Stay(MESS) -- invoice Mess/Hotel sama dengan ' + $lockedRow.attr('data-mess-relation-owner') + ' pada tanggal yang sama. Field dikunci.');
    } else {
      $tripType.prop('disabled', false);
      $note.attr('class', 'mess-relation-note date-block-feedback');
      $note.text('');
    }
  }

  /** Called when the OCR-check endpoint returns `mess_relation` for this row's uploaded file. */
  function applyMessRelation($row, relation) {
    if (!relation || !relation.trip_type_id) {
      return;
    }
    $row.attr(MESS_RELATION_ATTR, relation.no_invoice || '1');
    $row.attr('data-mess-relation-owner', relation.owner_name || '');
    $row.attr('data-mess-relation-trip-type', relation.trip_type_id);
    syncMessLockState($row.closest(PANE).length ? $row.closest(PANE) : $(PANE));
  }

  /** Called whenever this row's file is removed, or a fresh OCR check no longer returns a relation -- keeps the lock from outliving the invoice that justified it. */
  function clearMessRelation($row) {
    if (!$row || !$row.attr(MESS_RELATION_ATTR)) {
      return;
    }
    $row.removeAttr(MESS_RELATION_ATTR);
    $row.removeAttr('data-mess-relation-owner');
    $row.removeAttr('data-mess-relation-trip-type');
    syncMessLockState($row.closest(PANE).length ? $row.closest(PANE) : $(PANE));
  }

  function runOcrCheckForRow(row, file, previewDiv, uid) {
    if (!window.ReimbursementOcrCheck) {
      return;
    }
    var $row = $(row);
    // A fresh check supersedes whatever this row previously triggered --
    // re-applied by onMessRelation below only if the new result still matches.
    clearMessRelation($row);
    window.ReimbursementOcrCheck.verifyAndRender(
      Object.assign({ file: file }, ocrCheckOptions($row, previewDiv, uid))
    );
  }

  function processAndAppendFile(row, file) {
    return compressImageFile(file).then(function (processed) {
      var rowIndex = getRowIndex(row);
      var uid = appendAttachmentInput(row, rowIndex, processed);
      var previewDiv = getPreviewDivFromRow(row);
      return renderFilePreview(processed, uid).then(function ($el) {
        previewDiv.append($el);
        enableSubmitButtons();
        runOcrCheckForRow(row, processed, previewDiv, uid);
        return processed;
      });
    });
  }

  function resetPickerInput(input) {
    if (!input) {
      return;
    }
    input.value = '';
  }

  function markUploadHandled(event) {
    if (event && event.originalEvent) {
      event.originalEvent.__rtTravelUploadHandled = true;
    }
  }

  function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || '';
  }

  /**
   * A row that references another traveler's evidence (instead of
   * uploading its own) counts the same as an uploaded file for the pane-
   * wide "you haven't uploaded anything at all" gate.
   */
  function setReferenceStatus($row, status) {
    if (status) {
      $row.attr('data-reference-status', status);
    } else {
      $row.removeAttr('data-reference-status');
    }
    syncUploadWarning();
  }

  function renderReferenceFeedback($feedback, variant, message) {
    var colors = { found: '#1e7e42', checking: '#6c757d', error: '#c0392b', muted: '#6c757d' };
    $feedback.css('color', colors[variant] || colors.muted).text(message || '');
  }

  function checkReferenceInvoice($input) {
    var $row = $input.closest('tr');
    var $feedback = $input.closest('.reference-invoice-wrap').find('.reference-invoice-feedback');
    var value = $.trim($input.val());

    if (value === '') {
      renderReferenceFeedback($feedback, 'muted', '');
      setReferenceStatus($row, null);
      return;
    }

    renderReferenceFeedback($feedback, 'checking', 'Memeriksa…');

    $.ajax({
      url: REFERENCE_ENDPOINT,
      method: 'POST',
      dataType: 'json',
      data: {
        _token: csrfToken(),
        no_invoice: value,
        exclude_id: $(PANE).attr('data-main-id')
      }
    }).then(function (res) {
      res = res || {};
      if (res.found) {
        renderReferenceFeedback($feedback, 'found', res.message || 'Ditemukan.');
        setReferenceStatus($row, 'found');
      } else {
        renderReferenceFeedback($feedback, 'error', res.message || 'Tidak ditemukan.');
        setReferenceStatus($row, null);
      }
    }).catch(function () {
      renderReferenceFeedback($feedback, 'error', 'Tidak dapat memeriksa saat ini.');
      setReferenceStatus($row, null);
    });
  }

  function bindReferenceInvoiceHandlers() {
    if (window.__travelReferenceInvoiceBound) {
      return;
    }
    window.__travelReferenceInvoiceBound = true;

    var timers = {};
    $('body').on('input', PANE + ' .reference-invoice-input', function () {
      var input = this;
      var uid = $(input).data('rt-ref-uid');
      if (!uid) {
        uid = 'ref_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);
        $(input).data('rt-ref-uid', uid);
      }
      clearTimeout(timers[uid]);
      timers[uid] = setTimeout(function () {
        checkReferenceInvoice($(input));
      }, REFERENCE_DEBOUNCE_MS);
    });
  }

  /**
   * Advisory only (see checkTravelDateWindow() on the backend) -- doesn't
   * disable submit, just flags a likely duplicate/overlapping date entry
   * for the SAME user before they finish filling out the rest of the form.
   */
  function renderDateWindowFeedback($input, blocked, message) {
    var $feedback = $input.next('.travel-date-window-feedback');
    if (!$feedback.length) {
      $feedback = $('<div class="travel-date-window-feedback date-block-feedback" style="margin-top:4px;"></div>');
      $input.after($feedback);
    }
    if (!message) {
      $feedback.attr('class', 'travel-date-window-feedback date-block-feedback');
      $feedback.text('');
      return;
    }
    $feedback.attr('class', 'travel-date-window-feedback date-block-feedback ' + (blocked ? 'is-blocked' : 'is-ok'));
    $feedback.text((blocked ? '⚠ ' : '✔ ') + message);
  }

  function checkTravelDateWindow($input) {
    var value = $.trim($input.val());
    if (value === '') {
      renderDateWindowFeedback($input, false, null);
      return;
    }

    $.ajax({
      url: DATE_WINDOW_ENDPOINT,
      method: 'POST',
      dataType: 'json',
      data: {
        _token: csrfToken(),
        date: value,
        exclude_id: $(PANE).attr('data-main-id')
      }
    }).then(function (res) {
      res = res || {};
      if (res.blocked) {
        renderDateWindowFeedback($input, true, res.message || 'Tanggal ini berdekatan dengan pengajuan Travel Anda yang lain.');
      } else {
        renderDateWindowFeedback($input, false, null);
      }
    }).catch(function () {
      // fail-open: an unreachable check never blocks the form, it just skips the heads-up
      renderDateWindowFeedback($input, false, null);
    });
  }

  function bindTravelDateWindowHandler() {
    if (window.__travelDateWindowBound) {
      return;
    }
    window.__travelDateWindowBound = true;

    var timer = null;
    $('body').on('input change', PANE + ' input[name="date"]', function () {
      var input = this;
      clearTimeout(timer);
      timer = setTimeout(function () {
        checkTravelDateWindow($(input));
      }, DATE_WINDOW_DEBOUNCE_MS);
    });
  }

  function bindAttachmentHandlers() {
    if (window.__travelUploadBound) {
      return;
    }
    window.__travelUploadBound = true;

    $('body').on('click', PANE + ' .remove-pending-attachment', function () {
      removePendingPreview($(this).closest('.pending-attachment-item'));
    });

    $('body').on('click', PANE + ' .remove-existing-attachment', function () {
      var $btn = $(this);
      var $item = $btn.closest('.existing-attachment-item');
      var $preview = $btn.closest('[id^="preview_"]');
      var attachmentId = String($btn.data('attachment-id') || '');
      if (attachmentId !== '' && attachmentId !== '0') {
        $preview.find('input.keep-attachment-input[value="' + attachmentId + '"]').remove();
      }
      $item.remove();
      syncUploadWarning();
      if (typeof window.rtTravelSyncFileUploadWarning === 'function') {
        window.rtTravelSyncFileUploadWarning($(PANE));
      }
    });

    $('body').on('click', PANE + ' .addFile', function () {
      var btn = $(this);
      var row = btn.closest('tr');
      var fileInput = row.find('.file-input').first();

      fileInput.click();

      fileInput.off('change.travelUpload').on('change.travelUpload', function (event) {
        markUploadHandled(event);
        var file = event.target.files[0];
        if (!file) {
          return;
        }

        processAndAppendFile(row, file).then(function () {
          btn.find('i').removeClass('fa-upload').addClass('fa-check');
          resetPickerInput(fileInput[0]);
        });
      });
    });

    $('body').on('click', PANE + ' .addCamera', function () {
      var btn = $(this);
      var row = btn.closest('tr');
      var cameraInput = row.find('.camera-input').first();

      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        return;
      }

      navigator.mediaDevices.getUserMedia(getCameraConstraints())
        .then(function (stream) {
          $('#modalPhoto').modal('show');
          var videoElement = $('#videoElement')[0];
          videoElement.srcObject = stream;

          $('#captureButton').off('click.travelUpload').on('click.travelUpload', function () {
            captureFromVideo(videoElement).then(function (file) {
              return processAndAppendFile(row, file);
            }).then(function () {
              btn.find('i').removeClass('fa-camera').addClass('fa-check');
              stream.getTracks().forEach(function (track) { track.stop(); });
              $('#modalPhoto').modal('hide');
            }).catch(function (err) {
              console.error('Failed to capture image: ' + err);
            });
          });
        })
        .catch(function (err) {
          console.error('Error accessing webcam: ' + err);
        });
    });
  }

  if (typeof jQuery !== 'undefined') {
    $(function () {
      ensurePreviewCardStyles();
      bindAttachmentHandlers();
      bindReferenceInvoiceHandlers();
      bindTravelDateWindowHandler();
    });
  }

  return {
    ACCEPT_TYPES: ACCEPT_TYPES,
    compressImageFile: compressImageFile,
    setFileOnInput: setFileOnInput,
    captureFromVideo: captureFromVideo,
    getCameraConstraints: getCameraConstraints,
    getRowIndex: getRowIndex,
    syncDetailRowIndices: syncDetailRowIndices,
    getPreviewDivFromRow: getPreviewDivFromRow,
    appendAttachmentInput: appendAttachmentInput,
    renderFilePreview: renderFilePreview,
    removePendingPreview: removePendingPreview,
    processAndAppendFile: processAndAppendFile,
    enableSubmitButtons: enableSubmitButtons,
    bindAttachmentHandlers: bindAttachmentHandlers,
    bindReferenceInvoiceHandlers: bindReferenceInvoiceHandlers,
    bindTravelDateWindowHandler: bindTravelDateWindowHandler
  };
})();
