<?php
/**
 * Add taxonomy filters to admin post listings
 *
 * @package NikoTaxonomyFilters
 * @since 0.2
 */
class Niko_Taxonomy_Filters {

	/**
	 * Version of plugin
	 * @var float
	 */
	public $version = 0.2;

	/**
	 * Current post type
	 * @var string
	 */
	private $current_post_type = '';

	/**
	 * Available taxonomies
	 * @var array
	 */
	private $taxonomies = array();

	/**
	 * Taxonomies to exclude
	 * @var array
	 */
	private $exclude = array('*' => array('category'));

	private $options = null;

	/**
	 * Set initial values
	 * @param array $args Optional arguments
	 */
	public function __construct( $args = array() )
	{
		// Set current post type
		$current_screen = get_current_screen();
		$this->current_post_type = $current_screen->post_type;

		// Set taxonomies
		$this->taxonomies = get_object_taxonomies( $this->current_post_type );

		// Get options
		$this->options = get_option( 'niko_tf_options' );
		if( is_array($this->options['exclude']) ) {
			$this->exclude = array_merge($this->exclude, $this->options['exclude']);
		}
	}

	/**
	 * Render filters
	 * @return void
	 */
	public function render_filters()
	{
		if (empty($this->taxonomies)) { return; }

		foreach( $this->taxonomies as $tax_slug ) {

			// Taxonomies to skip
			if( $this->is_excluded($tax_slug) ) { continue; }

			// Get taxonomy name and terms
			$tax_obj = get_taxonomy($tax_slug);
			$terms = get_terms($tax_slug);

			// Only render if we have terms
			if( count($terms) > 0 ) {
				echo '<select name="' .$tax_slug. '" id="' .$tax_slug. '" class="postform">';
				echo '<option value="">' .__('All').' '.$tax_obj->labels->name. '</option>';
				echo $this->get_the_terms( $terms, $tax_slug );
				echo '</select>';
			}
		}
	}

	private function is_excluded($tax_slug)
	{
		return (
				is_array($this->exclude[$this->current_post_type]) && 
				in_array($tax_slug, $this->exclude[$this->current_post_type])
				) 
				|| 
				(
					in_array($tax_slug, $this->exclude['*'])
				);
	}

	/**
	 * Build HTML options for the selcetable terms
	 * @param  array  $terms
	 * @param  string $tax_slug
	 * @return string
	 */
	private function get_the_terms( $terms, $tax_slug )
	{
		$result = '';
		foreach ($terms as $term) { 
			$result.= '<option value="' .$term->slug. '"';
			$result.= isset($_GET[$tax_slug]) && $_GET[$tax_slug] == $term->slug ? ' selected="selected">' : '>';
			$result.= $term->name . ' (' . $term->count .')';
			$result.= '</option>';
		}

		return $result;
	}
}