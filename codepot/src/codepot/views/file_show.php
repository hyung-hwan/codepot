<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

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
$file_count = count ($file->file_list);

$creole_base = site_url() . "/wiki/show/{$project->id}/"; 
$creole_file_base = site_url() . "/wiki/attachment0/{$project->id}/"; 
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
		"<?php print $creole_base; ?>",
		"<?php print $creole_file_base; ?>/"
	);

	prettyPrint ();
}

function preview_edit_description (input_text)
{
	creole_render_wiki_with_input_text (
		input_text,
		"file_show_mainarea_edit_description_preview", 
		"<?php print $creole_base; ?>",
		"<?php print $creole_file_base; ?>/"
	);

	prettyPrint ();
}

var populated_file_obj_for_adding = [];
var populated_file_max_for_adding = 0;

function populate_selected_files_for_adding ()
{
	var file_desc = {};
	for (var n = 0; n < populated_file_max_for_adding; n++)
	{
		var f = populated_file_obj_for_adding[n];
		if (f != null)
		{
			var d = $('#file_show_mainarea_add_file_desc_' + n);
			if (d != null) file_desc[f.name] = d.val();
		}
	}

	$('#file_show_mainarea_add_file_table').empty();
	populated_file_obj_for_adding = [];

	var f = $('#file_show_mainarea_add_files').get(0);
	var f_no = 0;
	for (var n = 0; n < f.files.length; n++)
	{
		if (f.files[n] != null) 
		{
			var desc = file_desc[f.files[n].name];
			if (desc == null) desc = '';

			$('#file_show_mainarea_add_file_table').append (
				codepot_sprintf (
					'<tr id="file_show_mainarea_add_file_row_%d"><td><a href="#" id="file_show_mainarea_add_file_cancel_%d" onClick="cancel_out_add_file(%d); return false;"><i class="fa fa-trash"></i></a></td><td>%s</td><td><input type="text" id="file_show_mainarea_add_file_desc_%d" size="40" value="%s" /></td></tr>', 
					f_no, f_no, f_no, codepot_htmlspecialchars(f.files[n].name), f_no, codepot_addslashes(desc)
				)
			);

			populated_file_obj_for_adding[f_no] = f.files[n];
			f_no++;
		}
	}

	populated_file_max_for_adding = f_no;
}


function cancel_out_add_file (no)
{
	$('#file_show_mainarea_add_file_row_' + no).remove ();
	populated_file_obj_for_adding[no] = null;
}

function kill_edit_file (no)
{
	var n = $('#file_show_mainarea_edit_file_name_' + no);
	var d = $('#file_show_mainarea_edit_file_desc_' + no);
	if (n && d)
	{
		if (d.prop('disabled'))
		{
			n.css ('text-decoration', '');
			d.prop ('disabled', false);
		}
		else
		{
			n.css ('text-decoration', 'line-through');
			d.prop ('disabled', true);
		}
	}
}

var work_in_progress = false;

var original_file_name = [
	<?php
	for ($i = 0; $i < $file_count; $i++)
	{
		$f = $file->file_list[$i];
		printf ("%s\t'%s'", (($i == 0)? '': ",\n"), addslashes($f->filename));
	}
	print "\n";
	?>
];

var original_file_desc = [
	<?php
	for ($i = 0; $i < $file_count; $i++)
	{
		$f = $file->file_list[$i];
		printf ("%s\t'%s'", (($i == 0)? '': ",\n"), addslashes($f->description));
	}
	print "\n";
	?>
];

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

	$('#file_show_mainarea_files').accordion({
		collapsible: true
	});


