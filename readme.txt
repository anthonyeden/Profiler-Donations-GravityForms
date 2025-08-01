=== Profiler Integration for Gravity Forms ===
Contributors: anthonyeden
Tags: gravity-forms, fundraising, crm, donation, profiler
Requires at least: 6.0
Tested up to: 6.7.1
Stable tag: trunk
Requires PHP: 7.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Wordpress plugin to integrate your Gravity Forms with Profiler CRM.

== Description ==

A Wordpress plugin to integrate your Gravity Forms with Profiler CRM. You can send Donations, Interactions, Mailing List Subscriptions, and more directly to Profiler from your Gravity Form. You can also use Profiler as a Payment Gateway!


== Installation ==

1. Install and activate the plugin
2. Configure the plugin, and Profiler, based on these instructions: https://support.profiler.net.au/kb/linking-a-payment-donation-gravity-form-to-profiler-using-the-plugin/

== Changelog ==

= 2.5.4 =

IMPORTANT: After upgrading, you will need to re-configure your 'Mailing Lists (Advanced)' feeds to re-add your mailing list codes. This is due to the removal of the UDF configuration.

* Mailing Lists (Advanced): Use the latest version of the Profiler API
* Mailing Lists (Basic): Use the latest version of the Profiler API
* WooCommerce: Send the order's Phone field to Profiler
* Fix a bug with duplicate phone field config

= 2.5.3 =

* Feed List: Don't show feeds for inactive forms and forms in the trash
* Fix a fatal error when using Custom Fields in the Donation feed

= 2.5.2 =

Please backup your site before upgrading, and test all your integrations thoroughly after upgrading this plugin. If you experience trouble, please roll back to a previous version of your site.

* Fix a problem in Gravity Forms v2.9.1 where a payment feed's Transaction ID wasn't being sent to Profiler

= 2.5.1 =

* Bug Fix: Fix a problem introduced in 2.5.0 when using Profiler as a Gateway

= 2.5.0 =

This is a substantial update. Please backup your site before upgrading, and test all your integrations thoroughly after upgrading this plugin.

* Use the new Profiler Core API for Donations/payments. This removes the UDF field config and automates it within Profiler.
* Create 'Profiler Feed List' setting screen, for quick access to all configured feeds on your site
* Remove Profiler Logs fields, and move these to an entry metabox.
* Donation Feed: Add Extra Comments field
* Donation Feed: Automatic formatting of Australian phone numbers
* Add Profiler icon to all Feeds
* Donation Feed: Add new fields for gender, interactions, roles, membership mapping and payment split text.
* Membership Feed: Hide manual payment fields if Profiler-driven payments have been disabled

= 2.4.3 =

* Add support for PayFURL
* Stripe: Fix off_session setting in latest Stripe Elements
* Update cacert.pem

= 2.4.2 =

* Add 'bankdeposit' payment method
* Add filter profiler_integration_allow_profiler_gateway, allowing the Gateway option to be disabled on a site
* WooCommerce: Fix a timing issue to ensure payment info is received before sending transaction data to Profiler

= 2.4.1 =

* Fix a bug with Stripe payments where failed payments may not return an error message
* Explicitly require the 'gravityforms_edit_settings' capability for Profiler feeds

= 2.4.0 =

* Allow using "Form Total" on all product field dropdowns, even if a custom total field is present
* Donate Feed: Handle payment processing on multi-page forms
* Donate Feed: Add support for Client Privacy field
* Fix some PHP warnings when loading config on the frontend
* WooCommerce - we now have very basic WooCommerce integration, allowing all transactions in a store to be sent to Profiler as one payment on one source code

= 2.3.0 =

* BREAKING CHANGE: If you use the post-donate feed, you must update your token generation code to use crypt() instead of password_hash()
* PHP 8 Compatibility
* No longer sends data to Profiler if GF's Payment_Status field is 'Failed'
* Catch empty data returned from process_feed_custom
* Adjust permissions needed to edit feed
* Stripe: Fix customer meta fields, and add charge description override

= 2.2.0 =

Always make a backup before updating, and test your website thoroughly after any update.

* NEW: Add Conditional Logic to all Feeds, for optional feed processing based on user-defined criteria
* NEW: Mailing List Basic: Add the Phone Number field
* NEW: Allow editing feed settings with capability 'gravityforms_edit_settings'
* FIX: profiler_sourcecode & profiler_integrationid meta field storage

= 2.1.0 =

* CAUTION: This is a major update. Please take a backup before upgrading, and test your forms thoroughly after performing the update.
* New Feature: Import Profiler Subscribers as Wordpress Users
* New Feature: Import Organisation to a CPT, in order to create a Directory of organisations
* New Feature: Membership Sign-ups & Renewals
* New Feature: Update Details for existing Profiler Clients
* New Feature: Send Custom Fields to Profiler UDFs in many types of data feeds
* New Feature: Stripe Customer & Card compatibility
* Various bug fixes and minor changes

= 2.0.0 =

* CAUTION: This is a major update. Please take a backup before upgrading, and test your forms thoroughly after performing the update. If you want to wait until after your EOFY to apply this update, we won't mind. You must be running Profiler 9 to use this new version.
* Support for the new Profiler 9 API endpoints
* Refactor the feeds so they are easier to test & maintain
* Add support for PayPal Standard Addon in Gravity Forms
* Add List feeds
* Check the XML module is installed in PHP, and warn if it is missing
* When using Profiler as a gateway, send the CCV field

= 1.4.0 =

* CAUTION: We now require Gravity Forms v2.3.0 (or newer)
* Post-Donate: Restrict the allowed comment characters to ASCII-only.
* Add 'Receipt Name' UDF.
* Always send a masked credit-card number to Profiler (and tidy up the logic for masking card numbers).
* Trim all field data before sending it to the Profiler APIs.
* Post-Donate: Redact some extra fields from logging.
* Don't attempt to process payments on the Profiler Payment API if it's a 'Bank Debit' transaction.

= 1.3.0 =

* CAUTION: We do not yet support Gravity Forms v2.3.0
* Add a new 'Interaction' feed, allowing you to create an Interaction in Profiler directly from a Gravity Form
* Add support for Mailing List subscriptions based off checkbox field selection
* Add new "Post-Donate" feed (to allow sending comments and mailing lists after the gift has been sent to Profiler)
* Allow selecting a Pledge Source Code from a form field
* Store the Holding ID, Gift Type and Source Code as Meta Fields in the GF Entry
* Allow pulling various codes from the $form object (so you can insert it using your own logic, without the need for sessions)