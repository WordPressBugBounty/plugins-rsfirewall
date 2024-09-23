=== RSFirewall! ===
Contributors: rsjoomla
Tags: firewall, security, malware scanner, system check, web application firewall
Requires at least: 4.5.15
Tested up to: 6.6.2
Requires PHP: 5.4
Stable tag: 1.1.34
License: GPLv3

Based on the success of the most popular firewall for Joomla!, RSFirewall! is now available to protect your WordPress website as well.

== Description ==

The RSFirewall! WordPress plugin is the optimal solution for securing your website, helping you stay one step ahead of malicious users that wish to harm your website. The plugin is backed by a team of professionals with a long history in website security that are up to date with the latest known vulnerabilities and security updates.

RSFIREWALL FREE VERSION FEATURES:

* Free WordPress Firewall for your website
* Active protections against local file and remote file inclusion attacks
* SQL injection protections
* ReCAPTCHA for registration, login and commenting forms
* Filter uploaded files for possible malware and improper extensions
* Active monitoring WordPress core files integrity
* Active monitoring for your own files
* XML-RPC blocking
* REST API blocking with proper exceptions that you can define
* Protect the wp-admin/ slug with an extra password
* Change the wp-admin/ slug into a custom one
* Disallow direct access to PHP files in (wp-content, wp-content/uploads, wp-includes) with proper exceptions that you can define
* Receive email notifications on detected threats
* Automatically block repeated offenders IP addresses
* Perform a System check (WordPress and server configuration checks)
* Disable the creation of new Administrator accounts

RSFIREWALL PAID VERSION FEATURES:

* Two Factor Authentication
* Country blocking
* Convert email addresses to images
* Protect forms from abusive IPs
* File integrity check
* Convert email addresses from plain text to images
* More control over the system check
* Whitelist blocked PHP files
* Protect admin users from changes

== 3rd Party services ==

RSFirewall! will compare the MD5 hash of files with the original ones from the WordPress installation package. If differences are found (ie files have been modified) RSFirewall! upon request can download the original files from the GitHub synchronised repository of WordPress:

