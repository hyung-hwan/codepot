<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/issue.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<title><title><?php print htmlspecialchars($issue->id)?></title></title>
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
<?php print form_open("issue/delete/{$project->id}/".$this->converter->AsciiToHex($issue->id))?>

	<div>
		<div>
			<?php print form_checkbox('issue_confirm', 'yes', set_checkbox('issue_confirm', $issue_confirm))?>
			<?php print $this->lang->line('MSG_SURE_TO_DELETE_THIS')?> - <?php print htmlspecialchars($issue->id)?>
			<?php print form_error('issue_confirm')?>
		</div>
	</div>

	<div>
		<?php print form_hidden('issue_projectid', set_value('issue_projectid', $issue->projectid))?>
		<?php print form_hidden('issue_id', set_value('issue_id', $issue->id))?>
	</div>

	<div>
		<?php print form_submit('issue', $this->lang->line('Delete'))?>
	</div>

<?php print form_close();?>
</div>

</div> <!-- mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>  <!-- project_issue_delete_content -->

</body>

</html>
