/**
 * Validasi Tanggal Pengajuan & Validasi Invoice/Receipt: sebelum form
 * reimbursement benar-benar dikirim, cek ke server apakah tanggal dan/atau
 * nomor invoice/receipt yang diisi sudah pernah dipakai sebelumnya. Ini
 * hard block, bukan sekadar peringatan -- kalau ada yang duplikat, submit
 * dibatalkan dan tidak ada jalan untuk memaksa lanjut dari sisi form ini.
 * (store() di server juga menolak submission yang sama, jadi ini tidak
 * bisa dilewati dengan mematikan JS atau mengirim POST langsung.)
 */
(function (window, $) {
  function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || '';
  }

  function showDuplicateBlocked(message) {
    return window.swal({
      title: 'Tidak Bisa Diajukan',
      text: message,
      icon: 'error',
      button: 'Mengerti'
    });
  }

  function runCheck(check, $form) {
    var params = typeof check.params === 'function' ? check.params($form) : check.params;
    if (!params) {
      return Promise.resolve(null);
    }

    return $.post(check.url, Object.assign({ _token: csrfToken() }, params))
      .then(function (res) { return res; })
      .catch(function () { return null; }); // fail-open: a broken check never blocks submission
  }

  function bindReimbursementDuplicateChecks(options) {
    options = options || {};
    var $form = $(options.formSelector || '#sample_form');
    if (!$form.length || typeof window.swal !== 'function') {
      return;
    }

    var checks = Array.isArray(options.checks) ? options.checks : [];
    var confirmed = false;
    var lastClickedSubmitter = null;

    // A form.submit() triggered from JS (used below to let the request through
    // after the duplicate check) has no "submitter" at all, so the clicked
    // button's name/value (e.g. name="save" vs name="save_draft") would
    // otherwise be silently dropped from the request. Track it here and
    // re-add it as a hidden field before re-submitting.
    $form.on('click', 'button[type="submit"], input[type="submit"]', function () {
      lastClickedSubmitter = this;
    });

    function ensureSubmitterField(submitter) {
      $form.find('input.js-duplicate-check-submitter').remove();
      if (submitter && submitter.name) {
        $('<input type="hidden" class="js-duplicate-check-submitter">')
          .attr('name', submitter.name)
          .val(submitter.value || '1')
          .appendTo($form);
      }
    }

    $form.on('submit', function (e) {
      if (confirmed) {
        confirmed = false;
        return;
      }

      if (!checks.length) {
        return;
      }

      var submitter = (e.originalEvent && e.originalEvent.submitter) || lastClickedSubmitter;

      e.preventDefault();

      function proceed() {
        confirmed = true;
        ensureSubmitterField(submitter);
        $form.trigger('submit');
      }

      Promise.all(checks.map(function (check) { return runCheck(check, $form); }))
        .then(function (results) {
          var messages = results
            .filter(function (res) { return res && res.duplicate; })
            .map(function (res) { return res.message; });

          if (!messages.length) {
            proceed();
            return;
          }

          showDuplicateBlocked(messages.join('\n\n'));
          // Blocked: no proceed() call here on purpose -- the form stays
          // un-submitted until the user changes the date/invoice number.
        });
    });
  }

  window.bindReimbursementDuplicateChecks = bindReimbursementDuplicateChecks;
})(window, jQuery);
