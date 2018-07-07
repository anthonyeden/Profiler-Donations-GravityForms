=== Profiler Donations for Gravity Forms ===
Contributors: anthonyeden
Tags: gravity-forms, fundraising, crm, donation
Requires at least: 4.6
Tested up to: 4.9
Stable tag: trunk
Requires PHP: 5.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Wordpress plugin to integrate your Gravity Forms with Profile IMS.

== Description ==

A Wordpress plugin to integrate your Gravity Forms with Profile IMS. You can send Donations and Interactions directly to Profiler from your Gravity Form. You can also use Profiler as a Payment Gateway!


== Installation ==

1. Install and activate the plugin
2. Configure the plugin, and Profiler, based on these instructions: https://github.com/anthonyeden/Profiler-Donations-GravityForms/blob/master/README.md

== Changelog ==

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