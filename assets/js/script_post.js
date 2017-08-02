var satoshiToBtc = function (satoshis) {
  return satoshis / Math.pow(10,8);
};

var fromSatoshis = function (satoshis, rate) {
  return (satoshiToBtc(satoshis) * rate).toFixed(6).replace(/\.?0+$/, "");
};

var Rates = function(currency) {
  this.currency = currency.toLowerCase();
  this.rate = jQuery.Deferred();
  this.fetch();
};

/**
 * Fetch exchange rate for a currency from external API (async).
 */
Rates.prototype.fetch = function() {
  var self = this;
  jQuery.get('https://bitpay.com/api/rates/' + this.currency, function(res) {
    self.rate.resolve(res.rate);
  });
};

/**
 * Converts satoshi amounts to fiat.
 * @param {number} satoshis Amount as integer
 * @return {number}
 */
Rates.prototype.fromSatoshis = function(satoshis) {
  var Promise = jQuery.Deferred();
  this.rate.done(function (rate) {
    Promise.resolve(fromSatoshis(satoshis, rate));
  });
  return Promise.promise();
};

var convertEur = new Rates('eur');

jQuery(document).ready(function() {
  var satoshis = jQuery('#satoshipay_pricing_satoshi').val() || 8000;
  convertEur.fromSatoshis(satoshis).done(function (eur) {
    jQuery('#satoshipay_pricing_satoshi_fiat').html(satoshis + ' Satoshi &cong; ' + eur + '&euro;');
  });
  jQuery('#satoshipay_pricing_satoshi').on('keyup', function (event) {
    var satoshis = event.target.value;
    var max_limit = 2e6;

    if (satoshis > max_limit) {
      event.target.value = satoshis = max_limit;
    }

    convertEur.fromSatoshis(satoshis).done(function (eur) {
      jQuery('#satoshipay_pricing_satoshi_fiat').html(satoshis + ' Satoshis &cong; ' + eur + '&euro;');
    });
  });
});