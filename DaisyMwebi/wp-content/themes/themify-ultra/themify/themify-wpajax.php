<?php
/***************************************************************************
 *
 * 	----------------------------------------------------------------------
 * 						DO NOT EDIT THIS FILE
 *	----------------------------------------------------------------------
 * 
 *  				     Copyright (C) Themify
 * 
 *	----------------------------------------------------------------------
 *
 ***************************************************************************/

defined( 'ABSPATH' ) || exit;

// Initialize actions
$themify_ajax_actions = array(
	'plupload',
	'get_404_pages',
	'remove_video',
	'save',
	'reset_settings',
	'pull',
	'add_link_field',
	'media_lib_browse',
	'clear_all_webp',
	'clear_all_menu',
	'clear_all_concate',
	'clear_all_html',
	'clear_gfonts',
    'search_autocomplete',
    'twitter_flush',
    'ajax_load_more',
	'required_plugins_modal',
	'update_license',
	'news_widget',
	'activate_plugin',
    'upload_json'
);
foreach($themify_ajax_actions as $action){
	add_action('wp_ajax_themify_' . $action, 'themify_' . $action);
}

//Show 404 page in autocomplete
function themify_get_404_pages(){
    if(!empty($_POST['term'])){
        $args = array(
            'sort_order' => 'asc',
            'sort_column' => 'post_title',
            'post_type' => 'page',
            's'=>  sanitize_text_field($_POST['term']),
            'no_found_rows'=>true,
            'ignore_sticky_posts'=>true,
            'cache_results'=>false,
            'update_post_term_cache'=>false,
            'update_post_meta_cache'=>false,
            'post_status' => 'publish',
            'posts_per_page' => 15
        );
        add_filter( 'posts_search', 'themify_posts_where', 10, 2 );
        $terms = new WP_Query($args);
        $items = array();
        if($terms->have_posts()){
            while ($terms->have_posts()){
                $terms->the_post();
                $items[] = array('value'=>  get_the_ID(),'label'=>  get_the_title());
            }
        }
        echo wp_json_encode($items);
    }
    wp_die();
}

//Search only by post title
function themify_posts_where($search,$wp_query ){       
    if ( ! empty( $search ) && ! empty( $wp_query->query_vars['search_terms'] ) ) {
        global $wpdb;

        $q = $wp_query->query_vars;
        $n = ! empty( $q['exact'] ) ? '' : '%';

        $search = array();
        $search[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $wpdb->esc_like( implode(' ',$q['search_terms']) ) . $n );

        if ( ! is_user_logged_in() )
            $search[] = "$wpdb->posts.post_password = ''";

        $search = ' AND ' . implode( ' AND ', $search );
    }
    return $search;
}
/**
 * AJAX - Plupload execution routines
 * @since 1.2.2
 * @package themify
 */
