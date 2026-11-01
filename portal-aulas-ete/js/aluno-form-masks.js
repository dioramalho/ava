(function (root) {
  function maskCelular(value) {
    var digits = String(value || '').replace(/\D/g, '').slice(0, 11);
    if (!digits) {
      return '';
    }

    var out = '(' + digits.slice(0, Math.min(2, digits.length));
    if (digits.length < 2) {
      return out;
    }

    out += ')';
    if (digits.length === 2) {
      return out;
    }

    out += digits.slice(2, Math.min(7, digits.length));
    if (digits.length <= 7) {
      return out;
    }

    return out + '-' + digits.slice(7, 11);
  }

  function maskEmail(value) {
    var raw = String(value || '').toLowerCase().replace(/\s+/g, '');
    var clean = '';
    var hasAt = false;

    for (var i = 0; i < raw.length; i++) {
      var ch = raw.charAt(i);
      if (ch === '@') {
        if (hasAt || clean.length === 0) {
          continue;
        }
        hasAt = true;
        clean += ch;
        continue;
      }

      if (hasAt) {
        if (/[a-z0-9.\-]/.test(ch)) {
          clean += ch;
        }
        continue;
      }

      if (/[a-z0-9._\-]/.test(ch)) {
        clean += ch;
      }
    }

    return clean;
  }

  function emailValido(value) {
    if (!value || value.length > 120) {
      return false;
    }

    return /^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?@[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+$/.test(value);
  }

  var api = {
    maskCelular: maskCelular,
    maskEmail: maskEmail,
    emailValido: emailValido
  };

  root.PortalAlunoMasks = api;

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = api;
  }
})(typeof window !== 'undefined' ? window : this);
