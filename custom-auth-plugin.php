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
