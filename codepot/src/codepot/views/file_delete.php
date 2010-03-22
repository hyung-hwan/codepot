<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><title><?=htmlspecialchars($file->name)?></title></title>
</head>

<body>

<div class="content" id="project_file_delete_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,
		'site' => NULL,
		'pageid' => 'file',
		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea">

<?php if ($message != "") print '<div id="file_delete_message" class="form_message">'.htmlspecialchars($message).'</div>'; ?>

<?=form_open("file/delete/{$project->id}/".$this->converter->AsciiToHex($file->name))?>
	<?=form_fieldset()?>
		<div>
			<div>
				<?=form_checkbox('file_confirm', 'yes', set_checkbox('file_confirm', $file_confirm))?>
				<?=$this->lang->line('MSG_SURE_TO_DELETE_THIS') ?> - <?=htmlspecialchars($file->name)?>
				<?=form_error('file_confirm')?>
			</div>
		</div>

		<div>
			<?=form_hidden('file_projectid', set_value('file_projectid', $file->projectid))?>
			<?=form_hidden('file_name', set_value('file_name', $file->name))?>
		</div>

		<div>
			<?=form_submit('file', $this->lang->line('Delete'))?>
		</div>

	<?=form_fieldset_close()?>
<?=form_close();?>

</div>


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>  <!-- project_file_delete_content -->

</body>

</html>
