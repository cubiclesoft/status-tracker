Status Tracker
==============

A simple and elegant cron script and web application health status tracker written in PHP.  This tool allows cron scripts and web applications to rapidly report their status using a very simple JSON file storage mechanism.  The tool runs via cron and processes each report as it comes in.  Whenever the status changes, users are notified about the change.

If you have cron scripts that run on a regular basis (e.g. every minute) and flood your inbox with a bajillion e-mails when things like network failures occur, then this tool is for you.  It is a superior system monitor because you only register the script changes you are actually interested in receiving.  Notifications sent out only when the status changes, which results in only two or three e-mails being sent instead of a zillion e-mails.

Quiet inboxes are happy inboxes.

Features
--------

* Powerful status tracking in a very small solution.
* Blazing fast performance.  Simply write a JSON file to the /input directory to report current status.  No complex remote API to set up.
* Language agnostic.  As long as a language can create JSON and write it to a file, then it is supported.
* Tracks status start and end times in a JSON log file.  Other tools can then be used make pretty color-coded graphs.
* Extensible.  Add Slack or other notification systems via plugins.
* Also has a liberal open source license.  MIT or LGPL, your choice.
* Designed for relatively painless integration into your environment.
* Sits on GitHub for all of that pull request and issue tracker goodness to easily submit changes and ideas respectively.

Installation
------------

To install, download this repository and put it somewhere secure.  Put all desired plugins into the 'plugins/' directory.  Then run:

`php install.php`

And follow the prompts.

To secure the installation, create a new user group (e.g. `addgroup statustracker` on Ubuntu) and then set the group of the /input directory to the new user group (e.g. `chgrp statustracker input`) and then add the group to one or more users (e.g. `adduser www-data statustracker' on Ubuntu).

From an application or cron script, write a serialized JSON object to the /input directory containing:

* status (Required) - A string containing the current status.
* notify (Required) - A string or array containing one or more users to notify.  Plugins expect certain prefixes to be applied to each user.  For example, the "mailto:" prefix will be handled by the e-mail notification plugin.
* timeout (Optional) - An integer containing the number of seconds to wait before automatically setting the status to 'status-tracker-timeout' and notifying users about the change.  Useful for automatically discovering cron scripts that stop working for some unknown reason.

Example:

````
{
	"status": "normal",
	"timeout": 900,
	"notify": [
		"mailto:you@somewhere.com",
		"mailto:helpdesk@somewhere.com",
		"mailto:1235555555@txt.att.net"
	]
}
````

In the example, the status is set to 'normal' (you can use whatever statuses work for you), the timeout is set to 15 minutes (15 * 60 = 900), and three e-mail addresses are notified (one of which will translate to a SMS message for a cellphone on the AT&T cell network).

The filename must contain a hyphen, end in '.json', and be in the format:

`name-sortingmetric.json`

The 'name' portion can be whatever you want.  The 'sortingmetric' is probably best as a UNIX timestamp of some sort (e.g. `time()` or `microtime(true)` under PHP).

Once you have your first JSON object in the /input directory, manually run:

`php run.php`

And verify that all users receive their first notification from the system.

Finally, add the script to cron or other system task scheduler so that it runs every minute:

`* * * * * /usr/bin/php /var/scripts/script-tracker/run.php > /dev/null 2>&1`

About /logs
-----------

For every 'name' dropped into the /input directory, there are two associated files in the /logs directory.

The first associated file ends in '.json' and stores the previous state as recorded by status tracker.  It represents the current status and will simply be updated repeatedly until the 'status' changes.

The second associated file ends in '.log' and contains past statuses in JSON format, one per line.  The duration of a status can easily be calculated as `end - start`.  This makes it fairly straightforward to create a visual chart or graph of the changes in status over time to quickly identify problem areas that need to be resolved.  Analyzing the logs is beyond the scope of this tool.

Writing Plugins
---------------

Look at the 'plugins/email_notify.php' plugin to see a simple example of a plugin.  Support files should go into the /support directory.  Most plugins, when correctly written, will be about the same size as the e-mail notification plugin, which weighs in at less than 100 lines of code.
