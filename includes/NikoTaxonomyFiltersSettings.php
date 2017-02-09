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
     */
    private $options;

    private $post_types;

    /**
     * Start up
     */
    public function __construct()
    {   
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
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
            'niko_tf-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        $this->options = get_option( 'niko_tf_options' );

        ?>
        <div class="wrap">
            <h1>Niko Taxonomy Filters Settings</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'niko_tf_option_group' );
                do_settings_sections( 'niko_tf-setting-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

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
        $this->options = get_option( 'niko_tf_options' );

        register_setting(
            'niko_tf_option_group', // Option group
            'niko_tf_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        // Loop through all registered post types and register sections for each

        foreach( $this->post_types as $post_type ) {
            
            $section_id = 'niko_tf-section-' . $post_type;

            add_settings_section(
                $section_id, // ID
                ucfirst($post_type), // Title
                array( $this, 'print_section_info' ), // Callback
                'niko_tf-setting-admin' // Page
            );  

            // Add inputs for each taxonomy registered for current post type
            $taxonomies = get_object_taxonomies( $post_type );

            foreach( $taxonomies as $tax_slug ) {
                if( $tax_slug === 'category' ) { continue; }
                $tax_obj = get_taxonomy($tax_slug);
                $field_id = 'niko_tf-setting|' . $post_type . '|' . $tax_slug . '';
                $checked = $this->is_excluded($post_type, $tax_slug);

                add_settings_field(
                    $field_id,
                    $tax_obj->labels->name,
                    array( $this, 'taxonomy_callback' ), 
                    'niko_tf-setting-admin', 
                    $section_id,
                    array(
                        'label_for' =>  $field_id, 
                        'checked' => $checked
                    )
                );
            }
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
        if( !is_array($input) ) {
            return array(
                'exclude' => array()
            );            
        }

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
                if( !isset($inputs[$post_type]) || !in_array($taxonomy, $inputs[$post_type]) ) {
                    if( !is_array($exclude[$post_type]) ) { $exclude[$post_type] = array(); }
                    $exclude[$post_type][] = $taxonomy;
                }
            }
        }

        // var_dump($exclude); die();

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
