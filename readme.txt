=== Name Redactor ===
Contributors: joav
Tags: redact, redaction, names, privacy, hide, posts, comments, page
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The Name Redactor offer increased control over personal data by redacting 
personal names from the content if the visitor is a search engine robot.

== Description ==

Note: This plugin requires at least version 3.3 of Wordpress.

The Name Redactor is a Wordpress plugin which allows Wordpress users to 
hide personal data from search engines. As the name of the plugin implies, 
the type of content we are talking about in this context are personal 
names. The plugin works by checking whether the visitor to the site is 
human or a search engine robot. If the visitor is a search engine robot, 
the plugin will redact any personal names before delivering the content, 
replacing them with the text *[redacted]*. To human visitors, the names 
will appear as normal.

### Purpose of the Name Redactor ###
The web is full of personal names, which is usually attached to some 
contextual data (e.g. utterances, images, etc.). If these personal names 
are indexed by search engines, along with the contextual 
data attached to them, both will be discoverable by anyone searching for a 
specific name. While some such discoveries may be beneficial to the 
subject, others may be harmful. The purpose of the Name Redactor is not to 
block search engines from accessing your Wordpress site or indexing your 
content. The purpose is to avoid having personal names being indexed along 
with contextual data attached to those names. 

#### Features: ####
* Manually tag names to be redacted in pages, posts and comments by using 
the 'Redact' button in the Text Editor.
* Automatically redact names in pages, posts and comments, using a simple 
set of rules.
* Create an opt in list of names that should always be redacted, 
regardless of any automatic name detection.
* Create an opt out list of names that should *not* be redacted 
automatically.

### Detailed description ###
The Name Redactor plugin works by detecting if a visitor to the site is a 
search engine robot, and if so, the plugin will redact any personal names 
(which have been tagged with `<redact content="name"></redact>`) before 
delivering the content, replacing them with the text `[redacted]`. 
The tagging can either be done manually by the publisher, or automatically 
by the program. 

#### Manually tag names ####
When you install the plugin for the first time, it is set by default to 
only redact names that have been manually tagged. If you go to add a new 
post, page, or comment (or edit already existing content) and select the 
Text Editor, you will see that a new button has been added to the 
pre-existing ones. This button, labeled **redact**, allows you to tag a 
name in the text. Simply select the name you want to tag, and press the 
redact button. Alternatively, place the cursor before a name, press the 
redact button to add the name redact tag, place the cursor after the name, 
and press the redact button again to close the tag. Note that these tags 
will only be visible in the page source of the website. Before publishing 
something, you can view the text from a bot's point of view by pressing 
the 'Preview' button (note that you first need to select this option from 
the plugin settings menu). 

Also note that when uninstalling the plugin, any manually tagged names 
will remain tagged. If you want to remove the tags, you will have to 
remove them manually as well, by going back and editing the content. 

#### Automatic name detection ####
You can also set the plugin to automatically try to detect personal names, 
and redact them accordingly. This automatic name detection is accomplished 
by using a simple set of rules, written as regular expressions: 
1. It will match a single word with the first letter capitalized, as long 
as that word is not at the beginning of the sentence.
2. It will match two or more consecutive words starting with the first 
letter capitalized, as long as the first word is not at the beginning 
of the sentence.

Names that have been tagged manually will continue to be tagged until the 
tags are manually removed (so if you at a later date should wish to remove 
tags from a name, you will have to go back and edit the post, comment or 
page in question). Automatic tagging is done on the spot whenever the 
content is requested by a search engine bot. This means that the content 
in the database is left unchanged, and no tags are saved along with the 
text.

Detecting whether or not a visitor to the site is a web crawler, is done 
by checking the "User-Agent" header of the client software originating the 
request (see the 
<a href="http://en.wikipedia.org/wiki/User_agent">Wikipedia page</a> for 
more information on this). Whenever a visitor requests to view the content, 
be it a page, comment, or post, the plugin will check the user-agent 
string up against a list containing a set of known search engine bot 
names. If the User-Agent matches a name in the list, the plugin will 
redact any tagged content before returning it to the bot. Upon 
installation, the plugin will add a default set of bot names to the list. 
The user can then freely add or delete names to or from the list.

Note that while the plugin is primarily meant as a way of preventing 
search engines from indexing personal names, it can, in theory, also be 
used to prevent disclosure of other types of personal data, by manually 
tagging it in the same manner as you would do names.

