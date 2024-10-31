=== Wordpress Post to SMF ===
Contributors: kochab
Donate link: http://www.zonca.org/wordpress-post-to-smf#donate

Tags: comments, integration, forum, smf
Requires at least: 2.6
Tested up to: 2.7
Stable tag: 1.5
Version: 1.5

This plugin adds Wordpress posts as topics in SimpleMachines Forum 1.1.7, and links the WP post to the comments in the forum 

== Description ==

This plugin adds Wordpress posts as topics in SimpleMachines Forum 1.1.7, and allows to put a link to this topic below the Wordpress post for the user to add comments

More information on the plugin homepage:
[Wordpress plugin post to smf](http://www.zonca.org/wordpress-post-to-smf)

Main features:

* SMF post is added when the Wordpress post is published
* SMF post is updated any time the WP post is updated
* template function for linking from WP post to SMF post
* link back from the SMF post to the WP post
* it is possible to show on the SMF post just an excerpt 

Admin page options:

* SMF Board ID to post to 
* SMF User ID to set the post author
* SMF Forum path and Url
* Link from Forum to Wordpress text
* Max characters for SMF topic

If you use this plugin, please donate to support future improvements by clicking on the "Donate to this plugin" link

== Installation ==

1. Upload `post-to-smf.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Edit file `Sources/Security.php` of your SMF forum, comment out the lines from 819 to 823, which means the function `is_admin`. it is there just for backward compatibility and conflicts with a WP function
1. Go to the admin page post_to_smf and configure the plugin
1. Place `<p class="comments"><?php if(function_exists('post_to_smf_link')) {post_to_smf_link('Comments: read comments in the Forum'); } ?></p>` 
in your template, tipically in single.php near to the comments, outside of the php tags.

== Frequently Asked Questions ==

= How do I identify the numeric ID of a SMF board? =

The url of your board should be:

`http://example.com/forum/index.php?board=7.0`

7 is the board ID

= How do I identify the numeric ID of a SMF user? =

the url of a user profile should be:

`http://example.com/forum/index.php?action=profile;u=25`

25 is the user ID

= How do I find the absolute path of my forum? =

[Absolute path of a file](http://phpforbeginners.com/?p=23)

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the directory of the stable readme.txt, so in this case, `/tags/4.3/screenshot-1.png` (or jpg, jpeg, gif)
2. This is the second screen shot
