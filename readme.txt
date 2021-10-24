=== Profiler Integration for Gravity Forms ===
Contributors: anthonyeden
Tags: gravity-forms, fundraising, crm, donation, profiler
Requires at least: 5.0
Tested up to: 5.8.1
Stable tag: trunk
Requires PHP: 7.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Wordpress plugin to integrate your Gravity Forms with Profiler CRM.

== Description ==

A Wordpress plugin to integrate your Gravity Forms with Profiler CRM. You can send Donations and Interactions directly to Profiler from your Gravity Form. You can also use Profiler as a Payment Gateway!


== Installation ==

1. Install and activate the plugin
2. Configure the plugin, and Profiler, based on these instructions: https://github.com/anthonyeden/Profiler-Donations-GravityForms/blob/master/README.md

== Changelog ==

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