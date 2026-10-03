
(function () {
    // ---- Simulated "existing claims already in the system" ----
    // Each blocks a 3-day window starting at its own date (date, date+1, date+2).
    var EXISTING_CLAIMS = [
        { date: '2026-09-18', invoice: 'INV-88213', owner: 'Andi Wijaya' },
        { date: '2026-09-25', invoice: 'INV-90044', owner: 'Siti Rahma' }
    ];

    function addDays(isoDate, days) {
        var d = new Date(isoDate + 'T00:00:00');
        d.setDate(d.getDate() + days);
        return d.toISOString().slice(0, 10);
    }

    function fmtId(isoDate) {
        var d = new Date(isoDate + 'T00:00:00');
        var bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        return d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
    }

    function findBlockingClaim(isoDate) {
        for (var i = 0; i < EXISTING_CLAIMS.length; i++) {
            var c = EXISTING_CLAIMS[i];
            var windowStart = c.date;
            var windowEnd = addDays(c.date, 2);
            if (isoDate >= windowStart && isoDate <= windowEnd) {
                return { claim: c, windowStart: windowStart, windowEnd: windowEnd };
            }
        }
        return null;
    }

    // ---- 1) Date-blocking on Transaction Date ----
    var dateInput = document.getElementById('sim-date');
    var dateFeedback = document.getElementById('sim-date-feedback');
    var submitBtn = document.getElementById('action_button_submit');

    dateInput.addEventListener('change', function () {
        var val = dateInput.value;
        dateFeedback.className = 'date-block-feedback';
        dateInput.classList.remove('date-blocked');
        if (!val) { return; }

        var hit = findBlockingClaim(val);
        if (hit) {
            dateInput.classList.add('date-blocked');
            dateFeedback.className = 'date-block-feedback is-blocked';
            dateFeedback.innerHTML = '🚫 Tanggal ini berada dalam rentang 3 hari (' + fmtId(hit.windowStart) + ' – ' + fmtId(hit.windowEnd) + ')'
                + ' milik klaim lain <b>' + hit.claim.invoice + '</b> (a.n. ' + hit.claim.owner + '). Tanggal diblokir, silakan pilih tanggal lain.';
            submitBtn.setAttribute('disabled', 'disabled');
        } else {
            dateFeedback.className = 'date-block-feedback is-ok';
            dateFeedback.textContent = '✔ Tanggal aman, tidak bentrok dengan klaim manapun.';
        }
    });

    // ---- 2) OCR simulation on a row -> writes a date-range notice into Remarks ----
    document.querySelectorAll('.ocr-simulate-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.getAttribute('data-row');
            var checkin = btn.getAttribute('data-sim-checkin');
            var checkout = btn.getAttribute('data-sim-checkout');
            var statusCard = document.querySelector('.ocr-status-card[data-row-status="' + row + '"]');
            var remarksInput = document.querySelector('.remarks-input[data-row="' + row + '"]');

            statusCard.className = 'ocr-status-card show pending';
            statusCard.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Membaca struk (OCR)...';
            btn.setAttribute('disabled', 'disabled');

            setTimeout(function () {
                var msStart = new Date(checkin + 'T00:00:00');
                var msEnd = new Date(checkout + 'T00:00:00');
                var nights = Math.round((msEnd - msStart) / 86400000);

                var hit = findBlockingClaim(checkin) || findBlockingClaim(checkout);

                statusCard.className = 'ocr-status-card show found';
                statusCard.innerHTML = '✔ OCR terbaca: ' + fmtId(checkin) + ' – ' + fmtId(checkout) + ' (' + nights + ' hari)'
                    + (hit ? '<br><span style="color:#b02a37;">⚠ bentrok dgn ' + hit.claim.invoice + '</span>' : '');

                var notice = '📌 OCR mendeteksi durasi ' + nights + ' hari (' + fmtId(checkin) + '-' + fmtId(checkout) + ') dari bukti.';
                if (hit) {
                    notice += ' ⚠ Rentang ini bentrok dengan klaim ' + hit.claim.invoice + ' (a.n. ' + hit.claim.owner + ').';
                }

                if (remarksInput.value.trim() === '') {
                    remarksInput.value = notice;
                } else if (remarksInput.value.indexOf('OCR mendeteksi') === -1) {
                    remarksInput.value = remarksInput.value + '  ' + notice;
                }
                remarksInput.classList.add('remarks-ocr-flag');
                btn.removeAttribute('disabled');
            }, 700);
        });
    });

    // ---- Shared popup helper (Case 1) ----
    function showModal(title, message) {
        document.getElementById('sim-modal-title').textContent = title;
        document.getElementById('sim-modal-message').innerHTML = message;
        document.getElementById('sim-modal-overlay').classList.add('show');
    }
    document.getElementById('sim-modal-close').addEventListener('click', function () {
        document.getElementById('sim-modal-overlay').classList.remove('show');
    });

    function findClaimByInvoice(invoice) {
        invoice = (invoice || '').trim().toLowerCase();
        if (!invoice) { return null; }
        for (var i = 0; i < EXISTING_CLAIMS.length; i++) {
            if (EXISTING_CLAIMS[i].invoice.toLowerCase() === invoice) {
                return EXISTING_CLAIMS[i];
            }
        }
        return null;
    }

    // ---- Case 1: popup on duplicate invoice ----
    document.getElementById('case1-fill-dup').addEventListener('click', function () {
        document.getElementById('case1-invoice').value = 'INV-88213';
        document.getElementById('case1-date').value = '2026-09-30';
    });
    document.getElementById('case1-fill-new').addEventListener('click', function () {
        document.getElementById('case1-invoice').value = 'INV-55501';
        document.getElementById('case1-date').value = '2026-09-19';
    });
    document.getElementById('case1-submit-btn').addEventListener('click', function () {
        var invoice = document.getElementById('case1-invoice').value.trim();
        var dateVal = document.getElementById('case1-date').value;
        var fb = document.getElementById('case1-feedback');
        if (!invoice) {
            fb.className = 'date-block-feedback is-blocked';
            fb.textContent = 'Isi No. Invoice dulu.';
            return;
        }
        var match = findClaimByInvoice(invoice);
        if (match) {
            showModal('Invoice ini sama dengan invoice yang sebelumnya.',
                'No. Invoice <b>' + invoice + '</b> sudah diajukan sebelumnya oleh <b>' + match.owner + '</b> pada tanggal ' + fmtId(match.date) + '.'
                + (dateVal ? ' (Tanggal pengajuan Anda sekarang: ' + fmtId(dateVal) + ')' : ''));
            fb.className = 'date-block-feedback is-blocked';
            fb.textContent = '🚫 Duplikat terdeteksi — lihat popup.';
        } else {
            fb.className = 'date-block-feedback is-ok';
            fb.textContent = '✔ Invoice belum pernah dipakai, aman diajukan.';
        }
    });

    // ---- Case 2: same invoice, different date -> still rejected ----
    document.getElementById('case2-submit-btn').addEventListener('click', function () {
        var invoice = document.getElementById('case2-invoice').value.trim();
        var dateVal = document.getElementById('case2-date').value;
        var fb = document.getElementById('case2-feedback');
        var match = findClaimByInvoice(invoice);
        if (match) {
            showModal('Pengajuan Anda tidak bisa diajukan.',
                'Invoice <b>' + invoice + '</b> sudah digunakan oleh <b>' + match.owner + '</b> pada tanggal ' + fmtId(match.date)
                + ', meskipun tanggal pengajuan Anda sekarang (' + (dateVal ? fmtId(dateVal) : '-') + ') berbeda.');
            fb.className = 'date-block-feedback is-blocked';
            fb.textContent = '🚫 Ditolak — lihat popup.';
        } else {
            fb.className = 'date-block-feedback is-ok';
            fb.textContent = '✔ Invoice belum pernah dipakai, pengajuan bisa dilanjutkan.';
        }
    });

    // ---- Case 3: two users same time + same invoice -> Trip Type auto/disabled from Mess ----
    document.getElementById('case3-check-btn').addEventListener('click', function () {
        var aStart = document.getElementById('case3-a-start').value;
        var aEnd = document.getElementById('case3-a-end').value;
        var bStart = document.getElementById('case3-b-start').value;
        var bEnd = document.getElementById('case3-b-end').value;
        var aInv = document.getElementById('case3-a-invoice').value.trim();
        var bInv = document.getElementById('case3-b-invoice').value.trim();
        var fb = document.getElementById('case3-relation-feedback');
        var tripType = document.getElementById('case3-trip-type');
        var tripNote = document.getElementById('case3-trip-type-note');

        var sameInvoice = aInv !== '' && aInv.toLowerCase() === bInv.toLowerCase();
        var overlap = !!(aStart && aEnd && bStart && bEnd && aStart <= bEnd && bStart <= aEnd);

        if (sameInvoice && overlap) {
            fb.className = 'date-block-feedback is-blocked';
            fb.style.color = '#0a58ca';
            fb.innerHTML = '🔗 Terdeteksi relasi: pengajuan Anda berkaitan dengan pengajuan <b>Siti Rahma</b> — invoice sama ('
                + aInv + ') dan periode dinas tumpang tindih.';

            tripType.value = '18';
            tripType.setAttribute('disabled', 'disabled');
            tripNote.className = 'date-block-feedback is-ok';
            tripNote.textContent = '✔ Trip Type otomatis = Stay(MESS). Field dikunci karena allowance memakai invoice yang sama dengan user lain.';
        } else {
            fb.className = 'date-block-feedback is-ok';
            fb.style.color = '';
            fb.textContent = 'Tidak ada relasi terdeteksi (invoice beda dan/atau periode tidak tumpang tindih). Trip Type tetap bisa diisi manual.';

            tripType.removeAttribute('disabled');
            tripNote.className = 'date-block-feedback';
            tripNote.textContent = '';
        }
    });

    // ---- Case 4: OCR result on a NEW (non-duplicate) invoice -> success wording, not just "membaca..." ----
    document.getElementById('case4-fill-new').addEventListener('click', function () {
        document.getElementById('case4-invoice').value = 'INV-99871';
    });
    document.getElementById('case4-fill-dup').addEventListener('click', function () {
        document.getElementById('case4-invoice').value = 'INV-88213';
    });
    document.getElementById('case4-ocr-btn').addEventListener('click', function () {
        var invoice = document.getElementById('case4-invoice').value.trim();
        var status = document.getElementById('case4-status');
        var btn = this;

        if (!invoice) {
            status.className = 'ocr-status-card show';
            status.style.borderColor = '#dc3545';
            status.style.background = '#fff5f5';
            status.style.color = '#b02a37';
            status.textContent = 'Isi No. Invoice dulu.';
            return;
        }

        status.className = 'ocr-status-card show pending';
        status.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Membaca struk (OCR)...';
        btn.setAttribute('disabled', 'disabled');

        setTimeout(function () {
            var match = findClaimByInvoice(invoice);
            if (match) {
                showModal('Invoice ini sama dengan invoice yang sebelumnya.',
                    'No. Invoice <b>' + invoice + '</b> sudah diajukan sebelumnya oleh <b>' + match.owner + '</b> pada tanggal ' + fmtId(match.date) + '.');
                status.className = 'ocr-status-card show';
                status.style.borderColor = '#dc3545';
                status.style.background = '#fff5f5';
                status.style.color = '#b02a37';
                status.innerHTML = '🚫 OCR: invoice yang sama terdeteksi — lihat popup.';
            } else {
                status.className = 'ocr-status-card show found';
                status.style.borderColor = '';
                status.style.background = '';
                status.style.color = '';
                status.innerHTML = '✔ OCR: Invoice baru terdeteksi (<b>' + invoice + '</b>) — tidak mendeteksi invoice yang sama. Pengajuan bisa dilanjutkan.';
            }
            btn.removeAttribute('disabled');
        }, 700);
    });

    // ---- Optional autorun for documentation screenshots: ?demo=case1-duplicate|case1-new|case2-blocked|case4-new|case4-duplicate ----
    var demo = new URLSearchParams(location.search).get('demo');
    if (demo) {
        setTimeout(function () {
            if (demo === 'case1-duplicate') {
                document.getElementById('case1-invoice').value = 'INV-88213';
                document.getElementById('case1-date').value = '2026-09-30';
                document.getElementById('case1-submit-btn').click();
            } else if (demo === 'case1-new') {
                document.getElementById('case1-invoice').value = 'INV-55501';
                document.getElementById('case1-date').value = '2026-09-19';
                document.getElementById('case1-submit-btn').click();
            } else if (demo === 'case2-blocked') {
                document.getElementById('case2-invoice').value = 'INV-88213';
                document.getElementById('case2-date').value = '2026-09-30';
                document.getElementById('case2-submit-btn').click();
            } else if (demo === 'case4-new') {
                document.getElementById('case4-invoice').value = 'INV-99871';
                document.getElementById('case4-ocr-btn').click();
            } else if (demo === 'case4-duplicate') {
                document.getElementById('case4-invoice').value = 'INV-88213';
                document.getElementById('case4-ocr-btn').click();
            }
        }, 50);
    }
})();
