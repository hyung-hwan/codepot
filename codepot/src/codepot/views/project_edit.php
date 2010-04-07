<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><?=$project->name?></title>
</head>

<body>

<div class="content" id="project_edit_content">

<!------------------------------------------------------------------------>

<?php $this->load->view ('taskbar'); ?>

<!------------------------------------------------------------------------>

<?php
$this->load->view (
	'projectbar',
	array (
		'banner' => (($mode != 'create')? NULL: $this->lang->line('Projects')),

		'page' => array (
			'type' => 'project',
			'id' => 'project',
			'project' => (($mode != 'create')? $project: NULL)
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!------------------------------------------------------------------------>
<div class="mainarea" id="project_edit_mainarea">

<?php 
	if ($message != '') print "<div id='project_create_message' class='form_message'>$message</div>"; 

	$formurl = "project/{$mode}";
	if ($mode == 'update') $formurl .= '/'.$project->id;
?>

<?=form_open($formurl)?>
	<?=form_fieldset()?>
		<div>
			<div>
				<?=form_label($this->lang->line('ID').': ', 'project_id')?>
				<?=form_error('project_id')?>
			</div>
			<div>
				<?php
					$extra = ($mode == 'update')? 'readonly="readonly"': '';
					$extra .= 'maxlength="32" size="16"';
				?>

				<?=form_input('project_id', set_value('project_id', $project->id), $extra)?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Name').': ', 'project_name')?>
				<?=form_error('project_name')?>
			</div>
			<div>
				<?php $extra = 'maxlength="80" size="40"'; ?>
				<?=form_input('project_name', set_value('project_name', $project->name), $extra)?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Summary').': ', 'project_summary')?>
				<?=form_error('project_summary')?>
			</div>
			<div>
				<?php $extra = 'maxlength="80" size="50"'; ?>
				<?=form_input('project_summary', set_value('project_summary', $project->summary), $extra)?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Description').': ', 'project_description')?>
				<?=form_error('project_description')?>
			</div>
			<div>
				<?php
					$xdata = array (
						'name' => 'project_description',
						'value' => set_value ('project_description', $project->description),
						'id' => 'project_edit_mainarea_description',
						'rows' => 20,
						'cols' => 80
					);
					print form_textarea ($xdata);
				?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Members').': ', 'project_members')?>
				<?=form_error('project_members')?>
			</div>
			<div>
				<?php
					$xdata = array (
						'name' => 'project_members',
						'value' => set_value ('project_members', $project->members),
						'id' => 'project_edit_mainarea_members',
						'rows' => 2,
						'cols' => 80
					);
					print form_textarea ($xdata);
				?>
			</div>
		</div>

		<div>
			<?php $caption = ($mode == 'update')? $this->lang->line('Update'): $this->lang->line('Create'); ?>
			<?=form_submit('project', $caption)?>
		</div>

	<?=form_fieldset_close()?>
<?=form_close();?>

</div> <!-- project_edit_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!-- project_edit_content --> 

</body>

</html>
