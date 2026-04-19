<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'sc_localization_frontend', 'sc_loc_frontend_shortcode' );

function sc_loc_frontend_shortcode() {
    if ( ! file_exists( SC_LOC_UPLOAD_DIR . '/components.ini' ) || ! file_exists( SC_LOC_UPLOAD_DIR . '/vehicles.ini' ) ) {
        return '<p>' . esc_html__( 'The necessary files have not been uploaded for Star Citizen localization yet.', 'sc-localization' ) . '</p>';
    }

    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_script( 'jquery-ui-draggable' );
    wp_enqueue_style( 'sc-loc-frontend-css', SC_LOC_URL . 'assets/css/frontend.css', array(), (string) time() );
    wp_enqueue_script( 'sc-loc-frontend-js', SC_LOC_URL . 'assets/js/frontend.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable' ), (string) time(), true );

    $vehicles           = sc_loc_parse_ini( SC_LOC_UPLOAD_DIR . '/vehicles.ini' );
    $global_ini_version = get_option( 'sc_loc_global_ini_version', '' );

    wp_localize_script( 'sc-loc-frontend-js', 'scLocData', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'sc_loc_download_nonce' ),
            'i18n'  => array(
                    'confirmReset'   => __( 'Are you sure you want to reset the sorting? All selected vehicles will be moved back.', 'sc-localization' ),
                    'enterSeparator' => __( 'Enter separator:', 'sc-localization' ),
                    'selectFormat'   => __( 'Please select a format for components.', 'sc-localization' ),
                    'configLoaded'   => __( 'Configuration loaded!', 'sc-localization' ),
                    'invalidJson'    => __( 'Error: File is not a valid JSON file.', 'sc-localization' ),
                    'unindent'       => __( 'Outdent', 'sc-localization' ),
                    'indent'         => __( 'Indent', 'sc-localization' ),
                    'editName'       => __( 'Edit name', 'sc-localization' ),
                    'space'          => __( 'Space', 'sc-localization' ),
                    'searchVehicle'  => __( 'Search for vehicle...', 'sc-localization' ),
                    'groupHeader'    => __( '%s. Group', 'sc-localization' ), // If we had group headers
            )
    ) );

    ob_start();
    ?>
    <div class="sc-loc-frontend">
        <h3><?php esc_html_e( '1. Define Component Format', 'sc-localization' ); ?></h3>
        <div class="sc-loc-help">
            <div class="sc-loc-help-title">
                <p><?php echo wp_kses_post( __( 'Build your format by dragging the elements (chips) below into the blue area.<br/>You can also click the buttons to add spaces or hyphens.', 'sc-localization' ) ); ?></p>
                <p><small>
                        <button class="button success" onclick="document.getElementById('sc-loc-load-btn').click()">
                            <?php esc_html_e( 'Load your setup from the last patch', 'sc-localization' ); ?>
                        </button>
                    </small></p>
                <input type="file" id="sc-loc-load-btn" style="display:none" accept=".json">
            </div>
            <div>
                <ul>
                    <li><strong><?php esc_html_e( 'Type:', 'sc-localization' ); ?></strong><br/>
                        <?php echo wp_kses_post( __( 'COOL or C for Cooler<br/>POWR or P for Powerplant<br/>SHLD or S for Shield<br/>QDRV or Q for Quantumdrive<br/>RADR or R for Radar', 'sc-localization' ) ); ?>
                    </li>
                </ul>
            </div>
            <div>
                <ul>
                    <li>
                        <strong><?php esc_html_e( 'Classification:', 'sc-localization' ); ?></strong><br/><?php echo wp_kses_post( __( 'CIV or C (Civilian)<br/>CMP or R (Competition/Racing)<br/>IND or I (Industrial)<br/>MIL or M (Military)<br/>STL or S (Stealth)', 'sc-localization' ) ); ?>
                    </li>
                </ul>
            </div>
            <div>
                <ul>
                    <li>
                        <strong><?php esc_html_e( 'Size:', 'sc-localization' ); ?></strong><br/><?php esc_html_e( '1, 2, 3 etc.', 'sc-localization' ); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Grade:', 'sc-localization' ); ?></strong><br/><?php esc_html_e( 'A, B, C, D.', 'sc-localization' ); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Name:', 'sc-localization' ); ?></strong><br/><?php esc_html_e( 'The actual name of the component.', 'sc-localization' ); ?>
                    </li>
                </ul>
            </div>
        </div>

        <div id="sc-loc-format-builder" class="sc-loc-drop-zone">
            <div class="chip" data-type="type_long"
                 title="<?php esc_attr_e( 'E.g. COOL, POWR', 'sc-localization' ); ?>"><?php esc_html_e( 'Type (4 characters)', 'sc-localization' ); ?></div>
            <div class="chip" data-type="type_short"
                 title="<?php esc_attr_e( 'E.g. C, P', 'sc-localization' ); ?>"><?php esc_html_e( 'Type (1 character)', 'sc-localization' ); ?></div>
            <div class="chip" data-type="class_long"
                 title="<?php esc_attr_e( 'E.g. MIL, STL', 'sc-localization' ); ?>"><?php esc_html_e( 'Classification (3 characters)', 'sc-localization' ); ?></div>
            <div class="chip" data-type="class_short"
                 title="<?php esc_attr_e( 'E.g. M, S', 'sc-localization' ); ?>"><?php esc_html_e( 'Classification (1 character)', 'sc-localization' ); ?></div>
            <div class="chip" data-type="size"
                 title="<?php esc_attr_e( 'E.g. S1, S2', 'sc-localization' ); ?>"><?php esc_html_e( 'Size', 'sc-localization' ); ?></div>
            <div class="chip" data-type="grade"
                 title="<?php esc_attr_e( 'E.g. A, B', 'sc-localization' ); ?>"><?php esc_html_e( 'Grade', 'sc-localization' ); ?></div>
            <div class="chip" data-type="name"
                 title="<?php esc_attr_e( 'The actual name of the component.', 'sc-localization' ); ?>"><?php esc_html_e( 'Name', 'sc-localization' ); ?></div>
        </div>

        <div class="sc-loc-separator-controls">
            <span><?php esc_html_e( 'Add separator:', 'sc-localization' ); ?></span>
            <button type="button" class="add-sep"
                    data-sep=" "><?php esc_html_e( 'Space [ ]', 'sc-localization' ); ?></button>
            <button type="button" class="add-sep"
                    data-sep="-"><?php esc_html_e( 'Hyphen [-]', 'sc-localization' ); ?></button>
            <button type="button" class="add-sep"
                    data-sep="_"><?php esc_html_e( 'Underscore [_]', 'sc-localization' ); ?></button>
            <button type="button" class="add-sep"
                    data-sep="."><?php esc_html_e( 'Dot [.]', 'sc-localization' ); ?></button>
            <button type="button"
                    id="sc-loc-custom-sep"><?php esc_html_e( 'Custom character...', 'sc-localization' ); ?></button>
        </div>
        <div class="sc-loc-active-format-container">
            <div id="sc-loc-active-format" class="sc-loc-drop-zone active-format" style="flex-grow:1">
                <!-- Her trækkes de ned -->
            </div>
            <button id="sc-loc-clear-format">
                <?php esc_html_e( 'Clear and start over', 'sc-localization' ); ?>
            </button>
        </div>
        <div class="sc-loc-help">
            <i><?php esc_html_e( 'Tip: Double-click an element in the blue area to remove it again.', 'sc-localization' ); ?></i>
        </div>
        <div class="sc-loc-active-format-container">
            <span><?php esc_html_e( 'Example of component name in the game:', 'sc-localization' ); ?></span></code>
            <div id="sc-loc-format-example" class="notranslate" translate="no">
                <!-- generate an example here -->
            </div>
        </div>

        <input type="hidden" id="sc-loc-format-input" name="sc_loc_format" value="">

        <h3><?php esc_html_e( '2. Select and Sort Vehicles', 'sc-localization' ); ?></h3>
        <div class="sc-loc-help">
            <div>
                <p><?php esc_html_e( 'Find the vehicles (ships, cars, motorcycles) you want to include in your translation. Drag them from the left to the right column.', 'sc-localization' ); ?></p>
            </div>
            <div>
                <p>
                    <small><?php esc_html_e( 'Tip: You can drag vehicles back to the available list to remove them, or press "Clear list" to reset.', 'sc-localization' ); ?></small>
                </p>
                <p>
                    <small><?php esc_html_e( 'Bonus Tip: You can group vehicles by clicking on the ⇄ icon. Indented vehicles will share the same number as the vehicle above. You can still change the name by clicking on ✎.', 'sc-localization' ); ?></small>
                </p>
            </div>
        </div>
        <div class="sc-loc-columns">
            <div class="sc-loc-col">
                <h4><?php esc_html_e( 'Available Vehicles', 'sc-localization' ); ?></h4>
                <div class="sc-loc-search-container">
                    <input type="text" id="sc-loc-vehicle-search"
                           placeholder="<?php esc_attr_e( 'Search for vehicle...', 'sc-localization' ); ?>">
                </div>
                <ul id="sc-loc-available-vehicles" class="sc-loc-list">
                    <?php foreach ( $vehicles as $key => $name ) : ?>
                        <li data-key="<?php echo esc_attr( $key ); ?>" data-name="<?php echo esc_attr( $name ); ?>">
                            <span class="vehicle-name notranslate" translate="no"><?php echo esc_html( $name ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="sc-loc-col">
                <h4><?php esc_html_e( 'Selected Vehicles (Sorted)', 'sc-localization' ); ?></h4>
                <div class="sc-loc-search-container">
                    <input type="button" id="sc-loc-vehicle-clear"
                           value="<?php esc_attr_e( 'Clear list', 'sc-localization' ); ?>">
                </div>
                <ul id="sc-loc-selected-vehicles" class="sc-loc-list">
                    <!-- Her trækkes de over -->
                </ul>
            </div>
        </div>

        <h3><?php esc_html_e( '3. Download your customized file', 'sc-localization' ); ?></h3>
        <div style="margin-top: 20px;">
            <button id="sc-loc-download-btn" class="button success"><?php
                echo esc_html_x( 'Download', 'Frontend download button', 'sc-localization' ); ?><br/>
                <strong><?php echo $global_ini_version ? '(' . esc_html( $global_ini_version ) . ')' : '(' . esc_html__( 'version not specified', 'sc-localization' ) . ')';
                    ?></strong></button>
        </div>

        <div class="sc-loc-help" style="margin-top: 30px; border-left-color: #46b450;">
            <div>
                <h3><?php esc_html_e( '4. File Installation', 'sc-localization' ); ?></h3>
                <p><?php echo wp_kses_post( __( 'Once you have downloaded your <code>global.ini</code> file, follow these steps to activate it in Star Citizen:', 'sc-localization' ) ); ?></p>
                <ol>
                    <li><?php echo wp_kses_post( __( 'Go to your Star Citizen installation folder<br/><small>(typically <code class="notranslate">Program Files\Roberts Space Industries\StarCitizen\LIVE</code>).</small>', 'sc-localization' ) ); ?></li>
                    <li><?php echo wp_kses_post( __( 'Create the following folder structure if it does not exist: <b><code class="language-html">data\\Localization\\english</code></b>.', 'sc-localization' ) ); ?></li>
                    <li><?php echo wp_kses_post( __( 'Place your downloaded <code>global.ini</code> file in this folder.', 'sc-localization' ) ); ?></li>
                </ol>
            </div>
            <div>
                <h4><?php esc_html_e( 'Configuration', 'sc-localization' ); ?></h4>
                <p><?php echo wp_kses_post( __( 'To ensure the game uses the file, make sure that the <code>user.cfg</code> file in the <code>LIVE</code> folder contains the following line:', 'sc-localization' ) ); ?></p>
                <pre style="background: #eee; padding: 5px; border-radius: 3px;">g_language = english</pre>
                <p>
                    <small><?php echo wp_kses_post( __( 'If <code>user.cfg</code> does not exist, simply create a new text file with this name in the <code>LIVE</code> folder.', 'sc-localization' ) ); ?></small>
                </p>
            </div>
            <div>
                <h4><?php esc_html_e( 'Save your settings!', 'sc-localization' ); ?></h4>
                <p>
                    <?php esc_html_e( 'Remember to save your setup for the next patch, so you don\'t have to setup component format and sort all your virtual ships again. Your grouping and corrected names will, of course, also be saved.', 'sc-localization' ); ?>
                </p>
                <button id="sc-loc-save-btn"
                        class="button success"><?php echo wp_kses_post( __( 'Save your settings', 'sc-localization' ) ); ?></button>
            </div>
        </div>
        <p>
            <small><?php printf( esc_html__( 'Made by %s, tested by %s and %s.', 'sc-localization' ), '<a href="https://robertsspaceindustries.com/citizens/DK-Raven" target="_blank">DK-Raven</a>', '<a href="https://robertsspaceindustries.com/citizens/Rimlee" target="_blank">Rimlee</a>', '<a href="https://robertsspaceindustries.com/citizens/PacManiacDK" target="_blank">PacManiacDK</a>' ); ?></small><br/>
            <small><?php printf( esc_html__( 'Credit to %s for inspiration and the hard work to classify all components in 4.6.0 and inspiration to make this tool.', 'sc-localization' ), '<a href="https://github.com/ExoAE/ScCompLangPack/">ExoAE</a>' ); ?></small><br/>
            <small><?php printf( esc_html__( 'Credit to %s for the contracts and ordinance work.', 'sc-localization' ), '<a href="https://github.com/MrKraken/StarStrings/">MrKrakken</a>' ); ?></small>
        </p>
    </div>
    <?php
    return ob_get_clean();
}
