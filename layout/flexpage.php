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
 * Layout - courses with flexpage format.
 * This layout is baed on a moodle site index.php file but has been adapted to show news items in a different
 * way.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/flexpage/locallib.php');

// Require standard javascript libs.
\theme_snap\output\shared::page_requires_js();

$hasheading = ($PAGE->heading);
$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
$hasfooter = (empty($PAGE->layout_options['nofooter']));
$hassidetop = (empty($PAGE->layout_options['noblocks']) && $PAGE->blocks->region_has_content('side-top', $OUTPUT));
$hassidepre = (empty($PAGE->layout_options['noblocks']) && $PAGE->blocks->region_has_content('side-pre', $OUTPUT));
$hassidepost = (empty($PAGE->layout_options['noblocks']) && $PAGE->blocks->region_has_content('side-post', $OUTPUT));
$haslogininfo = (empty($PAGE->layout_options['nologininfo']));

$showsidepre = ($hassidepre && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT));
$showsidepost = ($hassidepost && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT));

// Always show block regions when editing so blocks can
// be dragged into empty block regions.
if ($PAGE->user_is_editing()) {
    if ($PAGE->blocks->is_known_region('side-pre')) {
        $showsidepre = true;
        $hassidepre  = true;
    }
    if ($PAGE->blocks->is_known_region('side-post')) {
        $showsidepost = true;
        $hassidepost  = true;
    }
    if ($PAGE->blocks->is_known_region('side-top')) {
        $hassidetop = true;
    }
}

$custommenu = $OUTPUT->custom_menu();
$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));

$courseheader = $coursecontentheader = $coursecontentfooter = $coursefooter = '';
if (empty($PAGE->layout_options['nocourseheaderfooter'])) {
    $courseheader = $OUTPUT->course_header();
    $coursecontentheader = $OUTPUT->course_content_header();
    if (empty($PAGE->layout_options['nocoursefooter'])) {
        $coursecontentfooter = $OUTPUT->course_content_footer();
        $coursefooter = $OUTPUT->course_footer();
    }
}

$bodyclasses = array();
if ($showsidepre && !$showsidepost) {
    if (!right_to_left()) {
        $bodyclasses[] = 'side-pre-only';
    } else {
        $bodyclasses[] = 'side-post-only';
    }
} else if ($showsidepost && !$showsidepre) {
    if (!right_to_left()) {
        $bodyclasses[] = 'side-post-only';
    } else {
        $bodyclasses[] = 'side-pre-only';
    }
} else if (!$showsidepost && !$showsidepre) {
    $bodyclasses[] = 'content-only';
}
if ($hascustommenu) {
    $bodyclasses[] = 'has_custom_menu';
}
$bodyclasses[] = 'format-flexpage';

echo $OUTPUT->doctype() ?>

<html <?php echo $OUTPUT->htmlattributes() ?>>
<head>
<title><?php echo $OUTPUT->page_title(); ?></title>
<link rel="shortcut icon" href="<?php echo $OUTPUT->favicon() ?>"/>
<?php echo $OUTPUT->standard_head_html() ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href='http://fonts.googleapis.com/css?family=Roboto:500,100,400,300' rel='stylesheet' type='text/css'>

    <?php echo format_flexpage_default_width_styles() ?>

    <style>
   #region-top {
        width: 100%;
        clear: both;
    }
    #page-content #region-pre,
    #page-content #region-post,
    #page-content #region-main-box {
        float: left;
        width: 25%;
        left: 0;
    }

    #page-content #region-main-box {
        width: 46%;
        margin: 0 2%;
    }


    .side-pre-only #page-content #region-main-box,
    .side-post-only #page-content #region-main-box {
        width: 70%;
        margin: 0 auto;
    }

    .block_settings {
        width: auto;
        height: auto;
        visibility: visible;
        position: relative;
        background-color: #FFF !important;
        right: 0;
    }

   .flexpage_actionbar {
       margin: 5px 10px;
    }

   .flexpage_prev_next #format_flexpage_next_page {
       display: block;
       float: right;
    }

   /* Makes the target larger for when you are dragging blocks into an empty top region */
   .format-flexpage #region-top .block-region {
       min-height: 20px;
   }

    .course-content {
        max-width: none;
    }

    .format_flexpage_tabs  #custommenu {
         padding-left: 10px;
         padding-right: 10px;
    }

    .smallicon {
        width: 16px;
        height: 16px;
    }

    .groupinglabel {
        display: inline;
    }

    .section .activity .actions {
        display: block;
        position: relative;
        overflow: show;
    }

    .section .activity .actions .menu {
        min-width: 16em;
        max-width: 360px;
    }

    .editing .block_flexpagemod_default li.activity .commands,
    .editing .block_flexpagemod_commands .commands {
        opacity: 1 !important;
    }
    .toggle-display .caret {
        display: inline;
    }

    .format-flexpage .moodle-actionmenu[data-enhanced].show {
        width: 100%;
    }
    .section li.activity {
        min-height: 0;
        height: auto;
        background-image: none;
        box-shadow: none;
    }
    .snap-assettype,
    .draft_info,
    .activityinstance .conditional_info,
    .conditional_info {
        display: none;
    }
    .toggle-display.textmenu:after{
        display: none;
    }

    .block-region {
        min-height: 150px;
    }

    </style>
