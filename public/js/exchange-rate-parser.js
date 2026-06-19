/**
 * Exchange rate input parsing for travel reimbursement forms.
 * Aligns with App\Support\ExchangeRateParser (17.883 = 17883 IDR, 12,89 = 12.89).
 */
(function (global) {
  'use strict';

  function isThousandsDotNotation(intPart, frac) {
    return intPart.length >= 1 && frac.length === 3 && /^\d{3}$/.test(frac);
  }

  function normalizeExchangeRateCanonicalString(raw) {
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
      var dotCount = (x.match(/\./g) || []).length;
      if (dotCount > 1) {
        out = (x.replace(/\./g, '').replace(/[^\d]/g, '') || '0');
      } else {
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
    }
    if (neg && out !== '0' && out !== '') {
      out = '-' + out;
    }
    return out;
  }

  function sanitizeExchangeRateInput(value, finalize) {
    var s = (value || '').toString().trim().replace(/\s/g, '');
    if (!s) return '';
    var lastC = s.lastIndexOf(',');
    var lastD = s.lastIndexOf('.');
    if (lastC > lastD) {
      s = s.replace(/\./g, '').replace(',', '.');
    } else {
      s = s.replace(/,/g, '');
    }
    s = s.replace(/[^0-9.]/g, '');
    var firstDot = s.indexOf('.');
    if (firstDot !== -1) {
      s = s.slice(0, firstDot + 1) + s.slice(firstDot + 1).replace(/\./g, '');
    }
    var parts = s.split('.');
    var intPart = parts[0] || '';
    var decPart = parts[1] || '';
    if (decPart.length > 6) {
      decPart = decPart.slice(0, 6);
    }
    if (finalize && intPart.length > 1) {
      intPart = intPart.replace(/^0+/, '') || '0';
    }
    if (finalize && parts.length > 1 && parts[1] === '' && s.slice(-1) === '.') {
      return intPart;
    }
    return parts.length > 1 ? (intPart + '.' + decPart) : intPart;
  }

  function normalizeExchangeRateValue(value) {
    var s = sanitizeExchangeRateInput(value, true);
    if (s === '') return '0,00';
    var canonical = normalizeExchangeRateCanonicalString(s);
    var n = parseFloat(canonical);
    if (isNaN(n)) return '0,00';
    return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function parseExchangeRateNumber(value) {
    var canonical = normalizeExchangeRateCanonicalString(String(value || '').trim());
    var n = parseFloat(canonical);
    return isNaN(n) ? 0 : n;
  }

  global.normalizeExchangeRateCanonicalString = normalizeExchangeRateCanonicalString;
  global.sanitizeExchangeRateInput = sanitizeExchangeRateInput;
  global.normalizeExchangeRateValue = normalizeExchangeRateValue;
  global.parseExchangeRateNumber = parseExchangeRateNumber;
})(typeof window !== 'undefined' ? window : this);