function themify_plupload() {
    $imgid = $_POST['imgid'];
    ! empty( $_POST[ '_ajax_nonce' ] ) && check_ajax_referer($imgid . 'themify-plupload');
	/** Decide whether to send this image to Media. @var String */
	$add_to_media_library = isset( $_POST['tomedia'] ) ? $_POST['tomedia'] : false;
	/** If post ID is set, uploaded image will be attached to it. @var String */
	$postid = isset( $_POST['topost'] )? $_POST['topost'] : '';
 
    /** Handle file upload storing file|url|type. @var Array */
    $file = wp_handle_upload($_FILES[$imgid . 'async-upload'], array('test_form' => true, 'action' => 'themify_plupload'));
	
	// if $file returns error, return it and exit the function
	if ( isset( $file['error'] ) && ! empty( $file['error'] ) ) {
		echo json_encode($file);
		exit;
	}

	//let's see if it's an image, a zip file or something else
	$ext = explode('/', $file['type']);
	
	// Import routines
	if( 'zip' === $ext[1] || 'rar' === $ext[1] || 'plain' === $ext[1] ){
		
		$url = wp_nonce_url('admin.php?page=themify');

		if (false === ($creds = request_filesystem_credentials($url) ) ) {
			return true;
		}
		if ( ! WP_Filesystem($creds) ) {
			request_filesystem_credentials($url, '', true);
			return true;
		}
		
		global $wp_filesystem;
		
		if ( 'zip' === $ext[1] || 'rar' === $ext[1] ) {
			$upload_dir = themify_get_cache_dir();
			unzip_file( $file['file'], $upload_dir['path'] );
			if( $wp_filesystem->exists( $upload_dir['path'] . 'data_export.txt' ) ){
				$data = $wp_filesystem->get_contents( $upload_dir['path'] . 'data_export.txt' );
				themify_set_data( unserialize( $data ) );
				$wp_filesystem->delete( $upload_dir['path'] . 'data_export.txt' );
				$wp_filesystem->delete($file['file']);
			} else {
				echo json_encode( array( 'error' => __( 'Data could not be loaded', 'themify' ) ) );
				die;
			}
		} else {
			if( $wp_filesystem->exists( $file['file'] ) ){
				$data = $wp_filesystem->get_contents( $file['file'] );
				themify_set_data( unserialize( $data ) );
				$wp_filesystem->delete($file['file']);
			} else {
				echo json_encode( array( 'error' => __( 'Data could not be loaded', 'themify' ) ) );
				die;
			}
		}
		
	} else {
		//Image Upload routines
		if( 'tomedia' === $add_to_media_library ){
			
			// Insert into Media Library
			// Set up options array to add this file as an attachment
	        $attachment = array(
	            'post_mime_type' => sanitize_mime_type($file['type']),
	            'post_title' => str_replace('-', ' ', sanitize_file_name(pathinfo($file['file'], PATHINFO_FILENAME))),
	            'post_status' => 'inherit'
	        );
			
			if( $postid ){
				$attach_id = wp_insert_attachment( $attachment, $file['file'], $postid );
			} else {
				$attach_id = wp_insert_attachment( $attachment, $file['file'] );
			}
			$file['id'] = $attach_id;

			// Common attachment procedures
			require_once(ABSPATH . 'wp-admin/includes/image.php');
		    $attach_data = wp_generate_attachment_metadata( $attach_id, $file['file'] );
		    wp_update_attachment_metadata($attach_id, $attach_data);
			
			if( $postid ) {
				
				$full = wp_get_attachment_image_src( $attach_id, 'full' );

				update_post_meta($postid, $_POST['fields'], $full[0]);
				update_post_meta($postid, '_'.$_POST['fields'] . '_attach_id', $attach_id);				
			}

			$thumb = wp_get_attachment_image_src( $attach_id, 'thumbnail' );
			
			//Return URL for the image field in meta box
			$file['thumb'] = $thumb[0];
		}
	}
	$file['type'] = $ext[1];
	// send the uploaded file url in response
	echo json_encode($file);
    exit;
}

/**
 * AJAX - Remove image assigned in Themify custom panel. Clears post_image and _thumbnail_id field.
 * @since 1.7.4
 * @package themify
 */
function themify_remove_video() {
	check_ajax_referer( 'tf_nonce', 'nonce' );
	if ( isset( $_POST['postid'], $_POST['customfield'] ) ) {
		update_post_meta( $_POST['postid'], $_POST['customfield'], '' );
	} else {
		_e( 'Missing vars: post ID and custom field.', 'themify' );
	}
	die();
}

/**
 * AJAX - Save user settings
 * @since 1.1.3
 * @package themify
 */
