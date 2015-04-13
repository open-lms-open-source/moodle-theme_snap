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
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
include(__DIR__.'/header.php');
?>
<!-- moodle js hooks -->
<div id="page">
<div id="page-content">

<!--
////////////////////////// MAIN  ///////////////////////////////
-->
<main id="moodle-page" class="clearfix">
<div id="page-header" class="clearfix
<?php if (!empty($courseimagecss)) : ?>
 mast-image
<?php endif;?>">
<div class="breadcrumb-nav" aria-label="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
<div id="page-mast">
<?php
echo $OUTPUT->page_heading();
echo $OUTPUT->course_header();
?>
</div>
<?php
echo $OUTPUT->print_settings_link();
if ($this->page->user_is_editing() && $PAGE->pagetype == 'site-index') {
    echo '<a class="btn btn-default btn-sm" href="'.$CFG->wwwroot.'/admin/settings.php?section=themesettingsnap#admin-poster">'.get_string('changecoverimage', 'theme_snap').'</a>';
}
?>
</div>

<section id="region-main">
<?php
echo $OUTPUT->course_content_header();

// Ensure edit blocks button is only shown for appropriate pages.
$hasadminbutton = stripos($PAGE->button, '"adminedit"') || stripos($PAGE->button, '"edit"');

if($hasadminbutton) {
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

        '/my/indexsys.php',
        '/mnet/service/enrol/index.php',
        '/local/mrooms/view.php'
    );
    $pagepath = $PAGE->url->get_path();

    foreach ($editbuttonblacklist as $blacklisted){
        if ($blacklisted[0] == '|'
            && $blacklisted[strlen($blacklisted)-1] == '|'
        ) {
            // Use regex to determine blacklisting.
            if (preg_match ($blacklisted, $pagepath) === 1) {
                // This url path is blacklisted, stop button from being displayed.
                $PAGE->set_button('');
            }

        } else if ($pagepath== $blacklisted){
            // This url path is blacklisted, stop button from being displayed.
            $PAGE->set_button('');
        }
    }
}

echo $OUTPUT->page_heading_button();

// On the front page, output some different content
if($PAGE->pagetype == 'site-index') {
    include(__DIR__.'/faux_site_index.php');
}
else {
    echo $OUTPUT->main_content();
}

echo $OUTPUT->course_content_footer();
?>
</section>

<?php include(__DIR__.'/moodle-blocks.php'); ?>
</main>

</div>
</div>
<!-- close moodle js hooks -->

<?php include(__DIR__.'/footer.php'); ?>
