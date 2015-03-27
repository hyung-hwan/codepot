<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/issue.css')?>" />

<title><title><?=htmlspecialchars($issue->id)?></title></title>
</head>

<body>

<div class="content" id="project_issue_delete_content">

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
			'id' => 'issue',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea">

<?php if ($message != "") print '<div id="issue_delete_message" class="form_message">'.htmlspecialchars($message).'</div>'; ?>

<div class="form_container">
<?=form_open("issue/delete/{$project->id}/".$this->converter->AsciiToHex($issue->id))?>

	<div>
		<div>
			<?=form_checkbox('issue_confirm', 'yes', set_checkbox('issue_confirm', $issue_confirm))?>
			<?=$this->lang->line('MSG_SURE_TO_DELETE_THIS')?> - <?=htmlspecialchars($issue->id)?>
			<?=form_error('issue_confirm')?>
		</div>
	</div>

	<div>
		<?=form_hidden('issue_projectid', set_value('issue_projectid', $issue->projectid))?>
		<?=form_hidden('issue_id', set_value('issue_id', $issue->id))?>
	</div>

	<div>
		<?=form_submit('issue', $this->lang->line('Delete'))?>
	</div>

<?=form_close();?>
</div>

</div> <!-- mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>  <!-- project_issue_delete_content -->

</body>

</html>
