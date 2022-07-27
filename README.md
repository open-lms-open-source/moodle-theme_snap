# Snap Theme
Snap is a Moodle theme that makes online learning an enjoyable and intuitive experience for learners and educators. https://moodle.org/plugins/theme_snap

Snap’s user-friendly design removes barriers to online learning, enabling you to create the modern, engaging experience users expect on the web today. Its intuitive layout is optimised for online learning, focusing on the things that matter - your learning activities and content.

![theme-snap-login](https://moodle.org/pluginfile.php/50/local_plugins/plugin_description/1465/snap-signin.png)

Snap’s easy to use navigation gives users an elegant way to perform frequent tasks - all your courses, deadlines, messages and feedback are always one click or tap away to save you time.

Working seamlessly across every device - from desktop to mobile, Snap’s responsive Twitter Bootstrap based framework provides a consistent, professional experience for learning whenever and wherever you want to learn.

![theme-snap-pm](https://moodle.org/pluginfile.php/50/local_plugins/plugin_description/1465/snap-personalmenu.png)

This plugin was contributed by the Open LMS Product Development team. Open LMS is an education technology company
dedicated to bringing excellent online teaching to institutions across the globe.  We serve colleges and universities,
schools and organizations by supporting the software that educators use to manage and deliver instructional content to
learners in virtual classrooms.

## Installation
Extract the contents of the plugin into _/wwwroot/theme_ then visit `admin/upgrade.php` or use the CLI script to upgrade your site.

## Technology

Built with Bootstrap 4, Sass, and jQuery.

## License
Copyright (c) 2021 Open LMS (https://www.openlms.net)

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.

## Configuration flags

This section documents the Snap configuration flags. These flags have the form
`$CFG->theme_snap_flag_name`, where `flag_name` is replaced by the name
of the flag. These flags are accessed with the global configuration object
`$CFG`.

The configuration flags are presented here in alphabetical order.

### The `theme_snap_bar_limit` flag

The purpose of this flag is to set the maximum amount of Courses that a Student
can have with the Progress Bar of those Courses being displayed in the Snap
personal menu (accessed by clicking on 'My Courses'). If a Student is enrolled
in more courses than this number, then no single Course will have its Progress
Bar displayed. In other case, all the Courses will have their Progress Bar
displayed. This is done so as to avoid degrading site performance when a
Student has many courses.

This flag is set as an integer:

   - `$CFG->theme_snap_bar_limit = 55` means that the Progress Bar will be
   displayed for up to 55 Courses. If a Student is enrolled in 56 or more
   Courses, then the Progress Bar of those Courses won't be displayed.

### The `theme_snap_disable_deadline_mods` flag

The purpose of this flag is to disable selected Activities from being shown in
the Deadlines feed in the Snap personal menu. This does not disable individual
Activities but rather this disables Activities by their type. For example, you
can use this flag to disable Assignment and Quiz activities from being shown in
the Deadlines feed.

This flag is set as an array of strings:

   - `$CFG->theme_snap_disable_deadline_mods = ['assign', 'quiz']` means that
   both Assignment and Quiz Activities will not be shown in the Deadlines feed.
   Note in this usage, that there is no prefix being used. For example, to
   disable the component `mod_label`, it should be done as
   `$CFG->theme_snap_disable_deadline_mods = ['label']`, without the prefix
   `mod_`.

### The `theme_snap_include_cm_checks_in_deadlines_task` flag

This flag is an auxiliary flag that should only be used for testing. Its
purpose is to skip over Course Module checks when refreshing the Deadlines
cache.

This flag is set as a boolean value:

   - `$CFG->theme_snap_include_cm_checks_in_deadlines_task = true` means that
   Course Module checks are skipped.
   - `$CFG->theme_snap_include_cm_checks_in_deadlines_task = false` means that
   Course Module checks are performed.

### The `theme_snap_max_concurrent_deadline_queries` flag

The purpose of this flag is to set the maximum amount of concurrent Deadline
queries. An exception is thrown if the amount of concurrent Deadline queries
is above this number. By default this maximum amount is equal to the PHP
constant INF which means infinite concurrent Deadline queries.

This flag is set as an integer:

   - `$CFG->theme_snap_max_concurrent_deadline_queries = 700` means that 700
   concurrent Deadline queries can be performed at a maximum.

### The `theme_snap_max_pm_completion_courses` flag

The purpose of this flag is to set the maximum amount of Courses that will have
their Progress Bar displayed in the Snap personal menu. The default amount is
currently set at 100. This flag can be used along with the flag
`theme_snap_bar_limit`.

This flag is set as an integer:

   - `$CFG->theme_snap_max_pm_completion_courses = 15` means that only the
   first 15 Courses will have their Progress Bar displayed in the Snap personal
   menu.

### The `theme_snap_max_pm_completion_time_courses` flag

The purpose of this flag is to set the maximum amount of time in seconds, that
the calculations for the Progress Bar of the Courses in the Snap personal menu
can last. Only the Progress Bars calculated within this time window will be
rendered. The default is currently 15 seconds.

This flag is set as a double:

   - `$CFG->theme_snap_max_pm_completion_time_courses = 20.9` means that only
   the Progress Bars that manage to be calculated within the first 20.9 seconds
   of the calculation, will be rendered.

### The `theme_snap_refresh_deadlines_last_login` flag

The purpose of this flag is to set the relative date of the last login, from
which the refresh Deadlines cache task will be executed. By default this is set
to six months in the past.

This flag is set as a string:

   - `$CFG->theme_snap_refresh_deadlines_last_login = '2 weeks 1 hour ago'`
   means that the Deadlines feed will be refreshed for users whose last login
   is dated 2 weeks and 1 hour ago, or more time in the past. Notice in this
   usage, that the string complies with PHP's relative datetime format.

### The `theme_snap_refresh_deadlines_max_duration` flag

The purpose of this flag is to set a maximum duration for the refresh Deadlines
cache task, in seconds. By default this duration is of 6 hours. This flag can
be used along with the flag `$CFG->theme_snap_refresh_deadlines_last_login`.

This flag is set as an integer:

   - `$CFG->theme_snap_refresh_deadlines_max_duration = 10800` means that the
   refresh Deadlines cache task can take up to 3 hours to be executed. The task
   stops if this amount of time passes.