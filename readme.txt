=== SatoshiPay ===

Contributors: satoshipay
Tags: micropayments, bitcoin, blockchain, paypal, paywall, paid content, paid downloads, payment, satoshipay, nanopayments, widget, adblocking, digital goods
Requires at least: 4.4.5
Tested up to: 4.7.5
Stable tag: 0.8
License: MIT
License URI: https://opensource.org/licenses/MIT

Adds SatoshiPay to your site, allowing you to charge small amounts for posts, images, audios, videos or downloads using nanopayments.

== Description ==

SatoshiPay is a cross-website, 1-click content payment service based on blockchain technology. To use SatoshiPay your readers don't need to sign up anywhere or download any additional software. If they come to your site with a pre-filled wallet, they will be able to pay for your content with just a single click. Generating extra income with your content through micro or nanopayments has never been this easy!

In addition to charging per post, image, audio, video or download, you can also enable an ad blocker detection, which asks users that have an ad blocker installed for a payment on each post they visit. You can set your own price for this. We recommend to set it low (e.g. 2000 satoshis) so users are more likely to pay.

As a publisher you only need to install the plugin, register at [SatoshiPay Dashboard](https://dashboard.satoshipay.io/sign-up), create a blockchain wallet for your earnings, and you are ready to go.

== Installation ==

* Upload plugin files to your plugins folder, or install using WordPress built-in Add New Plugin installer;
* Activate the plugin;
* Select SatoshiPay from the left-hand admin menu and enter your SatoshiPay API credentials (find them in the API section of the [SatoshiPay Dashboard](https://dashboard.satoshipay.io)).

== Frequently Asked Questions ==

= How do I add paid content? =

There are two options for paid content:

1. Paid posts: Simply edit the post or page you want to charge for, activate the "Paid Post" checkbox in the SatoshiPay metabox on the right, set a price (for example 4000 satoshis - that's less than 15c), and press Publish/Update. Your post will now show the SatoshiPay payment interface to your reader and will only be accessible after the reader pays for it.

2. Paid media inside a post: Edit a post and select "Add Paid Audio" from the SatoshiPay menu in the top toolbar of the visual editor. The media library will open and let you pick an existing audio file or upload one. Select an audio file and press "Insert". The next dialog will let you set a price (for example 8000 satoshis - that's less than 30c). Press "OK" to insert the paid audio. The editor will display a placeholder where the audio will appear on your post. Click the placeholder to edit options or remove the paid audio from the post. The procedure is very similar for paid downloads, images and videos. Try it out!

= How do I show a free teaser of my paid post? =

Use the Start Tag. When editing a post in the visual editor, place your cursor at the position your free teaser text ends and the paid text should begin. Now open the SatoshiPay menu in the top toolbar and select "Insert Start Tag". A horizontal dotted line with the text "SatoshiPay Start" will be inserted. Move this line around to change the starting point of the paid text.

Note: If no Start Tag is inserted, the whole post will become paid content. Also, make sure to have the "Paid Post" checkbox enabled to activate SatoshiPay for a post.

In addition to the Start Tag you can use an excerpt. When editing a post you can enter your teaser in the Excerpt box underneath the post edit box. The excerpt text will always be shown for free when viewing the post. Your theme needs to support excerpts.
If you don't see the Excerpt box when editing, activate Screen Options > Boxes > Excerpt.

= Will SatoshiPay work well with my other plugins? =

Yes. We ensured that our plugin is compatible with the most popular WordPress plugins. However, there are a few known issues:

**Duplicate Post plugin**

Issue: When using the [Duplicate Post plugin](https://wordpress.org/plugins/duplicate-post/), the duplicate of a SatoshiPay enabled post will not show up when displayed on the same page as the original.

Solution: Unfortunately there is currently no workaround for this, so you can't duplicate SatoshiPay enabled posts.

**W3 Total Cache plugin**

Issue: When using the [W3 Total Cache plugin](https://wordpress.org/plugins/w3-total-cache/), SatoshiPay's ad blocker detection feature will not work.

Solution: Disable Page Cache (W3 Total Cache settings > General Settings > Disable Page cache). You can enable all other caching features.

= Where can I find more documentation on SatoshiPay? =

Go to the [FAQ section](https://satoshipay.io/faq) on our website.

== Screenshots ==

1. NEW: Inserting a paid video into a post.
2. NEW: Setting price for paid video.
3. NEW: View post with paid video and SatoshiPay widget.
4. Editing and pricing a post.
5. Define free-to-read teaser via Start Tag.
6. Post with masked text, price tag and SatoshiPay widget.
7. Opened SatoshiPay widget.
8. Top-up dialog with PayPal support.
9. Post after payment.
10. SatoshiPay settings.

== Changelog ==

= 0.8 =

* Added support for paid audios, downloads, images and videos.
* Improved input handling for prices and settings.

= 0.7.1 =

* Fixed CSS cache issue, SatoshiPay icon now shows in editor.

= 0.7 =

* Added ability to define free-to-read teasers for each page.
* Improved compatibility by switching to new [Digital Goods API](http://docs.satoshipay.io/api/).

= 0.6.2 =

* Added price limit of 2MM satoshis per article.
* Added link to settings in plugin list.
* Improved API communication.
* Fixed delay when enabling ad blocker detection on blogs with many posts.

= 0.6.1 =

* Added known issues to FAQ.
* Fixed compatibility issue with Jetpack plugin.

= 0.6 =

* Added ad blocker detection feature.
* Added Euro conversion when adding a price to a post.
* Improved API settings validation.

= 0.5.2 =

* Improved API communication.
* Fixed minor bugs.

= 0.5.1 =

* Added screenshots.
* Added more detailed plugin description.
* Added plugin directory icon and banner.

= 0.5 =

* Initial release.
