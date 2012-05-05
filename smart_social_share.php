<?php

/*
Plugin Name: Smart Social Share
Plugin URI: (プラグインの説明と更新を示すページの URI)
Description: Google+, Twitter, Facebook Like ボタンを追加する
Version: (プラグインのバージョン番号。例: 1.0)
Author: (プラグイン作者の名前)
Author URI: (プラグイン作者の URI)
License: (ライセンス名の「スラッグ」 例: GPL2)
*/

register_uninstall_hook(__FILE__, 'smart_social_share_uninstall');

new SmartSocialShare();
new SmartSocialShareOptions();


/// アンインストール処理
function smart_social_share_uninstall() {
	delete_option(SmartSocialShare::OPTION_NAME);
}


/// ベースクラス
class SmartSocialShareBase {
	const CSS_FILE				= 'smart_social_share.css';
	const CSS_FILE_ADMIN		= 'smart_social_share_admin.css';
	const TEXTDOMAIN			= 'smart_social_share';
	const OPTION_GROUP			= 'smart_social_share_options';
	const OPTION_NAME			= 'smart_social_share_options';
	const SETTING_SECTION_STYLE	= 'smart_social_share_options_style';
	const SETTING_SECTION_LIST	= 'smart_social_share_options_list';
	const SETTING_PAGE			= 'smart_social_share_page';

	public $default_options;

	// デフォルト設定
	function default_options() {
		return array(
						'button_style_home' => 'button_count',
						'button_style_page' => 'button_count',
						'buttons' => 'gl_plusone tw_tweet fb_like'
					);
	}

	/// オプションを取得する
	function get_option($name = false) {
		if (empty($this->default_options))
			$this->default_options = $this->default_options();

		$opts = get_option(self::OPTION_NAME);

		if (! is_array($opts)) {
			if (! empty($opts))
				delete_option(self::OPTION_NAME);

			$opts = $this->default_options;
		} else {
			$update = false;

			if ($name) {
				if (! array_key_exists($name, $opts)) {
					$opts[$name] = $this->default_options[$key];
					$update = true;
				}
			} else {
				foreach ( $this->default_options as $key => $value ) {
					if (! array_key_exists($key, $opts)) {
						$opts[$key] = $value;
						$update = true;
					}
				}
			}

			if ($update)
				update_option(self::OPTION_NAME, $opts);
		}

		if ($name)
			return $opts[$name];
		else
			return $opts;
	}
}


/// 設定画面
class SmartSocialShareOptions extends SmartSocialShareBase {
	public $button_style_menu;

	function __construct() {
		load_theme_textdomain(self::TEXTDOMAIN, plugin_dir_path(__FILE__).'/languages');

		// 設定
		add_action('admin_menu', array($this, 'plugin_menu'));
		add_action('admin_init', array($this, 'settings_api_init'));
		add_action('admin_init', array($this, 'add_admin_script'));
	}

	/// ボタンのスタイル
	function button_style_menu() {
		return array(
						'none'			=> __('None'),
						'button'		=> __('Button Only', self::TEXTDOMAIN),
						'button_count'	=> __('Button Count', self::TEXTDOMAIN),
						'box_count'		=> __('Box Count', self::TEXTDOMAIN)
					);
	}

	/// メニューの追加
	function plugin_menu() {
		add_options_page('Smart Social Share', 'Smart Social Share', 'manage_options', __FILE__, array($this, 'options_page'));
	}

	/// 設定画面
	function options_page() {
		$reset_name = self::OPTION_NAME.'[reset]';
		$opts = get_option(self::OPTION_NAME);

		if (isset($opts['reset'])) {
			// reset が設定されていたら設定値を削除する
			delete_option(self::OPTION_NAME);
		}

		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2> <?php echo esc_html('Smart Social Share').' '.__('Settings') ?></h2>
			<form method="POST" action="options.php">

			<?php settings_fields(self::OPTION_GROUP); ?>
			<?php do_settings_sections(self::SETTING_PAGE); ?>

			<p class="submit">
				<input type="submit" value="<?php esc_attr_e(__('Save Changes')) ?>" class="button-primary">
				<input type="submit" value="<?php esc_attr_e(__('Reset')) ?>" class="button-secondary" name="<?php echo $reset_name; ?>">
				</form>
			</p>
			</form>
		</div>
		<?php
	}

