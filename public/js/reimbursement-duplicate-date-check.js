/**
 * Validasi Tanggal Pengajuan: sebelum form reimbursement benar-benar dikirim,
 * cek ke server apakah tanggal yang sama sudah pernah diajukan user ini untuk
 * jenis reimbursement yang sama. Jika ya, tampilkan konfirmasi sebelum lanjut submit.
 */
(function (window, $) {
  function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || '';
  }

  function showDuplicateWarning(message) {
    return window.swal({
      title: 'Tanggal Sudah Pernah Diajukan',
      text: message || 'Anda sudah pernah mengajukan reimbursement untuk tanggal ini sebelumnya.',
      icon: 'warning',
      buttons: {
        cancel: 'Batal',
        confirm: { text: 'Tetap Ajukan', value: true }
      }
    });
  }

  function bindReimbursementDuplicateDateCheck(options) {
    options = options || {};
    var $form = $(options.formSelector || '#sample_form');
    if (!$form.length || typeof window.swal !== 'function') {
      return;
    }

    var checkUrl = options.checkUrl;
    var dateSelector = options.dateSelector || 'input[name="date"]';
    var getDates = typeof options.getDates === 'function'
      ? options.getDates
      : function () {
          var value = $form.find(dateSelector).first().val();
          return value ? [value] : [];
        };
    var confirmed = false;

    $form.on('submit', function (e) {
      if (confirmed) {
        confirmed = false;
        return;
      }

      var dates = getDates();
      if (!dates.length || !options.reimbursementType) {
        return;
      }

      e.preventDefault();

      $.post(checkUrl, {
        _token: csrfToken(),
        reimbursement_type: options.reimbursementType,
        dates: dates
      }).always(function (res) {
        if (res && res.duplicate) {
          showDuplicateWarning(res.message).then(function (result) {
            if (result) {
              confirmed = true;
              $form.trigger('submit');
            }
          });
        } else {
          confirmed = true;
          $form.trigger('submit');
        }
      });
    });
  }

  window.bindReimbursementDuplicateDateCheck = bindReimbursementDuplicateDateCheck;
})(window, jQuery);
