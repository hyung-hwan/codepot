<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/wiki.css" />
<title><?=htmlspecialchars($wiki->name)?></title>
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
		'pageid' => 'wiki',
		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="wiki_edit_mainarea">

<?php if ($message != "") print '<div id="wiki_edit_message" class="form_message">'.htmlspecialchars($message).'</div>'; ?>

<?=form_open("wiki/{$mode}/{$project->id}/".$this->converter->AsciiToHex($wiki->name))?>
	<?=form_fieldset()?>
		<div>
			<div>
				<?=form_label($this->lang->line('Name').': ', 'wiki_name')?>
				<?=form_error('wiki_name');?>
			</div>
			<div>
				<?php 
					$extra = ($mode == 'update')? 'readonly="readonly"': ''; 
					$extra .= 'maxlength="80" size="40" id="wiki_edit_mainarea_name"';
				?>
				<?=form_input('wiki_name', set_value('wiki_name', $wiki->name), $extra)?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Text').': ', 'wiki_text')?>
				<?=form_error('wiki_text');?>
			</div>
			<div>
				<?php
					$xdata = array (
						'name' => 'wiki_text',
						'value' => set_value ('wiki_text', $wiki->text),
						'id' => 'wiki_edit_mainarea_text',
						'rows' => 20,
						'cols' => 80
					);
					print form_textarea ($xdata);
				?>
			</div>
		</div>
		
		<div>
			<?=form_hidden('wiki_projectid', set_value('wiki_projectid', $wiki->projectid))?>
		</div>

		<div>
			<?php $caption = ($mode == 'update')? $this->lang->line('Update'): $this->lang->line('Create'); ?>
			<?=form_submit('wiki', $caption)?>
		</div>

	<?=form_fieldset_close()?>
<?=form_close();?>

</div> <!-- wiki_edit_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>

</body>

</html>
