<?php

class ITSEC_Clef_Admin {
    private static $instance = null;

    private 
        $core,
        $module,
        $enabled;

    private function __construct( $core, $module ) {
        $this->core = $core;
        $this->module = $module;
        $this->enabled = $module != null;

        add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
        add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
        add_filter( 'itsec_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
        add_filter( 'itsec_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
    }

    public function clef_settings_path() {
        return $this->page;
    }

    public function add_admin_meta_boxes() {
        add_meta_box(
            'clef_description',
            __( 'Description', 'ithemes-security' ),
            array( $this, 'add_module_intro' ),
            'security_page_toplevel_page_itsec-clef',
            'normal',
            'core'
        );

        add_meta_box(
            'clef_options',
            __( 'Configure Clef', 'ithemes-security' ),
            array( $this, 'metabox_advanced_settings' ),
            'security_page_toplevel_page_itsec-clef',
            'advanced',
            'core'
        );
    }

    public function add_sub_page( $available_pages ) {
        global $itsec_globals;

        $this->page = $available_pages[0] . '-clef';
        add_filter( 'clef_settings_path', array( $this, 'clef_settings_path' ));

        $available_pages[] = add_submenu_page( 
            'itsec', 
            __( 'Clef', 'ithemes-security' ), 
            __( 'Clef', 'ithemes-security' ), 
            $itsec_globals['plugin_access_lvl'], 
            $available_pages[0] . '-clef',
            array( $this->core, 'render_page' ) );

        return $available_pages;
    }

    public function add_admin_tab( $tabs ) {
        $tabs[$this->page] = __( 'Clef', 'ithemes-security');
        return $tabs;
    }

    public function add_module_intro( $screen ) {
        $content = '<p>' . __( 'Clef provides two-factor, single-sign-on for WordPress. Once you sign in to one WordPress site using Clef, you can sign into all of your Clef-enabled sites with a single click. And once you sign out of the app on your phone, you are automatically signed out of all your WordPress sites', 'ithemes-security' ) . '</p>';

        echo $content;
    }

    public function clef_enable_header() {
        $content = '<h2 id="clef_enable" class="settings-section-header">' . __( 'Enable Clef', 'ithemes-security' ) . '</h2>';
        echo $content;
    }

    /**
     * Execute admin initializations
     *
     * @return void
     */
    public function initialize_admin() {
        add_settings_section(
            'clef-enable',
            __( 'Enable Clef', 'ithemes-security' ),
            array( $this, 'clef_enable_header' ),
            'security_page_toplevel_page_itsec-clef'
        );

        add_settings_field(
            'itsec_clef[enabled]',
            __( 'Enable Clef', 'ithemes-security' ),
            array( $this, 'clef_enabled' ),
            'security_page_toplevel_page_itsec-clef',
            'clef-enable'
        );

        register_setting(
            'security_page_toplevel_page_itsec-clef',
            'itsec_clef',
            array( $this, 'sanitize_module_input' )
        );
    }

    public function sanitize_module_input( $input ) {
        $input['enabled'] = ( isset( $input['enabled'] ) && intval( $input['enabled'] == 1 ) ? true : false );
        return $input;
    }

    public function clef_enabled( $args ) {
        $enabled = $this->enabled ? 1 : 0;

        $content = '<input type="checkbox" id="itsec_clef_enabled" name="itsec_clef[enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
        $content .= '<label for="itsec_clef_enabled"> ' . __( 'Enable two-factor login with Clef', 'ithemes-security' ) . '</label>';

        echo $content;
    }

    public function metabox_advanced_settings() {
        if ($this->enabled) {
            do_action('clef_render_settings');
            $this->render_form();
        } else {
            $this->render_form();
        }
    }

    public function render_form() {
        //set appropriate action for multisite or standard site
        if ( is_multisite() ) {
            $action = 'edit.php?action=itsec_clef';
        } else {
            $action = 'options.php';
        }

        printf( '<form name="%s" method="post" action="%s" class="itsec-form">', get_current_screen()->id, $action );

        $this->core->do_settings_sections( 'security_page_toplevel_page_itsec-clef', false );

        echo '<p>' . PHP_EOL;

        settings_fields( 'security_page_toplevel_page_itsec-clef' );

        echo '<input class="button-primary" name="submit" type="submit" value="' . __( 'Save Changes', 'ithemes-security' ) . '" />' . PHP_EOL;

        echo '</p>' . PHP_EOL;

        echo '</form>';
    }

    /**
     * Start the Clef Admin Module
     *
     * @param Ithemes_ITSEC_Core $core Instance of core plugin class
     * @param Clef $module Instance of the Clef module class
     *
     * @return ITSEC_Clef_Admin The instance of the ITSEC_Clef_Admin class
     */
    public static function start( $core, $module ) {

        if ( ! isset( self::$instance ) || self::$instance === null ) {
            self::$instance = new self( $core, $module );
        }

        return self::$instance;

    }
}

?>
