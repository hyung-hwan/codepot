<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/wiki.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />


<script type="text/javascript" src="<?php print base_url_make('/js/creole.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/showdown.js')?>"></script>

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
$hex_wikiname = $this->converter->AsciiToHex ($wiki->name);
$file_count = count($wiki->attachments);
?>

<script type="text/javascript">

var wiki_original_name = '<?php print addslashes($wiki->name); ?>';
var wiki_original_text = <?php print codepot_json_encode($wiki->text); ?>;
var previewing_text = false;

function show_alert (outputMsg, titleMsg) 
{
	$('#wiki_edit_alert').html(outputMsg).dialog({
		title: titleMsg,
		resizable: true,
		modal: true,
		width: 'auto',
		height: 'auto',
		buttons: {
			'<?php print $this->lang->line('OK')?>': function () {
				$(this).dialog("close");
			}
		}
	});
}

function show_in_progress_message (outputMsg, titleMsg)
{
	if (titleMsg == null || outputMsg == null)
	{
		$('#wiki_edit_alert').dialog('close');
	}
	else
	{
		$('#wiki_edit_alert').html(outputMsg).dialog({
			title: titleMsg,
			resizable: false,
			modal: true,
			width: 'auto',
			height: 'auto',

			buttons: { 
				'<?php print $this->lang->line('OK')?>': function () {
					// do nothing, don't event close the dialog.
				}
			},
			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		});
	}
}

function resize_text_editor()
{
	var editor_container = $("#wiki_edit_result");

	if (previewing_text)
	{
		editor_container.height('auto');
	}
	else
	{
		var editor = $("#wiki_edit_text_area");
		var titleband = $("wiki_edit_titleband");
		var footer = $("#codepot_footer");

		editor_container.height(0);
		var coff = editor_container.offset();
		var foff = footer.offset();

		var editor_height = foff.top - coff.top - 10;
		editor_container.innerHeight (editor_height);
		editor.innerHeight (editor_height);
		editor.innerWidth (titleband.innerWidth());
	}
}

function preview_text (input_text)
{
	if (previewing_text)
	{
		// switch to editing mode
		previewing_text = false;
		$("#wiki_edit_text_preview").empty();
		$("#wiki_edit_text_area").show();
		$("#wiki_edit_text_preview").hide();
		$("#wiki_edit_preview_button").button("option", "label", "<?php print $this->lang->line('Preview'); ?>");
		$("#wiki_edit_save_button").button("enable");
		$("#wiki_edit_exit_button").button("enable");
	}
	else
	{
		// switch to preview mode
		previewing_text = true;

		$("#wiki_edit_text_preview").show();
		$("#wiki_edit_text_area").hide();
		$("#wiki_edit_preview_button").button("option", "label", "<?php print $this->lang->line('Back'); ?>");
		$("#wiki_edit_save_button").button("disable");
		$("#wiki_edit_exit_button").button("disable");

		if ($('#wiki_edit_doctype').val() == 'M')
		{
			showdown_render_wiki_with_input_text (
				input_text,
				"wiki_edit_text_preview", 
				"<?php print site_url()?>/wiki/show/<?php print $project->id?>/",
				"<?php print site_url()?>/wiki/attachment/<?php print $project->id?>/" + codepot_string_to_hex(wiki_original_name) + "/",
				true // raw
			);
		}
		else
		{
			creole_render_wiki_with_input_text (
				input_text,
				"wiki_edit_text_preview", 
				"<?php print site_url()?>/wiki/show/<?php print $project->id?>/",
				"<?php print site_url()?>/wiki/attachment/<?php print $project->id?>/" + codepot_string_to_hex(wiki_original_name) + "/",
				true // raw
			);
		}

		prettyPrint ();
	}

	resize_text_editor ();
}

var populated_file_obj_for_adding = [];
var populated_file_max_for_adding = 0;
var cancelled_file_count_for_adding = 0;
var killed_file_count = 0;

var original_file_name_array = [
	<?php
	for ($i = 0; $i < $file_count; $i++)
	{
		$f = $wiki->attachments[$i];
		printf ("%s\t'%s'", (($i == 0)? '': ",\n"), addslashes($f->name));
	}
	print "\n";
	?>
];

