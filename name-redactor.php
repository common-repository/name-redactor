<?php
/*
Plugin Name: Name Redactor
Plugin URI: http://wordpress.org/extend/plugins/name-redactor/
Description: Redact personal names whenever the site is viewed by a search engine robot.
Version: 1.0.1
Author: Joakim Valla
Author URI: http://folk.uio.no/javalla/index.html
License: Copyright 2013  Joakim Valla

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! class_exists( 'Name_Redactor' ) ) {
	class Name_Redactor {
		
		/**
		 * @var string The version of the plugin
		 */
		var $plugin_version = '1.0.1';
		
		/**
		 * @var string The slug this plugin uses
		 */
		var $plugin_slug = 'nameredactor';
		
		/**
		 * @var object The name redactor settings object
		 */
		var $settings = null;
		
		/**
		 * @var string The name of the general settings key
		 */
		var $general_settings_key = 'name_redactor_options';
		
		/**
		 * @var string The name of the opt-in/opt-out settings key
		 */
		var $opt_in_out_settings_key = 'name_redactor_opt_in_opt_out';
		
		/**
		 * @var string The name of the bot settings key
		 */
		var $bot_settings_key = 'name_redactor_bots';
		
		/**
		 * @var string The name of the bot table
		 */
		var $bot_table_name = 'name_redactor_bots';
		
		/**
		 * @var string The name of the opt-in/opt-out table
		 */
		var $opt_in_out_table_name = 'name_redactor_opt_in_opt_out';
		
		
		function NameRedactor() { 
			$this->__construct();
		}
		
		
		function __construct() {
			add_action( 'admin_print_scripts', array( &$this, 'nameRedactor_custom_buttons' ) );
			add_action( 'the_content', array( &$this, 'preview_check' ) );  
			
			$options = get_option( $this->general_settings_key );
			if ( $options['redact_mode'] !== 'NO_REDACT' ) {
				if ( $options['filter_posts_and_pages'] == 'true' ) {
					add_filter('the_content', array( &$this, 'bot_check') ); //filter for posts and pages
				}
				if ( $options['filter_comments'] == 'true' ) {
					add_filter('comment_text', array( &$this, 'bot_check') ); //filter for comments
				}
			}
			
			if ( is_admin() ) {
				include_once(plugin_dir_path(__FILE__) . 'php/name-redactor-settings.php');
				$this->settings = new Name_Redactor_Settings( $this );
			}
		}
		
		
		/*****************************
		* When the plugin is installed and activated, this function is called to create 
		* tables for bot names and opt-in/opt-out names, unless they already exists.
		* Checks for minimum WordPress version, and prevents the plugin from activating if 
		* it doesn't meet the required minimum version. 
		* @param string $wp Minimum version of WordPress required for this plugin
		******************************/
		function on_activate( $wp = '3.3' ) {
			global $wp_version;
			// version_compare compares two "PHP-standardized" version number strings
			if ( version_compare( $wp_version, $wp, '<' ) ) {
				$flag = 'WordPress';
				deactivate_plugins( basename( __FILE__ ) );
				wp_die('<p>The <strong>Name Redactor</strong> plugin requires Wordpress version 3.3 or greater. Your Wordpress version is '.$wp_version.'.</p>','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
			}
			
			global $wpdb;
			
			$bots_table_name = $wpdb->prefix . $this->bot_table_name; 
			$bot_table_structure = "CREATE TABLE IF NOT EXISTS $bots_table_name (
				id INT(9) NOT NULL AUTO_INCREMENT,
				bot_name VARCHAR(100) NOT NULL,
				UNIQUE (bot_name),
				UNIQUE KEY id (id)
			);";
			
			$opt_in_opt_out_table_name = $wpdb->prefix . $this->opt_in_out_table_name; 
			$opt_in_opt_out_table_structure = "CREATE TABLE IF NOT EXISTS $opt_in_opt_out_table_name(
				id INT(9) NOT NULL AUTO_INCREMENT,
				name VARCHAR(100) NOT NULL,
				opt_in_out VARCHAR(10) NOT NULL,
				UNIQUE (name),
				UNIQUE KEY id (id)
			);";
			
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($bot_table_structure);
			dbDelta($opt_in_opt_out_table_structure);
			
			$this->add_robots_to_db( $wpdb->prefix . "name_redactor_bots" ); // add a small number of robot names as default
		}
		
		
		/*
		 * Upon plugin deactivation, delete options related to the plugin,
		 * as well as any DB tables created by the plugin.
		 */
		function on_deactivate() {
			$options = get_option( $this->general_settings_key );
			if ( $options['cleanup_on_deactivate'] == 'true' ) {
				global $wpdb;
				$bots_table = $wpdb->prefix . $this->bot_table_name;
				$opt_in_opt_out_table = $wpdb->prefix . $this->opt_in_out_table_name; 
				
				// Delete any options
				if ( get_option( $this->general_settings_key ) != false ) {
					delete_option( $this->general_settings_key );
				}
				
				// Delete tables
				$wpdb->query("DROP TABLE IF EXISTS $bots_table");
				$wpdb->query("DROP TABLE IF EXISTS $opt_in_opt_out_table");
			}
		}
		
		
		/*****************************
		* Function for adding a small set of robot-names to the DB.
		******************************/
		function add_robots_to_db( $table_name ) {
			global $wpdb;
			// default list of robots:
			$robots = array('googlebot', 'yammybot', 'openbot', 'yahoo', 'slurp', 'msnbot', 'ia_archiver', 
				'lycos', 'scooter', 'altavista', 'teoma', 'gigabot', 'bingbot');
			foreach ( $robots as $robot ) {
				$query = $wpdb->prepare("INSERT IGNORE INTO $table_name (bot_name) VALUES ( %s )", $robot );
				$wpdb->query($query);
			}
		}

		
		/*****************************
		* Function is called if a bot is detected. Searches content for tagged names, redacts them and returns content.
		******************************/
		function redactContent( $content ) {
			//Searches subject for all matches to the regular expression and puts them in $names:
			preg_match_all( "/\<redact content=\"name\"\>(.*?)\<\/redact\>/", $content, $names );
			
			for( $i = 0; $i < count( $names[0] ); $i++ ) {
				$content = str_replace( $names[1][$i], '[redacted]', $content );
			}
			
			// If two or three [redacted] next to each other, only separated by whitespace; replace with a single [redacted]:
			preg_match_all( "/(\<redact content=\"name\"\>\[redacted\]\<\/redact\>\s\<redact content=\"name\"\>\[redacted\]\<\/redact\>|
			\<redact content=\"name\"\>\[redacted\]\<\/redact\>\s\<redact content=\"name\"\>\[redacted\]\<\/redact\>\s\<redact content=\"name\"\>\[redacted\]\<\/redact\>)+/", $content, $redacted);
			
			for( $i=0; $i < count( $redacted[0]); $i++ ) {
				$content = str_ireplace( $redacted[1][$i],'[redacted]',$content );
			}
			return $content;
		}
		
		
		/*****************************
		* Function for adding xml-like tags to the text
		******************************/
		function add_tags_to_names( $content, $names_to_redact ) {
			$options = get_option( $this->general_settings_key );
			if ( $options['opt_in'] == 'true' ) {
				$content = $this->opt_in( $content );
			}
			foreach ( $names_to_redact as $name ) { //for each name
				//code for inserting xml-like tags around all occurrences of a name, if it hasn't already been tagged:
				$pattern = "/\b$name\b(?![^<]*<\/redact>)/";
				$value = '<redact content="name">' . $name . '</redact>';
				$content = preg_replace( $pattern, $value, $content );
			}
			return $content;
		}
		
		
		/*****************************
		* If this option is selected in admin menu, it allows the publisher to see which 
		* content will be redacted when published and viewed by search engine robots.
		* Uses the conditional tag is_preview().
		******************************/
		function preview_check( $content ) {  		
			// detect if the page or post you are looking at is in preview mode:
			if( is_preview() ) {  
				$options = get_option( $this->general_settings_key );
				if ( $options['preview_redact'] == 'true' ) {
					if ( $options['redact_mode'] == 'REDACT_MANUAL' ) {
						if ( $options['opt_in'] == 'true' ) {
							$content = $this->redactContent( $this->opt_in( $content ) );
						}
						else {
							$content = $this->redactContent( $content );
						}
					}
					elseif ( $options['redact_mode'] == 'SIMPLE_REDACT' ) {
						$content = $this->redactContent( $this->add_tags_to_names( $content, $this->detectNames( $content ) ) );
					}
				}
			}
			return $content;
		}
		
		
		/*****************************
		* Tags all names found in the opt-in list.
		******************************/
		function opt_in( $content ) {
			global $wpdb;
			$table_name = $wpdb->prefix . "name_redactor_opt_in_opt_out"; 
			$result = $wpdb->get_col( $wpdb->prepare("SELECT name FROM $table_name WHERE opt_in_out = %s", 'opt-in' ) ); 
			if ( !is_null( $result ) ) { // name(s) found
				usort( $result, array( $this, 'sortByLength' ) ); //sort names by length (longest array values appear first).
				//code for inserting xml-like tags around all occurrences of a name, if it hasn't already been tagged:
				foreach ( $result as $name ) {
					$pattern = "/\b$name\b(?![^<]*<\/redact>)/i"; // remove 'i' in order to make it case-sensitive
					$value = '<redact content="name">' . $name . '</redact>';
					$content = preg_replace( $pattern, $value, $content );
				}
			}
			return $content;
		}
		
		
		/*****************************
		* Takes an array of "proper nouns", and any items that are found in the opt-out list will be removed from the array.
		******************************/
		function opt_out( $arr ) {
			// this is a supplement to automatic name detection. detected names will be checked up against this list, 
			// and if found, will not be tagged and redacted. 
			global $wpdb;
			$table_name = $wpdb->prefix . "name_redactor_opt_in_opt_out"; 
			$result = $wpdb->get_col( $wpdb->prepare("SELECT name FROM $table_name WHERE opt_in_out = %s", 'opt-out' ) ); 
			if ( !is_null( $result ) ) { // name(s) found
				usort( $result, array( $this, 'sortByLength' ) ); //sort names by length (longest array values appear first).
				foreach ( $result as $name ) {
					if ( in_array( $name, $arr, true ) ) {
						$index = array_search( $name, $arr ); // find the right index
						if( $index !== FALSE ) {
							unset( $arr[ $index ] ); // remove item at given index
						}
					}
				}
				$arr = array_values( $arr ); // 'reindex' array
			}
			return $arr;
		}
		
		
		/*****************************
		* Redacts names based on simple rules
		******************************/
		function DetectNames( $content ) {
			/*
			The /m option together with ^ matches newlines.
			Because the negative look-behind in the regex doesn't support an unknown number of spaces, it will fail 
			if the first word in a sentence is preceded by more than one space. 
			If the first word in the sentence is directly preceded by a character (e.g. a quotation mark), 
			it will redact that word as well, provided the first letter is capitalized.
			*/
			
			// matches two or more consecutive words starting with capital letter not at beginning of sentence:
			preg_match_all( "/(?<!\.\s|^)[A-Z][a-z]+(?=\s[A-Z])(?:\s[A-Z][a-z]+)*/m", $content, $name_candidates_multiple );
			
			// Matches one word with first letter capitalized not at the beginning of sentence. 
			preg_match_all( "/(?<!\.\s|^)\b[A-Z][a-z]+\b/m", $content, $name_candidates_single );
			
			$name_candidates = array_merge( $name_candidates_multiple[0], $name_candidates_single[0] ); //setting the final list of names to redact
			$name_candidates = array_unique( $name_candidates ); //remove duplicates
			usort( $name_candidates, array( $this, 'sortByLength' ) ); //sort names by length
			
			// check if any of the name candidates should be opted out
			$options = get_option( $this->general_settings_key );
			if ( $options['opt_out'] == 'true' && !empty( $name_candidates ) ) {
				$name_candidates = $this->opt_out( $name_candidates );
			}
			return $name_candidates;
		}
		

		/*****************************
		* Function for sorting an array by length
		******************************/
		function sortByLength( $a, $b ){
			return strlen( $b ) - strlen( $a );
		}
		
		
		/*****************************
		* Checks if user agent is a search robot, and if so, calls the redact-function.
		******************************/
		function bot_check( $content ) {
			global $wpdb;
			$options = get_option( $this->general_settings_key );
			if (isset ( $_SERVER['HTTP_USER_AGENT'])) {
				$table_name = $wpdb->prefix . "name_redactor_bots";
				$result = $wpdb->get_results("SELECT bot_name FROM $table_name");
				foreach ($result as $bot) {
					if ( strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ), $bot->bot_name ) !== FALSE) {
						if ( $options['redact_mode'] == 'REDACT_MANUAL' ) {
							if ( $options['opt_in'] == 'true' ) {
								$content = $this->redactContent( $this->opt_in( $content ) );
							}
							else {
								$content = $this->redactContent( $content );
							}
						}
						elseif ( $options['redact_mode'] == 'SIMPLE_REDACT' ) {
							$content = $this->redactContent( $this->add_tags_to_names( $content, $this->detectNames( $content ) ) );
						}
						break;
					}
				}
			}
			return $content;
		}
		
		
		/* 
		 * Depending on the settings in the adminOptions, adds a redact button to the 
		 * html and/or visual editor. Requires WordPress ver. 3.3 and above
		 */
		function nameRedactor_custom_buttons() {
			//$adminOptions = $this->settings->get_name_redactor_settings();
			$options = get_option( $this->general_settings_key );
			if ( $options['add_quicktag_button'] == 'true' ) {
				wp_enqueue_script(
					'nameRedactor_custom_quicktags',
					plugins_url( 'js/name-redactor-custom-quicktags.js' , __FILE__ ),
					array( 'quicktags' )
				);
			}
		}
		

	} //end class NameRedactor
} //end if !class NameRedactor

//Instantiate class
if (class_exists("Name_Redactor")) {
	$nameRedactor = new Name_Redactor();
	register_activation_hook( __FILE__, array( &$nameRedactor, 'on_activate') );
	register_deactivation_hook( __FILE__, array( &$nameRedactor, 'on_deactivate') );
}