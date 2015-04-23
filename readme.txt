=== Peanut Butter Bar (smooth version) ===
Contributors: andrewcouch 
Tags: sticky bar, alert bar, sticky bar bottom, fixed bar, responsive bar, top bar, sticky header, hellobar, hello bar, notification bar, sticky notification bar,
Requires at least: 3.8
Tested up to: 4.1.2
Stable tag: trunk
License: GPLv2 or later

Peanut Butter Bar allows you to attach sticky bars to the roof of your site that stays visible no matter how far a user scrolls.

== Description ==

<b>A Peanut Butter Bar is all of the good stuff that sticks to the roof of your site.</b>

The plugin allows you to attach sticky bars to the roof of your site that stays visible no matter how far a user scrolls.

*   Responsive and mobile friendly.  
*   Choose from a range of tasty colors. 
*   Track clicks using Google Analytics Events using the Analytics you already use in your blog.  
*   Plugin runs within your WordPress install. No external servers to deal with.
*   Close button state persists through a user's session without cookies.    

This is the Smooth version of Peanut Butter Bar. It supports a single site-wide bar. The Crunchy (paid) version has more bits including multiple bars and attaching bars to specific posts/pages and categories. Learn more at <a href="http://peanutbutterplugin.com" target="_blank">peanutbutterplugin.com</a>.

Check out this <a href="http://peanutbutterplugin.com/help-topics/filters-and-hooks-in-pbb-smooth/" target="_blank">blog post</a> for the supported filters and actions in PBB Smooth.

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.
== Screenshots ==
1. The edit screen for a bar.

2. A Peanut Butter Bar in action.


==Readme Generator== 

This Readme file was generated using <a href = 'http://sudarmuthu.com/wordpress/wp-readme'>wp-readme</a>, which generates readme files for WordPress Plugins.

==Changelog== 

= 1.2.1 =
* XSS Security fix : add_query_arg escaping
* Enhancement : Added cache clearing for TotalCache and SuperCache when saving a bar change.

= 1.2.0 = 
* Enhancement : Added pbb_filter_bar_output filter. See http://peanutbutterplugin.com/help-topics/filters-and-hooks-in-pbb-smooth/
* Enhancement : Bar text now accepts and runs shortcodes.

= 1.1.3 =
* Bugfix : Unknown variable when building bar. (Thanks to kdot for finding this.)

= 1.1.2 = 
* Fixed a bug where for some users, the update file could not be found.

= 1.1.1 = 
* Fixed a bug when apostrophes were used in text fields

= 1.1.0 =
* Added checkbox to allow for hiding of bar without deactivating the plugin
* Added new capability 'manage_pbb' and added to Admin, Super Admin and Editor roles
* Permissions change: Editor roles can now create and manage bars
* Linktext and Link URL may be left blank to create a pure "notification bar" without a link.
* Changed labeling of boxes for clarity
* Added Link CSS Class option to add a class to the bar span tag

= 1.0.3 =
* Improved Smooth->Crunchy upgrade path
* Changed internal class name to prevent conflicts

= 1.0.2 =
* Credit link can now be deactivated.
* Link in bar now explicitly sets no border to deal with some theme compatibility issues

= 1.0.1 =
* Fixed links in info page

= 1.0 =
* Initial checkin