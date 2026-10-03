/**
 * Fast-feedback OCR check for Travel/Entertainment receipt uploads: right
 * after a file is attached to a cost-line row, POST it to the server which
 * OCRs it (Gemini vision API) to read the row's No. Invoice/Receipt (no
 * longer typed by the user -- OCR IS the source) and checks that number for
 * duplicates. Amount is NOT verified against the receipt -- it's manual
 * input only. A detected duplicate renders a red badge and disables the
 * submit buttons until it's resolved -- same hard-block philosophy as
 * reimbursement-duplicate-check.js, but per-file/immediate instead of only
 * at submit time. A broken/unconfigured OCR service (network error, no API
 * key) fails OPEN: it never blocks, it just shows a muted "not verified" badge.
 *
 * The real, unbypassable block still happens server-side when the item is
 * actually saved (see extractReceiptInvoiceNumber() / guardAgainstDuplicateRowInvoice()
 * in both reimbursement controllers) -- this module only gives the user
 * faster feedback.
 */
window.ReimbursementOcrCheck = (function (window, $) {
  var ENDPOINT = '/reimbursement/verify-receipt-ocr';
  var STATUS_ATTR = 'data-ocr-status';
  var BLOCKING_STATUSES = ['duplicate'];

  function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || '';
  }

  /**
   * Matches the look of the existing attachment cards this badge sits below
   * (.existing-attachment-item / .pending-attachment-item: bordered box,
   * 6px radius, 6px padding, 6px top margin) instead of a loud filled-color
   * pill -- a colored left edge + small icon carries the status, not a
   * flashing background block.
   */
  function ensureStyles() {
    if (document.getElementById('ocr-check-styles')) {
      return;
    }
    var style = document.createElement('style');
    style.id = 'ocr-check-styles';
    style.textContent =
      '.ocr-check-badge{display:flex;align-items:center;gap:5px;margin-top:5px;' +
      'width:100%;max-width:96px;box-sizing:border-box;' +
      'padding:4px 6px;border:1px solid #d9d9d9;border-left-width:3px;border-radius:6px;' +
      'background:#fff;font-size:10px;line-height:1.3;color:#495057;}' +
      '.ocr-check-badge .ocr-check-icon{flex:none;font-size:11px;line-height:1.3;}' +
      '.ocr-check-badge .ocr-check-text{flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}' +
      '.ocr-check-badge[data-variant="read"]{border-left-color:#28a745;}' +
      '.ocr-check-badge[data-variant="read"] .ocr-check-icon{color:#1e7e42;}' +
      '.ocr-check-badge[data-variant="blocked"]{border-left-color:#dc3545;background:#fff8f8;}' +
      '.ocr-check-badge[data-variant="blocked"] .ocr-check-text{color:#c0392b;}' +
      '.ocr-check-badge[data-variant="warning"]{border-left-color:#f0ad4e;background:#fffaf2;}' +
      '.ocr-check-badge[data-variant="warning"] .ocr-check-text{color:#a06a1c;}' +
      '.ocr-check-badge[data-variant="neutral"]{border-left-color:#adb5bd;color:#6c757d;}' +
      '.ocr-check-badge[data-variant="pending"]{border-left-color:#d9d9d9;color:#6c757d;}' +
      '.ocr-check-spinner{flex:none;width:10px;height:10px;margin-top:2px;border:2px solid #cfd4da;' +
      'border-top-color:#8a8f96;border-radius:50%;animation:ocrCheckSpin .7s linear infinite;}' +
      '@keyframes ocrCheckSpin{to{transform:rotate(360deg);}}';
    document.head.appendChild(style);
  }

  var NOTICE_MODAL_ID = 'ocrNoticeModal';

  /**
   * Plain "read this" popup for an OCR message that is too long for the inline
   * badge (Sep 2026 feedback: the warning under the preview was ellipsised to
   * "Foto ini sepe..." and unreadable on a phone). Non-blocking: it only
   * informs, the user closes it and carries on. Built on first use so no view
   * needs its own markup.
   */
  function showNoticeModal(message) {
    if (!$('#' + NOTICE_MODAL_ID).length) {
      $('body').append(
        '<div class="modal fade" id="' + NOTICE_MODAL_ID + '" tabindex="-1" role="dialog" aria-hidden="true">' +
          '<div class="modal-dialog modal-dialog-centered" role="document">' +
            '<div class="modal-content" style="border-radius:10px;border:none;">' +
              '<div class="modal-body" style="padding:24px;text-align:center;">' +
                '<div style="width:46px;height:46px;border-radius:50%;background:#fff7e6;color:#e0a800;' +
                     'display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 12px;">' +
                  '<i class="fa fa-exclamation-triangle"></i></div>' +
                '<div class="ocr-notice-modal-message" style="color:#495057;font-size:14px;"></div>' +
              '</div>' +
              '<div class="modal-footer" style="border-top:none;justify-content:center;padding:0 24px 24px;">' +
                '<button type="button" class="btn btn-primary" data-dismiss="modal" style="min-width:120px;border-radius:6px;">Mengerti</button>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>'
      );
    }
    var $m = $('#' + NOTICE_MODAL_ID);
    $m.find('.ocr-notice-modal-message').text(message);
    $m.modal('show');
  }

  function renderBadge($container, status, message) {
    ensureStyles();
    $container.find('.ocr-check-badge').remove();

    var config = {
      read: { variant: 'read', icon: '✓', label: 'Receipt read', showFull: true },
      duplicate: { variant: 'blocked', icon: '⚠', label: 'This Invoice/Receipt No. has already been used', showFull: true },
      // Non-blocking (unlike duplicate) -- OCR ran fine, it just found nothing
      // receipt-shaped on the photo at all (no amount/merchant/date/invoice),
      // so this is a nudge to re-upload the correct file, not a hard stop.
      // No inline badge at all: the full sentence never fitted (it was
      // ellipsised to "Foto ini sepe..." on a phone) and the popup below now
      // carries the whole message, so a second, truncated copy under the
      // preview was just noise (Sep 2026 feedback: "tulisan kecil yang dibawah
      // itu ilangin aja, soalnya kan udah ada dari modal").
      not_receipt: { variant: 'warning', icon: '⚠', label: '', showFull: false, notify: true, hideBadge: true },
      unavailable: { variant: 'neutral', icon: 'ℹ', label: 'OCR not available', showFull: false },
      skipped: { variant: 'neutral', icon: 'ℹ', label: 'OCR skipped', showFull: false },
      pending: { variant: 'pending', icon: null, label: 'Checking receipt…', showFull: false }
    }[status] || { variant: 'neutral', icon: 'ℹ', label: 'OCR not available', showFull: false };

    // Duplicate/read messages carry information the user needs to see (why
    // it's blocked, which invoice was read); unavailable/skipped/pending are
    // just status noise -- their longer explanation goes in the tooltip only,
    // so a verbose backend message never has to fit inside this small badge.
    var fullText = message || config.label;
    var displayText = config.showFull ? fullText : config.label;

    // Some statuses are delivered purely as a popup (see hideBadge): nothing is
    // drawn under the preview, but the notification below still fires.
    if (config.hideBadge) {
      if (config.notify && fullText) {
        setTimeout(function () { showNoticeModal(fullText); }, 0);
      }
      return;
    }

    var $badge = $('<div class="ocr-check-badge">').attr({
      'data-variant': config.variant,
      title: fullText
    });

    if (config.icon === null) {
      $badge.append('<span class="ocr-check-spinner"></span>');
    } else {
      $badge.append($('<span class="ocr-check-icon">').text(config.icon));
    }
    $badge.append($('<span class="ocr-check-text">').text(displayText));

    $container.append($badge);

    // Statuses whose message the user must actually read get a popup as well:
    // the badge alone is a single ellipsised line, which on a phone shows
    // almost nothing. Deferred so the badge is painted first.
    if (config.notify && message) {
      setTimeout(function () { showNoticeModal(message); }, 0);
    }
  }

  var DUPLICATE_MODAL_ID = 'ocrDuplicateInvoiceModal';

  /** Built once, on first use, so no parent view needs its own markup for this. */
  function ensureDuplicateModal() {
    if ($('#' + DUPLICATE_MODAL_ID).length) {
      return;
    }
    $('body').append(
      '<div class="modal fade" id="' + DUPLICATE_MODAL_ID + '" tabindex="-1" role="dialog" aria-hidden="true">' +
        '<div class="modal-dialog modal-dialog-centered" role="document">' +
          '<div class="modal-content">' +
            '<div class="modal-header">' +
              '<h5 class="modal-title">⚠ Invoice Number Already Used</h5>' +
            '</div>' +
            '<div class="modal-body">' +
              '<p class="ocr-duplicate-modal-message" style="margin-bottom:0;"></p>' +
            '</div>' +
            '<div class="modal-footer">' +
              '<button type="button" class="btn btn-primary" data-dismiss="modal">Got it</button>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }

  function showDuplicateModal(message) {
    ensureDuplicateModal();
    var $modal = $('#' + DUPLICATE_MODAL_ID);
    $modal.find('.ocr-duplicate-modal-message').text(message || 'This Invoice/Receipt No. has already been used.');
    $modal.modal('show');
  }

  var SAME_TRIP_INFO_MODAL_ID = 'ocrSameTripInfoModal';

  /**
   * Info-only counterpart to the interactive "convert this row to a
   * reference" modal in reimbursement-travel-upload.js -- used on forms
   * (create/edit-inquiry, create/edit-overseas) that have no reference-invoice
   * field yet, so there is nothing to convert the row to. It only explains
   * *why* the row is blocked (same-trip legitimate duplicate) instead of
   * pretending to resolve it; the row stays blocked until the file is removed.
   */
  function ensureSameTripInfoModal() {
    if ($('#' + SAME_TRIP_INFO_MODAL_ID).length) {
      return;
    }
    if (!document.getElementById('ocr-same-trip-info-styles')) {
      var style = document.createElement('style');
      style.id = 'ocr-same-trip-info-styles';
      style.textContent =
        '.ocr-same-trip-modal .modal-content{border-radius:10px;border:none;overflow:hidden;}' +
        '.ocr-same-trip-modal .modal-header{flex-direction:column;align-items:center;border-bottom:none;padding:24px 24px 0;}' +
        '.ocr-same-trip-modal .ocr-same-trip-icon{width:46px;height:46px;border-radius:50%;background:#fde8e8;' +
        'color:#d92626;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:10px;}' +
        '.ocr-same-trip-modal .modal-title{width:100%;text-align:center;font-weight:700;color:#2b3a55;}' +
        '.ocr-same-trip-modal .modal-body{padding:14px 24px 4px;text-align:center;}' +
        '.ocr-same-trip-modal .ocr-same-trip-message{color:#495057;font-size:14px;margin-bottom:16px;}' +
        '.ocr-same-trip-modal .ocr-same-trip-details{text-align:left;background:#f8f9fb;border-radius:8px;' +
        'padding:12px 14px;margin-bottom:14px;}' +
        '.ocr-same-trip-modal .ocr-same-trip-details dl{display:grid;grid-template-columns:auto 1fr;' +
        'row-gap:6px;column-gap:14px;margin:0;font-size:13px;}' +
        '.ocr-same-trip-modal .ocr-same-trip-details dt{color:#7a8699;font-weight:600;white-space:nowrap;}' +
        '.ocr-same-trip-modal .ocr-same-trip-details dd{color:#2b3a55;margin:0;word-break:break-word;}' +
        '.ocr-same-trip-modal .ocr-same-trip-note{display:flex;gap:8px;text-align:left;background:#fff7e6;' +
        'border:1px solid #ffe1a8;border-radius:8px;padding:10px 12px;font-size:12px;color:#8a6416;margin-bottom:18px;}' +
        '.ocr-same-trip-modal .ocr-same-trip-note i{flex:none;margin-top:1px;}' +
        '.ocr-same-trip-modal .modal-footer{border-top:none;padding:0 24px 24px;justify-content:center;}' +
        '.ocr-same-trip-modal .modal-footer .btn{min-width:120px;border-radius:6px;}';
      document.head.appendChild(style);
    }
    $('body').append(
      '<div class="modal fade ocr-same-trip-modal" id="' + SAME_TRIP_INFO_MODAL_ID + '" tabindex="-1" role="dialog" aria-hidden="true">' +
        '<div class="modal-dialog modal-dialog-centered" role="document">' +
          '<div class="modal-content">' +
            '<div class="modal-header">' +
              '<div class="ocr-same-trip-icon"><i class="fa fa-link"></i></div>' +
              '<h5 class="modal-title">Legitimate Duplicate Detected</h5>' +
            '</div>' +
            '<div class="modal-body">' +
              '<p class="ocr-same-trip-message"></p>' +
              '<div class="ocr-same-trip-details">' +
                '<dl>' +
                  '<dt>No. Invoice</dt><dd class="ocr-same-trip-no-invoice"></dd>' +
                  '<dt>Claim owner</dt><dd class="ocr-same-trip-owner"></dd>' +
                  '<dt>Ticket UUDP</dt><dd class="ocr-same-trip-ticket"></dd>' +
                  '<dt>Context</dt><dd>Legitimate duplicate — co-traveler on the same trip</dd>' +
                '</dl>' +
              '</div>' +
              '<div class="ocr-same-trip-note">' +
                '<i class="fa fa-info-circle"></i>' +
                '<span class="ocr-same-trip-note-text">This form does not yet support claiming the Travel Allowance via a co-traveler invoice reference. ' +
                'Please remove this attachment and use your own invoice/receipt, or submit it through the ' +
                'add item menu on an existing reimbursement.</span>' +
              '</div>' +
            '</div>' +
            '<div class="modal-footer">' +
              '<button type="button" class="btn btn-primary" data-dismiss="modal">Got it</button>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }

  function showSameTripInfoModal(offer) {
    if (!offer) {
      return;
    }
    ensureSameTripInfoModal();
    var $modal = $('#' + SAME_TRIP_INFO_MODAL_ID);
    $modal.find('.ocr-same-trip-message').text(offer.message || '');
    $modal.find('.ocr-same-trip-no-invoice').text(offer.no_invoice || '-');
    $modal.find('.ocr-same-trip-owner').text(offer.owner_name || '-');
    $modal.find('.ocr-same-trip-ticket').text(offer.ticket_number || '-');
    if (offer.note) {
      $modal.data('defaultNote', $modal.data('defaultNote') || $modal.find('.ocr-same-trip-note-text').text());
      $modal.find('.ocr-same-trip-note-text').text(offer.note);
    } else if ($modal.data('defaultNote')) {
      $modal.find('.ocr-same-trip-note-text').text($modal.data('defaultNote'));
    }
    $modal.modal('show');
  }

  var SAME_TRIP_CONFIRM_MODAL_ID = 'ocrSameTripConfirmModal';

  /**
   * Actionable same-trip popup (Travel create form): "Batal" discards the upload,
   * "Yes, Continue" converts the day to an allowance-only reference of the owner's
   * claim. Exactly one of onYes/onCancel is called, once.
   */
  function showSameTripConfirmModal(offer, onYes, onCancel) {
    if (!offer) {
      return;
    }
    ensureSameTripInfoModal(); // injects the shared modal CSS
    if (!$('#' + SAME_TRIP_CONFIRM_MODAL_ID).length) {
      $('body').append(
        '<div class="modal fade ocr-same-trip-modal" id="' + SAME_TRIP_CONFIRM_MODAL_ID + '" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">' +
          '<div class="modal-dialog modal-dialog-centered" role="document">' +
            '<div class="modal-content">' +
              '<div class="modal-header">' +
                '<div class="ocr-same-trip-icon"><i class="fa fa-link"></i></div>' +
                '<h5 class="modal-title">Legitimate Duplicate Detected</h5>' +
              '</div>' +
              '<div class="modal-body">' +
                '<p class="ocr-same-trip-message"></p>' +
                '<div class="ocr-same-trip-details">' +
                  '<dl>' +
                    '<dt>No. Invoice</dt><dd class="ocr-same-trip-no-invoice"></dd>' +
                    '<dt>Claim owner</dt><dd class="ocr-same-trip-owner"></dd>' +
                    '<dt>Ticket UUDP</dt><dd class="ocr-same-trip-ticket"></dd>' +
                    '<dt>Context</dt><dd>Legitimate duplicate — co-traveler on the same trip</dd>' +
                  '</dl>' +
                '</div>' +
                '<div class="ocr-same-trip-note">' +
                  '<i class="fa fa-info-circle"></i>' +
                  '<span>If you continue, this invoice is used ONLY for the Travel Allowance claim (not an expense) ' +
                  'and is automatically linked to the claim of the owner above. Expense details for this day are not filled in.</span>' +
                '</div>' +
              '</div>' +
              '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary js-same-trip-cancel">Cancel</button>' +
                '<button type="button" class="btn btn-primary js-same-trip-yes">Yes, Continue</button>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>'
      );
    }
    var $modal = $('#' + SAME_TRIP_CONFIRM_MODAL_ID);
    $modal.find('.ocr-same-trip-message').text(offer.message || '');
    $modal.find('.ocr-same-trip-no-invoice').text(offer.no_invoice || '-');
    $modal.find('.ocr-same-trip-owner').text(offer.owner_name || '-');
    $modal.find('.ocr-same-trip-ticket').text(offer.ticket_number || '-');
    var done = false;
    function finish(fn) {
      if (done) { return; }
      done = true;
      $modal.find('.js-same-trip-yes, .js-same-trip-cancel').off('click');
      $modal.modal('hide');
      if (typeof fn === 'function') { fn(offer); }
    }
    $modal.find('.js-same-trip-yes').off('click').on('click', function () { finish(onYes); });
    $modal.find('.js-same-trip-cancel').off('click').on('click', function () { finish(onCancel); });
    $modal.modal('show');
  }

  function setRowStatus($row, status) {
    $row.attr(STATUS_ATTR, status);
  }

  function hasBlockingMismatch($scope) {
    $scope = ($scope && $scope.length) ? $scope : $(document);
    var selector = BLOCKING_STATUSES.map(function (s) {
      return '[' + STATUS_ATTR + '="' + s + '"]';
    }).join(', ');
    return $scope.find(selector).length > 0;
  }

  function toggleSubmitButtons(disabled, submitSelectors) {
    $((submitSelectors || []).join(', ')).prop('disabled', !!disabled);
  }

  function buildBadgeMessage(res) {
    if (res.duplicate && res.duplicate_message) {
      return res.duplicate_message;
    }
    if (res.extracted_no_invoice) {
      return 'No. Invoice: ' + res.extracted_no_invoice;
    }
    return res.message || null;
  }

  /**
   * @param {Object} opts
   * @param {File} opts.file
   * @param {jQuery} opts.row
   * @param {jQuery} opts.badgeContainer
   * @param {string[]} [opts.submitSelectors]
   * @param {jQuery} [opts.formScope]
   * @param {number|string} [opts.excludeId] current reimbursement's own id, so re-saving
   *   its own row's unchanged invoice number isn't flagged as a duplicate of itself
   * @param {string} [opts.reimbursementType] passed through to the endpoint so it only
   *   offers the Travel same-trip/legitimate-duplicate flow where it applies
   * @param {function} [opts.onSameTripOffer] called with (offer, res) when the server
   *   flags the duplicate as a possible same-trip co-traveler case (see
   *   `same_trip_offer` in ReimbursementController::verifyReceiptOcr()) instead of a
   *   real duplicate -- lets the caller show a confirm dialog and convert the row to
   *   a reference instead of leaving it hard-blocked
   * @param {string} [opts.travelDate] this row's day (Y-m-d) -- required for the server
   *   to check `mess_relation` (a co-traveler sharing the exact same Mess/Hotel invoice
   *   on the exact same date); omitted entirely, that check is skipped server-side
   * @param {function} [opts.onMessRelation] called with (relation, res) when the server
   *   returns `mess_relation` -- a non-blocking, same-date shared-invoice match. The
   *   caller is expected to auto-select and lock Trip Type to `relation.trip_type_id`
   *   (Stay(MESS)), and to un-lock it again once this row's file/invoice no longer
   *   matches (this module has no view into the Trip Type field itself)
   */
  function verifyAndRender(opts) {
    var $row = opts.row;
    var $badgeContainer = opts.badgeContainer;
    var file = opts.file;

    if (!file || !$row || !$badgeContainer || !$badgeContainer.length) {
      return $.Deferred().resolve(null).promise();
    }

    setRowStatus($row, 'pending');
    renderBadge($badgeContainer, 'pending');
    toggleSubmitButtons(false, opts.submitSelectors); // pending never blocks, only a confirmed duplicate does

    var formData = new FormData();
    formData.append('receipt', file);
    formData.append('_token', csrfToken());
    if (opts.excludeId) {
      formData.append('exclude_id', opts.excludeId);
    }
    if (opts.reimbursementType) {
      formData.append('reimbursement_type', opts.reimbursementType);
    }
    if (opts.travelDate) {
      formData.append('travel_date', opts.travelDate);
    }

    return $.ajax({
      url: ENDPOINT,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false
    }).then(function (res) {
      res = res || {};
      var status = res.duplicate ? 'duplicate' : (res.status || 'unavailable');
      setRowStatus($row, status);
      renderBadge($badgeContainer, status, buildBadgeMessage(res));
      toggleSubmitButtons(hasBlockingMismatch(opts.formScope), opts.submitSelectors);
      if (res.mess_relation && typeof opts.onMessRelation === 'function') {
        opts.onMessRelation(res.mess_relation, res);
      } else if (res.same_trip_offer && typeof opts.onSameTripOffer === 'function') {
        opts.onSameTripOffer(res.same_trip_offer, res);
      } else if (status === 'duplicate') {
        showDuplicateModal(buildBadgeMessage(res));
      }
      return res;
    }).catch(function () {
      // fail-open: a broken OCR check never blocks submission
      setRowStatus($row, 'unavailable');
      renderBadge($badgeContainer, 'unavailable', 'OCR is not available at the moment.');
      toggleSubmitButtons(hasBlockingMismatch(opts.formScope), opts.submitSelectors);
      return null;
    });
  }

  return {
    verifyAndRender: verifyAndRender,
    hasBlockingMismatch: hasBlockingMismatch,
    toggleSubmitButtons: toggleSubmitButtons,
    showSameTripInfoModal: showSameTripInfoModal,
    showSameTripConfirmModal: showSameTripConfirmModal,
    STATUS_ATTR: STATUS_ATTR
  };
})(window, jQuery);
