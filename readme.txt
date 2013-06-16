
=== Better WP Security ===
Contributors: Bit51, ChrisWiegman
Donate link: http://bit51.com/software/better-wp-security/
Tags: security, secure, multi-site, network, mu, login, lockdown, htaccess, hack, header, cleanup, ban, restrict, access, protect, protection, disable, images, image, hotlink, admin, username, database, prefix, wp-content, rename, directory, directories, secure, SSL, FooPlugins
Requires at least: 3.5
Tested up to: 3.6
Stable tag: 3.5.3

The easiest, most effective way to secure WordPress. Improve the security of any WordPress site in seconds.

== License ==  
Released under the terms of the GNU General Public License. 

== Description ==

= #1 WORDPRESS SECURITY PLUGIN =

Better WP Security takes the best Wordpress security features and techniques and combines them in a single plugin thereby ensuring that as many security holes as possible are patched without having to worry about conflicting features or the possibility of missing anything on your site.

With one-click activation for most features as well as advanced features for experienced users Better WP Security can help protect any site.

= Professional Installation and Support Now Available =

Need help or want to make sure your setup is as secure as possible? Professional support is now available! <a href="http://fooplugins.com/plugins/better-wp-security/">Purchase support or installation today through our partner FooPlugins</a>

= Obscure =

As most WordPress attacks are a result of plugin vulnerabilities, weak passwords, and obsolete software. Better WP Security will hide the places those vulnerabilities live keeping an attacker from learning too much about your site and keeping them away from sensitive areas like login, admin, etc.

* Remove the meta "Generator" tag
* Change the urls for WordPress dashboard including login, admin, and more
* Completely turn off the ability to login for a given time period (away mode)
* Remove theme, plugin, and core update notifications from users who do not have permission to update them
* Remove Windows Live Write header information
* Remove RSD header information
* Rename "admin" account
* Change the ID on the user with ID 1
* Change the Wordpress database table prefix
* Change wp-content path
* Removes login error messages
* Display a random version number to non administrative users anywhere version is used

= Protect =

Just hiding parts of your site is helpful but won't stop everything. After we hide sensitive areas of the sites we'll protect it by blocking users that shouldn't be there and increasing the security of passwords and other vital information.

* Scan your site to instantly tell where vulnerabilities are and fix them in seconds
* Ban troublesome bots and other hosts
* Ban troublesome user agents
* Prevent brute force attacks by banning hosts and users with too many invalid login attempts
* Strengthen server security
* Enforce strong passwords for all accounts of a configurable minimum role
* Force SSL for admin pages (on supporting servers)
* Force SSL for any page or post (on supporting servers)
* Turn off file editing from within Wordpress admin area
* Detect and block numerous attacks to your filesystem and database

= Detect =

Should all the protection fail Better WP Security will still monitor your site and report attempts to scan it (automatically blocking suspicious users) as well as any changes to the filesystem that might indicate a compromise.

* Detect bots and other attempts to search for vulnerabilities
* Monitor filesystem for unauthorized changes

= Recover =

Finally, should the worst happen Better WP Security will make regular backups of your WordPress database (should you choose to do so) allowing you to get back online quickly in the event someone should compromise your site.

* Create and email database backups on a customizable schedule

= Other Benefits =

* Make it easier for users to log into a site by giving them login and admin URLs that make more sense to someone not accustomed to WordPress
* Detect hidden 404 errors on your site that can affect your SEO such as bad links, missing images, etc.

= Compatibility =

* Works on multi-site (network) and single site installations
* Works with Apache, LiteSpeed or NGINX (NGINX will require you to manually edit your virtual host configuration)
* Some features can be problematic if you don't have enough RAM to support them. All my testing servers allocate 128MB to WordPress and usually don't have any other plugins installed. I have seen issues with file check and database backups failing on servers with 64MB or less of RAM, particularly if there are many other plugins being used.

