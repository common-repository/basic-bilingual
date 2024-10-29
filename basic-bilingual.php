<?php
/*
Plugin Name: Basic Bilingual
Plugin URI: http://climbtothestars.org/wordpress/basic-bilingual/
Description: Makes managing your blog with two languages less cumbersome.
Author: Stephanie Booth
Author URI: http://climbtothestars.org/
Version: 1.3.5
Text Domain: basic-bilingual
Domain Path: /languages

# The code in this plugin is free software; you can redistribute the code aspects of
# the plugin and/or modify the code under the terms of the GNU Lesser General
# Public License as published by the Free Software Foundation; either
# version 3 of the License, or (at your option) any later version.

# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
# LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
# OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
# WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
#
# See the GNU lesser General Public License for more details.
*/

// Metadata keys
define('BB_POST_LANGUAGE', 'bb-post-language');
define('BB_POST_EXCERPTS', 'bb-post-excerpts');

// Options keys
define('BB_SITE_LANGUAGES', 'site-languages');
define('BB_USE_ACCEPT_HEADER', 'use-accept-header');
define('BB_POSTFIX_TITLES', 'postfix-titles');
define('BB_BEFORE_EXCERPT', 'before-excerpt');
define('BB_AFTER_EXCERPT', 'after-excerpt');
define('BB_AUTO_FILTER_CONTENT', 'auto-filter-content');


add_action('plugins_loaded', array('BasicBilingualPlugin', 'get_instance'));

class BasicBilingualPlugin {

	private static $instance;

