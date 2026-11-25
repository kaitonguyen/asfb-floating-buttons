<?php
/**
 * Plugin Name:       ASFB Floating Buttons
 * Plugin URI:        https://nguyenhieuky.com
 * Description:       Thêm các nút bấm hành động (Gọi, Zalo, Messenger, Lên đầu trang) cố định trên màn hình với chế độ thu gọn, nhãn gợi ý và tùy biến linh hoạt.
 * Version:           2.2.0
 * Author:            Ky Nguyen
 * Author URI:        https://nguyenhieuky.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       asfb-floating-buttons
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Tested up to:      7.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ASFB_FB_VERSION', '2.2.0' );
define( 'ASFB_FB_TEXT_DOMAIN', 'asfb-floating-buttons' );

/**
 * Return default plugin options.
 *
 * @return array
 */
function asfb_fb_get_default_options() {
	return array(
		'enable'             => 0,
		'position'           => 'right',
		'button_size'        => 'md',
		'offset_bottom'      => 20,
		'offset_side'        => 20,
		'device_visibility'  => 'all',
		'hide_checkout_cart' => 0,
		'ripple_effect'      => 1,
		'collapsible'        => 0,
		'show_labels'        => 0,
		'track_analytics'    => 0,
		'enable_phone'       => 0,
		'phone_number'       => '',
		'phone_label'        => 'Gọi ngay',
		'phone_bg_color'     => '#4CAF50',
		'enable_zalo'        => 0,
		'zalo_link'          => '',
		'zalo_label'         => 'Chat Zalo',
		'zalo_bg_color'      => '#0068FF',
		'enable_messenger'   => 0,
		'messenger_link'     => '',
		'messenger_label'    => 'Chat Messenger',
		'messenger_bg_color' => '#0084FF',
		'enable_scrolltop'   => 1,
		'scrolltop_bg_color' => '#18181B',
	);
}

/**
 * Get merged plugin options.
 *
 * @return array
 */
function asfb_fb_get_options() {
	$saved = get_option( 'asfb_fb_options', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, asfb_fb_get_default_options() );
}

/**
 * Add "Cài đặt" link on Plugins screen.
 *
 * @param array $links Existing action links.
 * @return array
 */
function asfb_fb_add_settings_link( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=asfb-floating-buttons' ) ) . '">' . esc_html__( 'Cài đặt', 'asfb-floating-buttons' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'asfb_fb_add_settings_link' );

/**
 * Register Settings Page in WordPress Admin.
 */
function asfb_fb_add_admin_menu() {
	add_options_page(
		__( 'ASFB Floating Buttons', 'asfb-floating-buttons' ),
		__( 'ASFB Floating Buttons', 'asfb-floating-buttons' ),
		'manage_options',
		'asfb-floating-buttons',
		'asfb_fb_options_page_html'
	);
}
add_action( 'admin_menu', 'asfb_fb_add_admin_menu' );

/**
 * Sanitize plugin settings.
 *
 * @param array $input Raw form input.
 * @return array
 */
function asfb_fb_options_sanitize( $input ) {
	$defaults  = asfb_fb_get_default_options();
	$new_input = array();

	if ( ! is_array( $input ) ) {
		return $defaults;
	}

	$checkboxes = array(
		'enable',
		'hide_checkout_cart',
		'ripple_effect',
		'collapsible',
		'show_labels',
		'track_analytics',
		'enable_phone',
		'enable_zalo',
		'enable_messenger',
		'enable_scrolltop',
	);
	foreach ( $checkboxes as $key ) {
		$new_input[ $key ] = ( isset( $input[ $key ] ) && '1' === (string) $input[ $key ] ) ? 1 : 0;
	}

	$new_input['position'] = ( isset( $input['position'] ) && in_array( $input['position'], array( 'left', 'right' ), true ) )
		? $input['position']
		: $defaults['position'];

	$new_input['button_size'] = ( isset( $input['button_size'] ) && in_array( $input['button_size'], array( 'sm', 'md', 'lg' ), true ) )
		? $input['button_size']
		: $defaults['button_size'];

	$new_input['device_visibility'] = ( isset( $input['device_visibility'] ) && in_array( $input['device_visibility'], array( 'all', 'desktop', 'mobile' ), true ) )
		? $input['device_visibility']
		: $defaults['device_visibility'];

	$new_input['offset_bottom'] = isset( $input['offset_bottom'] )
		? max( 8, min( 160, absint( $input['offset_bottom'] ) ) )
		: $defaults['offset_bottom'];

	$new_input['offset_side'] = isset( $input['offset_side'] )
		? max( 8, min( 120, absint( $input['offset_side'] ) ) )
		: $defaults['offset_side'];

	$new_input['phone_number'] = isset( $input['phone_number'] )
		? preg_replace( '/[^0-9\+]/', '', $input['phone_number'] )
		: '';

	$new_input['zalo_link']      = isset( $input['zalo_link'] ) ? esc_url_raw( trim( $input['zalo_link'] ) ) : '';
	$new_input['messenger_link'] = isset( $input['messenger_link'] ) ? esc_url_raw( trim( $input['messenger_link'] ) ) : '';

	$labels = array( 'phone_label', 'zalo_label', 'messenger_label' );
	foreach ( $labels as $key ) {
		$new_input[ $key ] = isset( $input[ $key ] )
			? sanitize_text_field( $input[ $key ] )
			: $defaults[ $key ];
	}

	$colors = array( 'phone_bg_color', 'zalo_bg_color', 'messenger_bg_color', 'scrolltop_bg_color' );
	foreach ( $colors as $key ) {
		if ( isset( $input[ $key ] ) && preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $input[ $key ] ) ) {
			$new_input[ $key ] = $input[ $key ];
		} else {
			$new_input[ $key ] = $defaults[ $key ];
		}
	}

	return $new_input;
}

