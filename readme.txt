=== Basic Bilingual ===
Contributors: Steph, cvedovini
Donate link: http://www.amazon.de/exec/obidos/wishlist/3ZN17IJ7B1XW/
Tags: multilingual, bilingual, translation, language, languages
Requires at least: 3.5
Tested up to: 4.9
Stable tag: 1.3.5
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html


== Description ==

Allows you to set the language of individual posts and pages and to summarize
them in other languages.

The excerpts are automatically inserted right before the post content, with the
correct language attribute. The correct language attribute is also set on the
post titles and original content.

If you were using this plugin prior to version 1.0 you will have to migrate
your posts and pages.

1. Make sure your back-up your database
1. Select the same 2 languages you were previously using in the "Site languages"
option
1. Press the "Migrate" button on the settings page

You might want to check [my work on multilingualism online](http://climbtothestars.org/focus/multilingual)
to understand what brought me to develop this plugin.

We also welcome volunteers to translate that plugin into more languages. If you
wish to help then contact [@stephtara](https://twitter.com/stephtara) on Twitter
or see [Stephanie's contact page](http://climbtothestars.org/contact/).


== Installation ==

1. Upload `basic-bilingual` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Select your site languages in the plugin's settings


== Changelog ==

= Version 1.3.5 =
- Fixing several bugs in rewrite rules and adding a workaround for a WordPress
bug that prevented the %lang% rewrite tag to work properly

= Version 1.3.4 =
- Fixing a bug that prevents old exerpts to show

= Version 1.3.3 =
- Preparing for language packs

= Version 1.3.2 =
- Fixed side effects with the media library
- Using only one option to save all the plugin's settings

= Version 1.3.1 =
- Updated Spanish translations

= Version 1.3 =
- Updated all translations

= version 1.2.2 =
- Fixing bug with `bb_the_other_excerpt` template tag.

= version 1.2.1 =
- Changes for WordPress 4.3

= version 1.2 =
- Added Serbo-Croatian translations.
- Added Dutch translations.
- Fallback mechanism to load language files in a smarter way.

= version 1.1 =
- Double and single line-breaks in excerpts are respectively converted
to `<p>...</p>` and `<br/>` tags (like for the content).
- Support for "language" column and filter in the dashboard.
- Support for rewrite tag %lang% to be used in permalink structures.
- Support for archive pages based on the language of the post (eg. /language/en/).
- Support for language based archive templates (eg. language-en.php, language.php).
- Support for URL based filtering of archive pages (works for dates, tags,
categories, authors and post type archives and for search). For example:
  - `en/category/uncategorized` to list uncatgorized posts in English.
  - `fr/author/admin` to list posts written in French by the admin user.
  - `es/2013` to list all post in Spanish written in 2013.
  - `en/search/hello+world` to search for English posts containing `hello world`.


= version 1.0 =
- Excerpts in multiple languages.
- Option to filter excerpts based on Accept-Language HTTP header.
- Migration from prior versions.
- Deprecated template tags.

= version 0.5 =
- Rewrite plugin to use classes and modern WP features.

= version 0.4 =
- Modified the be fully compatible with the last WordPress versions: drag the
language and other-excerpt boxes -- code provided by
[Luca Palli](http://lpal.li/) (27.09.2009).
- Add the "Allow empty language" option on the new Options page -- code
provided by [Luca Palli](http://lpal.li/) (27.09.2009).

= version 0.33 =
- Fixed a bunch of stuff, code provided kindly by
[Tim Isenheim](http://www.freshlabs.de/journal/).
- Half-arsed attempt to make the interface look prettier (30.01.2009).

= version 0.32 =
- Replaced the ugly "language box" in the admin section with a pretty DBX box.
Drag it to the top of the page!

= version 0.31 =
- Attempted to fix vanishing excerpts problem -- see
http://markjaquith.wordpress.com/2007/01/28/authorization-and-intentionorigination-verification-when-using-the-edit_post-hook/

= version 0.3 =
- Added stripslashes to get rid of slash problem
- No need to add template tag anymore for other-excerpt --
added automagically (30.11.2007)
- Added class to excerpt first-child

= version 0.21 =
- Fixed for WP 2.0 by replacing $postdata->ID with $post->ID (31.12.2005)
- Cosmetic changes to the edit form (03.01.2006)
- added hooks to deal with pages (03.01.2006)

= version 0.2 =
- Fixed update bug for other-excerpt (function name was wrong in action
statement!) 28.01.2005

= version 0.1 =
- Initial release


== Frequently Asked Questions ==

= Will the plugin translate my posts? =

No. Basic Bilingual gives you the possibility to write short summaries, or a
complete translation of your posts and pages, in other languages.

= Does it work with more than two languages? =

Yes, since version 1.0!

= Aren't there more complete plugins out there if I want to make all my content available in more than one language? =

Yes, there certainly are. My approach is to keep it simple and minimal, so that
it works. I've been blogging bilingually since 2000, using
[the method this plugin reproduces](http://climbtothestars.org/archives/2004/07/11/multilingual-weblog/)
since 2004.

Translating everything is just too hard. Giving a brief summary allows people
who don't understand the language you're writing the post well enough to at
least know what it's about.
See [my work on multilingualism online](http://climbtothestars.org/focus/multilingual)
for more explanations.

= Some of my excerpts are disappearing, help! =

This is a problem up to version 0.3, sorry.
See [Mark's explanation](http://markjaquith.wordpress.com/2007/01/28/authorization-and-intentionorigination-verification-when-using-the-edit_post-hook/)
and download 0.31, which should work.

= Is the plugin working with cache plugins? =

Yes, unless you are using the "Use Language-Accept header" option. In that case
you'll need to use the template tags and enclose them between `mfunc` tags
(see you cache plugin's documentation about how to use those tags).


== Screenshots ==

1. Post editing screen with Basic Bilingual installed.
2. Basic Bilingual settings page.


== Credits ==

Following is the list of people who worked on this plugin.

- [Stephanie Booth](http://climbtothestars.org/): Ideas and initial development.
- [Tim Isenheim](http://www.freshlabs.de/journal/): Contributed bug fixes.
- [Luca Palli](http://lpal.li/): Contributed bug fixes and features.
- [Claude Vedovini](http://vdvn.me/): Took over development for v1.0.
- [Nathalie Aynié](http://nathalieaynie.com/): Contributed the Italian
translations.
- [Borisa Djuraskovic](http://www.webhostinghub.com/): Contributed the
Serbo-Croatian translations.
- [Jan Spoelstra](http://www.linkedin.com/in/janspoelstra): Contributed the
Dutch translations.


== Privacy Policy ==

This plugin does not collect any personal information from your visitors.
