<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/file.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/creole.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/prettify/prettify.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-css.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lisp.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lua.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-sql.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-vb.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<script type="text/javascript">

function render_wiki(input_text)
{
	creole_render_wiki_with_input_text (
		input_text,
		"file_edit_mainarea_description_preview", 
		"<?php print site_url()?>/wiki/show/<?php print $project->id?>/",
		"<?php print site_url()?>/wiki/attachment0/<?php print $project->id?>/"
	);

	prettyPrint ();
}

$(function () {
	$("#file_edit_mainarea_description_preview_button").button().click(
		function () {
			render_wiki ($("#file_edit_mainarea_description").val());
		}
	);
});

</script>

<title><?php print htmlspecialchars($file->name)?></title>
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

<div class="form_container">
<?php print form_open_multipart("file/$mode/{$project->id}/" . $this->converter->AsciiToHex($file->name))?>

	<div class='form_input_field'>
		<?php print form_label($this->lang->line('Name').': ', 'file_name')?>
		<?php 
			$extra = ($mode == 'update')? 'readonly="readonly"': ''; 
			$extra .= 'maxlength="255" size="40"';
			if ($mode == 'update')
				print form_input('file_name', set_value('file_name', $file->name), $extra);
			else
				print form_upload('file_name', set_value('file_name', $file->name), $extra);
		?>
		<?php print form_error('file_name');?>
	</div>

	<div class='form_input_field'>
		<?php print form_label($this->lang->line('Tag').': ', 'file_tag')?>
		<?php 
			$extra = 'maxlength="50" size="25"';
		?>
		<?php print form_input('file_tag', set_value('file_tag', $file->tag), $extra)?>
		<?php print form_error('file_tag');?>
	</div>
	
	<div class='form_input_label'>
		<?php print form_label($this->lang->line('Summary').': ', 'file_summary')?>
		<?php print form_error('file_summary');?>
	</div>
	<div class='form_input_field'>
		<?php 
			$extra = 'maxlength="255" size="80"';
		?>
		<?php print form_input('file_summary', set_value('file_summary', $file->summary), $extra)?>
	</div>
	
	<div class='form_input_label'>
		<?php print form_label($this->lang->line('Description').': ', 'file_description')?>
		<a href='#' id='file_edit_mainarea_description_preview_button'><?php print $this->lang->line('Preview')?></a>
		<?php print form_error('file_description');?>
	</div>
	<div class='form_input_field'>
		<?php print form_textarea('file_description', set_value('file_description', $file->description), 'id=file_edit_mainarea_description')?>
	</div>
	<div id='file_edit_mainarea_description_preview' class='form_input_preview'></div>

	
	<?php print form_hidden('file_projectid', set_value('file_projectid', $file->projectid))?>

	<?php $caption = ($mode == 'update')? $this->lang->line('Update'): $this->lang->line('Create'); ?>
	<?php print form_submit('file', $caption)?>

<?php print form_close();?>
</div>

</div> <!-- file_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>

</body>

</html>
