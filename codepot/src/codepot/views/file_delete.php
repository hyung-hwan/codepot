<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/file.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<title><?php print htmlspecialchars($file->name)?></title>
</head>

<body>

<div class="content" id="file_delete_content">

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
			'id' => 'file',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea">

<?php if ($message != "") print '<div id="file_delete_message" class="form_message">'.htmlspecialchars($message).'</div>'; ?>

<div class="form_container">
<?php print form_open("file/delete/{$project->id}/".$this->converter->AsciiToHex($file->name))?>

	<div>
		<div>
			<?php print form_checkbox('file_confirm', 'yes', set_checkbox('file_confirm', $file_confirm))?>
			<?php print $this->lang->line('MSG_SURE_TO_DELETE_THIS') ?> - <?php print htmlspecialchars($file->name)?>
			<?php print form_error('file_confirm')?>
		</div>
	</div>

	<div>
		<?php print form_hidden('file_projectid', set_value('file_projectid', $file->projectid))?>
		<?php print form_hidden('file_name', set_value('file_name', $file->name))?>
	</div>

	<div>
		<?php print form_submit('file', $this->lang->line('Delete'))?>
	</div>

<?php print form_close();?>
</div>

</div>

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div>  <!-- file_delete_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>

</html>
