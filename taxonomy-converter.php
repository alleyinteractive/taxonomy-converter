<?php

/*
	Plugin Name: Taxonomy Converter
	Plugin URI: http://www.alleyinteractive.com/
	Description: Convert one taxonomy's set of terms to another taxonomy one by one
	Version: 0.1
	Author: Matthew Boynes
	Author URI: http://www.alleyinteractive.com
*/
/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


if ( ! class_exists( 'Taxonomy_Converter' ) ) {

	class Taxonomy_Converter {
		var $options;
		var $option_key;
		var $valid_options;

		/**
		 * Constructor. Sets up the main class variables, and sets up WP filters
		 *
		 * @return void
		 * @author Matthew Boynes
		 */
		function __construct() {
			$this->option_key = apply_filters( 'taxonomy_converter_options_key', 'taxcon_options' );
			$this->valid_options = apply_filters( 'taxonomy_converter_valid_options', array( 'old_tax', 'old_tax_nickname', 'new_tax', 'new_tax_nickname' ) );

			add_action( 'admin_menu', array( &$this, 'menu' ) );
			add_action( 'wp_ajax_reload_terms', array( &$this, 'reload_terms' ) );
			if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'taxonomy_converter' ) {
				add_action( 'admin_init', array( &$this, 'save_settings' ) );
				add_action( 'admin_init', array( &$this, 'load_js' ) );
			}

			register_activation_hook( __FILE__, array( &$this, 'activation_hook' ) );
			register_deactivation_hook( __FILE__, array( &$this, 'deactivation_hook' ) );
		}

		/**
		 * initialize the plugin options. This isn't called in the constructor to hopefully save a DB query if it isn't needed
		 *
		 * @return void
		 * @author Matthew Boynes
		 */
		function init() {
			$this->options();
		}


		function menu() {
			// Add new admin menu and save returned page hook
			$hook_suffix = add_management_page( __( 'Taxonomy Converter', 'taxonomy-converter' ), __( 'Taxonomy Converter', 'taxonomy-converter' ), 'manage_options', 'taxonomy_converter', array( &$this, 'admin_page' ) );
		}

		/**
		 * Initialize options
		 *
		 * @return array Associative array of plugin settings
		 * @author Matthew Boynes
		 */
		function options() {
			if ( !$this->options ) {
				$this->options = get_option( $this->option_key );
				if ( !is_array( $this->options ) )
					$this->options = array();

				$this->options = array_merge( array(
					# default options can go here
				), $this->options);
			}
			return $this->options;
		}

		/**
		 * get one of the specific settings from this plugin. This is essentially an extension of WP's get_option function
		 *
		 * @param string $key The option to get
		 * @param mixed $default If $key isn't found, return $default
		 * @return mixed
		 * @author Matthew Boynes
		 */
		function get_option( $key, $default=false ) {
			$options = $this->options();
			if ( isset( $options[$key] ) )
				return $options[$key];
			return $default;
		}

		/**
		 * Update and store the plugin settings
		 *
		 * @param array $new_options The new plugin settings
		 * @return void
		 * @author Matthew Boynes
		 */
		function update_options( $new_options ) {
			$this->init();
			$this->options = array();
			foreach ( $new_options as $key => $val ) {
				if ( in_array( $key, $this->valid_options ) )
					$this->options[$key] = $val;
			}
			update_option( $this->option_key, $this->options );
		}


		function admin_page() {
			if ( !current_user_can('manage_options') )
				wp_die( __('You do not have sufficient permissions to access this page.') );

			$this->init();
			$taxes = get_taxonomies();
			?>
			<style type="text/css" media="screen">
				#taxcon_questions {
					font: 300 48px/48px "Helvetica Neue", Arial, Helvetica, sans-serif;
					text-align: center;
				}
				.taxonomy-choices {
					overflow: hidden;
					border-top: 1px solid #dfdfdf;
					border-bottom: 1px solid #dfdfdf;
					padding: 20px 0;
				}
				.tax-choice {
					float: left;
					width: 50%;
				}
				.tax-answer {
					display: block;
					text-align: center;
					padding: 50px;
					font: 300 36px/36px "Helvetica Neue", Arial, Helvetica, sans-serif;
					text-decoration: none;
					margin: 20px;
					background: #efefef;
					border: 1px solid #dfdfdf;
					box-shadow: 0 0 10px rgba(0,0,0,0.3);
					border-radius: 5px;
				}
				.tax-answer:hover {
					background-color: #f7f7f7;
				}
				.question-term {
					margin: 20px 0;
				}
			</style>
			<script type="text/javascript" charset="utf-8">
				var tctpl;
				jQuery(function($){
					tctpl = _.template($('#taxonomy_tpl').html());
					$('#taxcon_questions').on('click','.tax-answer',function(e){
						e.preventDefault();
						$.post(ajaxurl, {action:'reload_terms',last_term: $(this).data('term'), answer: $(this).data('answer')}, function(response){
							if (response.msg)
								$('#taxcon_questions').html('All done!');
							else
								$('#taxcon_questions').html(tctpl(response));
						}, 'json');
					});
					<?php if ( isset( $this->options, $this->options['old_tax'], $this->options['new_tax'] ) ) : ?>
					$.hotkeys.add('a', function(){jQuery('#taxcon_questions a.tax-answer-old').click();return false;});
					$.hotkeys.add('l', function(){jQuery('#taxcon_questions a.tax-answer-new').click();return false;});
					term = <?php echo json_encode( $this->get_random_term() ) ?>;
					if ( term.msg )
						$('#taxcon_questions').html('<?php _e( 'All done!', 'taxonomy-converter' ); ?>');
					else
						$('#taxcon_questions').html(tctpl(term));
					$('#taxcon_settings_form').hide().before('<p><a href="#" class="button-secondary" onclick="jQuery(\'#taxcon_settings_form\').slideDown();jQuery(this).remove();return false;">Display Settings</a></p>');
					<?php endif; ?>
				});
			</script>
			<?php if ( isset( $this->options, $this->options['old_tax'], $this->options['new_tax'] ) ) : ?>
			<script type="text/template" id="taxonomy_tpl">
				<div class="taxonomy-choices">
					<div class="question-term">
						<%= name %>
					</div>
					<div class="tax-choice left-choice">
						<a href="#" class="tax-answer tax-answer-old" data-answer="old" data-term="<%= term_id %>"><?php echo isset( $this->options['old_tax_nickname'] ) ? $this->options['old_tax_nickname'] : $this->options['old_tax'] ?></a>
					</div>
					<div class="tax-choice right-choice">
						<a href="#" class="tax-answer tax-answer-new" data-answer="new" data-term="<%= term_id %>"><?php echo isset( $this->options['new_tax_nickname'] ) ? $this->options['new_tax_nickname'] : $this->options['new_tax'] ?></a>
					</div>
				</div>
			</script>
			<?php endif; ?>

			<div class="wrap">
				<h2><?php _e( 'Taxonomy Converter', 'taxonomy-converter' ); ?></h2>

				<div id="taxcon_questions"></div>

				<?php if (isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
					<div class="updated fade"><p><?php _e( 'Settings Updated', 'taxonomy-converter' ); ?></p></div>
				<?php endif ?>

				<form method="post" id="taxcon_settings_form">
					<h3><?php _e( 'Settings', 'taxonomy-converter' ); ?></h3>
					<?php wp_nonce_field( 'taxonomy_converter_settings', 'taxcon_nonce' ); ?>

					<p>
						<label for="old_tax"><?php _e( 'Old Taxonomy', 'taxonomy-converter' ); ?></label><br />
						<select id="old_tax" name="taxcon[old_tax]">
						<?php foreach ( $taxes as $tax ) : ?>
							<option<?php if ( $this->options['old_tax'] == $tax ) echo ' selected="selected"' ?>><?php echo esc_html( $tax ); ?></option>
						<?php endforeach; ?>
						</select>
						<input type="text" name="taxcon[old_tax_nickname]" value="<?php echo $this->get_option( 'old_tax_nickname' ) ?>" placeholder="<?php _e( 'Nickname', 'taxonomy-converter' ); ?>" />
					</p>

					<p>
						<label for="new_tax"><?php _e('New Taxonomy'); ?></label><br />
						<select id="new_tax" name="taxcon[new_tax]">
						<?php foreach ( $taxes as $tax ) : ?>
							<option<?php if ( $this->options['new_tax'] == $tax ) echo ' selected="selected"' ?>><?php echo esc_html( $tax ); ?></option>
						<?php endforeach; ?>
						</select>
						<input type="text" name="taxcon[new_tax_nickname]" value="<?php echo $this->get_option( 'new_tax_nickname' ) ?>" placeholder="<?php _e( 'Nickname', 'taxonomy-converter' ); ?>" />
					</p>

					<?php submit_button() ?>
				</form>
			</div>
			<?php
		}

		/**
		 * POST receiver method; saves settings from the donation_form() page/method
		 *
		 * @return void
		 * @author Matthew Boynes
		 */
		function save_settings() {
			global $parent_file;
			if ( isset( $_POST['taxcon'] ) ) {
				check_admin_referer( 'taxonomy_converter_settings', 'taxcon_nonce' );
				$this->update_options( $_POST['taxcon'] );
				wp_redirect( add_query_arg( 'msg', 'saved' ) );
				exit;
			}
		}

		function activation_hook() {
			# Add new column in terms table
			global $wpdb;
			$wpdb->query( "ALTER TABLE `{$wpdb->term_taxonomy}` ADD `taxcon_viewed` INT(1)  UNSIGNED  NULL" );
		}

		function deactivation_hook() {
			# Remove column in terms table
			global $wpdb;
			$wpdb->query( "ALTER TABLE `{$wpdb->term_taxonomy}` DROP `taxcon_viewed`" );
			delete_option( 'taxcon_options' );
		}

		function reload_terms() {
			global $wpdb;
			if ( isset( $_POST['last_term'], $_POST['answer'] ) ) {
				# Update term table; set the taxonomy if changed, set the "has been viewed" regardless
				$updates = array( 'taxcon_viewed' => 1 );
				if ( 'new' == $_POST['answer'] ) {
					$updates['taxonomy'] = $this->get_option( 'new_tax' );
				}
				$wpdb->update( $wpdb->term_taxonomy, $updates, array( 'term_id' => (int) $_POST['last_term'] ) );
			}
			echo json_encode( $this->get_random_term() );
			exit;
		}

		function get_random_term() {
			# get a new random term
			global $wpdb;
			$term = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT t.term_id,t.name FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE tt.taxcon_viewed IS NULL AND tt.taxonomy = %s ORDER BY RAND() LIMIT 1",
					$this->get_option( 'old_tax' )
				)
			);
			if ( !$term )
				return array( 'msg' => 'empty' );
			return $term;
		}

		function load_js() {
			wp_enqueue_script('underscore');
			wp_enqueue_script('jquery-hotkeys');
		}

	}

	$Taxonomy_Converter = new Taxonomy_Converter;
}
