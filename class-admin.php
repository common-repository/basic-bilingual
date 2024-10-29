<?php

class BasicBilingualAdmin {

	function __construct($plugin) {
		$this->plugin = $plugin;
		$this->add_settings();
		add_action('do_meta_boxes', array(&$this, 'customize_meta_boxes'));

		/* Use the save_post action to do something with the data entered */
		add_action('save_post', array(&$this, 'save_post_data'));

		add_filter('manage_posts_columns', array(&$this, 'post_columns'));
		add_action('manage_posts_custom_column', array(&$this, 'custom_post_columns'), 10, 2);
		add_filter('manage_pages_columns', array(&$this, 'post_columns'));
		add_action('manage_pages_custom_column', array(&$this, 'custom_post_columns'), 10, 2);

		global $pagenow;
		if ('edit.php' == $pagenow) {
			add_action('restrict_manage_posts', array(&$this, 'restrict_posts_by_lang'));
			add_filter('posts_where' , array(&$this, 'posts_where_lang'));
			add_action('admin_head-edit.php', array(&$this, 'admin_styles'));
		}
	}

	function admin_styles() { ?>
		<style>.fixed #lang { width: 10%; }</style><?php
	}

	function restrict_posts_by_lang() {
		$site_languages = $this->plugin->get_site_languages();
		$all_languages = $this->plugin->get_all_languages();
		$filter = (isset($_GET['lang'])) ? $_GET['lang'] : '<none>';

		echo '<select name="lang" id="lang">';
		echo '<option value="">' .  __('View all languages') . '</option>';
		foreach ($site_languages as $lang) {
			echo '<option value="' . $lang . '" ' . selected($filter, $lang, false) . '>' . $all_languages[$lang] . '</option>';
		}
		echo '</select>';
	}

	function posts_where_lang($where) {
		global $wpdb;
		if (isset($_GET['lang']) && !empty($_GET['lang'])) {
			$language = $_GET['lang'];
			$meta_key = BB_POST_LANGUAGE;
			$where .= " AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='$meta_key' AND meta_value='$language')";
		}

		return $where;
	}

	function post_columns($defaults) {
		$defaults['lang'] = __('Language', 'basic-bilingual');
		return $defaults;
	}

	function custom_post_columns($column, $post_id) {
		if ($column == 'lang') {
			$post_language = get_post_meta($post_id, BB_POST_LANGUAGE, true);
			if (!empty($post_language)) {
				$languages = $this->plugin->get_all_languages();
				echo $languages[$post_language];
			}
		}
	}

	function customize_meta_boxes() {
		remove_meta_box('postexcerpt', 'post', 'normal');
		remove_meta_box('postexcerpt', 'page', 'normal');
		add_meta_box('bb-post-language', __( 'Language', 'basic-bilingual' ), array(&$this, 'meta_box_post_language'), 'post', 'side');
		add_meta_box('bb-post-language', __( 'Language', 'basic-bilingual' ), array(&$this, 'meta_box_post_language'), 'page', 'side');
		add_meta_box('bb-post-excerpts', __( 'Excerpts', 'basic-bilingual' ), array(&$this, 'meta_box_post_excerpts'), 'post', 'normal');
		add_meta_box('bb-post-excerpts', __( 'Excerpts', 'basic-bilingual' ), array(&$this, 'meta_box_post_excerpts'), 'page', 'normal');
	}

