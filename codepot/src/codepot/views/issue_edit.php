<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />

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
		'site' => NULL,
		'pageid' => 'issue',
		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="issue_edit_mainarea">

<?php if ($message != "") print '<div id="issue_edit_message" class="form_message">'.htmlspecialchars($message).'</div>'; ?>

<?=form_open("issue/{$mode}/{$project->id}/".$this->converter->AsciiToHex($issue->id))?>
	<?=form_fieldset()?>
		<div>
			<?php if ($mode == 'create'): ?>
				<?=form_hidden('issue_id', set_value('issue_id', $issue->id))?>
			<?php else: ?>
				<div>
					<?=form_label($this->lang->line('ID').': ', 'issue_id')?>
					<?=form_error('issue_id');?>
				</div>
				<div>
					<?=form_input('issue_id', set_value('issue_id', $issue->id), 'readonly="readonly"')?>
				</div>
			<?php endif; ?>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Summary').': ', 'issue_summary')?>
				<?=form_error('issue_summary');?>
			</div>
			<div>
				<?=form_input('issue_summary', set_value('issue_summary', $issue->summary), 'size="80"')?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Description').': ', 'issue_description')?>
				<?=form_error('issue_description');?>
			</div>
			<div>
				<?=form_textarea('issue_description', set_value('issue_description', $issue->description), 'id="issue_description"')?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Type').': ', 'issue_type')?>
				<?=form_error('issue_type');?>
			</div>
			<div>
				<?=form_input('issue_type', set_value('issue_type', $issue->type), 'id="issue_type"')?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Priority').': ', 'issue_priority')?>
				<?=form_error('issue_priority');?>
			</div>
			<div>
				<?=form_input('issue_priority', set_value('issue_priority', $issue->priority))?>
			</div>
		</div>


		<div>
			<div>
				<?=form_label($this->lang->line('Status').': ', 'issue_status')?>
				<?=form_error('issue_status');?>
			</div>
			<div>
				<?=form_input('issue_status', set_value('issue_status', $issue->status))?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Owner').': ', 'issue_owner')?>
				<?=form_error('issue_owner');?>
			</div>
			<div>
				<?=form_input('issue_owner', set_value('issue_owner', $issue->owner))?>
			</div>
		</div>
		
		<div>
			<?=form_hidden('issue_projectid', set_value('issue_projectid', $issue->projectid))?>
		</div>

		<div>
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