function populate_selected_files_for_adding ()
{
	$('#wiki_edit_add_file_list').empty();
	populated_file_obj_for_adding = [];

	var f = $('#wiki_edit_add_files').get(0);
	var f_no = 0;
	for (var n = 0; n < f.files.length; n++)
	{
		if (f.files[n] != null) 
		{
			$('#wiki_edit_add_file_list').append (
				codepot_sprintf (
					'<li id="wiki_edit_add_file_row_%d"><a href="#" id="wiki_edit_add_file_cancel_%d" onClick="cancel_out_add_file(%d); return false;"><i class="fa fa-trash"></i></a> %s</li>', 
					f_no, f_no, f_no, codepot_htmlspecialchars(f.files[n].name)
				)
			);

			populated_file_obj_for_adding[f_no] = f.files[n];
			f_no++;
		}
	}

	populated_file_max_for_adding = f_no;
	resize_text_editor ();
}

function cancel_out_add_file (no)
{
	if (populated_file_obj_for_adding[no] != null)
	{
		cancelled_file_count_for_adding++;
		$('#wiki_edit_add_file_row_' + no).remove ();
		populated_file_obj_for_adding[no] = null;
		resize_text_editor ();
	}
}

function kill_file (no)
{
	var n = $('#wiki_edit_file_name_' + no);
	if (n)
	{
		if (n.prop('disabled'))
		{
			n.css ('text-decoration', '');
			n.prop ('disabled', false);
			killed_file_count--;
		}
		else
		{
			n.css ('text-decoration', 'line-through');
			n.prop ('disabled', true);
			killed_file_count++;
		}
	}

	resize_text_editor ();
}

function update_original_file_name_array ()
{
	$('#wiki_edit_add_file_list').empty();

	for (var i = 0; i < populated_file_max_for_adding; i++)
	{
		var f = populated_file_obj_for_adding[i];
		if (f != null) original_file_name_array.push (f.name);
	}

	populated_file_obj_for_adding = [];
	populated_file_max_for_adding = 0;
	cancelled_file_count_for_adding = 0;
	$('#wiki_edit_add_files').empty();

	var f_no = 0;
	var file_name_array = [];
	for (var i = 0; i < original_file_name_array.length; i++)
	{
		var n = $('#wiki_edit_file_name_' + i);
		if (n)
		{
			if (n.prop('disabled'))
			{
				// skip
			}
			else
			{
				file_name_array.push (original_file_name_array[i]);
			}
		}
	}

	$('#wiki_edit_file_list').empty();
	killed_file_count = 0;
	original_file_name_array = file_name_array;
	for (var i = 0; i < original_file_name_array.length; i++)
	{
		var anchor = codepot_sprintf ("<a href='%s'>%s</a>", 
			'<?php print site_url() . "/wiki/attachment/{$project->id}/" ?>' +
			codepot_string_to_hex(wiki_original_name) + '/' + codepot_string_to_hex(original_file_name_array[i]),
			codepot_htmlspecialchars(original_file_name_array[i])
		);
		$('#wiki_edit_file_list').append (
			codepot_sprintf (
				'<li><a href="#" onClick="kill_file(%d); return false;"><i class="fa fa-trash"></i></a><span id="wiki_edit_file_name_%d">%s</span></li>',
				i, i, anchor
			)
		);
	}
}

var wiki_text_editor = null;
var work_in_progress = false;

