<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sc_menu_slug = 'star-citizen';
function sc_loc_menu_exists() {
    global $menu, $sc_menu_slug;
    if (!is_array($menu)) {
        return false;
    }
    foreach ( $menu as $item ) {
        if ( $item[2] == $sc_menu_slug ) {
            return true;
        }
    }
    return false;
}

add_action('admin_menu', 'sc_loc_add_admin_menu');
add_action('admin_menu', function() {
    global $sc_menu_slug;
    if (sc_loc_menu_exists()) remove_submenu_page($sc_menu_slug, $sc_menu_slug);
}, 999);
add_action('admin_head', 'sc_loc_fix_svg_size');

function sc_loc_fix_svg_size() {
    global $sc_menu_slug;
    if (sc_loc_menu_exists()) echo '
    <style>
        /* Målret billedet i dit specifikke menupunkt */
        #toplevel_page_'.$sc_menu_slug.' .wp-menu-image img {
            width: 20px !important;   /* WordPress ikoner er 20x20 */
            height: 20px !important;
            padding: 0 !important;
            margin: 0 !important;
            box-sizing: border-box;
            display: inline-block;
            vertical-align: middle;
        }

        /* Centrer ikonet i cirklen/feltet */
        #toplevel_page_'.$sc_menu_slug.' .wp-menu-image {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
    </style>
    ';
}

function sc_loc_add_admin_menu() {
    if (!sc_loc_menu_exists()) {
        add_menu_page(
            'Star Citizen', // Page title
            'Star Citizen', // Menu title
            'manage_options', // Capability
            'star-citizen', // Menu slug
            null, // Callback function
                plugins_url('sc-localization/assets/scc-logo.svg', 'sc-localization'), // Icon (WordPress Dashicon)
            26
        );
    }
    add_submenu_page(
        'star-citizen', // Parent slug
        'Localization', // Page title
        'Localization', // Menu title
        'manage_options', // Capability
        'sc-localization', // Menu slug
        'sc_loc_admin_page', // Callback function
        10
    );
}

