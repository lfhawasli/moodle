<?php
// CCLE-3794
// Script to email admins when event queue is not being processed


// Satisfy Moodle's requirement for running CLI scripts
define('CLI_SCRIPT', true);

$moodleroot = dirname(dirname(dirname(dirname(__FILE__)))); 
require($moodleroot . '/config.php');
require($moodleroot . '/local/ucla/lib.php');
require($CFG->libdir . '/clilib.php');

// Support a 'tolerance' param
// Will allow admin to set a value threshold for retry count
list($ext_argv, $unrecog) = cli_get_params(
    array(
        'tolerance' => false,
        'maxcount' => false,
    ),
    array(
        't' => 'tolerance',
        'x' => 'maxcount',
    )
);

// Default values
$default_tolerance = 5;
$default_display = 20;
$defaultmaxcount = 30;

$tolerance = (!empty($ext_argv['tolerance']) && !empty($unrecog[0]))
    ? $unrecog[0] : $default_tolerance;

$maxcount = (!empty($ext_argv['maxcount']) && !empty($unrecog[1]))
    ? $unrecog[1] : $defaultmaxcount;

try {

    // Find records with the 'status' count that's greater than the tolerance
    $records = $DB->get_records_select('events_queue_handlers', 'status >= :limit',
            array('limit' => $tolerance));

    // Get count of event records.
    $totalrecords = $DB->count_records('events_queue_handlers');

    // If we find such records, notify admins
    if(!empty($records) || $totalrecords > $maxcount) {

        $out = "";
        if (!empty($records)) {
            $count = count($records);
            // Only display a small sampling.  Event queue backlog can grow
            // rather large.
            if($count <= $default_display) {
                foreach($records as $r) {
                    $out .= json_encode($r, JSON_PRETTY_PRINT);
                    $out .= "\n\n";
                }
            } else {
                $out = "There are more than $default_display records to display!";
            }
        }

        // Prepare message.
        $message = '';
        if (!empty($records)) {
            $message = "There are $count failed events in the queue that have been retried more than $tolerance times: \n";
            $message .= "------------------\n";
            $message .= $out;
        }
        if ($totalrecords > $maxcount) {
            $message = "There are $totalrecords total events in the queue, which is more than our threshold of $maxcount.\n";
        }

        $subject = "Total events in queue: " . $totalrecords;
        $to = get_config('local_ucla', 'admin_email');
        if (empty($to)) {
            // variable not properly set
            cli_error("Event queue check: Error -- you're missing the 'to' email field!");
        }

        cli_problem("Event queue check: Event queue has grown too much, email sent");
        return ucla_send_mail($to, $subject, $message);
    }
    
    echo "Event queue check: Successfully ran the script\n";
    return 0;
    
} catch (Exception $e) {
    // DB error
    cli_error("Event queue check: There was an error in the script.");
}
