<?php



defined('MOODLE_INTERNAL') || die();

/**
 * Get a list of fields to be displayed in lists of users, etc.
 *
 * The input of the function is a user record;
 * possibly null, in this case the function should return only the field titles.
 *
 * The function returns an array of objects that describe user data fields.
 * Each of these objects has the following properties:
 *  $field->title : Displayable title of the field
 *  $field->value : Value of the field for this user (not set if $user is null)
 *
 * @param stdClass $user the user record; may be null
 * @param context $context context for permission checks
 * @return array an array of field objects
 */
function bookking_get_user_fields($user, $context) {

    $fields = array();

    if (has_capability('moodle/site:viewuseridentity', $context)) {
        $emailfield = new stdClass();
        $fields[] = $emailfield;
        $emailfield->title = get_string('email');
        if ($user) {
            $emailfield->value = obfuscate_mailto($user->email);
        }
    }

    /*
     * As an example: Uncomment the following lines in order to display the user's city and country.
     */

    /*
    $cityfield = new stdClass();
    $cityfield->title = get_string('city');
    $fields[] = $cityfield;

    $countryfield = new stdClass();
    $countryfield->title = get_string('country');
    $fields[] = $countryfield;

    if ($user) {
        $cityfield->value = $user->city;
        if ($user->country) {
            $countryfield->value = get_string($user->country, 'countries');
        }
        else {
            $countryfield->value = '';
        }
    }
    */
    return $fields;
}
