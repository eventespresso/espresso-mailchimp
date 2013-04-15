# Event Espresso - MailChimp Integration #
**Contributors:** eventespresso, ivycat  
**Tags:** email, mailing list  
**Requires at least:** 3.5.1  
**Tested up to:** 3.5.1  
**Stable tag:** trunk  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

This plugin integrates Event Espresso events with MailChimp lists and groups.

## Installation ##

Make sure you have installed the latest version of Event Espresso and the MailChimp addon, then complete the following steps to finish MailChimp integration.

* Login to the WP Dashboard and click on "Plugins."
* Click "Activate" on the _Event Espresso - MailChimp Integration_ plugin.  You will notice a new configuration option in the "Event Espresso" menu titled "MailChimp Integration."
* Click the "MailChimp Integration" link and enter a MailChimp API key.
* If your API key is incorrect, the system will respond with a detailed error message.
* Once the API key is configured successfully, a "MailChimp List Integration" box will appear in the right menu of the Event Creation and Event Update dialogs, which will allow you to select which MailChimp List and Group you want to integrate with.

At this point, the MailChimp Integration is complete. All new attendees will be subscribed automatically to the MailChimp list selected above.

In addition, if an administrator creates an attendee for an event, that attendee will also be automatically subscribed to the MailChimp list selected above.

## Changelog ##

### 1.1.1 ###
* Fixed MailChimp group subscription within lists.

### 1.1.0 ###
* Added MailChimp group subscriptions.

## Upgrade Notice ##

### 1.1.1 ###
Important bug fixes & new functionality - please upgrade!

