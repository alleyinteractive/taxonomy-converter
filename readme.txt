=== Taxonomy Converter ===
Contributors: mboynes, alleyinteractive
Donate link: http://alleyinteractive.com/
Tags: taxonomy, terms
Requires at least: 3.4
Tested up to: 3.5RC2
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Convert terms from one taxonomy to another, lightning fast.

== Description ==

This plugin allows you to quickly and easily convert terms from one taxonomy to another, piecemeal.

**Features:**
* Keyboard Shortcuts: "a" for the left choice, "l" for the right one.
* Options for the button labels, to make the decision faster for you.

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Tools &rarr; Taxonomy Converter
4. Set the old taxonomy and new taxonomy to get started! Optionally, enter "nicknames" for each to make your decision-making faster.
5. Deactivate the plugin once you're done

== Frequently Asked Questions ==

= I see that this plugin modifies a core database table. What gives? =

This plugin adds a column to `{prefix}_terms_taxonomy` on activation, and removes it on deactivation. This is a temporary data store to make the process faster.


== Screenshots ==


== Changelog ==

= 0.1 =
* New plugin, nothing to report

== Upgrade Notice ==

= 0.1 =
New plugin, no upgrades possible!