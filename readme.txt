=== Plugin Name ===
Contributors: klimbermann
Tags: images, attachments, gallery
Requires at least: 2.8
Tested up to: 2.9
Stable tag: trunk

Rewrites gallery shortcode, uses FancyBox to display larger versions of all images and disables attachment pages.

== Description ==

Images Fancified alters the default WordPress attachment behavior:

* Images can only be linked to their "large" versions (that way users reserve control over image size). Bigger versions of both individual images and gallery items are displayed using [FancyBox](http://fancybox.net/) automatically.
* Attachment pages are disabled and entry points for linking to it are removed. Creating link to an attachment always creates a link to the file itself. Accessing an attachment page shows a 404.
* Gallery shortcode is also overridden to produce clearer syntax similar to the marvellous [Cleaner Gallery](http://wordpress.org/extend/plugins/cleaner-gallery/) plugin by Justin Tadlock.

== Installation ==

1. Upload the `images-fancified` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 0.2 =
* First public release
