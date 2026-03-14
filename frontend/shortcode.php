<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'sc_localization_frontend', 'sc_loc_frontend_shortcode' );

function sc_loc_frontend_shortcode() {
    if ( ! file_exists( SC_LOC_UPLOAD_DIR . '/components.ini' ) || ! file_exists( SC_LOC_UPLOAD_DIR . '/vehicles.ini' ) ) {
        return '<p>' . esc_html__( 'Der er ikke uploadet de nødvendige filer for at vi kan lave Star Citizen lokalisering endnu.', 'sc-localization' ) . '</p>';
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
                    'confirmReset'   => __( 'Er du sikker på, at du vil nulstille sorteringen? Alle valgte fartøjer flyttes tilbage.', 'sc-localization' ),
                    'enterSeparator' => __( 'Indtast adskillelsestegn:', 'sc-localization' ),
                    'selectFormat'   => __( 'Vælg venligst et format til komponenter.', 'sc-localization' ),
                    'configLoaded'   => __( 'Opsætningen er indlæst!', 'sc-localization' ),
                    'invalidJson'    => __( 'Fejl: Filen er ikke en gyldig JSON-fil.', 'sc-localization' ),
                    'unindent'       => __( 'Udryk', 'sc-localization' ),
                    'indent'         => __( 'Indryk', 'sc-localization' ),
                    'editName'       => __( 'Rediger navn', 'sc-localization' ),
                    'space'          => __( 'Mellemrum', 'sc-localization' ),
                    'searchVehicle'  => __( 'Søg efter fartøj...', 'sc-localization' ),
                    'groupHeader'    => __( '%s. Gruppe', 'sc-localization' ), // If we had group headers
            )
    ) );

    ob_start();
    ?>
    <div class="sc-loc-frontend">
        <h3><?php esc_html_e( '1. Definer Komponent Format', 'sc-localization' ); ?></h3>
        <div class="sc-loc-help">
            <div class="sc-loc-help-title">
                <p><?php echo wp_kses_post( __( 'Byg dit format ved at trække elementerne (chips) herunder ned i det blå område.<br/>Du kan også klikke på knapperne for at tilføje mellemrum eller bindestreger.', 'sc-localization' ) ); ?></p>
                <p><small>
                        <button class="button success" onclick="document.getElementById('sc-loc-load-btn').click()">
                            <?php esc_html_e( 'Indlæs din opsætning fra sidste patch', 'sc-localization' ); ?>
                        </button>
                    </small></p>
                <input type="file" id="sc-loc-load-btn" style="display:none" accept=".json">
            </div>
            <div>
                <ul>
                    <li><strong><?php esc_html_e( 'Type:', 'sc-localization' ); ?></strong><br/>
                        <?php echo wp_kses_post( __( 'COOL eller C for Cooler<br/>POWR eller P for Powerplant<br/>SHLD eller S for Shield<br/>QDRV eller Q for Quantumdrive<br/>RADR eller R for Radar', 'sc-localization' ) ); ?>
                    </li>
                </ul>
            </div>
            <div>
                <ul>
                    <li>
                        <strong><?php esc_html_e( 'Klassificering:', 'sc-localization' ); ?></strong><br/><?php echo wp_kses_post( __( 'CIV eller C (Civilian)<br/>CMP eller R (Competition/Racing)<br/>IND eller I (Industriel)<br/>MIL eller M (Military)<br/>STL eller S (Stealth)', 'sc-localization' ) ); ?>
                    </li>
                </ul>
            </div>
            <div>
                <ul>
                    <li>
                        <strong><?php esc_html_e( 'Størrelse:', 'sc-localization' ); ?></strong><br/><?php esc_html_e( '1, 2, 3 osv.', 'sc-localization' ); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Kvalitet:', 'sc-localization' ); ?></strong><br/><?php esc_html_e( 'A, B, C, D.', 'sc-localization' ); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Navn:', 'sc-localization' ); ?></strong><br/><?php esc_html_e( 'Komponentens faktiske navn.', 'sc-localization' ); ?>
                    </li>
                </ul>
            </div>
        </div>

        <div id="sc-loc-format-builder" class="sc-loc-drop-zone">
            <div class="chip" data-type="type_long"
                 title="<?php esc_attr_e( 'F.eks. COOL, POWR', 'sc-localization' ); ?>"><?php esc_html_e( 'Type (4 tegn)', 'sc-localization' ); ?></div>
            <div class="chip" data-type="type_short"
                 title="<?php esc_attr_e( 'F.eks. C, P', 'sc-localization' ); ?>"><?php esc_html_e( 'Type (1 tegn)', 'sc-localization' ); ?></div>
            <div class="chip" data-type="class_long"
                 title="<?php esc_attr_e( 'F.eks. MIL, STL', 'sc-localization' ); ?>"><?php esc_html_e( 'Klassificering (3 tegn)', 'sc-localization' ); ?></div>
            <div class="chip" data-type="class_short"
                 title="<?php esc_attr_e( 'F.eks. M, S', 'sc-localization' ); ?>"><?php esc_html_e( 'Klassificering (1 tegn)', 'sc-localization' ); ?></div>
            <div class="chip" data-type="size"
                 title="<?php esc_attr_e( 'F.eks. S1, S2', 'sc-localization' ); ?>"><?php esc_html_e( 'Størrelse', 'sc-localization' ); ?></div>
            <div class="chip" data-type="grade"
                 title="<?php esc_attr_e( 'F.eks. A, B', 'sc-localization' ); ?>"><?php esc_html_e( 'Kvalitet', 'sc-localization' ); ?></div>
            <div class="chip" data-type="name"
                 title="<?php esc_attr_e( 'Selve navnet på komponenten', 'sc-localization' ); ?>"><?php esc_html_e( 'Navn', 'sc-localization' ); ?></div>
        </div>

        <div class="sc-loc-separator-controls">
            <span><?php esc_html_e( 'Tilføj adskiller:', 'sc-localization' ); ?></span>
            <button type="button" class="add-sep"
                    data-sep=" "><?php esc_html_e( 'Mellemrum [ ]', 'sc-localization' ); ?></button>
            <button type="button" class="add-sep"
                    data-sep="-"><?php esc_html_e( 'Bindestreg [-]', 'sc-localization' ); ?></button>
            <button type="button" class="add-sep"
                    data-sep="_"><?php esc_html_e( 'Understreg [_]', 'sc-localization' ); ?></button>
            <button type="button" class="add-sep"
                    data-sep="."><?php esc_html_e( 'Punktum [.]', 'sc-localization' ); ?></button>
            <button type="button"
                    id="sc-loc-custom-sep"><?php esc_html_e( 'Eget tegn...', 'sc-localization' ); ?></button>
        </div>
        <div class="sc-loc-active-format-container">
            <div id="sc-loc-active-format" class="sc-loc-drop-zone active-format" style="flex-grow:1">
                <!-- Her trækkes de ned -->
            </div>
            <button id="sc-loc-clear-format">
                <?php esc_html_e( 'Ryd og start forfra', 'sc-localization' ); ?>
            </button>
        </div>
        <div class="sc-loc-help">
            <i><?php esc_html_e( 'Tip: Dobbeltklik på et element i det blå felt for at fjerne det igen.', 'sc-localization' ); ?></i>
        </div>
        <div class="sc-loc-active-format-container">
            <span><?php esc_html_e( 'Eksempel på komponent navn i spillet:', 'sc-localization' ); ?></span>
            <div id="sc-loc-format-example">
                <!-- generate an example here -->
            </div>
        </div>

        <input type="hidden" id="sc-loc-format-input" name="sc_loc_format" value="">

        <h3><?php esc_html_e( '2. Vælg og Sorter Fartøjer', 'sc-localization' ); ?></h3>
        <div class="sc-loc-help">
            <div>
                <p><?php esc_html_e( 'Find de fartøjer (skibe, biler, motorcykler) du vil have med i din oversættelse. Træk dem fra venstre til højre kolonne.', 'sc-localization' ); ?></p>
            </div>
            <div>
                <p>
                    <small><?php esc_html_e( 'Tip: Du kan trække fartøjer tilbage til den tilgængelige liste for at fjerne dem, eller trykke på "Ryd listen" for at nulstille.', 'sc-localization' ); ?></small>
                </p>
                <p>
                    <small><?php esc_html_e( 'Bonustip: Du kan gruppere fartøjer ved at klikke på ⇄ ikonet. Indrykkede fartøjer vil dele det samme nummer som det overstående fartøj. Du kan stadig ændre navnet ved at klikke på ✎.', 'sc-localization' ); ?></small>
                </p>
            </div>
        </div>
        <div class="sc-loc-columns">
            <div class="sc-loc-col">
                <h4><?php esc_html_e( 'Tilgængelige Fartøjer', 'sc-localization' ); ?></h4>
                <div class="sc-loc-search-container">
                    <input type="text" id="sc-loc-vehicle-search"
                           placeholder="<?php esc_attr_e( 'Søg efter fartøj...', 'sc-localization' ); ?>">
                </div>
                <ul id="sc-loc-available-vehicles" class="sc-loc-list">
                    <?php foreach ( $vehicles as $key => $name ) : ?>
                        <li data-key="<?php echo esc_attr( $key ); ?>" data-name="<?php echo esc_attr( $name ); ?>">
                            <span class="vehicle-name"><?php echo esc_html( $name ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="sc-loc-col">
                <h4><?php esc_html_e( 'Valgte Fartøjer (Sorteret rækkefølge)', 'sc-localization' ); ?></h4>
                <div class="sc-loc-search-container">
                    <input type="button" id="sc-loc-vehicle-clear"
                           value="<?php esc_attr_e( 'Ryd listen', 'sc-localization' ); ?>">
                </div>
                <ul id="sc-loc-selected-vehicles" class="sc-loc-list">
                    <!-- Her trækkes de over -->
                </ul>
            </div>
        </div>

        <h3><?php esc_html_e( '3. Download din tilpassede file', 'sc-localization' ); ?></h3>
        <div style="margin-top: 20px;">
            <button id="sc-loc-download-btn" class="button success"><?php
                echo esc_html_x( 'Download', 'Frontend download button', 'sc-localization' ); ?><br/>
                <strong><?php echo $global_ini_version ? '(' . esc_html( $global_ini_version ) . ')' : '(' . esc_html__( 'version ikke angivet', 'sc-localization' ) . ')';
                    ?></strong></button>
        </div>

        <div class="sc-loc-help" style="margin-top: 30px; border-left-color: #46b450;">
            <div>
                <h3><?php esc_html_e( '4. Installation af filen', 'sc-localization' ); ?></h3>
                <p><?php echo wp_kses_post( __( 'Når du har downloadet din <code>global.ini</code> fil, skal du følge disse trin for at aktivere den i Star Citizen:', 'sc-localization' ) ); ?></p>
                <ol>
                    <li><?php echo wp_kses_post( __( 'Gå til din Star Citizen installationsmappe<br/><small>(typisk <code>Program Files\Roberts Space Industries\StarCitizen\LIVE</code>).</small>', 'sc-localization' ) ); ?></li>
                    <li><?php echo wp_kses_post( __( 'Opret følgende mappestruktur hvis den ikke findes: <b><code>data\Localization\english</code></b>.', 'sc-localization' ) ); ?></li>
                    <li><?php echo wp_kses_post( __( 'Placer din downloadede <code>global.ini</code> fil i denne mappe.', 'sc-localization' ) ); ?></li>
                </ol>
            </div>
            <div>
                <h4><?php esc_html_e( 'Konfiguration', 'sc-localization' ); ?></h4>
                <p><?php echo wp_kses_post( __( 'For at spillet skal bruge filen, skal du sikre dig at <code>user.cfg</code> filen i <code>LIVE</code> mappen indeholder følgende linje:', 'sc-localization' ) ); ?></p>
                <pre style="background: #eee; padding: 5px; border-radius: 3px;">g_language = english</pre>
                <p>
                    <small><?php echo wp_kses_post( __( 'Hvis <code>user.cfg</code> ikke findes, skal du blot oprette en ny tekstfil med dette navn i <code>LIVE</code> mappen.', 'sc-localization' ) ); ?></small>
                </p>
            </div>
            <div>
                <h4><?php esc_html_e( 'Gem din opsætning!', 'sc-localization' ); ?></h4>
                <p>
                    <?php esc_html_e( 'Husk at gemme din opsætning til den næste patch, så slipper du for at lave dit format og sortere alle dine virtuelle rumskibe igen. Din gruppering og dine rettede navne bliver selvfølgelig også gemt.', 'sc-localization' ); ?>
                </p>
                <button id="sc-loc-save-btn"
                        class="button success"><?php echo wp_kses_post( __( 'Gem din opsætning<br/>til den næste patch', 'sc-localization' ) ); ?></button>
            </div>
        </div>
        <p>
            <small><?php printf( esc_html__( 'Made by %s, tested by %s and %s.', 'sc-localization' ), '<a href="https://robertsspaceindustries.com/citizens/DK-Raven" target="_blank">DK-Raven</a>', '<a href="https://robertsspaceindustries.com/citizens/Rimlee" target="_blank">Rimlee</a>', '<a href="https://robertsspaceindustries.com/citizens/PacManiacDK" target="_blank">PacManiacDK</a>' ); ?></small><br/>
            <small><?php printf( esc_html__( 'Credit to %s for inspiration and the hard work to classify all components in 4.6.0.', 'sc-localization' ), '<a href="https://github.com/ExoAE/ScCompLangPack/">ExoAE</a>' ); ?></small>
        </p>
    </div>
    <?php
    return ob_get_clean();
}