=== Integrate Flodesk and WPForms ===
Contributors: cultivatewp, billerickson
Donate link: https://cultivatewp.com
Tags: form, wpforms, flodesk, email, marketing
Requires at least: 5.0
Tested up to: 6.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create Flodesk signup forms using WPForms

== Description ==

[Flodesk](https://flodesk.com/c/63SZN2) lets you design beautiful emails and create high converting checkout pages.

This plugin integrates [WPForms](https://cultivatewp.com/go/wpforms/) with [Flodesk](https://flodesk.com/c/63SZN2), allowing form submissions to be automatically sent to your Flodesk account.

Full plugin documentation is located [here](https://cultivatewp.com/our-plugins/integrate-flodesk-wpforms).

== Installation ==

1. Upload the `integrate-flodesk-wpforms` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Configuration ==

1. Configure the plugin by navigating to WPForms > Settings > Integrations > Flodesk in the WordPress Administration Menu, entering your [API Key](https://app.flodesk.com/account/integration/api)
2. Configure sending WPForms Form Entries to Flodesk, by editing your WPForms Form, and navigating to Marketing > Flodesk within the Form.

== Frequently asked questions ==

= Does this plugin require a paid service? =

Yes, you'll need a Flodesk Email account which is $35/month for unlimited emails and subscribers. When you [sign up](https://flodesk.com/c/63SZN2) you get one month free, so you can test Flodesk and this plugin for one month with no credit card required.

You can use the free [WPForms Lite](https://wordpress.org/plugins/wpforms-lite/) plugin, but we recommend using a [paid version of WPForms](https://cultivatewp.com/go/wpforms/) so you'll have a saved copy of all submissions on your site in case there's an issue with your API connection.

== Screenshots ==

1. WPForms Flodesk API Connections at WPForms > Settings > Integrations > Flodesk
2. WPForms Flodesk Form Settings when editing a WPForms Form at Marketing > Flodesk

== Changelog ==

= 1.1.1 =
* Only create an array for custom fields if there is at least one custom field.

= 1.1.0 =
* Added the ability to choose Flodesk segments for a WPForm form
* Added mapping of custom fields to WPForm fields

= 1.0.0 =
* Initial release
