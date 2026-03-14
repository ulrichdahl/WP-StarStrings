<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Læser en .ini fil og returnerer et array af nøgle => værdi par.
 * Understøtter det specifikke format: variabel=værdi
 */
function sc_loc_parse_ini( $filepath ) {
	$data = array();
	if ( ! file_exists( $filepath ) ) {
		return $data;
	}

	$lines = file( $filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	foreach ( $lines as $line ) {
		if ( strpos( $line, '=' ) !== false ) {
			list( $key, $value ) = explode( '=', $line, 2 );
			$data[ trim( $key ) ] = trim( $value );
		}
	}

	return $data;
}

/**
 * Parser komponent tekst for at udtrække info.
 * Format: "MIL-2C Shroud"
 */
function sc_loc_parse_component_value( $value ) {
	// Forventer format som: MIL-1C "Suldrath" eller CIV-3C "FrostBurn"
	// Vi splitter på mellemrum
	$parts = explode( ' ', $value, 2 );
	$info  = $parts[0]; // F.eks. MIL-1C
	$name  = isset( $parts[1] ) ? trim( $parts[1], '"' ) : '';

	// Split info (MIL-1C)
	// MIL er de første 3 tegn
	$class_long  = substr( $info, 0, 3 );
	$class_short = substr( $info, 0, 1 );

	// Størrelse og kvalitet (typisk -1C eller lignende)
	$size  = '';
	$grade = '';
	if ( preg_match( '/-(\d)([A-Z])/', $info, $matches ) ) {
		$size  = $matches[1];
		$grade = $matches[2];
	}

	return array(
		'class_long'  => $class_long,
		'class_short' => $class_short,
		'size'        => $size,
		'grade'       => $grade,
		'name'        => $name
	);
}

/**
 * Parser komponent variabel for at udtrække type.
 * Format: item_NameCOOL_...
 */
function sc_loc_parse_component_type( $key ) {
	$types = array(
		'COOL' => array( 'long' => 'COOL', 'short' => 'C' ),
		'POWR' => array( 'long' => 'POWR', 'short' => 'P' ),
		'QDRV' => array( 'long' => 'QDRV', 'short' => 'Q' ),
		'SHLD' => array( 'long' => 'SHLD', 'short' => 'S' ),
	);

	foreach ( $types as $k => $v ) {
		if ( strpos( $key, $k ) !== false ) {
			return $v;
		}
	}

	return array( 'long' => 'UNKN', 'short' => 'U' );
}

add_action( 'wp_ajax_sc_loc_download', 'sc_loc_handle_download' );
add_action( 'wp_ajax_nopriv_sc_loc_download', 'sc_loc_handle_download' );

function sc_loc_handle_download() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sc_loc_download_nonce' ) ) {
		wp_die( 'Sikkerhedsfejl' );
	}

	$format_json   = isset( $_POST['format'] ) ? stripslashes( $_POST['format'] ) : '[]';
	$vehicles_json = isset( $_POST['vehicles'] ) ? stripslashes( $_POST['vehicles'] ) : '[]';

	$format            = json_decode( $format_json, true );
	$selected_vehicles = json_decode( $vehicles_json, true );

	$global_path        = SC_LOC_UPLOAD_DIR . '/global.ini';
	$components_path    = SC_LOC_UPLOAD_DIR . '/components.ini';
	$vehicles_path      = SC_LOC_UPLOAD_DIR . '/vehicles.ini';
	$global_ini_version = trim( (string) get_option( 'sc_loc_global_ini_version', '' ) );
	$version_message = trim( (string) get_option( 'sc_loc_version_message', '' ) );

	if ( ! file_exists( $global_path ) ) {
		wp_die( 'global.ini mangler' );
	}

	$global_content  = file( $global_path, FILE_IGNORE_NEW_LINES );
	$components_data = sc_loc_parse_ini( $components_path );
	$vehicles_data   = sc_loc_parse_ini( $vehicles_path );

	// Forbered komponent erstatninger
	$replacements = array();
	foreach ( $components_data as $key => $raw_value ) {
		$comp_info = sc_loc_parse_component_value( $raw_value );
		$type_info = sc_loc_parse_component_type( $key );

		$new_value = '';
		foreach ( $format as $step ) {
			if ( $step['type'] === 'text' ) {
				$new_value .= $step['value'];
			} else {
				$var = $step['value'];
				if ( $var === 'type_long' ) {
					$new_value .= $type_info['long'];
				} elseif ( $var === 'type_short' ) {
					$new_value .= $type_info['short'];
				} elseif ( $var === 'class_long' ) {
					$new_value .= $comp_info['class_long'];
				} elseif ( $var === 'class_short' ) {
					$new_value .= $comp_info['class_short'];
				} elseif ( $var === 'size' ) {
					$new_value .= $comp_info['size'];
				} elseif ( $var === 'grade' ) {
					$new_value .= $comp_info['grade'];
				} elseif ( $var === 'name' ) {
					$new_value .= $comp_info['name'];
				}
			}
		}
		$replacements[ trim( $key ) ] = $new_value;
	}

	// Forbered vehicle erstatninger
	$count               = 0;
	$total_main_vehicles = 0;
	foreach ( $selected_vehicles as $v_item ) {
		if ( ! ( is_array( $v_item ) && ! empty( $v_item['is_nested'] ) ) ) {
			$total_main_vehicles ++;
		}
	}
	$use_padding   = $total_main_vehicles > 9;

	$postPrefix         = 0;
	foreach ( $selected_vehicles as $i => $v_item ) {
		$v_key       = is_array( $v_item ) ? $v_item['key'] : $v_item;
		$custom_name = is_array( $v_item ) ? $v_item['name'] : ( isset( $vehicles_data[ $v_key ] ) ? $vehicles_data[ $v_key ] : '' );
		$is_nested = is_array( $v_item ) && ! empty( $v_item['is_nested'] );
		$is_next_nested = is_array( $selected_vehicles[ $i + 1 ] ?? null ) && ! empty( $selected_vehicles[ $i + 1 ]['is_nested'] );

		if ( $v_key && ( isset( $vehicles_data[ $v_key ] ) || $custom_name ) ) {
			if ( ! $is_nested ) {
				$count ++;
			}
			$display_count = $count;
			if ( $use_padding && $count < 10 ) {
				$display_count = "0" . $count;
			}
			if ( $postPrefix === 0 && $is_next_nested ) {
				$postPrefix = 1;
			} elseif ( $is_nested ) {
				$postPrefix += 1;
			}
			if ( $postPrefix > 0 ) {
				$display_count .= chr( 96 + $postPrefix );
			}
			$prefix = $display_count . ". ";
			$replacements[ trim( $v_key ) ] = $prefix . $custom_name;
			if ( $postPrefix > 0 && ! $is_next_nested ) {
				$postPrefix = 0;
			}
		}
	}

	// Opdater global.ini indhold
	if ( $global_ini_version !== '' ) {
		$replacements['Frontend_PU_Version,P'] = $global_ini_version . ' ' . (!empty($version_message) ? $version_message . ' ' : '') . SC_LOC_MESSAGE;
	}

	$output = "";
	foreach ( $global_content as $line ) {
		// Fjern eksisterende BOM hvis den findes i starten af filen
		if ( $output === "" ) {
			$line = preg_replace( '/^\xEF\xBB\xBF/', '', $line );
		}

		if ( strpos( $line, '=' ) !== false ) {
			list( $key, $value ) = explode( '=', $line, 2 );
			$trimmed_key = trim( $key );
			if ( isset( $replacements[ $trimmed_key ] ) ) {
				$output .= $trimmed_key . "=" . trim( $replacements[ $trimmed_key ] ) . "\r\n";
			} else {
				$output .= $line . "\r\n";
			}
		} else {
			$output .= $line . "\r\n";
		}
	}

	if ( file_exists( SC_LOC_UPLOAD_DIR . '/counter' ) ) {
		$counter = (int) file_get_contents( SC_LOC_UPLOAD_DIR . '/counter' );
	} else {
		$counter = 0;
	}
	file_put_contents( SC_LOC_UPLOAD_DIR . '/counter', ++ $counter );
	// Send filen til download
	header( 'Content-Description: File Transfer' );
	header( 'Content-Type: text/plain' );
	header( 'Content-Disposition: attachment; filename="global.ini"' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate' );
	header( 'Pragma: public' );
	echo "\xEF\xBB\xBF"; // UTF-8 BOM hvis nødvendigt for global.ini
	echo $output;
	exit;
}