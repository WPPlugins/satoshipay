<div id="satoshipay-ad-blocker-bait" class="pub_300x250 pub_300x250m pub_728x90 text-ad textAd text_ad text_ads text-ads text-ad-links" style="width: 1px !important; height: 1px !important; position: absolute !important; left: -10000px !important; top: -1000px !important;"></div>
<script>
var cookieValue = document.cookie.replace(/(?:(?:^|.*;)\s*{{cookie_name}}\s*\=\s*([^;]*).*$)|^.*$/, "$1");
var baitElement = document.getElementById('satoshipay-ad-blocker-bait');
var wasAdBlockerEnabled = (cookieValue == 'true') ? true : false;
var isAdBlockerEnabled = false;
function checkAdBlocker() {
  isAdBlockerEnabled = ((getComputedStyle(baitElement)['display'] == 'none') || (baitElement.offsetParent === null)) ? true : false;
  document.cookie = '{{cookie_name}}=' + isAdBlockerEnabled;
  if (wasAdBlockerEnabled != isAdBlockerEnabled) {
    window.location.reload(true);
  }
}
if (!/firefox|ucbrowser|trident|edge/i.test(navigator.userAgent)) {
  checkAdBlocker();
} else {
  if (window.addEventListener !== undefined) {
    window.addEventListener('load', function() { setTimeout(checkAdBlocker, 1); }, false);
  } else {
    window.attachEvent('onload', function() { setTimeout(checkAdBlocker, 1); });
  }
}
</script>