[https://github.com/WordPress/WordPress/](https://github.com/WordPress/WordPress/)

All connections are made with [wp_remote_get](https://codex.wordpress.org/Function_Reference/wp_remote_get) and the following information will be sent along with the request:
- WordPress version
- WordPress user agent along with your WordPress website address
- Your server's IP address

== Installation ==

Upload the RSFirewall! plugin to your blog and activate it. Out of the box protection is supplied upon activation but it's always wise to check out the Configuration area to view all options available and perform a System Check to ensure your website's integrity.

== Changelog ==

= 1.1.34 =

* Fixed - Some PHP 8.1+ compatibility improvements.

= 1.1.33 =

* Fixed - In some cases the post types used by the firewall could not be permanently deleted.
* Fixed - Some PHP 8.1+ compatibility improvements.

= 1.1.32 =

* Fixed - Some PHP 8.1+ compatibility improvements.

= 1.1.31 =

* Added - Button to empty the 'Threats' log.

= 1.1.30 =

* Fixed - WP DB errors were showing up forcefully in other plugins.
* Fixed - Some PHP 8 compatibility improvements.

= 1.1.29 =

* Fixed - Sometimes the login didn't work properly with third-party plugins.

= 1.1.28 =

* Updated - Capturing the backend password attempts.

= 1.1.27 =

* Fixed - Filtering 'Critical' threats was not working.

= 1.1.26 =

* Fixed - Sometimes a PHP error could occur when sending email alerts on newer versions of PHP.

= 1.1.25 =

* Fixed - 'Grab IP from Proxy Headers' is now empty by default on installation. Please select the appropiate header if your hosting provider or reverse proxy does not reveal the connecting IP address in the REMOTE_ADDR variable.

= 1.1.24 =

* Fixed - A PHP Warning could occur when grabbing the GeoIP location of the visitor.

= 1.1.23 =

* Fixed - Display input for the Two Factor Authentication code.

= 1.1.22 =

* Fixed - Ignore files counter.
* Fixed - Bulk actions threats counter.
* Updated - Malware hashes.
* Updated - DNS Library.

= 1.1.21 =

* Fixed - Some PHP 8 compatibilities.

= 1.1.20 =

* Fixed - Email addresses were converted into images even in REST API calls.

= 1.1.19 =

* Added - Ignored Hidden Files option that can be used to ignore hidden files (false positives) that start with dot on the System Check.
* Updated - The System Check can now be run with Xdebug enabled by adjusting the xdebug.max_nesting_level directive.
* Updated - Replaced references to lists as 'Blocklist' and 'Safelist'.

= 1.1.18 =

* Fixed - Files starting with a dot were not being downloaded during the System Check.

= 1.1.17 =

* Fixed - Cached functions were not cleared correctly.

= 1.1.16 =

* Fixed - Range and wildcard IPs were not working in the Blacklist/Whitelist section.

= 1.1.15 =

* Updated - License key support for downloading the GeoIP Database from MaxMind.

= 1.1.14 =

* Fixed - In some cases bulk blacklisting was not working.

= 1.1.13 =

* Fixed - IPs could be added multiple times in Blacklist/Whitelist

= 1.1.12 =

* Added - Google Web Risk API added as an alternative to the Google Safe Browsing API.
* Updated - Choose which Google APIs to use during the System Check.
* Fixed - Logout Redirect Link was not working.

= 1.1.11 =

* Fixed - A Fatal Error (memory_limit reached) could occur in the Dashboard when there were too many threats blocked
* Fixed - A PHP Notice could show up in the Dashboard area

= 1.1.10 =

* Fixed - A wrong password in TFA could generate a PHP Fatal Error.
* Fixed - In some cases the System Check would halt on the Safari browser.

= 1.1.9 =

* Added - Possibility of disabling the plugin using a ".disabled" file in the plugin root directory.

= 1.1.8 =

* Fixed - Check PHP version during activation and deactivate plugin if lower than 5.4.0.

= 1.1.7 =

* Added - Google reCAPTCHA for comment and registration forms.
* Updated - Country blocking is now using the GeoLite2 database.

= 1.1.6 =

* Fixed - "Disable the creation of new Administrators" was throwing a 500 error in some cases.

= 1.1.5 =

* Updated - Added a "Logout Redirect Link" for the backend password when the logout process redirects the user to the wp-login.php page.
* Updated - Added an exception for the Backend Password so that login forms in frontend that trigger the login action in wp-login.php continue to work.
* Fixed - "Disable the creation of new Administrators" from Lockdown in some cases did not work.
* Fixed - 2FA could not be used with Email Authentication and Unique Codes when updating from older versions.

= 1.1.4 =

* Fixed - Blacklisted IPs were shown an incorrect reason.
* Fixed - Resolved MainWP compatibility issue.

= 1.1.3 =

* Updated - IP address is now included in the subject of the email alerts.
* Updated - Added the option "Use MD5 Signature DB" for the System Check.
* Updated - Removed the buttons "Add New" from the Plugins and Themes pages for non administrator users.
* Updated - Prevent the WP GDPR Compliance plugin vulnerability exploit for versions below 1.4.3.
* Updated - Added the System Check step "Checking administrator users for compromised accounts".
* Updated - Added some exceptions for Malware checking on files that should not be there.
* Fixed - Table Views are no longer checked in the Database Check because they will halt the scan.
* Fixed - Disable access to the WordPress Plugins and Themes installer for non administrator users.
* Fixed - Remote file inclusion is now checking for both www and non-www domains of current website so it doesn't trigger false positives.

= 1.1.2 =

* Fixed - Skipped the step "Checking if your website is blacklisted" from System Check if the server IP address is not available.

= 1.1.1 =

* Added - New signature that checks for all "eval()" instances in the File Integrity check.
* Added - A Tooltip on the Threats list with the Country name on the IP of each listing, when using the GeoIP.
* Fixed - Backend Password login screen was showing up on logout process stopping it in some cases.

= 1.1.0 =

* Added - Two Factor Authentication security feature.
* Added - Accept Changes for the Quick actions in the RSFirewall! dashboard.
* Added - Checks for files that are not supposed to be on the installation.
* Fixed - Accept Changes for the core files in the System Check wasn't working correctly.
* Fixed - "Upload license code from the configuration.json" option was not taken in consideration.
* Fixed - Backend password login screen was showing up on AJAX requests from the frontend.

= 1.0.6 =

* Added - The Configuration area can be accessed directly through the Plugins area.
* Fixed - GeoIP files were not being downloaded in the Country Blocking area.

= 1.0.5 =

* Fixed - Incorrect WordPress core files were scanned in the Quick Actions.
* Fixed - On Windows servers the file paths would not appear correctly in the Dashboard and Threats areas.

= 1.0.4 =

* Fixed - Some tables were missing when installing the plugin.
* Fixed - Left side plugin menu was not translated.
* Fixed - Some options were incorrectly appearing as selected.
* Fixed - Some signatures were missing from the database.

= 1.0.3 =

* Fixed - Configuration page was throwing an error when using a language other than English.
* Fixed - Some strings were untranslated.

= 1.0.2 =

* Fixed - An SQL error would show up in the Threats section.

= 1.0.1 =

* Fixed - A Javascript error was preventing the System Check from completing on Mac Safari.
* Fixed - Frontend logins are now being monitored as well.

= 1.0.0 =

* Initial release