function save_wiki (wiki_new_name, wiki_new_text)
{
	work_in_progress = true;

	show_in_progress_message ('<?php print $this->lang->line('WIKI_MSG_SAVE_IN_PROGRESS'); ?>', wiki_new_name);

	var form_data = new FormData();

	var f_no = 0;
	for (var i = 0; i < populated_file_max_for_adding; i++)
	{
		var f = populated_file_obj_for_adding[i];
		if (f != null)
		{
			form_data.append ('wiki_file_' + f_no, f);
			f_no++;
		}
	}
	form_data.append ('wiki_file_count', f_no);

	f_no = 0;
	for (var i = 0; i < original_file_name_array.length; i++)
	{
		var n = $('#wiki_edit_file_name_' + i);
		if (n)
		{
			if (n.prop('disabled'))
			{
				form_data.append ('wiki_kill_file_name_' + f_no, original_file_name_array[i]);
				f_no++;
			}
		}
	}
	form_data.append ('wiki_kill_file_count', f_no);

	form_data.append ('wiki_doctype', $('#wiki_edit_doctype').val()); 
	form_data.append ('wiki_name', wiki_new_name);
	form_data.append ('wiki_original_name', wiki_original_name);
	form_data.append ('wiki_text', wiki_new_text);

	$.ajax({
		url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/wiki/xhr_edit/{$project->id}"; ?>'),
		type: 'POST',
		data: form_data,
		mimeType: 'multipart/form-data',
		contentType: false,
		processData: false,
		cache: false,

		success: function (data, textStatus, jqXHR) { 
			work_in_progress = false;

			if (data == 'ok') 
			{
				var name_changed = (wiki_original_name != wiki_new_name);
				wiki_original_name = wiki_new_name;
				wiki_original_text = wiki_new_text;
				update_original_file_name_array ();
				show_in_progress_message (null, null);
				if (name_changed)
				{
					// reload the whole page if the name has changed.
					$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/wiki/update/{$project->id}/"; ?>' + codepot_string_to_hex(wiki_new_name)));
				}
			}
			else
			{
				show_in_progress_message (null, null);
				show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
			}
		},

		error: function (jqXHR, textStatus, errorThrown) { 
			work_in_progress = false;
			show_in_progress_message (null, null);
			var errmsg = '';
			if (errmsg == '' && errorThrown != null) errmsg = errorThrown;
			if (errmsg == '' && textStatus != null) errmsg = textStatus;
			if (errmsg == '') errmsg = 'Unknown error';
			show_alert ('Failed - ' + errmsg, "<?php print $this->lang->line('Error')?>");
		}
	});
}


function save_wiki_with_confirmation (outputMsg, titleMsg, wiki_new_name, wiki_new_text) 
{
	$('#wiki_edit_alert').html(outputMsg).dialog({
		title: titleMsg,
		resizable: true,
		modal: true,
		width: 'auto',
		height: 'auto',
		buttons: {
			'<?php print $this->lang->line('OK')?>': function () {
				$(this).dialog("close");
				save_wiki (wiki_new_name, wiki_new_text);
			},
			'<?php print $this->lang->line('Cancel')?>': function () {
				$(this).dialog("close");
			}
		}
	});
}