/**
 * Register Settings API sections & fields.
 */
function asfb_fb_settings_init() {
	register_setting(
		'asfb_fb_settings_group',
		'asfb_fb_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'asfb_fb_options_sanitize',
			'default'           => asfb_fb_get_default_options(),
		)
	);

	// General section.
	add_settings_section( 'asfb_fb_section_general', __( 'Cài đặt chung & Hiển thị', 'asfb-floating-buttons' ), null, 'asfb-floating-buttons' );
	add_settings_field( 'asfb_fb_enable', __( 'Bật/Tắt Plugin', 'asfb-floating-buttons' ), 'asfb_fb_field_enable_html', 'asfb-floating-buttons', 'asfb_fb_section_general' );
	add_settings_field( 'asfb_fb_position', __( 'Vị trí hiển thị', 'asfb-floating-buttons' ), 'asfb_fb_field_position_html', 'asfb-floating-buttons', 'asfb_fb_section_general' );
	add_settings_field( 'asfb_fb_button_size', __( 'Kích thước nút', 'asfb-floating-buttons' ), 'asfb_fb_field_button_size_html', 'asfb-floating-buttons', 'asfb_fb_section_general' );
	add_settings_field( 'asfb_fb_offsets', __( 'Khoảng cách lề (px)', 'asfb-floating-buttons' ), 'asfb_fb_field_offsets_html', 'asfb-floating-buttons', 'asfb_fb_section_general' );
	add_settings_field( 'asfb_fb_device_visibility', __( 'Hiển thị trên thiết bị', 'asfb-floating-buttons' ), 'asfb_fb_field_device_visibility_html', 'asfb-floating-buttons', 'asfb_fb_section_general' );
	add_settings_field( 'asfb_fb_hide_checkout_cart', __( 'Ẩn tại Giỏ hàng / Thanh toán', 'asfb-floating-buttons' ), 'asfb_fb_field_hide_checkout_cart_html', 'asfb-floating-buttons', 'asfb_fb_section_general' );
	add_settings_field( 'asfb_fb_ripple', __( 'Hiệu ứng sóng', 'asfb-floating-buttons' ), 'asfb_fb_field_ripple_html', 'asfb-floating-buttons', 'asfb_fb_section_general' );
	add_settings_field( 'asfb_fb_collapsible', __( 'Chế độ thu gọn (Speed Dial)', 'asfb-floating-buttons' ), 'asfb_fb_field_collapsible_html', 'asfb-floating-buttons', 'asfb_fb_section_general' );
	add_settings_field( 'asfb_fb_show_labels', __( 'Hiện nhãn gợi ý (Tooltip)', 'asfb-floating-buttons' ), 'asfb_fb_field_show_labels_html', 'asfb-floating-buttons', 'asfb_fb_section_general' );
	add_settings_field( 'asfb_fb_track_analytics', __( 'Đo lường GA4 / Meta Pixel', 'asfb-floating-buttons' ), 'asfb_fb_field_track_analytics_html', 'asfb-floating-buttons', 'asfb_fb_section_general' );

	// Phone section.
	add_settings_section( 'asfb_fb_section_phone', __( 'Nút Gọi điện', 'asfb-floating-buttons' ), null, 'asfb-floating-buttons' );
	add_settings_field( 'asfb_fb_enable_phone', __( 'Hiển thị nút Gọi điện', 'asfb-floating-buttons' ), 'asfb_fb_field_enable_phone_html', 'asfb-floating-buttons', 'asfb_fb_section_phone' );
	add_settings_field( 'asfb_fb_phone_number', __( 'Số điện thoại', 'asfb-floating-buttons' ), 'asfb_fb_field_phone_number_html', 'asfb-floating-buttons', 'asfb_fb_section_phone' );
	add_settings_field( 'asfb_fb_phone_label', __( 'Nhãn hiển thị', 'asfb-floating-buttons' ), 'asfb_fb_field_phone_label_html', 'asfb-floating-buttons', 'asfb_fb_section_phone' );
	add_settings_field( 'asfb_fb_phone_color', __( 'Màu nền', 'asfb-floating-buttons' ), 'asfb_fb_field_phone_bg_color_html', 'asfb-floating-buttons', 'asfb_fb_section_phone' );

	// Zalo section.
	add_settings_section( 'asfb_fb_section_zalo', __( 'Nút Zalo', 'asfb-floating-buttons' ), null, 'asfb-floating-buttons' );
	add_settings_field( 'asfb_fb_enable_zalo', __( 'Hiển thị nút Zalo', 'asfb-floating-buttons' ), 'asfb_fb_field_enable_zalo_html', 'asfb-floating-buttons', 'asfb_fb_section_zalo' );
	add_settings_field( 'asfb_fb_zalo_link', __( 'Link Zalo', 'asfb-floating-buttons' ), 'asfb_fb_field_zalo_link_html', 'asfb-floating-buttons', 'asfb_fb_section_zalo' );
	add_settings_field( 'asfb_fb_zalo_label', __( 'Nhãn hiển thị', 'asfb-floating-buttons' ), 'asfb_fb_field_zalo_label_html', 'asfb-floating-buttons', 'asfb_fb_section_zalo' );
	add_settings_field( 'asfb_fb_zalo_bg_color', __( 'Màu nền', 'asfb-floating-buttons' ), 'asfb_fb_field_zalo_bg_color_html', 'asfb-floating-buttons', 'asfb_fb_section_zalo' );

	// Messenger section.
	add_settings_section( 'asfb_fb_section_messenger', __( 'Nút Messenger', 'asfb-floating-buttons' ), null, 'asfb-floating-buttons' );
	add_settings_field( 'asfb_fb_enable_messenger', __( 'Hiển thị nút Messenger', 'asfb-floating-buttons' ), 'asfb_fb_field_enable_messenger_html', 'asfb-floating-buttons', 'asfb_fb_section_messenger' );
	add_settings_field( 'asfb_fb_messenger_link', __( 'Link Messenger', 'asfb-floating-buttons' ), 'asfb_fb_field_messenger_link_html', 'asfb-floating-buttons', 'asfb_fb_section_messenger' );
	add_settings_field( 'asfb_fb_messenger_label', __( 'Nhãn hiển thị', 'asfb-floating-buttons' ), 'asfb_fb_field_messenger_label_html', 'asfb-floating-buttons', 'asfb_fb_section_messenger' );
	add_settings_field( 'asfb_fb_messenger_bg_color', __( 'Màu nền', 'asfb-floating-buttons' ), 'asfb_fb_field_messenger_bg_color_html', 'asfb-floating-buttons', 'asfb_fb_section_messenger' );

	// Scroll to top section.
	add_settings_section( 'asfb_fb_section_scroll', __( 'Nút Lên đầu trang', 'asfb-floating-buttons' ), null, 'asfb-floating-buttons' );
	add_settings_field( 'asfb_fb_enable_scrolltop', __( 'Hiển thị nút Lên đầu trang', 'asfb-floating-buttons' ), 'asfb_fb_field_enable_scrolltop_html', 'asfb-floating-buttons', 'asfb_fb_section_scroll' );
	add_settings_field( 'asfb_fb_scrolltop_bg_color', __( 'Màu nền', 'asfb-floating-buttons' ), 'asfb_fb_field_scrolltop_bg_color_html', 'asfb-floating-buttons', 'asfb_fb_section_scroll' );
}
add_action( 'admin_init', 'asfb_fb_settings_init' );