#### Plugin settings ####
You can change the settings for the plugin in the 'Name Redactor Settings' 
sub menu, located in the 'Tools' menu in the admin panel. The Name 
Redactor settings menu is organized into three different options pages, 
with tabs to make navigation easier. The option pages are organized as 
follows: Options, Opt-in/opt-out, and Bots.

* The Options page allows you to change all the different settings of the 
plugin, like which redact-mode to use. Each setting is accompanied by an 
explanation of what it does.
* The Opt-in/opt-out page allows you to create a list of names that should 
always be redacted, regardless of any automatic name detection, or names 
that should *not* be redacted automatically. Each name in the list is 
accompanied by an opt-in or opt-out status. Names that are opt-in will be 
tagged automatically, while names that are opt-out will not. You can add 
or remove names to/from the list, as well as change the opt-in/opt-out 
status of each name in the list at any time.
* The Bots page displays a list of search engine bot names. Whenever 
someone visits the site, the plugin will check the visitor (or rather, 
its user agent) up against this list, and if the visitor matches a name 
in the list (which means the visitor is a search engine bot), personal 
names that have been tagged will be redacted before the content is 
returned to the bot. For example, the search engine bot from Google is 
named Googlebot. So by adding the name Googlebot to the list, you avoid 
personal names (that have been tagged for redaction) being indexed by 
Google. The plugin comes with a default set of bot names, and you can 
add or remove names at any time.

##### Credit #####
The original idea for this plugin comes from Gisle Hannemyr
<a href="http://hannemyr.com/index.php">http://hannemyr.com/index.php</a>.

== Installation ==

1. Go to 'Plugins' -> 'Add New' in the Wordpress admin panel.
2. Under 'Search', type in 'Name Redactor'.
3. Click 'Install Now' to install the plugin. 
4. When the plugin has been installed, click 'Activate Plugin' to activate 
the plugin. 
5. You can change the settings for the plugin in the 'Name Redactor 
Settings' sub menu, located in the 'Tools' menu in the admin panel.

Or

1. Download and unzip the folder 'name-redactor.zip' to the 
'/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. You can change the settings for the plugin in the 'Name Redactor 
Settings' sub menu, located in the 'Tools' menu in the admin panel.

== Uninstallation ==

1. If you want the plugin to remove all tables and options associated with 
the plugin upon deactivation, you can choose this in the Name Redactor 
Settings sub menu, located in the 'Tools' menu in the admin panel.
2. Deactivate the plugin through the 'Plugins' menu in WordPress.
3. If you want to completely remove the plugin, click 'Delete' next to the 
plugin name in the 'Plugins' menu in WordPress, or delete the folder named 
'name-redactor' in the '/wp-content/plugins/' directory. Clicking 'Delete' 
in the 'Plugins' menu ensures that all tables and options associated with 
the plugin are removed as well. 
4. Note that when uninstalling the plugin, any manually tagged names 
will remain tagged. If you want to remove the tags, you will have to 
remove them manually as well, by going back and editing the content. 

== Frequently Asked Questions ==

None.

== Screenshots ==

1. screenshot-1.png - The Options tab, which controls various plugin 
settings. 
2. screenshot-2.png - The Options tab (cont.), which controls various 
plugin settings. 
3. screenshot-3.png - The Opt-in/opt-out tab, where the admin can maintain 
lists of opt in/opt out names. 
4. screenshot-4.png - The Bots tab, where the admin can maintain a list of 
bot names for the plugin to check against.
5. screenshot-5.png - If the 'add a redact button' option is selected in 
the Options tab, a redact button will show up in HTML editor for manual 
tagging. 
6. screenshot-6.png - The redact button has been clicked, generating a 
redact tag in the text window. 
7. screenshot-7.png - Two personal names have been manually tagged in 
the text.
8. screenshot-8.png - This is what a normal visitor will see when viewing 
the text. Both personal names will appear as normal.
9. screenshot-9.png - This is what the search engine robot will see when 
viewing the text. Both personal names have been replaced with the text 
[redacted].

== Changelog ==

= 1.0.1 =
* Fixed a bug in the uninstall.php file, resulting in an error message when deleting the plugin.
* Made some minor code changes in name-redactor.php.
* Made some minor changes to the readme.txt.
* Moved the screenshots from the downloadable folder into the /assets directory, reducing the file size of the name-redactor.zip.

== Upgrade Notice ==

= 1.0.1 =
This version fixes a bug in the uninstall.php file, resulting in an error message when deleting the plugin. An upgrade is recommended.