	public static function get_instance() {
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	function __construct() {
		// Make plugin available for translation
		// Translations can be filed in the /languages/ directory
		add_filter('load_textdomain_mofile', array(&$this, 'smarter_load_textdomain'), 10, 2);
		load_plugin_textdomain('basic-bilingual', false, dirname(plugin_basename(__FILE__)) . '/languages/' );

		register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
		add_action('init', array(&$this, 'init'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
	}

	function init() {
		if (!is_admin()) {
			wp_register_style('basic-bilingual', plugins_url('style.css', __FILE__), false, '1.0');
			add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
		}

		if ($this->get_auto_filter_content()) {
			add_action('the_content', array(&$this, 'filter_the_content'));
			add_filter('the_title', array(&$this, 'filter_the_title'));
		}

		// Won't work as expected...
		// add_filter('locale', array(&$this, 'filter_the_locale'));

		$this->setup_rewrite_rules();
	}

	function smarter_load_textdomain($mofile, $domain) {
		if ($domain == 'basic-bilingual' && !is_readable($mofile)) {
			extract(pathinfo($mofile));
			$pos = strrpos($filename, '_');

			if ($pos !== false) {
				# cut off the locale part, leaving the language part only
				$filename = substr($filename, 0, $pos);
				$mofile = $dirname . '/' . $filename . '.' . $extension;
			}
		}

		return $mofile;
	}

	function admin_menu() {
		require_once 'class-admin.php';
		$this->admin = new BasicBilingualAdmin($this);
	}

	function setup_rewrite_rules() {
		if (isset($_GET['action']) && $_GET['action'] == 'deactivate' &&
				$_GET['plugin'] == 'basic-bilingual/basic-bilingual.php') {
			// Don't do anything if we're deactivating this plugin
			return;
		}

		global $wp_rewrite;
		$langs = $this->get_site_languages();
		// temporary hack to accomodate ticket https://core.trac.wordpress.org/ticket/36812
		// $regexp = '(' . implode('|', $langs) . ')';
		$regexp = '([a-z]{2})';

		$category_base = get_option('category_base') ? get_option('category_base') : 'category';
		$tag_base = get_option('tag_base') ? get_option('tag_base') : 'tag';
		$rules = get_option('rewrite_rules');

		add_rewrite_tag('%lang%', $regexp, 'lang=');
		add_filter('query_vars', array(&$this, 'query_vars'));
		add_filter('post_link', array(&$this, 'post_link'), 10, 2);
		add_action('pre_get_posts', array(&$this, 'posts_by_language'));
		add_filter('template_include', array(&$this, 'template_include'));

		add_permastruct('basic-bilingual-root', 'language/%lang%');
		add_permastruct('basic-bilingual-date', '%lang%/%year%/%monthnum%/%day%');
		add_permastruct('basic-bilingual-category', "%lang%/{$category_base}/%category%");
		add_permastruct('basic-bilingual-tag', "%lang%/{$tag_base}/%tag%");
		add_permastruct('basic-bilingual-author', "%lang%/{$wp_rewrite->author_base}/%author%");
		add_permastruct('basic-bilingual-type', '%lang%/type/%post_format%');
		add_permastruct('basic-bilingual-search', "%lang%/{$wp_rewrite->search_base}/%search%");

		if (!isset($rules["language/$regexp/?$"])) {
			flush_rewrite_rules();
		}
	}

	function query_vars($vars) {
		$vars[] = 'lang';
		return $vars;
	}

	function post_link($permalink, $post) {
		if (false === strpos($permalink, '%lang%'))	return $permalink;

		$lang = urlencode($this->get_post_language(false, $post->ID));
		return str_replace('%lang%', $lang, $permalink);
	}

	function posts_by_language($query) {
		$lang = $query->get('lang');

		if (!$query->is_singular() && $lang) {
			$meta_query[] = array(
								'key' => BB_POST_LANGUAGE,
								'value' => $lang,
								'compare' => '='
							);

			if ($lang == $this->get_default_language()) {
				$meta_query['relation'] = 'OR';
				$meta_query[] = array(
									'key' => BB_POST_LANGUAGE,
									'value' => $lang,
									'compare' => 'NOT EXISTS'
								);
			}

			$query->set('meta_query', $meta_query);
		} else {
			// remove this or for some reason we end-up with an archive page
			// instead of a singular page
			unset($query->query_vars['lang']);
		}

	}

	function template_include($template) {
		if (get_query_var('lang')) {
			$templates = array(
					'language-' . get_query_var('lang') . '.php',
					'language.php',
					'archive.php');
			if (is_paged()) $templates[] = 'paged.php';
			$templates[] = 'index.php';
			return locate_template($templates);
		}

		return $template;
	}

	function enqueue_scripts() {
		wp_enqueue_style('basic-bilingual');
	}

	function get_option($option_name, $default) {
		$options = get_option('basic-bilingual-options');

		if ($options && is_array($options)) {
			return (isset($options[$option_name])) ? $options[$option_name] : false;
		}

		return get_option('bb-' . $option_name, $default);
	}

	function get_site_languages() {
		return $this->get_option(BB_SITE_LANGUAGES, array('en', 'fr'));
	}

	function get_use_accept_header() {
		return $this->get_option(BB_USE_ACCEPT_HEADER, false);
	}

	function get_postfix_titles() {
		return $this->get_option(BB_POSTFIX_TITLES, true);
	}

	function get_before_excerpt() {
		return $this->get_option(BB_BEFORE_EXCERPT, '<blockquote class="other-excerpt" lang="%lg">');
	}

	function get_after_excerpt() {
		return $this->get_option(BB_AFTER_EXCERPT, '</blockquote>');
	}

	function get_auto_filter_content() {
		return $this->get_option(BB_AUTO_FILTER_CONTENT, true);
	}

	function get_default_language() {
		$all_languages = $this->get_all_languages();
		$wp_language = explode('_', get_locale());
		$wp_language = $wp_language[0];

		if (isset($all_languages[$wp_language])) {
			return $wp_language;
		}

		return 'en';
	}

	function get_post_language($default=false, $post_id=false) {
		if ($post_id === false) $post_id = get_the_ID();
		$post_language = get_post_meta($post_id, BB_POST_LANGUAGE, true);
		if (empty($post_language)) $post_language = ($default) ? $default : $this->get_default_language();
		return $post_language;
	}

	function get_post_excerpts() {
		$excerpts = get_post_meta(get_the_ID(), BB_POST_EXCERPTS, true);
		if (!is_array($excerpts)) $excerpts = array();
		$excerpts[$this->get_post_language()] = get_post()->post_excerpt;
		return $excerpts;
	}

	function filter_excerpts($excerpts, $languages) {
		//remove spaces
		$languages = preg_replace('/\s+/', '', $languages);
		// remove quality values
		$languages = preg_replace('/;q=\d+(\.\d+)?/i', '', $languages);
		// remove country codes
		$languages = preg_replace('/-[a-z]+/i', '', $languages);
		$languages = array_unique(explode(',', $languages));
		$filtered = array();

		foreach ($languages as $lang) {
			if (key_exists($lang, $excerpts)) $filtered[$lang] = $excerpts[$lang];
		}

		return $filtered;
	}

	function the_excerpts($before=false, $after=false) {
		$post_language = $this->get_post_language();
		$excerpts = $this->get_post_excerpts();
		$content = '';

		if (!empty($excerpts)) {
			if (!$before) $before = $this->get_before_excerpt();
			if (!$after) $after = $this->get_after_excerpt();

			if ($this->get_use_accept_header() && !is_feed()) {
				$excerpts = $this->filter_excerpts($excerpts, $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			}

			foreach ($excerpts as $lang => $excerpt) {
				if (!empty($excerpt) && $lang != $post_language) {
					// Add some paragraphs
					$excerpt = '<span class="bb-lang">[' . $lang . ']</span> ' . $excerpt;
					$excerpt = wpautop($excerpt);

					// add a nice little lang attribute where asked for
					$the_before = str_replace('%lg', $lang, $before);
					$the_after = str_replace('%lg', $lang, $after);

					$content .= $the_before . $excerpt . $the_after;

					// backward compatibility, removing extra <p>
					$content = preg_replace('|<p([^>]*)>\s*<p>|im', '<p$1>', $content);
					$content = preg_replace('|</p>\s*</p>|im', '</p>', $content);
				}
			}
		}

		return $content;
	}

	function filter_the_content($content) {
		$lang = $this->get_post_language();

		if (!empty($lang)) {
			// If we are in the feed then add a prefix like in the excerpt.
			$prefix = (is_feed()) ? "[$lang] " : '';
			$content = "<div lang='$lang'>$prefix$content</div>";
		}

		return $this->the_excerpts() . $content;
	}

	function filter_the_title($title) {
		if (in_the_loop() && !is_admin()) {
			$lang = $this->get_post_language();

			// Check that we actually have a language and that we did not already add our
			// stuff, because this filter is called several times sometimes.
			if (!empty($lang) && (strpos($title, "<span lang='$lang'>") === false)) {
				$postfix = ($this->get_postfix_titles()) ? " <span class='bb-lang'>[$lang]</span>" : '';
				$title = "<span lang='$lang'>$title$postfix</span>";
			}
		}

		return $title;
	}

	function filter_the_locale($lang) {
		if (is_singular()) {
			return $this->get_post_language($lang);
		}

		return $lang;
	}

	function get_all_languages() {
		static $languages;

		if (!isset($languages)) {
			$languages = array(
					'ar' => __('Arabic', 'basic-bilingual'),
					'az' => __('Azerbaijani', 'basic-bilingual'),
					'bg' => __('Bulgarian', 'basic-bilingual'),
					'bn' => __('Bengali', 'basic-bilingual'),
					'bs' => __('Bosnian', 'basic-bilingual'),
					'ca' => __('Catalan', 'basic-bilingual'),
					'cs' => __('Czech', 'basic-bilingual'),
					'cy' => __('Welsh', 'basic-bilingual'),
					'da' => __('Danish', 'basic-bilingual'),
					'de' => __('German', 'basic-bilingual'),
					'el' => __('Greek', 'basic-bilingual'),
					'en' => __('English', 'basic-bilingual'),
					'eo' => __('Esperanto', 'basic-bilingual'),
					'es' => __('Spanish', 'basic-bilingual'),
					'et' => __('Estonian', 'basic-bilingual'),
					'eu' => __('Basque', 'basic-bilingual'),
					'fa' => __('Persian', 'basic-bilingual'),
					'fi' => __('Finnish', 'basic-bilingual'),
					'fr' => __('French', 'basic-bilingual'),
					'fy' => __('Frisian', 'basic-bilingual'),
					'ga' => __('Irish', 'basic-bilingual'),
					'gl' => __('Galician', 'basic-bilingual'),
					'he' => __('Hebrew', 'basic-bilingual'),
					'hi' => __('Hindi', 'basic-bilingual'),
					'hr' => __('Croatian', 'basic-bilingual'),
					'hu' => __('Hungarian', 'basic-bilingual'),
					'id' => __('Indonesian', 'basic-bilingual'),
					'is' => __('Icelandic', 'basic-bilingual'),
					'it' => __('Italian', 'basic-bilingual'),
					'ja' => __('Japanese', 'basic-bilingual'),
					'ka' => __('Georgian', 'basic-bilingual'),
					'kk' => __('Kazakh', 'basic-bilingual'),
					'km' => __('Khmer', 'basic-bilingual'),
					'kn' => __('Kannada', 'basic-bilingual'),
					'ko' => __('Korean', 'basic-bilingual'),
					'lt' => __('Lithuanian', 'basic-bilingual'),
					'lv' => __('Latvian', 'basic-bilingual'),
					'mk' => __('Macedonian', 'basic-bilingual'),
					'ml' => __('Malayalam', 'basic-bilingual'),
					'mn' => __('Mongolian', 'basic-bilingual'),
					'nb' => __('Norwegian Bokmal', 'basic-bilingual'),
					'ne' => __('Nepali', 'basic-bilingual'),
					'nl' => __('Dutch', 'basic-bilingual'),
					'nn' => __('Norwegian Nynorsk', 'basic-bilingual'),
					'pa' => __('Punjabi', 'basic-bilingual'),
					'pl' => __('Polish', 'basic-bilingual'),
					'pt' => __('Portuguese', 'basic-bilingual'),
					'ro' => __('Romanian', 'basic-bilingual'),
					'ru' => __('Russian', 'basic-bilingual'),
					'sk' => __('Slovak', 'basic-bilingual'),
					'sl' => __('Slovenian', 'basic-bilingual'),
					'sq' => __('Albanian', 'basic-bilingual'),
					'sr' => __('Serbian', 'basic-bilingual'),
					'sv' => __('Swedish', 'basic-bilingual'),
					'sw' => __('Swahili', 'basic-bilingual'),
					'ta' => __('Tamil', 'basic-bilingual'),
					'te' => __('Telugu', 'basic-bilingual'),
					'th' => __('Thai', 'basic-bilingual'),
					'tr' => __('Turkish', 'basic-bilingual'),
					'tt' => __('Tatar', 'basic-bilingual'),
					'uk' => __('Ukrainian', 'basic-bilingual'),
					'ur' => __('Urdu', 'basic-bilingual'),
					'vi' => __('Vietnamese', 'basic-bilingual'),
					'zh' => __('Chinese', 'basic-bilingual')
			);
			asort($languages);
		}

		return $languages;
	}

}


// TEMPLATE TAGS
function bb_the_time($format="%A %d.%m.%Y<br />%Hh%M") {
	global $post;
	$plugin = BasicBilingualPlugin::get_instance();
	$language = $plugin->get_post_language();
	$locale = $language . '_' . strtoupper($language);

	// change locale
	$old_locale = setlocale(LC_ALL,"0");
	setlocale(LC_TIME, $locale);

	// write it out -- this was lifted from the_time() iirc
	$timestamp = strtotime($post->post_date);
	echo strftime($format, $timestamp);

	// make sure to restore it
	setlocale(LC_TIME, $old_locale);
}

// this one outputs the language
function bb_the_language() {
	$plugin = BasicBilingualPlugin::get_instance();
	echo $plugin->get_post_language();
}

// this outputs the other language excerpt
function bb_get_the_other_excerpt($before=false, $after=false) {
	$plugin = BasicBilingualPlugin::get_instance();
	return $plugin->the_excerpts($before, $after);
}

// this prints the other language excerpt
function bb_the_other_excerpt($before=false, $after=false) {
	$plugin = BasicBilingualPlugin::get_instance();
	echo $plugin->the_excerpts($before, $after);
}
