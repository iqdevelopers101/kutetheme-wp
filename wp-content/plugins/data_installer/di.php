<?php
/*
Plugin Name: Data Installer
Plugin URI: http://kutethemes.net/
Description: Install Sample Data Category, content, history, media, menus, mics, stage, widget,...
Version: 1.0
Author: Angels.IT
*/
// Load API
require_once dirname( __FILE__ ) . '/utility.php';


class KT_Data_Installer{
    /**
	 * the paths.
	 *
	 * @since 1.0
	 * @var string
	 */
	private $paths;
    
    private $url;
    /**
	 * Core singleton class
	 * @var Singleton self - pattern realization
	 */
	private static $_instance;
    
    /**
     * the demo data
     * 
     * @since 1.0
     * @var array()
     */
    private $data = array(
        'default' => array(
            'title'      => 'Default demo',
            'screenshot' => 'http://localhost/wordpress/wp-content/themes/Newsmag/includes/demos/default/screenshot.png',
            'file'       => 'default'
        ),//
    );
    /**
	 * Get the instane of KT_Data_Installer
	 *
	 * @return self
	 */
	public static function getInstance() {
		if ( ! ( self::$_instance instanceof self ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
    
    /**
	 * Constructor loads API functions, defines paths and adds required wp actions
	 *
	 * @since  1.0
	 */
	public function __construct() {
        $this->paths = dirname( __FILE__ );
        $this->url = plugin_dir_url( __FILE__ );
        
        add_action('admin_menu', array( &$this, 'data_installer_menu' ));
        
        // Add hooks
        do_action( 'kt_data_installer_plugins_loaded' );
        
        load_plugin_textdomain( 'kutetheme', false, $this->paths . 'languages' );
        
        add_action( 'init', array( &$this, 'init' ), 9 );
        
        register_activation_hook( __FILE__, array( $this, 'activationHook' ) );
        
        add_action( 'admin_enqueue_scripts', array( $this, 'kt_enqueue_script' ) );
        
        add_action( 'wp_ajax_nopriv_kt_ajax_demo_install', array($this, 'kt_ajax_demo_install'));
        add_action( 'wp_ajax_kt_ajax_demo_install', array($this, 'kt_ajax_demo_install'));
        
        
	}
    public function data_installer_menu(){
        add_menu_page( 'Data Exporter', 'Data Exporter', 'edit_posts', 'kt_data_exporter', array( &$this, 'kt_data_exporter_page' ), 'dashicons-admin-tools' );
        add_menu_page( 'Data Installer', 'Data Installer', 'edit_posts', 'kt_data_installer', array( &$this, 'kt_data_installer_page' ), 'dashicons-admin-network' );
    }
    /**
	 * Enables to add hooks in activation process.
	 * @since 1.0
	 */
	public function activationHook() {
		do_action( 'kt_data_installer_activation_hook' );
	}
    public function kt_enqueue_script(){
        wp_localize_script( 'kt-ajax-script', 'kt_object_ajax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
    	) );  
        
        wp_register_style( 'kt_data_css', $this->url . '/assets/css/style.css' );
        wp_enqueue_style( 'kt_data_css' );
        
        wp_register_script( 'kt_data_js', $this->url . '/assets/js/scripts.js' );
        wp_enqueue_script( 'kt_data_js', array( 'jquery' ) );
    }
    
    public function kt_ajax_demo_install(){
        @set_time_limit(600);
        $packet = 'default';
        $action = 'install';
        
        if (isset($_POST[ 'kt_demo_action' ])) {
            $action = $_POST[ 'kt_demo_action' ];
        }
        
        if (isset($_POST[ 'kt_packet' ])) {
            $packet = $_POST[ 'kt_packet' ];
        }
        
        if( $action == 'install'){
            $current = get_option( 'kt_demo_packet' );
            if( $current && $current != $action ){
                $this->remove_all();
            }
            $this->install( $packet );
        }else{
            $this->remove_all();
            delete_option( 'kt_demo_packet' );
        }
    }
    public function install( $packet ){
        if( isset( $this->data[ $packet ]['file'] ) and ! empty( $this->data[ $packet ]['file'] ) ){
            $file = $this->data[ $packet ]['file'];
            require_once( $this->paths . "/data/{$packet}/{$file}.php");
            update_option( 'kt_demo_packet', $packet );
        }
    }
    /**
	 * Callback function for WP init action hook. Sets kt data installer mode and loads required objects.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return void
	 */
	public function init() {
		do_action( 'kt_data_installer_before_init' );
        do_action( 'kt_data_installer_after_init' );
    }
    
    /**
	 * Gets absolute path for file/directory in filesystem.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $file - file name or directory inside path
	 *
	 * @return string
	 */
	public function path( $file = '' ) {
		$path = $this->paths . ( strlen( $file ) > 0 ? '/' . preg_replace( '/^\//', '', $file ) : '' );

		return apply_filters( 'kt_data_installer_path_filter', $path );
	}
    public function remove_all(){
        kt_remove_attachment();
        kt_remove_cate();
        
        kt_remove_menu();
        remove_post();
        remove_page();
        remove_other_post();
        
        kt_remove_menu_items();
        remove_widget();
    }
    /**
     * Create admin page kt_data_installer_page
     * @since 1.0
     */
    public function kt_data_installer_page(){
        ?>
        <div class="kt-page-importer">
            <div class="kt-plugin-title">
                <h2><?php _e( 'Importer', 'kutetheme' ) ?></h2>
            </div>
            <?php if( ! empty( $this->data )  ): $current = get_option( 'kt_demo_packet' ); ?>
            <div class="kt-plugin-content">
                <ul class="container-box">
                    <?php foreach( $this->data as $k => $d ): ?>
                    <li class="box-item">
                        <?php if( isset( $d['screenshot'] ) ): ?>
                        <div class="item-thumbnail">
                            <img src="<?php echo $d['screenshot']; ?>" alt="screenshot" />
                        </div>
                        <?php endif; ?>
                        <?php if( isset( $d['title'] ) ): ?>
                        <div class="item-info">
                            <h3 class="info-title"><?php echo $d['title']; ?></h3>
                        </div>
                        <?php endif; ?>
                        <?php if( $current && $current == $k ): ?>
                            <div class="item-button">
                                <button class="button button-primary button-large" data-method="uninstall" data-packet="<?php echo $k; ?>"><?php _e( 'Uninstall', 'thumbnail' ) ?></button>
                            </div>
                        <?php else: ?>
                            <div class="item-button">
                                <button class="button button-primary button-large" data-method="install" data-packet="<?php echo $k; ?>"><?php _e( 'Install', 'thumbnail' ) ?></button>
                            </div>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    public function kt_data_exporter_page(){
        global $wpdb;
        ?>
        <div class="wrap">
            <h1><?php _e( 'Export', 'kutetheme' ); ?></h1>
            
            <p><?php _e('When you click the button below, We will create an PHP file for you to save to your computer.'); ?></p>
            <p><?php _e('Once you&#8217;ve saved the download file, you can use the Import function in another, We installation to import the content from this site.'); ?></p>
            
            <h3><?php _e( 'Choose what to export' ); ?></h3>
            <form name="kt_data_installer_page" id="kt_data_installer_page" method="GET" action="<?php echo plugin_dir_url(__FILE__) . 'export.php' ?>">
                <input type="hidden" name="download_export" value="true" />
                <p><label><input type="radio" name="content" value="all" checked="checked" /> <?php _e( 'All content' ); ?></label></p>
                <p class="description"><?php _e( 'This will contain all of your posts, pages, comments, custom fields, terms, navigation menus and custom posts.' ); ?></p>
                
                <p><label><input type="radio" name="content" value="posts" /> <?php _e( 'Posts' ); ?></label></p>
                <ul id="post-filters" class="export-filters">
                	<li>
                		<label><?php _e( 'Categories:' ); ?></label>
                		<?php wp_dropdown_categories( array( 'show_option_all' => __('All') ) ); ?>
                	</li>
                	<li>
                		<label><?php _e( 'Authors:' ); ?></label>
                        <?php
                    		$authors = $wpdb->get_col( "SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type = 'post'" );
                    		wp_dropdown_users( array( 'include' => $authors, 'name' => 'post_author', 'multi' => true, 'show_option_all' => __('All') ) );
                        ?>
                	</li>
                	<li>
                		<label><?php _e( 'Date range:' ); ?></label>
                		<select name="post_start_date">
                			<option value="0"><?php _e( 'Start Date' ); ?></option>
                			<?php kt_export_date_options(); ?>
                		</select>
                		<select name="post_end_date">
                			<option value="0"><?php _e( 'End Date' ); ?></option>
                			<?php kt_export_date_options(); ?>
                		</select>
                	</li>
                	<li>
                		<label><?php _e( 'Status:' ); ?></label>
                		<select name="post_status">
                			<option value="0"><?php _e( 'All' ); ?></option>
                			<?php $post_stati = get_post_stati( array( 'internal' => false ), 'objects' );
                			 foreach ( $post_stati as $status ) : ?>
                			 <option value="<?php echo esc_attr( $status->name ); ?>"><?php echo esc_html( $status->label ); ?></option>
                			<?php endforeach; ?>
                		</select>
                	</li>
                </ul>
                
                <p><label><input type="radio" name="content" value="pages" /> <?php _e( 'Pages' ); ?></label></p>
                <ul id="page-filters" class="export-filters">
                	<li>
                		<label><?php _e( 'Authors:' ); ?></label>
                        <?php
                        	$authors = $wpdb->get_col( "SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type = 'page'" );
                        	wp_dropdown_users( array( 'include' => $authors, 'name' => 'page_author', 'multi' => true, 'show_option_all' => __('All') ) );
                        ?>
                	</li>
                	<li>
                		<label><?php _e( 'Date range:' ); ?></label>
                		<select name="page_start_date">
                			<option value="0"><?php _e( 'Start Date' ); ?></option>
                			<?php kt_export_date_options( 'page' ); ?>
                		</select>
                		<select name="page_end_date">
                			<option value="0"><?php _e( 'End Date' ); ?></option>
                			<?php kt_export_date_options( 'page' ); ?>
                		</select>
                	</li>
                	<li>
                		<label><?php _e( 'Status:' ); ?></label>
                		<select name="page_status">
                			<option value="0"><?php _e( 'All' ); ?></option>
                			<?php foreach ( $post_stati as $status ) : ?>
                			<option value="<?php echo esc_attr( $status->name ); ?>"><?php echo esc_html( $status->label ); ?></option>
                			<?php endforeach; ?>
                		</select>
                	</li>
                </ul>
                
                <?php foreach ( get_post_types( array( '_builtin' => false, 'can_export' => true ), 'objects' ) as $post_type ) : ?>
                <p><label><input type="radio" name="content" value="<?php echo esc_attr( $post_type->name ); ?>" /> <?php echo esc_html( $post_type->label ); ?></label></p>
                <?php endforeach; ?>
                
                <p><label><input type="radio" name="content" value="nav_menu_item" /> <?php _e( 'Menu', 'kutetheme' ) ?></label></p>
                <p><label><input type="radio" name="content" value="widget" /> <?php _e( 'Widget', 'kutetheme' ) ?></label></p>
                <?php
                /**
                 * Fires after the export filters form.
                 *
                 * @since 3.5.0
                 */
                do_action( 'kt_export_filters' );
                ?>
                
                <?php submit_button( __('Download Export File') ); ?>
                </form>
            </div>
        <?php
    }
}
$data_installer = new KT_Data_Installer();

//require_once dirname( __FILE__ ) . '/data/welcometowordpressthemes.wordpress.2015-11-12.php';