= Translations =
* French by <a href="http://www.sceric.net/">SCUDELLER Eric</a>
* Hindi by <a href="http://outshinesolutions.com/">Outshine Solutions</a>
* Lithuanian by <a href="http://www.host1free.com/">Vincent G</a>
* Persian by <a href="http://www.gonak.ir/">Ibrahim Jafari</a>
* Russian
* Simplified Chinese by <a href="http://haib.in">海滨</a>
* Slovak by <a href="http://www.nawebik.sk">Erich Szabó</a>
* Spanish by <a href="http://pabloromero.org">Pablo Romero</a>
* Tagalog by <a href="http://pointen.dk/">Hanne</a>
* Turkish by <a href="http://hakanertr.wordpress.com">Hakan Er</a>

Please <A href="http://bit51.com/contact" target="_blank">let us know</a> if you would like to contribute a translation.

= Warning =

Please read the installation instructions and FAQ before installing this plugin. It makes some significant changes to your database and other site files which, without a proper backup, can cause problems if something goes wrong. While problems are rare, most (not all) support requests I get for this plugin involve the users failure to make a proper backup before installing.

== Installation ==

Don't want to configure Better WP Security yourself but still want to make sure your site is secure as possible? <a href="http://fooplugins.com/plugins/better-wp-security/" target="_blank">Purchase premium installation and configuration for Better WP Security</a>

1. Backup your Wordpress database, config file, and .htaccess file
2. Upload the zip file to the `/wp-content/plugins/` directory
3. Unzip
4. Activate the plugin through the 'Plugins' menu in WordPress
5. Visit the Better security menu for checklist and options

NOTE: It is possible that something will break due to the complexity of the changes made by this plugin. That said, under no circumstances do I release this plugin with any warranty, implied or otherwise, and at no time will I take any responsibility for any damage that might arise from the use of this plugin. REMEMBER TO ALWAYS BACKUP BEFORE TRYING NEW SOFTWARE!

== Frequently Asked Questions ==

= Why does Better WP Security require WordPress the latest WordPress version? Can't I use a slightly older version? =
* One of the best practices a WordPress site owner can do to keep their site secure is to keep your software up to date. Because of this fact I do not test this plugin in anything but the latest stable version of WordPress and will only guarantee it works in the latest version.

= Will this completely stop all attacks on my site? =
* Of course not. Better WP Security is designed to help improve the security of your WordPress installation from many common attack methods. It can no way prevent every possible attack on your website. Nothing replaces diligence and good practice. This plugin just makes it a little easier for you to apply both.

= Is "one-click" protection good enough? =
* While one-click protection will go a long way to helping reduce the risk of attack on your site, the more features you can activate the better off you are. If you have a plugin or theme that conflicts with a feature of Better WP Security then just turn off the offending feature. It is better to use as much as you can than to not use anything at all.

= Is this only for new sites or can I use it on existing sites too? =
* Many of the changes made by this plugin are complex and can break existing sites. While it can be installed in either a new or existing site I cannot stress enough the importance of making a backup of your existing site before applying any of the options in this plugin.

= Will this work on all servers and hosts? =
* Better WP Security requires Apache or LiteSpeed and mod_rewrite or NGINX to work.
* While this plugin should work on all hosts with Apache or LiteSpeed and mod_rewrite or NGINX it has been known to experience problems in shared hosting environments where it runs out of resources such as available CPU or RAM. For this reason it is extremely important that you make a backup of your site before installing on any existing site as, if you run out of resources during an operation such as renaming your database table, you may need your backup to be able to restore access to your site.
* Finally, please make sure you have adequate RAM if you plan to use the file change detector of make large backups.

= Are you still developing this plugin? =
* Yes. I use this plugin on every site I build and as such will continue to develop it for the foreseeable future.

= Does this work with netowork or multisite installations? =
* Yes.

= Can I help? =
* Of course! I am in constant need of testers and I would be more than happy to add the right contributor. In addition, I could always use help with translations for internationalization. <a href="http://bit51.com/contributing-to-better-wp-security/">For more information on contributing to Better WP Security visit Bit51</a>.

