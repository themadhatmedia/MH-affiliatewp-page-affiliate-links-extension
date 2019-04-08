<?php 
/**
 * Plugin Name: Madhatmedia AffiliateWP Page Affiliate Links Extension
 * Plugin URI: https://madhatmafia.com
 * Description:  Display All pages with affiliate link using shortcode [mhm-affiliatewp-pages].   - Use to place users Affiliate link inside text  [mhm-affiliatewp-pages-link].   - Get user first name [mhm-affiliatewp-pages-firstname].   - Get user last name [mhm-affiliatewp-pages-lastname].   - Get user full name [mhm-affiliatewp-pages-fullname].
 * Version: 1.1
 * Author: Mad Hat Media LLC
 * Author URI: https://madhatmafia.com
 */

/*INCLUDE FILES*/

function mhm_awp_page_extension_save_post($post_id ) {

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (array_key_exists('mhm_awp_page_extension_field', $_POST)) {
        update_post_meta(
            $post_id,
            '_mhm_awp_page_extension_meta_box_value',
            $_POST['mhm_awp_page_extension_field']
        );
    }
    if (array_key_exists('mhm_awp_page_text_extension_field', $_POST)) {
        update_post_meta(
            $post_id,
            '_mhm_awp_page_text_extension_meta_box_value',
            $_POST['mhm_awp_page_text_extension_field']
        );
    }
    if (array_key_exists('mhm_awp_page_above_text_extension_field', $_POST)) {
        update_post_meta(
            $post_id,
            '_mhm_awp_page_text_above_extension_meta_box_value',
            $_POST['mhm_awp_page_above_text_extension_field']
        );
    }
    if (array_key_exists('mhm_awp_page_text_below_extension_field', $_POST)) {
        update_post_meta(
            $post_id,
            '_mhm_awp_page_text_below_extension_meta_box_value',
            $_POST['mhm_awp_page_text_below_extension_field']
        );
    }

    remove_action( 'save_post', 'mhm_awp_page_extension_save_post' );
}

add_action( 'save_post', 'mhm_awp_page_extension_save_post',100 );

function mhm_awp_page_extension_meta_box_affiliate()
{
    $screens = ['page'];
    foreach ($screens as $screen) {
        add_meta_box(
            'mhm_awp_page_extension_meta_box_id',           // Unique ID
            'Affiliate Page',  // Box title
            'mhm_awp_page_extension_meta_box_html',  // Content callback, must be of type callable
            $screen                   // Post type
        );
    }
}
add_action('add_meta_boxes', 'mhm_awp_page_extension_meta_box_affiliate');


function mhm_awp_page_extension_meta_box_html($post)
{
	global $wpdb;
	$value = get_post_meta($post->ID, '_mhm_awp_page_extension_meta_box_value', true);
	$text = get_post_meta($post->ID, '_mhm_awp_page_text_extension_meta_box_value', true);

	$pages = $wpdb->get_results(   "
          SELECT posts.* 
	  FROM $wpdb->posts as posts
	  WHERE posts.post_type = 'page' 
	  GROUP BY posts.post_title ASC
	  "
	);


    ?>
    <label for="mhm_awp_page_extension_field">Make this page as a Affiliate Page</label> <br>
    <select name="mhm_awp_page_extension_field" id="mhm_awp_page_extension_field" class="postbox">
        <option value="0" <?php selected($value, 0); ?> >No</option>
        <option value="1" <?php selected($value, 1); ?> >Yes</option>
    </select>
    <br>
    <label for="mhm_awp_page_text_extension_field">Affiliate Page Sales Text</label> <br>
    <select name="mhm_awp_page_text_extension_field" id="mhm_awp_page_text_extension_field" class="postbox">
    		<option value="" > ---- </option>
    	<?php foreach ( $pages as $page) : ?>
        		<option value="<?php echo $page->ID; ?>" <?php selected($text, $page->ID); ?> >
        			<?php echo $page->post_title; ?>
        		</option>
    	<?php endforeach; ?>
    </select>
    <br>
    <label for="mhm_awp_page_above_text_extension_field">Above link text</label> <br>
		<?php
		$text_above = get_post_meta($post->ID, '_mhm_awp_page_text_above_extension_meta_box_value', true);

			$settings = array(
			    'teeny' => true,
			    'textarea_rows' => 15,
			    'tabindex' => 1
			);

				wp_editor( $text_above , 'mhm_awp_page_above_text_extension_field', $settings);

		?>
    <br>
    <label for="mhm_awp_page_text_below_extension_field">Below link text</label> <br>
		<?php
		$text_below = get_post_meta($post->ID, '_mhm_awp_page_text_below_extension_meta_box_value', true);

			$settings = array(
			    'teeny' => true,
			    'textarea_rows' => 15,
			    'tabindex' => 1
			);

				wp_editor( $text_below , 'mhm_awp_page_text_below_extension_field', $settings);

		?>
    <?php
}