function themify_save(){
	$previous_data = themify_get_data();

	check_ajax_referer( 'tf_nonce', 'nonce' );
	$temp = apply_filters( 'themify_save_data', themify_normalize_save_data( $_POST['data'] ), $previous_data );
	unset($temp['tmp_cache_network'],$temp['tmp_cache_concte_network'],$temp['tmp_regenerate_all_css']);
	themify_set_data( $temp );
	_e('Your settings were saved', 'themify');

	if (
		Themify_Enqueue_Assets::$mobileMenuActive !== intval( $temp['setting-mobile_menu_trigger_point'] )
		|| ( isset( $previous_data['skin'] ) && $previous_data['skin'] !== $temp['skin'])
        || ( isset( $previous_data['setting-header_design'] ) && $previous_data['setting-header_design'] !== $temp['setting-header_design'])
        || ( isset( $previous_data['setting-exclude_menu_navigation'] ) && $previous_data['setting-exclude_menu_navigation'] !== $temp['setting-exclude_menu_navigation'])
	) {
		Themify_Enqueue_Assets::clearConcateCss();
	}

	/* clear webP image cache when changing image quality */
	if ( (empty( $previous_data['setting-gf'] ) &&  !empty($temp['setting-gf']))  || (!empty( $previous_data['setting-gf'] ) &&  empty($temp['setting-gf']))) {
	    Themify_Storage::deleteByPrefix('tf_fg_css_');
	}
	/* clear google fonts cache*/

	if ( class_exists( 'Themify_Builder_Stylesheet' ) ) {
		$breakpoints=themify_get_breakpoints('all',true);
		foreach ( $breakpoints as $bp=>$v ) {
			if ( isset( $previous_data["setting-customizer_responsive_design_{$bp}"] ) && $previous_data["setting-customizer_responsive_design_{$bp}"] !== $temp["setting-customizer_responsive_design_{$bp}"] ) {
				Themify_Builder_Stylesheet::regenerate_css_files();
				break;
			}
		}
	}
	unset($previous_data);
	if(themify_get_server()==='nginx'){
		if(empty($temp['setting-webp'])){
			Themify_Enqueue_Assets::removeWebp();
		}
	}
	else{
            $isDev=!empty($temp['setting-dev-mode']);
            $gzip=$isDev?true:empty($temp['setting-cache_gzip']);
            $browser=$isDev?true:empty($temp['setting-cache_browser']);
            Themify_Enqueue_Assets::rewrite_htaccess($gzip,empty($temp['setting-webp']),$browser);
	}
	TFCache::remove_cache();
	if(empty($temp['setting-dev-mode'])){
		TFCache::create_config($temp);
	}
	else{
		TFCache::disable_cache();
	}
    TFCache::clear_3rd_plugins_cache();
	wp_die();
}

function themify_normalize_save_data($data){
    $data = explode('&', $data);
    $temp = array();
    foreach($data as $a){
	    $v = explode('=', $a);
	    $temp[$v[0]] = urldecode( str_replace('+',' ',preg_replace_callback('/%([0-9a-f]{2})/i', 'themify_save_replace_cb', urlencode($v[1]))) );
    }
    return $temp;
}

/**
 * Replace callback for preg_replace_callback used in themify_save().
 * 
 * @since 2.2.5
 * 
 * @param array $matches 0 complete match 1 first match enclosed in (...)
 * 
 * @return string One character specified by ascii.
 */
function themify_save_replace_cb( $matches ) {
	// "chr(hexdec('\\1'))"
	return chr( hexdec( $matches[1] ) );
}

/**
 * AJAX - Reset Settings
 * @since 1.1.3
 * @package themify
 */
function themify_reset_settings(){
	check_ajax_referer( 'tf_nonce', 'nonce' );
	$temp_data = themify_normalize_save_data($_POST['data']);
	$temp = array();
	foreach($temp_data as $key => $val){
		// Don't reset if it's not a setting or the # of social links or a social link or the Hook Contents
		if(strpos($key, 'setting') === false || strpos($key, 'hooks') || strpos($key, 'link_field_ids') || strpos($key, 'themify-link') || strpos($key, 'twitter_settings') || strpos($key, 'custom_css')){
			$temp[$key] = $val;
		}
	}
        $temp['setting-script_minification'] = 'disable';
	print_r(themify_set_data($temp));
	die();
}

/**
 * Pull data for inspection
 * @since 1.1.3
 * @package themify
 */
function themify_pull(){
	print_r(themify_get_data());
	die();
}

function themify_add_link_field(){
	check_ajax_referer( 'tf_nonce', 'nonce' );
	
	if( isset($_POST['fid']) ) {
		$hash = $_POST['fid'];
		$type = isset( $_POST['type'] )? $_POST['type'] : 'image-icon';
		echo themify_add_link_template( 'themify-link-'.$hash, array(), true, $type);
		wp_die();
	}
}

/**
 * Set image from wp library
 * @since 1.2.9
 * @package themify
 */
function themify_media_lib_browse() {
	if ( ! wp_verify_nonce( $_POST['media_lib_nonce'], 'media_lib_nonce' ) ) die(-1);

	$file = array();
	$postid = $_POST['post_id'];
	$attach_id = $_POST['attach_id'];

	$full = wp_get_attachment_image_src( $attach_id, 'full' );
	if( $_POST['featured'] ){
		//Set the featured image for the post
		set_post_thumbnail($postid, $attach_id);
	}
	update_post_meta($postid, $_POST['field_name'], $full[0]);
	update_post_meta($postid, '_'.$_POST['field_name'] . '_attach_id', $attach_id);

	$thumb = wp_get_attachment_image_src( $attach_id, 'thumbnail' );

	//Return URL for the image field in meta box
	$file['thumb'] = $thumb[0];

	echo json_encode($file);

	exit();
}


