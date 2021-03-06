<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The form to insert the historic data from CSVs.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('../../lib/outputcomponents.php');
require_once('classes/importer/import_form.php');
require_once('classes/importer/csv_importer.php');

global $CFG, $COURSE;

/**
 * Initializes the page.
 *
 * @return int Course id of current course.
 */
function init_page() {
    global $PAGE;

    require_login();
    $courseid = required_param('courseid', PARAM_INT);

    $context = context_course::instance($courseid);

    require_capability('block/mycourse_recommendations:importfromcsv', $context);

    $PAGE->set_context($context);
    $PAGE->set_url('/blocks/mycourse_recommendations/import_csv.php', array('courseid' => $courseid));
    $PAGE->set_title(get_string('upload_title', 'block_mycourse_recommendations'));
    $PAGE->set_pagelayout('course');

    return $courseid;
}

/**
 * Prints the summary of the made insertions.
 */
function print_success_summary() {
    echo html_writer::start_tag('h4');
    echo get_string('success', 'block_mycourse_recommendations');
    echo html_writer::end_tag('h4');
    echo html_writer::start_tag('hr');

    echo 'Summary of importation:';
    echo html_writer::start_tag('br');

    $insertedcourses = \block_mycourse_recommendations\csv_importer::get_lastinsertedcourses();
    $insertedcourses = get_string('importedcourses', 'block_mycourse_recommendations') . $insertedcourses;

    $insertedusers = \block_mycourse_recommendations\csv_importer::get_lastinsertedusers();
    $insertedusers = get_string('importedusers', 'block_mycourse_recommendations') . $insertedusers;

    $insertedlogs = \block_mycourse_recommendations\csv_importer::get_lastinsertedlogs();
    $insertedlogs = get_string('importedlogs', 'block_mycourse_recommendations') . $insertedlogs;

    echo html_writer::alist(array($insertedcourses, $insertedusers, $insertedlogs));
}

$courseid = init_page();

$actionurl = $_SERVER['PHP_SELF'] . "?courseid=$courseid";

echo $OUTPUT->header();
echo $OUTPUT->navbar();

$form = new \block_mycourse_recommendations\import_form($actionurl);
$formdata = $form->get_data();

// If the form has submitted, this branch is entered, where the data is imported using the csv importer.
if ($formdata) {
    $coursefile = $form->get_file_content('courses');
    $usersfile = $form->get_file_content('users');
    $logsfile = $form->get_file_content('logs');

    try {
        \block_mycourse_recommendations\csv_importer::import_data($formdata, $coursefile, $usersfile, $logsfile, $courseid);
        print_success_summary();
    } catch (Exception $e) {
        echo get_string('errorimporting', 'block_mycourse_recommendations');
        echo $e->getMessage();
        echo html_writer::start_tag('br');
        echo $e->getTraceAsString();
    }
    // Display the form if we're not handling the submission.
} else {
    $form->display();
}

echo $OUTPUT->footer();
