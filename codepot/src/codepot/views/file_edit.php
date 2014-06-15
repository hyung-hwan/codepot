<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/file.css')?>" />

<script type="text/javascript" src="<?=base_url_make('/js/creole.js')?>"></script>

<script type="text/javascript" src="<?=base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/jquery-ui.css')?>" />



<script type="text/javascript">

function render_wiki(input_text)
{
        creole_render_wiki_with_input_text (
		input_text,
                "file_edit_mainarea_description_preview", 
		"<?=site_url()?>/wiki/show/<?=$project->id?>/",
		"<?=site_url()?>/wiki/attachment0/<?=$project->id?>/"
        );
}

$(function () {
	$("#file_edit_mainarea_description_preview_button").button().click(
		function () {
			render_wiki ($("#file_edit_mainarea_description").val());
		}
	);
});

</script>

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
		'banner' => NULL,

		'page' => array (
			'type' => 'project',
			'id' => 'file',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="file_mainarea">

<?php if ($message != "") print '<div id="file_message" class="form_message">'.htmlspecialchars($message).'</div>'; ?>

<?=form_open_multipart("file/$mode/{$project->id}/" . $this->converter->AsciiToHex($file->name))?>
	<?=form_fieldset()?>
		<div class='form_input_field'>
			<?=form_label($this->lang->line('Name').': ', 'file_name')?>
			<?php 
				$extra = ($mode == 'update')? 'readonly="readonly"': ''; 
				$extra .= 'maxlength="255" size="40"';
				if ($mode == 'update')
					print form_input('file_name', set_value('file_name', $file->name), $extra);
				else
					print form_upload('file_name', set_value('file_name', $file->name), $extra);
			?>
			<?=form_error('file_name');?>
		</div>

		<div class='form_input_field'>
			<?=form_label($this->lang->line('Tag').': ', 'file_tag')?>
			<?php 
				$extra = 'maxlength="50" size="25"';
			?>
			<?=form_input('file_tag', set_value('file_tag', $file->tag), $extra)?>
			<?=form_error('file_tag');?>
		</div>
		
		<div class='form_input_label'>
			<?=form_label($this->lang->line('Summary').': ', 'file_summary')?>
			<?=form_error('file_summary');?>
		</div>
		<div class='form_input_field'>
			<?php 
				$extra = 'maxlength="255" size="80"';
			?>
			<?=form_input('file_summary', set_value('file_summary', $file->summary), $extra)?>
		</div>
		
		<div class='form_input_label'>
			<?=form_label($this->lang->line('Description').': ', 'file_description')?>
			<a href='#' id='file_edit_mainarea_description_preview_button'>Preview</a>
			<?=form_error('file_description');?>
		</div>
		<div class='form_input_field'>
			<?=form_textarea('file_description', set_value('file_description', $file->description), 'id=file_edit_mainarea_description')?>
		</div>
		<div id='file_edit_mainarea_description_preview' class='form_input_preview'></div>

		
		<?=form_hidden('file_projectid', set_value('file_projectid', $file->projectid))?>

		<?php $caption = ($mode == 'update')? $this->lang->line('Update'): $this->lang->line('Create'); ?>
		<?=form_submit('file', $caption)?>

	<?=form_fieldset_close()?>
<?=form_close();?>

</div> <!-- file_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>

</body>

</html>
