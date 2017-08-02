var secretPattern = new RegExp(/^[0-9a-f]{40}$/);
var oldSecretPattern = new RegExp(/^[0-9a-f]{40}$/);
var keyPattern = new RegExp(/^[0-9A-Za-z]{17}$/);
var SATOSHIPAY_LAST_API_KEY;
var SATOSHIPAY_LAST_API_SECRET;
var SATOSHIPAY_REMOTE_CHECK_DONE = false;
var SATOSHIPAY_REMOTE_CHECK_SUCCESSFUL = false;

SATOSHIPAY_LOG = false;

var wrapError = function (errorText) {
    return '<div id="setting-error-auth_key" class="error settings-error notice is-dismissible">' +
        '<p><strong><span class="satoshipay-settings-error">' + errorText +
        '</span></strong></p><button type="button" id="satoshipay-error-dismiss" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
};

var localKeyError = wrapError('API Key has wrong format');
var localSecretError = wrapError('API Secret has wrong format');
var remoteAuthError = wrapError('The API credentials you entered were rejected by the SatoshiPay server. Please enter valid API Key and API Secret.');

// Default provider API url
var providerApiUrl = 'https://api.satoshipay.io/v1';


var log = function (string) {
    if (SATOSHIPAY_LOG) {
        console.log(string);
    }
};

var activateButtons = function () {
    jQuery('#satoshipay-error-dismiss').on('click', function () {
        removeErrors();
    });
};

var addKeyError = function () {
    var errorHandle = jQuery('.satoshipay-error');
    errorHandle.html(localKeyError);
    activateButtons();
};
var addSecretError = function () {
    var errorHandle = jQuery('.satoshipay-error');
    errorHandle.html(localSecretError);
    activateButtons();
};
var addRemoteAuthError = function () {
    var errorHandle = jQuery('.satoshipay-error');
    errorHandle.html(remoteAuthError);
    activateButtons();
};
var localApiKeyCheck = function () {
    var apiKey = jQuery('#satoshipay_api_auth_key').val();
    if (!keyPattern.test(apiKey)) {
        addKeyError();
        return false;
    }
    return true;
};

var localApiSecretCheck = function () {
    var apiSecret = jQuery('#satoshipay_api_auth_secret').val();
    if (!secretPattern.test(apiSecret)) {
        addSecretError();
        return false;
    }
    return true;
};


var removeErrors = function () {
    jQuery('.satoshipay-error').html('');
};

jQuery(document).ready(function () {
    jQuery('#submit').removeAttr('name').removeAttr('id').attr('id', 'submit-button');

    jQuery('.wrap').find('h2').first().after('<div class="satoshipay-error"></div>');

    var apiSecret = jQuery('#satoshipay_api_auth_secret').val();
    var apiKey = jQuery('#satoshipay_api_auth_key').val();

    jQuery('form').submit(function (event) {
        removeErrors();
        if (!(localApiKeyCheck() && localApiSecretCheck())) {
            event.preventDefault();
            jQuery('html, body').animate({scrollTop:'0px'});
        }
    });
});
