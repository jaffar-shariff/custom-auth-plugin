<?php
/*
Plugin Name: Custom Auth Plugin
Description: A simple plugin for custom user authentication and session management.
Version: 1.0
Author: Jaffar Shariff
*/

// Enqueue plugin CSS
function custom_auth_plugin_enqueue_styles() {
    wp_enqueue_style('custom-auth-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'custom_auth_plugin_enqueue_styles');

// Load Google reCAPTCHA API
function custom_auth_plugin_enqueue_scripts() {
    wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], null, true);
}
add_action('wp_enqueue_scripts', 'custom_auth_plugin_enqueue_scripts');

// Helper function to verify reCAPTCHA response
function custom_auth_plugin_verify_recaptcha($response) {
    $secret_key = '6LeqqvwrAAAAAPvkoTk7iG4Mj0tTUK2aFbx90TU1';  // replace with your real secret key
    $remote_ip = $_SERVER['REMOTE_ADDR'];

    $verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
            'secret' => $secret_key,
            'response' => $response,
            'remoteip' => $remote_ip,
        ]
    ]);

    if (is_wp_error($verify)) {
        return false;
    }

    $body = wp_remote_retrieve_body($verify);
    $result = json_decode($body, true);
    return isset($result['success']) && $result['success'] === true;
}

