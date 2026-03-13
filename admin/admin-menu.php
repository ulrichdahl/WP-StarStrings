<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'sc_loc_add_admin_menu' );

function sc_loc_add_admin_menu() {
	add_menu_page(
		'SC Localization',
		'SC Localization',
		'manage_options',
		'sc-localization',
		'sc_loc_admin_page',
		'dashicons-translation',
		26
	);
}

function sc_loc_admin_page() {
	if ( isset( $_POST['sc_loc_upload_nonce'] ) && wp_verify_nonce( $_POST['sc_loc_upload_nonce'], 'sc_loc_upload_action' ) ) {
		sc_loc_handle_uploads();
		echo '<div class="updated"><p>Filer uploadet korrekt.</p></div>';
	}
	if (file_exists(SC_LOC_UPLOAD_DIR . '/counter')) {
		$counter = (int)file_get_contents(SC_LOC_UPLOAD_DIR . '/counter');
	}
	else {
		$counter = 0;
	}
	exec('ls -l '.SC_LOC_UPLOAD_DIR . '/', $out);
	echo '<pre>'.implode('<br/>', $out).'</pre>';
	?>
	<div class="wrap">
		<h1>Star Citizen Localization Indstillinger</h1>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'sc_loc_upload_action', 'sc_loc_upload_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="global_ini">Global.ini (Hovedfil) i flere filer (max størrelse <?php echo ini_get('upload_max_filesize');?>Bytes)</label></th>
					<td>
						<input type="file" name="global_ini" id="global_ini" accept=".zip">
						<input type="file" name="global_ini_1" id="global_ini_1" accept=".z01">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="components_ini">Components.ini</label></th>
					<td><input type="file" name="components_ini" id="components_ini" accept=".ini"></td>
				</tr>
				<tr>
					<th scope="row"><label for="vehicles_ini">Vehicles.ini</label></th>
					<td><input type="file" name="vehicles_ini" id="vehicles_ini" accept=".ini"></td>
				</tr>
			</table>
			<?php submit_button( 'Upload Filer' ); ?>
		</form>

		<hr>
		<h2>Nuværende Status</h2>
		<ul>
			<li>Downloads: <?php echo $counter;?></li>
			<?php foreach(['global.ini', 'components.ini', 'vehicles.ini'] as $file) { ?>
				<li><?php echo $file ?>: <?php echo file_exists( SC_LOC_UPLOAD_DIR . '/' . $file ) ? '<span style="color:green;">Uploadet</span> <a href="'.wp_upload_dir()["baseurl"] . '/sc-localization/' . $file.'" target="_blank">Download</a>' : '<span style="color:red;">Mangler</span>'; ?></li>
			<?php } // endforeach ?>
		</ul>
	</div>
	<?php
	exec('rm -f '.SC_LOC_UPLOAD_DIR . '/*.z* '.SC_LOC_UPLOAD_DIR . '/z*');
}

function sc_loc_handle_uploads() {
	//echo '<pre>'.var_export($_FILES,1).'</pre>';
	$files = array( 'global_ini', 'global_ini_1', 'components_ini', 'vehicles_ini' );
	foreach ( $files as $file_key ) {
		if ( ! empty( $_FILES[ $file_key ]['tmp_name'] ) ) {
			$destination = SC_LOC_UPLOAD_DIR . '/';
			if ($file_key === 'global_ini_1') {
				$destination .= 'global_ini.z01';
			}
			elseif ($file_key === 'global_ini') {
				$destination .= 'global_ini.zip';
			}
			else {
				$destination .= str_replace( '_', '.', $file_key );
			}
			move_uploaded_file( $_FILES[ $file_key ]['tmp_name'], $destination );
		}
	}
	if (!empty($_FILES['global_ini_1']['tmp_name'])) {
		$out = [];
		exec('mv '.SC_LOC_UPLOAD_DIR . '/global.ini '.SC_LOC_UPLOAD_DIR . '/global-old.ini', $out);
		exec('zip -F '.SC_LOC_UPLOAD_DIR . '/global_ini.zip --out '.SC_LOC_UPLOAD_DIR . '/temp.zip', $out);
		exec('unzip '.SC_LOC_UPLOAD_DIR . '/temp.zip -d ' . SC_LOC_UPLOAD_DIR, $out);
		exec('ls -l '.SC_LOC_UPLOAD_DIR . '/', $out);
		echo '<pre>'.implode('<br/>', $out).'</pre>';
		unlink(SC_LOC_UPLOAD_DIR . '/temp.zip');
		unlink(SC_LOC_UPLOAD_DIR . '/global_ini.zip');
		unlink(SC_LOC_UPLOAD_DIR . '/global_ini.z01');
	}
	elseif (!empty($_FILES['global_ini']['tmp_name'])) {
		$out = [];
		exec('unzip '.SC_LOC_UPLOAD_DIR . '/global_ini.zip -d ' . SC_LOC_UPLOAD_DIR, $out);
		//exec('ls -l '.SC_LOC_UPLOAD_DIR . '/', $out);
		//echo '<pre>'.implode('<br/>', $out).'</pre>';
		unlink(SC_LOC_UPLOAD_DIR . '/global_ini.zip');
	}
}