function themify_clear_all_webp(){
	check_ajax_referer('tf_nonce', 'nonce');
	wp_send_json_success(Themify_Enqueue_Assets::removeWebp());
}

function themify_clear_all_concate(){
	check_ajax_referer('tf_nonce', 'nonce');
        $type=false;
        if(is_multisite()){
	    if(!empty($_POST['all'])){
		$type='all';
	    }
	    else{
		$data = themify_normalize_save_data($_POST['data']);
		if(!empty($data['tmp_cache_concte_network'])){
                    $type='all';
		}
		$data=null;
	    }
	}
	Themify_Enqueue_Assets::clearConcateCss($type);
	wp_send_json_success();
}

function themify_clear_all_menu(){
	check_ajax_referer('tf_nonce', 'nonce');
	TFCache::remove_cache();
        TFCache::clear_3rd_plugins_cache();
	themify_clear_menu_cache();
	die('1');
}

function themify_clear_all_html(){
    check_ajax_referer('tf_nonce', 'nonce');
	$type='blog';
	if(is_multisite()){
		$data = themify_normalize_save_data($_POST['data']);
		if(!empty($data['tmp_cache_network'])){
			$type='all';
		}
		$data=null;
	}
    TFCache::remove_cache($type);
    die('1');
}
function themify_clear_gfonts(){
    check_ajax_referer('tf_nonce', 'nonce');
    Themify_Storage::deleteByPrefix('tf_fg_css_');
    wp_send_json_success();
}
add_action('wp_ajax_nopriv_themify_search_autocomplete','themify_search_autocomplete');
function themify_search_autocomplete(){
    if(!empty($_POST['s'])){
        $s  = sanitize_text_field($_POST['s']);
        if(!empty($s)){
            global $query,$found_types;
            if(!empty($_POST['post_type'])){
                $post_types  = array(sanitize_text_field($_POST['post_type']));
            }else{
                if(true===themify_is_woocommerce_active() && 'product' === themify_get( 'setting-search_post_type','all',true )){
                    $post_types = array('product');
                }else{
                    $post_types = Themify_Builder_Model::get_post_types();
                    unset($post_types['attachment']);
                    $post_types=array_keys($post_types);
                }
            }
            $query_args = array(
                'post_type'=>$post_types,
                'post_status'=>'publish',
                'posts_per_page'=>22,
                's'=>$s
            );
            if(!empty($_POST['term'])){
                Themify_Builder_Model::parseTermsQuery( $query_args, urldecode($_POST['term']), $_POST['tax'] );
            }
            $query_args = apply_filters('themify_search_args',$query_args);
            wp_reset_postdata();
            $query = new WP_Query( $query_args );
            $found_types=array();
            while ( $query->have_posts() ){
                $query->the_post();
                $post_type = get_post_type();
                if (($key = array_search($post_type, $query_args['post_type'])) !== false) {
                    unset($query_args['post_type'][$key]);
                    $found_types[]=$post_type;
                }
                if(empty($query_args['post_type'])){
                    break;
                }
            }
            $query->rewind_posts();

            ob_start();
            include( THEMIFY_DIR.'/includes/search-box-result.php' );
            ob_end_flush();
        }
    }
    wp_die();
}

function themify_twitter_flush() {
	check_ajax_referer( 'tf_nonce', 'nonce' );
	if ( ! class_exists( 'Themify_Twitter_Api' ) ) {
		require THEMIFY_DIR . '/class-themify-twitter-api.php';
	}
	Themify_Twitter_Api::clear_cache();
	wp_send_json_success();
}