function mhm_awp_page_extension_show_pages( $atts ){
	global $wpdb, $current_user;
	get_currentuserinfo();
	
	$email = get_option('mhm_memberpress_init_license_awp_page_extention_email');
	$license_key = get_option('mhm_memberpress_init_license_awp_page_extention_license_key');
	$product_id = get_option('mhm_memberpress_init_license_awp_page_extention_product_id');
	
	$verify = mhm_plugin_verify_account( $email, $license_key, $product_id ); 
	if ( !isset($verify) ) {
		return "<h3>The license key entered was invalid. Please check your credentials and try again</h3>";
	}

	if ( $current_user->ID !== 0 ) {

		$keyword = isset( $_GET['link_search_page'] ) ? $_GET['link_search_page'] : '';
		$limit = isset( $_GET['page_per_page'] ) ? $_GET['page_per_page'] : 10;
		$page_link_page = isset( $_GET['page_link_page'] ) ? $_GET['page_link_page'] : 1;
		$from = ($page_link_page-1) * $limit; 


		$r = $wpdb->get_results(   "
	          SELECT posts.* 
		  FROM $wpdb->posts as posts
		  JOIN $wpdb->postmeta as postmeta
		  ON postmeta.post_id = posts.ID
		  WHERE posts.post_type = 'page' 
		  AND postmeta.meta_key = '_mhm_awp_page_extension_meta_box_value'
		  AND postmeta.meta_value = 1
		  GROUP BY postmeta.post_id
		   LIMIT ".$from.", ".$limit."
		  "
	        );

		$args = [];

		$args['page']   = ( int ) isset( $_GET['link_search_page'] ) ? $_GET['link_search_page'] : 1;

		$args['offset'] = ( $args['page'] - 1 ) * $limit;
		$args['search'] = isset( $_GET['link_search_page'] ) ? $_GET['link_search_page'] : '';

		$r = array_filter( $r, function( $value, $key ) use ( $args ) {

			$search = ! empty( $args['search'] ) ? strpos( strtolower( $value->post_title ), strtolower( $args['search'] ) ) !== false : true;



			return $search;

		}, ARRAY_FILTER_USE_BOTH );



		$r = array_values( $r );


		$output = '<div class="clear"></div>';
		$output .= '<h4 style="float:left;">Pages Affiliate Links</h4>';
		//$output .= affilateWP_page_link_search_box();
		$output .= affilateWP_page_link_page_per_page();
		$output .= '<div class="clear"></div>';

		$output .= '<div class="affiliates-container">';

		if ( count($r) > 0 ) {
			foreach ($r as $key) {
				$page_id = ( $key->ID ) ? $key->ID : 0;
				$page_id_a = '&affiliate_page='.$page_id;
				$page_button_redirect = get_page_link(get_post_meta($key->ID, '_mhm_awp_page_text_extension_meta_box_value', true));
				$above_link_text = get_post_meta($key->ID, '_mhm_awp_page_text_above_extension_meta_box_value', true);
				$below_link_text = get_post_meta($key->ID, '_mhm_awp_page_text_below_extension_meta_box_value', true);
				$page_id_page_button_redirect = '?affiliate_page='.$page_id;
				$url = affwp_get_affiliate_referral_page_url(get_page_link( $key->ID ) );
				$img_url = get_the_post_thumbnail_url($key->ID, 'thumbnail');
				$img_url = ( !empty($img_url) ) ? $img_url : 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6c/No_image_3x4.svg/1024px-No_image_3x4.svg.png' ;

				//$url = get_page_link( $key->ID );

				// $output .= '			<tr>';
				// $output .= '				<td>'. $key->post_title.'</td>';
				// $output .= '				<td>'. $url.'</td>';
				// $output .= '			</tr>';
				$output .= '<div class="affiliates-item-container">';
					$output .= '<img src="'.$img_url.'">';
					$output .= '<div class="paragraph">';
						$output .= '<h2 style="font-size: 27px;line-height: 1;text-align: left" class="vc_custom_heading vc_custom_1535145464306"><font style="vertical-align: inherit;"></font><font style="vertical-align: inherit; text-transform: uppercase;">'. $key->post_title.'</font></h2>';
						$output .= '
								<div id="text-block-3" class="mk-text-block   ">
									
									<div class="vc_btn3-container vc_btn3-inline">
									'. $above_link_text .' 
									</div><br>	
									
									<p><span style="color: #ff0000;"><strong>Ihr Link:</strong></span> '. $url .'</p>

									<div class="clearboth"></div>
								</div>				
								<div class="vc_btn3-container vc_btn3-inline">
									<a class="vc_general vc_btn3 vc_btn3-size-md vc_btn3-shape-rounded vc_btn3-style-modern vc_btn3-icon-left vc_btn3-color-juicy-pink" href=" '. $page_button_redirect.$page_id_page_button_redirect.'" target="_blank"title=""><i class="vc_btn3-icon fa fa-share"></i> Werbetexte für E-Mailings und Social Media
									</a>
								</div><br>

								<div class="vc_btn3-container vc_btn3-inline">
								'. $below_link_text .'
								</div>	

								';

					$output .= '</div>';
					$output .= '<div class="clear"></div>';

				$output .= '</div>';
			}
		} else {
			$output .= '			<tr>';
			$output .= '				<td  colspan="2" style="text-align:center; font-weight: bold;">No Pages Affiliate</td>';
			$output .= '			</tr>';
		}
		$output .= '</div>';

		$output .= affilateWP_page_pagination();
		return $output;
	}

}
add_shortcode( 'mhm-affiliatewp-pages', 'mhm_awp_page_extension_show_pages' );