<?php if (isset($login['id']) && $login['id'] != ''): ?>

	$('#file_show_mainarea_edit_description_tabs').tabs ();
	$('#file_show_mainarea_edit_description_tabs').bind ('tabsshow', function (event, ui) {
		if (ui.index == 1) preview_edit_description ($('#file_show_mainarea_edit_description').val());
	});

	$('#file_show_mainarea_edit_form').dialog (
		{
			title: '<?php print $this->lang->line('Edit');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {

				'<?php print $this->lang->line('OK')?>': function () {
					if (work_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						work_in_progress = true;

						var form_data = new FormData();

						var new_name = $('#file_show_mainarea_edit_name').val()

						form_data.append ('file_edit_name', new_name);
						form_data.append ('file_edit_tag', $('#file_show_mainarea_edit_tag').val());
						form_data.append ('file_edit_description', $('#file_show_mainarea_edit_description').val());

						$('#file_show_mainarea_edit_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/file/xhr_update/{$project->id}/{$hexname}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#file_show_mainarea_edit_form').dialog('enable');
								$('#file_show_mainarea_edit_form').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/file/show/{$project->id}/"; ?>' + codepot_ascii_to_hex(new_name)));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								work_in_progress = false;
								$('#file_show_mainarea_edit_form').dialog('enable');
								$('#file_show_mainarea_edit_form').dialog('close');
								var errmsg = '';
								if (errmsg == '' && errorThrown != null) errmsg = errorThrown;
								if (errmsg == '' && textStatus != null) errmsg = textStatus;
								if (errmsg == '') errmsg = 'Unknown error';
								show_alert ('Failed - ' + errmsg, "<?php print $this->lang->line('Error')?>");
							}
						});
					}
					else
					{
						show_alert ('<pre>NOT SUPPORTED</pre>', "<?php print $this->lang->line('Error')?>");
					}
				},
				'<?php print $this->lang->line('Cancel')?>': function () {
					if (work_in_progress) return;
					$('#file_show_mainarea_edit_form').dialog('close');
				}
			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);


	$('#file_show_mainarea_delete_form').dialog (
		{
			title: '<?php print $this->lang->line('Delete');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'<?php print $this->lang->line('OK')?>': function () {
					if (work_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						work_in_progress = true;

						var form_data = new FormData();

						var f = $('#file_show_mainarea_delete_confirm');
						if (f != null && f.is(':checked')) form_data.append ('file_delete_confirm', 'Y');

						$('#file_show_mainarea_delete_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/file/xhr_delete/{$project->id}/{$hexname}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#file_show_mainarea_delete_form').dialog('enable');
								$('#file_show_mainarea_delete_form').dialog('close');
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
								work_in_progress = false;
								$('#file_show_mainarea_delete_form').dialog('enable');
								$('#file_show_mainarea_delete_form').dialog('close');
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
					if (work_in_progress) return;
					$('#file_show_mainarea_delete_form').dialog('close');
				}

			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);

	$('#file_show_mainarea_add_files').change (function () {
		populate_selected_files_for_adding ();
	});

	$('#file_show_mainarea_add_file_form').dialog (
		{
			title: '<?php print $this->lang->line('Add');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'<?php print $this->lang->line('OK')?>': function () {
					if (work_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						work_in_progress = true;

						var form_data = new FormData();

						var f_no = 0;
						for (var i = 0; i <= populated_file_max_for_adding; i++)
						{
							var f = populated_file_obj_for_adding[i];
							if (f != null)
							{
								form_data.append ('file_add_file_' + f_no, f);

								var d = $('#file_show_mainarea_add_file_desc_' + i);
								if (d != null) form_data.append('file_add_file_desc_' + f_no, d.val());
								f_no++;
							}
						}
						form_data.append ('file_add_file_count', f_no);

						$('#file_show_mainarea_add_file_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/file/xhr_add_file/{$project->id}/{$hexname}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#file_show_mainarea_add_file_form').dialog('enable');
								$('#file_show_mainarea_add_file_form').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/file/show/{$project->id}/{$hexname}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								work_in_progress = false;
								$('#file_show_mainarea_add_file_form').dialog('enable');
								$('#file_show_mainarea_add_file_form').dialog('close');
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
					if (work_in_progress) return;
					$('#file_show_mainarea_add_file_form').dialog('close');
				}

			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);

	$('#file_show_mainarea_edit_file_form').dialog (
		{
			title: '<?php print $this->lang->line('Edit');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'<?php print $this->lang->line('OK')?>': function () {
					if (work_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						work_in_progress = true;

						var form_data = new FormData();

						var f_no = 0;
						for (var i = 0; i <= <?php print $file_count; ?>; i++)
						{
							var n = $('#file_show_mainarea_edit_file_name_' + i);
							var d = $('#file_show_mainarea_edit_file_desc_' + i);

							if (n && d)
							{
								if (d.prop('disabled'))
								{
									form_data.append ('file_edit_file_name_' + f_no, original_file_name[i]);
									form_data.append('file_edit_file_kill_' + f_no, 'yes');
									f_no++;
								}
								else if (d.val() != original_file_desc[i])
								{
									form_data.append ('file_edit_file_name_' + f_no, original_file_name[i]);
									form_data.append('file_edit_file_desc_' + f_no, d.val());
									f_no++;
								}
							}
						}
						form_data.append ('file_edit_file_count', f_no);

						$('#file_show_mainarea_edit_file_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/file/xhr_edit_file/{$project->id}/{$hexname}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#file_show_mainarea_edit_file_form').dialog('enable');
								$('#file_show_mainarea_edit_file_form').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/file/show/{$project->id}/{$hexname}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								work_in_progress = false;
								$('#file_show_mainarea_edit_file_form').dialog('enable');
								$('#file_show_mainarea_edit_file_form').dialog('close');
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
					if (work_in_progress) return;
					$('#file_show_mainarea_edit_file_form').dialog('close');
				}

			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);

	$('#file_show_mainarea_edit_button').button().click (
		function () { 
			$('#file_show_mainarea_edit_form').dialog('open'); 
			return false; // prevent the default behavior
		}
	);
	$('#file_show_mainarea_delete_button').button().click (
		function() {
			$('#file_show_mainarea_delete_form').dialog('open');
			return false;
		}
	);

	$('#file_show_mainarea_add_file_button').button().click (
		function() {
			$('#file_show_mainarea_add_file_form').dialog('open');
			return false;
		}
	);

	$('#file_show_mainarea_edit_file_button').button().click (
		function() {
			$('#file_show_mainarea_edit_file_form').dialog('open');
			return false;
		}
	);
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
			// DEPRECATED
			//array ("file/update/{$project->id}/{$hexname}", '<i class="fa fa-edit"></i> ' . $this->lang->line('Edit'))
		)
	)
);
?>

