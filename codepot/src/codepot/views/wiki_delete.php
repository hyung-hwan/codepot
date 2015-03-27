<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/wiki.css')?>" />

<title><title><?=htmlspecialchars($wiki->name)?></title></title>
</head>

<body>

<div class="content" id="wiki_delete_content">

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
			'id' => 'wiki',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea">

<?php if ($message != "") print '<div id="wiki_delete_message" class="form_message">'.htmlspecialchars($message).'</div>'; ?>

<div class="form_container">
<?=form_open("wiki/delete/{$project->id}/".$this->converter->AsciiToHex($wiki->name))?>

	<div>
		<div>
			<?=form_checkbox('wiki_confirm', 'yes', set_checkbox('wiki_confirm', $wiki_confirm))?>
			<?=$this->lang->line('MSG_SURE_TO_DELETE_THIS')?> - <?=htmlspecialchars($wiki->name)?>
			<?=form_error('wiki_confirm')?>
		</div>
	</div>

	<div>
		<?=form_hidden('wiki_projectid', set_value('wiki_projectid', $wiki->projectid))?>
		<?=form_hidden('wiki_name', set_value('wiki_name', $wiki->name))?>
	</div>

	<div>
		<?=form_submit('wiki', $this->lang->line('Delete'))?>
	</div>

<?=form_close();?>
</div>

</div> <!-- mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>  <!-- wiki_delete_content -->

</body>

</html>
