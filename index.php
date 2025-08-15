<?php
/**
 * Plugin Name: Advanced WordPress Appointments
 * Plugin URI: https://github.com/codewithhamza1/advanced-wordpress-appointments
 * Description: A comprehensive appointment booking system for physiotherapy sessions with WhatsApp integration.
 * Version: 1.0.2
 * Author: Muhammad Hamza Yousaf
 * Text Domain: drfarwa-appointments
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DRFARWA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DRFARWA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DRFARWA_VERSION', '1.0.1');

class DrFarwaAppointments {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Add shortcode
        add_shortcode('drfarwa_appointment_form', array($this, 'appointment_form_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_drfarwa_submit_appointment', array($this, 'handle_appointment_submission'));
        add_action('wp_ajax_nopriv_drfarwa_submit_appointment', array($this, 'handle_appointment_submission'));
        add_action('wp_ajax_drfarwa_export_csv', array($this, 'export_appointments_csv'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_appointment_meta_boxes'));
        add_action('save_post', array($this, 'save_appointment_meta'));
        
        // Custom columns for appointments list
        add_filter('manage_appointment_posts_columns', array($this, 'appointment_columns'));
        add_action('manage_appointment_posts_custom_column', array($this, 'appointment_column_content'), 10, 2);
    }
    
    public function init() {
        $this->register_post_type();
        load_plugin_textdomain('drfarwa-appointments', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        $this->register_post_type();
        flush_rewrite_rules();
    }
    
    public function register_post_type() {
        $labels = array(
            'name' => __('Appointments', 'drfarwa-appointments'),
            'singular_name' => __('Appointment', 'drfarwa-appointments'),
            'menu_name' => __('Appointments', 'drfarwa-appointments'),
            'add_new' => __('Add New', 'drfarwa-appointments'),
            'add_new_item' => __('Add New Appointment', 'drfarwa-appointments'),
            'edit_item' => __('Edit Appointment', 'drfarwa-appointments'),
            'new_item' => __('New Appointment', 'drfarwa-appointments'),
            'view_item' => __('View Appointment', 'drfarwa-appointments'),
            'search_items' => __('Search Appointments', 'drfarwa-appointments'),
            'not_found' => __('No appointments found', 'drfarwa-appointments'),
            'not_found_in_trash' => __('No appointments found in trash', 'drfarwa-appointments'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title'),
            'menu_position' => 25,
        );
        
        register_post_type('appointment', $args);
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('drfarwa-appointments', DRFARWA_PLUGIN_URL . 'assets/style.css', array(), DRFARWA_VERSION);
        wp_enqueue_script('drfarwa-appointments', DRFARWA_PLUGIN_URL . 'assets/script.js', array('jquery'), DRFARWA_VERSION, true);
        
        wp_localize_script('drfarwa-appointments', 'drfarwa_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('drfarwa_appointment_nonce'),
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        if ($hook == 'appointment_page_drfarwa-appointments' || get_post_type() == 'appointment') {
            wp_enqueue_style('drfarwa-admin', DRFARWA_PLUGIN_URL . 'assets/admin-style.css', array(), DRFARWA_VERSION);
        }
    }
    
    public function appointment_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Book Your Appointment', 'drfarwa-appointments')
        ), $atts);
        
        ob_start();
        ?>
        <div class="drfarwa-appointment-form-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <form id="drfarwa-appointment-form" class="drfarwa-form" method="post">
                <?php wp_nonce_field('drfarwa_appointment_nonce', 'drfarwa_nonce'); ?>
                
                <div class="drfarwa-form-group">
                    <label for="drfarwa_name"><?php _e('Full Name', 'drfarwa-appointments'); ?> <span class="required">*</span></label>
                    <input type="text" id="drfarwa_name" name="name" required>
                </div>
                
                <div class="drfarwa-form-group">
                    <label for="drfarwa_service"><?php _e('Service', 'drfarwa-appointments'); ?> <span class="required">*</span></label>
                    <select id="drfarwa_service" name="service" required>
						<option value=""><?php _e('Select a service', 'drfarwa-appointments'); ?></option>
						<option value="Physiotherapy Session"><?php _e('Physiotherapy Session', 'drfarwa-appointments'); ?></option>
						<option value="Dry Needling / Acupuncture"><?php _e('Dry Needling / Acupuncture', 'drfarwa-appointments'); ?></option>
						<option value="Hijama Therapy (Static Cupping)"><?php _e('Hijama Therapy (Static Cupping)', 'drfarwa-appointments'); ?>						   </option>
						<option value="Dynamic Cupping Massage"><?php _e('Dynamic Cupping Massage', 'drfarwa-appointments'); ?></option>
						<option value="Consultation Only"><?php _e('Consultation Only', 'drfarwa-appointments'); ?></option>
						<option value="Home Physiotherapy Visit"><?php _e('Home Physiotherapy Visit', 'drfarwa-appointments'); ?></option>
						<option value="Online Consultation"><?php _e('Online Consultation', 'drfarwa-appointments'); ?></option>
					</select>

                </div>
                
                <div class="drfarwa-form-group">
                    <label for="drfarwa_date"><?php _e('Appointment Date', 'drfarwa-appointments'); ?> <span class="required">*</span></label>
                    <input type="date" id="drfarwa_date" name="date" required min="<?php echo esc_attr(date('Y-m-d')); ?>" max="<?php echo esc_attr(date('Y-m-d', strtotime('+3 months'))); ?>">
                </div>
                
                <div class="drfarwa-form-group">
                    <label for="drfarwa_time"><?php _e('Time Slot', 'drfarwa-appointments'); ?> <span class="required">*</span></label>
                    <select id="drfarwa_time" name="time" required>
                        <option value=""><?php _e('Select time slot', 'drfarwa-appointments'); ?></option>
                        <option value="12:00-13:00">12:00 - 1:00 PM</option>
                        <option value="13:00-14:00">1:00 - 2:00 PM</option>
                        <option value="14:00-15:00">2:00 - 3:00 PM</option>
                        <option value="15:00-16:00">3:00 - 4:00 PM</option>
                        <option value="16:00-17:00">4:00 - 5:00 PM</option>
                        <option value="17:00-18:00">5:00 - 6:00 PM</option>
                        <option value="18:00-19:00">6:00 - 7:00 PM</option>
                        <option value="19:00-20:00">7:00 - 8:00 PM</option>
                        <option value="20:00-21:00">8:00 - 9:00 PM</option>
                        <option value="21:00-22:30">9:00 - 10:30 PM</option>
                    </select>
                </div>
                
                <div class="drfarwa-form-group">
                    <label for="drfarwa_phone"><?php _e('Phone Number', 'drfarwa-appointments'); ?> <span class="required">*</span></label>
                    <input type="tel" id="drfarwa_phone" name="phone" required placeholder="e.g., +923001234567">
                </div>
                
                <div class="drfarwa-form-group">
                    <label for="drfarwa_email"><?php _e('Email Address', 'drfarwa-appointments'); ?> <span style="color: #666; font-size: 12px;">(<?php _e('optional - for email notifications', 'drfarwa-appointments'); ?>)</span></label>
                    <input type="email" id="drfarwa_email" name="email" placeholder="<?php _e('Enter your email to receive booking confirmation', 'drfarwa-appointments'); ?>">
                </div>
                
                <div class="drfarwa-form-group">
                    <button type="submit" class="drfarwa-submit-btn"><?php _e('Book Appointment', 'drfarwa-appointments'); ?></button>
                </div>
                
                <div id="drfarwa-form-message" class="drfarwa-message" style="display: none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handle_appointment_submission() {
        if (!wp_verify_nonce($_POST['drfarwa_nonce'], 'drfarwa_appointment_nonce')) {
            wp_send_json_error(__('Security check failed', 'drfarwa-appointments'));
        }
        
        $name = sanitize_text_field($_POST['name']);
        $service = sanitize_text_field($_POST['service']);
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);
        
        // Validate required fields
        if (empty($name) || empty($service) || empty($date) || empty($time) || empty($phone)) {
            wp_send_json_error(__('Please fill in all required fields.', 'drfarwa-appointments'));
        }
        
        // Create appointment post
        $post_data = array(
            'post_title' => sprintf('%s - %s - %s', $name, $service, $date),
            'post_type' => 'appointment',
            'post_status' => 'publish',
            'meta_input' => array(
                '_drfarwa_name' => $name,
                '_drfarwa_service' => $service,
                '_drfarwa_date' => $date,
                '_drfarwa_time' => $time,
                '_drfarwa_phone' => $phone,
                '_drfarwa_email' => $email,
                '_drfarwa_status' => 'pending',
                '_drfarwa_created' => current_time('mysql'),
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id) {
            // Send email notification to admin (mandatory)
            $admin_email_sent = $this->send_admin_notification($name, $service, $date, $time, $phone, $email);
            
            // Send email notification to patient (only if email provided)
            $patient_email_sent = false;
            if (!empty($email)) {
                $patient_email_sent = $this->send_patient_notification($name, $service, $date, $time, $phone, $email);
            }
            
            // Generate WhatsApp notification data for patient
            $whatsapp_data = $this->send_whatsapp_notification($name, $phone, $email, $service, $date, $time);
            $whatsapp_sent = true; // Assuming WhatsApp is always sent
            
            // Store WhatsApp URL in appointment meta for admin access
            if ($whatsapp_sent) {
                update_post_meta($post_id, '_drfarwa_whatsapp_url', $whatsapp_data['whatsapp_url']);
                update_post_meta($post_id, '_drfarwa_whatsapp_message', $whatsapp_data['message']);
            }
            
            // Success message
            $success_message = __('Appointment booked successfully!', 'drfarwa-appointments');
            if (!empty($email) && $patient_email_sent) {
                $success_message .= ' ' . __('A confirmation email has been sent to your email address.', 'drfarwa-appointments');
            }
            $success_message .= ' ' . __('You will be redirected to WhatsApp to send your appointment details to the clinic.', 'drfarwa-appointments');
            
            wp_send_json_success(array(
                'message' => $success_message,
                'admin_email_sent' => $admin_email_sent,
                'patient_email_sent' => $patient_email_sent,
                'whatsapp_sent' => $whatsapp_sent,
                'whatsapp_url' => $whatsapp_data['whatsapp_url'],
                'fallback_url' => $whatsapp_data['fallback_url']
            ));
        } else {
            wp_send_json_error(__('Failed to book appointment. Please try again.', 'drfarwa-appointments'));
        }
    }
    
    private function send_admin_notification($name, $service, $date, $time, $phone, $email) {
        $admin_email = 'contact.drfarwa@gmail.com';
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('New Appointment Booking - %s', 'drfarwa-appointments'), $site_name);
        
        // Format time for display
        $time_parts = explode('-', $time);
        $formatted_time = isset($time_parts[0]) ? $time_parts[0] : $time;
        
        // HTML email template for admin
        $message = '
<html>
<head>
    <title>' . esc_html($subject) . '</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h2 style="color: #2c3e50; text-align: center; margin-bottom: 30px;">🏥 New Appointment Booking</h2>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <h3 style="color: #27ae60; margin-top: 0;">Patient Details:</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; width: 120px;">Name:</td>
                    <td style="padding: 8px 0;">' . esc_html($name) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Phone:</td>
                    <td style="padding: 8px 0;">
                        <a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a>
                        <a href="https://wa.me/' . esc_attr(preg_replace('/[^0-9+]/', '', $phone)) . '" 
                           style="margin-left: 10px; color: #25D366; text-decoration: none;">📱 WhatsApp</a>
                    </td>
                </tr>' .
                (!empty($email) ? '
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Email:</td>
                    <td style="padding: 8px 0;"><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></td>
                </tr>' : '') . '
            </table>
        </div>
        
        <div style="background: #e8f5e8; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <h3 style="color: #27ae60; margin-top: 0;">Appointment Details:</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; width: 120px;">Service:</td>
                    <td style="padding: 8px 0; color: #2c3e50; font-weight: bold;">' . esc_html($service) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Date:</td>
                    <td style="padding: 8px 0; color: #e74c3c; font-weight: bold;">' . esc_html(date('l, F j, Y', strtotime($date))) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Time:</td>
                    <td style="padding: 8px 0; color: #e74c3c; font-weight: bold;">' . esc_html($formatted_time) . '</td>
                </tr>
            </table>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . admin_url('edit.php?post_type=appointment') . '" 
               style="background: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                📋 Manage Appointments
            </a>
        </div>
        
        <div style="text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 15px; margin-top: 20px;">
            <p>This email was sent automatically from ' . esc_html($site_name) . '<br>
            Booking received on ' . esc_html(date_i18n('F j, Y \a\t g:i A')) . '</p>
        </div>
    </div>
</body>
</html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . $admin_email . '>'
        );
        
        return wp_mail($admin_email, $subject, $message, $headers);
    }
    
    private function send_patient_notification($name, $service, $date, $time, $phone, $email) {
        $site_name = get_bloginfo('name');
        $admin_email = get_option('admin_email');
        
        $subject = sprintf(__('Appointment Confirmation - %s', 'drfarwa-appointments'), $site_name);
        
        // Format time for display
        $time_parts = explode('-', $time);
        $formatted_time = isset($time_parts[0]) ? $time_parts[0] : $time;
        
        // HTML email template for patient
        $message = '
        <html>
        <head>
            <title>' . esc_html($subject) . '</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="color: #2c3e50; margin-bottom: 10px;">🏥 Dr. Farwa\'s Clinic</h2>
                    <h3 style="color: #27ae60; margin-top: 0;">Appointment Confirmation</h3>
                </div>
                
                <div style="background: #f0f8ff; padding: 20px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #3498db;">
                    <p style="margin: 0; font-size: 16px;">
                        <strong>Dear ' . esc_html($name) . ',</strong><br><br>
                        Thank you for booking an appointment with Dr. Farwa\'s clinic. We have successfully received your appointment request.
                    </p>
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                    <h3 style="color: #2c3e50; margin-top: 0; text-align: center;">📅 Your Appointment Details</h3>
                    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                        <tr style="background: #e8f5e8;">
                            <td style="padding: 12px; font-weight: bold; border: 1px solid #ddd;">Service:</td>
                            <td style="padding: 12px; border: 1px solid #ddd; color: #2c3e50; font-weight: bold;">' . esc_html($service) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; font-weight: bold; border: 1px solid #ddd;">Date:</td>
                            <td style="padding: 12px; border: 1px solid #ddd; color: #e74c3c; font-weight: bold;">' . esc_html(date('l, F j, Y', strtotime($date))) . '</td>
                        </tr>
                        <tr style="background: #e8f5e8;">
                            <td style="padding: 12px; font-weight: bold; border: 1px solid #ddd;">Time:</td>
                            <td style="padding: 12px; border: 1px solid #ddd; color: #e74c3c; font-weight: bold;">' . esc_html($formatted_time) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; font-weight: bold; border: 1px solid #ddd;">Status:</td>
                            <td style="padding: 12px; border: 1px solid #ddd;"><span style="background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">PENDING CONFIRMATION</span></td>
                        </tr>
                    </table>
                </div>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                    <p style="margin: 0; color: #856404;">
                        <strong>⚠️ Important Note:</strong><br>
                        Your appointment is currently <strong>pending confirmation</strong>. Our team will contact you within 24 hours to confirm your appointment slot.
                    </p>
                </div>
                
                <div style="background: #d4edda; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                    <h4 style="margin-top: 0; color: #155724;">📞 Need to Contact Us?</h4>
                    <p style="margin: 5px 0; color: #155724;">
                        If you have any questions or need to reschedule, please contact us:<br>
                        <strong>Phone:</strong> +92 300 0000000<br>
                        <strong>Email:</strong> ' . esc_html($admin_email) . '
                    </p>
                </div>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <h4 style="margin-top: 0; color: #2c3e50;">📋 Before Your Appointment:</h4>
                    <ul style="color: #495057; margin: 0; padding-left: 20px;">
                        <li>Please arrive 10 minutes early for your appointment</li>
                        <li>Bring any relevant medical documents or previous reports</li>
                        <li>Wear comfortable clothing suitable for physical examination</li>
                        <li>If you need to cancel or reschedule, please notify us at least 24 hours in advance</li>
                    </ul>
                </div>
                
                <div style="text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 15px; margin-top: 20px;">
                    <p><strong>Dr. Farwa\'s Physiotherapy Clinic</strong><br>
					Providing quality healthcare services<br>
					This confirmation was sent on ' . esc_html(date_i18n('F j, Y \a\t g:i A')) . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . $admin_email . '>',
            'Reply-To: ' . $admin_email
        );
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    private function send_whatsapp_notification($name, $phone, $email, $service, $date, $time) {
        // Create WhatsApp message for patient to send to clinic
        // Using simple text without special characters to avoid encoding issues
        $message = "APPOINTMENT BOOKING REQUEST\n\n";
        $message .= "Hello! I would like to book an appointment.\n\n";
        $message .= "My Details:\n";
        $message .= "- Name: " . $name . "\n";
        $message .= "- Phone: " . $phone . "\n";
        if (!empty($email)) {
            $message .= "- Email: " . $email . "\n";
        }
        $message .= "\nService Required:\n";
        $message .= "- " . $service . "\n";
        $message .= "\nPreferred Appointment:\n";
        $message .= "- Date: " . date('l, F j, Y', strtotime($date)) . "\n";
        $message .= "- Time: " . $time . "\n";
        $message .= "\nAdditional Information:\n";
        $message .= "- This booking was made online\n";
        $message .= "- Booking ID: " . time() . "\n";
        $message .= "- Please confirm my appointment slot\n\n";
        $message .= "Thank you!";
        
        // Create WhatsApp URL with proper encoding
        $whatsapp_url = "https://wa.me/+923202663389?text=" . urlencode($message);
        
        // Also create a fallback URL for better desktop app compatibility
        $fallback_url = "https://api.whatsapp.com/send?phone=+923000000000&text=" . urlencode($message);
        
        return array(
            'message' => $message,
            'whatsapp_url' => $whatsapp_url,
            'fallback_url' => $fallback_url
        );
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=appointment',
            __('Appointment Dashboard', 'drfarwa-appointments'),
            __('Dashboard', 'drfarwa-appointments'),
            'manage_options',
            'drfarwa-appointments',
            array($this, 'admin_dashboard')
        );
    }
    
    public function admin_dashboard() {
        $appointments = get_posts(array(
            'post_type' => 'appointment',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        ?>
        <div class="wrap drfarwa-admin-dashboard">
            <h1><?php _e('Dr. Farwa Appointments Dashboard', 'drfarwa-appointments'); ?></h1>
            
            <div class="drfarwa-stats">
                <div class="drfarwa-stat-box">
                    <h3><?php _e('Total Appointments', 'drfarwa-appointments'); ?></h3>
                    <p class="drfarwa-stat-number"><?php echo count($appointments); ?></p>
                </div>
                
                <div class="drfarwa-stat-box">
                    <h3><?php _e('Today\'s Appointments', 'drfarwa-appointments'); ?></h3>
                    <p class="drfarwa-stat-number">
                        <?php 
                        $today_count = 0;
                        foreach ($appointments as $apt) {
                            $apt_date = get_post_meta($apt->ID, '_drfarwa_date', true);
                            if ($apt_date == date('Y-m-d')) {
                                $today_count++;
                            }
                        }
                        echo $today_count;
                        ?>
                    </p>
                </div>
                
                <div class="drfarwa-stat-box">
                    <h3><?php _e('Pending Appointments', 'drfarwa-appointments'); ?></h3>
                    <p class="drfarwa-stat-number">
                        <?php 
                        $pending_count = 0;
                        foreach ($appointments as $apt) {
                            $status = get_post_meta($apt->ID, '_drfarwa_status', true);
                            if ($status == 'pending') {
                                $pending_count++;
                            }
                        }
                        echo $pending_count;
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="drfarwa-actions">
                <a href="<?php echo admin_url('admin-ajax.php?action=drfarwa_export_csv&_wpnonce=' . wp_create_nonce('drfarwa_export_csv')); ?>" 
                   class="button button-secondary"><?php _e('Export CSV', 'drfarwa-appointments'); ?></a>
            </div>
            
            <h2><?php _e('Recent Appointments', 'drfarwa-appointments'); ?></h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'drfarwa-appointments'); ?></th>
                        <th><?php _e('Service', 'drfarwa-appointments'); ?></th>
                        <th><?php _e('Date', 'drfarwa-appointments'); ?></th>
                        <th><?php _e('Time', 'drfarwa-appointments'); ?></th>
                        <th><?php _e('Phone', 'drfarwa-appointments'); ?></th>
                        <th><?php _e('Status', 'drfarwa-appointments'); ?></th>
                        <th><?php _e('Actions', 'drfarwa-appointments'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($appointments, 0, 10) as $appointment): ?>
                    <tr>
                        <td><?php echo esc_html(get_post_meta($appointment->ID, '_drfarwa_name', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($appointment->ID, '_drfarwa_service', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($appointment->ID, '_drfarwa_date', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($appointment->ID, '_drfarwa_time', true)); ?></td>
                        <td>
                            <?php 
                            $phone = get_post_meta($appointment->ID, '_drfarwa_phone', true);
                            echo esc_html($phone);
                            if ($phone) {
                                echo ' <a href="https://wa.me/' . esc_attr(preg_replace('/[^0-9+]/', '', $phone)) . '" target="_blank" title="WhatsApp">📱</a>';
                            }
                            ?>
                        </td>
                        <td>
                            <span class="drfarwa-status drfarwa-status-<?php echo esc_attr(get_post_meta($appointment->ID, '_drfarwa_status', true)); ?>">
                                <?php echo esc_html(get_post_meta($appointment->ID, '_drfarwa_status', true)); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo get_edit_post_link($appointment->ID); ?>" class="button button-small">
                                <?php _e('Edit', 'drfarwa-appointments'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function add_appointment_meta_boxes() {
        add_meta_box(
            'drfarwa_appointment_details',
            __('Appointment Details', 'drfarwa-appointments'),
            array($this, 'appointment_details_meta_box'),
            'appointment',
            'normal',
            'high'
        );
        
        add_meta_box(
            'drfarwa_appointment_admin',
            __('Admin Panel', 'drfarwa-appointments'),
            array($this, 'appointment_admin_meta_box'),
            'appointment',
            'side',
            'high'
        );
    }
    
    public function appointment_details_meta_box($post) {
        wp_nonce_field('drfarwa_appointment_meta', 'drfarwa_appointment_meta_nonce');
        
        $name = get_post_meta($post->ID, '_drfarwa_name', true);
        $service = get_post_meta($post->ID, '_drfarwa_service', true);
        $date = get_post_meta($post->ID, '_drfarwa_date', true);
        $time = get_post_meta($post->ID, '_drfarwa_time', true);
        $phone = get_post_meta($post->ID, '_drfarwa_phone', true);
        $email = get_post_meta($post->ID, '_drfarwa_email', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="drfarwa_name"><?php _e('Full Name', 'drfarwa-appointments'); ?></label></th>
                <td><input type="text" id="drfarwa_name" name="drfarwa_name" value="<?php echo esc_attr($name); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="drfarwa_service"><?php _e('Service', 'drfarwa-appointments'); ?></label></th>
                <td>
                    <select id="drfarwa_service" name="drfarwa_service">
                        <option value="Physiotherapy & Rehab" <?php selected($service, 'Physiotherapy & Rehab'); ?>>Physiotherapy & Rehab</option>
                        <option value="Hijama Therapy" <?php selected($service, 'Hijama Therapy'); ?>>Hijama Therapy</option>
                        <option value="Dry Needling" <?php selected($service, 'Dry Needling'); ?>>Dry Needling</option>
                        <option value="Women's Therapy" <?php selected($service, "Women's Therapy"); ?>>Women's Therapy</option>
                        <option value="Home Visit" <?php selected($service, 'Home Visit'); ?>>Home Visit</option>
                        <option value="Online Consultation" <?php selected($service, 'Online Consultation'); ?>>Online Consultation</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="drfarwa_date"><?php _e('Date', 'drfarwa-appointments'); ?></label></th>
                <td><input type="date" id="drfarwa_date" name="drfarwa_date" value="<?php echo esc_attr($date); ?>" /></td>
            </tr>
            <tr>
                <th><label for="drfarwa_time"><?php _e('Time', 'drfarwa-appointments'); ?></label></th>
                <td><input type="text" id="drfarwa_time" name="drfarwa_time" value="<?php echo esc_attr($time); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="drfarwa_phone"><?php _e('Phone', 'drfarwa-appointments'); ?></label></th>
                <td>
                    <input type="tel" id="drfarwa_phone" name="drfarwa_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" />
                    <?php if ($phone): ?>
                        <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>" target="_blank" class="button">WhatsApp</a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="drfarwa_email"><?php _e('Email', 'drfarwa-appointments'); ?></label></th>
                <td><input type="email" id="drfarwa_email" name="drfarwa_email" value="<?php echo esc_attr($email); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }
    
    public function appointment_admin_meta_box($post) {
        $status = get_post_meta($post->ID, '_drfarwa_status', true);
        $notes = get_post_meta($post->ID, '_drfarwa_admin_notes', true);
        $created = get_post_meta($post->ID, '_drfarwa_created', true);
        
        ?>
        <p><strong><?php _e('Created:', 'drfarwa-appointments'); ?></strong> <?php echo esc_html($created); ?></p>
        
        <p>
            <label for="drfarwa_status"><?php _e('Status', 'drfarwa-appointments'); ?></label><br>
            <select id="drfarwa_status" name="drfarwa_status" style="width: 100%;">
                <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Pending', 'drfarwa-appointments'); ?></option>
                <option value="confirmed" <?php selected($status, 'confirmed'); ?>><?php _e('Confirmed', 'drfarwa-appointments'); ?></option>
                <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('Completed', 'drfarwa-appointments'); ?></option>
                <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php _e('Cancelled', 'drfarwa-appointments'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="drfarwa_admin_notes"><?php _e('Admin Notes', 'drfarwa-appointments'); ?></label><br>
            <textarea id="drfarwa_admin_notes" name="drfarwa_admin_notes" rows="4" style="width: 100%;"><?php echo esc_textarea($notes); ?></textarea>
        </p>
        <?php
    }
    
    public function save_appointment_meta($post_id) {
        if (!isset($_POST['drfarwa_appointment_meta_nonce']) || !wp_verify_nonce($_POST['drfarwa_appointment_meta_nonce'], 'drfarwa_appointment_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = array('name', 'service', 'date', 'time', 'phone', 'email', 'status', 'admin_notes');
        
        foreach ($fields as $field) {
            if (isset($_POST['drfarwa_' . $field])) {
                update_post_meta($post_id, '_drfarwa_' . $field, sanitize_text_field($_POST['drfarwa_' . $field]));
            }
        }
        
        // Update post title
        if (isset($_POST['drfarwa_name']) && isset($_POST['drfarwa_service']) && isset($_POST['drfarwa_date'])) {
            $new_title = sprintf('%s - %s - %s', 
                sanitize_text_field($_POST['drfarwa_name']),
                sanitize_text_field($_POST['drfarwa_service']),
                sanitize_text_field($_POST['drfarwa_date'])
            );
            
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $new_title
            ));
        }
    }
    
    public function appointment_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['drfarwa_patient'] = __('Patient', 'drfarwa-appointments');
        $new_columns['drfarwa_service'] = __('Service', 'drfarwa-appointments');
        $new_columns['drfarwa_datetime'] = __('Date & Time', 'drfarwa-appointments');
        $new_columns['drfarwa_contact'] = __('Contact', 'drfarwa-appointments');
        $new_columns['drfarwa_status'] = __('Status', 'drfarwa-appointments');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    public function appointment_column_content($column, $post_id) {
        switch ($column) {
            case 'drfarwa_patient':
                echo esc_html(get_post_meta($post_id, '_drfarwa_name', true));
                break;
            case 'drfarwa_service':
                echo esc_html(get_post_meta($post_id, '_drfarwa_service', true));
                break;
            case 'drfarwa_datetime':
                $date = get_post_meta($post_id, '_drfarwa_date', true);
                $time = get_post_meta($post_id, '_drfarwa_time', true);
                echo esc_html($date . ' ' . $time);
                break;
            case 'drfarwa_contact':
                $phone = get_post_meta($post_id, '_drfarwa_phone', true);
                $email = get_post_meta($post_id, '_drfarwa_email', true);
                if ($phone) {
                    echo esc_html($phone) . '<br>';
                    echo '<a href="https://wa.me/' . esc_attr(preg_replace('/[^0-9+]/', '', $phone)) . '" target="_blank">📱 WhatsApp</a><br>';
                }
                if ($email) {
                    echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                }
                break;
            case 'drfarwa_status':
                $status = get_post_meta($post_id, '_drfarwa_status', true);
                echo '<span class="drfarwa-status drfarwa-status-' . esc_attr($status) . '">' . esc_html($status) . '</span>';
                break;
        }
    }
    
    public function export_appointments_csv() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to export appointments.', 'drfarwa-appointments'));
        }
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'drfarwa_export_csv')) {
            wp_die(__('Security check failed', 'drfarwa-appointments'));
        }
        
        $appointments = get_posts(array(
            'post_type' => 'appointment',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $filename = 'dr-farwa-appointments-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, array(
            'Name',
            'Service',
            'Date',
            'Time',
            'Phone',
            'Email',
            'Status',
            'Admin Notes',
            'Created'
        ));
        
        // CSV Data
        foreach ($appointments as $appointment) {
            fputcsv($output, array(
                get_post_meta($appointment->ID, '_drfarwa_name', true),
                get_post_meta($appointment->ID, '_drfarwa_service', true),
                get_post_meta($appointment->ID, '_drfarwa_date', true),
                get_post_meta($appointment->ID, '_drfarwa_time', true),
                get_post_meta($appointment->ID, '_drfarwa_phone', true),
                get_post_meta($appointment->ID, '_drfarwa_email', true),
                get_post_meta($appointment->ID, '_drfarwa_status', true),
                get_post_meta($appointment->ID, '_drfarwa_admin_notes', true),
                get_post_meta($appointment->ID, '_drfarwa_created', true)
            ));
        }
        
        fclose($output);
        exit;
    }
}

// Initialize the plugin
new DrFarwaAppointments();

// Elementor Widget (Optional)
if (class_exists('Elementor\Widget_Base')) {
    class DrFarwaAppointmentsElementorWidget extends \Elementor\Widget_Base {
        
        public function get_name() {
            return 'drfarwa_appointments';
        }
        
        public function get_title() {
            return __('Dr. Farwa Appointments', 'drfarwa-appointments');
        }
        
        public function get_icon() {
            return 'eicon-calendar';
        }
        
        public function get_categories() {
            return ['general'];
        }
        
        protected function _register_controls() {
            $this->start_controls_section(
                'content_section',
                [
                    'label' => __('Content', 'drfarwa-appointments'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );
            
            $this->add_control(
                'form_title',
                [
                    'label' => __('Form Title', 'drfarwa-appointments'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => __('Book Your Appointment', 'drfarwa-appointments'),
                ]
            );
            
            $this->end_controls_section();
        }
        
        protected function render() {
            $settings = $this->get_settings_for_display();
            echo do_shortcode('[drfarwa_appointment_form title="' . esc_attr($settings['form_title']) . '"]');
        }
    }
    
    // Register Elementor Widget
    add_action('elementor/widgets/widgets_registered', function() {
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new DrFarwaAppointmentsElementorWidget());
    });
}

// Create plugin assets directory and files on activation
register_activation_hook(__FILE__, 'drfarwa_create_assets');

function drfarwa_create_assets() {
    $upload_dir = wp_upload_dir();
    $plugin_assets_dir = $upload_dir['basedir'] . '/drfarwa-appointments/';
    
    if (!file_exists($plugin_assets_dir)) {
        wp_mkdir_p($plugin_assets_dir);
    }
    
    // Create CSS file
    $css_content = '
/* Dr. Farwa Appointments Styles */
.drfarwa-appointment-form-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.drfarwa-appointment-form-container h3 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 30px;
    font-size: 24px;
}

