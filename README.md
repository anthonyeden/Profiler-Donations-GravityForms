# Profiler Donations / Gravity Forms - Wordpress Plugin
A Wordpress plugin to integrate your Gravity Forms with Profile IMS. This is an unofficial plugin, not created by the developers of Profiler IMS.

This plugin is free software, distributd under the GPLv2 license. See the full terms of the license in the file `LICENSE.md` and the disclaimer at the bottom of this `README.md` file.

## Setup

This guide will help you configure your Profiler system for RAPID integration with the Wordpress Gravity Forms plugin.

### Prerequisites

*	[Wordpress](https://wordpress.org) site with Administrative access
*	[Gravity Forms](http://www.gravityforms.com)
*	Payment Gateway integration with Gravity Forms (must be pre-existing and use standard Gravity Forms payment hooks and database fields)
  * For Australian non-profits, we recommend the [Gravity Forms eWay plugin](https://wordpress.org/plugins/gravityforms-eway/)
*	[Profiler](https://profiler.net.au) v7.3 or higher
*	Full administrative access to Profiler
*	cURL Enabled on Web Server

### 1. Installing the Gravity Forms plugin

1.  Download the [latest release](https://github.com/anthonyeden/Profiler-Donations-GravityForms) of this plugin
1.	Login to your Wordpress site
2.	Navigate to the Plugins page
3.	Click “Add New” (in the header)
4.	Click “Upload Plugin” (in the header)
5.	Choose the “profiler-donations-gf.zip” file and press “Upload Now”
6.	Click “Activate Plugin”

### 2. Configure your Donation Form

Setup your donation form with the following fields:

| Field Name         | Type                                         | Notes                                                                                                                    |
| ------------------ | -------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| Donation Type      | Standard > Radio Buttons                     | Two values: "once" and "regular"                                                                                         |
| Donation Amount    | Pricing > Option (Radio Buttons)             | Required.                                                                                                                |
| Other Amount       | Pricing > Product                            | Required. Conditional Logic to only display if "Other" selected in "Donation Amount" field.                              |
| Regular Frequency  | Standard > Hidden *Or* Standard > Radio      | Two possible values: 'monthly' or 'yearly'. Conditional Logic to only display if "regular" is selected in Donation Type. |
| Credit Card        | Pricing > Credit Card                        | Required.                                                                                                                |
| First Name         | Standard > Single Line Text                  | Required.                                                                                                                |
| Last Name          | Standard > Single Line Text                  | Required.                                                                                                                |
| Organisation       | Standard > Single Line Text                  | Optional.                                                                                                                |
| Email Address      | Advanced > Email                             | Required.                                                                                                                |
| Address            | Advanced > Address                           | Required. Address Type: Australia. Default Country: Australia. Disable "Street Address 2".                               |
| Phone Number       | Advanced > Phone                             | Phone Format: International.                                                                                             |
| Comments           | Standard > Paragraph Text                    | Optional.                                                                                                                |
| Profiler Logs      | Standard > Hidden                            |                                                                                                                          |


### 3. Profiler Setup

1.	Login to Profiler
2.	Go to *Setup > System Wide Settings > System Wide Settings*
3.	If "API User" and "API Password" are blank, press “Create API Access”
4.	Make note of the following fields on this screen:
 1.	API User
 2.	API Password
 3.	DB Name
5.	Go to *Setup > Donation & Pledge Attributes/Settings > Source Codes*
 a.	Create or find a Source Code to use for once-off donations and a Source Code to use for recurring pledges (make note of these)
6.	Go to *Setup > Donation & Pledge Attributes/Settings > Pledge Acquire Codes*
 1.	Create or find an Acquisition Code for regular pledges (make a note of this code).
7.	Go to *Setup > System Parameters > Special Event Settings*
8.	Under the heading "Website RAPID", set the following field values and save your changes:
 1.	Enable XML Rapid = Yes
 2.	External Matching = Yes
 3.	Auto-Approve Cmmnts = No
 4.	Watchdog Time-Out = 5 (minutes)
 5.	Approval Score = 5
 6.	Auto Don/Pledge Create = Donations
 7.	Pledge Method = Website
9.	Go to *Setup > Integration/3rd Party Parameters > General Settings*, set the following fields and save your changes:
 1.	Source Code = (your source for general web donations)
 2.	Donation Pay Method = Credit Card
 3.	Comment Type = (Web Comment reason – create one if it doesn’t already exist)
 4.	Show New Int. Waiting = Yes
 5.	No Gateway Needed = Yes
10.	Go to *Setup > Integration/3rd Party Parameters > User Defined Field Maps*, set the following fields (these fields are saved automatically)
 1.	User Defined 1 = Donation Source Code
 2.	User Defined 2 = Gateway Response
 3.	User Defined 3 = Client IP Address
 4.	User Defined 4 = Pledge Source Code
 5.	User Defined 5 = Pledge Acquisition Code

### 4. Configure the Gravity Forms Profiler Feed

1.	Go to *Form Settings > Profiler Donations*
2.	Click "Add New"
3.	Fill out the fields as follows:

| Setting Name                             | How to set this field                                                                                                                                                              |
| ---------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Profiler Server Address                  | An address in this format, with your Profiler database name substituted in: "https://DATABASE.profiler.net.au/ProfilerPROG/api/api_call.cfm"                                       |
| Profiler Database Name                   | The database name found in Step 3.d.c.                                                                                                                                             |
| Profiler API Key                         | The API Key found in Step 3.d.a.                                                                                                                                                   |
| Profiler API Password                    | The API Password found in Step 3.d.b.                                                                                                                                              |
| Profiler Errors Email Address            | An email address you want error messages to be emailed to. Emails sent to this address will need to be manually entered into Profiler as the integration failed.                   |
| Amount Field                             | In 99% of setups, this will need to be set to “Total”.                                                                                                                             |
| Donation Type                            | The Donation Type field you setup in Step 2.                                                                                                                                       |
| Pledge Frequency                         | The Pledge Frequency field you setup in Step 2. This field’s value needs to be “monthly” or “yearly”. If you don’t want to give users the option, set a hidden field to “monthly”. |
| Pledge Amount                            | In 99% of setups, this will need to be set to “Total”.                                                                                                                             |
| Client fields                            | Map all these fields to the corresponding form fields. If you don’t need a field, select nothing.                                                                                  |
| Comments                                 | Map this to the user’s comment field (or leave this empty if you don’t want to accept comments).                                                                                   |
| UDF: Donation Source Code                | User Defined 1. |                                                                                                                                                                  |
| Source Code – Default Value              | The default donation source code you found in Step 3.e.                                                                                                                            |
| UDF: Pledge Source Code                  | User Defined 4.                                                                                                                                                                    |
| Pledge Source Code – Default Value       | The default recurring pledge source code you found in Step 3.e.                                                                                                                    |
| UDF: Pledge Acquisition Code             | User Defined 5.                                                                                                                                                                    |
| Pledge Acquisition Code – Default Value  | The pledge acquisition source code you found in Step 3.f.                                                                                                                          |
| UDF: Client IP Address                   | User Defined 3.                                                                                                                                                                    |
| UDF: Gateway Transaction ID              | User Defined 2. This is the transaction ID generated by the payment gateway.                                                                                                       |
| Profiler Logs                            | Set this to a hidden field in which you want to store the Profiler request and response data (this must be a hidden field). You may need to check this data to debug any errors.   |

4.	Save the settings

### 5. Test the form

Testing the form is very important. Make sure you test all combinations, pre-defined amounts and other options. Ensure all data is correctly passed through to Profiler. **It's your responsibility to test all functionality to ensure it performs to your requriements.**

## How to Override the Source and Acquisition Codes

There are two ways to override the default source and acquisition codes:

1.	GET Parameters
2.	Short Code

Here is an example source code you can use:

   [donate_setoptions sourcecode=”WEBDON” pledgesourcecode=”REGULAR” pledgeacquisitioncode=”WEBPLEDGE”]

Ensure you embed this shortcode before the Gravity Form shortcode on the donation page. You can use this to create campaign-specific donation pages, with only one Gravity Form powering it all.

Here is an example query string you can use:
http://example.com/donate/?sourcecode=WEBDON&pledgesourcecode=REGULAR&pledgeacquisitioncode=WEBPLEDGE

If both a GET Parameter and Short Code are used on the same page, the GET parameter will take precedence.

## RAPID Quick Guide

When donations are sent to Profiler, they appear on the *Donations > Integration > RAPID Integration* screen.

To see all donations regardless of status, click on the "List Filter" drop-down and select "All/Force". The latest donations will show at the top of the list.

If a Donation says "Continue":

1.	Click the "Continue" button
2.	Manually match the client (or create a new one)
3.	Press the "Finish/Back" button

If a Donation says "Check":

1.	Click the "Check" button
2.	Check the "Entered in RAPID" and "Entered in Profiler" data, and make sure the correct new details are stored in the far-right column.
3.	Press "Save" (or create a new client)

If a Donation was entered by mistake or cannot be processed, you need to press the "Remove" link

## Disclaimer

THERE IS NO WARRANTY FOR THIS PROGRAM. IT IS PROVIDED "AS IS" WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU. SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY SERVICING, REPAIR OR CORRECTION. 

IN NO EVENT UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING WILL ANY COPYRIGHT HOLDER, OR ANY OTHER PARTY WHO MAY MODIFY AND/OR REDISTRIBUTE THE PROGRAM AS PERMITTED ABOVE, BE LIABLE TO YOU FOR DAMAGES, INCLUDING ANY GENERAL, SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF THE USE OR INABILITY TO USE THE PROGRAM (INCLUDING BUT NOT LIMITED TO LOSS OF DATA OR DATA BEING RENDERED INACCURATE OR LOSSES SUSTAINED BY YOU OR THIRD PARTIES OR A FAILURE OF THE PROGRAM TO OPERATE WITH ANY OTHER PROGRAMS), EVEN IF SUCH HOLDER OR OTHER PARTY HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
