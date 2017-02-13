<?php
/**
 * Create a settings page
 *
 * @package NikoTaxonomyFilters
 * @since 0.2
 */
class Niko_Taxonomy_Filters_Settings {
   /**
     * Holds the values to be used in the fields callbacks
     * @var array
     */
    private $options;

    /**
     * Available post types
     * @var array
     */
    private $post_types;

    /**
     * Key of database entry
     * @var string
     */
    private $db_key = 'niko_tf_options';

    /**
     * Options page ID
     * @var string
     */
    private $page_id = 'niko_tf-setting-admin';

    /**
     * Options group ID
     * @var string
     */
    private $group_id = 'niko_tf_option_group';

    /**
     * Start up
     */
    public function __construct()
    {   
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Get options
     * @return void
     */
    private function getOptions() {
        if( get_option($this->db_key) === false ) {
            add_option($this->db_key, array('excluded' => array()));
        }
        return get_option( $this->db_key );   
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Niko Taxonomy Filters Settings', 
            'NTF Settings', 
            'manage_options', 
            $this->page_id, 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        print '<div class="wrap">';
        print '<h1>Niko Taxonomy Filters Settings</h1>';
        print '<form method="post" action="options.php">';
        
        // This prints out all hidden setting fields
        settings_fields( $this->group_id );
        do_settings_sections( $this->page_id );
        submit_button();

        print '</form>';
        print '</div>';
    }

    /**
     * Get only the post types that we want to handle
     * @return array
     */
    private function get_post_types() 
    {
        $post_types = get_post_types();
        $exclude = array('post', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset');
        
        return array_diff($post_types, $exclude);
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {   
        
        // Set class property
        $this->post_types = $this->get_post_types();
        $this->options = $this->getOptions();

        register_setting(
            $this->group_id, // Option group
            $this->db_key, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        $this->add_settings_section();
    }

    /**
     * Add settings section for all post types
     */
    private function add_settings_section() {
        // Loop through all registered post types and register sections for each
        foreach( $this->post_types as $post_type ) {
            $section_id = 'niko_tf-section-' . $post_type;

            add_settings_section(
                $section_id, // ID
                ucfirst($post_type), // Title
                array( $this, 'print_section_info' ), // Callback
                $this->page_id // Page
            );  

            $this->add_settings_fields($post_type, $section_id);
        }      
    }

    /**
     * Get all taxonomies for post type and add a settings field for each
     * @param string $post_type  Post type
     * @param string $section_id Id of settings section
     */
    private function add_settings_fields($post_type, $section_id) {
        $taxonomies = get_object_taxonomies( $post_type );
        foreach( $taxonomies as $tax_slug ) {
            if( $tax_slug === 'category' ) { continue; } // Always exclude default taxonomy "category"
            $tax_obj = get_taxonomy($tax_slug);
            $field_id = 'niko_tf-setting|' . $post_type . '|' . $tax_slug . '';
            $checked = $this->is_excluded($post_type, $tax_slug);

            add_settings_field(
                $field_id,
                $tax_obj->labels->name,
                array( $this, 'taxonomy_callback' ), 
                $this->page_id, 
                $section_id,
                array(
                    'label_for' =>  $field_id, 
                    'checked' => $checked
                )
            );
        }       
    }

    /**
     * Check if a taxonomy is excluded for a post type
     * @param  string  $post_type
     * @param  string  $tax_slug
     * @return boolean
     */
    private function is_excluded($post_type, $tax_slug)
    {   
        if( !is_array($this->options['exclude']) ) {
            return true;

        } elseif( !is_array($this->options['exclude'][$post_type]) ) {
            return true;

        } elseif( !in_array($tax_slug, $this->options['exclude'][$post_type]) ) {
            return true;

        } else {
            return false;

        }
    }

    /**
     * Sanitize each setting field as needed
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {   
        
        if( !is_array($input) ) { $input = array(); }

        // Structure the data by array[post_type][tax]
        $inputs = array();
        foreach( $input as $key => $value ) {
            $params = explode('|', $key);
            $post_type = $params[1];
            $tax = $params[2];

            if (!is_array($inputs[$post_type])) { $inputs[$post_type] = array(); }
            $inputs[$post_type][] = $tax;
        }

        // Loop through all post types and taxes and exclude ones not posted
        $exclude = array();
        foreach( $this->post_types as $post_type ) {
            $taxonomies = get_object_taxonomies( $post_type );

            foreach( $taxonomies as $taxonomy ) {
                if( $taxonomy === 'category' ) { continue; }
                if( !isset($inputs[$post_type]) || !in_array($taxonomy, $inputs[$post_type]) ) {
                    if( !is_array($exclude[$post_type]) ) { $exclude[$post_type] = array(); }
                    $exclude[$post_type][] = $taxonomy;
                }
            }
        }

        return array(
            'exclude' => $exclude
        );
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Show taxonomy filters for:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function taxonomy_callback($args)
    {   
        print '<input 
            type="checkbox" 
            id="' .$args['label_for']. '" 
            name="niko_tf_options[' .$args['label_for']. ']"';
        print $args['checked'] === true ? 'checked>' : '>';
    }
}