/* --- Field Callbacks --- */
function asfb_fb_field_enable_html() {
	$options = asfb_fb_get_options();
	?>
	<label><input type="checkbox" name="asfb_fb_options[enable]" value="1" <?php checked( $options['enable'], 1 ); ?> /> <em><?php esc_html_e( 'Chọn để kích hoạt hiển thị các nút trên website.', 'asfb-floating-buttons' ); ?></em></label>
	<?php
}

function asfb_fb_field_position_html() {
	$options = asfb_fb_get_options();
	?>
	<label><input type="radio" name="asfb_fb_options[position]" value="right" <?php checked( $options['position'], 'right' ); ?> /> <?php esc_html_e( 'Góc phải dưới', 'asfb-floating-buttons' ); ?></label><br>
	<label><input type="radio" name="asfb_fb_options[position]" value="left" <?php checked( $options['position'], 'left' ); ?> /> <?php esc_html_e( 'Góc trái dưới', 'asfb-floating-buttons' ); ?></label>
	<?php
}

function asfb_fb_field_button_size_html() {
	$options = asfb_fb_get_options();
	?>
	<select name="asfb_fb_options[button_size]">
		<option value="sm" <?php selected( $options['button_size'], 'sm' ); ?>><?php esc_html_e( 'Nhỏ (44px)', 'asfb-floating-buttons' ); ?></option>
		<option value="md" <?php selected( $options['button_size'], 'md' ); ?>><?php esc_html_e( 'Tiêu chuẩn (50px)', 'asfb-floating-buttons' ); ?></option>
		<option value="lg" <?php selected( $options['button_size'], 'lg' ); ?>><?php esc_html_e( 'Lớn (56px)', 'asfb-floating-buttons' ); ?></option>
	</select>
	<?php
}

function asfb_fb_field_offsets_html() {
	$options = asfb_fb_get_options();
	?>
	<label><?php esc_html_e( 'Cách đáy:', 'asfb-floating-buttons' ); ?> <input type="number" min="8" max="160" name="asfb_fb_options[offset_bottom]" value="<?php echo esc_attr( $options['offset_bottom'] ); ?>" style="width:70px;" /> px</label>
	&nbsp;&nbsp;
	<label><?php esc_html_e( 'Cách mép ngang:', 'asfb-floating-buttons' ); ?> <input type="number" min="8" max="120" name="asfb_fb_options[offset_side]" value="<?php echo esc_attr( $options['offset_side'] ); ?>" style="width:70px;" /> px</label>
	<?php
}

function asfb_fb_field_device_visibility_html() {
	$options = asfb_fb_get_options();
	?>
	<select name="asfb_fb_options[device_visibility]">
		<option value="all" <?php selected( $options['device_visibility'], 'all' ); ?>><?php esc_html_e( 'Tất cả thiết bị (Desktop & Mobile)', 'asfb-floating-buttons' ); ?></option>
		<option value="desktop" <?php selected( $options['device_visibility'], 'desktop' ); ?>><?php esc_html_e( 'Chỉ Desktop (>= 768px)', 'asfb-floating-buttons' ); ?></option>
		<option value="mobile" <?php selected( $options['device_visibility'], 'mobile' ); ?>><?php esc_html_e( 'Chỉ Mobile (< 768px)', 'asfb-floating-buttons' ); ?></option>
	</select>
	<?php
}

