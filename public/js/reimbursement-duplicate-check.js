/**
 * Validasi Tanggal Pengajuan & Validasi Invoice/Receipt: sebelum form
 * reimbursement benar-benar dikirim, cek ke server apakah tanggal dan/atau
 * nomor invoice/receipt yang diisi sudah pernah dipakai sebelumnya. Kalau
 * ada yang duplikat, tampilkan satu konfirmasi gabungan sebelum submit.
 */
(function (window, $) {
  function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || '';
  }

  function showDuplicateWarning(message) {
    return window.swal({
      title: 'Kemungkinan Duplikat',
      text: message,
      icon: 'warning',
      buttons: {
        cancel: 'Batal',
        confirm: { text: 'Tetap Ajukan', value: true }
      }
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

    $form.on('submit', function (e) {
      if (confirmed) {
        confirmed = false;
        return;
      }

      if (!checks.length) {
        return;
      }

      e.preventDefault();

      Promise.all(checks.map(function (check) { return runCheck(check, $form); }))
        .then(function (results) {
          var messages = results
            .filter(function (res) { return res && res.duplicate; })
            .map(function (res) { return res.message; });

          if (!messages.length) {
            confirmed = true;
            $form.trigger('submit');
            return;
          }

          showDuplicateWarning(messages.join('\n\n')).then(function (result) {
            if (result) {
              confirmed = true;
              $form.trigger('submit');
            }
          });
        });
    });
  }

  window.bindReimbursementDuplicateChecks = bindReimbursementDuplicateChecks;
})(window, jQuery);
