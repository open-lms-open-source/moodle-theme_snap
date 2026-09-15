# theme_snap — Architecture Reference

## Overview

Snap is Open LMS's flagship learner-facing theme — a Boost child theme that replaces most of Moodle's default interface rather than restyling it. On a Snap site the learner gets a personal menu carrying deadline, grading, message and forum feeds, courses presented as cards with cover images, and inside a course a table of contents down the side rather than Moodle's stacked section list. Administrators get a large branding surface on top of that: brand and per-category colours, profile-field-driven branding, fonts, login backgrounds and carousels, feature spots and featured categories.

Most of that is produced at render time rather than by templates alone. Snap registers 19 layout entries against 5 layout files, overrides a large part of core's renderer, runs three SCSS callbacks plus a post-processor to build its CSS, and ships 47 AMD modules and 11 web service functions to drive the interface. It owns two tables of its own, `theme_snap_course_favorites` and `theme_snap_toc_hidden`, and carries the TOC-hidden data through course backup and restore — unusual for a theme, and the reason `backup/moodle2/` exists here at all.

What Snap deliberately does not do is multitenancy. Per-tenant branding lives in child themes that sit on top of Snap — `theme_snap_tenants` — and swap in tenant-specific values at render time. Snap itself reads site-level configuration only.

If the theme were uninstalled the site falls back to Boost: the table of contents, cover images, personal feeds, course cards and every Snap setting disappear, the two tables are left orphaned, and anything relying on Snap's web services or AMD modules stops working.

## Plugin identity

| Field | Value |
|---|---|
| Component | `theme_snap` |
| Version | 2026060900 (release 5.1.4) |
| Requires Moodle | 2025100600 (4.5+) |
| Parent theme | `theme_boost` |
| Maturity | MATURITY_STABLE |
| Dependency | `theme_boost` >= 2020110900 |

---

## Directory structure

```
theme/snap/
├── config.php                              # Theme registration, SCSS callbacks, layout map
├── lib.php                                 # All plugin-level callbacks (21 functions)
├── settings.php                            # Includes 13 modular settings files
├── version.php
├── rest.php                                # REST endpoint for AJAX requests
├── index.php                               # (placeholder)
│
├── settings/                               # Modular admin settings (13 files)
│   ├── snap_basics.php
│   ├── navigation_bar_settings.php
│   ├── login_settings.php
│   ├── cover_settings.php
│   ├── course_settings.php
│   ├── snap_feeds_settings.php
│   ├── feature_spots_settings.php
│   ├── featured_categories_and_courses_settings.php
│   ├── social_media_settings.php
│   ├── snap_footer_settings.php
│   ├── snap_hvp_settings.php
│   ├── categories_color_settings.php
│   └── profile_based_branding.php
│
├── amd/src/                                # 47 AMD JavaScript modules
├── classes/
│   ├── output/                             # Renderer overrides (8 files + 2 traits)
│   ├── renderables/                        # Data objects for templates (15 classes)
│   ├── webservice/                         # Web service implementations (11 classes)
│   ├── controller/                         # MVC controllers (5 classes)
│   ├── calendar/                           # Calendar event overrides (3 classes)
│   ├── task/                               # Scheduled tasks (2 classes)
│   ├── services/                           # Service layer (1 class)
│   ├── traits/                             # Shared traits (1 class)
│   └── privacy/                            # Privacy provider
│
├── db/
│   ├── hooks.php                           # 1 hook callback
│   ├── install.xml                         # 2 custom tables
│   ├── services.php                        # 11 web service definitions
│   └── upgrade.php                         # 15 upgrade steps
│
├── lang/                                   # 17 language packs
├── layout/                                 # 13 layout PHP files + 3 shared partials
├── templates/                              # 73 mustache templates
├── scss/                                   # Pre/Boost/Post SCSS pipeline (31 files)
├── style/
│   └── editor.css                          # Precompiled TinyMCE editor styles
├── pix/ pix_core/ pix_plugins/             # Icons and images
├── fonts/                                  # Custom font files
└── vendorjs/snap-custom-elements/          # Web component library
```

---

## `$THEME` config attributes (`config.php`)