// Shortcode to display login form
function custom_login_form_shortcode() {
    if (is_user_logged_in()) {
        return '<p>You are already logged in.</p>';
    }

    $html = '<form method="post" id="custom-login-form" class="custom-auth-form">';
    $html .= wp_nonce_field('custom_login_action', 'custom_login_nonce', true, false);
    $html .= '<p><label for="username">Username</label><br />';
    $html .= '<input type="text" name="username" id="username" placeholder="Enter your username" autocomplete="username" required /></p>';
    $html .= '<p><label for="password">Password</label><br />';
    $html .= '<input type="password" name="password" id="password" placeholder="Enter your password" autocomplete="current-password" required /></p>';

    // Add Google reCAPTCHA widget
    $html .= '<div class="g-recaptcha" data-sitekey="6LeqqvwrAAAAAD2ABOdno8y2DKJuKEWOQ3krRCLn"></div>';

    $html .= '<p><input type="submit" name="custom_login_submit" id="custom-login-submit" value="Login" /></p>';
    $html .= '</form>';

    // Frontend validation, loading state for login form
    $html .= <<<EOD
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('custom-login-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const username = form.querySelector('input[name="username"]').value.trim();
        const password = form.querySelector('input[name="password"]').value.trim();
        const recaptcha = form.querySelector('.g-recaptcha-response').value;

        if (username === '' || password === '') {
            alert('Please fill out both username and password.');
            e.preventDefault();
            return;
        }
        if (recaptcha === '') {
            alert('Please verify that you are not a robot.');
            e.preventDefault();
            return;
        }

        const submitBtn = form.querySelector('input[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.value = 'Please wait…';
    });
});
</script>
EOD;

    if (isset($_POST['custom_login_submit'])) {
        if (!isset($_POST['custom_login_nonce']) || !wp_verify_nonce($_POST['custom_login_nonce'], 'custom_login_action')) {
            $html .= '<p class="error-message">Invalid form submission detected.</p>';
            return $html;
        }

        if (!isset($_POST['g-recaptcha-response']) || !custom_auth_plugin_verify_recaptcha($_POST['g-recaptcha-response'])) {
            $html .= '<p class="error-message">reCAPTCHA verification failed. Please try again.</p>';
            return $html;
        }

        $user = wp_signon([
            'user_login'    => sanitize_text_field($_POST['username']),
            'user_password' => sanitize_text_field($_POST['password']),
            'remember'      => true,
        ], false);

        if (is_wp_error($user)) {
            $html .= '<p class="error-message">Login failed: ' . $user->get_error_message() . '</p>';
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

    $html = '<form method="post" id="custom-registration-form" class="custom-auth-form">';
    $html .= wp_nonce_field('custom_register_action', 'custom_register_nonce', true, false);
    $html .= '<p><label for="reg_username">Username</label><br />';
    $html .= '<input type="text" name="reg_username" id="reg_username" placeholder="Choose a username" autocomplete="username" required /></p>';
    $html .= '<p><label for="reg_email">Email</label><br />';
    $html .= '<input type="email" name="reg_email" id="reg_email" placeholder="Enter your email" autocomplete="email" required /></p>';
    $html .= '<p><label for="reg_password">Password</label><br />';
    $html .= '<input type="password" name="reg_password" id="reg_password" placeholder="Set a password" autocomplete="new-password" required /></p>';

    // Add Google reCAPTCHA widget
    $html .= '<div class="g-recaptcha" data-sitekey="6LeqqvwrAAAAAD2ABOdno8y2DKJuKEWOQ3krRCLn"></div>';

    $html .= '<p><input type="submit" name="custom_register_submit" id="custom-register-submit" value="Register" /></p>';
    $html .= '</form>';

    // Frontend validation, loading state for registration form
    $html .= <<<EOD
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('custom-registration-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const username = form.querySelector('input[name="reg_username"]').value.trim();
        const email = form.querySelector('input[name="reg_email"]').value.trim();
        const password = form.querySelector('input[name="reg_password"]').value.trim();
        const recaptcha = form.querySelector('.g-recaptcha-response').value;

        if (username.length < 3) {
            alert('Username must be at least 3 characters.');
            e.preventDefault();
            return;
        }
        const emailPattern = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;
        if (!emailPattern.test(email)) {
            alert('Please enter a valid email address.');
            e.preventDefault();
            return;
        }
        if (password.length < 6) {
            alert('Password must be at least 6 characters.');
            e.preventDefault();
            return;
        }
        if (recaptcha === '') {
            alert('Please verify that you are not a robot.');
            e.preventDefault();
            return;
        }

        const submitBtn = form.querySelector('input[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.value = 'Please wait…';
    });
});
</script>
EOD;

    if (isset($_POST['custom_register_submit'])) {
        if (!isset($_POST['custom_register_nonce']) || !wp_verify_nonce($_POST['custom_register_nonce'], 'custom_register_action')) {
            $html .= '<p class="error-message">Invalid form submission detected.</p>';
            return $html;
        }

        if (!isset($_POST['g-recaptcha-response']) || !custom_auth_plugin_verify_recaptcha($_POST['g-recaptcha-response'])) {
            $html .= '<p class="error-message">reCAPTCHA verification failed. Please try again.</p>';
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
                echo '<p class="success-message">Registration successful! You can now <a href="' . wp_login_url() . '">log in</a>.</p>';
                return;
            }
        } else {
            foreach ($errors->get_error_messages() as $msg) {
                $html .= '<p class="error-message">' . esc_html($msg) . '</p>';
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

// Shortcode to show password reset form with reCAPTCHA and loading state
function custom_password_reset_shortcode() {
    if (is_user_logged_in()) {
        return '<p>You are logged in. No need to reset password.</p>';
    }

    ob_start();

    if (isset($_POST['custom_password_reset_email'])) {
        if (!isset($_POST['custom_password_reset_nonce']) || !wp_verify_nonce($_POST['custom_password_reset_nonce'], 'custom_password_reset_action')) {
            echo '<p class="error-message">Invalid form submission detected.</p>';
            return ob_get_clean();
        }

        if (!isset($_POST['g-recaptcha-response']) || !custom_auth_plugin_verify_recaptcha($_POST['g-recaptcha-response'])) {
            echo '<p class="error-message">reCAPTCHA verification failed. Please try again.</p>';
            return ob_get_clean();
        }

        $email = sanitize_email($_POST['custom_password_reset_email']);
        if (!email_exists($email)) {
            echo '<p class="error-message">Email does not exist in our records.</p>';
        } else {
            retrieve_password($email);
            echo '<p class="success-message">Password reset instructions sent to your email.</p>';
        }
    }
    ?>
    <form method="post" id="custom-password-reset-form" class="custom-auth-form">
        <?php wp_nonce_field('custom_password_reset_action', 'custom_password_reset_nonce', true, false); ?>
        <p><label for="custom_password_reset_email">Enter your email</label><br />
        <input type="email" name="custom_password_reset_email" id="custom_password_reset_email" placeholder="Enter your email" autocomplete="email" required /></p>
        <div class="g-recaptcha" data-sitekey="6LeqqvwrAAAAAD2ABOdno8y2DKJuKEWOQ3krRCLn"></div>
        <p><input type="submit" value="Reset Password" id="custom-password-reset-submit" /></p>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('custom-password-reset-form');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            const emailInput = form.querySelector('input[name="custom_password_reset_email"]');
            const recaptcha = form.querySelector('.g-recaptcha-response').value;

            if (!emailInput || emailInput.value.trim() === '') {
                alert('Please enter your email.');
                e.preventDefault();
                return;
            }

            const email = emailInput.value.trim();
            const emailPattern = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;

            if (!emailPattern.test(email)) {
                alert('Please enter a valid email address.');
                e.preventDefault();
                return;
            }

            if (recaptcha === '') {
                alert('Please verify that you are not a robot.');
                e.preventDefault();
                return;
            }

            const submitBtn = form.querySelector('input[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.value = 'Please wait…';
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_password_reset', 'custom_password_reset_shortcode');
