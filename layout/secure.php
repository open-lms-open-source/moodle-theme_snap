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
 * Layout - secure.
 * This layout is baed on a moodle site index.php file but has been adapted to show news items in a different
 * way.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body <?php echo $OUTPUT->body_attributes(); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<nav role="navigation" class="navbar navbar-default">
    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#moodle-navbar">
            <span class="sr-only"><?php print_string('togglenavigation', 'theme_snap') ?></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <a class="brand" href="<?php echo s($CFG->wwwroot);?>"><?php echo format_string($SITE->shortname); ?></a>

        <div id="moodle-navbar" class="navbar-collapse collapse">
            <?php echo $OUTPUT->custom_menu(); ?>
            <ul class="nav pull-right">
                <li><?php echo $OUTPUT->page_heading_menu(); ?></li>
                <li class="navbar-text">
                    <?php
                        if (method_exists($OUTPUT, 'user_menu')) {
                            echo $OUTPUT->user_menu(); // user_menu, for Moodle 2.8
                        } else {
                            echo $OUTPUT->login_info(); // login_info, Moodle 2.7 and before
                        }
                     ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div id="page" class="container">

    <header id="page-header" class="clearfix">
        <?php echo $OUTPUT->page_heading(); ?>
    </header>

    <div id="page-content" class="row">
        <div id="region-bs-main-and-pre" class="col-md-9">
            <div class="row-fluid">
                <section id="region-main" class="col-md-8 pull-right">
                    <?php echo $OUTPUT->main_content(); ?>
                </section>
                <?php echo $OUTPUT->blocks('side-pre', 'col-md-4 desktop-first-column'); ?>
            </div>
        </div>
        <?php echo $OUTPUT->blocks('side-post', 'col-md-3'); ?>
    </div>

    <?php echo $OUTPUT->standard_end_of_body_html() ?>

</div>
</body>
</html>