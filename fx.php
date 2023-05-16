<?php
/**
 * Get unique key for a user
 * @param WP_User|integer $user Optional. User to update. Can be either user ID or WP_User object. If empty will use logged in user.
 * @return string|boolean Key for specified user or false if user doesn't exist.
 */
function bbconnect_personalisation_get_key_for_user($user = null) {
    if (empty($user)) {
        $user = get_current_user_id();
    }
    if (!empty($user)) {
        if (is_numeric($user)) {
            $user = get_user_by('id', $user);
        }
        if ($user instanceof WP_User) {
            $key = get_user_meta($user->ID, 'bbconnect_personalisation_key', true);
            if (empty($key)) {
                $key = bbconnect_personalisation_generate_key($user->ID);
            }
            return $key;
        }
    }
    return false;
}

/**
 * Get user based on unique key
 * @param string $key Optional. If empty will attempt to read from querystring.
 * @return WP_User|boolean User object on success or false on failure.
 */
function bbconnect_personalisation_get_user_for_key($key = null) {
    if (empty($key) && isset($_GET['bbpk'])) {
        $key = sanitize_text_field($_GET['bbpk']);
    }
    if (!empty($key)) {
        $args = array(
                'number' => 1,
                'meta_query' => array(
                        array(
                                'key' => 'bbconnect_personalisation_key',
                                'value' => $key,
                        ),
                ),
        );
        $users = get_users($args);
        if (!empty($users)) {
            $user = array_shift($users);
	        return $user;
        }
    }
    return false;
}

add_action('user_register', 'bbconnect_personalisation_generate_key');
/**
 * Generate a unique user key
 * @param integer $user_id Optional. If specified the generated key will be stored against the user. You generally don't want to do this as it will overwrite any previous key they already have.
 * @return string
 */
function bbconnect_personalisation_generate_key($user_id = null) {
    do {
        $key = wp_generate_password(32, false);
    } while (bbconnect_personalisation_get_user_for_key($key) !== false);

    if ($user_id) {
        update_user_meta($user_id, 'bbconnect_personalisation_key', $key);
    }

    return $key;
}

add_action('bbconnect_personalisation_generate_keys_for_all_users', 'bbconnect_personalisation_generate_keys_for_all_users');
/**
 * Generate keys for all users who don't already have one. Don't call this function directly - it's run automatically when the plugin activates and then every hour after that.
 */
function bbconnect_personalisation_generate_keys_for_all_users() {
    if (empty(get_option('bbconnect_personalisation_generate_lock'))) {
        // Generate a unique key for all existing users
        set_time_limit(600);
        $args = array(
        		'meta_query' => array(
		        		'relation' => 'OR',
		        		array(
		        				'key' => 'bbconnect_personalisation_key',
		        				'compare' => 'NOT EXISTS',
		        		),
		        		array(
		        				'key' => 'bbconnect_personalisation_key',
		        				'value' => '',
		        		),
		        )
        );
        $users = get_users($args);
        foreach ($users as $user) {
            set_time_limit(300);
            update_option('bbconnect_personalisation_generate_lock', $user->ID);
            bbconnect_personalisation_get_key_for_user($user);
	        delete_option('bbconnect_personalisation_generate_lock');
        }
    }
}

add_filter('bbconnect_field_disabled', 'bbconnect_personalisation_field_disabled', 10, 2);
/**
 * Disable unique key field from user input
 * @param boolean $disabled
 * @param string $key
 * @return boolean
 */
function bbconnect_personalisation_field_disabled($disabled, $key) {
    if ($key == 'personalisation_key') {
        return true;
    }
    return $disabled;
}

add_filter('bbconnect_identify_user', 'bbconnect_personalisation_identify_user');
/**
 * Check for key and find matching user
 * @param mixed $user Previously identified user
 * @return WP_User|mixed User identified by key else original user
 */
function bbconnect_personalisation_identify_user($user) {
    $key_user = bbconnect_personalisation_get_user_for_key();
    if ($key_user instanceof WP_User) {
        return $key_user;
    }
    return $user;
}

add_filter('bbconnect_kpi_cron_mailchimp_push_data', 'bbconnect_personalisation_kpi_cron_push_data', 10, 3);
function bbconnect_personalisation_kpi_cron_push_data($push_data, $user, $kpi_prefix) {
	$push_data['KEY'] = bbconnect_personalisation_get_key_for_user($user);
	return $push_data;
}
