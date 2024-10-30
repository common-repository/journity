<?php
/**
 * Plugin Name: Journity Installer
 * Author: Five Q Innovations, LLC
 * Author URI: https://journity.com
 * Contributors: iandbanks
 * Description: This plugin makes it easy to install the scripts necessary to run Journity on your website. Journity helps grow your Nonprofit through personalized engagement.
 * Version: 1.0.12
 */

/*************************
 * Table of Contents
 * 1.0 Initial Setup
 * 2.0 Set up Admin Notices
 * 2.1 Add Settings Link
 * 3.0 Enqueue Scripts
 * 4.0 Add Options Page
 *************************/


/*************************
 * 1.0 Initial Setup
 *************************/

// Check to see if the customer ID is saved in the DB.
$journity_generated_id = ''; // ID's from auto-generation should get saved here.
$customer_id           = '';

$settings = get_option( 'journity_settings' );
// Check if the customer ID has been entered in the database.
if ( $settings === false || $settings['journity_id'] === '' ) {

	// The option/value doesn't exist. Check to see if this is an auto generated plugin form the Journity website.
	if ( $journity_generated_id !== '' ) {
		// Auto Generated. Lets update the option table to this value.
		update_option( 'journity_settings', array( 'journity_id' => $journity_generated_id ) );

	}

	// Now lets set $customer_id to our new value.
	$customer_id = $journity_generated_id; // Assign it this way so we're not doing any extra DB queries.  options table
} else {
	$setting = get_option( 'journity_settings' );

	// Assign Customer ID to the value stored in the databse.
	$customer_id = $setting['journity_id'];
}


/*************************
 * 2.0 Set up Admin Notices
 *************************/

/**
 * journity_admin_notice
 * Journity Admin Notice
 *
 * Displays an admin notice if the user does not have their Journity ID set.
 */
function journity_admin_notice() {
	$setting = get_option( 'journity_settings' );
	if ( $setting === false || $setting['journity_id'] === '' ):
		?>
        <div class="notice notice-success">
            <p><?php _e( 'Almost Finished. <a href="' . get_home_url() . '/wp-admin/options-general.php?page=journity" style="color:#ff624b;">Enter your Journity ID to finish setup!</a> Don\'t have a Journity account? <a href="https://app.journity.com/signup/" style="color:#ff624b;">Sign up today!</a>', 'fiveq' ); ?></p>
        </div>
	<?php endif;
}

add_action( 'admin_notices', 'journity_admin_notice' );


/*************************
 * 2.1 Add Settings Link
 *************************/

function plugin_add_settings_link( $links ) {
	$settings_link = '<a href="options-general.php?page=journity">' . __( 'Settings' ) . '</a>';
	array_push( $links, $settings_link );

	return $links;
}

$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'plugin_add_settings_link' );


/*************************
 * 3.0 Enqueue Scripts
 *************************/

/**
 * journity_enqueue_scripts
 * Installs the necessary Journity scripts
 */
function journity_enqueue_scripts() {
	global $customer_id;

	// Prevent Journity scripts from loading in the WordPress admin.
	if(!is_admin()){
		// Enqueue Journity collector
		wp_enqueue_script( 'journity', '//cf.journity.com/collector/' . $customer_id . '.js' );

		// Enqueue Journity styles.
		wp_enqueue_style( 'journity-css', '//cf.journity.com/personalizer/' . $customer_id . '.css' );

		// Enqueue Journity script.
		wp_enqueue_script( 'journity-js', '//cf.journity.com/personalizer/' . $customer_id . '.min.js', array( 'journity' ) );
    }

}

add_action( 'wp_enqueue_scripts', 'journity_enqueue_scripts', 99 ); // Installs Journity on the front end.

function journity_async_scripts( $tag ) {
	if (stripos($tag, 'journity') !== false) {

		return str_replace('<script ', '<script async="true" ', $tag);
	}
	return $tag;
}

add_action( 'script_loader_tag', 'journity_async_scripts', 10, 1 );


/******************
 * 4.0 Add Options Page
 ******************/

add_action( 'admin_menu', 'journity_add_admin_menu' );
add_action( 'admin_init', 'journity_settings_init' );


/**
 * journity_add_admin_menu
 *
 * Adds the admin menu to the settings tab.
 */
function journity_add_admin_menu() {

	add_submenu_page( 'options-general.php', 'Journity', 'Journity', 'manage_options', 'journity', 'journity_options_page' );

}


/**
 * journity_settings_init
 *
 * Initializes the settings needed for the Journity Plugin
 */
function journity_settings_init() {

	register_setting( 'journityPlugin', 'journity_settings' );

	add_settings_section(
		'journity_journityPlugin_section',
		__( '', 'fiveq' ),
		'journity_settings_section_callback',
		'journityPlugin'
	);

	add_settings_field(
		'journity_id',
		__( 'Journity ID', 'fiveq' ),
		'journity_id_render',
		'journityPlugin',
		'journity_journityPlugin_section'
	);
}


/**
 * journity_id_render
 * Renders out the text input required for entering the Journity ID
 */
function journity_id_render() {

	$options = get_option( 'journity_settings' );
	?>

    <input placeholder="Paste Journity ID Here" type='text' name='journity_settings[journity_id]'
           value='<?php echo $options['journity_id']; ?>' style="width:300px; border-radius: 3px;"> <a class="where"
                                                                                                       href="#"
                                                                                                       data-container="find">
        <small>Where is my ID?</small>
    </a>
    <div id="find">
        <ol>
            <li>Log into your Journity account</li>
            <li>Copy the ID from the address bar in your browser<br>
                <img src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/images/journity-id-location@2x.jpg'; ?>"
                     alt="Journity" width="512"></li>
        </ol>

        <p>Your ID may be the same as your website address. If this is the case, enter in the website without the <code>http://</code>
            or
            <code>https://</code>.<br>
            <small>So
                if your website is https://example.com, then you'd enter <code
                        style="font-size:10px;">example.com</code></small>
        </p>
    </div>

    <style>
        .where {
            margin-left: 10px;
        }

        #find {
            display: none;
            background: #ffffff;
            max-width: 512px;
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
        }

        #find img {
            width: 100%;
        }
    </style>

    <script>
        jQuery(function ($) {
            let id_link = $('a.where');

            id_link.on('click', function (e) {
                e.preventDefault();

                let container_id = $(this).data('container');
                console.log(container_id);

                $('#' + container_id).toggle();
            })
        });
    </script>

	<?php

}

/**
 * journity_settings_section_callback
 *
 * Currently doesn't do anything. Will likely be used in the future.
 */
function journity_settings_section_callback() {

	//echo __( 'Enter your Journity Settings', 'fiveq' );

}

/**
 * journity_options_page
 * Journity Options Page
 */
function journity_options_page() {

	?>
    <div class="wrap">
        <form action='options.php' method='post'>

            <h1 class="title"><img src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/images/journity.svg'; ?>"
                                   alt="Journity" width="200"></h1>

			<?php
			settings_fields( 'journityPlugin' );
			do_settings_sections( 'journityPlugin' );
			submit_button( 'Activate' );
			?>

        </form>
        <style>
            .button-primary {
                background: #ff624b !important;
                border: 1px solid #ff624b !important;
                border-color: #ff624b !important;
                box-shadow: none !important;
                text-shadow: none !important;
                font-weight: 700;
                letter-spacing: .2px;
                text-transform: uppercase;
            }

            .button-primary:hover {
                background: #e04e38 !important;
            }
        </style>
    </div>
	<?php

}
