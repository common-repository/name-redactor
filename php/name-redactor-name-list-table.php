<?php 
/**
 * This file creates and displays the table for listing opt-in/opt-out names.
 * Based on the Custom List Table Example plugin created by Matt Van Andel (http://www.mattvanandel.com).
 */

if ( !class_exists( 'Name_Redactor_Name_List_Table' ) ) {
	class Name_Redactor_Name_List_Table extends WP_List_Table {
		
		/**
		 * @var object The creator of this plugin
		 */
		var $parent = null;
		
		/**
		 * @var string The name of the general settings key
		 */
		var $general_settings_key = '';
		
		/**
		 * @var string The name of the opt-in/opt-out settings key
		 */
		var $opt_in_out_settings_key = '';
		
		
		/** ************************************************************************
		 * REQUIRED. Set up a constructor that references the parent constructor. We 
		 * use the parent reference to set some default configs.
		 ***************************************************************************/
		function __construct( $owner ){
			$this->parent = $owner;
			$this->general_settings_key = $this->parent->general_settings_key;
			$this->opt_in_out_settings_key = $this->parent->opt_in_out_settings_key;
			global $status, $page;
					
			//Set parent defaults
			parent::__construct( array(
				'singular'  => 'name',     //singular name of the listed records
				'plural'    => 'names',    //plural name of the listed records
				'ajax'      => false        //does this table support ajax?
			) );
		}
			
			
		/** ************************************************************************
		 * Recommended. This method is called when the parent class can't find a method
		 * specifically build for a given column. Generally, it's recommended to include
		 * one method for each column you want to render, keeping your package class
		 * neat and organized. For example, if the class needs to process a column
		 * named 'title', it would first see if a method named $this->column_title() 
		 * exists - if it does, that method will be used. If it doesn't, this one will
		 * be used. Generally, you should try to use custom column methods as much as 
		 * possible. 
		 * 
		 * Since we have defined a column_title() method later on, this method doesn't
		 * need to concern itself with any column with a name of 'title'. Instead, it
		 * needs to handle everything else.
		 * 
		 * For more detailed insight into how columns are handled, take a look at 
		 * WP_List_Table::single_row_columns()
		 * 
		 * @param array $item A singular item (one full row's worth of data)
		 * @param array $column_name The name/slug of the column to be processed
		 * @return string Text or HTML to be placed inside the column <td>
		 **************************************************************************/
		function column_default($item, $column_name){
			switch($column_name){
				case 'name':
				case 'opt_in_out':
					return $item[$column_name];
				default:
					return print_r($item,true); //Show the whole array for troubleshooting purposes
			}
		}
		
		
		/** ************************************************************************
		 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
		 * is given special treatment when columns are processed. It ALWAYS needs to
		 * have it's own method.
		 * 
		 * @see WP_List_Table::::single_row_columns()
		 * @param array $item A singular item (one full row's worth of data)
		 * @return string Text to be placed inside the column <td>
		 **************************************************************************/
		function column_cb($item){
			return sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				/*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("name")
				/*$2%s*/ $item['id']                //The value of the checkbox should be the record's id
			);
		}
		
		
		/** ************************************************************************
		 * REQUIRED! This method dictates the table's columns and titles. This should
		 * return an array where the key is the column slug (and class) and the value 
		 * is the column's title text. If you need a checkbox for bulk actions, refer
		 * to the $columns array below.
		 * 
		 * The 'cb' column is treated differently than the rest. If including a checkbox
		 * column in your table you must create a column_cb() method. If you don't need
		 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
		 * 
		 * @see WP_List_Table::::single_row_columns()
		 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
		 **************************************************************************/
		function get_columns(){
			$columns = array(
				'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
				'name'     => 'Name',
				'opt_in_out'    => 'Opt-in/opt-out',
			);
			return $columns;
		}
		
		/** ************************************************************************
		 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
		 * you will need to register it here. This should return an array where the 
		 * key is the column that needs to be sortable, and the value is db column to 
		 * sort by. Often, the key and value will be the same, but this is not always
		 * the case (as the value is a column name from the database, not the list table).
		 * 
		 * This method merely defines which columns should be sortable and makes them
		 * clickable - it does not handle the actual sorting. You still need to detect
		 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
		 * your data accordingly (usually by modifying your query).
		 * 
		 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
		 **************************************************************************/
		function get_sortable_columns() {
			$sortable_columns = array(
				'name'     => array('name',false),     //true means it's already sorted
				'opt_in_out'    => array('opt_in_out',false),
			);
			return $sortable_columns;
		}
		
		
		/** ************************************************************************
		 * Optional. If you need to include bulk actions in your list table, this is
		 * the place to define them. Bulk actions are an associative array in the format
		 * 'slug'=>'Visible Title'
		 * 
		 * If this method returns an empty value, no bulk action will be rendered. If
		 * you specify any bulk actions, the bulk actions box will be rendered with
		 * the table automatically on display().
		 * 
		 * Also note that list tables are not automatically wrapped in <form> elements,
		 * so you will need to create those manually in order for bulk actions to function.
		 * 
		 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
		 **************************************************************************/
		function get_bulk_actions() {
			$actions = array(
				'delete'    => 'Delete',
				'change'    => 'Change Opt-status'
			);
			return $actions;
		}
		
		
		/** ************************************************************************
		 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
		 * For this example package, we will handle it in the class to keep things
		 * clean and organized.
		 * 
		 * @see $this->prepare_items()
		 **************************************************************************/
		function process_bulk_action() {
			if ( 'delete' === $this->current_action() ) {
				if( isset( $_POST[ 'name' ] ) && !empty( $_POST[ 'name' ] ) ) {
					global $wpdb;
					$table_name = $wpdb->prefix . $this->parent->opt_in_out_table_name;
					foreach($_POST['name'] as $name_id) {
						//$name_id will be a string containing the ID of the name
						//i.e. $name_id = "123";
						$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id=%s", $name_id ) );
					}
					?>
					<div class="updated"><p><strong><?php echo "Name(s) deleted.";?></strong></p></div>
					<?php
				}
				if( empty( $_POST[ 'name' ] ) ){
					?>
					<div class="updated"><p><strong><?php echo "No names were selected.";?></strong></p></div>
					<?php
				}
			}
			if ( 'change' === $this->current_action() ) {
				if( isset( $_POST[ 'name' ] ) && !empty( $_POST[ 'name' ] ) ) {
					global $wpdb;
					$table_name = $wpdb->prefix . $this->parent->opt_in_out_table_name;
					foreach($_POST['name'] as $name_id) {
						//$name_id will be a string containing the ID of the name
						//i.e. $name_id = "123";
						$opt_value = $wpdb->get_var( $wpdb->prepare( "SELECT opt_in_out FROM $table_name WHERE id=%s", $name_id ) );
						if( $opt_value === 'opt-in' ) {
							$wpdb->update( $table_name, array( 'opt_in_out' => 'opt-out' ), array( 'id' => $name_id ), '%s' );
						}
						else {
							$wpdb->update( $table_name, array( 'opt_in_out' => 'opt-in' ), array( 'id' => $name_id ), '%s' );
						}
					}
					?>
					<div class="updated"><p><strong><?php echo "Opt-status changed for the selected name(s).";?></strong></p></div>
					<?php
				}
				if( empty( $_POST[ 'name' ] ) ){
					?>
					<div class="updated"><p><strong><?php echo "No names were selected.";?></strong></p></div>
					<?php
				}
			}
			

		}
		
		
		/** ************************************************************************
		 * REQUIRED! This is where you prepare your data for display. This method will
		 * usually be used to query the database, sort and filter the data, and generally
		 * get it ready to be displayed. At a minimum, we should set $this->items and
		 * $this->set_pagination_args(), although the following properties and methods
		 * are frequently interacted with here...
		 * 
		 * @global WPDB $wpdb
		 * @uses $this->_column_headers
		 * @uses $this->items
		 * @uses $this->get_columns()
		 * @uses $this->get_sortable_columns()
		 * @uses $this->get_pagenum()
		 * @uses $this->set_pagination_args()
		 **************************************************************************/
		function prepare_items() {
			global $wpdb; //This is used only if making any database queries

			/**
			 * First, lets decide how many records per page to show
			 */
			$options = get_option( $this->general_settings_key );
			if ( $options['records_per_page_opt_table'] == '10' ) {
				$per_page = (int) 10;
			}
			elseif ( $options['records_per_page_opt_table'] == '25' ) {
				$per_page = (int) 25;
			}
			elseif ( $options['records_per_page_opt_table'] == '50' ) {
				$per_page = (int) 50;
			}
			//$per_page = 5;
			
			
			/**
			 * REQUIRED. Now we need to define our column headers. This includes a complete
			 * array of columns to be displayed (slugs & titles), a list of columns
			 * to keep hidden, and a list of columns that are sortable. Each of these
			 * can be defined in another method (as we've done here) before being
			 * used to build the value for our _column_headers property.
			 */
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
			
			
			/**
			 * REQUIRED. Finally, we build an array to be used by the class for column 
			 * headers. The $this->_column_headers property takes an array which contains
			 * 3 other arrays. One for all columns, one for hidden columns, and one
			 * for sortable columns.
			 */
			$this->_column_headers = array($columns, $hidden, $sortable);
			
			
			/**
			 * Optional. You can handle your bulk actions however you see fit. In this
			 * case, we'll handle them within our package just to keep things clean.
			 */
			$this->process_bulk_action();
			
			
			/**
			 * Here we fetch and order the table data.
			 */
			$table_name = $wpdb->prefix . $this->parent->opt_in_out_table_name; 
			$data = '';
			
			// Parameters that are going to be used to order the result:
	        $orderby = !empty( $_GET['orderby'] ) ? mysql_real_escape_string( $_GET['orderby'] ) : 'name';
	        $order = !empty( $_GET['order'] ) ? mysql_real_escape_string( $_GET['order'] ) : 'asc';
	        
			if ( !empty( $orderby ) && !empty( $order ) ) { 
				$data = $wpdb->get_results( "SELECT id, name, opt_in_out FROM $table_name ORDER BY $orderby $order", ARRAY_A );
			}
			else {
				$data = $wpdb->get_results( "SELECT id, name, opt_in_out FROM $table_name", ARRAY_A );
			}
			
			
			/**
			 * This checks for sorting input and sorts the data in our array accordingly.
			 */
			 
			function usort_reorder($a,$b){
				$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'name'; //If no sort, default to title
				$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
				$result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
				return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
			}
			
			
			/**
			 * REQUIRED for pagination. Let's figure out what page the user is currently 
			 * looking at. We'll need this later, so you should always include it in 
			 * your own package classes.
			 */
			$current_page = $this->get_pagenum();
			
			/**
			 * REQUIRED for pagination. Let's check how many items are in our data array. 
			 * In real-world use, this would be the total number of items in your database, 
			 * without filtering. We'll need this later, so you should always include it 
			 * in your own package classes.
			 */
			$total_items = count( $data );
			
			
			/**
			 * The WP_List_Table class does not handle pagination for us, so we need
			 * to ensure that the data is trimmed to only the current page. We can use
			 * array_slice() to 
			 */
			$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
			
			
			/**
			 * REQUIRED. Now we can add our *sorted* data to the items property, where 
			 * it can be used by the rest of the class.
			 */
			$this->items = $data;
			
			
			/**
			 * REQUIRED. We also have to register our pagination options & calculations.
			 */
			$this->set_pagination_args( array(
				'total_items' => $total_items,                  //We have to calculate the total number of items
				'per_page'    => $per_page,                     //We have to determine how many items to show on a page
				'total_pages' => ceil($total_items/$per_page)   //We have to calculate the total number of pages
			) );
		}
	
	} // end class Name_Redactor_Name_List_Table
} // end !class_exists