	/// 設定画面用のスクリプトを追加
	function add_admin_script() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-widget');
		wp_enqueue_script('jquery-ui-mouse');
		wp_enqueue_script('jquery-ui-sortable');

		wp_register_script('sortable_list', plugin_dir_url(__FILE__).'js/sortable_list.js');
		wp_enqueue_script('sortable_list');

		wp_enqueue_style('my-css', plugin_dir_url(__FILE__).self::CSS_FILE_ADMIN);
	}

	/// 設定の登録
	function settings_api_init() {
		add_settings_section(self::SETTING_SECTION_STYLE, __('Button Style', self::TEXTDOMAIN),
			array($this, 'setting_section_callback'), self::SETTING_PAGE);

		add_settings_field('setting_button_style_home', __('Home').' / '.__('Archive', self::TEXTDOMAIN),
			array($this, 'setting_button_style_home'), self::SETTING_PAGE, self::SETTING_SECTION_STYLE);

		add_settings_field('setting_button_style_page', __('Post').' / '.__('Page'),
			array($this, 'setting_button_style_page'), self::SETTING_PAGE, self::SETTING_SECTION_STYLE);

		add_settings_section(self::SETTING_SECTION_LIST, __('Button List', self::TEXTDOMAIN),
			array($this, 'setting_section_callback'), self::SETTING_PAGE);

		add_settings_field('setting_buttons', __('Show Buttons', self::TEXTDOMAIN),
			array($this, 'setting_buttons'), self::SETTING_PAGE, self::SETTING_SECTION_LIST);

		register_setting(self::OPTION_GROUP, self::OPTION_NAME);
	}

	function setting_section_callback() {
//		echo '<p></p>';
	}

	/// select タグでプルダウンメニューを作成する
	function select_option($menu, $value, $name) {
		echo '<select name="'.$name.'">';

		foreach ( $menu as $key => $text ) {
			echo '<option value="'.esc_attr($key).'" '.selected($value, $key).'>'.esc_html($text).'</option>';
		}

		echo '</select>';
	}

	/// スタイルの設定
	function setting_button_style($key) {
		$value = $this->get_option($key);

		if (empty($this->button_style_menu))
			$this->button_style_menu = $this->button_style_menu();

		$this->select_option($this->button_style_menu, $value, self::OPTION_NAME."[$key]");
	}

	/// ホーム・カーかイブの設定
	function setting_button_style_home() {
		$this->setting_button_style('button_style_home');
	}

	/// 投稿・個別ページの設定
	function setting_button_style_page() {
		$this->setting_button_style('button_style_page');
	}

	/// ボタン一覧
	function setting_buttons() {
		$name = self::OPTION_NAME.'[buttons]';
		$id = self::OPTION_NAME.'_buttons';
		$buttons = $this->get_option('buttons');
		?>
		<script>
		(function() {
			var menu = {'gl_plusone': 'Google+', 'tw_tweet': 'Twitter', 'fb_like': 'Facebook'};
			var selectors = ['#smart_social_share_show_buttons', '#smart_social_share_hide_buttons'];
			var value_selector = '#<?php echo $id; ?>';
			var li_class = 'ui-dragbox';

			sortable_list(selectors, menu, value_selector, li_class);
		})();
		</script>

		<div class="sortable_list">
			<h3><?php _e('Show'); ?></h3>
			<ul id="smart_social_share_show_buttons" class="connectedSortable"></ul>
		</div>
		<div class="sortable_list">
			<h3><?php _e('Disable'); ?></h3>
			<ul id="smart_social_share_hide_buttons" class="connectedSortable"></ul>
		</div>

		<input type="hidden" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo $buttons; ?>">
		<?php
	}
}