function affwp_get_affiliate_referral_page_url( $url = '') {

	$defaults = array(
		'pretty' => '',
		'format' => '',
	);

	$args = wp_parse_args( $args, $defaults );

	if ( isset( $args['affiliate_id'] ) ) {
		$affiliate = affwp_get_affiliate( $args['affiliate_id'] );
	} else {
		$affiliate = affwp_get_affiliate();
	}

	$affiliate_id = $affiliate ? $affiliate->ID : 0;

	// get format, username or id
	$format = isset( $args['format'] ) ? $args['format'] : affwp_get_referral_format();

	// pretty URLs
	if ( ! empty( $args['pretty'] ) && 'yes' == $args['pretty'] ) {
		// pretty URLS explicitly turned on
		$pretty = true;
	} elseif ( ( ! empty( $args['pretty'] ) && 'no' == $args['pretty'] ) || false === $args['pretty'] ) {
		// pretty URLS explicitly turned off
		$pretty = false;
	} else {
		// pretty URLs set from admin
		$pretty = affwp_is_pretty_referral_urls();
	}

	// get base URL
	if ( isset( $args['base_url'] ) ) {
		$base_url = $args['base_url'];
	} else {
		$base_url = affwp_get_affiliate_base_url();
	}

	// add trailing slash only if no query string exists and there's no fragment identifier
	if ( isset( $args['base_url'] ) && ! array_key_exists( 'query', parse_url( $base_url ) ) && ! array_key_exists( 'fragment', parse_url( $base_url ) ) ) {
		$base_url = trailingslashit( $args['base_url'] );
	}

	// the format value, either affiliate's ID or username
	$format_value = affwp_get_referral_format_value( $format, $affiliate_id );

	$url_parts = parse_url( $base_url );

	// if fragment identifier exists in base URL, strip it and store in variable so we can append it later
	$fragment        = array_key_exists( 'fragment', $url_parts ) ? '#' . $url_parts['fragment'] : '';

	// if query exists in base URL, strip it and store in variable so we can append to the end of the URL
	$query_string    = array_key_exists( 'query', $url_parts ) ? '?' . $url_parts['query'] : '';

	$url_scheme      = isset( $url_parts['scheme'] ) ? $url_parts['scheme'] : 'http';
	$url_host        = isset( $url_parts['host'] ) ? $url_parts['host'] : '';
	$url_path        = isset( $url_parts['path'] ) ? $url_parts['path'] : '';
	$constructed_url = $url_scheme . '://' . $url_host . $url_path;
	$base_url        = $constructed_url;

	$base_url 		 =  $url;

	// set up URLs
	$pretty_urls     = trailingslashit( $base_url ) . trailingslashit( affiliate_wp()->tracking->get_referral_var() ) . trailingslashit( $format_value ) . $query_string . $fragment;
	$non_pretty_urls = esc_url( add_query_arg( affiliate_wp()->tracking->get_referral_var(), $format_value, $base_url . $query_string . $fragment ) );

	if ( $pretty ) {
		$referral_url = $pretty_urls;
	} else {
		$referral_url = $non_pretty_urls;
	}

	return $referral_url;

}