function sc_loc_admin_page() {
    if ( isset( $_POST['sc_loc_upload_nonce'] ) && wp_verify_nonce( $_POST['sc_loc_upload_nonce'], 'sc_loc_upload_action' ) ) {
        if ( isset( $_POST['sc_loc_global_ini_version'] ) ) {
            update_option(
                    'sc_loc_global_ini_version',
                    sanitize_text_field( wp_unslash( $_POST['sc_loc_global_ini_version'] ) )
            );
        }

        if ( isset( $_POST['sc_loc_version_message'] ) ) {
            update_option(
                    'sc_loc_version_message',
                    sanitize_text_field( wp_unslash( $_POST['sc_loc_version_message'] ) )
            );
        }

        sc_loc_handle_uploads();
        echo '<div class="updated"><p>' . esc_html__( 'Files uploaded correctly.', 'sc-localization' ) . '</p></div>';
    }

    $global_ini_version = get_option( 'sc_loc_global_ini_version', '' );
    $version_message    = get_option( 'sc_loc_version_message', '' );

    if ( file_exists( SC_LOC_UPLOAD_DIR . '/counter' ) ) {
        $counter = (int) file_get_contents( SC_LOC_UPLOAD_DIR . '/counter' );
    } else {
        $counter = 0;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Star Citizen Localization Settings', 'sc-localization' ); ?></h1>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'sc_loc_upload_action', 'sc_loc_upload_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label
                                 for="sc_loc_global_ini_version"><?php esc_html_e( 'Latest global.ini version', 'sc-localization' ); ?></label>
                    </th>
                    <td>
                        <input
                                type="text"
                                name="sc_loc_global_ini_version"
                                id="sc_loc_global_ini_version"
                                class="regular-text"
                                value="<?php echo esc_attr( $global_ini_version ); ?>"
                                 placeholder="<?php esc_attr_e( 'e.g. 4.7.0-11445650', 'sc-localization' ); ?>"
                        >
                         <p class="description"><?php esc_html_e( 'This version is displayed in the frontend via the download button.', 'sc-localization' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label
                                 for="sc_loc_version_message"><?php esc_html_e( 'Version message in the main menu', 'sc-localization' ); ?></label>
                    </th>
                    <td>
                        <input
                                type="text"
                                name="sc_loc_version_message"
                                id="sc_loc_version_message"
                                class="regular-text"
                                value="<?php echo esc_attr( $version_message ); ?>"
                                 placeholder="<?php esc_attr_e( 'e.g. Alliance Aid', 'sc-localization' ); ?>"
                        >
                         <p class="description"><?php esc_html_e( 'This will be displayed at the top of the game\'s main menu, above the "Persistent Universe" button.', 'sc-localization' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label
                                 for="global_ini"><?php printf( esc_html__( 'Global.ini (Main file) in multiple files (max size %sBytes)', 'sc-localization' ), ini_get( 'upload_max_filesize' ) ); ?></label>
                    </th>
                    <td>
                        <input type="file" name="global_ini" id="global_ini" accept=".zip">
                        <input type="file" name="global_ini_1" id="global_ini_1" accept=".z01">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label
                                for="components_ini"><?php esc_html_e( 'Components.ini', 'sc-localization' ); ?></label>
                    </th>
                    <td><input type="file" name="components_ini" id="components_ini" accept=".ini"></td>
                </tr>
                <tr>
                    <th scope="row"><label
                                for="vehicles_ini"><?php esc_html_e( 'Vehicles.ini', 'sc-localization' ); ?></label>
                    </th>
                    <td><input type="file" name="vehicles_ini" id="vehicles_ini" accept=".ini"></td>
                </tr>
                <tr>
                    <th scope="row"><label
                                for="contracts_ini"><?php esc_html_e( 'contracts.ini', 'sc-localization' ); ?></label>
                    </th>
                    <td><input type="file" name="contracts_ini" id="contracts_ini" accept=".ini"></td>
                </tr>
                <tr>
                    <th scope="row"><label
                                for="extras_ini"><?php esc_html_e( 'extras.ini', 'sc-localization' ); ?></label>
                    </th>
                    <td><input type="file" name="extras_ini" id="extras_ini" accept=".ini"></td>
                </tr>
            </table>
            <?php submit_button( __( 'Save and upload files', 'sc-localization' ) ); ?>
        </form>

        <hr>
        <h2><?php esc_html_e( 'Current Status', 'sc-localization' ); ?></h2>
        <ul>
            <li><?php printf( esc_html__( 'Downloads: %d', 'sc-localization' ), $counter ); ?></li>
            <li><?php printf( esc_html__( 'Latest global.ini version: %s', 'sc-localization' ), $global_ini_version ? esc_html( $global_ini_version ) : '<span style="color:red;">' . esc_html__( 'Not specified', 'sc-localization' ) . '</span>' ); ?></li>
            <?php foreach ( array( 'global.ini', 'components.ini', 'vehicles.ini', 'contracts.ini', 'extras.ini' ) as $file ) { ?>
                <li><?php echo $file; ?>
                    : <?php echo file_exists( SC_LOC_UPLOAD_DIR . '/' . $file ) ? '<span style="color:green;">' . esc_html__( 'Uploaded', 'sc-localization' ) . ': ' . date( "D, d M Y H:i:s", filemtime( SC_LOC_UPLOAD_DIR . '/' . $file ) ) . '</span> <a href="' . wp_upload_dir()['baseurl'] . '/sc-localization/' . $file . '" target="_blank">' . esc_html__( 'Download', 'sc-localization' ) . '</a>' : '<span style="color:red;">' . esc_html__( 'Missing', 'sc-localization' ) . '</span>'; ?></li>
            <?php } // endforeach ?>
        </ul>
    </div>
    <?php
    exec( 'rm -f ' . SC_LOC_UPLOAD_DIR . '/*.z* ' . SC_LOC_UPLOAD_DIR . '/z*' );
}

function sc_loc_handle_uploads() {
    //echo '<pre>'.var_export($_FILES,1).'</pre>';
    $files = array( 'global_ini', 'global_ini_1', 'components_ini', 'vehicles_ini', 'contracts_ini', 'extras_ini' );
    foreach ( $files as $file_key ) {
        if ( ! empty( $_FILES[ $file_key ]['tmp_name'] ) ) {
            $destination = SC_LOC_UPLOAD_DIR . '/';
            if ( $file_key === 'global_ini_1' ) {
                $destination .= 'global_ini.z01';
            } elseif ( $file_key === 'global_ini' ) {
                $destination .= 'global_ini.zip';
            } else {
                $destination .= str_replace( '_', '.', $file_key );
            }
            move_uploaded_file( $_FILES[ $file_key ]['tmp_name'], $destination );
        }
    }
    if ( ! empty( $_FILES['global_ini_1']['tmp_name'] ) ) {
        $out = [];
        exec( 'mv ' . SC_LOC_UPLOAD_DIR . '/global.ini ' . SC_LOC_UPLOAD_DIR . '/global-old.ini', $out );
        exec( 'zip -F ' . SC_LOC_UPLOAD_DIR . '/global_ini.zip --out ' . SC_LOC_UPLOAD_DIR . '/temp.zip', $out );
        exec( 'unzip ' . SC_LOC_UPLOAD_DIR . '/temp.zip -d ' . SC_LOC_UPLOAD_DIR, $out );
        exec( 'ls -l ' . SC_LOC_UPLOAD_DIR . '/', $out );
        echo '<pre>' . implode( '<br/>', $out ) . '</pre>';
        unlink( SC_LOC_UPLOAD_DIR . '/temp.zip' );
        unlink( SC_LOC_UPLOAD_DIR . '/global_ini.zip' );
        unlink( SC_LOC_UPLOAD_DIR . '/global_ini.z01' );
    } elseif ( ! empty( $_FILES['global_ini']['tmp_name'] ) ) {
        $out = [];
        exec( 'unzip ' . SC_LOC_UPLOAD_DIR . '/global_ini.zip -d ' . SC_LOC_UPLOAD_DIR, $out );
        //exec('ls -l '.SC_LOC_UPLOAD_DIR . '/', $out);
        //echo '<pre>'.implode('<br/>', $out).'</pre>';
        unlink( SC_LOC_UPLOAD_DIR . '/global_ini.zip' );
    }
}
