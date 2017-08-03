<?php
/**
 * Get unique key for a user
 * @param integer $user_id Optional. If empty will use logged in user.
 * @return string|boolean Key for specified user or false if user doesn't exist.
 */
function bbconnect_personalisation_get_key_for_user($user_id = null) {
    if (empty($user_id)) {
        $user_id = get_current_user_id();
    }
    if (!empty($user_id)) {
        $user = get_user_by('id', $user_id);
        if ($user instanceof WP_User) {
            $key = get_user_meta($user_id, 'bbconnect_personalisation_key', true);
            if (empty($key)) {
                $key = bbconnect_personalisation_generate_key($user_id);
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
        $key = $_GET['bbpk'];
    }
    if (!empty($key)) {
        $args = array(
                'meta_query' => array(
                        array(
                                'key' => 'bbconnect_personalisation_key',
                                'value' => $key,
                        ),
                ),
        );
        $users = get_users($args);
        if (count($users)) {
            return array_shift($users);
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