/*Load More Ajax - Used for module ajax load more*/
if(!function_exists('themify_ajax_load_more')){
    function themify_ajax_load_more(){
        if(!empty($_POST['module']) && !empty($_POST['id'])){
            $builder_id=(int)($_POST['id']);
            $el_id=sanitize_text_field($_POST['module']);
            $mod_id=str_replace('tb_','',$el_id);
            global $ThemifyBuilder;
            $data = $ThemifyBuilder->get_flat_modules_list( (int)$_POST['id'] );
            if ( ! empty( $data ) ) {
                foreach ( $data as $module ) {
                    if ( isset( $module['element_id'], $module['mod_settings'] ) && $module['element_id'] === $mod_id ) {
                        $mod_setting = $module['mod_settings'];
                        $slug = $module['mod_name'];
                    }
                }
            }
            if(!empty($mod_setting)){
                global $paged;
                $paged=(int)$_POST['page'];
                $paged=$paged<1?1:$paged;
                if(themify_is_themify_theme() && is_file(THEME_DIR.'/theme-options.php')){
                    require_once( THEME_DIR.'/theme-options.php' );
                    global $themify;
                    if(isset($themify) && method_exists($themify,'template_redirect')){
                        $themify->template_redirect();
                    }
                }
                echo Themify_Builder_Model::$modules[ $slug ]->render($slug, $el_id, $builder_id, $mod_setting);
            }
        }
        wp_die();
    }
    // Ajax filter actions
    add_action('wp_ajax_nopriv_themify_ajax_load_more','themify_ajax_load_more');
}


function themify_required_plugins_modal() {
    check_ajax_referer( 'tf_nonce', 'nonce' );
    if(!current_user_can('manage_options')){
	wp_send_json_error(__( 'You are not allowed to import data.','themify' ));
    }
    $required_plugins = !empty($_POST['plugins'])?sanitize_text_field($_POST['plugins']):'';
    $result=array('plugins'=>array());
    if( ! empty( $required_plugins )) {
	$required_plugins =  explode( ',', $required_plugins );
	$all_plugins = get_plugins();
	$can_install=current_user_can( 'install_plugins' ) ;
	$themify_updater = class_exists('Themify_Updater')?Themify_Updater::get_instance():null;
	foreach($required_plugins as $plugin){
	    $plugin=trim($plugin);
	    $plugin_info = themify_get_known_plugin_info($plugin);
	    if($plugin_info!==false){
		if(isset($all_plugins[$plugin_info['path']])){
		    if(is_plugin_active( $plugin_info['path'] )){
			$plugin_info['active']=1;
		    }
		    elseif(current_user_can( 'activate_plugin', $plugin )){
			$valid= function_exists('validate_plugin_requirements')?validate_plugin_requirements($plugin_info['path']):true;
			if(is_wp_error( $valid )){
			    $plugin_info['error']=$valid->get_error_message();
			}
			else{
			    $plugin_info['active']=0;
			}
		    }
		    else{
			$plugin_info['error']=__( 'You are not allowed to activate this plugin.','themify' );
		    }
		}
		elseif($can_install===true){
		    $plugin_info['install']=$themify_updater===null || $themify_updater->has_error() || !empty($plugin_info['wp_hosted']) || $themify_updater->has_access( $plugin )?1:'buy';
		}
		else{
		    $plugin_info['error']= __( 'You are not allowed to install plugins on this site.' ,'themify' );
			   
		}
		unset($plugin_info['desc'],$plugin_info['image'],$plugin_info['path'],$plugin_info['wp_hosted']);
		$result['plugins'][$plugin]=$plugin_info;
	    }
	    else{
		$result['plugins'][$plugin]=array(
		    'error'=> __( 'Unknown plugin.','themify' ),
		    'name'=>$plugin
		);
	    }
	}
	unset($required_plugins,$all_plugins,$can_install);
    }
    $result['labels']=array(
	'head'=>__( 'This demo requires these plugins/addons:', 'themify' ),
	'import_warning'=>__( 'Proceed import without the required addons/plugins might show incomplete/missing content.', 'themify' ),
	'proceed_import'=> __( 'Proceed Import', 'themify' ),
	'erase'=>__( 'Erase ALL previously imported demo content', 'themify' ),
	'modify'=> __( 'Keep modified posts/pages', 'themify' ),
	'builder_img'=>__('Import Builder layout images (will take longer)','themify'),
	'install'=>__( 'Install', 'themify' ),
	'activate'=>__( 'Activate', 'themify' ),
	'buy'=>__( 'Buy', 'themify' ),
	'note'=>__('WARNING: Importing the demo content will override your Themify settings, menu and widget settings. It will also add the content (posts, pages, featured images, widgets, menus, etc.) to your site as per our demo setup. It is recommend to do on a fresh/development site.','themify'),
	'plugins'=>array(
	    'activate_done'=>__('%plugin% successfully activated','themify'),
	    'activate_fail'=>__('Failed to activate %plugin%: %error%','themify'),
	    'install_fail'=>__('Failed to install %plugin%: %error%','themify'),
	    'install_done'=>__('%plugin% successfully installed','themify'),
	    'install'=>__('Installing %plugin%','themify'),
	    'activate'=>__('Activating %plugin%','themify'),
	)
    );
    $result['has_demo']=Themify_Import_Helper::has_demo_content();
    wp_send_json_success($result);
}