</head>

<body id="<?php p($PAGE->bodyid) ?>" class="<?php p($PAGE->bodyclasses.' '.join(' ', $bodyclasses)) ?>">
<?php echo $OUTPUT->standard_top_of_body_html() ?>

<?php require(__DIR__.'/nav.php'); ?>


<!-- moodle js hooks -->
<div id="page">
<div id="page-content">



<!--
////////////////////////// MAIN  ///////////////////////////////
-->
<main id="moodle-page" class="clearfix">

<header id="page-header" class="clearfix">
<nav class="breadcrumb-nav" role="navigation" aria-label="breadcrumb"><?php echo $OUTPUT->navbar(); ?></nav>
<div id="page-mast">
<?php
// Output flex page front page warning if necessary.
if ($PAGE->pagetype == 'site-index') {
    echo \theme_snap\output\shared::flexpage_frontpage_warning(true);
} else if (strpos($PAGE->pagetype, 'course-view-') === 0) {
    $output = $PAGE->get_renderer('core', 'course');
    echo $output->course_format_warning();
}
echo $OUTPUT->page_heading();
echo $OUTPUT->course_header();
?>
</div>

</header>

<?php
if ($PAGE->user_allowed_editing()) {
    if ($COURSE->id == SITEID) {
        $url = new moodle_url('/course/view.php', array('id' => SITEID));
        echo $OUTPUT->edit_button($url);
    } else {
        echo $OUTPUT->edit_button($PAGE->url);
    }
}
?>

<!-- flexpage tab bar -->
<?php echo format_flexpage_tabs(); ?>

<!-- Flexpage editing menu/custommenu -->
<div id="flexpage_actionbar" class="flexpage_actionbar clearfix">
    <?php echo $OUTPUT->main_content(); ?>
</div>


<!-- top box -->
<?php if ($hassidetop) { ?>
<div id="region-top" class="block-region">
    <!-- This is bad - we have to have a region-content div for drag and drop to work! -->
    <div class="region-content">
        <?php echo $OUTPUT->blocks('side-top'); ?>
    </div>
</div>
<?php } ?>


<!-- next / previous buttons -->
<?php if (format_flexpage_has_next_or_previous()) { ?>
<div class="flexpage_prev_next">
<?php
    echo format_flexpage_previous_button();
    echo format_flexpage_next_button();
?>
</div>
<?php } ?>

<!-- blocks pre -->
<?php if ($hassidepre) { ?>
<div id="region-pre" class="block-region">
    <div class="region-content">
        <?php echo $OUTPUT->blocks('side-pre'); ?>
    </div>
</div>
<?php } ?>


<!-- actual main content -->
<div id="region-main-box" class="block-region">
    <div class="region-content">
        <?php echo $OUTPUT->blocks('main'); ?>
        <?php echo $OUTPUT->blocks('side-main-box'); ?>
    </div>
</div>


<!-- blocks post -->
<?php if ($hassidepost) { ?>
<div id="region-post" class="block-region">
    <div class="region-content">
        <?php echo $OUTPUT->blocks('side-post'); ?>
    </div>
</div>
<?php } ?>


</main>

</div>
</div>
<!-- close moodle js hooks -->



<?php require(__DIR__.'/footer.php');
