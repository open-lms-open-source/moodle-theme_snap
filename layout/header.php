<?php
$PAGE->set_popup_notification_allowed(false);
$PAGE->requires->jquery(); // TODO: Might be a better place to put this (EG: in lib.php in a theme lifecycle function).
$PAGE->requires->strings_for_js(array('close',
        'conditional',
        'debugerrors',
        'problemsfound',
        'forumtopic',
        'forumauthor',
        'forumpicturegroup',
        'forumreplies',
        'forumlastpost'
        ),
    'theme_snap');

echo $OUTPUT->doctype();
?>

<html id="blocks" <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
<title><?php echo $OUTPUT->page_title(); ?></title>
<link rel="shortcut icon" href="<?php echo $OUTPUT->favicon() ?>"/>
<?php echo $OUTPUT->standard_head_html() ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href='http://fonts.googleapis.com/css?family=Roboto:500,100,400,300' rel='stylesheet' type='text/css'>
<?php
$snapcourseimage = $OUTPUT->get_course_image();
if (!empty($snapcourseimage)) : ?>
<style>
#page-header {
	background:transparent url(<?php echo $snapcourseimage; ?>) no-repeat top center;
	-webkit-background-size: cover;
	-moz-background-size: cover;
	-o-background-size: cover;
	background-size: cover;
}
</style>
<?php endif; ?>

</head>

<body <?php echo $OUTPUT->body_attributes(); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<?php include(__DIR__.'/nav.php'); ?>