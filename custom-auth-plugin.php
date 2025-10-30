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
    $html .= '<p><label for="username">Username</label><br />';
    $html .= '<input type="text" name="username" id="username" required /></p>';
    $html .= '<p><label for="password">Password</label><br />';
    $html .= '<input type="password" name="password" id="password" required /></p>';
    $html .= '<p><input type="submit" name="custom_login_submit" value="Login" /></p>';
    $html .= '</form>';

    if (isset($_POST['custom_login_submit'])) {
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
    $html .= '<p><label for="reg_username">Username</label><br />';
    $html .= '<input type="text" name="reg_username" id="reg_username" required /></p>';
    $html .= '<p><label for="reg_email">Email</label><br />';
    $html .= '<input type="email" name="reg_email" id="reg_email" required /></p>';
    $html .= '<p><label for="reg_password">Password</label><br />';
    $html .= '<input type="password" name="reg_password" id="reg_password" required /></p>';
    $html .= '<p><input type="submit" name="custom_register_submit" value="Register" /></p>';
    $html .= '</form>';

    if (isset($_POST['custom_register_submit'])) {
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
