<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/issue.css')?>" />

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
		"issue_edit_mainarea_description_preview", 
		"<?php print site_url()?>/wiki/show/<?php print $project->id?>/",
		"<?php print site_url()?>/wiki/attachment0/<?php print $project->id?>/"
	);
	prettyPrint ();
}

$(function () {
	$("#issue_edit_mainarea_description_preview_button").button().click(
		function () {
			render_wiki ($("#issue_edit_mainarea_description").val());
		}
	);
});

</script>


<title><?php print htmlspecialchars($issue->id)?></title>
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
			'id' => 'issue',
			'project' => $project,
		),

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

<div class='form_container'>
<?php print form_open("issue/{$mode}/{$project->id}/".$this->converter->AsciiToHex($issue->id))?>
	<div>
		<?php print form_hidden('issue_id', set_value('issue_id', $issue->id))?>
		<?php print form_hidden('issue_projectid', set_value('issue_projectid', $issue->projectid))?>
		<?php print form_hidden('issue_status', set_value('issue_status', $issue->status))?>
		<?php print form_hidden('issue_priority', set_value('issue_priority', $issue->priority))?>
		<?php print form_hidden('issue_owner', set_value('issue_owner', $issue->owner))?>
	</div>

	<div id='issue_edit_mainarea_type' class='form_input_field'>
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
			'id="issue_edit_mainarea_type"');
		print form_error('issue_type');
	}
	?>
	</div>

	<div class='form_input_label'>
		<?php print form_label($this->lang->line('Summary').': ', 'issue_summary')?>
		<?php print form_error('issue_summary');?>
	</div>
	<div class='form_input_field'>
		<?php print form_input('issue_summary', 
			set_value('issue_summary', $issue->summary), 
			'size="80" id="issue_edit_mainarea_summary"')
		?>
	</div>

	<div class='form_input_label'>
		<?php print form_label($this->lang->line('Description').': ', 'issue_description')?>
		<a href='#' id='issue_edit_mainarea_description_preview_button'><?php print $this->lang->line('Preview')?></a>
		<?php print form_error('issue_description');?>
	</div>
	<div class='form_input_field'>
	<?php
		$xdata = array (
			'name' => 'issue_description',
			'value' => set_value ('issue_description', $issue->description),
			'id' => 'issue_edit_mainarea_description',
			'rows' => 20,
			'cols' => 80
		);
		print form_textarea ($xdata);
	?>
	</div>
	<div id='issue_edit_mainarea_description_preview' class='form_input_preview'></div>


	<?php $caption = ($mode == 'update')? $this->lang->line('Update'): $this->lang->line('Create'); ?>
	<?php print form_submit('issue', $caption)?>

<?php print form_close();?>
</div> <!-- form_container -->

</div> <!-- issue_edit_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>

</body>

</html>
