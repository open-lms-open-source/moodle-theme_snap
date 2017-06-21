<?php
defined('MOODLE_INTERNAL') || die();
require(__DIR__.'/header.php');
?>

<!-- moodle js hooks -->
<div id="page">
    <div id="page-content">
    <!--
    ////////////////////////// MAIN  ///////////////////////////////
    -->
        <main id="moodle-page" class="clearfix">
        <div id="page-header" class="clearfix snap-category-header">
        <div class="breadcrumb-nav" aria-label="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
            <div id="page-mast">
            <?php
                $categories = $PAGE->categories;
                if (empty($categories)) {
                    $catname = get_string('courses', 'theme_snap');
                    echo '<h1>' .s($catname). '</h1>';
                }
                else {
                    // Get the current catagory name and description.
                    // In the array of cats, the current is always first.
                    foreach ($categories as $cat) {
                        $catid = $cat->id;
                        $catname = $cat->name;
                        $catdescription = $cat->description;
                        // Re-write plugins and format for course cat.
                        $catsummary = '';
                        if ($catdescription) {
                            $content = context_coursecat::instance($cat->id);
                            $catdescription = file_rewrite_pluginfile_urls($catdescription,
                                'pluginfile.php', $content->id, 'coursecat', 'description', null);
                            $options = array('noclean' => true, 'overflowdiv' => false);
                            $catsummary = '<div class="snap-category-description">'
                            .format_text($catdescription, $cat->descriptionformat, $options).
                            '</div>';
                        }
                        break;
                    }
                    echo '<h1>' .s($catname). '</h1>';
                    echo $catsummary;
                    if (can_edit_in_category($catid)) {
                        $editurl = new moodle_url('/course/editcategory.php', ['id' => $catid]);
                        echo '<div><a href=" '.$editurl.' " class="btn btn-default btn-sm">' .get_string('categoryedit', 'theme_snap'). '</a></div>';
                    }
                }

                if (has_capability('moodle/category:manage', context_system::instance())) {
                    $manageurl = new moodle_url('/course/management.php');
                    echo '<div class="text-right"><a class="btn btn-default btn-sm" href="' .$manageurl. '">' .get_string('managecourses', 'moodle'). '</a></div>';
                }
                ?>
            </div>
        </div>
        <section id="region-main">
            <?php echo $OUTPUT->main_content(); ?>
        </section>
        </main>
    </div>
</div>
<!-- close moodle js hooks -->
<?php require(__DIR__.'/footer.php');