function themify_update_license() {
    check_ajax_referer( 'tf_nonce', 'nonce' );
    $themify_updater = Themify_Updater::get_instance();
    $result = $themify_updater->menu_p(true);
    if(true === $result){
	$theme = wp_get_theme();
        $theme = is_child_theme() ? $theme->parent() : $theme;
        ob_start();
        $themify_updater->themify_reinstall_theme( $theme->stylesheet );
        $result = array('html'=>ob_get_clean());
	wp_send_json_success($result);
    }
    else{
	$msg = $result===false?__('Invalid license key. Please enter your Themify username and a valid license key.','themify'):
	    __('You need the latest Themify Updater plugin for this feature. Please update your Themify Updater plugin.','themify');
	    
	wp_send_json_error($msg);
    }
}

/**
 * Install or Activate plugin for skin demo import and themify updater
 */
function themify_activate_plugin() {
    check_ajax_referer( 'tf_nonce', 'nonce' );
    $err='';
    if(!empty($_POST['plugin'])){
	$plugin=sanitize_key($_POST['plugin']);
	$plugin_info = themify_get_known_plugin_info($plugin);
	if($plugin_info!==false){
	    $allPlugins= get_plugins();
	    if(isset($allPlugins[$plugin_info['path']])){
		unset($allPlugins);
		if(!is_plugin_active( $plugin_info['path'] )){
		    if(current_user_can( 'activate_plugin', $plugin )){
			$result =activate_plugin($plugin_info['path'],false,false);
			if(is_wp_error($result)){
			    $err=$result->get_error_message();
			}
		    }
		    else{
			$err=__( 'You are not allowed to activate this plugin.','themify' );
		    }
		}
	    }
	    elseif(current_user_can( 'install_plugins' )){
		$isFree=!empty($plugin_info['wp_hosted']);	
		if($isFree===false){
		    if($plugin==='themify-updater'){
			if(!empty( $_FILES['data'] ) && is_file($_FILES['data']['tmp_name'] )){
			    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			    $updgrader=new Plugin_Upgrader();
			    $result=$updgrader->install($_FILES['data']['tmp_name'],array('overwrite_package'=>true));
			    if($result===true){
				$result =activate_plugin($plugin_info['path'],false,false);
				if(is_wp_error($result)){
				    $err=$result->get_error_message();
				}
			    }
			    else{
				$err= is_wp_error($result)?$result->get_error_message():__('Themify Updater installation failed','themify');
			    }
			}
			else{
			    $err=array('install_updater'=>1);
			}
		    }
		    else{
			if(!class_exists('Themify_Updater')){
			    $updater=themify_get_known_plugin_info('themify-updater');
			    if(isset($allPlugins[$updater['path']]) && !is_plugin_active( $updater['path'] )){
				$result =activate_plugin($updater['path'],false,false);
				if(is_wp_error($result)){
				    $err=$result->get_error_message();
				}
				elseif(!class_exists('Themify_Updater') && function_exists('themify_updater_init')){//plugins_loaded event already fired
				    themify_updater_init();
				}
			    }
			    unset($updater);
			}
			unset($allPlugins);
			if($err===''){
			    $themify_updater = class_exists('Themify_Updater')?Themify_Updater::get_instance():null;
			    if($themify_updater && method_exists($themify_updater, 'get_versions')){
				$versions = $themify_updater->get_versions();
				$isFree=!empty($versions)?$versions->has_attribute($plugin, 'wp_hosted'):false;
				unset($versions);
			    }
			    else{
				$themify_updater=null;
			    }
			}
		    }
		}
		if($err==='' && $plugin!=='themify-updater'){
		    if($isFree===true){
			$_POST['slug']=$plugin;
			$_REQUEST['_ajax_nonce']=wp_create_nonce( 'updates' );
			wp_ajax_install_plugin();
		    }
		    elseif(empty($themify_updater)){
			$err=array('install_updater'=>1);
		    }
		    else{
			if($themify_updater->has_error()){
			    $err=array('check_license'=>1,'errorMessage'=>sprintf(__('A valid membership is required to install %s','themify'),$plugin_info['name']));
			}
			elseif(!$themify_updater->has_access( $plugin )){
			    $err=array('buy'=>1,'errorMessage'=>sprintf(__('Your membership/license does not include %s','themify'),$plugin_info['name']),'url'=>$plugin_info['page']);
			}
			else{
			    $nonce=wp_create_nonce( 'install-plugin_'. str_replace('-plugin', '', $plugin) );
			    $installUrl= add_query_arg(array('action'=>'install-plugin','plugin'=>$plugin,'_wpnonce'=>$nonce),self_admin_url( 'update.php' ));
			    wp_send_json_success(array('install_plugin_url'=>$installUrl));
			} 
			
		    }
		    unset($themify_updater,$plugin_info);
		}
	    }
	    else{
		$err=__( 'You are not allowed to install plugins on this site.','themify' );
	    }
	    if($err===''){
		wp_send_json_success();
	    }
	}
	else{
	    $err=__('Unknown Plugin.','themify');
	}
    }
    wp_send_json_error($err);
}

