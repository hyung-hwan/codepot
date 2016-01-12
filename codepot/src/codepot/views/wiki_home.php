<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/wiki.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<title><?php print htmlspecialchars($project->name)?></title>

<script type="text/javascript">
/* <![CDATA[ */
function show_alert (outputMsg, titleMsg) 
{
	$('#wiki_home_alert').html(outputMsg).dialog({
		title: titleMsg,
		resizable: true,
		modal: true,
		width: 'auto',
		height: 'auto',
		buttons: {
			"OK": function () {
				$(this).dialog("close");
			}
		}
	});
}

$(function () { 

<?php if (isset($login['id']) && $login['id'] != ''): ?>
	$("#wiki_home_new_h_button").button().click (
		function () { 
			$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/wiki/createx/{$project->id}"; ?>'));
			return false;
		}
	);
	$("#wiki_home_new_c_button").button().click (
		function () { 
			$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/wiki/create/{$project->id}"; ?>'));
			return false;
		}
	);
<?php endif; ?>

});

/* ]]> */
</script>

</head>

<body>

<div class="content" id="wiki_home_content">

<!-- =================================================================== -->

<?php $this->load->view ('taskbar'); ?>

<!-- =================================================================== -->

<?php
$this->load->view (
	'projectbar', 
	array (
		'banner' => NULL,

		'page' => array (
			'type' => 'project',
			'id' => 'wiki',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
); 
?>

<!-- =================================================================== -->

<div class="mainarea" id="wiki_home_mainarea">


<div class="title-band" id="wiki_home_title_band">
	<div class="title"><?php print $this->lang->line('Wikis');?></div>

	<div class="actions">
		<?php if (isset($login['id']) && $login['id'] != ''): ?>
		<a id="wiki_home_new_h_button" href='#'><?php print $this->lang->line('New')?> [H]</a>
		<a id="wiki_home_new_c_button" href='#'><?php print $this->lang->line('New')?> [C]</a>
		<?php endif; ?>
		<!-- <a id="wiki_home_search_button" href='#'><?php print $this->lang->line('Search')?></a> -->
	</div>

	<div style='clear: both'></div>
</div>

<div id="wiki_home_result" class="result">
<?php
if (empty($wikis))
{
	print $this->lang->line('WIKI_MSG_NO_PAGES_AVAILABLE');
}
else
{
	print '<ul>';
	foreach ($wikis as $wiki) 
	{
		$hexname = $this->converter->AsciiToHex ($wiki->name);
		print '<li>' . anchor ("wiki/show/{$project->id}/{$hexname}", htmlspecialchars($wiki->name)) .'</li>';
	}
	print '</ul>';
}
?>
</div>

<div id='wiki_home_alert'></div>

</div> <!-- wiki_home_mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- wiki_home_content -->

<!-- =================================================================== -->

<?php $this->load->view ('footer'); ?>

<!-- =================================================================== -->



</body>
</html>
