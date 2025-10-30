<?php
/*
Plugin Name: Custom Auth Plugin
Description: A simple plugin for custom user authentication and session management.
Version: 1.0
Author: Jaffar Shariff
*/

// Shortcode to display login form
function custom_login_form_shortcode() {
 if (is_user_logged_in()) {
        return '<p>You are already logged in.</p>';
    }

    $html = '<form method="post">';
    // Add nonce field here for security
    $html .= wp_nonce_field('custom_login_action', 'custom_login_nonce', true, false);

    $html .= '<p><label for="username">Username</label><br />';
    $html .= '<input type="text" name="username" id="username" required /></p>';
    $html .= '<p><label for="password">Password</label><br />';
    $html .= '<input type="password" name="password" id="password" required /></p>';
    $html .= '<p><input type="submit" name="custom_login_submit" value="Login" /></p>';
    $html .= '</form>';

    if (isset($_POST['custom_login_submit'])) {
        // Verify nonce before processing login
        if (!isset($_POST['custom_login_nonce']) || !wp_verify_nonce($_POST['custom_login_nonce'], 'custom_login_action')) {
            $html .= '<p style="color:red;">Invalid form submission detected.</p>';
            return $html;
        }

        $user = wp_signon([
            'user_login'    => sanitize_text_field($_POST['username']),
            'user_password' => sanitize_text_field($_POST['password']),
            'remember'      => true,
        ], false);

        if (is_wp_error($user)) {
            $html .= '<p style="color:red;">Login failed: ' . $user->get_error_message() . '</p>';
        } else {
            wp_redirect(home_url());
            exit;
        }
    }

    return $html;
}
add_shortcode('custom_login_form', 'custom_login_form_shortcode');

// Shortcode to display registration form
function custom_registration_form_shortcode() {
    if (is_user_logged_in()) {
        return '<p>You are already registered and logged in.</p>';
    }

    $html = '<form method="post">';
    $html .= wp_nonce_field('custom_register_action', 'custom_register_nonce', true, false);
    $html .= '<p><label for="reg_username">Username</label><br />';
    $html .= '<input type="text" name="reg_username" id="reg_username" required /></p>';
    $html .= '<p><label for="reg_email">Email</label><br />';
    $html .= '<input type="email" name="reg_email" id="reg_email" required /></p>';
    $html .= '<p><label for="reg_password">Password</label><br />';
    $html .= '<input type="password" name="reg_password" id="reg_password" required /></p>';
    $html .= '<p><input type="submit" name="custom_register_submit" value="Register" /></p>';
    $html .= '</form>';

    if (isset($_POST['custom_register_submit'])) {
        // Verify nonce first
        if (!isset($_POST['custom_register_nonce']) || !wp_verify_nonce($_POST['custom_register_nonce'], 'custom_register_action')) {
            $html .= '<p style="color:red;">Invalid form submission detected.</p>';
            return $html;
        }

        $username = sanitize_user($_POST['reg_username']);
        $email    = sanitize_email($_POST['reg_email']);
        $password = sanitize_text_field($_POST['reg_password']);

        $errors = new WP_Error();

        if (username_exists($username)) {
            $errors->add('username_exists', 'Username already exists');
        }
        if (!is_email($email)) {
            $errors->add('invalid_email', 'Invalid email address');
        }
        if (email_exists($email)) {
            $errors->add('email_exists', 'Email already exists');
        }
        if (empty($password)) {
            $errors->add('empty_password', 'Please enter a password');
        }

        if (empty($errors->errors)) {
            $user_id = wp_create_user($username, $password, $email);
            if (!is_wp_error($user_id)) {
                echo '<p>Registration successful! You can now <a href="' . wp_login_url() . '">log in</a>.</p>';
                return;
            }
        } else {
            foreach ($errors->get_error_messages() as $msg) {
                $html .= '<p style="color:red;">' . esc_html($msg) . '</p>';
            }
        }
    }
    return $html;
}
add_shortcode('custom_registration_form', 'custom_registration_form_shortcode');
// Shortcode for logout link
function custom_logout_shortcode() {
    if (is_user_logged_in()) {
        $logout_url = wp_logout_url(home_url());
        return '<a href="' . esc_url($logout_url) . '">Logout</a>';
    } else {
        return '<p>You are not logged in.</p>';
    }
}
add_shortcode('custom_logout', 'custom_logout_shortcode');
// Shortcode to show password reset form
function custom_password_reset_shortcode() {
   if (is_user_logged_in()) {
        return '<p>You are logged in. No need to reset password.</p>';
    }

    ob_start();

    if (isset($_POST['custom_password_reset_email'])) {
        // Verify nonce first
        if (!isset($_POST['custom_password_reset_nonce']) || !wp_verify_nonce($_POST['custom_password_reset_nonce'], 'custom_password_reset_action')) {
            echo '<p style="color:red;">Invalid form submission detected.</p>';
            return ob_get_clean();
        }

        $email = sanitize_email($_POST['custom_password_reset_email']);
        if (!email_exists($email)) {
            echo '<p style="color:red;">Email does not exist in our records.</p>';
        } else {
            retrieve_password($email);
            echo '<p style="color:green;">Password reset instructions sent to your email.</p>';
        }
    }
    ?>
    <form method="post">
        <?php wp_nonce_field('custom_password_reset_action', 'custom_password_reset_nonce', true, false); ?>
        <p><label for="custom_password_reset_email">Enter your email</label><br />
        <input type="email" name="custom_password_reset_email" id="custom_password_reset_email" required /></p>
        <p><input type="submit" value="Reset Password" /></p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_password_reset', 'custom_password_reset_shortcode');

// Enqueue plugin CSS
function custom_auth_plugin_enqueue_styles() {
    wp_enqueue_style('custom-auth-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'custom_auth_plugin_enqueue_styles');