| Attribute | Value | Notes |
|---|---|---|
| `name` | `'snap'` | |
| `parents` | `['boost']` | Boost child-theme |
| `rendererfactory` | `'theme_overridden_renderer_factory'` | Activates renderer overrides |
| `prescsscallback` | `'theme_snap_get_pre_scss'` | SCSS variables injection |
| `extrascsscallback` | `'theme_snap_get_extra_scss'` | Post-SCSS content |
| `scss` | `theme_snap_get_main_scss_content($theme)` | Main SCSS builder |
| `csspostprocess` | `'theme_snap_process_css'` | String-replacement post-processor (unique to Snap) |
| `supportscssoptimisation` | `false` | SCSS optimisation disabled |
| `iconsystem` | `\theme_snap\output\icon_system_fontawesome` | Custom icon class (not Boost's default) |
| `haseditswitch` | `false` | Snap handles its own edit toggle; Boost's navbar toggle is NOT used |
| `yuicssmodules` | `['cssgrids']` | Required for Joule Grader |
| `editor_sheets` | `['editor']` | `style/editor.css` for TinyMCE |
| `enable_dock` | `false` | |
| `addblockposition` | `BLOCK_ADDBLOCK_POSITION_FLATNAV` | |
| `requiredblocks` | `['settings']` (+ `navigation` in Behat) | |
| `blockrtlmanipulations` | `side-pre ↔ side-post` | RTL block region swap (Flexpage support) |

**Important side effects in `config.php`** (executed at config load, not just registration):

- Calls `local::resolve_theme()` to detect if Snap is active.
- On non-AJAX requests with Snap active: calls `$PAGE->initialise_theme_and_output()` and replaces `$PAGE`'s requirements manager with `snap_page_requirements_manager` — a custom subclass of `\core\page_requirements_manager`.
- Backs up `$SESSION->wantsurl` into `$SESSION->snapwantsurl` (core can unset it during auth redirects).

---

## Layout map

Snap defines 19 layout entries in `config.php`, mapping to 5 distinct layout files (plus 3 shared partials):

| Layout name | File | Regions | Notes |
|---|---|---|---|
| `base` | `default.php` | none | |
| `standard` | `default.php` | side-pre | |
| `message` | `default.php` | none | |
| `incourse` | `default.php` | side-pre | |
| `frontpage` | `default.php` | side-pre | nonavbar |
| `admin` | `default.php` | side-pre | |
| `mydashboard` | `default.php` | side-pre | langmenu |
| `mypublic` | `default.php` | side-pre | |
| `frametop` | `default.php` | none | nofooter, nocoursefooter |
| `print` | `default.php` | none | nofooter |
| `report` | `default.php` | side-pre | |
| `course` | `course.php` | side-pre | langmenu; manages TOC, cover images |
| `coursecategory` | `course-index-category.php` | side-pre | optional cover image |
| `mycourses` | `mycourses.php` | side-pre | delegates to `OUTPUT->my_courses_snap_page_content()` |
| `login` | `login.php` | none | langmenu, nonavbar; carousel/bg image support |
| `popup` | `embedded.php` | none | nofooter, nonavbar |
| `embedded` | `embedded.php` | none | |
| `redirect` | `embedded.php` | none | |
| `maintenance` | `maintenance.php` | none | zero DB/cache calls — ultra-safe |

**Shared partials** (included by most layouts, not layout files themselves):
- `header.php` — page setup, theme color CSS variables, font loading
- `footer.php` — custom menu, footnote, custom footer content
- `nav.php` — `mr-nav` header bar; handles MFA pending, Genius dashboard link, settings link

Note: Snap does **not** define `course_index_drawer` or `blocks_drawer` as named layouts — these are layout PHP files that can be included but are not registered in the layout map.

---

## CSS pipeline (`lib.php`, `config.php`)

Snap uses **three SCSS callbacks plus a post-processor**:

### 1. `theme_snap_get_pre_scss($theme)` — `lib.php:321`

Injects SCSS variables from theme settings. Key variables set:

| SCSS variable | Setting source |
|---|---|
| `$primary` | `brandcolor` |
| `$font-family-feature` | `headingfont` (Google font name) |
| `$font-family-body` | `seriffont` |
| `$feature-spot-background-color` | `feature_spot_background_color` |
| `$feature-spot-title-color` | `feature_spot_title_color` |
| `$feature-spot-description-color` | `feature_spot_description_color` |
| + many more | Various snap_basics, cover, nav settings |

Also calls `theme_boost_get_pre_scss($theme)` — delegates to Boost for Bootstrap variable injection.

### 2. `theme_snap_get_main_scss_content($theme)` — `lib.php:274`

Assembles main SCSS following the **pre/Boost/post** pattern:
1. `pre.scss` — Snap variable overrides
2. `_boost.scss` — imports Bootstrap + Moodle core SCSS + FontAwesome
3. `post.scss` — imports 23 Snap-specific partials

### 3. `theme_snap_get_extra_scss($theme)` — `lib.php:411`

Appends any extra SCSS (minimal; most custom CSS is handled in pre/post).

### 4. `theme_snap_process_css($css, $theme)` — `lib.php:45` *(unique to Snap)*

**Post-processor** that runs string-replacement on the compiled CSS output. Calls in sequence:
- `theme_snap_set_category_colors($css, $theme)` — injects per-category brand colours using `[[setting:categoryX_color]]` placeholder tags
- `theme_snap_set_logo($css, $logo)` — replaces `[[setting:logo]]` placeholder
- `theme_snap_set_customcss($css, $customcss)` — replaces `/**setting:customcss**/` placeholder

This placeholder/replacement approach means some CSS values cannot be set via SCSS variables — they are injected into the already-compiled CSS string.

### SCSS file structure (`scss/`)

| File | Role |
|---|---|
| `pre.scss` | Entry point: Snap variable overrides before Boost |
| `_boost.scss` | Imports Bootstrap, Moodle core SCSS, FontAwesome |
| `post.scss` | Imports 23 Snap-specific partials |
| `_mixins.scss` | Custom SCSS mixins |
| `_core.scss` | General/core styles |
| `_course.scss` | Course page styles |
| `_toc.scss` | Table of contents |
| `_personalmenu.scss` | Snap feeds sidebar |
| `_courselistings.scss` | Course cards and listings |
| `_brandcolor.scss` | Dynamic brand colour CSS (injected via `process_css`) |
| `_blocks.scss` | Block styling |
| `_forms.scss` | Form customisations |
| `_plugins.scss` | Plugin integrations |
| `_themesettings.scss` | Theme admin settings page |
| `_behat.scss` | Behat test-specific styles |
| `_print.scss` | Print styles |
| `_cropper.scss` | Image cropper widget |
| + 6 more | joule, yui, filemanager, message, userprofile, coursefooter, coursemanagement, typefaces, custom |

---

## `lib.php` — all 21 functions

| Function | Signature | Purpose |
|---|---|---|
| `theme_snap_process_site_coverimage` | `()` | Processes and resizes the site-level cover image after upload |
| `theme_snap_process_css` | `($css, $theme)` | Post-processor: runs all string-replacement passes |
| `theme_snap_set_category_colors` | `($css, $theme)` | Replaces `[[setting:categoryX_color]]` tags in CSS |
| `theme_snap_set_logo` | `($css, $logo)` | Replaces `[[setting:logo]]` tag in CSS |
| `theme_snap_set_customcss` | `($css, $customcss)` | Replaces `/**setting:customcss**/` tag in CSS |
| `theme_snap_send_file` | `($context, $filearea, $args, $forcedownload, $options)` | Internal file-serving helper |
| `theme_snap_pluginfile` | `($course, $cm, $context, $filearea, $args, $forcedownload, $options)` | Serves theme file areas (cover images, HVP, etc.) |
| `theme_snap_myprofile_navigation` | `($tree, $user, $iscurrentuser, $course)` | Adds Snap-specific nodes to the user profile navigation |
| `theme_snap_get_main_scss_content` | `($theme)` | Assembles main SCSS (pre → _boost → post) |
| `theme_snap_get_pre_scss` | `($theme)` | Prepends SCSS variables from settings |
| `theme_snap_get_extra_scss` | `($theme)` | Appends extra SCSS |
| `theme_snap_output_fragment_cmitem` | `($args): string` | Fragment API: renders a single course module item |
| `theme_snap_output_fragment_section` | `($args)` | Fragment API: renders a full course section |
| `theme_snap_course_module_background_deletion_recommended` | `()` | Returns true — tells core to delete module background images |
| `theme_snap_serve_hvp_css` | `($filename, $hvpcustomcss=false)` | Serves H5P (HVP plugin) custom CSS |
| `theme_snap_resize_bgimage_after_save` | `()` | Resizes cover background images after save (callback) |
| `theme_snap_user_preferences` | `(): array` | Registers user preference keys for drawer state, TOC visibility, etc. |
| `theme_snap_coursemodule_standard_elements` | `($formwrapper, $mform): void` | Injects Snap fields into the activity edit form |
| `theme_snap_coursemodule_definition_after_data` | `($formwrapper, $mform): void` | Post-data hook on activity edit form |
| `theme_snap_coursemodule_edit_post_actions` | `($moduleinfo, $course): stdClass` | Post-save hook on activity edit — updates TOC hidden state |

---

## Renderer overrides (`classes/output/`)

### Inheritance chain

```
theme_snap\output\core_renderer
  └── theme_boost\output\core_renderer
        └── \core_renderer
              └── \renderer_base
```

### `core_renderer.php` — extends `\theme_boost\output\core_renderer`

The largest file in the theme (~2500 lines, 69 methods). Methods are grouped below by whether they **override a parent** or are **new to Snap**:

#### Methods that override parent class methods

| Method | Overrides | What changes |
|---|---|---|
| `edit_button(moodle_url $url, string $method)` | `theme_boost\output\core_renderer` | Returns empty string — Snap has its own editing toggle mechanism (`haseditswitch = false` means the Boost navbar toggle is also absent; Snap uses its own UI) |
| `context_header($headerinfo, $headinglevel): string` | `theme_boost\output\core_renderer` | Adds Snap cover image, page header, and course-specific header elements |
| `activity_header()` | `\core_renderer` | Wraps core activity header with Snap-specific markup |
| `page_heading($tag)` | `\core_renderer` | Returns Snap's custom page heading, respecting course context and cover images |
| `body_css_classes(array $additionalclasses)` | `\core_renderer` | Adds extensive Snap-specific CSS classes (page type, role, feature flags, etc.) |
| `favicon()` | `\core_renderer` | Reads `favicon` theme setting; falls back to parent |
| `render_custom_menu(\core\output\custom_menu $menu)` | `\core_renderer` | Adds Snap-specific menu items (social, spacers) |
| `render_navigation_node(navigation_node $item)` | `\core_renderer` | Modifies course editing links; adds communication and content bank nodes |
| `image_url($imagename, $component)` | `\renderer_base` | Strips icon size suffixes (e.g. `-24`, `-32`) for SVG icon compatibility |
| `confirm($message, $continue, $cancel, $displayoptions)` | `\core_renderer` | Wraps in Snap modal markup |
| `course_modchooser()` | `\core_renderer` | Renders Snap's custom activity chooser |
| `get_logo_url($maxwidth, $maxheight)` | `\renderer_base` | Reads `logo` theme setting file URL (system-level only) |
| `navbar(): string` | `theme_boost\output\core_renderer` | Replaces Boost's boostnavbar with Snap's custom nav |
| `heading_with_help($text, $helpidentifier, $component, $icon, $iconalt)` | `\core_renderer` | Wraps with Snap collapsible help pattern |

#### New methods (Snap-only, no parent to override)

**Personal menu / Snap Feeds:**
`render_callstoaction()`, `render_snap_feeds_mobile()`, `render_deadlines()`, `render_grading()`, `render_graded()`, `render_messages()`, `render_forumposts()`, `snap_feeds()`, `snap_feeds_side_menu()`, `snap_feeds_side_menu_trigger()`

**Login page:**
`login_button()`, `render_login_alternative_methods()`, `render_login_base_method()`, `login_bg_slides()`, `login_carousel_first()`

**Featured content / cover images:**
`cover_image_selector()`, `cover_carousel()`, `render_featured_courses()`, `render_featured_categories()`, `feature_spot_cards()`, `get_course_image()`

**Course / navigation:**
`snap_page_header()`, `snap_blocks()`, `my_courses_nav_link()`, `user_menu_nav_dropdown()`, `snap_my_courses_management_options()`, `my_courses_snap_page_content()`, `course_activitychooser()`, `site_frontpage_news()`, `snap_content_bank()`

**Third-party integrations:**
`render_intelliboard()`, `render_intelliboard_link()`, `render_intellicart()`, `render_notification_popups()`, `render_genius_dashboard_link()`, `render_settings_link()`

**Page state checks:**
`snap_page_is_activity_view()`, `snap_page_is_activity_mod()`, `snap_page_is_edit_section()`, `snap_page_is_user_view()`, `in_alternative_role()`, `feedback_toggle_enabled()`, `advanced_feeds_enabled()`

**Utilities:**
`column_header_icon_link()`, `mobile_menu_link()`, `social_menu_link()`, `friendly_datetime()`, `snap_media_object()`, `custom_menu_spacer()`, `secure_layout_language_menu()`, `get_poweredby_subdomain()`, `get_path_hiddentoc()`, `snap_make_coursename_link()`

---

### Other renderer overrides

| Class | Extends | Override |
|---|---|---|
| `site_renderer` | `\core_courseformat\output\site_renderer` | Uses `format_section_trait` for section rendering |
| `core_renderer_ajax` | `\core\output\core_renderer_ajax` | Overrides `image_url()` (same icon suffix stripping) |
| `format_topics_renderer` | `\format_topics\output\renderer` | Uses `format_section_trait` |
| `format_weeks_renderer` | `\format_weeks\output\renderer` | Uses `format_section_trait` |
| `format_singleactivity_renderer` | `\format_singleactivity\output\renderer` | Overrides `display()` to suppress H5P subtype warnings |
| `block_myoverview_renderer` | `\block_myoverview\output\renderer` | Overrides `render_main()` to add year/progress filtering |
| `course_renderer` | `\core_course_renderer` | Overrides `frontpage_section1()`; adds `snap_course_section_cm_availability()` |
| `icon_system_fontawesome` | `\core\output\icon_system_fontawesome` | Custom FontAwesome icon system |

### `format_section_trait` (`classes/output/format_section_trait.php`)

Shared by `site_renderer`, `format_topics_renderer`, and `format_weeks_renderer`. Contains the bulk of Snap's course page rendering logic:

- `render_from_template()` — intercepts template rendering to inject Snap custom data
- `render()` — handles `content` and `delegatedsection` renderables
- `add_snap_custom_section_summary()` — wraps core summary with edit button
- `add_snap_custom_module_data()` — injects control menu, metadata, restrictions, alternative content into each activity
- `section_header()` — section title, editing controls, bulk buttons
- `section_edit_control_items()` / `section_edit_control_items_menued()` — move, visibility, delete, highlight, duplicate, permalink actions
- `course_section_add_cm_control_snap()` — "add activity/resource" button and drag-drop zone

---

## Custom PHP classes (`classes/`)

### Renderables (data objects, `classes/renderables/`)

All implement `renderable` + `templatable` via `trait_exportable`:

| Class | Purpose |
|---|---|
| `course_card` | Single course card |
| `featured_courses` / `featured_course` | Featured courses section |
| `featured_categories` / `featured_category` | Featured categories section |
| `course_section_navigation` / `course_section_navigation_link` | Section prev/next links |
| `course_toc_module` | Activity item in table of contents |
| `course_toc_footer` | TOC footer nav |
| `login_alternative_methods` | IDP / alternative login options |
| `settings_link` | Admin settings trigger |
| `genius_dashboard_link` | Open LMS Genius dashboard link |
| `course_action_section_*` (6 classes) | Move / visibility / delete / highlight / duplicate / permalink actions |

### Web services (`classes/webservice/`)

All extend `\core\webservice\external_api`. Registered in `db/services.php`:

| Class | Purpose |
|---|---|
| `ws_course_card` | Single course card data |
| `ws_course_cards_data` | Multiple course cards |
| `ws_course_cards_categories` | Cards filtered by category |
| `ws_cover_image` | Cover image upload and management |
| `ws_feed` | General activity feed data |
| `ws_file_manager_options` | File manager configuration |
| `ws_block_myoverview` | My Overview block AJAX |
| `ws_coursetools_block_actions` | Course tools block actions |
| `ws_course_toc_progressbar` | TOC progress bar data |
| `ws_course_section_progress` | Section progress data |
| `ws_get_hidden_toc_activities` | Course-module IDs currently hidden from the Snap TOC |

### Controllers (`classes/controller/`)

| Class | Purpose |
|---|---|
| `controller_abstract` | Abstract base (`handle()` interface) |
| `kernel` | Main routing kernel |
| `router` | Route matching |
| `addsection_controller` | "Add section" AJAX handler |
| `pagemod_controller` | Page module management |
| `mediaresource_controller` | Media resource handling |
| `snap_personal_menu_controller` | Personal menu AJAX endpoints |

### Scheduled tasks (`classes/task/`)

| Class | Schedule |
|---|---|
| `refresh_deadline_caches_task` | Refreshes per-user deadline cache |
| `reset_deadlines_query_count_task` | Resets deadline query counter |

### Other notable classes

| Class | Extends / Notes |
|---|---|
| `snap_page_requirements_manager` | Extends `\core\page_requirements_manager` — replaces `$PAGE`'s manager at config load time |
| `event_handlers` | Event listener callbacks |
| `hook_callbacks` | Hook system callbacks |
| `local` | Static utility methods (course images, URLs, capability checks) |
| `color_contrast` | WCAG contrast ratio validation |
| `image` | Image processing utilities |
| `mod_hvp_renderer` | H5P/HVP module renderer override |
| `calendar/event/container` | Extends `\core_calendar\local\event\container` |

---

## Database

### Tables (`db/install.xml`)

**`theme_snap_course_favorites`**
- `(id, courseid, userid, timefavorited)`
- Unique index: `(userid, courseid)`
- Stores user course favourites (personal menu "starred" courses)

**`theme_snap_toc_hidden`**
- `(id, cmid, timecreated)`
- Unique index: `(cmid)`
- Stores which course modules a user has hidden from the table of contents

### Upgrade history (`db/upgrade.php`)

15 upgrade steps from 2014 through 2025121900. Most recent step creates `theme_snap_toc_hidden`.

---

## Hooks (`db/hooks.php`)

Single registration:

| Hook | Callback | Priority |
|---|---|---|
| `core\hook\output\before_footer_html_generation` | `\theme_snap\hook_callbacks::before_footer_html_generation` | 0 |

Injects Snap-specific HTML before the closing `</body>` (web components, footer alerts, etc.).

---

## No capabilities (`db/access.php`)

Snap defines **no custom capabilities**. It relies entirely on Moodle core capability checks. There is no `db/access.php` file.

---

## Templates (73 mustache files)

### Core and Boost overrides

| Template | Namespace | What it overrides |
|---|---|---|
| `core/action_menu_trigger.mustache` | core | Action menu trigger button |
| `core/drawer.mustache` | core | Main page drawer |
| `core/help_icon.mustache` | core | Help icon |
| `core/loginform.mustache` | core | Login form layout |
| `core/notification_error.mustache` | core | Error notification |
| `core/notification_info.mustache` | core | Info notification |
| `core/notification_success.mustache` | core | Success notification |
| `core/notification_warning.mustache` | core | Warning notification |
| `core/signup_form_layout.mustache` | core | Sign-up form wrapper |
| `theme_boost/admin_setting_tabs.mustache` | theme_boost | Admin settings tabs |
| `theme_boost/drawers.mustache` | theme_boost | Boost drawer container |
| `core_admin/setting.mustache` | core_admin | Individual setting row |
| `core_admin/settings.mustache` | core_admin | Settings group |
| `core_course/activity_navigation.mustache` | core_course | Activity prev/next nav |

### Course format overrides (10 files in `templates/core_courseformat/`)

`content.mustache`, `bulkedittoggler.mustache`, `bulkedittools.mustache`, `cm/controlmenu.mustache`, `section/availability.mustache`, `frontpagesection.mustache`, and 4 course index templates.

### Block overrides (9 files in `templates/block_myoverview/`)

`main.mustache`, `courses-view.mustache`, `view-cards.mustache`, `view-list.mustache`, `view-summary.mustache`, `nav-year-selector.mustache`, `nav-completion-selector.mustache`, `progress-bar.mustache`, `star_button.mustache`.

### Activity module overrides (5 files)

`mod_assign/grading_app.mustache`, `mod_assign/grading_navigation.mustache`, `mod_forum/forum_discussion_nested_v2.mustache`, `mod_forum/settings_drawer_trigger.mustache`, `mod_forum/settings_header.mustache`.

### Snap-specific templates (34 files at root)

Course/content: `my_courses.mustache`, `course_card_modal.mustache`, `course_search_form.mustache`, `featured_courses.mustache`, `featured_course.mustache`, `featured_categories.mustache`, `featured_category.mustache`, `home_page_coursebox.mustache`, `cover_image_selector.mustache`, `course_toc_footer.mustache`, `course_toc_progress.mustache`, `course_toc_progress_bar.mustache`, `course_toc_module_search.mustache`, `course_action_section.mustache`, `course_action_section_menu.mustache`, `course_section_buttons.mustache`, `course_section_loading.mustache`, `course_section_navigation.mustache`

Page structure: `page_header.mustache`, `secondary_navigation.mustache`, `sidebar_menu.mustache`, `blocks_drawer.mustache`

Feeds/social: `snap_feeds.mustache`, `snap_feeds_mobile_menu.mustache`

Login: `login_alternative_methods.mustache`, `login_base_methods.mustache`, `carousel.mustache`

Utilities: `media_object.mustache`, `custom_menu_item.mustache`, `footer_alert.mustache`, `form_alert.mustache`, `heading_help_collapse.mustache`, `animated_graphics_pause.mustache`, `return_to_normal_role.mustache`

---

## Settings (13 modules)

| File | Covers |
|---|---|
| `snap_basics.php` | Brand colour, fonts (heading/body), custom CSS, logo, background |
| `navigation_bar_settings.php` | Nav bar colour, position, behaviour |
| `login_settings.php` | Login template (stylish/standard), background images, carousel |
| `cover_settings.php` | Course/site cover image defaults, display options |
| `course_settings.php` | TOC visibility, activity display, completion indicators |
| `snap_feeds_settings.php` | Deadlines, grading, messages, forum feeds toggles |
| `feature_spots_settings.php` | Feature spot cards (title, image, colours, links) |
| `featured_categories_and_courses_settings.php` | Featured course/category IDs |
| `social_media_settings.php` | Social media icon links |
| `snap_footer_settings.php` | Footer content, footnote |
| `snap_hvp_settings.php` | H5P custom CSS |
| `categories_color_settings.php` | Per-category brand colours (maps to `[[setting:categoryX_color]]` CSS placeholders) |
| `profile_based_branding.php` | User profile field → brand colour mapping |

---

## JavaScript (47 AMD modules)

**Core:**
`snap.js` (main init), `util.js`, `repository.js` (API layer), `model_view.js`

**Course:**
`course-lazy.js`, `course_modules.js`, `courseindex_adjustments.js`, `section_navigation.js`, `section_progress_component.js`, `section_asset_management.js`, `progressbar.js`, `progress_bar_component.js`

**Course format:**
`courseformat/content.js`, `courseformat/content/section.js`, `courseformat/content/actions/bulkselection.js`, `courseformat/content/bulkedittools.js`, `courseformat/courseindex/courseindex.js`, `courseformat/courseindex/cm.js`, `courseformat/courseindex/section.js`

**My Overview block:**
`block_myoverview/main.js`, `block_myoverview/repository.js`, `block_myoverview/view.js`, `block_myoverview/view_nav.js`

**UI / Navigation:**
`sidebar_menu.js`, `headroom.js`, `accessibility.js`, `activity_cards.js`, `snap_feeds.js`, `snap_feeds_mobile_menu.js`

**Admin:**
`adminevents.js`, `hide_settings.js`, `coursetools_blocks_management.js`

**Media / Images:**
`cropper.js`, `cover_image.js`, `carousel_login.js`, `appear.js`, `scroll.js`

**Lazy-loaded (performance):**
`alternative_role_handler-lazy.js`, `dndupload-lazy.js`, `login_render-lazy.js`, `conversation_badge_count-lazy.js`

**Other:**
`wcloader.js` (web components), `footer_alert.js`, `messages.js`, `ajax_notification.js`

---
