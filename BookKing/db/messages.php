<?php

/**
 * Defines message providers (types of messages being sent)
 *
 */

defined('MOODLE_INTERNAL') || die();

$messageproviders = array (

    // Invitations to make a booking.
    'invitation' => array(
    ),

    // Notifications about bookings (to teachers or students).
    'bookingnotification' => array(
    ),

    // Automated reminders about upcoming appointments.
    'reminder' => array(
    ),

);