/// 主機能
class SmartSocialShare extends SmartSocialShareBase {
	function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'add_scripts'));
		add_action('wp_footer', array($this, 'add_fb_script'));
		add_filter('the_content', array($this, 'add_buttons'));
	}

	/// ブログのロケールを ja_JP 形式で取得する
	function blog_locale() {
		$locale_map = array('ja' => 'ja_JP');

		$lang = get_bloginfo('language');
		$locale = str_replace('-', '_', $lang);

		if (array_key_exists($locale, $locale_map))
			$locale = $locale_map[$locale];

		return $locale;
	}

	/// Facebook SDK の追加
	function add_fb_script() {
		$locale = $this->blog_locale();
		?>
		<div id="fb-root"></div>
		<script>(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/<?php echo $locale; ?>/all.js#xfbml=1";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, "script", "facebook-jssdk"));</script>
		<?php
	}

	/// スクリプトを追加
	function add_scripts() {
		$lang = preg_replace('/[-_].+$/', '', get_bloginfo('language'));

		// Google+
		wp_register_script('plusone', 'https://apis.google.com/js/plusone.js');
		wp_enqueue_script('plusone');
		wp_localize_script('plusone', '___gcfg', array('lang' => $lang));

		// Twitter
		wp_register_script('twitter', 'http://platform.twitter.com/widgets.js');
		wp_enqueue_script('twitter');

		// Facebook
		/* @attention ver= が入るためにうまく動作しない。代わりに wp_footer でスクリプトを追加する */

		// CSS
		wp_enqueue_style('my-css', plugin_dir_url(__FILE__).self::CSS_FILE);
	}

	/// class付の <div> タグで囲む
	function div($text, $classes = array()) {
		return '<div class="'.join(' ', $classes).'">'.$text.'</div>';
	}

	/// 連想配列を連結して属性値の文字列を作成する
	function atts_to_str($hash) {
		$atts = array();

		foreach ( $hash as $key => $value ) {
			if ($value)
				array_push($atts, $key.'="'.esc_attr($value).'"');
			else
				array_push($atts, $key);
		}

		return join(' ', $atts);
	}

	/// HTMLの生成
	function generate_button_container($buttons, $data_count = 'none') {
		$content = '';
		$permalink = get_permalink();

		// CSS の class
		$container_classes = array('entry-meta', 'smart-social-share-container');

		$button_class = 'smart-social-share-button';
		$plusone_classes = array($button_class, 'gl_plusone');
		$twitter_classes = array($button_class, 'tw_tweet');
		$facebook_classes = array($button_class, 'fb_like');

		//
		$plusone_atts['href'] = $permalink;
		$twitter_atts['data-url'] = $permalink;
		$fb_like_atts = array(
								'data-href' => $permalink,
								'data-show-faces' => 'true',
								'data-send' => 'false',
								'data-font' => 'arial'
							);

		// Tweet するデータテキストを設定
		$twitter_atts['data-text'] = get_the_title();

		switch ($data_count) {
		case 'none':
			return;
		case 'button_count':
			array_push($container_classes, 'button-count');
			$plusone_atts['size'] = 'medium';
			$fb_like_atts['data-layout'] = 'button_count';
			break;
		case 'box_count':
			array_push($container_classes, 'box-count');
			$plusone_atts['size'] = 'tall';
			$twitter_atts['data-count'] = 'vertical';
			$fb_like_atts['data-layout'] = 'box_count';
			break;
		case 'button':
			array_push($container_classes, 'none-count');
			$plusone_atts['size'] = 'medium';
			$plusone_atts['count'] = 'false';
			$twitter_atts['data-count'] = 'none';
			$fb_like_atts['data-layout'] = 'button_count';
			break;
		default:
			return;
		}

		foreach ( $buttons as $key => $value ) {
			switch ($value) {
				case 'gl_plusone':
					$content .= $this->div('<g:plusone '.$this->atts_to_str($plusone_atts).'></g:plusone>', $plusone_classes);
					break;

				case 'tw_tweet':
					$content .= $this->div('<a href="https://twitter.com/share" class="twitter-share-button" '.$this->atts_to_str($twitter_atts).'>Tweet</a>', $twitter_classes);
					break;

				case 'fb_like':
					$content .= $this->div('<div class="fb-like" '.$this->atts_to_str($fb_like_atts).'></div>', $facebook_classes);
					break;
			}
		}

		return $this->div($content, $container_classes);
	}

	/// ボタンを追加
	function add_buttons($content) {
		$buttons = preg_split('/\s+/', $this->get_option('buttons'));
		if (count($buttons) == 0) return $content;

		if (is_single() or is_page())
			$data_count = $this->get_option('button_style_page');
		else
			$data_count = $this->get_option('button_style_home');

		$content .= $this->generate_button_container($buttons, $data_count);

		return $content;
	}
}