$(function () {
	$('#wiki_edit_files').accordion({
		collapsible: true,
		heightStyle: "content",
		activate: function() { resize_text_editor(); }
	});

	$('#wiki_edit_add_files_button').button().click (function () {
		$('#wiki_edit_add_files').trigger('click');
		return false;
	});
	$('#wiki_edit_add_files').change (function () {
		populate_selected_files_for_adding ();
	});

<?php if ($mode == 'update'): ?>
	$('#wiki_edit_text_area').val (wiki_original_text);
	$('#wiki_edit_doctype').val ('<?php print $wiki->doctype; ?>');
<?php endif; ?>

	$("#wiki_edit_preview_button").button().click (function() {
		preview_text ($('#wiki_edit_text_area').val());
		return false;
	});
	$("#wiki_edit_save_button").button().click (function() {
		if (work_in_progress) return;

		if (!!window.FormData)
		{
			// FormData is supported
			var wiki_new_name = $('#wiki_edit_name').val();
			var wiki_new_text = $('#wiki_edit_text_area').val();
			if (populated_file_max_for_adding > cancelled_file_count_for_adding || 
			    killed_file_count > 0 ||
			    wiki_original_name != wiki_new_name ||
			    wiki_original_text != wiki_new_text) 
			{
				// there are changes. just save the document
				save_wiki (wiki_new_name, wiki_new_text);
			}
			else
			{
				// no changes detected.
				save_wiki_with_confirmation ("<?php print $this->lang->line('WIKI_MSG_SAVE_DESPITE_NO_CHANGES?'); ?>", wiki_new_name, wiki_new_name, wiki_new_text);
			}
		}
		else
		{
			show_alert ('<pre>NOT SUPPORTED</pre>', "<?php print $this->lang->line('Error')?>");
		}

		return false;
	});

	$("#wiki_edit_exit_button").button().click (function() {
		if (wiki_original_name == '')
			$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/wiki/home/{$project->id}"; ?>'));
		else
			$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/wiki/show/{$project->id}/"; ?>' + codepot_string_to_hex(wiki_original_name)));
		return false;
	});

	$(window).on ("beforeunload", function (e) {
		var wiki_new_name = $('#wiki_edit_name').val();
		var wiki_new_text = $('#wiki_edit_text_area').val();
		if (populated_file_max_for_adding > cancelled_file_count_for_adding || 
		    killed_file_count > 0 ||
		    wiki_original_name != wiki_new_name ||
		    wiki_original_text != wiki_new_text) 
		{
			return '<?php print $this->lang->line('MSG_DISCARD_CHANGES?'); ?>';
		}
		// return null;  // this line caused firefox to show the default message.
	});

	$('#wiki_edit_text_preview').hide();
	$(window).resize(resize_text_editor);
	resize_text_editor ();

	$('#wiki_edit_text_area').keydown (function(e) {
		if (e.keyCode == 9)  // capture a tab key to insert "\n".
		{
			// get caret position/selection
			var start = this.selectionStart;
			var end = this.selectionEnd;

			var $this = $(this);
			var value = $this.val();

			// set textarea value to: text before caret + tab + text after caret
			$this.val(value.substring(0, start) + "\t" + value.substring(end));

			// put caret at right position again (add one for the tab)
			this.selectionStart = this.selectionEnd = start + 1;
			e.preventDefault();
		}
	});
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

<div class="codepot-title-band" id="wiki_edit_title_band">
	<div class="title">
		<select id="wiki_edit_doctype" name="wiki_doctype">
			<option value="C">WikiCreole</option>
			<option value="M">Markdown</option>
		</select>
		<input type="text" name="wiki_name" value="<?php print htmlspecialchars($wiki->name); ?>" maxlength="80" size="40" id="wiki_edit_name" placeholder="<?php print $this->lang->line('Name'); ?>" />
	</div>

	<div class="actions">
		<a id="wiki_edit_preview_button" href='#'><?php print $this->lang->line('Preview')?></a>
		<a id="wiki_edit_save_button" href='#'><?php print $this->lang->line('Save')?></a>
		<a id="wiki_edit_exit_button" href='#'><?php print $this->lang->line('Exit')?></a>
	</div>

	<div style='clear: both'></div>
</div>

<div id='wiki_edit_files' class='collapsible-box'>
	<div id='wiki_edit_files_header' class='collapsible-box-header'>
		<?php print $this->lang->line('WIKI_ATTACHMENTS')?>
		<a href='#' id='wiki_edit_add_files_button'><?php print $this->lang->line('New')?></a>
	</div>
	<div id='wiki_edit_files_body' class='codepot-metadata-collapsible-body'>
		<input type='file' id='wiki_edit_add_files' name='wiki_add_files' multiple='' autocomplete='off' style='color: transparent; visibility: hidden; display: none;' />

		<ul id='wiki_edit_file_list'>
		<?php if (!empty($wiki->attachments)): ?>
			<?php
			for ($i = 0; $i < $file_count; $i++)
			{
				$att = $wiki->attachments[$i];;
				print '<li>';
				printf ('<a href="#" onClick="kill_file(%d); return false;"><i class="fa fa-trash"></i></a>', $i);

				//printf (' <span id="wiki_edit_file_name_%d">%s</span>', $i, htmlspecialchars($att->name));
				$hexattname = $this->converter->AsciiToHex ($att->name);
				printf (' <span id="wiki_edit_file_name_%d">%s</span>', $i, 
					anchor (
						"wiki/attachment/{$project->id}/{$hex_wikiname}/{$hexattname}", 
						htmlspecialchars($att->name)
					)
				);
				print '</li>';
			}
			?>
		<?php endif; ?>
		</ul>

		<ul id='wiki_edit_add_file_list'>
		</ul>
	</div>
</div>

<div id="wiki_edit_result" class="codepot-relative-container-view">
	<div id='wiki_edit_text_preview' class='codepot-styled-text-preview'></div>
	<textarea id='wiki_edit_text_area' style='resize: none;'></textarea>
</div> <!-- wiki_edit_result -->


<div id='wiki_edit_alert'></div>

</div> <!-- wiki_edit_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</body>

</html>
