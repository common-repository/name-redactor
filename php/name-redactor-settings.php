<?php 
/**
 * This file displays the options page and tabs for the Name Redactor plugin.
 * Thanks to http://theme.fm for their guide on how to create tabs with the Settings API in Wordpress:
 * (http://theme.fm/2011/10/how-to-create-tabs-with-the-settings-api-in-wordpress-2590/)
 */
 
 if ( !class_exists( 'Name_Redactor_Settings' ) ) {
	class Name_Redactor_Settings {
		
		/**
		 * @var object The creator of this plugin
		 */
		var $parent = null;
		
		/**
		 * @var object The name-list-table object
		 */
		var $wp_name_list_table = null;
		
		/**
		 * @var object The bot-list-table object
		 */
		var $wp_bot_list_table = null;
		
		/**
		 * @protected array The plugin settings tab array
		 */
		protected $plugin_settings_tabs = array();
		
		/**
		 * @var string The name of the bot table
		 */
		var $bot_table_name = '';
		
		/**
		 * @var string The name of the opt-in/opt-out table
		 */
		var $opt_in_out_table_name = '';
		
		/**
		 * @var string The name of the general settings key
		 */
		var $general_settings_key = '';
		
		/**
		 * @var string The name of the opt-in/opt-out settings key
		 */
		var $opt_in_out_settings_key = '';
		
		/**
		 * @var string The name of the bot settings key
		 */
		var $bot_settings_key = '';
		
		
		function Name_Redactor_Settings( $owner ){
			$this->__construct( $owner );
		}
		
		
		function __construct( $owner ){
			$this->parent = $owner;
			$this->bot_table_name = $this->parent->bot_table_name;
			$this->opt_in_out_table_name = $this->parent->opt_in_out_table_name;
			$this->general_settings_key = $this->parent->general_settings_key;
			$this->opt_in_out_settings_key = $this->parent->opt_in_out_settings_key;
			$this->bot_settings_key = $this->parent->bot_settings_key;
			
			/**
			 * constants Defining the names of the setting sections, tabs and redact modes.
			 */
			// general options:
			define( "NAME_REDACTOR_PLUGIN_TAB1_SLUG1", "name-redactor-options-section-one" );
			define( "NAME_REDACTOR_PLUGIN_TAB1_SLUG2", "name-redactor-options-section-two" );
			define( "NAME_REDACTOR_PLUGIN_TAB1_SLUG3", "name-redactor-options-section-three" );
			// add names (opt-in and opt-out):
			define( "NAME_REDACTOR_PLUGIN_TAB2_SLUG", "name-redactor-section-opt-in-opt-out" );
			// add bot names:
			define( "NAME_REDACTOR_PLUGIN_TAB3_SLUG", "name-redactor-section-bot-names" );
			// redact modes:
			define( "NO_REDACT_MODE", "NO_REDACT" );
			define( "REDACT_MANUAL_MODE", "REDACT_MANUAL" );
			define( "SIMPLE_REDACT_MODE", "SIMPLE_REDACT" );
			
			add_action( 'init', array( &$this, 'load_settings' ) );
			add_action( 'admin_init', array( &$this, 'register_general_settings' ) );
			add_action( 'admin_init', array( &$this, 'register_opt_in_out_settings' ) );
			add_action( 'admin_init', array( &$this, 'register_botname_settings' ) );
			add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
		} // end __construct
		
		
		/*
		 * Loads the settings from the database into their respective arrays. 
		 * Sets default values if they're missing.
		 */
		function load_settings() {
			$settings_general = array(	
							'add_quicktag_button' => 'true',
							'filter_posts_and_pages' => 'true',
							'filter_comments' => 'true',
							'redact_mode' => 'REDACT_MANUAL', 
							'opt_in' => 'false',
							'opt_out' => 'false',
							'preview_redact' => 'false',
							'cleanup_on_deactivate' => 'false',
							'records_per_page_opt_table' => '10',
							'records_per_page_bot_table' => '10',
							);
			$general_options = get_option( $this->general_settings_key );
			if ( !empty( $general_options ) ) {
                foreach ( $general_options as $key => $option )
                    $settings_general[ $key ] = $option;
            } 
			update_option( $this->general_settings_key, $settings_general );
		}
		
		
		/*
		 * Registers the general settings via the Settings API,
		 * appends the setting to the tabs array of the object.
		 */
		function register_general_settings() {
			$this->plugin_settings_tabs[$this->general_settings_key] = 'Options'; // add to tabs-array
			register_setting( $this->general_settings_key, $this->general_settings_key );
			
			/*
			NOTE!!!
			The Settings name, the second parameter in the register_setting() function, MUST 
			match the name of the option being updated in the database!
			For example, say you have add_option( 'foo_bar', 'isfoo' ), you MUST use foo_bar 
			as the second parameter for the register_setting() function. Otherwise WordPress 
			doesn't know which setting it's supposed to update and it will fail to update.
			*/
			
			// settings section:
			add_settings_section( 
				NAME_REDACTOR_PLUGIN_TAB1_SLUG1, 				// String for use in the 'id' attribute of tags.
				'Name Redactor Mode', 							// Title of the section.
				array( &$this, 'section_redact_mode_desc' ), 	// Function that fills the section with the desired content. The function should echo its output.
				$this->general_settings_key				// The menu page on which to display this section.
			);
			add_settings_field( 
				'name_redactor_mode', 										// String for use in the 'id' attribute of tags.
				'How do you want this plugin to detect and redact names?', 	// Title of the field. 
				array( &$this, 'field_display_name_redactor_mode' ),		// Function that fills the field with the desired inputs as part of the larger form.
				$this->general_settings_key, 						// The menu page on which to display this field
				NAME_REDACTOR_PLUGIN_TAB1_SLUG1 							// The section of the settings page in which to show the box
			);
			
			// settings section:
			add_settings_section( 
				NAME_REDACTOR_PLUGIN_TAB1_SLUG2, 
				'Opt-in/Opt-out', 
				array( &$this, 'section_opt_in_out_desc' ), 
				$this->general_settings_key	
			);
			add_settings_field( 
				'opt_in_out', 
				'Check against an opt-in/opt-out list?', 
				array( &$this, 'field_display_check_optin_optout' ), 
				$this->general_settings_key, 
				NAME_REDACTOR_PLUGIN_TAB1_SLUG2  
			);
			
			// settings section:
			add_settings_section( 
				NAME_REDACTOR_PLUGIN_TAB1_SLUG3, 
				'General Settings', 
				array( &$this, 'section_general_desc' ), 
				$this->general_settings_key	
			);
			add_settings_field( 
				'add_quicktag_button', 
				'Add a redact button to the HTML editor for manual tagging?', 
				array( &$this, 'field_display_name_redactor_quicktag_button' ), 
				$this->general_settings_key, 
				NAME_REDACTOR_PLUGIN_TAB1_SLUG3  
			);
			add_settings_field( 
				'redact_from_posts', 
				'Redact names from posts and pages?', 
				array( &$this, 'field_display_redact_from_posts' ), 
				$this->general_settings_key, 
				NAME_REDACTOR_PLUGIN_TAB1_SLUG3
			);
			add_settings_field( 
				'redact_from_comments', 
				'Redact names from comments?', 
				array( &$this, 'field_display_redact_from_comments' ), 
				$this->general_settings_key, 
				NAME_REDACTOR_PLUGIN_TAB1_SLUG3  
			);
			add_settings_field( 
				'redact_from_preview', 
				'When previewing a post, view the content from the robot\'s point of view?', 
				array( &$this, 'field_display_redact_from_preview' ), 
				$this->general_settings_key, 
				NAME_REDACTOR_PLUGIN_TAB1_SLUG3  
			);
			add_settings_field( 
				'records_per_page_opt_table', 
				'How many records per page to show in the opt-in/opt-out names table?', 
				array( &$this, 'field_display_records_per_page_opt_table' ), 
				$this->general_settings_key, 
				NAME_REDACTOR_PLUGIN_TAB1_SLUG3  
			);
			add_settings_field( 
				'records_per_page_bot_table', 
				'How many records per page to show in the bot names table?', 
				array( &$this, 'field_display_records_per_page_bot_table' ), 
				$this->general_settings_key, 
				NAME_REDACTOR_PLUGIN_TAB1_SLUG3  
			);
			add_settings_field( 
				'cleanup_on_deactivate', 
				'Remove any tables and options associated with the plugin upon deactivation?', 
				array( &$this, 'field_display_cleanup_on_deactivate' ), 
				$this->general_settings_key, 
				NAME_REDACTOR_PLUGIN_TAB1_SLUG3  
			);
		}
		
		/*
		 * Registers the opt-in/opt-out settings and appends the
		 * key to the plugin settings tabs array.
		 */
		function register_opt_in_out_settings() {
			$this->plugin_settings_tabs[$this->opt_in_out_settings_key] = 'Opt-in/opt-out';
			register_setting( $this->opt_in_out_settings_key, $this->opt_in_out_settings_key );
			add_settings_section( 
				NAME_REDACTOR_PLUGIN_TAB2_SLUG, 
				'Maintain Opt-in/opt-out Lists', 
				array( &$this, 'section_second_tab_desc' ), 
				$this->opt_in_out_settings_key 
			);
			add_settings_field( 
				'add_to_opt_in_out', 
				'Type the name you want to add:', 
				//array( &$this, 'field_display_list_names' ), 
				array( &$this, 'field_display_opt_in_out' ),
				$this->opt_in_out_settings_key, 
				NAME_REDACTOR_PLUGIN_TAB2_SLUG 
			);
		}
		
		
		/*
		 * Registers the bot name settings and appends the
		 * key to the plugin settings tabs array.
		 */
		function register_botname_settings() {
			$this->plugin_settings_tabs[$this->bot_settings_key] = 'Bots';
			register_setting( $this->bot_settings_key, $this->bot_settings_key );
			add_settings_section( 
				NAME_REDACTOR_PLUGIN_TAB3_SLUG, 
				'Maintain the Bot Names List', 
				array( &$this, 'section_botname_desc' ), 
				$this->bot_settings_key 
			);
			add_settings_field( 
				'add_to_bot_list', 
				'Type the bot name you want to add:', 
				array( &$this, 'field_display_bots' ),
				$this->bot_settings_key, 
				NAME_REDACTOR_PLUGIN_TAB3_SLUG 
			);
		}
		
		
		/*
		 * The following functions provide descriptions
		 * for their respective sections, used as callbacks
		 * with add_settings_section
		 */
		function section_redact_mode_desc() { 
			?>			
			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
				<p>
				The purpose of the Name Redactor plugin is to offer publishers more control over personal data in relation to search engines. 
				It does this by checking whether the visitor to the site is human or a search engine robot. If the visitor is a search engine robot, the 
				plugin will redact any names that have been tagged with <em>&ltredact content="name"&gt&lt/redact&gt</em>. Names tagged in this way will 
				be replaced with [redacted] when the search engine robot tries to view the content. To human visitors, the names will appear as normal.
				</p>
			</div>
			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
				<p>The Name Redactor plugin offers three different modes of operation:
				<ul>
				<li>Choosing option 1 basically means that the plugin does nothing. It will neither detect or redact any names. This is an 
					alternative to disabling the plugin, if you do not want it to redact any names for a limited time period.</li>
				<li>If option 2 is chosen, the plugin will not attempt any name detection. It will redact names from the content, 
					but only those that have been tagged manually by the user.</li>
				<li>If option 3 is chosen, the plugin will attempt to detect names using a simple set of rules. It will match one or more 
					consecutive words with first letter capitalized, unless the word is at the beginning of a sentence. 
					(Note that if the first word of a sentence is preceded by more than one space, that word will be matched as well. 
					Additionally, if the first word of a sentence is directly preceded by a character, e.g. a quotation mark, 
					it will redact that word as well, provided that the first letter is capitalized). Both automatically 
					and manually tagged names will be redacted from the content.</li>
				</ul>
				</p>
			</div>
			<?php
		}
		
		function section_opt_in_out_desc() {
			?>	
			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
				<p>
				As a supplement to automatic name detection and manual tagging, you can choose to use an opt-in list and/or opt-out list. These are lists which you can 
				add names to manually. Names in the opt-in list will be tagged, while names in the opt-out list will not (unless they have been manually tagged). 
				Go to the Opt-in/opt-out settings tab to add names to these lists.
				</p>
			</div>
			<?php
		}
		
		function section_general_desc() {
			?>	
			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
				<p>
				To make manual tagging easier, you can add a redact button to the html editor. You can also choose the type of content 
				the plugin should redact tagged names from (i.e. posts, pages and comments). Setting both types of content to 'no' is 
				basically the same as choosing option 1 above. In addition, you can choose to preview posts in robot-mode, meaning that 
				you will be able to see which names will be redacted before you publish anything. This allows you to go back and either 
				prevent specific names from being tagged, or add additional names to be redacted, by either tagging/un-tagging them manually, 
				or by adding them to the opt-in/opt-out list. The next two settings lets you control how many records to show per page in the 
				opt-in/opt-out table and the bots table on their respective tabs. The last setting allows you to decide whether or not the plugin should delete 
				any database tables and options associated with the plugin when you deactivate it (note that, if you decide to delete the plugin, 
				those DB tables and options will be removed regardless of this setting).
				</p>
			</div>
			<?php
		}
		

		/*
		 * Description for opt-in/opt-out section.
		 */
		function section_second_tab_desc() { 
			?>			
			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
				<p>
				Here you can add names to the opt-in or opt-out list. <strong>Names in the opt-in list will be tagged, while names in the opt-out list will not.</strong> 
				Simply type the name you want to add in the text field below, choose the list you want to add it to from the dropdown menu, and click 
				the 'Add name' button. In the list at the bottom of the page you can see which names are already added. You can delete one or more 
				names from it by selecting the names you want to delete, select 'Delete' from the 'Bulk Actions' dropdown menu, and click the 'Apply' button. 
				You can also change the opt-status of one or more names by selecting them in the table, select 'Change Opt-status' from the 'Bulk Actions' 
				dropdown menu, and click the 'Apply' button. If the selected name belonged to the opt-in list, it will now be in the opt-out list, and vice versa.
				</p>
			</div>
			<?php
		}
		
		
		/*
		 * Description for the bot name section.
		 */
		function section_botname_desc() { 
			?>			
			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
				<p>
				This is a list containing the names of various bots (or robots). Whenever someone visits the blog and tries to view 
				the content, the plugin will check the user agent up against the names in this list. If the user agent matches a name in the list, 
				the plugin will redact any tagged content before returning it to the visitor. Upon activation, the plugin will add a default set of 
				bot names to the list. You can add or delete names to/from the list. It doesn't matter if any of the characters are capitalized, 
				as the function doing the checking will automatically make all names lowercase.  
				</p>
			</div>
			<?php
		}
		
		
		/*
		 * General Option field callback, renders a radio input.
		 */
		function field_display_name_redactor_mode() {
			$options = get_option( $this->general_settings_key );
			echo '<input type="radio" name="' . $this->general_settings_key . '[redact_mode]" value="' . NO_REDACT_MODE . '" ' . checked( NO_REDACT_MODE, $options['redact_mode'], false ) . '/> Option 1 (Do not detect or redact names).<br />';
			echo '<input type="radio" name="' . $this->general_settings_key . '[redact_mode]" value="' . REDACT_MANUAL_MODE . '" ' . checked( REDACT_MANUAL_MODE, $options['redact_mode'], false ) . '/> Option 2 (Do not detect names. Only redact names that have been tagged manually).<br />';
			echo '<input type="radio" name="' . $this->general_settings_key . '[redact_mode]" value="' . SIMPLE_REDACT_MODE . '" ' . checked( SIMPLE_REDACT_MODE, $options['redact_mode'], false ) . '/> Option 3 (Attempt to detect names using a simple set of rules. Both automatically and manually tagged names will be redacted).<br />';
		}
		
		/*
		 * General Option field callback, renders a checkbox input.
		 */
		function field_display_check_optin_optout() {
			$options = get_option( $this->general_settings_key );
			echo '<input type="checkbox" name="' . $this->general_settings_key . '[opt_in]" value="true" ' . checked( 'true', $options['opt_in'], false ) . '/> Check against an opt-in list <br />';
			echo '<input type="checkbox" name="' . $this->general_settings_key . '[opt_out]" value="true" ' . checked( 'true', $options['opt_out'], false ) . '/> Check against an opt-out list <br />';
		}
		
		/*
		 * General Option field callback, renders a checkbox input.
		 */
		function field_display_name_redactor_quicktag_button() {
			$options = get_option( $this->general_settings_key );
			echo '<input type="checkbox" name="' . $this->general_settings_key . '[add_quicktag_button]" value="true" ' . checked( 'true', $options['add_quicktag_button'], false ) . '/> <br />';
		}
		
		/*
		 * General Option field callback, renders a checkbox input.
		 */
		function field_display_redact_from_posts() {
			$options = get_option( $this->general_settings_key );
			echo '<input type="checkbox" name="' . $this->general_settings_key . '[filter_posts_and_pages]" value="true" ' . checked( 'true', $options['filter_posts_and_pages'], false ) . '/> <br />';
		}
		
		/*
		 * General Option field callback, renders a checkbox input.
		 */
		function field_display_redact_from_comments() {
			$options = get_option( $this->general_settings_key );
			echo '<input type="checkbox" name="' . $this->general_settings_key . '[filter_comments]" value="true" ' . checked( 'true', $options['filter_comments'], false ) . '/> <br />';
		}
		
		/*
		 * General Option field callback, renders a checkbox input.
		 */
		function field_display_redact_from_preview() {
			$options = get_option( $this->general_settings_key );
			echo '<input type="checkbox" name="' . $this->general_settings_key . '[preview_redact]" value="true" ' . checked( 'true', $options['preview_redact'], false ) . '/> <br />';
		}
		
		/*
		 * General Option field callback, renders a checkbox input.
		 */
		function field_display_cleanup_on_deactivate() {
			$options = get_option( $this->general_settings_key );
			echo '<input type="checkbox" name="' . $this->general_settings_key . '[cleanup_on_deactivate]" value="true" ' . checked( 'true', $options['cleanup_on_deactivate'], false ) . '/> <br />';
		}
		
		/*
		 * General Option field callback, renders a radio input.
		 */
		function field_display_records_per_page_opt_table() {
			$options = get_option( $this->general_settings_key );
			echo '<input type="radio" name="' . $this->general_settings_key . '[records_per_page_opt_table]" value="10" ' . checked( '10', $options['records_per_page_opt_table'], false ) . '/> 10 records per page <br />';
			echo '<input type="radio" name="' . $this->general_settings_key . '[records_per_page_opt_table]" value="25" ' . checked( '25', $options['records_per_page_opt_table'], false ) . '/> 25 records per page <br />';
			echo '<input type="radio" name="' . $this->general_settings_key . '[records_per_page_opt_table]" value="50" ' . checked( '50', $options['records_per_page_opt_table'], false ) . '/> 50 records per page <br />';
		}
		
		/*
		 * General Option field callback, renders a radio input.
		 */
		function field_display_records_per_page_bot_table() {
			$options = get_option( $this->general_settings_key );
			echo '<input type="radio" name="' . $this->general_settings_key . '[records_per_page_bot_table]" value="10" ' . checked( '10', $options['records_per_page_bot_table'], false ) . '/> 10 records per page <br />';
			echo '<input type="radio" name="' . $this->general_settings_key . '[records_per_page_bot_table]" value="25" ' . checked( '25', $options['records_per_page_bot_table'], false ) . '/> 25 records per page <br />';
			echo '<input type="radio" name="' . $this->general_settings_key . '[records_per_page_bot_table]" value="50" ' . checked( '50', $options['records_per_page_bot_table'], false ) . '/> 50 records per page <br />';
		}
		
		
		/*
		 * General Option field callback, renders a text input, 
		 * along with a dropdown menu.
		 */
		function field_display_opt_in_out() {
			?>
			<input type="text" name="name_to_opt" size="30" />
			<select name="opt-dropdown">
			<option value="opt-in">Opt-in</option>
			<option value="opt-out">Opt-out</option>
			</select>
			<?php
		}
		
		
		/*
		 * General Option field callback, renders a text input.
		 */
		function field_display_bots() {
			?>
			<input type="text" name="bot_name" size="30" />
			<?php
		}
		
		
		/*
		 * Called when someone attempts to add a name to the opt-in/opt-out table. 
		 * Returns true if name is added successfully, or false if name already exists.
		 */
		function opt_in_out_name_to_db( $name, $opt ) {
			global $wpdb;
			$added = '';
			$table_name = $wpdb->prefix . $this->opt_in_out_table_name; 
			$result = $wpdb->query( $wpdb->prepare("SELECT name FROM $table_name WHERE name = %s", $name ) ); // query returns an integer corresponding to the number of rows affected/selected
			
			if ( $result == 0 ) { // row not found
				$query = $wpdb->prepare( "INSERT INTO $table_name ( name, opt_in_out ) VALUES ( %s, %s )", array( $name, $opt ) );
				$wpdb->query($query);
				$added = true;
			}
			else {
				$added = false;
			}
			return $added;
		}
		
		
		/*
		 * Called when someone attempts to add a name to the bot name table. 
		 * Returns true if name is added successfully, or false if name already exists.
		 */
		function bot_name_to_db( $bot_name ) {
			global $wpdb;
			$added = '';
			$table_name = $wpdb->prefix . $this->bot_table_name; 
			$result = $wpdb->query( $wpdb->prepare("SELECT bot_name FROM $table_name WHERE bot_name = %s", $bot_name ) ); // query returns an integer corresponding to the number of rows affected/selected
			
			if ( $result == 0 ) { // row not found
				$query = $wpdb->prepare( "INSERT INTO $table_name ( bot_name ) VALUES ( %s )", array( $bot_name ) );
				$wpdb->query($query);
				$added = true;
			}
			else {
				$added = false;
			}
			return $added;
		}
		
		
		/*
		 * Called during admin_menu, adds an options
		 * page under Settings called My Settings, rendered
		 * using the plugin_options_page method.
		 */
		function add_admin_menus() {
			add_management_page( 
				'Name Redactor', 
				'Name Redactor', 
				'manage_options', 
				$this->parent->plugin_slug, 
				array( &$this, 'plugin_options_page' ) 
			);
		}
		
		
		/*
		 * Plugin Options page rendering goes here, checks
		 * for active tab and replaces key with the related
		 * settings key. Uses the plugin_options_tabs method
		 * to render the tabs.
		 */
		function plugin_options_page() {
			$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;
			?>
			<div class="wrap">
				<h2>Name Redactor Settings</h2>
				<?php $this->plugin_options_tabs();
				
				if( $current_tab == 'name_redactor_opt_in_opt_out' ) {
					$hidden_field_name = 'nameRedactor_submit_hidden';
					if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
						if( isset( $_POST[ 'name_to_opt' ] ) && !empty( $_POST[ 'name_to_opt' ] ) ) {
							$name = esc_attr( $_POST[ 'name_to_opt' ] );
							$opt = esc_attr( $_POST[ 'opt-dropdown' ] );
							$added = $this->opt_in_out_name_to_db( $name, $opt );

							// Display a settings saved message on the screen
							if ( $added == true ) {
								?>
								<div class="updated"><p><strong><?php echo "The name " . $name . " was added to the " . $opt . " list.";?></strong></p></div>
								<?php
							}
							else {
								?>
								<div class="updated"><p><strong><?php echo "The name " . $name . " has already been added. 
									Remember that you can change its opt-status in the table at the bottom of the page.";?></strong></p></div>
								<?php
							}
						}
						if( empty( $_POST[ 'name_to_opt' ] ) ){
							?>
							<div class="updated"><p><strong><?php echo "No new name was added.";?></strong></p></div>
							<?php
						}
					}
					?>
					<form name="form1" method="post" action="">
					<?php 
					wp_nonce_field( 'update-options' ); 
					settings_fields( $current_tab ); 
					do_settings_sections( $current_tab ); 
					
					//Our class extends the WP_List_Table class, so we need to make sure that it's there
					if(!class_exists( 'WP_List_Table' )) {
						require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
					}
					include_once( plugin_dir_path(__FILE__) . 'name-redactor-name-list-table.php' );
					//Create an instance of our package class...
					$this->wp_name_list_table = new Name_Redactor_Name_List_Table( $this );
					//Fetch, prepare, sort, and filter our data...
					$this->wp_name_list_table->prepare_items();
					?>
					<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
					<p class="submit">
					<input type="submit" name="Save" class="button-primary" value="<?php esc_attr_e('Add name') ?>" />
					</p>
					</form>
					<hr />
					<!-- Forms are NOT created automatically, so we need to wrap the table in one to use features like bulk actions -->
					<form id="names-filter" method="post">
						<!-- For plugins, we also need to ensure that the form posts back to our current page -->
						<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
						<!-- Now we can render the completed list table -->
						<?php $this->wp_name_list_table->display() ?>
					</form>
					<?php 
				}
				elseif( $current_tab == 'name_redactor_bots' ) {
					$hidden_field_name = 'nameRedactor_submit_hidden';
					if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
						if( isset( $_POST[ 'bot_name' ] ) && !empty( $_POST[ 'bot_name' ] ) ) {
							$bot_name = esc_attr( $_POST[ 'bot_name' ] );
							$added = $this->bot_name_to_db( $bot_name );

							// Display a settings saved message on the screen
							if ( $added == true ) {
								?>
								<div class="updated"><p><strong><?php echo "The bot name " . $bot_name . " was added to the list.";?></strong></p></div>
								<?php
							}
							else {
								?>
								<div class="updated"><p><strong><?php echo "The bot name " . $bot_name . " has already been added.";?></strong></p></div>
								<?php
							}
						}
						if( empty( $_POST[ 'bot_name' ] ) ){
							?>
							<div class="updated"><p><strong><?php echo "No new bot name was added.";?></strong></p></div>
							<?php
						}
					}
					?>
					<form name="form2" method="post" action="">
					<?php 
					wp_nonce_field( 'update-options' ); 
					settings_fields( $current_tab ); 
					do_settings_sections( $current_tab ); 
					
					//Our class extends the WP_List_Table class, so we need to make sure that it's there
					if(!class_exists( 'WP_List_Table' )) {
						require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
					}
					include_once( plugin_dir_path(__FILE__) . 'name-redactor-bot-list-table.php' );
					//Create an instance of our package class...
					$this->wp_bot_list_table = new Name_Redactor_Bot_List_Table( $this );
					//Fetch, prepare, sort, and filter our data...
					$this->wp_bot_list_table->prepare_items();
					?>
					<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
					<p class="submit">
					<input type="submit" name="Save" class="button-primary" value="<?php esc_attr_e('Add bot name') ?>" />
					</p>
					</form>
					<hr />
					<!-- Forms are NOT created automatically, so we need to wrap the table in one to use features like bulk actions -->
					<form id="names-filter" method="post">
						<!-- For plugins, we also need to ensure that the form posts back to our current page -->
						<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
						<!-- Now we can render the completed list table -->
						<?php $this->wp_bot_list_table->display() ?>
					</form>
					<?php 
				}
				else {
					?>
					<form method="post" action="options.php">
					<?php 
					wp_nonce_field( 'update-options' ); 
					settings_fields( $current_tab ); 
					do_settings_sections( $current_tab ); 
					submit_button();
					?>
					</form>
					<?php 
				}
				?>
					
				
			</div>
			<?php
		}
		
		
		/*
		 * Renders our tabs in the plugin options page,
		 * walks through the object's tabs array and prints
		 * them one by one. Provides the heading for the
		 * plugin_options_page method.
		 */
		function plugin_options_tabs() {
			$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;
			screen_icon();
			echo '<h2 class="nav-tab-wrapper">';
			foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
				$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
				echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->parent->plugin_slug . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';	
			}
			echo '</h2>';
		}
		
	} // class Name_Redactor_Settings
} // end !class_exists