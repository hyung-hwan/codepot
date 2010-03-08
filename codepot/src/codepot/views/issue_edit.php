<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><?=htmlspecialchars($issue->name)?></title>
</head>

<body>

<div class="content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$hexname = $this->converter->AsciiToHex ($issue->name);
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

<?=form_open("issue/{$mode}/".$project->id.'/'.$this->converter->AsciiToHex($issue->name))?>
	<?=form_fieldset()?>
		<div>
			<div>
				<?=form_label($this->lang->line('Name').': ', 'issue_name')?>
				<?=form_error('issue_name');?>
			</div>
			<div>
				<?php 
					$extra = ($mode == 'update')? 'readonly="readonly"': ''; 
					$extra .= 'maxlength="80" size="40"';
				?>
				<?=form_input('issue_name', set_value('issue_name', $issue->name), $extra)?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Text').': ', 'issue_text')?>
				<?=form_error('issue_text');?>
			</div>
			<div>
				<?=form_textarea('issue_text', set_value('issue_text', $issue->text))?>
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
