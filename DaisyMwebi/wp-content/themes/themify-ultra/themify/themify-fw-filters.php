<?php
/**
 * Changes to WordPress behavior and interface applied by Themify framework, used in themes and builder
 *
 * @package Themify
 */

defined( 'ABSPATH' ) || exit;

add_filter('script_loader_tag', 'themify_defer_js', 11, 3);
add_filter('wp_get_attachment_image_src', 'themify_generate_src_webp', 100,1);
add_filter('wp_handle_upload_prefilter', 'themify_validate_json_file', 9999,1);
add_filter('wp_handle_sideload_prefilter', 'themify_validate_json_file', 9999,1);
add_filter( 'upload_mimes', 'themify_upload_json_mime_types' );

if(!is_admin() || themify_is_ajax()){
    add_filter('parse_query','themify_set_is_shop',100,1);
}

/**
 * load all themify, plugins and theme js with attribute defer(without blocking page render)
 *
 * @since 3.2.3
 */
function themify_defer_js($tag, $handle, $src) {
    if (!empty($tag)) {
        static $isJq = null;
        if ($isJq === null) {
            $isJq = themify_check('setting-jquery', true) && (!is_admin() || themify_is_ajax() ) && !is_customize_preview() && !themify_is_login_page();
        }
        if ($isJq === true || $handle === 'admin-bar' || Themify_Enqueue_Assets::is_themify_file($src, $handle) || in_array($handle, Themify_Enqueue_Assets::getKnownJs(), true)) {
            static $ex = null;
            if ($ex === null) {
                $ex = apply_filters('themify_defer_js_exclude', array());
            }
            if (!in_array($handle, $ex, true)) {
                if(strpos($tag, ' defer') === false){
                    $tag = str_replace(' src', ' defer="defer" src', $tag);
                }
                if($handle==='themify-main-script'){
                    $tag = str_replace(' src', ' data-v="'.THEMIFY_VERSION.'" data-pl-href="'.esc_attr(rtrim(plugins_url(),'/')).'/fake.css" data-no-optimize="1" data-noptimize="1" src', $tag);
                }
            }
        }
    }

    return $tag;
}

function themify_generate_src_webp($url) {
    if (!empty($url[0])) {
        themify_generateWebp($url[0]);
    }
    return $url;
}

function themify_set_is_shop($query){
	if($query && $query->is_main_query()){
		remove_filter('parse_query','themify_set_is_shop',100,1);
		$id=false;
        if($query->is_page()){
            $id=!empty($query->query_vars['page_id'])?$query->query_vars['page_id']:(!empty($query->queried_object->ID)?$query->queried_object->ID:-1);
            if($id>0){
                $id=(int)$id;
            }
        }
		themify_is_shop($id);
	}
	return $query;
}

/*
 * Validate if json file and allow to upload
 * */
function themify_validate_json_file(array $file){
    if(empty($file['error']) && strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))==='json' && is_file($file['tmp_name'])){
        try{
            $testJson=json_decode(file_get_contents($file['tmp_name']),true);
            $err = empty($testJson) || json_last_error()!==JSON_ERROR_NONE?__('Json file isn`t valid json', 'themify'):'';
            unset($testJson);
        }
        catch(Error  $e){
            $err=sprintf(__('Json file isn`t valid json:%s', 'themify'),$e->getMessage());
        }
        if($err!==''){
            remove_filter( 'upload_mimes', 'themify_upload_json_mime_types' );
            $file['error']=$err;
        }
    }
    return $file;
}

function themify_upload_json_mime_types( $existing_mime_types = array() ) {
    $existing_mime_types['json'] = extension_loaded( 'fileinfo' )?'text/plain':'application/json';
    return $existing_mime_types;
}
/**
 * Disable builder in page option modal
 */
if(!empty($_GET['tf-meta-opts'])){
    function themify_theme_disable_builder_page_opts() {
        add_filter('themify_enable_builder','themify_theme_filter_page_options',99);
    }

    function themify_theme_filter_page_options(){
        return 'disable';
    }
    add_action( 'after_setup_theme', 'themify_theme_disable_builder_page_opts', 1 );
}