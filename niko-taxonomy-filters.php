<?php

/**
 * Plugin Name: Niko Taxonomy Filters
 * Version: 0.2
 * Plugin URI: -
 * Description: Add taxonomy filters to post listings in admin
 * Author: @trevald
 * Author URI: https://trevald.com/
 * Text Domain: niko-taxonomy-filters
 */

require_once('includes/NikoTaxonomyFilters.php');
require_once('includes/NikoTaxonomyFiltersSettings.php');

/**
 * Initialize settings page
 */
if( is_admin() ) {
	$niko_taxonomy_filters__settings_page = new Niko_Taxonomy_Filters_Settings();
}

/**
 * Creates SELECT filters for all taxonomies associated 
 * with the current post type beign listed
 * @return void
 */
function niko_taxonomy_filters_add_taxonomy_filters() {
	$filter = new Niko_Taxonomy_Filters();
	$filter->render_filters();
}
add_action( 'restrict_manage_posts', 'niko_taxonomy_filters_add_taxonomy_filters'  );