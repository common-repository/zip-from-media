<?php
/**
 * ZIP from Media
 *
 * @package    ZIP from Media
 * @subpackage ZipFromMediaAdmin Management screen
	Copyright (c) 2020- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
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

$zipfrommediaadmin = new ZipFromMediaAdmin();

/** ==================================================
 * Management screen
 */
class ZipFromMediaAdmin {

	/** ==================================================
	 * Path
	 *
	 * @var $upload_dir  upload_dir.
	 */
	private $upload_dir;

	/** ==================================================
	 * Path
	 *
	 * @var $upload_url  upload_url.
	 */
	private $upload_url;

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		$wp_uploads         = wp_upload_dir();
		$relation_path_true = strpos( $wp_uploads['baseurl'], '../' );
		if ( $relation_path_true > 0 ) {
			$basepath   = substr( $wp_uploads['baseurl'], 0, $relation_path_true );
			$upload_url = $this->realurl( $basepath, $relationalpath );
			$upload_dir = wp_normalize_path( realpath( $wp_uploads['basedir'] ) );
		} else {
			$upload_url = $wp_uploads['baseurl'];
			$upload_dir = wp_normalize_path( $wp_uploads['basedir'] );
		}
		if ( is_ssl() ) {
			$upload_url = str_replace( 'http:', 'https:', $upload_url );
		}
		$upload_dir = untrailingslashit( $upload_dir );
		$upload_url = untrailingslashit( $upload_url );
		$this->upload_dir = trailingslashit( $upload_dir );
		$this->upload_url = trailingslashit( $upload_url );

		add_action( 'init', array( $this, 'register_settings' ) );

