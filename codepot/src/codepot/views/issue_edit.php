<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/issue.css" />

<title><?=htmlspecialchars($issue->id)?></title>
</head>

<body>

<div class="content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,
		'site' => NULL,
		'pageid' => 'issue',
		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="issue_edit_mainarea">

<?php 
	if ($message != "") 
	{
		print '<div id="issue_edit_message" class="form_message">';
		print htmlspecialchars($message);
		print '</div>'; 
	}
?>

<?=form_open("issue/{$mode}/{$project->id}/".$this->converter->AsciiToHex($issue->id))?>
	<?=form_fieldset()?>
		<div>
			<?=form_hidden('issue_id', set_value('issue_id', $issue->id))?>
			<?=form_hidden('issue_projectid', set_value('issue_projectid', $issue->projectid))?>
			<?=form_hidden('issue_status', set_value('issue_status', $issue->status))?>
			<?=form_hidden('issue_priority', set_value('issue_priority', $issue->priority))?>
			<?=form_hidden('issue_owner', set_value('issue_owner', $issue->owner))?>
		</div>

		<div id='issue_edit_mainarea_type'>
		<?php
		if ($mode == 'update')
		{
			print form_hidden('issue_type', set_value('issue_type', $issue->type));
		}
		else
		{
			print form_label($this->lang->line('Type').': ', 'issue_type');
			print form_dropdown (
				'issue_type', 
				$issue_type_array,
				set_value('issue_type', $issue->type),
				'id="project_issue_type"');
			print form_error('issue_type');
		}
		?>
		</div>

		<div id='issue_edit_mainarea_summary'>
			<div>
				<?=form_label($this->lang->line('Summary').': ', 'issue_summary')?>
				<?=form_error('issue_summary');?>
			</div>
			<div>
				<?=form_input('issue_summary', 
					set_value('issue_summary', $issue->summary), 
					'size="80" id="project_issue_summary"')
				?>
			</div>
		</div>

		<div id='issue_edit_mainarea_description'>
			<div>
				<?=form_label($this->lang->line('Description').': ', 'issue_description')?>
				<?=form_error('issue_description');?>
			</div>
			<div>
			<?php
				$xdata = array (
					'name' => 'issue_description',
                                        'value' => set_value ('issue_description', $issue->description),
                                        'id' => 'project_issue_description',
                                        'rows' => 20,
                                        'cols' => 80
                                );
                                print form_textarea ($xdata);
                        ?>
			</div>
		</div>

		<div id='issue_edit_mainarea_buttons'>
			<?php $caption = ($mode == 'update')? $this->lang->line('Update'): $this->lang->line('Create'); ?>
			<?=form_submit('issue', $caption)?>
		</div>

	<?=form_fieldset_close()?>
<?=form_close();?>

</div> <!-- issue_edit_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>

</body>

</html>
