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
      '.ocr-check-badge{display:flex;align-items:flex-start;gap:6px;margin-top:6px;' +
      'max-width:180px;box-sizing:border-box;' +
      'padding:5px 8px;border:1px solid #d9d9d9;border-left-width:3px;border-radius:6px;' +
      'background:#fff;font-size:11px;line-height:1.35;color:#495057;}' +
      '.ocr-check-badge .ocr-check-icon{flex:none;font-size:12px;line-height:1.35;}' +
      '.ocr-check-badge .ocr-check-text{flex:1;min-width:0;white-space:normal;word-break:break-word;overflow-wrap:anywhere;}' +
      '.ocr-check-badge[data-variant="read"]{border-left-color:#28a745;}' +
      '.ocr-check-badge[data-variant="read"] .ocr-check-icon{color:#1e7e42;}' +
      '.ocr-check-badge[data-variant="blocked"]{border-left-color:#dc3545;background:#fff8f8;}' +
      '.ocr-check-badge[data-variant="blocked"] .ocr-check-text{color:#c0392b;}' +
      '.ocr-check-badge[data-variant="neutral"]{border-left-color:#adb5bd;color:#6c757d;}' +
      '.ocr-check-badge[data-variant="pending"]{border-left-color:#d9d9d9;color:#6c757d;}' +
      '.ocr-check-spinner{flex:none;width:10px;height:10px;margin-top:2px;border:2px solid #cfd4da;' +
      'border-top-color:#8a8f96;border-radius:50%;animation:ocrCheckSpin .7s linear infinite;}' +
      '@keyframes ocrCheckSpin{to{transform:rotate(360deg);}}';
    document.head.appendChild(style);
  }

  function renderBadge($container, status, message) {
    ensureStyles();
    $container.find('.ocr-check-badge').remove();

    var config = {
      read: { variant: 'read', icon: '✓', label: 'Struk terbaca', showFull: true },
      duplicate: { variant: 'blocked', icon: '⚠', label: 'No. Invoice/Receipt ini sudah pernah digunakan', showFull: true },
      unavailable: { variant: 'neutral', icon: 'ℹ', label: 'OCR tidak tersedia', showFull: false },
      skipped: { variant: 'neutral', icon: 'ℹ', label: 'OCR dilewati', showFull: false },
      pending: { variant: 'pending', icon: null, label: 'Memeriksa struk…', showFull: false }
    }[status] || { variant: 'neutral', icon: 'ℹ', label: 'OCR tidak tersedia', showFull: false };

    // Duplicate/read messages carry information the user needs to see (why
    // it's blocked, which invoice was read); unavailable/skipped/pending are
    // just status noise -- their longer explanation goes in the tooltip only,
    // so a verbose backend message never has to fit inside this small badge.
    var fullText = message || config.label;
    var displayText = config.showFull ? fullText : config.label;

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
      return res;
    }).catch(function () {
      // fail-open: a broken OCR check never blocks submission
      setRowStatus($row, 'unavailable');
      renderBadge($badgeContainer, 'unavailable', 'OCR tidak tersedia saat ini.');
      toggleSubmitButtons(hasBlockingMismatch(opts.formScope), opts.submitSelectors);
      return null;
    });
  }

  return {
    verifyAndRender: verifyAndRender,
    hasBlockingMismatch: hasBlockingMismatch,
    toggleSubmitButtons: toggleSubmitButtons,
    STATUS_ATTR: STATUS_ATTR
  };
})(window, jQuery);
