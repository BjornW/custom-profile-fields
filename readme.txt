=== Custom Profile Fields ===
Contributors: BjornW
Tags: user, profile, user profile, extend profile, custom profile, author profile
Requires at least: 3.01
Tested up to: 3.01
Stable tag: trunk

Custom Profile Fields allows you to customize WordPress user profile's with new fields

== Description ==

Custom Profile Fields allows you to extend WordPress' user profiles with custom
input fields, such as dates, images, texts etc. The plugin adds one table to 
your database to keep track of its fields. All other data, most importantly 
field values are saved as user meta information in the usermeta table. 
This makes it easy to search through the data or access the data with other 
plugins.

The plugin works perfectly with WordPress Multisite installations.  

== Installation ==
1. Upload `custom-profile-fields` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the directory of the stable readme.txt, so in this case, `/tags/4.3/screenshot-1.png` (or jpg, jpeg, gif)
2. This is the second screen shot

== Changelog ==

= 0.1 =
This is the first public version.  

== Todo & Notes ==

- uploading requires javascript to be active.
- removing images does not remove them from the wp-content directory
- keep track of how many times a field has been filled in by a user 
and allow to see all user names of those who have and have not filled in 
a particular field. This might become a plugin in itself :) 
- when removing a field, also remove the related data from the user profiles