/**
 * Handle the display of the Themify News admin dashboard widget
 *
 * Hooked to wp_ajax_themify_news_widget
 */
function themify_news_widget() {
	ob_start();
	wp_widget_rss_output( 'https://themify.me/blog/feed', array(
		'title'			=> esc_html__( 'Themify News', 'themify' ),
		'items'			=> 4,
		'show_summary'	=> 1,
		'show_author'	=> 0,
		'show_date'		=> 1
	) );
	$cache_key = 'themify_news_dashboard_widget';
	Themify_Storage::set( $cache_key, ob_get_flush(), 12 * HOUR_IN_SECONDS ); // Default lifetime in cache of 12 hours (same as the feeds)
	wp_die();
}


/**
 * Handle the upload json file
 */
function themify_upload_json(){
    check_ajax_referer( 'tf_nonce', 'nonce' );
    if (!empty($_POST['file'])) {
        if (!current_user_can('upload_files')) {
            $error = __('You aren`t allowed to upload file', 'themify');
        } else {
            if ( isset( $_POST['data'] ) ) {
                $data = stripslashes_deep( $_POST['data'] );
            }
            elseif ( isset( $_FILES['data'] ) ) {
                $data = file_get_contents( $_FILES['data']['tmp_name'] );
            }
            if(!empty($data)){
                global $wpdb;
                $slug = sanitize_file_name(pathinfo( $_POST['file'],PATHINFO_FILENAME));
                $filename= $slug.'.json';
                $sql= sprintf('post_name="%1$s" OR post_name="%1$s-1" OR post_name="%1$s-2" OR post_name="%1$s-3"',esc_sql($slug));
                $query = $wpdb->get_row("SELECT ID,post_mime_type FROM {$wpdb->prefix}posts WHERE ({$sql}) AND post_type='attachment' LIMIT 1",ARRAY_A );
                $attach_id=!empty($query) ? $query['ID'] : null;
                if($attach_id!==null) {
                    $duplicate = get_attached_file($attach_id);
                    if(!$duplicate ||!is_file($duplicate) || sha1_file($duplicate)!==sha1($data)){
                        if($duplicate && !is_file($duplicate)){
                            wp_delete_attachment($attach_id);
                        }
                        $attach_id=null;
                    }
                    unset($duplicate);
                }
                unset($query,$sql);
                if(empty($attach_id)){
                    $tmp = rtrim(sys_get_temp_dir(),'/').'/'.$filename;
                    if(file_put_contents($tmp,$data)){
                        $file = array(
                            'size'     => filesize($tmp),
                            'name'=> $filename,
                            'error'=>0,
                            'tmp_name' => $tmp
                        );
                        $title=!empty($_POST['title'])?sanitize_textarea_field($_POST['title']):'';
                        $attach_id=media_handle_sideload( $file, 0,$title,array(
                            'post_mime_type'=>'application/json',
                            'post_name'=>$slug
                        ) );
                        if(is_wp_error($attach_id)){
                            $error=$attach_id->get_error_message();
                            $attach_id=null;
                            if(is_file($tmp)){
                                unlink($tmp);
                            }
                        }
                        unset($tmp,$file);
                    }else{
                        $error = __('Can`t write tmp file', 'themify');
                    }
                }
                if(!empty($attach_id)){
                    wp_send_json_success(wp_get_attachment_url($attach_id));
                }
            }else{
                $error = __('Upload data is corrupted', 'themify');
            }
        }
        wp_send_json_error($error);
    }
}