function asfb_fb_field_hide_checkout_cart_html() {
	$options = asfb_fb_get_options();
	?>
	<label><input type="checkbox" name="asfb_fb_options[hide_checkout_cart]" value="1" <?php checked( $options['hide_checkout_cart'], 1 ); ?> /> <em><?php esc_html_e( 'Ẩn nút nổi trên trang Giỏ hàng và Thanh toán WooCommerce để tránh che nút đặt hàng.', 'asfb-floating-buttons' ); ?></em></label>
	<?php
}

function asfb_fb_field_ripple_html() {
	$options = asfb_fb_get_options();
	?>
	<label><input type="checkbox" name="asfb_fb_options[ripple_effect]" value="1" <?php checked( $options['ripple_effect'], 1 ); ?> /> <em><?php esc_html_e( 'Tạo hiệu ứng sóng lan tỏa xung quanh các nút.', 'asfb-floating-buttons' ); ?></em></label>
	<?php
}

function asfb_fb_field_collapsible_html() {
	$options = asfb_fb_get_options();
	?>
	<label><input type="checkbox" name="asfb_fb_options[collapsible]" value="1" <?php checked( $options['collapsible'], 1 ); ?> /> <em><?php esc_html_e( 'Gom các kênh liên hệ vào 1 nút mở rộng (Speed Dial), giúp gọn màn hình trên điện thoại.', 'asfb-floating-buttons' ); ?></em></label>
	<?php
}

function asfb_fb_field_show_labels_html() {
	$options = asfb_fb_get_options();
	?>
	<label><input type="checkbox" name="asfb_fb_options[show_labels]" value="1" <?php checked( $options['show_labels'], 1 ); ?> /> <em><?php esc_html_e( 'Hiển thị nhãn chữ bên cạnh nút khi rê chuột trên Desktop.', 'asfb-floating-buttons' ); ?></em></label>
	<?php
}

function asfb_fb_field_track_analytics_html() {
	$options = asfb_fb_get_options();
	?>
	<label><input type="checkbox" name="asfb_fb_options[track_analytics]" value="1" <?php checked( $options['track_analytics'], 1 ); ?> /> <em><?php esc_html_e( 'Tự động gửi sự kiện click sang Google Analytics (gtag) và Meta Pixel (fbq) nếu có.', 'asfb-floating-buttons' ); ?></em></label>
	<?php
}

function asfb_fb_field_enable_phone_html() {
	$options = asfb_fb_get_options();
	?><input type="checkbox" name="asfb_fb_options[enable_phone]" value="1" <?php checked( $options['enable_phone'], 1 ); ?> /><?php
}

function asfb_fb_field_phone_number_html() {
	$options = asfb_fb_get_options();
	?><input type="text" class="regular-text" name="asfb_fb_options[phone_number]" value="<?php echo esc_attr( $options['phone_number'] ); ?>" placeholder="09xxxxxxxx" /><?php
}

function asfb_fb_field_phone_label_html() {
	$options = asfb_fb_get_options();
	?><input type="text" class="regular-text" name="asfb_fb_options[phone_label]" value="<?php echo esc_attr( $options['phone_label'] ); ?>" placeholder="Gọi ngay" /><?php
}

function asfb_fb_field_phone_bg_color_html() {
	$options = asfb_fb_get_options();
	?><input type="color" name="asfb_fb_options[phone_bg_color]" value="<?php echo esc_attr( $options['phone_bg_color'] ); ?>" /><?php
}

function asfb_fb_field_enable_zalo_html() {
	$options = asfb_fb_get_options();
	?><input type="checkbox" name="asfb_fb_options[enable_zalo]" value="1" <?php checked( $options['enable_zalo'], 1 ); ?> /><?php
}

function asfb_fb_field_zalo_link_html() {
	$options = asfb_fb_get_options();
	?><input type="url" class="regular-text" name="asfb_fb_options[zalo_link]" value="<?php echo esc_attr( $options['zalo_link'] ); ?>" placeholder="https://zalo.me/09xxxxxxxx" /><?php
}

function asfb_fb_field_zalo_label_html() {
	$options = asfb_fb_get_options();
	?><input type="text" class="regular-text" name="asfb_fb_options[zalo_label]" value="<?php echo esc_attr( $options['zalo_label'] ); ?>" placeholder="Chat Zalo" /><?php
}

function asfb_fb_field_zalo_bg_color_html() {
	$options = asfb_fb_get_options();
	?><input type="color" name="asfb_fb_options[zalo_bg_color]" value="<?php echo esc_attr( $options['zalo_bg_color'] ); ?>" /><?php
}

function asfb_fb_field_enable_messenger_html() {
	$options = asfb_fb_get_options();
	?><input type="checkbox" name="asfb_fb_options[enable_messenger]" value="1" <?php checked( $options['enable_messenger'], 1 ); ?> /><?php
}

function asfb_fb_field_messenger_link_html() {
	$options = asfb_fb_get_options();
	?><input type="url" class="regular-text" name="asfb_fb_options[messenger_link]" value="<?php echo esc_attr( $options['messenger_link'] ); ?>" placeholder="https://m.me/your.facebook.page" /><?php
}

