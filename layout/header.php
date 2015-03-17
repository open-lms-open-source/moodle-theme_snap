<?php
$PAGE->set_popup_notification_allowed(false);

// Require standard page js.
snap_shared::page_requires_js();

echo $OUTPUT->doctype();
?>

<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <?php
    if (stripos($PAGE->bodyclasses, 'path-blocks-reports') !== false) {
        // Fix IE charting bug (flash stuff does not work correctly in IE).
        echo ("\n".'<meta http-equiv="X-UA-Compatible" content="IE=8,9,10">'."\n");
    }
    ?>
<title><?php echo $OUTPUT->page_title(); ?></title>
<link rel="shortcut icon" href="<?php echo $OUTPUT->favicon() ?>"/>
<?php echo $OUTPUT->standard_head_html() ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href='//fonts.googleapis.com/css?family=Roboto:500,100,400,300' rel='stylesheet' type='text/css'>
<?php

// Output course cover image?
if ($COURSE->id != SITEID) {
    $courseimagecss = \theme_snap\local::course_coverimage_css($COURSE->id);
}
if (!empty($courseimagecss)) {
    echo "<style>$courseimagecss</style>";
}
?>

</head>

<body <?php echo $OUTPUT->body_attributes(); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<?php include(__DIR__.'/nav.php'); ?>
