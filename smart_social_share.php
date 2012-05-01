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

function smart_social_share_uninstall() {
	delete_option(SmartSocialShare::OPTION_NAME);
}

class SmartSocialShare {
	const CSS_FILE = 'smart_social_share.css';
	const OPTION_GROUP = 'smart_social_share_options';
	const OPTION_NAME = 'smart_social_share_options';
	const SETTING_SECTION = 'smart_social_share_options';
	const SETTING_PAGE = 'smart_social_share_page';

	public $button_kind_menu;

	function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'add_scripts'));
		add_action('wp_footer', array($this, 'add_fb_script'));
		add_filter('the_content', array($this, 'add_buttons'));

		// 設定
		add_action('admin_menu', array($this, 'plugin_menu'));
		add_action('admin_init', array($this, 'settings_api_init'));
	}

	function __destruct() {
	}

	/// オプションを取得する
	function get_option($name = false) {
		$default_options = array(
								'custom_button_home' => 'button_count',
								'custom_button_page' => 'button_count'
							);

		$opts = get_option(self::OPTION_NAME);

		if (! is_array($opts)) {
			update_option(self::OPTION_NAME, $default_options);
			$opts = $default_options;
		} else {
			$update = false;

			if ($name) {
				if (! array_key_exists($name, $opts)) {
					$opts[$name] = $default_options[$key];
					$update = true;
				}
			} else {
				foreach ( $default_options as $key => $value ) {
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

	/// メニューの追加
	function plugin_menu() {
		add_options_page('Smart Social Share', 'Smart Social Share', 'manage_options', __FILE__, array($this, 'options_page'));
	}

	/// 設定画面
	function options_page() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2> <?php echo esc_html('Smart Social Share').' '.__('Settings') ?></h2>
			<form method="POST" action="options.php">

			<?php settings_fields(self::OPTION_GROUP); ?>
			<?php do_settings_sections(self::SETTING_PAGE); ?>

			<p class="submit">
				<input type="submit" value="<?php esc_attr_e(__('Save Changes')) ?>">
			</p>
			</form>
		</div>
		<?php
	}

	/// 設定の登録
	function settings_api_init() {
		add_settings_section(self::SETTING_SECTION, __('ボタンの表示方法'),
			array($this, 'setting_section_callback'), self::SETTING_PAGE);

		add_settings_field('setting_custom_home', __('Home'),
			array($this, 'setting_custom_home'), self::SETTING_PAGE, self::SETTING_SECTION);

		add_settings_field('setting_custom_page', __('Post').' / '.__('Page'),
			array($this, 'setting_custom_page'), self::SETTING_PAGE, self::SETTING_SECTION);

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

	function setting_custom_button($key) {
		$value = $this->get_option($key);

		if (empty($this->button_kind_menu))
			$this->button_kind_menu = array('none' => _('ボタンのみ'), 'button_count' => __('カウントあり（横）'), 'box_count' => __('カウントあり（縦）'));

		$this->select_option($this->button_kind_menu, $value, self::OPTION_NAME."[$key]");
	}

	function setting_custom_home() {
		$this->setting_custom_button('custom_button_home');
	}

	function setting_custom_page() {
		$this->setting_custom_button('custom_button_page');
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

		echo '<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/'.$locale.'/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, "script", "facebook-jssdk"));</script>';
	}

	/// スクリプトを追加
	function add_scripts() {
		// Google+
		wp_register_script('plusone', 'https://apis.google.com/js/plusone.js');
		wp_enqueue_script('plusone');

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
	function generate_button_container($data_count = 'none') {
		$content = '';
		$permalink = get_permalink();

		// CSS の class
		$container_classes = array('entry-meta', 'smart-social-share-container');

		$button_class = 'smart-social-share-button';
		$plusone_classes = array($button_class, 'plusone');
		$twitter_classes = array($button_class, 'twitter');
		$facebook_classes = array($button_class, 'facebook');

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
		default:
			array_push($container_classes, 'none-count');
			$plusone_atts['size'] = 'medium';
			$plusone_atts['count'] = 'false';
			$twitter_atts['data-count'] = 'none';
			$fb_like_atts['data-layout'] = 'button_count';
			break;
		}

		$content .= $this->div('<g:plusone '.$this->atts_to_str($plusone_atts).'></g:plusone>', $plusone_classes);
		$content .= $this->div('<a href="https://twitter.com/share" class="twitter-share-button" '.$this->atts_to_str($twitter_atts).'>Tweet</a>', $twitter_classes);
		$content .= $this->div('<div class="fb-like" '.$this->atts_to_str($fb_like_atts).'></div>', $facebook_classes);

		return $this->div($content, $container_classes);
	}

	/// ボタンを追加
	function add_buttons($content) {
		if (is_single() or is_page())
			$data_count = $this->get_option('custom_button_page');
		else
			$data_count = $this->get_option('custom_button_home');

		$content .= $this->generate_button_container($data_count);

		return $content;
	}
}

new SmartSocialShare();
