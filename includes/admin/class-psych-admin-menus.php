<?php
/**
 * Register all admin menus for the Psych Complete System plugin.
 *
 * @package Psych_Complete_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Psych_Admin_Menus {

    const ASSIGNMENTS_TABLE = 'psych_coach_assignments';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'wp_ajax_psych_get_sales_chart_data', [ $this, 'ajax_get_sales_chart_data' ] );
	}

	/**
	 * Register all the admin menus.
	 */
	public function register_menus() {
		// Main Menu Page
		add_menu_page(
			__( 'Psych System', 'psych-system' ),
			__( 'Psych System', 'psych-system' ),
			'manage_options',
			'psych-dashboard', // New parent slug
			[ $this, 'dashboard_page_callback' ],
			'dashicons-admin-generic',
			20
		);

		// Submenu: Dashboard (the new main page)
		add_submenu_page(
			'psych-dashboard',
			__( 'Dashboard', 'psych-system' ),
			__( 'Dashboard', 'psych-system' ),
			'manage_options',
			'psych-dashboard',
			[ $this, 'dashboard_page_callback' ]
		);

		// Submenu: Settings
		add_submenu_page(
			'psych-dashboard',
			__( 'Settings', 'psych-system' ),
			__( 'Settings', 'psych-system' ),
			'manage_options',
			'psych-system-settings',
			[ $this, 'settings_page_callback' ]
		);

		// Submenu: Modules
		add_submenu_page(
			'psych-dashboard',
			__( 'Modules', 'psych-system' ),
			__( 'Modules', 'psych-system' ),
			'manage_options',
			'psych-modules',
			[ $this, 'modules_page_callback' ]
		);

		// Submenu: Gamification
		add_submenu_page(
			'psych-dashboard',
			__( 'Gamification', 'psych-system' ),
			__( 'Gamification', 'psych-system' ),
			'manage_options',
			'psych-gamification-center',
			[ $this, 'gamification_page_callback' ]
		);

		// Sub-Submenu: Levels (under Gamification)
		add_submenu_page(
			'psych-gamification-center',
			__( 'Levels', 'psych-system' ),
			__( 'Levels', 'psych-system' ),
			'manage_options',
			'psych-levels',
			[ $this, 'levels_page_callback' ]
		);

		// Sub-Submenu: Badges (under Gamification)
		add_submenu_page(
			'psych-gamification-center',
			__( 'Badges', 'psych-system' ),
			__( 'Badges', 'psych-system' ),
			'manage_options',
			'psych-badges',
			[ $this, 'badges_page_callback' ]
		);

		// Sub-Submenu: Manual Award (under Gamification)
		add_submenu_page(
			'psych-gamification-center',
			__( 'Manual Award', 'psych-system' ),
			__( 'Manual Award', 'psych-system' ),
			'manage_options',
			'psych-manual-award',
			[ $this, 'manual_award_page_callback' ]
		);

        // Submenu: Coaches
		add_submenu_page(
			'psych-dashboard',
			__( 'Coaches', 'psych-system' ),
			__( 'Coaches', 'psych-system' ),
			'manage_options',
			'edit.php?post_type=psych_coach'
		);

		// Sub-Submenu: Manual Assignment (under Coaches)
		add_submenu_page(
			'edit.php?post_type=psych_coach',
			__( 'Manual Assignment', 'psych-system' ),
			__( 'Manual Assignment', 'psych-system' ),
			'manage_options',
			'psych-manual-assignment',
			[ $this, 'manual_assignment_page_callback' ]
		);

		// Submenu: Reports
		add_submenu_page(
			'psych-dashboard',
			__( 'Reports', 'psych-system' ),
			__( 'Reports', 'psych-system' ),
			'manage_options',
			'psych-reports',
			[ $this, 'reports_page_callback' ]
		);
	}

	public function dashboard_page_callback() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Psych System Dashboard', 'psych-system' ); ?></h1>
			<div id="dashboard-widgets-wrap">
				<div id="dashboard-widgets" class="metabox-holder">
					<div id="postbox-container-1" class="postbox-container">
						<div class="meta-box-sortables">
							<?php $this->render_kpi_widgets(); ?>
							<?php $this->render_quick_actions_widget(); ?>
						</div>
					</div>
					<div id="postbox-container-2" class="postbox-container">
						<div class="meta-box-sortables">
							<?php $this->render_activity_feed_widget(); ?>
						</div>
					</div>
					<div id="postbox-container-3" class="postbox-container">
						<div class="meta-box-sortables">
							<?php $this->render_system_health_widget(); ?>
						</div>
						<div class="meta-box-sortables">
							<div class="postbox">
								<div class="postbox-header">
									<h2 class="hndle">
										<span class="dashicons-before dashicons-chart-bar"></span>
										<?php esc_html_e( 'Sales Over Time', 'psych-system' ); ?>
									</h2>
								</div>
								<div class="inside">
									<canvas id="sales-over-time-chart"></canvas>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<script>
				jQuery(document).ready(function($) {
					// Sales Over Time Chart
					$.post(ajaxurl, { action: 'psych_get_sales_chart_data' }, function(response) {
						if (response.success) {
							var ctx = document.getElementById('sales-over-time-chart').getContext('2d');
							new Chart(ctx, {
								type: 'line',
								data: {
									labels: response.data.labels,
									datasets: [{
										label: 'Sales',
										data: response.data.values,
										borderColor: '#3498db',
										backgroundColor: 'rgba(52, 152, 219, 0.2)',
									}]
								}
							});
						}
					});
				});
			</script>
		</div>
		<?php
	}

	private function render_kpi_widgets() {
		$kpis = [
			'total_students' => [
				'title' => __( 'Total Students', 'psych-system' ),
				'value' => $this->get_total_students(),
				'icon' => 'dashicons-groups',
			],
			'monthly_revenue' => [
				'title' => __( 'Revenue (Last 30 Days)', 'psych-system' ),
				'value' => wc_price($this->get_monthly_revenue()),
				'icon' => 'dashicons-chart-area',
			],
			'new_users' => [
				'title' => __( 'New Users (Last 30 Days)', 'psych-system' ),
				'value' => $this->get_new_users_count(),
				'icon' => 'dashicons-admin-users',
			],
			'avg_progress' => [
				'title' => __( 'Average Course Progress', 'psych-system' ),
				'value' => $this->get_average_progress() . '%',
				'icon' => 'dashicons-performance',
			],
		];

		foreach ($kpis as $kpi) {
			?>
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle">
						<span class="dashicons-before <?php echo esc_attr($kpi['icon']); ?>"></span>
						<?php echo esc_html($kpi['title']); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="main">
						<p class="kpi-value"><?php echo wp_kses_post($kpi['value']); ?></p>
					</div>
				</div>
			</div>
			<?php
		}
	}

	private function get_total_students() {
		return count_users( [ 'role' => 'subscriber' ] )['total_users'];
	}

	private function get_monthly_revenue() {
		$args = [
			'status' => ['wc-completed', 'wc-processing'],
			'date_created' => '>=' . (time() - (30 * DAY_IN_SECONDS)),
			'return' => 'ids',
		];
		$orders = wc_get_orders( $args );
		$total = 0;
		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );
			$total += $order->get_total();
		}
		return $total;
	}

	private function get_new_users_count() {
		$args = [
			'date_query' => [
				[
					'after' => '30 days ago',
				],
			],
		];
		$user_query = new WP_User_Query( $args );
		return $user_query->get_total();
	}

	private function get_average_progress() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'psych_paths';
		$avg = $wpdb->get_var( "SELECT AVG(progress) FROM $table_name" );
		return round( $avg, 2 );
	}

	private function render_quick_actions_widget() {
		?>
		<div class="postbox">
			<div class="postbox-header">
				<h2 class="hndle">
					<span class="dashicons-before dashicons-controls-play"></span>
					<?php esc_html_e( 'Quick Actions', 'psych-system' ); ?>
				</h2>
			</div>
			<div class="inside">
				<div class="main">
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=psych_coach' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Coach', 'psych-system' ); ?></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=psych-manual-award' ) ); ?>" class="button"><?php esc_html_e( 'Manual Award', 'psych-system' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_system_health_widget() {
		$health_checks = [];
		$api_settings = get_option( 'psych_api_settings' );
		if ( empty( $api_settings['openai_key'] ) ) {
			$health_checks[] = [ 'message' => __( 'OpenAI API Key is not set.', 'psych-system' ), 'status' => 'error' ];
		}
		if ( empty( $api_settings['sms_api_key'] ) ) {
			$health_checks[] = [ 'message' => __( 'SMS Gateway API Key is not set.', 'psych-system' ), 'status' => 'warning' ];
		}

		// You can add more checks here, e.g., for missions pending approval
		?>
		<div class="postbox">
			<div class="postbox-header">
				<h2 class="hndle">
					<span class="dashicons-before dashicons-shield-alt"></span>
					<?php esc_html_e( 'System Health', 'psych-system' ); ?>
				</h2>
			</div>
			<div class="inside">
				<div class="main">
					<ul>
						<?php if ( ! empty( $health_checks ) ) : ?>
							<?php foreach ( $health_checks as $check ) : ?>
								<li class="<?php echo esc_attr( $check['status'] ); ?>"><?php echo esc_html( $check['message'] ); ?></li>
							<?php endforeach; ?>
						<?php else : ?>
							<li class="success"><?php esc_html_e( 'All systems nominal.', 'psych-system' ); ?></li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	public function ajax_get_sales_chart_data() {
		$args = [
			'status' => ['wc-completed', 'wc-processing'],
			'date_created' => '>=' . (time() - (30 * DAY_IN_SECONDS)),
		];
		$orders = wc_get_orders( $args );
		$sales_data = [];
		foreach ( $orders as $order ) {
			$date = $order->get_date_created()->format('Y-m-d');
			if ( ! isset( $sales_data[ $date ] ) ) {
				$sales_data[ $date ] = 0;
			}
			$sales_data[ $date ] += $order->get_total();
		}

		ksort( $sales_data );

		wp_send_json_success( [
			'labels' => array_keys( $sales_data ),
			'values' => array_values( $sales_data ),
		] );
	}

	private function render_activity_feed_widget() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'psych_activity_log';
		$activities = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10" );
		?>
		<div class="postbox">
			<div class="postbox-header">
				<h2 class="hndle">
					<span class="dashicons-before dashicons-list-view"></span>
					<?php esc_html_e( 'Recent Activity', 'psych-system' ); ?>
				</h2>
			</div>
			<div class="inside">
				<div class="main">
					<ul>
						<?php if ( ! empty( $activities ) ) : ?>
							<?php foreach ( $activities as $activity ) : ?>
								<li>
									<?php
									$user = get_userdata( $activity->user_id );
									echo esc_html( $user->display_name . ' ' . $activity->description );
									?>
									<small>(<?php echo human_time_diff( strtotime( $activity->created_at ), current_time( 'timestamp' ) ); ?> ago)</small>
								</li>
							<?php endforeach; ?>
						<?php else : ?>
							<li><?php esc_html_e( 'No recent activity.', 'psych-system' ); ?></li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	public function register_settings() {
		// General Settings
		register_setting( 'psych_general_settings', 'psych_general_settings' );

		// Gamification Settings
		register_setting( 'psych_gamification_settings', 'psych_gamification_settings' );

		// API Settings
		register_setting( 'psych_api_settings', 'psych_api_settings' );

		// Coach/Business Settings
		register_setting( 'psych_coach_settings', 'psych_coach_settings' );

		// Notifications Settings
		register_setting( 'psych_notification_settings', 'psych_notification_settings' );
	}

	/**
	 * Callback for the settings page.
	 */
	public function settings_page_callback() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Psych Complete System Settings', 'psych-system' ); ?></h1>
			<?php settings_errors(); ?>

			<?php
			$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
			?>

			<h2 class="nav-tab-wrapper">
				<a href="?page=psych-system-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'psych-system' ); ?></a>
				<a href="?page=psych-system-settings&tab=gamification" class="nav-tab <?php echo $active_tab === 'gamification' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Gamification', 'psych-system' ); ?></a>
				<a href="?page=psych-system-settings&tab=apis" class="nav-tab <?php echo $active_tab === 'apis' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'APIs', 'psych-system' ); ?></a>
				<a href="?page=psych-system-settings&tab=coach" class="nav-tab <?php echo $active_tab === 'coach' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Coach/Business', 'psych-system' ); ?></a>
				<a href="?page=psych-system-settings&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Notifications', 'psych-system' ); ?></a>
			</h2>

			<form method="post" action="options.php">
				<?php
				if ( $active_tab === 'general' ) {
					settings_fields( 'psych_general_settings' );
					do_settings_sections( 'psych_general_settings' );
					$this->render_general_settings();
				} elseif ( $active_tab === 'gamification' ) {
					settings_fields( 'psych_gamification_settings' );
					do_settings_sections( 'psych_gamification_settings' );
					$this->render_gamification_settings();
				} elseif ( $active_tab === 'apis' ) {
					settings_fields( 'psych_api_settings' );
					do_settings_sections( 'psych_api_settings' );
					$this->render_api_settings();
				} elseif ( $active_tab === 'coach' ) {
					settings_fields( 'psych_coach_settings' );
					do_settings_sections( 'psych_coach_settings' );
					$this->render_coach_settings();
				} elseif ( $active_tab === 'notifications' ) {
					settings_fields( 'psych_notification_settings' );
					do_settings_sections( 'psych_notification_settings' );
					$this->render_notification_settings();
				}

				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	private function render_general_settings() {
		$options = get_option( 'psych_general_settings' );
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Primary Color', 'psych-system' ); ?></th>
				<td><input type="text" name="psych_general_settings[primary_color]" value="<?php echo esc_attr( $options['primary_color'] ?? '#3498db' ); ?>" class="color-picker" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Enable SPA Mode', 'psych-system' ); ?></th>
				<td><input type="checkbox" name="psych_general_settings[spa_mode]" value="1" <?php checked( isset( $options['spa_mode'] ) ? $options['spa_mode'] : 0, 1 ); ?> /></td>
			</tr>
		</table>
		<?php
	}

	private function render_gamification_settings() {
		$options = get_option( 'psych_gamification_settings' );
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Points for Mission Completion', 'psych-system' ); ?></th>
				<td><input type="number" name="psych_gamification_settings[mission_points]" value="<?php echo esc_attr( $options['mission_points'] ?? 10 ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Points for Daily Login', 'psych-system' ); ?></th>
				<td><input type="number" name="psych_gamification_settings[login_points]" value="<?php echo esc_attr( $options['login_points'] ?? 5 ); ?>" /></td>
			</tr>
		</table>
		<?php
	}

	private function render_api_settings() {
		$options = get_option( 'psych_api_settings' );
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'OpenAI API Key', 'psych-system' ); ?></th>
				<td><input type="text" name="psych_api_settings[openai_key]" value="<?php echo esc_attr( $options['openai_key'] ?? '' ); ?>" class="regular-text" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'SMS Gateway API Key', 'psych-system' ); ?></th>
				<td><input type="text" name="psych_api_settings[sms_api_key]" value="<?php echo esc_attr( $options['sms_api_key'] ?? '' ); ?>" class="regular-text" /></td>
			</tr>
		</table>
		<?php
	}

	private function render_coach_settings() {
		$options = get_option( 'psych_coach_settings' );
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Default Commission Rate (%)', 'psych-system' ); ?></th>
				<td><input type="number" name="psych_coach_settings[commission_rate]" value="<?php echo esc_attr( $options['commission_rate'] ?? 10 ); ?>" /></td>
			</tr>
		</table>
		<?php
	}

	private function render_notification_settings() {
		$options = get_option( 'psych_notification_settings' );
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Enable Email Notifications', 'psych-system' ); ?></th>
				<td><input type="checkbox" name="psych_notification_settings[email_enabled]" value="1" <?php checked( isset( $options['email_enabled'] ) ? $options['email_enabled'] : 0, 1 ); ?> /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Enable SMS Notifications', 'psych-system' ); ?></th>
				<td><input type="checkbox" name="psych_notification_settings[sms_enabled]" value="1" <?php checked( isset( $options['sms_enabled'] ) ? $options['sms_enabled'] : 0, 1 ); ?> /></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Callback for the modules page.
	 */
	public function modules_page_callback() {
		if ( isset( $_POST['psych_modules_submit'] ) && check_admin_referer( 'psych_modules_nonce' ) ) {
			$this->save_module_settings();
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Module settings saved!', 'psych-system' ) . '</p></div>';
		}

		$modules = $this->get_available_modules();
		$active_modules = get_option( 'psych_active_modules', [] );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Module Management', 'psych-system' ); ?></h1>
			<p><?php echo esc_html__( 'Activate or deactivate modules for the Psych Complete System.', 'psych-system' ); ?></p>
			<form method="post" action="">
				<input type="hidden" name="psych_modules_submit" value="1" />
				<?php wp_nonce_field( 'psych_modules_nonce' ); ?>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Module', 'psych-system' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Description', 'psych-system' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'psych-system' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $modules as $slug => $module ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $module['name'] ); ?></strong></td>
								<td><?php echo esc_html( $module['description'] ); ?></td>
								<td>
									<label class="switch">
										<input type="checkbox" name="active_modules[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $active_modules ), true ); ?>>
										<span class="slider round"></span>
									</label>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<style>
			.switch { position: relative; display: inline-block; width: 60px; height: 34px; }
			.switch input { opacity: 0; width: 0; height: 0; }
			.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; }
			.slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; }
			input:checked + .slider { background-color: #2196F3; }
			input:focus + .slider { box-shadow: 0 0 1px #2196F3; }
			input:checked + .slider:before { transform: translateX(26px); }
			.slider.round { border-radius: 34px; }
			.slider.round:before { border-radius: 50%; }
		</style>
		<?php
	}

	private function get_available_modules() {
		return [
			'gamification' => [
				'name' => __( 'Gamification', 'psych-system' ),
				'description' => __( 'Engage users with points, badges, and levels.', 'psych-system' ),
			],
			'coaching' => [
				'name' => __( 'Coaching', 'psych-system' ),
				'description' => __( 'Manage coaches and their students.', 'psych-system' ),
			],
			'assessments' => [
				'name' => __( 'Assessments', 'psych-system' ),
				'description' => __( 'Create and manage assessments and quizzes.', 'psych-system' ),
			],
			'reporting' => [
				'name' => __( 'Reporting', 'psych-system' ),
				'description' => __( 'View detailed reports on user progress and sales.', 'psych-system' ),
			],
		];
	}

	private function save_module_settings() {
		$active_modules = isset( $_POST['active_modules'] ) ? array_map( 'sanitize_key', $_POST['active_modules'] ) : [];
		update_option( 'psych_active_modules', $active_modules );
	}

    /**
	 * Callback for the gamification page.
	 */
	public function gamification_page_callback() {
		echo '<h1>' . esc_html__( 'Gamification Center', 'psych-system' ) . '</h1>';
	}

	/**
	 * Callback for the levels page.
	 */
	public function levels_page_callback() {
		echo '<h1>' . esc_html__( 'Levels', 'psych-system' ) . '</h1>';
	}

	/**
	 * Callback for the badges page.
	 */
	public function badges_page_callback() {
		echo '<h1>' . esc_html__( 'Badges', 'psych-system' ) . '</h1>';
	}

	/**
	 * Callback for the manual award page.
	 */
	public function manual_award_page_callback() {
		echo '<h1>' . esc_html__( 'Manual Award Points/Badges', 'psych-system' ) . '</h1>';
	}

	/**
	 * Callback for the reports page.
	 */
	public function reports_page_callback() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Reports', 'psych-system' ); ?></h1>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<div class="postbox">
								<h2><?php esc_html_e( 'Sales by Coach', 'psych-system' ); ?></h2>
								<div class="inside">
									<?php $this->render_sales_by_coach_report(); ?>
								</div>
							</div>
						</div>
					</div>
					<div id="postbox-container-1" class="postbox-container">
						<div class="meta-box-sortables">
							<div class="postbox">
								<h2><?php esc_html_e( 'Commission Report', 'psych-system' ); ?></h2>
								<div class="inside">
									<?php $this->render_commission_report(); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_sales_by_coach_report() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::ASSIGNMENTS_TABLE;
		$results = $wpdb->get_results( "
			SELECT coach_id, COUNT(id) as sales, SUM(p.meta_value) as total_value
			FROM $table_name a
			JOIN {$wpdb->postmeta} p ON a.product_id = p.post_id AND p.meta_key = '_price'
			GROUP BY coach_id
		" );

		?>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Coach', 'psych-system' ); ?></th>
					<th><?php esc_html_e( 'Sales', 'psych-system' ); ?></th>
					<th><?php esc_html_e( 'Total Value', 'psych-system' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $results ) ) : ?>
					<?php foreach ( $results as $result ) : ?>
						<?php $coach = get_post( $result->coach_id ); ?>
						<tr>
							<td><?php echo esc_html( $coach->post_title ); ?></td>
							<td><?php echo esc_html( $result->sales ); ?></td>
							<td><?php echo wc_price( $result->total_value ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="3"><?php esc_html_e( 'No sales data found.', 'psych-system' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_commission_report() {
		$options = get_option( 'psych_coach_settings' );
		$commission_rate = isset( $options['commission_rate'] ) ? $options['commission_rate'] : 10;

		global $wpdb;
		$table_name = $wpdb->prefix . self::ASSIGNMENTS_TABLE;
		$results = $wpdb->get_results( "
			SELECT coach_id, SUM(p.meta_value) as total_value
			FROM $table_name a
			JOIN {$wpdb->postmeta} p ON a.product_id = p.post_id AND p.meta_key = '_price'
			GROUP BY coach_id
		" );

		?>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Coach', 'psych-system' ); ?></th>
					<th><?php esc_html_e( 'Total Sales', 'psych-system' ); ?></th>
					<th><?php esc_html_e( 'Commission', 'psych-system' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $results ) ) : ?>
					<?php foreach ( $results as $result ) : ?>
						<?php $coach = get_post( $result->coach_id ); ?>
						<tr>
							<td><?php echo esc_html( $coach->post_title ); ?></td>
							<td><?php echo wc_price( $result->total_value ); ?></td>
							<td><?php echo wc_price( $result->total_value * ( $commission_rate / 100 ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="3"><?php esc_html_e( 'No sales data found.', 'psych-system' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Callback for the manual assignment page.
	 */
	public function manual_assignment_page_callback() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Manual Student Assignment', 'psych-system' ); ?></h1>
			<p><?php esc_html_e( 'Manually assign a student to a coach for a specific product.', 'psych-system' ); ?></p>
			<form method="post" action="">
				<input type="hidden" name="psych_manual_assignment_submit" value="1" />
				<?php wp_nonce_field( 'psych_manual_assignment_nonce' ); ?>

				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Student', 'psych-system' ); ?></th>
						<td>
							<?php
							wp_dropdown_users( [
								'name'             => 'student_id',
								'id'               => 'student_id',
								'show_option_none' => __( 'Select a Student', 'psych-system' ),
							] );
							?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Coach', 'psych-system' ); ?></th>
						<td>
							<select name="coach_id" id="coach_id">
								<option value=""><?php esc_html_e( 'Select a Coach', 'psych-system' ); ?></option>
								<?php
								$coaches = get_posts( [ 'post_type' => 'psych_coach', 'numberposts' => -1 ] );
								foreach ( $coaches as $coach ) {
									echo '<option value="' . esc_attr( $coach->ID ) . '">' . esc_html( $coach->post_title ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Product', 'psych-system' ); ?></th>
						<td>
							<select name="product_id" id="product_id">
								<option value=""><?php esc_html_e( 'Select a Product', 'psych-system' ); ?></option>
								<?php
								$products = wc_get_products( [ 'limit' => -1, 'status' => 'publish' ] );
								foreach ( $products as $product ) {
									echo '<option value="' . esc_attr( $product->get_id() ) . '">' . esc_html( $product->get_name() ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Assign Student', 'psych-system' ) ); ?>
			</form>
		</div>
		<?php

		if ( isset( $_POST['psych_manual_assignment_submit'] ) && check_admin_referer( 'psych_manual_assignment_nonce' ) ) {
			$this->handle_manual_assignment();
		}
	}

	private function handle_manual_assignment() {
		$student_id = isset( $_POST['student_id'] ) ? intval( $_POST['student_id'] ) : 0;
		$coach_id   = isset( $_POST['coach_id'] ) ? intval( $_POST['coach_id'] ) : 0;
		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

		if ( $student_id <= 0 || $coach_id <= 0 || $product_id <= 0 ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid data.', 'psych-system' ) . '</p></div>';
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'psych_coach_assignments';

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table_name WHERE student_id = %d AND product_id = %d",
			$student_id, $product_id
		) );

		if ( $existing ) {
			$wpdb->update(
				$table_name,
				[ 'coach_id' => $coach_id, 'status' => 'active', 'assigned_at' => current_time( 'mysql' ) ],
				[ 'id' => $existing ]
			);
		} else {
			$wpdb->insert( $table_name, [
				'student_id'  => $student_id,
				'coach_id'    => $coach_id,
				'product_id'  => $product_id,
				'assigned_at' => current_time( 'mysql' ),
				'status'      => 'active',
			] );
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Student assigned successfully!', 'psych-system' ) . '</p></div>';
	}
}

new Psych_Admin_Menus();
