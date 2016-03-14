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
 * Redirect from recommendation to resource itself, after recording into database the recommendation view.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('classes/importer/import_form.php');
require_once('classes/importer/csv_importer.php');

global $CFG, $COURSE;

/**
 * Initializes the page.
 *
 * @return int Course id of current course.
 */
function init_page() {
    global $DB, $PAGE;

    require_login();
    $courseid = required_param('courseid', PARAM_INT);
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

    $PAGE->set_context(context_course::instance($courseid));
    $PAGE->set_url('/blocks/mycourse_recommendations/import_csv.php', array('courseid' => $courseid));
    $PAGE->set_title(get_string('upload_title', 'block_mycourse_recommendations'));
    $PAGE->set_pagelayout('course');

    return $courseid;
}

$courseid = init_page();

$actionurl = $_SERVER['PHP_SELF'] . "?courseid=$courseid";

echo $OUTPUT->header();
echo $OUTPUT->navbar();

$form = new \block_mycourse_recommendations\import_form($actionurl);
$formdata = $form->get_data();

if ($formdata) {
    $coursefile = $form->get_file_content('courses');
    $usersfile = $form->get_file_content('users');
    $logsfile = $form->get_file_content('logs');

    \block_mycourse_recommendations\csv_importer::import_data($formdata, $coursefile, $usersfile, $logsfile);
} else {
    $form->display();
}

echo $OUTPUT->footer();
