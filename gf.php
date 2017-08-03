<?php
add_filter('gform_pre_render', 'bbconnect_personalisation_populate_form');
/**
 * Pre-render form with user data based on key
 * @param array $form
 * @return array $form
 */
function bbconnect_personalisation_populate_form($form) {
    $user = bbconnect_personalisation_get_user_for_key();
    if ($user instanceof WP_User) {
        // Get user meta for later
        $usermeta = get_user_meta($user->ID);

        // Phone numbers are a special case
        $phone_number = '';
        $phone_data = maybe_unserialize($usermeta['telephone'][0]);
        foreach ($phone_data as $existing_phone) {
            if (!empty($existing_phone['value'])) {
                $phone_number = $existing_phone['value'];
                break;
            }
        }
        foreach ($form['fields'] as &$field) {
            // Standard fields
            switch ($field->type) {
                case 'email':
                    $field->defaultValue = $user->user_email;
                    break;
                case 'name':
                    foreach ($field->inputs as &$input) {
                        if ($input['id'] == $field->id.'.3') {
                            $input['defaultValue'] = $user->user_firstname;
                        } elseif ($input['id'] == $field->id.'.6') {
                            $input['defaultValue'] = $user->user_lastname;
                        }
                    }
                    break;
            }

            // Fields mapped to user meta
            if (!empty($field->inputs)) {
                foreach ($field->inputs as &$input) {
                    if (!empty($input['usermeta_key'])) {
                        switch ($input['usermeta_key']) {
                            case 'telephone':
                                $input['defaultValue'] = $phone_number;
                                break;
                            default:
                                if (isset($usermeta[$input['usermeta_key']])) {
                                    $input['defaultValue'] = $usermeta[$input['usermeta_key']][0];
                                }
                                break;
                        }
                    }
                }
            } elseif (!empty($field->usermeta_key)) {
                switch ($field->usermeta_key) {
                    case 'telephone':
                        $field->defaultValue = $phone_number;
                        break;
                    default:
                        if (isset($usermeta[$field->usermeta_key])) {
                            $field->defaultValue = $usermeta[$field->usermeta_key][0];
                        }
                        break;
                }
            }
        }
    }
    return $form;
}
