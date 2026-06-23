/**
 * IDR money formatting for travel reimbursement (BDC vs Cash).
 * BDC (credit card): 2 decimal places (183.472,66).
 * Cash: whole rupiah (183.473).
 */
(function (global) {
  'use strict';

  function isThousandsDotNotation(intPart, frac) {
    return intPart.length >= 1 && frac.length === 3 && /^\d{3}$/.test(frac);
  }

  function normalizeEuropeanNumberString(raw) {
    var x = String(raw || '').trim().replace(/\s/g, '');
    if (!x) return '0';
    var neg = false;
    if (x.charAt(0) === '-') {
      neg = true;
      x = x.slice(1);
    } else if (x.charAt(0) === '+') {
      x = x.slice(1);
    }
    if (!x) return '0';
    var lastC = x.lastIndexOf(',');
    var lastD = x.lastIndexOf('.');
    var out;
    if (lastC > lastD) {
      x = x.replace(/\./g, '').replace(',', '.');
      out = (x.replace(/[^\d.]/g, '') || '0');
    } else {
      x = x.replace(/,/g, '');
      var idx = x.lastIndexOf('.');
      if (idx === -1) {
        out = (x.replace(/[^\d]/g, '') || '0');
      } else {
        var intRaw = x.slice(0, idx);
        var frac = x.slice(idx + 1).replace(/\D/g, '');
        var intPart = intRaw.replace(/\./g, '');
        if (isThousandsDotNotation(intPart, frac)) {
          out = intPart + frac;
        } else {
          out = (intPart || '0') + (frac ? '.' + frac : '');
        }
      }
    }
    if (neg && out !== '0' && out !== '') {
      out = '-' + out;
    }
    return out;
  }

  function isBdcPayment(paymentType) {
    return String(paymentType || '').trim().toUpperCase() === 'BDC';
  }

  function roundIdrForPayment(num, paymentType) {
    var n = Number(num);
    if (isNaN(n)) return 0;
    if (isBdcPayment(paymentType)) {
      return Math.round(n * 100) / 100;
    }
    return Math.round(n);
  }

  function formatTravelIdrMoney(num, paymentType) {
    var n = roundIdrForPayment(num, paymentType);
    if (isBdcPayment(paymentType)) {
      return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    return n.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  }

  function parseTravelMoney(raw) {
    var canonical = normalizeEuropeanNumberString(String(raw || '').trim());
    var n = parseFloat(String(canonical || '0'));
    return isNaN(n) ? 0 : n;
  }

  function getPaymentTypeFromRow($tr) {
    if (!$tr || !$tr.length) return '';
    var $sel = $tr.find('select[name="payment_type[]"]');
    return $sel.length ? String($sel.val() || '').trim() : '';
  }

  function scopeHasBdcPayment($scope) {
    if (!$scope || !$scope.length) return false;
    var found = false;
    $scope.find('select[name="payment_type[]"]').each(function () {
      if (isBdcPayment($(this).val())) {
        found = true;
        return false;
      }
    });
    return found;
  }

  function formatTravelDayTotal(num, hasBdc) {
    return formatTravelIdrMoney(num, hasBdc ? 'BDC' : 'Cash');
  }

  global.isBdcPayment = isBdcPayment;
  global.roundIdrForPayment = roundIdrForPayment;
  global.formatTravelIdrMoney = formatTravelIdrMoney;
  global.parseTravelMoney = parseTravelMoney;
  global.normalizeEuropeanNumberString = normalizeEuropeanNumberString;
  global.getPaymentTypeFromRow = getPaymentTypeFromRow;
  global.scopeHasBdcPayment = scopeHasBdcPayment;
  global.formatTravelDayTotal = formatTravelDayTotal;
})(typeof window !== 'undefined' ? window : this);