.drfarwa-form-group {
    margin-bottom: 20px;
}

.drfarwa-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #34495e;
}

.drfarwa-form-group .required {
    color: #e74c3c;
}

.drfarwa-form-group input,
.drfarwa-form-group select,
.drfarwa-form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.drfarwa-form-group input:focus,
.drfarwa-form-group select:focus,
.drfarwa-form-group textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
}

.drfarwa-form-group input[type="date"] {
    cursor: pointer;
}

.drfarwa-submit-btn {
    width: 100%;
    padding: 15px;
    background: #043788 !important;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s;
}

.drfarwa-submit-btn:hover {
    background: #021f5e !important;
}

.drfarwa-submit-btn:disabled {
    background: #95a5a6;
    cursor: not-allowed;
}

.drfarwa-message {
    padding: 15px;
    margin: 20px 0;
    border-radius: 4px;
    text-align: center;
    font-weight: bold;
}

.drfarwa-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.drfarwa-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.drfarwa-loading {
    opacity: 0.7;
    pointer-events: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .drfarwa-appointment-form-container {
        margin: 10px;
        padding: 15px;
    }
    
    .drfarwa-appointment-form-container h3 {
        font-size: 20px;
    }
    
    .drfarwa-form-group input,
    .drfarwa-form-group select {
        font-size: 16px; /* Prevents zoom on iOS */
    }
}