function asfb_fb_field_messenger_label_html() {
	$options = asfb_fb_get_options();
	?><input type="text" class="regular-text" name="asfb_fb_options[messenger_label]" value="<?php echo esc_attr( $options['messenger_label'] ); ?>" placeholder="Chat Messenger" /><?php
}

function asfb_fb_field_messenger_bg_color_html() {
	$options = asfb_fb_get_options();
	?><input type="color" name="asfb_fb_options[messenger_bg_color]" value="<?php echo esc_attr( $options['messenger_bg_color'] ); ?>" /><?php
}

function asfb_fb_field_enable_scrolltop_html() {
	$options = asfb_fb_get_options();
	?><input type="checkbox" name="asfb_fb_options[enable_scrolltop]" value="1" <?php checked( $options['enable_scrolltop'], 1 ); ?> /><?php
}

function asfb_fb_field_scrolltop_bg_color_html() {
	$options = asfb_fb_get_options();
	?><input type="color" name="asfb_fb_options[scrolltop_bg_color]" value="<?php echo esc_attr( $options['scrolltop_bg_color'] ); ?>" /><?php
}

/**
 * Render Admin Settings Page with Modern Card UI, Live Preview & Author/Donate Box.
 */
function asfb_fb_options_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$options  = asfb_fb_get_options();
	$img_path = plugin_dir_url( __FILE__ ) . 'img/';
	$enabled  = ! empty( $options['enable'] );
	?>
	<style>
		.asfb-admin-wrap {
			max-width: 1120px;
			margin: 20px 20px 40px 0;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		}
		.asfb-admin-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 16px;
			flex-wrap: wrap;
			background: #fff;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			padding: 18px 24px;
			margin-bottom: 20px;
		}
		.asfb-admin-title {
			margin: 0;
			font-size: 20px;
			font-weight: 700;
			color: #0f172a;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.asfb-admin-ver {
			font-size: 12px;
			font-weight: 600;
			color: #475569;
			background: #f1f5f9;
			padding: 3px 9px;
			border-radius: 999px;
		}
		.asfb-admin-subtitle {
			margin: 4px 0 0;
			font-size: 13px;
			color: #64748b;
		}
		.asfb-status-pill {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			font-size: 12.5px;
			font-weight: 600;
			padding: 6px 12px;
			border-radius: 999px;
		}
		.asfb-status-pill.is-on {
			background: #ecfdf5;
			color: #047857;
			border: 1px solid #a7f3d0;
		}
		.asfb-status-pill.is-off {
			background: #f8fafc;
			color: #64748b;
			border: 1px solid #e2e8f0;
		}
		.asfb-admin-grid {
			display: grid;
			grid-template-columns: minmax(0, 1fr) 320px;
			gap: 20px;
			align-items: start;
		}
		.asfb-admin-form {
			background: #fff;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			padding: 8px 24px 24px;
		}
		.asfb-admin-form h2 {
			font-size: 14px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: #0f172a;
			margin: 24px 0 10px;
			padding-bottom: 10px;
			border-bottom: 1px solid #e2e8f0;
		}
		.asfb-admin-form .form-table {
			margin-top: 0;
		}
		.asfb-admin-form .form-table th {
			padding: 13px 16px 13px 0;
			font-weight: 600;
			color: #334155;
			width: 220px;
		}
		.asfb-admin-form .form-table td {
			padding: 11px 0;
			color: #475569;
		}
		.asfb-admin-form input[type="text"],
		.asfb-admin-form input[type="url"],
		.asfb-admin-form input[type="number"],
		.asfb-admin-form select {
			border: 1px solid #cbd5e1;
			border-radius: 6px;
			min-height: 36px;
			box-shadow: none;
		}
		.asfb-admin-form input[type="color"] {
			width: 44px;
			height: 34px;
			padding: 2px;
			border: 1px solid #cbd5e1;
			border-radius: 6px;
			background: #fff;
			cursor: pointer;
		}
		.asfb-admin-form .submit {
			margin: 20px 0 0;
			padding: 16px 0 0;
			border-top: 1px solid #e2e8f0;
		}
		.asfb-admin-form .button-primary {
			background: #0f172a;
			border-color: #0f172a;
			border-radius: 6px;
			padding: 6px 20px;
			height: auto;
			font-weight: 600;
		}
		.asfb-admin-form .button-primary:hover {
			background: #1e293b;
			border-color: #1e293b;
		}
		.asfb-admin-sidebar {
			position: sticky;
			top: 44px;
			display: flex;
			flex-direction: column;
			gap: 16px;
		}
		.asfb-side-card {
			background: #fff;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			padding: 18px 20px;
		}
		.asfb-side-card-head {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 12px;
		}
		.asfb-side-card h2 {
			margin: 0;
			font-size: 14px;
			font-weight: 700;
			color: #0f172a;
		}
		.asfb-preview-theme-btn {
			font-size: 11px;
			font-weight: 600;
			padding: 4px 9px;
			border-radius: 4px;
			border: 1px solid #cbd5e1;
			background: #f8fafc;
			color: #334155;
			cursor: pointer;
		}
		.asfb-preview-stage {
			background: #f8fafc;
			border: 1px dashed #cbd5e1;
			border-radius: 8px;
			padding: 20px;
			min-height: 210px;
			display: flex;
			flex-direction: column;
			justify-content: flex-end;
			align-items: <?php echo 'left' === $options['position'] ? 'flex-start' : 'flex-end'; ?>;
			gap: 10px;
			transition: background-color 0.2s ease;
		}
		.asfb-preview-stage.is-dark {
			background: #0f172a;
			border-color: #334155;
		}
		.asfb-preview-btn {
			width: 44px;
			height: 44px;
			border-radius: 50%;
			border: 1.5px solid rgba(255, 255, 255, 0.85);
			display: inline-flex;
			align-items: center;
			justify-content: center;
			color: #fff;
			box-shadow: 0 6px 14px rgba(15, 23, 42, 0.22);
		}
		.asfb-preview-btn img {
			width: 52%;
			height: 52%;
			object-fit: contain;
		}
		.asfb-donate-box {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 6px;
			padding: 12px 14px;
			margin-top: 10px;
			font-size: 12.5px;
		}
		.asfb-copy-acct {
			margin-left: 6px;
			font-size: 11px;
			padding: 2px 7px;
			border-radius: 4px;
			border: 1px solid #cbd5e1;
			background: #fff;
			cursor: pointer;
		}
		@media (max-width: 960px) {
			.asfb-admin-grid {
				grid-template-columns: 1fr;
			}
			.asfb-admin-sidebar {
				position: static;
			}
		}
	</style>

	<div class="wrap asfb-admin-wrap">
		<div class="asfb-admin-header">
			<div>
				<h1 class="asfb-admin-title">
					<?php echo esc_html( get_admin_page_title() ); ?>
					<span class="asfb-admin-ver">v<?php echo esc_html( ASFB_FB_VERSION ); ?></span>
				</h1>
				<p class="asfb-admin-subtitle"><?php esc_html_e( 'Cấu hình cụm nút liên hệ nổi, chế độ Speed Dial, nhãn gợi ý và đo lường chuyển đổi.', 'asfb-floating-buttons' ); ?></p>
			</div>
			<span class="asfb-status-pill <?php echo $enabled ? 'is-on' : 'is-off'; ?>">
				<?php echo $enabled ? esc_html__( 'Đang hiển thị trên web', 'asfb-floating-buttons' ) : esc_html__( 'Đang tắt', 'asfb-floating-buttons' ); ?>
			</span>
		</div>

		<div class="asfb-admin-grid">
			<form action="options.php" method="post" class="asfb-admin-form" id="asfb-fb-settings-form">
				<?php
				settings_fields( 'asfb_fb_settings_group' );
				do_settings_sections( 'asfb-floating-buttons' );
				submit_button( __( 'Lưu thay đổi', 'asfb-floating-buttons' ) );
				?>
			</form>

			<aside class="asfb-admin-sidebar">
				<div class="asfb-side-card">
					<div class="asfb-side-card-head">
						<h2><?php esc_html_e( 'Xem trước cụm nút', 'asfb-floating-buttons' ); ?></h2>
						<button type="button" class="asfb-preview-theme-btn" id="asfb-preview-theme-toggle">Nền tối</button>
					</div>
					<div class="asfb-preview-stage" id="asfb-preview-stage">
						<span class="asfb-preview-btn" data-preview="scrolltop" style="background:<?php echo esc_attr( $options['scrolltop_bg_color'] ); ?>;<?php echo empty( $options['enable_scrolltop'] ) ? 'display:none;' : ''; ?>">↑</span>
						<span class="asfb-preview-btn" data-preview="messenger" style="background:<?php echo esc_attr( $options['messenger_bg_color'] ); ?>;<?php echo empty( $options['enable_messenger'] ) ? 'display:none;' : ''; ?>"><img src="<?php echo esc_url( $img_path . 'mess.png' ); ?>" alt=""></span>
						<span class="asfb-preview-btn" data-preview="zalo" style="background:<?php echo esc_attr( $options['zalo_bg_color'] ); ?>;<?php echo empty( $options['enable_zalo'] ) ? 'display:none;' : ''; ?>"><img src="<?php echo esc_url( $img_path . 'zalo.png' ); ?>" alt=""></span>
						<span class="asfb-preview-btn" data-preview="phone" style="background:<?php echo esc_attr( $options['phone_bg_color'] ); ?>;<?php echo empty( $options['enable_phone'] ) ? 'display:none;' : ''; ?>"><img src="<?php echo esc_url( $img_path . 'phone.png' ); ?>" alt=""></span>
					</div>
				</div>

				<div class="asfb-side-card asfb-fb-credit">
					<h2 style="margin-bottom:10px;"><?php esc_html_e( 'Tác giả & Ủng hộ', 'asfb-floating-buttons' ); ?></h2>
					<p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#334155;">
						Phát triển bởi <strong>Ky Nguyen</strong><br>
						Website: <a href="https://nguyenhieuky.com" target="_blank" rel="noopener noreferrer">nguyenhieuky.com</a><br>
						Email: <a href="mailto:nguyenhieuky@gmail.com">nguyenhieuky@gmail.com</a>
					</p>
					<div class="asfb-donate-box">
						<div style="margin-bottom:6px;color:#475569;">Nếu plugin hữu ích, mời tác giả 1 ly cà phê:</div>
						<div><strong>Ngân hàng:</strong> Vietcombank - VCB</div>
						<div style="margin-top:4px;">
							<strong>STK:</strong> <code id="asfb-vcb-acct">0071000996305</code>
							<button type="button" class="asfb-copy-acct" id="asfb-copy-acct-btn">Sao chép</button>
						</div>
					</div>
				</div>
			</aside>
		</div>
	</div>

	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var stage = document.getElementById('asfb-preview-stage');
		var themeBtn = document.getElementById('asfb-preview-theme-toggle');
		var form = document.getElementById('asfb-fb-settings-form');

		if (themeBtn && stage) {
			themeBtn.addEventListener('click', function () {
				var dark = stage.classList.toggle('is-dark');
				themeBtn.textContent = dark ? 'Nền sáng' : 'Nền tối';
			});
		}

		if (form && stage) {
			var syncPreview = function () {
				var pos = form.querySelector('input[name="asfb_fb_options[position]"]:checked');
				if (pos) {
					stage.style.alignItems = pos.value === 'left' ? 'flex-start' : 'flex-end';
				}
				['scrolltop', 'messenger', 'zalo', 'phone'].forEach(function (key) {
					var btn = stage.querySelector('[data-preview="' + key + '"]');
					var chk = form.querySelector('input[name="asfb_fb_options[enable_' + key + ']"]');
					var clr = form.querySelector('input[name="asfb_fb_options[' + key + '_bg_color]"]');
					if (btn && chk) {
						btn.style.display = chk.checked ? 'inline-flex' : 'none';
					}
					if (btn && clr) {
						btn.style.backgroundColor = clr.value;
					}
				});
			};
			form.addEventListener('input', syncPreview);
			form.addEventListener('change', syncPreview);
		}

		var copyBtn = document.getElementById('asfb-copy-acct-btn');
		var acctEl = document.getElementById('asfb-vcb-acct');
		if (copyBtn && acctEl) {
			copyBtn.addEventListener('click', function () {
				var text = acctEl.textContent.trim();
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(function () {
						copyBtn.textContent = 'Đã chép';
						setTimeout(function () { copyBtn.textContent = 'Sao chép'; }, 1500);
					});
				}
			});
		}
	});
	</script>
	<?php
}