= Will this break my site? =
* It is possible that something will break. That said, under no circumstances do I release this plugin with any warranty, implied or otherwise, and at no time will I take any responsibility for any damage that might arise from the use of this plugin. REMEMBER TO ALWAYS BACKUP BEFORE TRYING NEW SOFTWARE!
* Note that renaming the wp-content directory will not update the path in existing content. Use this feature only on new sites or in a situation where you can easily update all existing links.
* <a href="http://bit51.com/fixing-better-wp-security-lockouts/">Fixing Better WP Security Lockouts</a>
* <a href="http://bit51.com/what-is-changed-by-better-wp-security/">What is Changed By Better WP Security</a>

= I've enabled the Enforce SSL option and it broke my site. How do I get back in? =
* Open your wp-config.php file in a text editor and remove the following 2 lines:
* define('FORCE_SSL_LOGIN', true);
* define('FORCE_SSL_ADMIN', true);

= Where can I get help if something goes wrong? =
* Free support is available by reading the <a href="http://bit51.com/software/better-wp-security/" target="_blank">plugin documentation</a> or may be available with the help of the community in the <a href="http://wordpress.org/support/plugin/better-wp-security" target="_blank">WordPress.org support forums</a> (this is community provided support as Bit51 does not monitor the WordPress.org support forums). If you need more formal support you can <a href="http://fooplugins.com/plugins/better-wp-security/" target="_blank">purchase a premium support ticket</a> on a per-incident basis.

== Screenshots ==

1. Instantly scan your site and see where you can improve your security.
2. One-click access to most features can make securing your site easy
3. Simple informative options panels show you what you need to know about each and every option
4. If you do get stuck help is never more than a few clicks away.

== Changelog ==

= 3.5.3 =
* Simplified Chinese by <a href="http://haib.in">海滨</a>
* Persian by <a href="http://forum.wp-parsi.com/user/1469-ibrahim/">Ibrahim Jafari</a>
* Typo correction by ihuston
* Fixed Bit51 Google+ Link
* Better proxy support for ban users by kalvindukes
* Updated Spanish translation by <a href="http://pabloromero.org">Pablo Romero</a>
* Updated Readme
* Updated .pot file

= 3.5.2 =
* Fixed error message that could appear when creating backups
* Correct Changelog not displaying correctly on WordPress.org after version 3.5.1

= 3.5.1 =
* Replaced Turkish language version lost in when tagging 3.5 in the WordPress.org repository
* Solved a conflict with other Bit51 plugins that use the common Bit51 class

= 3.5 =
* Integrate with Foo Plugins support system
* Fixed role translation call for Strong password enforcement
* Turkish by <a href="http://hakanertr.wordpress.com">Hakan Er</a>
* Random version number no longer strips unrelated GET variables for better compatibility
* Upgrading no longer automatically rewrites .htaccess and wp-config resulting in much improved reliability
* Fixed possible error when login fails and PHP is set to report errors to screen

= 3.4.10 =
* Replaced feed with standard WordPress feed
* Added better error checking for feed should Feedburner (or any other provider) kill it again

= 3.4.9 =
* More secure user query thanks to John Cave
* Greatly improved intl date handling by <a href="http://www.sceric.net/">SCUDELLER Eric</a>
* Added: French translation by <a href="http://www.sceric.net/">SCUDELLER Eric</a>
* Fixed: bug preventing Jetpack's Infinite Scroll from working with long URL protection

= 3.4.8 =
* Fixed error message that may occur if InfiniteWP is not installed.

