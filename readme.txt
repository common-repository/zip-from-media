=== ZIP from Media ===
Contributors: Katsushi Kawamori
Donate link: https://shop.riverforest-wp.info/donate/
Tags: archive, compress, media, zip
Requires at least: 4.7
Requires PHP: 8.0
Tested up to: 6.6
Stable tag: 1.07
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Compress from Media Library to ZIP archive.

== Description ==

= Compress to ZIP archive =
* Compression and Download.
* Maintain folder structure.
* Only original files. Does not include thumbnails.
* Perform background processing and notify by e-mail.
* Sibling plugin -> [Media from ZIP](https://wordpress.org/plugins/media-from-zip/).

= Note =
* On servers where PHP max_execution_time is fixed to a short time (30 seconds), you may time out if you want to generate to zip from a large number of medias.

= How it works =
[youtube https://youtu.be/PSJd3ElUlNQ]

== Installation ==

1. Upload `zip-from-media` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

none

== Screenshots ==

1. Compress to ZIP archive
2. Settings

== Changelog ==

= [1.07] 2024/03/05 =
* Fix - Changed file operations to WP_Filesystem.

= 1.06 =
Supported WordPress 6.4.
PHP 8.0 is now required.

= 1.05 =
Added processing for cases where media does not exist.

= 1.04 =
Supported WordPress 5.7.

= 1.03 =
Supported XAMPP.

= 1.02 =
Displays the size of the generated ZIP file.

= 1.01 =
Fixed a translation issue.

= 1.00 =
Initial release.

== Upgrade Notice ==

= 1.00 =
Initial release.