function affilateWP_page_link_search_box()
{
	$protocol = is_ssl() ? 'https://' : 'http://';
	$url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$keyword = isset( $_GET['link_search_page'] ) ? $_GET['link_search_page'] : '';

	

	$output ='<div id="affwp-paffl-search" style="float: right; margin-top: 7px;">
				<form type="GET" action="'. $url .'">'; ?>
					<?php foreach ( $_GET as $key => $value ) : ?>
					<?php if ( $key == 'link_search_page' || $key == 'link_page' ) continue; ?>
					<?php	$output .='<input type="hidden" name="'.esc_html( $key ) .'" value="'. esc_html( $value ) .'">'; ?>
					<?php endforeach; ?>
					<?php	$output .='<input type="search" name="link_search_page" placeholder="'. __( 'Enter page name ...', 'affwp-paffl' ).'" value="'. esc_html( $keyword ) .'">';?>
					<?php	$output .='<input type="submit" value="'. __( 'Search', 'affwp-paffl' ) .' ">
				</form>
			</div>';
	return $output;


}

function affilateWP_page_link_page_per_page(){
	$protocol = is_ssl() ? 'https://' : 'http://';

	$url = add_query_arg( array( 'page_per_page' => ' . per_page . ' ), $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

	

	$output = '<script type="text/javascript">
		jQuery(document).ready(function($) {
			$( "#affwp-paffl-page-per-page select" ).change( function() {
				var per_page = $( this ).val();
				var url = "'. add_query_arg( array( 'page_per_page' => 'per_page_value' ), $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ). '";
				url = url.replace( "per_page_value", per_page );
				window.location.href = url;
			} );
		});
	</script>';


	$output .= '<div id="affwp-paffl-page-per-page" style="float: right; margin-top: 7px; margin-right: 12px;">
		<span>'. __( 'Page Per Page: ', 'affwp-paffl' ) .'</span>
		<select> ';

		$selected = isset( $_GET['page_per_page'] ) && 10 == $_GET['page_per_page'] ? 'selected="selected"' : '' ;
	$output .= '
			<option value="10" '. $selected .'>10</option>'; ?>

		<?php for ( $i = 20; $i <= 100; $i += 20 ) : ?>
			<?php $selected = isset( $_GET['page_per_page'] ) && $i == $_GET['page_per_page'] ? 'selected="selected"' : ''; ?>
			<?php
					$output .= '<option value="'. $i.' " '. $selected .'> '. $i .'</option>'; ?>
		<?php endfor; ?>
	<?php
	$output .= '	
		</select>
	</div>';

	return $output;

}

function affilateWP_page_pagination()
{
	global $wpdb;
	$keyword = isset( $_GET['link_search_page'] ) ? $_GET['link_search_page'] : '';
	$limit = isset( $_GET['page_per_page'] ) ? $_GET['page_per_page'] : 10;
	$page_link_page = isset( $_GET['page_link_page'] ) ? $_GET['page_link_page'] : 1;
	$from = ($page_link_page-1) * $limit; 

	$counter = $wpdb->get_results(   "
          SELECT posts.* 
	  FROM $wpdb->posts as posts
	  JOIN $wpdb->postmeta as postmeta
	  ON postmeta.post_id = posts.ID
	  WHERE posts.post_type = 'page' 
	  AND postmeta.meta_key = '_mhm_awp_page_extension_meta_box_value'
	  AND postmeta.meta_value = 1
	  GROUP BY postmeta.post_id
	  "
	);

	$r = $wpdb->get_results(   "
          SELECT posts.* 
	  FROM $wpdb->posts as posts
	  JOIN $wpdb->postmeta as postmeta
	  ON postmeta.post_id = posts.ID
	  WHERE posts.post_type = 'page' 
	  AND postmeta.meta_key = '_mhm_awp_page_extension_meta_box_value'
	  AND postmeta.meta_value = 1
	  GROUP BY postmeta.post_id
	   LIMIT ".$from.", ".$limit."
	  "
	);

	$args = [];
	$args['total_products'] = count($counter);
	$args['total_displayed_products'] = count($r);
	$args['products_per_page'] = $limit;
	$args['total_page'] = ( int ) ceil( $args['total_products'] / $args['products_per_page'] );



	$output = '	
			<div class="affwp-paffl-pagination" style="float: right; margin-top: -15px;">
			<small>
			<span class="affwp-paffl-pagination-display"> Show '.$args['total_displayed_products'].' of '.  $args['total_products'] .'</span>
			<span class="affwp-paffl-pagination-page-label">'. __( 'Page: ', 'affwp-paffl' ) .'</span>';

			 $protocol = is_ssl() ? 'https://' : 'http://'; 

			$start = $args['page'] - 2;
			$end   = $args['page'] + 2; 

			for ( $i = $start ; $i <= $end; $i++ ) : 

				if ( $i < 1 || $i > $args['total_page'] ) continue;
					// First page link 
					if ( $i != 1 && $i == $start ) : 
					
					$output .= '	
							<span class="affwp-paffl-pagination-page" style="margin-right: 3px;"><a href="'. add_query_arg( array( 'page_link_page' => 1 ), $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']  ). '">1 ..</a></span> ';

					 endif;

			$url = ( $i == $args['page'] ) ? $i : '<a href="' . add_query_arg( array( 'page_link_page' => $i ), $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) . '">' . $i . '</a>'; 
			
			$output .= '	<span class="affwp-paffl-pagination-page" style="margin-right: 3px;">'. $url .'</span> ';

			// Last page link
			 if ( $i == $end && $i != $args['total_page'] ) : 
				
				$output .= '	<span class="affwp-paffl-pagination-page" style="margin-right: 3px;"><a href="'. add_query_arg( array( 'page_link_page' => $args['total_page'] ), $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']  ).'">.. '. $args['total_page'] .'</a></span>';

			 endif;

			endfor;
			$output .= '	</small>
					</div>';

	
	return $output;
}
function mhm_awp_page_extension_show_pages_link() {
	if ( $_GET['affiliate_page'] ) {
		$id = $_GET['affiliate_page'];
		$url = '';

		if ( isset($id) && !empty($id) ) {
			$url = affwp_get_affiliate_referral_page_url(get_page_link( $id ) );
		}
		
		return $url;

	}
}

add_shortcode( 'mhm-affiliatewp-pages-link', 'mhm_awp_page_extension_show_pages_link' );

function mhm_awp_page_extension_show_pages_firstname() {
	global $wpdb, $current_user;
	get_currentuserinfo();
	
	$email = get_option('mhm_memberpress_init_license_awp_page_extention_email');
	$license_key = get_option('mhm_memberpress_init_license_awp_page_extention_license_key');
	$product_id = get_option('mhm_memberpress_init_license_awp_page_extention_product_id');
	
	$verify = mhm_plugin_verify_account( $email, $license_key, $product_id ); 
	if ( !isset($verify) ) {
		return "<h3>The license key entered was invalid. Please check your credentials and try again</h3>";
	}
	
	if ( $current_user->ID !== 0 ) {
		$id = $current_user->ID;
		$user_info = get_userdata($id);
		return $user_info->first_name;

	}
}

add_shortcode( 'mhm-affiliatewp-pages-firstname', 'mhm_awp_page_extension_show_pages_firstname' );

function mhm_awp_page_extension_show_pages_lastname() {
	global $wpdb, $current_user;
	get_currentuserinfo();

	if ( $current_user->ID !== 0 ) {
		$id = $current_user->ID;
		$user_info = get_userdata($id);
		return $user_info->last_name;

	}
}

add_shortcode( 'mhm-affiliatewp-pages-lastname', 'mhm_awp_page_extension_show_pages_lastname' );

function mhm_awp_page_extension_show_pages_fullname() {
	global $wpdb, $current_user;
	get_currentuserinfo();

	if ( $current_user->ID !== 0 ) {
		$id = $current_user->ID;
		$user_info = get_userdata($id);
		return $user_info->first_name. " ".$user_info->last_name;

	}
}

add_shortcode( 'mhm-affiliatewp-pages-fullname', 'mhm_awp_page_extension_show_pages_fullname' );

if (!function_exists('mhm_plugin_verify_account')) {
	function mhm_plugin_verify_account( $email, $license_key, $product_id ) {

		$data = wp_remote_get( 'https://madhatmafia.com/woocommerce/?wc-api=software-api&request=check&email='.$email.'&license_key='.$license_key.'&product_id='.$product_id);

		if ( isset( $data["body"])) {
			$data = json_decode($data["body"]);
			return $data->success;
			
		}
	}
}
/* DASHBOARD PAGE */
//
add_action('admin_menu', 'mhm_affiliatewp_menu_setup');

function mhm_affiliatewp_menu_setup() {

	add_menu_page('MHM Affiliatewp Extension', 'MHM Affiliatewp Extension', 'manage_options', 'mhm-affiliate-setup');

	add_submenu_page( 'mhm-affiliate-setup', 'License', 'License',
	'manage_options', 'mhm-affiliate-setup', 'mhm_affiliatewp_setup_callback');

}

function mhm_affiliatewp_setup_callback() {
	if ( isset($_POST['mhm_verify_affilatewp_form']) ) {
		update_option('mhm_memberpress_init_license_awp_page_extention_email', $_POST['email'] );
		update_option('mhm_memberpress_init_license_awp_page_extention_license_key', $_POST['license_key'] );
		update_option('mhm_memberpress_init_license_awp_page_extention_product_id', 'affiliatewp-affiliate-links' );
		echo "<script type='text/javascript'>
        window.location=document.location.href;
        </script>"; 
		
	}
	$email = get_option('mhm_memberpress_init_license_awp_page_extention_email');
	$license_key = get_option('mhm_memberpress_init_license_awp_page_extention_license_key');
	$product_id = get_option('mhm_memberpress_init_license_awp_page_extention_product_id');
	$verify = mhm_plugin_verify_account( $email, $license_key, $product_id ); 
	
	if ( !isset($verify) ) {
		echo "<h3>The license key entered was invalid. Please check your credentials and try again.</h3>";
	} else {
		echo "<h3>Your license is successfully verified.</h3>";
	}	
	
    ?>
    <div class="notice notice-<?php echo $data?> ">
	<h3>Software key for Madhatmedia AffiliateWP Extension plugin</h3>
		<form method="POST">
		  <p>
			<input style="display: inline;" type="text" value="<?php echo $email ?>" placeholder="Email" name="email" required />
			<input style="display: inline;" type="text" value="<?php echo $license_key ?>" placeholder="License Key" name="license_key" required />
			<input style="display: inline;" type="submit" value="Submit" name="mhm_verify_affilatewp_form" />
		  </p>
		</form>

    </div>
    <?php
}