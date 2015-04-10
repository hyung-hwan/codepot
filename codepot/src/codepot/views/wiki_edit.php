<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/wiki.css')?>" />

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

<?php
$hexname = $this->converter->AsciiToHex ($wiki->name);
?>

<script type="text/javascript">

function render_wiki(input_text)
{
	var column_count = $("#wiki_edit_mainarea_text_column_count").val();
	var x_column_count = parseInt (column_count);
	if (isNaN(x_column_count) || x_column_count < 1) x_column_count = 1;
	else if (x_column_count > 9) x_column_count = 9; // sync this max value with wiki_show. TODO: put this into codepot.ini

	column_count = x_column_count.toString();
	$("#wiki_edit_mainarea_text_column_count").val(column_count);

	$("#wiki_edit_mainarea_text_preview").css ({
		"-moz-column-count":    column_count,
		"-webkit-column-count": column_count,
		"column-count":         column_count
	});

	creole_render_wiki_with_input_text (
		input_text,
		"wiki_edit_mainarea_text_preview", 
		"<?php print site_url()?>/wiki/show/<?php print $project->id?>/",
		"<?php print site_url()?>/wiki/attachment/<?php print $project->id?>/<?php print $hexname?>/"
	);

	prettyPrint ();
}

var new_attachment_no = 0;

$(function () {
	$('#wiki_edit_more_new_attachment').button().click (
		function () {
			var html = [
				'<li><input type="file" name="wiki_new_attachment_',
				++new_attachment_no,
				'" /></li>'
			].join("");
			$('#wiki_edit_new_attachment_list').append (html);
			return false;
		}
	);

	$("#wiki_edit_mainarea_text_preview_button").button().click(
		function () {
			render_wiki ($("#wiki_edit_mainarea_text").val());
		}
	);
});
</script>

<title><?php print htmlspecialchars($wiki->name)?></title>
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
			'id' => 'wiki',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="wiki_edit_mainarea">

<?php if ($message != "") print '<div id="wiki_edit_message" class="form_message">'.htmlspecialchars($message).'</div>'; ?>

<div class="form_container">
<?php print form_open_multipart("wiki/{$mode}/{$project->id}/".$this->converter->AsciiToHex($wiki->name))?>

	<div class='form_input_label'>
		<?php print form_label($this->lang->line('Name').': ', 'wiki_name')?>
		<?php print form_error('wiki_name');?>
	</div>
	<div class='form_input_field'>
		<?php 
			$extra = 'maxlength="80" size="40" id="wiki_edit_mainarea_name"';
			//$extra .= ($mode == 'update')? ' readonly="readonly"': ''; 
		?>
		<?php print form_input('wiki_name', set_value('wiki_name', $wiki->name), $extra)?>
		<?php print ($mode == 'update')? form_hidden('wiki_original_name', set_value('wiki_original_name', $wiki->name)): ''?>
	</div>

	<div class='form_input_label'>
		<?php print form_label($this->lang->line('Text').': ', 'wiki_text')?>
		<a href='#' id='wiki_edit_mainarea_text_preview_button'><?php print $this->lang->line('Preview')?></a>

		<?php
			$attrs = array (
				'name' => 'wiki_columns',
				'id' => 'wiki_edit_mainarea_text_column_count',
				'value' => set_value('wiki_columns', $wiki->columns),
				'size' => '2',
				'min' => '1',
				'max' => '9',
				'type' => 'number');
			print form_input ($attrs);
		?>
		
		<?php print $this->lang->line('Columns')?>(1-9)

		<?php print form_error('wiki_text');?>
	</div>
	<div class='form_input_field'>
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
	<div id='wiki_edit_mainarea_text_preview' class='form_input_preview'></div>

	<?php if (!empty($wiki->attachments)): ?>
	<div class='form_input_label'>
		<?php print form_label($this->lang->line('WIKI_ATTACHMENTS').': ', 'wiki_edit_attachment_list')?> 
		<?php print form_error('wiki_attachment_list');?>
	</div>

	<div class='form_input_field'>
		<ul id='wiki_edit_attachment_list'>
		<?php
			foreach ($wiki->attachments as $att)
			{
				$hexattname = 
					$this->converter->AsciiToHex($att->name) . 
					'@' .
					$this->converter->AsciiToHex($att->encname);
				$escattname = htmlspecialchars($att->name);

				print '<li>';
				print "<input type='checkbox' name='wiki_delete_attachment[]' value='{$hexattname}' title='Check to delete {$escattname}'/>";
				print $escattname;
				print '</li>';
			}
		?>
		</ul>
	</div>
	<?php endif; ?>

	<div class='form_input_label'>
		<?php print form_label($this->lang->line('WIKI_NEW_ATTACHMENTS').': ', 'wiki_edit_new_attachment_list')?> 
		<a href='#' id='wiki_edit_more_new_attachment'>
			<?php print $this->lang->line('WIKI_MORE_NEW_ATTACHMENTS')?>
		</a>
		<?php print form_error('wiki_edit_new_attachment_list');?>
	</div>

	<div class='form_input_field'>
	<ul id='wiki_edit_new_attachment_list'>
	<li>	
		<input type='file' name='wiki_new_attachment_0' />
		<!--<input type='checkbox' name='wiki_delete_attachment[]' value='delete'/>Delete-->
	</li>
	</ul>

	</div>
	
	<div>
		<?php print form_hidden('wiki_projectid', set_value('wiki_projectid', $wiki->projectid))?>
	</div>

	<div>
		<?php $caption = ($mode == 'update')? $this->lang->line('Update'): $this->lang->line('Create'); ?>
		<?php print form_submit('wiki', $caption)?>
	</div>

<?php print form_close();?>
</div> <!-- form_container -->

</div> <!-- wiki_edit_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>

</body>

</html>
