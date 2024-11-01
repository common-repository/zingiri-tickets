=== Support Tickets Center ===
Contributors: zingiri
Donate link: http://www.zingiri.com/donations
Tags: ticket, support
Requires at least: 2.1.7
Tested up to: 3.8.1
Stable tag: 3.0.3

Support Tickets Center is a Wordpress plugin that adds state of the art ticketing support functionality to your website.

== Description ==

Support Tickets Center brings a state of the art ticketing support system to the Wordpress world.

Support Tickets Center seamlessly integrates inquiries created via email, phone and web-based forms into a simple easy-to-use multi-user web interface.
Manage, organize and archive all your support requests and responses in one place while providing your customers with accountability and responsiveness they deserve.

The standard version allows up to 15 outstanding tickets. If you need more than that, we have a Pro version available for purchase.

Note: Support Tickets Center uses web services stored on Zingiri's servers, read more in the plugin's FAQ about what that means.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory or install via the Wordpress plugins control panel
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the plugin Integration page and click Install.

Please visit the [Zingiri](http://forums.zingiri.com/forumdisplay.php?fid=63 "Zingiri Support Forum") for more information and support.

== Frequently Asked Questions ==

= This plugin uses web services, what exactly does that mean? =
Web services are simple way of delivering software solutions. Basically it means that the software & data is hosted on our secure servers and that you can access it from anywhere in the world. 
No need to worry about backing up your data, managing systems, we do it for you.

= What about data privacy? =
Support Tickets Center uses web services stored on Zingiri's servers. In doing so, personal data is collected and stored on our servers. 
This data includes amongst others your admin email address as this is used, together with the API key as a unique identifier for your account on Zingiri's servers. 
We have a very strict [privacy policy](http://www.zingiri.com/privacy-policy/ "privacy policy") as well as [terms & conditions](http://www.zingiri.com/terms/ "terms & conditions") governing data stored on our servers.

= How does the Wordpress integration work? =
Wordpress administrators are automatically created as Support Tickets Center admin users. Wordpress editors are automatically created in as staff.

Other Wordpress users (subscribers, contributors, authors) are considered guest users and won't be created as Support Tickets Center users.

= How can customers log their tickets =
After installation, a page called 'Tickets' is created. Make sure you add this page to your menus. Customers can log and view tickets by going to this page.

Please visit the [Zingiri](http://forums.zingiri.com/forumdisplay.php?fid=63 "Zingiri Support Forum") for more information and support.

== Screenshots ==

None available yet.

== Upgrade notice ==

Simply upload the new version and go to the control panel to ugprade your version.

== Changelog ==

= 3.0.3 =
* Fixed issue with login to view ticket in front-end not working

= 3.0.2 =
* Fixed compatibility with Wordpress 3.8.1

= 3.0.1 =
* Included Brazilian Portuguese translations
* Fixed alignment issue on customer ticket submission page
* Fixed various small styling issues
* Verified compatibility with Wordpress 3.7

= 3.0.0 =
* Verified compatibility with Wordpress 3.6.1
* Corrected Dutch translation
* Major relook

= 2.3.2 =
* Added UTF-8 encoding of email subject (hosted version)
* Fixed issue with @ being encoded in email messages
* When syncing users from WP to STC only update email and user name(s)
* Fixed issues with email downloading (hosted version)

= 2.3.1 =
* Fixed issue with viewing attachments (local install)

= 2.3.0 =
* Added license key field

= 2.2.2 =
* Added auto login for WP users to ticketing system if the user has already logged a ticket previously

= 2.2.1 =
* Added Bulgarian translation (thanks to Stanimir Koruev)
* Removed obsolete activation and deactivation hooks
* Fixed issue where install fails if WP site title is not filled

= 2.2.0 =
* Verified compatibility with Wordpress 3.5.1
* Fixed issue with viewing attachments uploaded to tickets

= 2.1.8 =
* Fixed user sync issues
* Verified compatibility with Wordpress 3.5

= 2.1.7 =
* Added Hebrew translation (courtesy of Keslacy Hanan)

= 2.1.6 =
* Added language template file

= 2.1.5 =
* Fixed packaging issue

= 2.1.4 =
* Added Norwegian language
* Verified compatiblity with WP 3.4.1

= 2.1.3 =
* Updated readme.txt and settings page regarding the use of web services and data privacy policy
* Fixed security vulnerability
* Removed loading of ads

= 2.1.2 =
* Fixed issue with resetting API key

= 2.1.1 =
* Fixed upgrade issue for users using a version prior to version 2
* Removed obsolete osticket.zip file
* Removed uninstallation when deactivating

= 2.1.0 =
* Removed superfluous notifications
* Disabled follow redirect on login
* Left full osticket directory for backward compatibility for versions upgrading prior to version 2

= 2.0.6 =
* Fixed packaging issue

= 2.0.5 =
* Fixed installation issue
* Checked that class ZipArchive is installed
* Fixed issue with preferences 'helpdesk url' not being accepted
* Fixed issue when trying to update or create a user

= 2.0.4 =
* Fixed activation issue
* Fixed minor code warnings

= 2.0.3 =
* Fixed packaging issue

= 2.0.2 =
* Fixed upgrade issue

= 2.0.1 =
* Fixed packaging issue

= 2.0.0 =
* Moved to tickets as a service concept

= 1.4.4 =
* Updated Dutch language file

= 1.4.3 =
* Fixed issue with selection of language

= 1.4.2 =
* Fixed issue where a HTTP error 500 is displayed after replying to a ticket from the backend

= 1.4.1 =
* Fixed issue with captcha not showing on ticket submission

= 1.4.0 =
* Now passing admin email in base 64 encoded form to avoid connectivity problems on some installations
* Added compatibility with Wordpress 3.3
* Added support for running on multi site installations (beta)
* Updated look and feel of control panel

= 1.3.2 =
* Fixed issue with canned responses throwing a 404 error
* Fixed autolock issue

= 1.3.1 =
* Split plugin activation process in plugin activation and separate installation, idem for deactivate/uninstall
* Fixed attachment download issues in front end and in admin panel
* Added missing definition of constant BLOGUPLOADDIR

= 1.3.0 =
* Added support for other languages to back-end, formerly only the front-end was translated

= 1.2.8 =
* Added Dutch language translations (partial)
* Improved language parsing

= 1.2.7 =
* Updated installation instructions
* Updated FAQ
* Fixed issue with wrong form being displayed when logging out from the ticket front end system
* Check sessions save path is filled before displaying alert
* Updated http class to v1.10.02

= 1.2.6 =
* Assured compatibility with PHP versions below 5.3
* Fixed issue with osTicket staff / WP editors not having access to osTicket back end functionality

= 1.2.5
* Fixed display of PHP version warning, should be PHP 5.3 as minimum version
* Fixed issue with footer being displayed on every page
* Prefil user name and email when opening a ticket if the user is logged into WP
* Fixed issue with previous logged in user's tickets still being shown after logout 

= 1.2.4 =
* Moved footer to bottom of pages instead of site
* Added a check to verify that PHP sessions are properly configured
* Fixed issue with HTPP error 417 on non Apache servers
* Fixed issue with front end users being redirected to login page despite successful login

= 1.2.3 =
* Only load tickets header on tickets admin pages

= 1.2.2 =
* Limited load of plugin header files to plugin pages only in WP administration back end
* Fixed issue with plugin header files being loaded outside of the HEAD tag
* Fixed potential issue with loading of simple_html_dom library
* Updated Support Us page
* Removed tickets menu widget as administration is handled through the back end menu
* Replaced deprecated get_settings() function
* Upgraded http.class.php to version 0.10
* Update osticket admin users when WP admin user details change
* Fixed a few minor PHP syntax issues
* Fixed issue with login when activating the 'osTickets' type of integration

= 1.2.1 =
* Set default admin_email for database errors to WP admin email
* Generate hashing salt for new installations

= 1.2.0 =
* Upgraded osTicket to version 1.6 ST
* Revamped navigation menu, moved to WP sidebar
* Updated Support Us page
* Improved usability

= 1.1.3 =
* Fixed activation error "The plugin generated ... characters of *unexpected output* during activation."
* Updated Support Us page

= 1.1.2 =
* Renamed plugin to Support Tickets Center to avoid conflict with existing plugin

= 1.1.1 =
* Rebranding
* Compatibility with Wordpress 3.2.1
* Improved handling of connection to osTickets avoiding the use of a cache directory
* Renamed plugin to Support Tickets

= 1.1.0 =
* Renamed plugin to ccTickets
* Minor fixes

= 1.0.6 =
* Highlight unanswered tickets in admin/user panel
* Changed default admin page to tickets page
* Fixed issue with canned responses not working

= 1.0.5 =
* Added possibility to specify what tickets page to display by changing the value of custom field "zing_tickets_page"
* Upgraded to work with Wordpress 3.0.1

= 1.0.4 =
* Reviewed way plugin name is retrieved to ensure compatibility with WAMP installations
* Create default email based on Wordpress admin email (only for new installs)
* Fixed issue with URL links in email not defaulting to correct URL
* Changed message related to check if file settings.php is writable
* Changed default admin page to tickets page instead of dashboard
* Added Spanish language version (in beta release, still some missing translations)

= 1.0.3 =
* Fixed issue when using URL containing IP address instead of host name
* Added compatibility with other ChoppedCode plugins

= 1.0.2 =
* Fixed issue with ostnav tag not being replaced properly
* Fixed issue with errors on str() function

= 1.0.1 =
* Moved osTicket administration panel to Wordpress backend

= 1.0.0 =
* Auto login subscribers as guest users if they have logged a ticket previously
* Fixed issue with download of attachements in staff panel
* Replaced element #nav by #ostnav to avoid conflicts with certain themes
* Fixed issue with plugin showing empty pages if local install uses an IP instead of a host name

= 0.9.2 =
* Fixed issue with advanced search not showing on tickets staff page
* Fixed filter by date issue on system logs
* Removed bullets appearing on guest page with certain themes
* Added tmp extension to cache files
* Fixed issue with staff access to tickets
* Removed change password option in case of WP integration

= 0.9.1 =
* Added option to restrict access to ticketing system to Wordpress subscribers
* Updated installation instructions
* Fixed issue with cookie management causing Wordpress integration not to work in certain cases
* Fixed issue with login page breaking theme
* Added list of synchronised Wordpress users on control panel
* Fixed issue with Autosuggest javascript
* Resolved issue getting a 'no posts found' page when adding a ticket through the admin panel
* Fixed issue with selection of multiple checkboxes not working
* Disabled autolock warning messages
* Fixed issue with various icons not appearing
* Fixed issue with forms using 'get' method being redirected to a 'no posts found' page
* Cleaned up style sheets

= 0.9.0 =
* Fixed issues with Wordpress login integration
* Fixed issues occuring when integrating with certain themes

= 0.8 =
* Fixed issue with missing files
* Added support for PHP installs running in safe mode or open base dir set

= 0.7 =
* Reworked integration engine
* Added compatibility with permalinks
* Set comments status to closed for Tickets pages

= 0.6 =
* Fixed compatibility issue with other plugins
* Fixed issue when trying to activate sidebar widget after first install
* Clarified control panel settings

= 0.5 =
* Fixed issue with autocron not running
* Fixed issue when trying to add premade reply in knowledgebase
* Fixed some usability issues

= 0.4 =
* Removed obsolete files

= 0.3 =
* Set default integration to Wordpress
* Added logout menu in sidebar menu
* Fixed issue when trying to edit configuration
* Fixed issues with email piping
* Removed copyright from bottom of content page

= 0.2 =
* Alpha release
