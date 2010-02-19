<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><?=htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="project_delete_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->
<?php
$this->load->view (
	'projectbar',
	array (
		'site' => NULL,
		'pageid' => 'project',
		'ctxmenuitems' => array ()
        )
);
?>
<!---------------------------------------------------------------------------->

<div class="mainarea">

<?php if ($message != "") print "<div id='project_create_message' class='form_message'>$message</div>"; ?>

<?=form_open('project/delete/'.$project->id)?>
	<?=form_fieldset()?>
		<div>
			<div>
				<?=form_checkbox('project_confirm', 'yes', set_checkbox('project_confirm', $project_confirm))?>
				<?=$this->lang->line('MSG_SURE_TO_DELETE_THIS')?> - <?=htmlspecialchars($project->name)?>
				<?=form_error('project_confirm')?>
			</div>
		</div>

		<div>
			<?=form_hidden('project_id', set_value('project_id', $project->id))?>
			<?=form_hidden('project_name', set_value('project_name', $project->name))?>
		</div>

		<div>
			<?=form_submit('project', $this->lang->line('Delete'))?>
		</div>

	<?=form_fieldset_close()?>
<?=form_close();?>

</div>

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>  <!-- project_delete_content -->

</body>

</html>