<!---------------------------------------------------------------------------->


<div class="mainarea" id="file_show_mainarea">
<div class="title"><?php print htmlspecialchars($file->name)?></div>

<div class="infostrip" id="wiki_show_mainarea_infostrip">
	<?php if (isset($login['id']) && $login['id'] != ''): ?>
	<a id="file_show_mainarea_edit_button" href='#'><?php print $this->lang->line('Edit')?></a>
	<a id="file_show_mainarea_delete_button" href='#'><?php print $this->lang->line('Delete')?></a>
	<?php endif; ?>
	<a id="file_show_mainarea_metadata_button" href='#'><?php print $this->lang->line('Metadata')?></a>
</div>

<div id="file_show_mainarea_result">

<div id='file_show_mainarea_files' class='collapsible-box'>
	<div id='file_show_mainarea_files_header' class='collapsible-box-header'><?php print $this->lang->line('Files')?></div>

	<div id='file_show_mainarea_files_body'>
	<?php if (isset($login['id']) && $login['id'] != ''): ?>
	<div>
		<a id="file_show_mainarea_add_file_button" href='#'><?php print $this->lang->line('Add')?></a>
		<a id="file_show_mainarea_edit_file_button" href='#'><?php print $this->lang->line('Edit')?></a>
	</div>
	<?php endif; ?>

	<table>
	<?php
	for ($i = 0; $i < $file_count; $i++)
	{
		$f = $file->file_list[$i];
	
		$xname = $this->converter->AsciiToHex($f->filename);
		print '<tr><td class="file-name-td">';
		print anchor ("file/get/{$project->id}/{$xname}", '<i class="fa fa-download" /> ' . htmlspecialchars($f->filename));
		print '</td><td class="file-description-td">';
		print htmlspecialchars($f->description);
		print '</td><td class="file-md5sum-td">';
		print " <tt>{$f->md5sum}</tt>";
		print '</td></tr>';
	}
	?>
	</table>
	</div>
</div>

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
</div> <!-- file_show_mainarea_result_info -->

</div> <!-- file_show_mainarea_result -->


<?php if (isset($login['id']) && $login['id'] != ''): ?>

<div id='file_show_mainarea_edit_form'>
	<div style='line-height: 2em;'>
		<?php print $this->lang->line('Tag'); ?>: <input type='text' id='file_show_mainarea_edit_tag' name='file_show_edit_tag' size='30' value='<?php print addslashes($file->tag); ?>'/>
		<?php print $this->lang->line('Name'); ?>: <input type='text' id='file_show_mainarea_edit_name' name='file_show_edit_name' size='60' value='<?php print addslashes($file->name); ?>'/>
	</div>

	<div id='file_show_mainarea_edit_description_tabs' style='width:100%;'>
		<ul>
			<li><a href='#file_show_mainarea_edit_description_input'><?php print $this->lang->line('Description'); ?></a></li>
			<li><a href='#file_show_mainarea_edit_description_preview'><?php print $this->lang->line('Preview'); ?></a></li>
		</ul>

		<div id='file_show_mainarea_edit_description_input'>
			<textarea type='textarea' id='file_show_mainarea_edit_description' name='file_show_edit_description' rows=24 cols=100 style='width:100%;'><?php print htmlspecialchars($file->description); ?></textarea>
		</div>
		<div id='file_show_mainarea_edit_description_preview' class='form_input_preview'>
		</div>
	</div>
</div>

<div id='file_show_mainarea_delete_form'>
	<input type='checkbox' id='file_show_mainarea_delete_confirm' />
	<?php print $this->lang->line('MSG_SURE_TO_DELETE_THIS') . ' - ' . htmlspecialchars($file->name); ?>
</div>

<div id='file_show_mainarea_add_file_form'>
	<div id='file_show_mainarea_add_file_input'>
		<input type='file' id='file_show_mainarea_add_files' name='file_show_add_files' multiple='' autocomplete='off' style='color: transparent;' />
		<table id='file_show_mainarea_add_file_table'></table>
	</div>
</div>

<div id='file_show_mainarea_edit_file_form'>
	<table>
	<?php
	for ($i = 0; $i < $file_count; $i++)
	{
		$f = $file->file_list[$i];
		print '<tr><td>';
		printf ('<a href="#" onClick="kill_edit_file(%d); return false;"><i class="fa fa-trash"></i></a>', $i);
		print '</td><td>';
		printf ('<span id="file_show_mainarea_edit_file_name_%d">%s</span>', $i, htmlspecialchars($f->filename));
		print '</td><td>';
		printf ('<input type="text" id="file_show_mainarea_edit_file_desc_%d" value="%s" size="40" autocomplete="off" />', $i, addslashes($f->description));
		print '</td></tr>';
	}
	?>
	</table>
</div>

<?php endif; ?>

<div id='file_show_mainarea_alert'></div>

</div> <!-- file_show_mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  file_show_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>

</html>