	/**
	 * Prints the inner fields for the language post/page section
	 */
	function meta_box_post_language() {
		$site_languages = $this->plugin->get_site_languages();
		$all_languages = $this->plugin->get_all_languages();

		// retrieving existing language, or setting to default if new post
		$post_language = $this->plugin->get_post_language();

		// Use nonce for verification
		echo '<input type="hidden" name="bb_noncename" id="bb_noncename" value="' . wp_create_nonce(plugin_basename(__FILE__)) . '" />';

		echo '<select name="' . BB_POST_LANGUAGE . '" id="' . BB_POST_LANGUAGE . '">';
		foreach ($site_languages as $lang) {
			echo '<option value="' . $lang . '" ' . selected($post_language, $lang, false) . '>' . $all_languages[$lang] . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Prints the inner fields for the other excerpt_box post/page section
	 */
	function meta_box_post_excerpts() {
		$post_language = $this->plugin->get_post_language();
		$site_languages = $this->plugin->get_site_languages();
		$all_languages = $this->plugin->get_all_languages();
		$excerpts = $this->plugin->get_post_excerpts();

		// Place the post language at the end of the list
		$site_languages = array_diff($site_languages, array($post_language));
		$site_languages[] = $post_language;
		?>

		<style>
			.excerpts-panel { display: none; background-color: white; border: 1px solid #dfdfdf; }
			.excerpts-panel textarea { width: 98%; padding: 12px 8px; border: none; }
		</style>
		<input type="hidden" name="bb_noncename" id="bb_noncename" value="<?php echo wp_create_nonce(plugin_basename(__FILE__)); ?>" />
		<div id="excerpts">
			<ul class="category-tabs ">
				<?php foreach ($site_languages as $lang): ?>
		    	<li><a href="#tab-<?php echo $lang; ?>"><?php echo $all_languages[$lang]; ?></a></li>
		    	<?php endforeach; ?>
			</ul>
			<?php foreach ($site_languages as $lang): ?>
			<div id="tab-<?php echo $lang; ?>" class="excerpts-panel"><textarea rows="10" cols="80" name="excerpt-<?php echo $lang; ?>"
				id="excerpt-<?php echo $lang; ?>" lang="<?php echo $lang; ?>"><?php echo isset($excerpts[$lang]) ? $excerpts[$lang] : ''; ?></textarea></div>
	    	<?php endforeach; ?>
		</div>
		<script>jQuery(document).ready(function($) {
				$('#excerpts li:first').addClass('tabs');
				$('#excerpts .excerpts-panel:first').show();
				$('#excerpts a').click(function(){
					var t = $(this).attr('href');
					$(this).parent().addClass('tabs').siblings('li').removeClass('tabs');
					$('.excerpts-panel').hide();
					$(t).show();
					return false;
				});
			});</script>
		<p><?php _e('Write an excerpt of your post in the different languages you use on your blog. Short and sweet, or long and detailed.', 'basic-bilingual'); ?></p><?php
	}

	function add_settings_link($links) {
		$url = home_url('/wp-admin/options-general.php?page=basic-bilingual');
		$links[] = '<a href="' . esc_url($url) . '">' . __('Settings') . '</a>';
		return $links;
	}

	function add_settings_field($id, $title) {
		$callback = 'field_' . str_replace('-', '_', $id);
		add_settings_field($id, $title, array(&$this, $callback), 'basic-bilingual',
				'default', array('field_name' => "basic-bilingual-options[$id]"));
	}

	function field_site_languages($args) {
		extract($args);
		$site_languages = $this->plugin->get_site_languages();
		$all_languages = $this->plugin->get_all_languages();
		$default_language = $this->plugin->get_default_language();

		// Just make sure it's there...
		if (!in_array($default_language, $site_languages)) {
			$site_languages[] = $default_language;
		} ?>
		<style>
			#languages-list { width: 25em; height: 16em; padding: 0.5em 0.8em; border: 1px solid #dfdfdf;
				background-color: white; overflow: auto; border-radius: 3px; -moz-border-radius: 3px; }
			#site-languages { border-bottom: 1px solid #dfdfdf; padding-bottom: 2px; }
		</style>
		<div id="languages-list"><div id="site-languages">
		<?php foreach ($site_languages as $lang):
			$name = $all_languages[$lang];
			if ($lang == $default_language): ?>
				<div>
					<input type="hidden" name="<?php echo $field_name; ?>[]" value="<?php echo $lang; ?>" />
					<label style="color:darkgray"><input type="checkbox" name="" value="" checked="true"
						disabled="disabled" />&nbsp;<?php echo $name; ?> (<?php _e('default site language', 'basic-bilingual'); ?>)</label>
				</div>
			<?php else: ?>
				<div>
					<label><input type="checkbox" name="<?php echo $field_name; ?>[]"
						value="<?php echo $lang; ?>" checked="checked" />&nbsp;<?php echo $name; ?></label>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
		</div>
		<?php foreach ($all_languages as $lang => $name): ?>
			<?php if (!in_array($lang, $site_languages)): ?>
				<div>
					<label><input type="checkbox" name="<?php echo $field_name; ?>[]"
						value="<?php echo $lang; ?>" />&nbsp;<?php echo $name; ?></label>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
		</div>
		<p><em><?php _e('Select all the languages you will be using on that site.', 'basic-bilingual') ?>
			<?php _e('The WordPress language is always selected, please see <a href="http://codex.wordpress.org/Installing_WordPress_in_Your_Language">Installing WordPress in Your Language</a> if you need to change it.', 'basic-bilingual'); ?></em></p>
		<?php
	}

	function field_auto_filter_content($args) {
		extract($args); ?>
		<label>
			<input type="checkbox" name="<?php echo $field_name; ?>"
				value="1" <?php checked($this->plugin->get_auto_filter_content()); ?> />&nbsp;<?php
			_e('Uncheck this option if you want to use template tags in your theme instead of letting the plugin do the work for you.', 'basic-bilingual'); ?>
		</label><?php
	}

	function field_before_excerpt($args) {
		extract($args); ?>
		<p><input type="text" name="<?php echo $field_name; ?>" class="large-text code"
			value="<?php echo htmlspecialchars($this->plugin->get_before_excerpt()); ?>" /></p>
		<p><em><?php _e('HTML to put before the excerpts when they are output into a post (use the %lg placeholder to specify where to write the language of the excerpt).', 'basic-bilingual'); ?></em></p><?php
	}

	function field_after_excerpt($args) {
		extract($args); ?>
		<p><input type="text" name="<?php echo $field_name; ?>" class="large-text code"
			value="<?php echo htmlspecialchars($this->plugin->get_after_excerpt()); ?>" /></p>
		<p><em><?php _e('HTML to put after the excerpts.', 'basic-bilingual'); ?></em></p><?php
	}

	function field_use_accept_header($args) {
		extract($args); ?>
		<label>
			<input type="checkbox" name="<?php echo $field_name; ?>"
				value="1" <?php checked($this->plugin->get_use_accept_header()); ?> />&nbsp;<?php
				_e('Use the Accept-Language header sent by the visitor\'s browser to decide what excerpts to show. Deselect to always show all excerpts.', 'basic-bilingual'); ?>
		</label><?php
	}

	function field_postfix_titles($args) {
		extract($args); ?>
		<label>
			<input type="checkbox" name="<?php echo $field_name; ?>"
				value="1" <?php checked($this->plugin->get_postfix_titles()); ?> />&nbsp;<?php
				_e('Postfix titles with the language code so that visitors know what\'s the main language of the content (especially useful when the title appears in links).', 'basic-bilingual'); ?>
		</label><?php
	}

	/**
	 * Adds a settings
	 */
	function add_settings() {
		add_submenu_page('options-general.php', __('Basic Bilingual Options', 'basic-bilingual'), __('Basic Bilingual', 'basic-bilingual'), 'manage_options', 'basic-bilingual', array(&$this, 'options_page'));
		add_filter('plugin_action_links_basic-bilingual/basic-bilingual.php', array(&$this, 'add_settings_link'));
		add_settings_section('default', '', '', 'basic-bilingual');
		register_setting('basic-bilingual', 'basic-bilingual-options');
		$this->add_settings_field(BB_SITE_LANGUAGES, __('Site languages', 'basic-bilingual'));
		$this->add_settings_field(BB_AUTO_FILTER_CONTENT, __('Filter content automatically', 'basic-bilingual'));
		$this->add_settings_field(BB_BEFORE_EXCERPT, __('Before excerpt', 'basic-bilingual'));
		$this->add_settings_field(BB_AFTER_EXCERPT, __('After excerpt', 'basic-bilingual'));
		$this->add_settings_field(BB_USE_ACCEPT_HEADER, __('Use Accept-Language header', 'basic-bilingual'));
		$this->add_settings_field(BB_POSTFIX_TITLES, __('Postfix titles with language code', 'basic-bilingual'));
	}

	function options_page() {
		if (isset($_GET['migrate'])) {
			$count = $this->migrate();
			if ($count) {
				$message = sprintf(_n('Successfully migrated %d post or page.', 'Successfully migrated %d posts and pages.',
						$count, 'basic-bilingual'), $count);
			} else {
				$message = __('Nothing found to migrate.', 'basic-bilingual');
			}?>
			<div class="updated"><p><?php echo $message; ?></p></div>
		<?php } ?>
		<div class="wrap">
			<h1><?php _e('Basic Bilingual Options', 'basic-bilingual'); ?></h1>
			<div id="main-container" class="postbox-container metabox-holder" style="width:75%;"><div style="margin:0 8px;">
				<div class="postbox">
					<div class="inside">
						<form method="POST" action="options.php"><?php
						settings_fields('basic-bilingual');
						do_settings_sections('basic-bilingual');
						submit_button();
						?></form>
					</div> <!-- .inside -->
				</div> <!-- .postbox -->
				<div class="postbox">
					<h3 style="cursor:default;"><span><?php _e('Migration', 'basic-bilingual'); ?></span></h3>
					<div class="inside">
						<p>
							<p><?php _e('If you have been using this plugin prior to version 1.0 then you will need to migrate your existing data.', 'basic-bilingual'); ?>
								<?php _e('To migrate, first make sure to select the same two languages you had before in the box above and save the changes, then click on the "Migrate" button below.', 'basic-bilingual'); ?></p>
							<p><strong><?php _e('It is strongly recommended that you backup your database before proceeding.', 'basic-bilingual'); ?></strong></p>
							<span class="submit"><a href="<?php echo esc_url(home_url('/wp-admin/options-general.php?page=basic-bilingual&migrate')); ?>" class="button button-primary"><?php _e('Migrate data from old Plugin', 'basic-bilingual'); ?></a></span>
						</p>
					</div> <!-- .inside -->
				</div> <!-- .postbox -->
				</div></div> <!-- #main-container -->

			<div id="side-container" class="postbox-container metabox-holder" style="width:24%;"><div style="margin:0 8px;">
				<div class="postbox">
					<h3 style="cursor:default;"><span><?php _e('Do you like this Plugin?', 'basic-bilingual'); ?></span></h3>
					<div class="inside">
						<p><?php _e('Please consider offering something to Stephanie from her <a href="http://www.amazon.de/exec/obidos/wishlist/3ZN17IJ7B1XW/">Amazon wishlist</a> or click on the Paypal button below to make a donation.', 'basic-bilingual'); ?></p>
						<div style="text-align:center">
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
							<input type="hidden" name="cmd" value="_s-xclick">
							<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
							<input type="image" src="https://www.paypalobjects.com/en_US/CH/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
							<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
							</form>
						</div>
						<p><?php _e('We also need volunteers to translate that plugin into more languages.', 'basic-bilingual'); ?>
							<?php _e('If you wish to help then contact <a href="https://twitter.com/stephtara">@stephtara</a> on Twitter or see <a href="http://climbtothestars.org/contact/">Stephanie\'s contact page</a>.', 'basic-bilingual'); ?></p>
					</div> <!-- .inside -->
				</div> <!-- .postbox -->
				<div>
					<a class="twitter-timeline" href="https://twitter.com/cvedovini/basic-bilingual" data-widget-id="378520771070414848">Tweets from @cvedovini/basic-bilingual</a>
					<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
				</div>
			</div></div> <!-- #side-container -->

		</div><?php
		}

	/**
	 * When the post is saved, saves our custom data
	 */
	function save_post_data($post_id) {
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if (!isset($_POST['bb_noncename']) || !wp_verify_nonce($_POST['bb_noncename'], plugin_basename(__FILE__))) {
			return $post_id;
		}

		// Check permissions
		if ('page' == $_POST['post_type']) {
			if (!current_user_can('edit_page', $post_id)) {
				return $post_id;
			}
		} else {
			if (!current_user_can('edit_post', $post_id)) {
				return $post_id;
			}
		}

		// update language and other language excerpts custom fields
		$post_language = $this->get_request_data(BB_POST_LANGUAGE);
		$site_languages = $this->plugin->get_site_languages();
		$other_excerpts = array();

		foreach ($site_languages as $lang) {
			$excerpt = $this->get_request_data('excerpt-' . $lang);
			if ($excerpt) {
				$other_excerpts[$lang] = $excerpt;
				if ($post_language == $lang) {
					remove_action('save_post', array(&$this, 'save_post_data'));
					wp_update_post(array('ID' => $post_id, 'post_excerpt' => $excerpt));
					add_action('save_post', array(&$this, 'save_post_data'));
				}
			}
		}

		update_post_meta($post_id, BB_POST_LANGUAGE, $post_language);
		update_post_meta($post_id, BB_POST_EXCERPTS, $other_excerpts);
	}

	// general custom field update function
	function get_request_data($field) {
		if (isset($_REQUEST[$field])) {
			return stripslashes($_REQUEST[$field]);
		}

		return false;
	}

	function migrate() {
		$site_languages = $this->plugin->get_site_languages();

		// The Query
		$query = new WP_Query(array (
			'posts_per_page' => -1,
			'meta_query' => array(array(
				'key' => 'other-excerpt',
				'compare' => 'EXISTS'))));

		$count = 0;
		while ($query->have_posts()) {
			$query->next_post();
			$post = $query->post;

			$post_language = get_post_meta($post->ID, 'language', true);
			$other_language = array_pop(array_diff($site_languages, array($post_language)));

			$excerpt = $post->post_excerpt;
			$other_excerpt = get_post_meta($post->ID, 'other-excerpt', true);

			$other_excerpts = array();
			if (!empty($excerpt)) $other_excerpts[$post_language] = $excerpt;
			if (!empty($other_excerpt)) $other_excerpts[$other_language] = $other_excerpt;

			update_post_meta($post->ID, BB_POST_LANGUAGE, $post_language);
			update_post_meta($post->ID, BB_POST_EXCERPTS, $other_excerpts);

			$count++;
		}

		// Restore original Post Data
		wp_reset_postdata();
		return $count;
	}

}