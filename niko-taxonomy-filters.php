<?php

/**
 * Plugin Name: Niko Taxonomy Filters
 * Version: 0.1
 * Plugin URI: -
 * Description: Add taxonomy filters to post listings in admin
 * Author: @trevald
 * Author URI: https://trevald.com/
 * Text Domain: niko-taxonomy-filters
 */

/**
 * Creates SELECT filters for all taxonomies associated with the current post type beign listed
 * @return void
 */
function niko_taxonomy_filters_add_taxonomy_filters() {
	
	// Get current post type
	$current_screen = get_current_screen();
	$current_post_type = $current_screen->post_type;

	$taxonomies = get_object_taxonomies( $current_post_type );
 
	if( !empty($taxonomies) ) {
 
		foreach( $taxonomies as $tax_slug ) {
			
			// Default categories are added by default
			if( $tax_slug === 'category' ) { continue; }

			// Get taxonomy name and terms
			$tax_obj = get_taxonomy($tax_slug);
			$tax_name = $tax_obj->labels->name;
			$terms = get_terms($tax_slug);

			// Only if we have terms
			if( count($terms) > 0 ) {
				echo '<select name="' .$tax_slug. '" id="' .$tax_slug. '" class="postform">';
				echo '<option value="">' .__('All').' '.$tax_name. '</option>';

				foreach ($terms as $term) { 
					echo '<option value="' .$term->slug. '"';
					echo isset($_GET[$tax_slug]) && $_GET[$tax_slug] == $term->slug ? ' selected="selected">' : '>';
					echo $term->name . ' (' . $term->count .')';
					echo '</option>';
				}

				echo '</select>';
			}
		}
	}
}
add_action( 'restrict_manage_posts', 'niko_taxonomy_filters_add_taxonomy_filters'  );
