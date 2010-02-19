<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><?=htmlspecialchars($site->name)?></title>
</head>

<body>

<div class="content" id="site_edit_content">

<!------------------------------------------------------------------------>

<?php $this->load->view ('taskbar'); ?>

<!------------------------------------------------------------------------>

<?php
$this->load->view (
	'projectbar',
	array (
		'site' => NULL,
		'project' => NULL,
		'pageid' => 'site',
		'ctxmenuitems' => array ()
	)
);
?>

<!------------------------------------------------------------------------>
<div class="mainarea" id="site_edit_mainarea">

<?php 
	if ($message != '') print "<div id='site_create_message' class='form_message'>$message</div>"; 

	$formurl = "site/{$mode}";
	if ($mode == 'update') $formurl .= '/'.$site->id;
?>

<?=form_open($formurl)?>
	<?=form_fieldset()?>
		<div>
			<div>
				<?=form_label($this->lang->line('ID').': ', 'site_id')?>
				<?=form_error('site_id')?>
			</div>
			<div>
				<?php
					$extra = ($mode == 'update')? 'readonly="readonly"': '';
					$extra .= 'maxlength="32" size="16"';
				?>

				<?=form_input('site_id', set_value('site_id', $site->id), $extra)?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Name').': ', 'site_name')?>
				<?=form_error('site_name')?>
			</div>
			<div>
				<?php $extra = 'maxlength="80" size="40"'; ?>
				<?=form_input('site_name', set_value('site_name', $site->name), $extra)?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Text').': ', 'site_text')?>
				<?=form_error('site_text')?>
			</div>
			<div>
				<?=form_textarea('site_text', set_value('site_text', $site->text))?>
			</div>
		</div>

		<div>
			<?php $caption = ($mode == 'update')? $this->lang->line('Update'): $this->lang->line('Create'); ?>
			<?=form_submit('site', $caption)?>
		</div>

	<?=form_fieldset_close()?>
<?=form_close();?>

</div> <!-- site_edit_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!-- site_edit_content --> 

</body>

</html>