/**
 * Determine if floating buttons should render on the current request.
 *
 * @param array $options Plugin options.
 * @return bool
 */
function asfb_fb_should_render( $options ) {
	if ( empty( $options['enable'] ) ) {
		return false;
	}

	if ( is_admin() || is_feed() || wp_doing_ajax() ) {
		return false;
	}

	if ( ! empty( $options['hide_checkout_cart'] ) ) {
		if ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Enqueue frontend CSS & vanilla JS (zero jQuery dependency).
 */
function asfb_fb_enqueue_scripts() {
	$options = asfb_fb_get_options();
	if ( ! asfb_fb_should_render( $options ) ) {
		return;
	}

	$css_ver = file_exists( plugin_dir_path( __FILE__ ) . 'style.css' ) ? (string) filemtime( plugin_dir_path( __FILE__ ) . 'style.css' ) : ASFB_FB_VERSION;
	$js_ver  = file_exists( plugin_dir_path( __FILE__ ) . 'script.js' ) ? (string) filemtime( plugin_dir_path( __FILE__ ) . 'script.js' ) : ASFB_FB_VERSION;

	wp_enqueue_style( 'asfb-fb-styles', plugin_dir_url( __FILE__ ) . 'style.css', array(), $css_ver );
	wp_enqueue_script( 'asfb-fb-script', plugin_dir_url( __FILE__ ) . 'script.js', array(), $js_ver, true );
	wp_localize_script(
		'asfb-fb-script',
		'asfbFbConfig',
		array(
			'trackAnalytics' => ! empty( $options['track_analytics'] ),
			'collapsible'    => ! empty( $options['collapsible'] ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'asfb_fb_enqueue_scripts' );

/**
 * Render floating buttons in wp_footer.
 */
function asfb_fb_display_buttons() {
	$options = asfb_fb_get_options();
	if ( ! asfb_fb_should_render( $options ) ) {
		return;
	}

	$position_class   = ( 'left' === $options['position'] ) ? 'asfb-fb-pos-left' : 'asfb-fb-pos-right';
	$size_class       = 'asfb-fb-size-' . $options['button_size'];
	$device_class     = 'asfb-fb-device-' . $options['device_visibility'];
	$labels_class     = ! empty( $options['show_labels'] ) ? 'asfb-fb-has-labels' : '';
	$collapsible_mode = ! empty( $options['collapsible'] );
	$ripple_class     = ! empty( $options['ripple_effect'] ) ? 'asfb-fb-ripple' : '';
	$plugin_img_path  = plugin_dir_url( __FILE__ ) . 'img/';

	$inline_vars = sprintf(
		'--asfb-fb-bottom:%dpx;--asfb-fb-side:%dpx;',
		(int) $options['offset_bottom'],
		(int) $options['offset_side']
	);

	$has_messenger = ( ! empty( $options['enable_messenger'] ) && ! empty( $options['messenger_link'] ) );
	$has_zalo      = ( ! empty( $options['enable_zalo'] ) && ! empty( $options['zalo_link'] ) );
	$has_phone     = ( ! empty( $options['enable_phone'] ) && ! empty( $options['phone_number'] ) );
	$has_scrolltop = ! empty( $options['enable_scrolltop'] );
	$has_channels  = ( $has_messenger || $has_zalo || $has_phone );

	if ( ! $has_channels && ! $has_scrolltop ) {
		return;
	}

	$container_classes = trim( "asfb-fb-container {$position_class} {$size_class} {$device_class} {$labels_class}" . ( $collapsible_mode && $has_channels ? ' asfb-fb-is-collapsible' : '' ) );
	?>
	<div class="<?php echo esc_attr( $container_classes ); ?>" style="<?php echo esc_attr( $inline_vars ); ?>" role="region" aria-label="<?php esc_attr_e( 'Liên hệ nhanh', 'asfb-floating-buttons' ); ?>">
		<?php if ( $has_scrolltop ) : ?>
			<button
				type="button"
				id="asfb-fb-scroll-top"
				class="asfb-fb-button <?php echo esc_attr( $ripple_class ); ?>"
				title="<?php esc_attr_e( 'Lên đầu trang', 'asfb-floating-buttons' ); ?>"
				aria-label="<?php esc_attr_e( 'Lên đầu trang', 'asfb-floating-buttons' ); ?>"
				style="background-color: <?php echo esc_attr( $options['scrolltop_bg_color'] ); ?>;"
			>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M12 19V5M5 12l7-7 7 7" />
				</svg>
				<?php if ( ! empty( $options['show_labels'] ) ) : ?>
					<span class="asfb-fb-label"><?php esc_html_e( 'Lên đầu trang', 'asfb-floating-buttons' ); ?></span>
				<?php endif; ?>
			</button>
		<?php endif; ?>

		<?php if ( $has_channels ) : ?>
			<div class="asfb-fb-channels" <?php echo $collapsible_mode ? 'id="asfb-fb-channels-list"' : ''; ?>>
				<?php if ( $has_messenger ) : ?>
					<a
						href="<?php echo esc_url( $options['messenger_link'] ); ?>"
						target="_blank"
						rel="nofollow noopener noreferrer"
						id="asfb-fb-messenger"
						class="asfb-fb-button <?php echo esc_attr( $ripple_class ); ?>"
						data-asfb-channel="messenger"
						title="<?php echo esc_attr( $options['messenger_label'] ); ?>"
						aria-label="<?php echo esc_attr( $options['messenger_label'] ); ?>"
						style="background-color: <?php echo esc_attr( $options['messenger_bg_color'] ); ?>;"
					>
						<img src="<?php echo esc_url( $plugin_img_path . 'mess.png' ); ?>" alt="" width="28" height="28" loading="lazy" />
						<?php if ( ! empty( $options['show_labels'] ) && ! empty( $options['messenger_label'] ) ) : ?>
							<span class="asfb-fb-label"><?php echo esc_html( $options['messenger_label'] ); ?></span>
						<?php endif; ?>
					</a>
				<?php endif; ?>

				<?php if ( $has_zalo ) : ?>
					<a
						href="<?php echo esc_url( $options['zalo_link'] ); ?>"
						target="_blank"
						rel="nofollow noopener noreferrer"
						id="asfb-fb-zalo"
						class="asfb-fb-button <?php echo esc_attr( $ripple_class ); ?>"
						data-asfb-channel="zalo"
						title="<?php echo esc_attr( $options['zalo_label'] ); ?>"
						aria-label="<?php echo esc_attr( $options['zalo_label'] ); ?>"
						style="background-color: <?php echo esc_attr( $options['zalo_bg_color'] ); ?>;"
					>
						<img src="<?php echo esc_url( $plugin_img_path . 'zalo.png' ); ?>" alt="" width="28" height="28" loading="lazy" />
						<?php if ( ! empty( $options['show_labels'] ) && ! empty( $options['zalo_label'] ) ) : ?>
							<span class="asfb-fb-label"><?php echo esc_html( $options['zalo_label'] ); ?></span>
						<?php endif; ?>
					</a>
				<?php endif; ?>

				<?php if ( $has_phone ) : ?>
					<a
						href="tel:<?php echo esc_attr( $options['phone_number'] ); ?>"
						id="asfb-fb-phone"
						class="asfb-fb-button <?php echo esc_attr( $ripple_class ); ?>"
						data-asfb-channel="phone"
						title="<?php echo esc_attr( $options['phone_label'] ); ?>"
						aria-label="<?php echo esc_attr( $options['phone_label'] . ': ' . $options['phone_number'] ); ?>"
						style="background-color: <?php echo esc_attr( $options['phone_bg_color'] ); ?>;"
					>
						<img src="<?php echo esc_url( $plugin_img_path . 'phone.png' ); ?>" alt="" width="28" height="28" loading="lazy" />
						<?php if ( ! empty( $options['show_labels'] ) && ! empty( $options['phone_label'] ) ) : ?>
							<span class="asfb-fb-label"><?php echo esc_html( $options['phone_label'] ); ?></span>
						<?php endif; ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( $collapsible_mode ) : ?>
				<button
					type="button"
					id="asfb-fb-toggle-btn"
					class="asfb-fb-button asfb-fb-toggle-btn <?php echo esc_attr( $ripple_class ); ?>"
					aria-expanded="false"
					aria-controls="asfb-fb-channels-list"
					aria-label="<?php esc_attr_e( 'Mở kênh liên hệ', 'asfb-floating-buttons' ); ?>"
					title="<?php esc_attr_e( 'Liên hệ', 'asfb-floating-buttons' ); ?>"
				>
					<svg class="asfb-fb-icon-open" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
					</svg>
					<svg class="asfb-fb-icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<line x1="18" y1="6" x2="6" y2="18" />
						<line x1="6" y1="6" x2="18" y2="18" />
					</svg>
				</button>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'wp_footer', 'asfb_fb_display_buttons' );
