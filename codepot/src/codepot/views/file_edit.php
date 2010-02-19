<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><?=htmlspecialchars($file->name)?></title>
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
		'pageid' => 'file',
		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="file_edit_mainarea">

<?php if ($message != "") print '<div id="file_edit_message" class="form_message">'.htmlspecialchars($message).'</div>'; ?>

<?=form_open_multipart("file/$mode/{$project->id}/" . $this->converter->AsciiToHex($file->name))?>
	<?=form_fieldset()?>
		<div>
			<div>
				<?=form_label($this->lang->line('Name').': ', 'file_name')?>
				<?=form_error('file_name');?>
			</div>
			<div>
				<?php 
					$extra = ($mode == 'update')? 'readonly="readonly"': ''; 
					$extra .= 'maxlength="255" size="40"';
					if ($mode == 'update')
						print form_input('file_name', set_value('file_name', $file->name), $extra);
					else
						print form_upload('file_name', set_value('file_name', $file->name), $extra);
				?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Tag').': ', 'file_tag')?>
				<?=form_error('file_tag');?>
			</div>
			<div>
				<?php 
					$extra = 'maxlength="50" size="25"';
				?>
				<?=form_input('file_tag', set_value('file_tag', $file->tag), $extra)?>
			</div>
		</div>
		
		<div>
			<div>
				<?=form_label($this->lang->line('Summary').': ', 'file_summary')?>
				<?=form_error('file_summary');?>
			</div>
			<div>
				<?php 
					$extra = 'maxlength="255" size="80"';
				?>
				<?=form_input('file_summary', set_value('file_summary', $file->summary), $extra)?>
			</div>
		</div>
		
		<div>
			<div>
				<?=form_label($this->lang->line('Description').': ', 'file_description')?>
				<?=form_error('file_description');?>
			</div>
			<div>
				<?=form_textarea('file_description', set_value('file_description', $file->description))?>
			</div>
		</div>
		
		<div>
			<?=form_hidden('file_projectid', set_value('file_projectid', $file->projectid))?>
		</div>

		<div>
			<?php $caption = ($mode == 'update')? $this->lang->line('Update'): $this->lang->line('Create'); ?>
			<?=form_submit('file', $caption)?>
		</div>

	<?=form_fieldset_close()?>
<?=form_close();?>

</div> <!-- file_edit_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>

</body>

</html>
