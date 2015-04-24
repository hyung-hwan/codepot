<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="keywords" content="<?php print $project->id?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/project.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />


<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<title><?php print htmlspecialchars($project->name)?></title>
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
		'banner' => NULL,

		'page' => array (
			'type' => 'project',
			'id' => 'project',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>
<!---------------------------------------------------------------------------->

<div class="mainarea">

<?php if ($message != "") print "<div id='project_create_message' class='form_message'>$message</div>"; ?>

<div class="form_container">
<?php print form_open('project/delete/'.$project->id)?>

	<div>
		<div>
			<?php print form_checkbox('project_confirm', 'yes', set_checkbox('project_confirm', $project_confirm))?>
			<?php print $this->lang->line('MSG_SURE_TO_DELETE_THIS')?> - <?php print htmlspecialchars($project->name)?>
			<?php print form_error('project_confirm')?>
		</div>
	</div>

	<div>
		<?php print form_hidden('project_id', set_value('project_id', $project->id))?>
		<?php print form_hidden('project_name', set_value('project_name', $project->name))?>
	</div>

	<div>
		<?php print form_submit('project', $this->lang->line('Delete'))?>
	</div>

<?php print form_close();?>
</div>

</div> <!-- mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div>  <!-- project_delete_content -->
<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>

</html>
