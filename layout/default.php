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
 * Layout - default.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require(__DIR__.'/header.php');

use theme_snap\local;

// @codingStandardsIgnoreStart
// Note, coding standards ignore is required so that we can have more readable indentation under php tags.

$mastimage = '';
// Check we are in a course (not the site level course), and the course is using a cover image.
if ($COURSE->id != SITEID && !empty($coverimagecss)) {
    $mastimage = 'mast-image';
}
?>

<!-- Moodle js hooks -->
<div id="page">
<div id="page-content">

<!--
////////////////////////// MAIN  ///////////////////////////////
-->
<main id="moodle-page" class="clearfix">
<div id="page-header" class="clearfix <?php echo $mastimage; ?>">
    <?php if ($PAGE->pagetype !== 'site-index') { ?>
        <div class="breadcrumb-nav" aria-label="breadcrumb"><?php echo $OUTPUT->navbar($mastimage); ?></div>
    <?php }
        if ($carousel) {
            // Front page carousel.
            echo $carousel;
        } else {
            // Front page banner image.
    ?>
        <div id="page-mast">
        <?php
            echo $OUTPUT->page_heading();
            echo $OUTPUT->course_header();
            if ($PAGE->pagetype === 'site-index') {
                echo $OUTPUT->login_button();
            }
        ?>
        </div>
        <?php
            if ($this->page->user_is_editing() && $PAGE->pagetype == 'site-index') {
                echo $OUTPUT->cover_image_selector();
            }
        } // End else.
    ?>
</div>

<section id="region-main">
<?php
echo $OUTPUT->course_content_header();

// Ensure edit blocks button is only shown for appropriate pages.
$hasadminbutton = stripos($PAGE->button, '"adminedit"') || stripos($PAGE->button, '"edit"');

if ($hasadminbutton) {
    // List paths to black list for 'turn editting on' button here.
    // Note, to use regexs start and end with a pipe symbol - e.g. |^/report/| .
    $editbuttonblacklist = array(
        '/comment/',
        '/cohort/index.php',
        '|^/report/|',
        '|^/admin/|',
        '|^/mod/data/|',
        '/tag/manage.php',
        '/grade/edit/scale/index.php',
        '/outcome/admin.php',
        '/mod/assign/adminmanageplugins.php',
        '/message/defaultoutputs.php',
        '/theme/index.php',
        '/user/editadvanced.php',
        '/user/profile/index.php',
        '/mnet/service/enrol/index.php',
        '/local/mrooms/view.php',
        '/local/xray/view.php'
    );
    $pagepath = local::current_url_path();

    foreach ($editbuttonblacklist as $blacklisted) {
        if ($blacklisted[0] == '|' && $blacklisted[strlen($blacklisted) - 1] == '|') {
            // Use regex to determine blacklisting.
            if (preg_match ($blacklisted, $pagepath) === 1) {
                // This url path is blacklisted, stop button from being displayed.
                $PAGE->set_button('');
            }
        } else if ($pagepath == $blacklisted) {
            // This url path is blacklisted, stop button from being displayed.
            $PAGE->set_button('');
        }
    }
}

echo $OUTPUT->page_heading_button();

if ($PAGE->pagelayout === 'frontpage' && $PAGE->pagetype === 'site-index') {
    require(__DIR__.'/faux_site_index.php');
} else {
    echo $OUTPUT->main_content();
}

echo $OUTPUT->activity_navigation();
echo $OUTPUT->course_content_footer();

if (stripos($PAGE->bodyclasses, 'format-singleactivity') !== false ) {
    // Shared renderer is only loaded if required at this point.
    $output = \theme_snap\output\shared::course_tools();
    if (!empty($output)) {
        echo $output;
    }
}

?>

</section>

<?php require(__DIR__.'/moodle-blocks.php'); ?>
</main>

</div>
</div>
<!-- close moodle js hooks -->
<?php // @codingStandardsIgnoreEnd
require(__DIR__.'/footer.php');
