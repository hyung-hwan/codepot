<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/wiki.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<title><title><?php print htmlspecialchars($wiki->name)?></title></title>
</head>

<body>

<div class="content" id="wiki_delete_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

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

<!---------------------------------------------------------------------------->

<div class="mainarea">

<?php if ($message != "") print '<div id="wiki_delete_message" class="form_message">'.htmlspecialchars($message).'</div>'; ?>

<div class="form_container">
<?php print form_open("wiki/delete/{$project->id}/".$this->converter->AsciiToHex($wiki->name))?>

	<div>
		<div>
			<?php print form_checkbox('wiki_confirm', 'yes', set_checkbox('wiki_confirm', $wiki_confirm))?>
			<?php print $this->lang->line('MSG_SURE_TO_DELETE_THIS')?> - <?php print htmlspecialchars($wiki->name)?>
			<?php print form_error('wiki_confirm')?>
		</div>
	</div>

	<div>
		<?php print form_hidden('wiki_projectid', set_value('wiki_projectid', $wiki->projectid))?>
		<?php print form_hidden('wiki_name', set_value('wiki_name', $wiki->name))?>
	</div>

	<div>
		<?php print form_submit('wiki', $this->lang->line('Delete'))?>
	</div>

<?php print form_close();?>
</div>

</div> <!-- mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>  <!-- wiki_delete_content -->

</body>

</html>
