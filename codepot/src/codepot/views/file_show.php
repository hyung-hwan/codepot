<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/file.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

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
$hexname = $this->converter->AsciiToHex ($file->name);
?>

<script type="text/javascript">
function show_alert (outputMsg, titleMsg) 
{
	$('#file_show_mainarea_alert').html(outputMsg).dialog({
		title: titleMsg,
		resizable: true,
		modal: true,
		width: 'auto',
		height: 'auto',
		buttons: {
			"OK": function () {
				$(this).dialog("close");
			}
		}
	});
}

function render_wiki()
{
	creole_render_wiki (
		"file_show_mainarea_wiki_text", 
		"file_show_mainarea_wiki", 
		"<?php print site_url()?>/wiki/show/<?php print $project->id?>/",
		"<?php print site_url()?>/wiki/attachment0/<?php print $project->id?>/"
	);

	prettyPrint ();
}

var delete_in_progress = false;

$(function () {
	if ($("#file_show_mainarea_result_info").is(":visible"))
		btn_label = "<?php print $this->lang->line('Hide metadata')?>";
	else
		btn_label = "<?php print $this->lang->line('Show metadata')?>";


	btn = $('#file_show_mainarea_metadata_button').button({'label': btn_label}).click (function () {
		
		if ($('#file_show_mainarea_result_info').is(':visible'))
		{
			$('#file_show_mainarea_result_info').hide('blind',{},200);
			$('#file_show_mainarea_metadata_button').button(
				'option', 'label', "<?php print $this->lang->line('Show metadata')?>");
		}
		else
		{
			$('#file_show_mainarea_result_info').show('blind',{},200);
			$('#file_show_mainarea_metadata_button').button(
				'option', 'label', '<?php print $this->lang->line('Hide metadata')?>');
		}
	});

<?php if (isset($login['id']) && $login['id'] != ''): ?>

	$('#file_show_mainarea_delete_form_div').dialog (
		{
			title: '<?php print $this->lang->line('Delete');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'<?php print $this->lang->line('OK')?>': function () {
					if (delete_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						delete_in_progress = true;

						var form_data = new FormData();

						var f = $('#file_show_mainarea_delete_confirm');
						if (f != null && f.is(':checked')) form_data.append ('file_delete_confirm', 'Y');

						$('#file_show_mainarea_delete_form_div').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/file/xhr_delete/{$project->id}/{$hexname}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								delete_in_progress = false;
								$('#file_show_mainarea_delete_form_div').dialog('enable');
								$('#file_show_mainarea_delete_form_div').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/file/home/{$project->id}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								delete_in_progress = false;
								$('#file_show_mainarea_delete_form_div').dialog('enable');
								$('#file_show_mainarea_delete_form_div').dialog('close');
								show_alert ('Failed - ' + errorThrown, "<?php print $this->lang->line('Error')?>");
							}
						});
					}
					else
					{
						show_alert ('<pre>NOT SUPPORTED</pre>', "<?php print $this->lang->line('Error')?>");
					}
				},
				'<?php print $this->lang->line('Cancel')?>': function () {
					if (delete_in_progress) return;
					$('#file_show_mainarea_delete_form_div').dialog('close');
				}

			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !delete_in_progress;
			}
		}
	);

	$('#file_show_mainarea_delete_button').button().click (function() {
		$('#file_show_mainarea_delete_form_div').dialog('open');
		return false;
	});

	$('#file_show_mainarea_add_file_button').button ();
	$('#file_show_mainarea_delete_file_button').button ();
<?php endif; ?>

	render_wiki ();
});

</script>

<title><?php print htmlspecialchars($project->name)?> - <?php print htmlspecialchars($file->name)?></title>
</head>

<body>

<div class="content" id="file_show_content">

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

		'ctxmenuitems' => array (
			array ("file/update/{$project->id}/{$hexname}", '<i class="fa fa-edit"></i> ' . $this->lang->line('Edit'))
		)
	)
);
?>

<!---------------------------------------------------------------------------->


<div class="mainarea" id="file_show_mainarea">
<div class="title"><?php print htmlspecialchars($file->name)?></div>

<div class="infostrip" id="wiki_show_mainarea_infostrip">
	<?php if (isset($login['id']) && $login['id'] != ''): ?>
	<a id="file_show_mainarea_delete_button" href='#'><?php print $this->lang->line('Delete')?></a>
	<?php endif; ?>
	<a id="file_show_mainarea_metadata_button" href='#'><?php print $this->lang->line('Metadata')?></a>
</div>

<div id="file_show_mainarea_result">


<div class="result" id="file_show_mainarea_wiki">
<pre id="file_show_mainarea_wiki_text" style="visibility: hidden">
<?php print htmlspecialchars($file->description); ?>
</pre>
</div> <!-- file_show_mainarea_wiki -->


<div id="file_show_mainarea_result_info" class="infobox">

	<ul>
	<li><?php print $this->lang->line('Created on')?> <?php print  $file->createdon ?></li>
	<li><?php print $this->lang->line('Created by')?> <?php print  $file->createdby ?></li>
	<li><?php print $this->lang->line('Last updated on')?> <?php print  $file->updatedon ?></li>
	<li><?php print $this->lang->line('Last updated by')?> <?php print  $file->updatedby ?></li>
	</ul>

	<div class="title"><?php print $this->lang->line('Files')?></div>
	<?php if (isset($login['id']) && $login['id'] != ''): ?>
<!--
	<div>
		<a id="file_show_mainarea_add_file_button" href='#'><?php print $this->lang->line('Add')?></a>
		<a id="file_show_mainarea_delete_file_button" href='#'><?php print $this->lang->line('Delete')?></a>
	</div>
-->
	<?php endif; ?>
	<ul>
	<?php
	foreach ($file->file_list as $f)
	{
		$xname = $this->converter->AsciiToHex($f->filename);
		print '<li>'; 
		print anchor ("file/get/{$project->id}/{$xname}", $f->filename);
		print " <tt>{$f->md5sum}</tt>";
		print '</li>';
	}
	?>
	</ul>

</div> <!-- file_show_mainarea_result_info -->

</div> <!-- file_show_mainarea_result -->


<div id='file_show_mainarea_delete_form_div'>
	<input type='checkbox' id='file_show_mainarea_delete_confirm' />
	<?php print $this->lang->line('MSG_SURE_TO_DELETE_THIS') . ' - ' . htmlspecialchars($file->name); ?>
</div>

<div id='file_show_mainarea_alert'></div>

</div> <!-- file_show_mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  file_show_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>

</html>

