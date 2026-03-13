<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'sc_localization_frontend', 'sc_loc_frontend_shortcode' );

function sc_loc_frontend_shortcode() {
	if ( ! file_exists( SC_LOC_UPLOAD_DIR . '/components.ini' ) || ! file_exists( SC_LOC_UPLOAD_DIR . '/vehicles.ini' ) ) {
		return '<p>Vent venligst indtil administrator har uploadet de nødvendige filer.</p>';
	}

	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'jquery-ui-draggable' );
	wp_enqueue_style( 'sc-loc-frontend-css', SC_LOC_URL . 'assets/css/frontend.css', [], (string)time());
	wp_enqueue_script( 'sc-loc-frontend-js', SC_LOC_URL . 'assets/js/frontend.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable' ), (string)time(), true );

	$vehicles = sc_loc_parse_ini( SC_LOC_UPLOAD_DIR . '/vehicles.ini' );

	// Send data til JS
	wp_localize_script( 'sc-loc-frontend-js', 'scLocData', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'sc_loc_download_nonce' )
	) );

	ob_start();
	?>
	<div class="sc-loc-frontend">
		<h3>1. Definer Komponent Format</h3>
		<div class="sc-loc-help">
			<div>
				<p>Byg dit format ved at trække elementerne (chips) herunder ned i det blå område.<br/>Du kan også klikke på knapperne for at tilføje mellemrum eller bindestreger.</p>
				<p><small>Tip: Dobbeltklik på et element i det blå felt for at fjerne det igen.</small></p>
				<p><small>Indlæs din opsætning fra sidste patch:<br/><input type="file" id="sc-loc-load-btn" style="display:none" accept=".json">
						<button class="button success" onclick="document.getElementById('sc-loc-load-btn').click()">Indlæs din opsætning</button></small></p>
			</div>
			<div>
				<ul>
					<li><strong>Type:</strong> Komponentens type (f.eks. COOL eller C for Cooler).</li>
					<li><strong>Klassificering:</strong> MIL (Military), STL (Stealth), CIV (Civilian), CMP (Competition).</li>
					<li><strong>Størrelse:</strong> S1, S2, S3 osv.</li>
					<li><strong>Kvalitet:</strong> A, B, C, D.</li>
					<li><strong>Navn:</strong> Komponentens faktiske navn.</li>
				</ul>
			</div>
		</div>

		<div id="sc-loc-format-builder" class="sc-loc-drop-zone">
			<div class="chip" data-type="type_long" title="F.eks. COOL, POWR">Type (4 tegn)</div>
			<div class="chip" data-type="type_short" title="F.eks. C, P">Type (1 tegn)</div>
			<div class="chip" data-type="class_long" title="F.eks. MIL, STL">Klassificering (3 tegn)</div>
			<div class="chip" data-type="class_short" title="F.eks. M, S">Klassificering (1 tegn)</div>
			<div class="chip" data-type="size" title="F.eks. S1, S2">Størrelse</div>
			<div class="chip" data-type="grade" title="F.eks. A, B">Kvalitet</div>
			<div class="chip" data-type="name" title="Selve navnet på komponenten">Navn</div>
		</div>

		<div class="sc-loc-separator-controls">
			<span>Tilføj adskiller:</span>
			<button type="button" class="add-sep" data-sep=" ">Mellemrum [ ]</button>
			<button type="button" class="add-sep" data-sep="-">Bindestreg [-]</button>
			<button type="button" class="add-sep" data-sep="_">Understreg [_]</button>
			<button type="button" class="add-sep" data-sep=".">Punktum [.]</button>
			<button type="button" id="sc-loc-custom-sep">Eget tegn...</button>
		</div>
		<div class="sc-loc-active-format-container">
			<div id="sc-loc-active-format" class="sc-loc-drop-zone active-format" style="flex-grow:1">
				<!-- Her trækkes de ned -->
			</div>
			<button id="sc-loc-clear-format">
				Ryd og start forfra
			</button>
		</div>

		<div class="sc-loc-active-format-container">
			<span>Eksempel på komponent navn i spillet:</span>
			<div id="sc-loc-format-example">
				<!-- generate an example here -->
			</div>
		</div>

		<input type="hidden" id="sc-loc-format-input" name="sc_loc_format" value="">

		<h3>2. Vælg og Sorter Fartøjer</h3>
		<div class="sc-loc-help">
			<div>
				<p>Find de fartøjer (skibe, biler, motorcykler) du vil have med i din oversættelse. Træk dem fra venstre til højre kolonne.</p>
			</div>
			<div>
				<p><small>Tip: Du kan trække fartøjer tilbage til den tilgængelige liste for at fjerne dem, eller trykke på "Ryd listen" for at nulstille.</small></p>
				<p><small>Bonustip: Du kan ændre navnet på dine favorit fartøjer, ved at klikke på det lille rediger ikon i den sorterede liste.</small></p>
			</div>
		</div>
		<div class="sc-loc-columns">
			<div class="sc-loc-col">
				<h4>Tilgængelige Fartøjer</h4>
				<div class="sc-loc-search-container">
					<input type="text" id="sc-loc-vehicle-search" placeholder="Søg efter fartøj...">
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
				<h4>Valgte Fartøjer (Sorteret rækkefølge)</h4>
				<div class="sc-loc-search-container">
					<input type="button" id="sc-loc-vehicle-clear" value="Ryd listen">
				</div>
				<ul id="sc-loc-selected-vehicles" class="sc-loc-list">
					<!-- Her trækkes de over -->
				</ul>
			</div>
		</div>

		<h3>3. Download din tilpassede file</h3>
		<div style="margin-top: 20px;">
			           <button id="sc-loc-download-btn" class="button success">Download<br/><STRONG>(4.7.0-11445650)</STRONG></button>
		</div>

		<h3>4. Installation af filen</h3>
		<div class="sc-loc-help" style="margin-top: 30px; border-left-color: #46b450;">
			<div>
				<p>Når du har downloadet din <code>global.ini</code> fil, skal du følge disse trin for at aktivere den i Star Citizen:</p>
				<ol>
					<li>Gå til din Star Citizen installationsmappe<br/><small>(typisk <code>Program Files\Roberts Space Industries\StarCitizen\LIVE</code>).</small></li>
					<li>Opret følgende mappestruktur hvis den ikke findes: <b><code>data\Localization\english</code></b>.</li>
					<li>Placer din downloadede <code>global.ini</code> fil i denne mappe.</li>
				</ol>
			</div>
			<div>
				<h4>Konfiguration</h4>
				<p>For at spillet skal bruge filen, skal du sikre dig at <code>user.cfg</code> filen i <code>LIVE</code> mappen indeholder følgende linje:</p>
				<pre style="background: #eee; padding: 5px; border-radius: 3px;">g_language = english</pre>
				<p><small>Hvis <code>user.cfg</code> ikke findes, skal du blot oprette en ny tekstfil med dette navn i <code>LIVE</code> mappen.</small></p>
			</div>
			<div>
				<h4>Opsætning!</h4>
				<p>
					Husk at gemme din opsætning til den næste patch, så slipper du for at lave dit format og sortere alle din virtuelle rumskibe igen.
				</p>
				<button id="sc-loc-save-btn" class="button success">Gem din opsætning<br/>til den næste patch</button>
			</div>
		</div>
		<p>
			<small>Made by <a href="https://robertsspaceindustries.com/citizens/DK-Raven" target="_blank">DK-Raven</a>, tested by <a href="https://robertsspaceindustries.com/citizens/Rimlee" target="_blank">Rimlee</a> and <a href="https://robertsspaceindustries.com/citizens/PacManiacDK" target="_blank">PacManiacDK</a>.</small><br/>
			<small>Credit to <a href="https://github.com/ExoAE/ScCompLangPack/">ExoAE</a> for inspiration and the hard work to classify all components in 4.6.0.</small>
		</p>
	</div>
	<?php
	return ob_get_clean();
}
