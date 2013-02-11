<?php
/*
Plugin Name: Prism Code Highlighting
Description: Highlights code on the site.
Author: Luke Woodward
Version: 1.0
Author URI: http://luke-woodward.com
*/

/**
 * Wraps everything in a class to keep stuff out of global scope.
 */
class WPC_Prism{
	/**
	 * The one and only instance of this class.
	 */
	private static $_instance = false;
	/**
	 * Prevents the plugin from being initialized twice.
	 */
	private static $_inited = false;
	/**
	 * Gets the one and only instance of this class
	 *
	 * @return obj The one instance of this class.
	 */
	public static function get_instance(){
		if ( ! self::$_instance instanceof WPC_Prism )
			self::$_instance = new WPC_Prism;
		return self::$_instance;
	}
	/**
	 * The available languages.
	 */
	private $languages = array( 'markup', 'css', 'javascript', 'php', 'clike' );
	/**
	 * The protected code blocks
	 */
	private $codeblocks = array();
	/**
	 * Sets up everything for the plugin
	 *
	 * @return void.
	 */
	private function __construct(){
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'the_content', array( $this, 'preserve_line_breaks' ), 5 ); //before other wp content filters
		add_shortcode( 'code', array( $this, 'block_output' ) );
		add_shortcode( 'icode', array( $this, 'inline_output' ) );
	}
	/**
	 * Enqueues the necessary scripts and styles.
	 *
	 * @return void.
	 */
	public function enqueue_scripts(){
		wp_enqueue_script( 'prism', plugins_url( '/prism/inc/prism.js' ), array(), '', true );
		wp_enqueue_style( 'prism-style', plugins_url( '/prism/inc/prism.css' ) );
	}
	/**
	 * Filters linebreaks out of our block shortcode so that they don't get littered with
	 * <br /> and <p> tags in wpautop. 
	 * 
	 * @param string $content The post content to filter
	 * @return string The filtered post content
	 */
	public function preserve_line_breaks( $content ){
		preg_match_all( '#\[code.*?\](.*?)\[/code\]#sm', $content, $matches );
		if ( $matches ) {
			for( $i = 0; $i < count( $matches ); $i++ ) {
				if ( isset( $matches[1][ $i ] ) ){
					$this->codeblocks[$i] = $matches[1][ $i ];
					$content = str_replace( $matches[1][ $i ], '{{cb' . $i . '}}', $content );
				}
			}
		}
		return $content;
	}
	/**
	 * Takes care of shortcodes for code highlighting a block
	 *
	 * @param array $atts Index [0] defines our language.
	 * @param string $code The code we are highlighting.
	 * @return string The code marked up for highlighting.
	 */
	public function block_output( $atts, $code ){
		if( ! isset( $atts[0] ) || ! in_array( $atts[0], $this->languages ) )
			return;

		$language = $atts[0];
		$code = trim( preg_replace( '#{{cb([0-9]*?)}}#e', '$this->codeblocks[ "$1" ]' , $code ) );

		$return = '<div class="codeblock">';
		$return .= '<h3>' . $language . '</h3>';
		$return .= '<pre><code class="language-' . $language . '">' . htmlentities( $code ) . '</code></pre>';
		$return .= '</div>';
		return $return;
	}
	/**
	 * Takes care of shortcodes for code highlighting for some inline code.
	 *
	 * @param array $atts Index [0] defines our language.
	 * @param string $code The code we are highlighting.
	 * @return string The code marked up for highlighting.
	 */
	public function inline_output( $atts, $code ){
		if( ! isset( $atts[0] ) || ! in_array( $atts[0], $this->languages ) )
			return;

		$language = $atts[0];
		return '<code class="language-' . $language . '">' . htmlentities2( $code ) . '</code>';
	}
}
add_action( 'plugins_loaded', array( 'WPC_Prism', 'get_instance' ) );