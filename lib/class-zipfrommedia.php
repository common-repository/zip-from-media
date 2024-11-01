<?php
/**
 * ZIP from Media
 *
 * @package    ZIP from Media
 * @subpackage ZipFromMedia Main function
/*  Copyright (c) 2020- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$zipfrommedia = new ZipFromMedia();

/** ==================================================
 * Class Main function
 *
 * @since 1.00
 */
class ZipFromMedia {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'zipfrommedia_compress_hook', array( $this, 'compress' ), 10, 6 );
		add_action( 'zipfrommedia_check_zip_generate', array( $this, 'check_generate' ), 10, 2 );
	}

	/** ==================================================
	 * Check Zip Generate
	 *
	 * @param int    $uid  uid.
	 * @param string $now_date_time  now_date_time.
	 * @since 1.00
	 */
	public function check_generate( $uid, $now_date_time ) {

		if ( ! get_user_option( 'zipfrommedia_generate_mail_' . $now_date_time, $uid ) ) {
			update_user_option( $uid, 'zipfrommedia_stop_' . $now_date_time, true );
		}
	}

	/** ==================================================
	 * Compress
	 *
	 * @param string $zip_file  zip_file.
	 * @param string $zip_url  zip_url.
	 * @param int    $uid  uid.
	 * @param string $to  mail address.
	 * @param string $now_date_time  now_date_time.
	 * @param string $upload_dir  upload_dir.
	 * @since 1.00
	 */
	public function compress( $zip_file, $zip_url, $uid, $to, $now_date_time, $upload_dir ) {

		$zipfrommedia_settings = get_user_option( 'zipfrommedia', $uid );

		$max_exe_time = $zipfrommedia_settings['max_execution_time'];
		$def_max_execution_time = ini_get( 'max_execution_time' );
		$limit_seconds_html = '<font color="red">' . $def_max_execution_time . __( 'seconds', 'zip-from-media' ) . '</font>';
		if ( ! @set_time_limit( $max_exe_time ) ) {
			/* translators: %1$s: limit max execution time */
			echo '<div class="notice notice-info is-dismissible"><ul><li>' . wp_kses_post( sprintf( __( 'Execution time for this server is fixed at %1$s. If this limit is exceeded, times out. No email is sent.', 'zip-from-media' ), $limit_seconds_html ) ) . '</li></ul></div>';
			$max_exe_time = $def_max_execution_time;
		}

		wp_schedule_single_event( time() + $max_exe_time + 30, 'zipfrommedia_check_zip_generate', array( $uid, $now_date_time ) );

		/* translators: Date and Time */
		$message = sprintf( __( 'ZIP from Media : %s', 'zip-from-media' ), $now_date_time ) . "\r\n\r\n";
		/* translators: File url */
		$message .= sprintf( __( 'Download : %s', 'zip-from-media' ), $zip_url ) . "\r\n\r\n";

		/* Scan files */
		global $wpdb;
		if ( $zipfrommedia_settings['all_user'] ) {
			$post_ids = $wpdb->get_col(
				"
				SELECT	ID
				FROM	{$wpdb->prefix}posts
				WHERE	post_type = 'attachment'
						AND post_status = 'inherit'
				"
			);
		} else {
			$post_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT	ID
					FROM	{$wpdb->prefix}posts
					WHERE	post_type = 'attachment'
							AND post_author = %d
							AND post_status = 'inherit'
					",
					$uid
				)
			);
		}

		if ( ! empty( $post_ids ) ) {
			$pathmedia = array();
			foreach ( $post_ids as $attach_id ) {
				$metadata = wp_get_attachment_metadata( $attach_id );
				if ( ! empty( $metadata ) ) {
					if ( ! empty( $metadata['original_image'] ) ) {
						$pathmedia[] = wp_normalize_path( wp_get_original_image_path( $attach_id, false ) );
					} else {
						$pathmedia[] = wp_normalize_path( get_attached_file( $attach_id, false ) );
					}
				} else {
					$pathmedia[] = wp_normalize_path( get_attached_file( $attach_id, false ) );
				}
			}
		}

		$count = 0;
		if ( ! empty( $pathmedia ) ) {
			/* zip object */
			$objzip = new zipArchive();
			/* zip open */
			$result = $objzip->open( $zip_file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE );
			if ( true !== $result ) {
				?>
				<div class="notice notice-error is-dismissible"><ul><li><?php esc_html_e( 'The ZIP file cannot be opened.', 'zip-from-media' ); ?></li></ul></div>
				<?php
				exit;
			}
			/* add zip */
			foreach ( $pathmedia as $filepath ) {
				$filename = wp_basename( $filepath );
				$upload_path = str_replace( $upload_dir, '', $filepath );
				$objzip->addFile( $filepath, $upload_path );
				$message .= $upload_path . "\n";
				$count++;
			}
			$objzip->close();
			$message .= "\r\n\r\n";
			/* translators: zipname for message */
			$message .= sprintf( __( '[%d] media files is compressed.', 'zip-from-media' ), $count ) . "\r\n\r\n";
		}

		$zipfrommedia_mail_send = array();
		$zipfrommedia_mail_send['count'] = $count;
		update_user_option( $uid, 'zipfrommedia_generate_mail_' . $now_date_time, $zipfrommedia_mail_send );

		/* translators: blogname for subject */
		$subject = sprintf( __( '[%1$s] Compressed [%2$d] media files', 'zip-from-media' ), get_option( 'blogname' ), $count );
		$message .= "\r\n\r\n";

		wp_mail( $to, $subject, $message );
	}
}