= 3.4.7 =
* Added compatibility with InfiniteWP (http://infinitewp.com/)
* Updated default ban list as it was a little too restrictive for my taste
* Added export of 404 logs in .csv format (experimental)
* Add X-Forwarded-For ability to IP logging
* Minor bug and typo fixes

= 3.4.6 =
* Updated usability on ban lists
* Ban list threshold now triggered on hit and not 1 after

= 3.4.5 =
* Replace database override of awaymode with wp-config constant
* Filecheck override is now done through wp-config constant
* Added option to generate new secret key in hide backend
* Added Slovak translation by Erich Szabó
* Possible Google Maps fix for Apache
* Improved time handling for away mode, lockouts, logs, and more
* Added Tagalog translation by Hanne of ​<a href="http://pointen.dk/">http://pointen.dk/</a>
* Various table updates from Michael Conover (<a href="twitter.com/sidtheduck">@sidtheduck</a>) at <a href="http://sidtheduck.com">sidtheduck.com</a>.
* Load plugin as global to reduce multiple executions
* Fixed rewrite rules for banned hosts
* Updated .pot file
* Other minor bugfixes and refactoring

= 3.4.4 =
* fixed input vulnerability found at http://packetstormsecurity.org/files/116317/Better-WP-Security-3.4.3-Cross-Site-Scripting.html
* fixed email address in footer information on backup screen
* file check exclusions should now work properly for individual files.
* One-click protection is now part of the install script.
* Won't log or even check 404 if feature is off.
* Don't clear cache during away check. Let's see instead if the transients …
* Don't clear Supercache page cache on clear logs

= 3.4.3 =
* Only clear WP Supercachce when full page cache clearing is required

= 3.4.2 =
* Gravatars will no longer dissappear after changing user 1 id
* Better cache clearing when changing options
* Reworked away mode for better cache handling
* Subdirectory redirects should now work
* Fixed error message on logout
* Fixed password reset email link
* Will no longer duplicate IPs in ban list when entered via auto-ban
* Minor style updates
* Better namespacing in content.php
* Removed 38.0.0.0/8 from hackrepair.com blacklist
* remove yandex from hackrepair.com blacklist

= 3.4.1 =
* Clean all logs when checked
* Better logic for SSL checking
* Removed echo statement in Filecheck
* Highly compressed NGINX rules
* Added to "Filter Suspicious Query String" Logic

= 3.4 =
* Added Russian Translation
* Updated Hindi contributor to <a href="http://outshinesolutions.com/">Outshine Solutions</a>
* Prevented file change warning from displaying to non-admins
* Fixed error causing multiple backup emails
* Added ability to change ID of user with ID 1
* Fixed bug in plugin base url
* Added extra warnings and "escape route" for away mode
* Fixed hide backend issues since WordPress 3.4
* Lookup IP addresses directly from logs
* Fixed dbdelta errors on upgrade
* Updated form styles and appearance
* Added tabs to settings pages for increased usability
* Duplicate IP addresses won't be saved to banned list
* Wildcards now correctly save to banned list
* Suppress errors on filecheck arrays
* Fixed link to permalink settings in hide backend
* Added extra save buttons to system tweaks
* Added logging memory usage to filecheck
* Updated readme.txt
* Updated .pot

= 3.3 =
* More checks to ensure blank "Deny from" lines don't appear in .htaccess
* Added host and user agent blacklist by <a href="http://hackrepair.com">HackRepair.com</a>
* Changed "Options All -Indexes" to "Options -Indexes" in .htaccess rules
* Added log view for all bad login attempts to view logs
* Always show .htaccess and wp-config.php changes in Dashboard
* Database backups no longer turn on automatically with one-click secure.
* Replaced unique key in database tables with primary key (tested in 3.4)

= 3.2.7 =
* Hindi translation by Outshine Solutions
* Spanish translation by Pablo Romero

= 3.2.6 =

* Lithuanian translation by Vincent G
* Fixed bug that could allow blank hosts in .htaccess for ban users
* Removed obsolete translations from before version 3.0
* Fixed various typos
* Numerous minor bug fixes
* Support moved back to WordPress.org forums

= 3.2.5 =

* Users can now specify email address for database backups
* Fixed bug throwing error when saving changes to existing users
* Corrected typo in intl hook
* List banned IPs on separate lines for readability
* Replaced all instances of Wordpress with WordPress
* Logs no longer show errors when records are cleared while viewing file change details
* File check will no longer automatically enable on servers with low RAM
* An extra database key has been introduced to easily disable file checking if it causes memory errors
* updated .pot
* Sanitize ALL server variables to prevent XSS vulnerability

= 3.2.4 =

* Added configurable email address for all email notifications
* Added ability to turn off dashboard warning for file check
* Password reset form will now require strong passwords if configured
* Ability to automatically blacklist an IP address after a specified number of lockouts
* Various minor bugfixes
* Turning off front-end ssl will stop ssl redirect loops in sites with an existing ssl implementation
* Updated language and explanations for various features
* Updated .pot

= 3.2.3 =

* Fixed date offset in log views
* Fixed site admin renaming for multisite users
* Fixed typos throughout
* Block concat MySQL command
* Deny access to readme.txt in protect files
* Fixed 404 table description
* Added domain name to email notifications
* Improved folder check login
* Suppress error messages for file-check operations

= 3.2.2 =

* Fixed 500 error when ban-users in enabled and IP or agents list are empty
* Fixed error that logged bad logins and 404s even when features were turned off

= 3.2.1 =

* Added choice to completely disable front-end SSL, enable per page, or enable site-wide
* Fixed login URL on new user email when new user is created by an existing user and hide backend is enabled
* Default all SSL to off for new installations
* Fixed strong password roles to work correctly
* A little 418 humor
* Updated .pot file
* Updated readme.txt

= 3.2 =

* File checker checks for changed files
* SSL for individual pages and posts
* One-click protection removes all .htaccess and wp-config.php options
* Option to not allow the plugin to write to .htaccess and wp-config.php
* Tweaked NGINX rewrite rules
* Moved SSL options to separate page for better usability
* Tables now display in native WordPress format
* Updated language throughout
* Tweaked Apache rewrite rules
* Various minor bug-fixes
* New installation video (see plugin homepage)
* Updated .pot file

= 3.1 =

* Significantly less resource usage
* Fixed white screen errors on load
* Fixed backup scheduling errors
* updated .pot file
* numerous minor bugfixes

= 3.0.12 =

* Displays log messages for all lockouts
* Scheduled backup times can be much more easily customized
* Setting wp-config.php and .htaccess to 0444 is now optional
* Updated .pot file
* Fixes to Apache/LiteSpeed rules and NGINX rules
* Numerous minor bugfixes

= 3.0.11 =

* Fixed bug with redundant backup caller in admin script

= 3.0.10 =

* Better LiteSpeed support
* Better database backup scheduling
* Better line spacing in .htaccess and wp-config.php
* WordPress 3.3.1 now required
* Status area now links to proper options and not top of tweaks page
* NGINX rule fixes
* admin-ajax.php now works even with hide backend
* error surpression on file operations
* update .pot
* Many language updates
* Better update script for multisite installs

= 3.0.9 =

* Fixed multi-site issue due to 3.0.8 update support fix
* Fixed awaymode settings issues

= 3.0.8 =

* Fixed improper php open tags
* Fixed erroneous PHP_EOL in nginx rules
* LiteSpeed support
* Better update support (not relying on activation hook anymore)
* Added abstract keyword to bit51.php
* Removed bwps references in bit51.php
* updated .pot file

= 3.0.7 =

* Changed method of end of line character technique for better cross-platform server compatibility
* Fixed 2 lines of <? in content.php replacing them with <?php

= 3.0.6 =

* Another fix to the "line 2072" error. This would be a lot easier with a Windows host as those are the only folks that seem to have the issue

= 3.0.5 =

* Changes to language on ban users page
* fixed "line 2072" error

= 3.0.4 =

* Changed IP banning to only accept * wildcards for ranges
* All host banning is not done via server configuration rather than php
* Numerous minor bugfixes

= 3.0.3 =

* Fixed bug in backup file path

= 3.0.2 =

* Fixed default options on saving
* Fixed setup options
* Other minor bugfixes

= 3.0.1 =

* Turned off flag that caused plugin settings to reset on update.

= 3.0 =

* Complete rewrite from the ground up
* Menu changes
* UI completely rewritten
* Now supports NGINX
* Scheduled database backups
* Added ability to block user agents in addition to hosts
* Numerous bugfixes

= 2.18 =

* Another attempt to fix the login error that started with 2.16. Changed logic for determining hide backend feature.

= 2.17 =

* Fixed an error that started with version 2.16 and prevented user from being able to login to the WordPress Dashboard.

= 2.16 =

* Fixed login link in new user email after breaking it in version 2.15

= 2.15 =

* Now loads all features at init to [hopefully] eliminate function not found errors

= 2.14 =

* Bugfixes from 2.13
* Removed randomized version for all logged-in users due to conflicts with admin-bar

= 2.13 =

* Bugfixes from 2.12

= 2.12 =

* Bugfixes from 2.11

= 2.11 =

* Fixed login-slug in new user email
* Fixed login slugs throughout site
* Remove reset-password options
* Improved rewrite rules (I would credit the author but I'm afraid in a bone headed move I never wrote down the author with the notes)
* No longer loads pluggable

= 2.10 =

* Added Romanian translation by Luke Tyler

= 2.9 =

* readme.txt typo correction
* Added ability to whitelist hosts and ip addresses for intrusion detection
* intrusion detection now lists 404 errors found to help ease troubleshooting
* intrusion detection now records referrer to make tracking 404 errors easier
* corrected error when attempting to list multiple hosts when banning users

= 2.8 =

* German Translation by Stefan Meier

= 2.7.1 =

* Fixed a logic bug caused by changes in 2.7

= 2.7 =

* Fixed a bug preventing login lockouts from releasing.

= 2.6 =

* Added link to author of Italian Translation
* Fixed bug preventing the "Ban Users" function from working.

= 2.5 =

* Italian translation by Paolo Stivanin
* Support information moved to separate page for easy access
* Minor bug fixes

= 2.4 =

* Fixed a bug that generated a 404 error when clicking the reset password link that is emailed to users
* Added the option to customize the error message displayed for the login lockdown and intrusion detection lockouts

= 2.3 =

* Fixed various typos
* meta.php require_once now works correctly
* fixed bug in which .htaccess and wp-config.php were not reporting correct permissions
* Version is now hidden on admin pages except for multi-site

= 2.2 =

* Emergency fix restoring version number display for backend as previous fix made multi-site installations unusable

= 2.1 =

* Added options to customize intrusion detection to allow custom lockout duration and error threshold
* Time now correctly displays for intrusion detection lockouts and lockouts are released at the correct time
* Version number now hidden for all users without administrator role on backend
* Saved hide backend key to database to allow for easier use in other plugins that link directly to wp-login.php (still has to be manually entered in each affected plugin)
* Will now use the correct wp-config.php file if it is located outside of the directory used for the wordpress installation
* Empties APC cache (when installed) after changing wp-content directory preventing the necessity to restart Apache
* Fixed display bugs for login and intrusion lockout lists.

= 2.0 =

* Now supported by Bit51.com
* Removed blocking of http HEAD requests to improve integration with social networking APIs such as Twitter
* French translation by Claude ALTAYRAC

= 1.9 =

* Error message on lockouts more ambiguous
* Added email notification for intrusion detection lockouts
* Added Bahasa Indonesia (Indonesian) translation by Belajar SEO, Jasa SEO Indonesia

= 1.8.1 =

* Minor bug fixes

= 1.8 =

* Changed plugin description
* Improved translation support
* Added Turn off file editor in WordPress backend
* Improved accuracy of version checking when upgrading
* Ban Users now allows for more than just IP address, it has been renamed accordingly

= 1.7 =

* Renamed detect 404s section to intrusion detection to include upcoming features
* general spelling and grammer corrections
* Moved configuration to network dashboard for multisite installations
* Improved multisite support
* Warns if .htaccess or wp-config.php files aren't writable where needed
* Added icon to menu for easier identification
* Cleaned up and refined status information

= 1.6 =

* Fixed WLManifest link removal from header
* Added nofollow to all meta links
* "Away Mode" page now displays current time even when feature has not been enabled
* Status page now shows system information
* htaccess contents moved to status page
* fixed fatal activation error affecting php 5.2 users

= 1.5 =

* Meta links update correctly when changing backend links

= 1.4 =

* Fixed another issue that prevented the "htaccess" options page from displaying on some hosts

= 1.3 =

* Fixed an issue that prevented the "htaccess" options page from displaying on some hosts

= 1.2 =

* Finished support for localization

= 1.1 =

* Fixed bug that prevented cleaning old lockouts from database

= 1.0 =

* More code documentation
* Added warnings to changing content directory (until I can find a good way to update all existing content)
* Added options to clean old entries out of the database
* Fixed minor typos throughout

= 0.16.BETA =

* Updated Homepage

= 0.15.BETA =

* Fixed error for potential conflicts with old htaccess rules

= 0.14.BETA =

* Removed hotlinking protection as it has been deemed to be outside the scope of this project
* Removed protocol from hide backend htaccess rules for consistency between http and https
* Combined all httaccess rules into single Better WP Security Block
* 404 check now ignores all logged in users

= 0.13.BETA =

* Fixed a bug that could erase part of the wp-config file= 0.12.BETA =

* Changing content directories should no longer break sites that were upgraded from versions prior to 3.0

= 0.11.BETA =

* Update to project homepage and other minor changes

= 0.10.BETA =

* Removed WP version check from status page as it was redundant
* On uninstall wp-content location will be returned to default
* Fixed setup error
* Error checking now correctly identifies database table prefix
* Rendom version # generator now removes version number from scripts and css where it can (thanks to Dave for this)

= 0.9.BETA =

* Bug fixes
* Internationalization improvements

= 0.8.BETA =

* Fixed more critical bugs

= 0.7.BETA =

* Fixed more critical bugs

= 0.6.BETA =

* Fixed 2 critical bugs

= 0.5.BETA =

* Major refactoring
* Streamline database tables
* Numerous bugfixes
* Code documentation and continued internationalization prep

= 0.4.BETA =

* Changed the main menu name to "Security"
* Minimum requirement raised to 3.0.2
* Begun code documentation and intl prep

= 0.3.BETA =

* Numerous bugfixes
* 404 check will NOT ban logged in users
* Lockdown rules no longer apply to logged in users

= 0.2.BETA =

* Updated hidebe to handle standard logout links
* Numerous other bugfixes

= 0.1.BETA =

* Finished status reporting
* Force SSL for admin pages (on supporting servers)
* Change wp-content path

= ALPHA 11 =

* Added security checklist
* Added option to rename existing admin account
* Added option to change DB table prefix
* Various bugfixes

= ALPHA 10 =

* Added more htaccess security options
* All htaccess options have been moved to their own page
* Added simple intrusion detection based on 404s
* Bugfixes and code optimization

= ALPHA 9 =

* Deactivating now removes all htaccess areas and turns off associated options
* Enforce strong passwords for all users of a given minimum role
* Minor bug fixes

= ALPHA 8 =

* Added various .htaccess options to strengthen file security
* Modified "hide backend" rewrite rules to work with multi-site
* Removed non-security hide-backend options
* Various bug fixes
* Renamed "General" options page to "System Tweaks" to avoid confusion
* Added more options to clean up WordPress headers
* Added options to remove plugin notifications from non-super admin users

= ALPHA 7 =

* Continued code refactoring and bug-fixes
* Improved error handling and upgrade support
* Combined status and support options pages

= ALPHA 6 =

* Added sanitization and validation to user input
* Added "away mode" to limit backend access by time
* Script no longer dies when logged out and instead returns to homepage.

= ALPHA 5 =

* Complete refactor of the existing code
* Divided settings sections for better UX
* Added htaccess checks
* Redesigned options system for less database calls
* Reduced table usage from 4 to 2
* Added email notifications for login limiter
* Added complete access blocker for login limiter

= ALPHA 4 =

* Added login limiter to limit invalid attempts
* various Bug fixes

= ALPHA 3 =

* Corrected error display
* Added registration rules regardless of whether registrations are on or off.
* Added "Display random version to non-admins"
* Fixed rewrite rules on hide admin urls so going to the admin slug will work whether the user is logged in or not
* Added crude upgrade warning to warn of old (not so great) rewrite rules

= ALPHA 2 =

* Optimized and commented code
* Added uninstall function
* Numerous fixes to bugs and logic

= 0.1 ALPHA =

* First alpha release including simple featureset.
