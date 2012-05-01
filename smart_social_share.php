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

class SmartSocialShare {
	const CSS_FILE = 'smart_social_share.css';

	function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'add_scripts'));
		add_action('wp_footer', array($this, 'add_fb_script'));
		add_filter('the_content', array($this, 'add_buttons'));
	}

	function __destruct() {
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
		$plusone_atts = array('href="'.$permalink.'"');
		$twitter_atts = array('data-url="'.$permalink.'"');
		$fb_like_atts = array('data-href="'.$permalink.'"',
							'data-show-faces="true"', 'data-send="false"', 'data-font="arial"');

		// Tweet するデータテキストを設定
		array_push($twitter_atts, 'data-text="'.get_the_title().'"');

		switch ($data_count) {
		case 'button_count':
			array_push($container_classes, 'button-count');
			array_push($plusone_atts, 'size="medium"');
			array_push($fb_like_atts, 'data-layout="button_count"');
			break;
		case 'box_count':
			array_push($container_classes, 'box-count');
			array_push($plusone_atts, 'size="tall"');
			array_push($twitter_atts, 'data-count="vertical"');
			array_push($fb_like_atts, 'data-layout="box_count"');
			break;
		default:
			array_push($container_classes, 'none-count');
			array_push($plusone_atts, 'size="medium"', 'count="false"');
			array_push($twitter_atts, 'data-count="none"');
			array_push($fb_like_atts, 'data-layout="button_count"');
			break;
		}

		$content .= $this->div('<g:plusone '.join(' ', $plusone_atts).'></g:plusone>', $plusone_classes);
		$content .= $this->div('<a href="https://twitter.com/share" class="twitter-share-button" '.join(' ', $twitter_atts).'>Tweet</a>', $twitter_classes);
		$content .= $this->div('<div class="fb-like" '.join(' ', $fb_like_atts).'></div>', $facebook_classes);

		return $this->div($content, $container_classes);
	}

	/// ボタンを追加
	function add_buttons($content) {
		$permalink = get_permalink();

		if (is_single() or is_page())
			$data_count = 'box_count';
		else
			$data_count = 'none';

		$content .= $this->generate_button_container($data_count);

		return $content;
	}
}

new SmartSocialShare();