/* Admin Styles */
.drfarwa-admin-dashboard {
    padding: 20px 0;
}

.drfarwa-stats {
    display: flex;
    gap: 20px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.drfarwa-stat-box {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    flex: 1;
    min-width: 200px;
    text-align: center;
}

.drfarwa-stat-box h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 16px;
}

.drfarwa-stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #3498db;
    margin: 0;
}

.drfarwa-actions {
    margin: 20px 0;
}

.drfarwa-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.drfarwa-status-pending {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.drfarwa-status-confirmed {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.drfarwa-status-completed {
    background: #cce5ff;
    color: #004085;
    border: 1px solid #99d5ff;
}

.drfarwa-status-cancelled {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
';
    
    file_put_contents(DRFARWA_PLUGIN_PATH . 'assets/style.css', $css_content);
    
    // Create Admin CSS file
    $admin_css_content = '
/* Admin Styles */
.drfarwa-admin-dashboard .drfarwa-stats {
    display: flex;
    gap: 20px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.drfarwa-admin-dashboard .drfarwa-stat-box {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    flex: 1;
    min-width: 200px;
    text-align: center;
    border-left: 4px solid #3498db;
}

.drfarwa-admin-dashboard .drfarwa-stat-box h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.drfarwa-admin-dashboard .drfarwa-stat-number {
    font-size: 28px;
    font-weight: bold;
    color: #3498db;
    margin: 0;
}

.drfarwa-actions {
    margin: 20px 0;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}
';
    
    file_put_contents(DRFARWA_PLUGIN_PATH . 'assets/admin-style.css', $admin_css_content);
    
    // Create JavaScript file
    $js_content = '
jQuery(document).ready(function($) {
    // Log AJAX URL for debugging
    console.log("AJAX URL:", drfarwa_ajax.ajax_url);
    console.log("Nonce:", drfarwa_ajax.nonce);

    // Form submission
    $("#drfarwa-appointment-form").on("submit", function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find(".drfarwa-submit-btn");
        var messageDiv = $("#drfarwa-form-message");
        
        // Validate all fields before submission
        var isValid = true;
        form.find("input[required], select[required]").each(function() {
            if (!validateField($(this))) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            messageDiv.removeClass("success").addClass("error")
                .html("Please correct the errors in the form.").show();
            return;
        }
        
        // Show loading state
        submitBtn.prop("disabled", true).text("Booking...");
        form.addClass("drfarwa-loading");
        messageDiv.hide();
        
        // Collect form data
        var formData = {
            action: "drfarwa_submit_appointment",
            drfarwa_nonce: form.find("input[name=drfarwa_nonce]").val(),
            name: form.find("input[name=name]").val(),
            service: form.find("select[name=service]").val(),
            date: form.find("input[name=date]").val(),
            time: form.find("select[name=time]").val(),
            phone: form.find("input[name=phone]").val(),
            email: form.find("input[name=email]").val()
        };
        
        console.log("Submitting form data:", formData);
        
        // Submit via AJAX
        $.ajax({
            url: drfarwa_ajax.ajax_url,
            type: "POST",
            data: formData,
            success: function(response) {
                console.log("AJAX success response:", response);
                if (response.success) {
                    var successMessage = response.data.message;
                    
                    // Add WhatsApp redirect info
                    if (response.data.whatsapp_sent && response.data.whatsapp_url) {
                        successMessage += \'<br><br>📱 <strong>Redirecting to WhatsApp to send appointment details to the clinic...</strong>\';
                        
                        // Try multiple methods for better compatibility with desktop apps
                        setTimeout(function() {
                            var whatsappUrl = response.data.whatsapp_url;
                            var fallbackUrl = response.data.fallback_url || whatsappUrl;
                            
                            // First try to open in new tab
                            try {
                                var newWindow = window.open(whatsappUrl, \'_blank\');
                                if (!newWindow || newWindow.closed || typeof newWindow.closed == \'undefined\') {
                                    // If popup blocked, try fallback URL
                                    window.location.href = fallbackUrl;
                                }
                            } catch (error) {
                                // Fallback to same window with fallback URL
                                window.location.href = fallbackUrl;
                            }
                        }, 1000);
                    }
                    
                    messageDiv.removeClass("error").addClass("success")
                        .html(successMessage).show();
                    
                    // Reset form
                    form[0].reset();
                    
                    // Scroll to success message
                    $("html, body").animate({
                        scrollTop: messageDiv.offset().top - 100
                    }, 500);
                } else {
                    messageDiv.removeClass("success").addClass("error")
                        .html(response.data || "An error occurred. Please try again.").show();
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", xhr, status, error);
                messageDiv.removeClass("success").addClass("error")
                    .html("Network error: " + error + ". Please try again.").show();
            },
            complete: function() {
                // Reset loading state
                submitBtn.prop("disabled", false).text("Book Appointment");
                form.removeClass("drfarwa-loading");
            }
        });
    });
    
    // Phone number formatting
    $("#drfarwa_phone").on("input", function() {
        var phone = $(this).val();
        // Remove any non-digit characters except + at the beginning
        phone = phone.replace(/[^0-9+]/g, "");
        if (phone.indexOf("+") > 0) {
            phone = phone.replace(/\+/g, "");
            phone = "+" + phone;
        }
        $(this).val(phone);
    });
    
    // Form validation
    $("#drfarwa-appointment-form input, #drfarwa-appointment-form select").on("blur", function() {
        validateField($(this));
    });
    
    function validateField(field) {
        var value = field.val().trim();
        var isRequired = field.prop("required");
        var fieldType = field.attr("type") || field.prop("tagName").toLowerCase();
        
        // Remove existing error styling
        field.removeClass("error");
        
        if (isRequired && !value) {
            field.addClass("error");
            return false;
        }
        
        // Email validation
        if (fieldType === "email" && value) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                field.addClass("error");
                return false;
            }
        }
        
        // Phone validation
        if (field.attr("name") === "phone" && value) {
            var phoneRegex = /^\+?[1-9]\d{1,14}$/;
            if (!phoneRegex.test(value.replace(/\s/g, ""))) {
                field.addClass("error");
                return false;
            }
        }
        
        // Date validation
        if (field.attr("type") === "date" && value) {
            var selectedDate = new Date(value);
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            var maxDate = new Date();
            maxDate.setMonth(maxDate.getMonth() + 3);
            maxDate.setHours(0, 0, 0, 0);
            
            if (selectedDate < today || selectedDate > maxDate) {
                field.addClass("error");
                return false;
            }
        }
        
        return true;
    }
    
    // Add error styles to CSS
    var errorStyles = `
        <style>
        .drfarwa-form-group input.error,
        .drfarwa-form-group select.error {
            border-color: #e74c3c !important;
            box-shadow: 0 0 5px rgba(231, 76, 60, 0.3) !important;
        }
        </style>
    `;
    $("head").append(errorStyles);
});
';
    
    file_put_contents(DRFARWA_PLUGIN_PATH . 'assets/script.js', $js_content);
}

// Create assets directory if it doesn't exist
if (!file_exists(DRFARWA_PLUGIN_PATH . 'assets/')) {
    wp_mkdir_p(DRFARWA_PLUGIN_PATH . 'assets/');
    drfarwa_create_assets();
}
?>