		add_action( 'admin_menu', array( $this, 'add_pages' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'generate_notice' ) );
	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'zip-from-media/zipfrommedia.php';
		}
		if ( $file == $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'admin.php?page=zipfrommedia' ) . '">ZIP from Media</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=zipfrommedia-generate-zip' ) . '">' . __( 'Generate ZIP', 'zip-from-media' ) . '</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=zipfrommedia-settings' ) . '">' . __( 'Settings' ) . '</a>';
		}
		return $links;
	}

	/** ==================================================
	 * Add page
	 *
	 * @since 1.0
	 */
	public function add_pages() {
		add_menu_page(
			'ZIP from Media',
			'ZIP from Media',
			'upload_files',
			'zipfrommedia',
			array( $this, 'manage_page' ),
			'dashicons-download'
		);
		add_submenu_page(
			'zipfrommedia',
			__( 'Generate ZIP', 'zip-from-media' ),
			__( 'Generate ZIP', 'zip-from-media' ),
			'upload_files',
			'zipfrommedia-generate-zip',
			array( $this, 'generate_zip_page' )
		);
		add_submenu_page(
			'zipfrommedia',
			__( 'Settings' ),
			__( 'Settings' ),
			'upload_files',
			'zipfrommedia-settings',
			array( $this, 'settings_page' )
		);
	}

	/** ==================================================
	 * Generate ZIP
	 *
	 * @since 1.00
	 */
	public function generate_zip_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$scriptname = admin_url( 'admin.php?page=zipfrommedia-generate-zip' );
		$zipfrommedia_settings = get_user_option( 'zipfrommedia', get_current_user_id() );

		$filename = $this->upload_dir . $zipfrommedia_settings['name'] . '_' . get_current_user_id() . '.zip';
		$fileurl  = $this->upload_url . $zipfrommedia_settings['name'] . '_' . get_current_user_id() . '.zip';
		if ( isset( $_POST['Czip'] ) && ! empty( $_POST['Czip'] ) ) {
			if ( check_admin_referer( 'zm_file_zip', 'zipfrommedia_file_zip' ) ) {
				if ( ! get_user_option( 'zipfrommedia_submit_time', get_current_user_id() ) ) {
					$user = wp_get_current_user();
					if ( function_exists( 'wp_date' ) ) {
						$now_date_time = wp_date( 'Y-m-d H:i:s' );
					} else {
						$now_date_time = date_i18n( 'Y-m-d H:i:s' );
					}
					update_user_option( $user->ID, 'zipfrommedia_submit_time', $now_date_time );
					if ( ! wp_next_scheduled( 'zipfrommedia_compress_hook', array( $filename, $fileurl, $user->ID, $user->user_email, $now_date_time, $this->upload_dir ) ) ) {
						wp_schedule_single_event( time(), 'zipfrommedia_compress_hook', array( $filename, $fileurl, $user->ID, $user->user_email, $now_date_time, $this->upload_dir ) );
						echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( $now_date_time ) . ' : ' . esc_html__( 'Generation of ZIP from Media Library in the background has started. You will be notified by email at the end.', 'zip-from-media' ) . '</li></ul></div>';
					}
				} else {
					echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html( get_user_option( 'zipfrommedia_submit_time', get_current_user_id() ) ) . ' : ' . esc_html__( 'Currently, there is a process that is being compressed.', 'zip-from-media' ) . '</li></ul></div>';
				}
			}
		}
		if ( isset( $_POST['Dzip'] ) && ! empty( $_POST['Dzip'] ) ) {
			if ( check_admin_referer( 'zm_file_zip', 'zipfrommedia_file_zip' ) ) {
				if ( ! empty( $_POST['delete_file'] ) ) {
					$delete_file = sanitize_text_field( wp_unslash( $_POST['delete_file'] ) );
					if ( file_exists( $delete_file ) ) {
						wp_delete_file( $delete_file );
					}
				}
			}
		}

		?>
		<div class="wrap">

		<h2>ZIP from Media <a href="<?php echo esc_url( admin_url( 'admin.php?page=zipfrommedia-generate-zip' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Generate ZIP', 'zip-from-media' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=zipfrommedia-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
			<?php
			if ( class_exists( 'MediaFromZip' ) ) {
				$mediafromzip_url = admin_url( 'admin.php?page=mediafromzip' );
			} elseif ( is_multisite() ) {
					$mediafromzip_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=media-from-zip' );
			} else {
				$mediafromzip_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=media-from-zip' );
			}
			?>
			<a href="<?php echo esc_url( $mediafromzip_url ); ?>" class="page-title-action">Media from ZIP</a>
		</h2>
		<div style="clear: both;"></div>

		<h3><?php esc_html_e( 'Compress', 'zip-from-media' ); ?></h3>
		<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
		<div style="margin: 5px; padding: 5px;">
			<p class="description">
			<?php esc_html_e( 'Compresses the original files, excluding the thumbnails and metadata of the media library, into ZIP files while maintaining the folder hierarchy.', 'zip-from-media' ); ?>
			</p>
			<?php wp_nonce_field( 'zm_file_zip', 'zipfrommedia_file_zip' ); ?>
			<?php submit_button( __( 'Compress to ZIP', 'zip-from-media' ), 'large', 'Czip', true ); ?>
		</div>
		<?php
		if ( file_exists( $filename ) ) {
			$zip_time = wp_date( 'Y-m-d H:i:s', filemtime( $filename ) );
			$zip_byte = filesize( $filename );
			$zip_size = size_format( $zip_byte, 2 );
			?>
			<h3><?php esc_html_e( 'Download' ); ?></h3>
			<div style="margin: 5px; padding: 5px;">
			<p class="description">
			<?php
			/* translators: %1$s: Date time %2$s File size */
			echo esc_html( sprintf( __( 'The file created on %1$s can be downloaded from the following. The file size is %2$s.', 'zip-from-media' ), $zip_time, $zip_size ) );
			?>
			</p>
			<p class="submit">
			<button type="button" class="button button-large" onclick="location.href='<?php echo esc_url( $fileurl ); ?>'"><?php esc_html_e( 'Download ZIP', 'zip-from-media' ); ?></button>
			&nbsp;
			<input type="hidden" name="delete_file" value="<?php echo esc_attr( $filename ); ?>">
			<?php submit_button( __( 'Delete' ), 'large', 'Dzip', false ); ?>
			</p>
			</div>
			<?php
		}
		?>
		</form>

		</div>

		<?php
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function settings_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->options_updated();

		$zipfrommedia_settings = get_user_option( 'zipfrommedia', get_current_user_id() );

		$def_max_execution_time = ini_get( 'max_execution_time' );
		$def_memory_limit = ini_get( 'memory_limit' );
		$scriptname = admin_url( 'admin.php?page=zipfrommedia-settings' );

		?>
		<div class="wrap">

		<h2>ZIP from Media <a href="<?php echo esc_url( admin_url( 'admin.php?page=zipfrommedia-settings' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Settings' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=zipfrommedia-generate-zip' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Generate ZIP', 'zip-from-media' ); ?></a>
			<?php
			if ( class_exists( 'MediaFromZip' ) ) {
				$mediafromzip_url = admin_url( 'admin.php?page=mediafromzip' );
			} elseif ( is_multisite() ) {
					$mediafromzip_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=media-from-zip' );
			} else {
				$mediafromzip_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=media-from-zip' );
			}
			?>
			<a href="<?php echo esc_url( $mediafromzip_url ); ?>" class="page-title-action">Media from ZIP</a>
		</h2>
		<div style="clear: both;"></div>

			<div class="wrap">
				<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
				<?php wp_nonce_field( 'zfm_settings', 'zipfrommedia_settings' ); ?>
				<h3><?php esc_html_e( 'File', 'zip-from-media' ); ?></h3>
				<div style="display: block;padding:5px 5px">
					<p class="description">
					<?php
					/* translators: %1$s: upload_path */
					echo wp_kses_post( sprintf( __( 'Output the following files to the %1$s directory. If you enter an invalid string, it will be automatically sanitized.', 'zip-from-media' ), str_replace( ABSPATH, '', untrailingslashit( $this->upload_dir ) ) ) );
					?>
					</p>
					<div>
					<?php esc_html_e( 'File name:' ); ?>
					<input type="text" name="zipfrommedia_name" value="<?php echo ( esc_attr( $zipfrommedia_settings['name'] ) ); ?>">
					_<?php echo( esc_html( get_current_user_id() ) ); ?>.zip
					</div>
					<?php
					if ( current_user_can( 'manage_options' ) ) {
						?>
						<div>
						<input type="checkbox" name="zipfrommedia_all_user" value="1" <?php checked( '1', $zipfrommedia_settings['all_user'] ); ?> /> <?php esc_html_e( 'All users', 'zip-from-media' ); ?>
						</div>
						<?php
					}
					?>
				</div>

				<h3><?php esc_html_e( 'Execution time', 'zip-from-media' ); ?></h3>
				<div style="display:block; padding:5px 5px">
					<?php
					$max_execution_time = $zipfrommedia_settings['max_execution_time'];
					if ( ! @set_time_limit( $max_execution_time ) ) {
						$limit_seconds_html = '<font color="red">' . $def_max_execution_time . __( 'seconds', 'zip-from-media' ) . '</font>';
						?>
						<p class="description">
						<?php
						/* translators: %1$s: limit max execution time */
						echo wp_kses_post( sprintf( __( 'Execution time for this server is fixed at %1$s. If this limit is exceeded, times out. No email is sent.', 'zip-from-media' ), $limit_seconds_html ) );
						?>
						</p>
						<input type="hidden" name="zipfrommedia_max_execution_time" value="<?php echo esc_attr( $def_max_execution_time ); ?>" />
						<?php
					} else {
						$max_execution_time_text = __( 'The number of seconds a script is allowed to run.', 'zip-from-media' ) . '(' . __( 'The max_execution_time value defined in the php.ini.', 'zip-from-media' ) . '[<font color="red">' . $def_max_execution_time . '</font>])';
						?>
						<p class="description">
						<?php esc_html_e( 'This is to suppress timeouts when there are too many medias. If you do not receive the processing completion notification email, increase the number of seconds.', 'zip-from-media' ); ?>
						</p>
						<p class="description">
						<?php echo wp_kses_post( $max_execution_time_text ); ?>:<input type="number" step="1" min="1" max="9999" style="width: 80px;" name="zipfrommedia_max_execution_time" value="<?php echo esc_attr( $max_execution_time ); ?>" />
						</p>
						<?php
					}
					?>
				</div>
				<?php submit_button( __( 'Save Changes' ), 'large', 'zip-from-media-settings-options-apply', true ); ?>
				</form>
			</div>

		</div>
		<?php
	}

	/** ==================================================
	 * Main
	 *
	 * @since 1.00
	 */
	public function manage_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		?>
		<div class="wrap">

		<h2>ZIP from Media
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=zipfrommedia-generate-zip' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Generate ZIP', 'zip-from-media' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=zipfrommedia-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
			<?php
			if ( class_exists( 'MediaFromZip' ) ) {
				$mediafromzip_url = admin_url( 'admin.php?page=mediafromzip' );
			} elseif ( is_multisite() ) {
					$mediafromzip_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=media-from-zip' );
			} else {
				$mediafromzip_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=media-from-zip' );
			}
			?>
			<a href="<?php echo esc_url( $mediafromzip_url ); ?>" class="page-title-action">Media from ZIP</a>
		</h2>
		<div style="clear: both;"></div>

		<h3><?php esc_html_e( 'Extract from ZIP archive to Media Library.', 'zip-from-media' ); ?></h3>

		<?php $this->credit(); ?>

		</div>
		<?php
	}

	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( esc_html__( 'https://wordpress.org/plugins/%s/faq', 'zip-from-media' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = sprintf( esc_html__( 'https://shop.riverforest-wp.info/donate/', 'zip-from-media' ), $slug );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank" rel="noopener noreferrer">FAQ</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank" rel="noopener noreferrer">Support Forums</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank" rel="noopener noreferrer">Reviews</a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank" rel="noopener noreferrer">
		<?php
		/* translators: Plugin translation link */
		echo esc_html( sprintf( __( 'Translations for %s' ), $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php sprintf( esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'zip-from-media' ) ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;' ); ?></button>
		</div>

		<?php
	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @since 1.00
	 */
	private function options_updated() {

		if ( isset( $_POST['zip-from-media-settings-options-apply'] ) && ! empty( $_POST['zip-from-media-settings-options-apply'] ) ) {
			if ( check_admin_referer( 'zfm_settings', 'zipfrommedia_settings' ) ) {
				$zipfrommedia_settings = get_user_option( 'zipfrommedia', get_current_user_id() );
				if ( ! empty( $_POST['zipfrommedia_name'] ) ) {
					$zipfrommedia_settings['name'] = sanitize_file_name( wp_unslash( $_POST['zipfrommedia_name'] ) );
				}
				if ( ! empty( $_POST['zipfrommedia_all_user'] ) ) {
					$zipfrommedia_settings['all_user'] = true;
				} else {
					$zipfrommedia_settings['all_user'] = false;
				}
				if ( ! empty( $_POST['zipfrommedia_max_execution_time'] ) ) {
					$zipfrommedia_settings['max_execution_time'] = intval( $_POST['zipfrommedia_max_execution_time'] );
				}
				update_user_option( get_current_user_id(), 'zipfrommedia', $zipfrommedia_settings );
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Settings' ) . ' --> ' . __( 'Changes saved.' ) ) . '</li></ul></div>';
			}
		}
	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 1.00
	 */
	public function register_settings() {

		if ( ! get_user_option( 'zipfrommedia', get_current_user_id() ) ) {
			$zipfrommedia_tbl = array(
				'name' => 'upload_files',
				'all_user' => false,
				'max_execution_time' => 600,
			);
			update_user_option( get_current_user_id(), 'zipfrommedia', $zipfrommedia_tbl );
		}
	}

	/** ==================================================
	 * Real Url
	 *
	 * @param  string $base  base.
	 * @param  string $relationalpath relationalpath.
	 * @return string $realurl realurl.
	 * @since  1.00
	 */
	private function realurl( $base, $relationalpath ) {

		$parse = array(
			'scheme'   => null,
			'user'     => null,
			'pass'     => null,
			'host'     => null,
			'port'     => null,
			'query'    => null,
			'fragment' => null,
		);
		$parse = wp_parse_url( $base );

		if ( strpos( $parse['path'], '/', ( strlen( $parse['path'] ) - 1 ) ) !== false ) {
			$parse['path'] .= '.';
		}

		if ( preg_match( '#^https?://#', $relationalpath ) ) {
			return $relationalpath;
		} elseif ( preg_match( '#^/.*$#', $relationalpath ) ) {
			return $parse['scheme'] . '://' . $parse['host'] . $relationalpath;
		} else {
			$base_path = explode( '/', dirname( $parse['path'] ) );
			$rel_path  = explode( '/', $relationalpath );
			foreach ( $rel_path as $rel_dir_name ) {
				if ( '.' === $rel_dir_name ) {
					array_shift( $base_path );
					array_unshift( $base_path, '' );
				} elseif ( '..' === $rel_dir_name ) {
					array_pop( $base_path );
					if ( count( $base_path ) === 0 ) {
						$base_path = array( '' );
					}
				} else {
					array_push( $base_path, $rel_dir_name );
				}
			}
			$path = implode( '/', $base_path );
			return $parse['scheme'] . '://' . $parse['host'] . $path;
		}
	}

	/** ==================================================
	 * Generate notice
	 *
	 * @since 1.00
	 */
	public function generate_notice() {

		if ( get_user_option( 'zipfrommedia_submit_time', get_current_user_id() ) ) {
			$post_time = get_user_option( 'zipfrommedia_submit_time', get_current_user_id() );
			if ( ! empty( $post_time ) ) {
				if ( get_user_option( 'zipfrommedia_generate_mail_' . $post_time, get_current_user_id() ) ) {
					$zipfrommedia_mail_send = get_user_option( 'zipfrommedia_generate_mail_' . $post_time, get_current_user_id() );
					if ( 0 < $zipfrommedia_mail_send['count'] ) {
						$zipfrommedia_settings = get_user_option( 'zipfrommedia', get_current_user_id() );
						?>
						<div class="notice notice-success is-dismissible"><ul><li><strong>ZIP from Media</strong>
						<?php
						/* translators: %1$s Date Time, %2$d File Count */
						echo wp_kses_post( sprintf( __( ' : %1$s : %2$d files have been compressed into %3$s. Details have been sent by e-mail.', 'zip-from-media' ), $post_time, $zipfrommedia_mail_send['count'], $zipfrommedia_settings['name'] . '_' . get_current_user_id() . '.zip' ) );
						?>
						</li></ul></div>
						<?php
					}
					wp_clear_scheduled_hook( 'zipfrommedia_check_zip_generate', array( get_current_user_id(), $post_time ) );
					delete_user_option( get_current_user_id(), 'zipfrommedia_generate_mail_' . $post_time );
				}
				if ( get_user_option( 'zipfrommedia_stop_' . $post_time, get_current_user_id() ) ) {
					?>
					<div class="notice notice-error is-dismissible"><ul><li><strong>ZIP from Media</strong>
					<?php
					/* translators: %1$s Zip Name */
					echo wp_kses_post( sprintf( __( ' : %1$s : Processing may have been interrupted. Please increase "Execution time" and register again.', 'zip-from-media' ), $post_time ) );
					?>
					</div>
					<?php
					delete_user_option( get_current_user_id(), 'zipfrommedia_stop_' . $post_time );
				}
			}
			delete_user_option( get_current_user_id(), 'zipfrommedia_submit_time' );
		}
	}
}


