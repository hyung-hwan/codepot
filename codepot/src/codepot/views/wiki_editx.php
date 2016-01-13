<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/wiki.css')?>" />
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

<script type="text/javascript" src="<?php print base_url_make('/js/medium-editor.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/medium-editor.min.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/medium-editor-theme.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/medium-editor-tables.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/medium-editor-tables.min.css')?>" />

<?php
$hex_wikiname = $this->converter->AsciiToHex ($wiki->name);
$file_count = count($wiki->attachments);
?>

<script type="text/javascript">

function show_alert (outputMsg, titleMsg) 
{
	$('#wiki_edit_alert').html(outputMsg).dialog({
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


function resize_editor()
{
	var editor = $("#wiki_edit_text_editor");

	editor.height(0); // to prevent from continuous growing. it seems to affect footer placement when not set to 0.

	var titleband = $("#wiki_edit_title_band");
	var toolbar = $("#medium-editor-toolbar-1");
	var files = $("#wiki_edit_files");
	var footer = $("#codepot_footer");
	var editor_container = $("#wiki_edit_result");

	var ioff = titleband.offset();
	var foff = footer.offset();

	ioff.top += titleband.outerHeight() + files.outerHeight() + toolbar.outerHeight() + 10;

	editor.offset (ioff);
	editor.innerHeight (foff.top - ioff.top - 5);
	editor.innerWidth (titleband.innerWidth());
}

var populated_file_obj_for_adding = [];
var populated_file_max_for_adding = 0;

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
}

function cancel_out_add_file (no)
{
	$('#wiki_edit_add_file_row_' + no).remove ();
	populated_file_obj_for_adding[no] = null;
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
		}
		else
		{
			n.css ('text-decoration', 'line-through');
			n.prop ('disabled', true);
		}
	}

	resize_editor ();
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
var wiki_original_name = '<?php print addslashes($wiki->name); ?>';
var wiki_new_name = '';
<?php if (function_exists('json_encode')): ?>
var wiki_original_text = <?php print json_encode($wiki->text); ?>;
<?php else: ?>
var wiki_original_text = '<?php print addcslashes($wiki->text, "\0..\37'\"\\"); ?>';
<?php endif; ?>

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

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		});
	}
}

$(function () {
	$('#wiki_edit_files').accordion({
		collapsible: true,
		heightStyle: "content",
		activate: function() { resize_editor(); }
	});

	$('#wiki_edit_add_files_button').button().click (function () {
		$('#wiki_edit_add_files').trigger('click');
		return false;
	});
	$('#wiki_edit_add_files').change (function () {
		populate_selected_files_for_adding ();
	});

	wiki_text_editor = new MediumEditor('#wiki_edit_text_editor', {
		autoLink: true,
		imageDragging: true,
		buttonLabels: 'fontawesome',
		anchorPreview: false,

		toolbar: {
			allowMultiParagraphSelection: true,
			buttons: ['bold', 'italic', 'underline', 'strikethrough', 
			          'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 
			          'subscript', 'superscript', 'removeFormat',
			          'quote', 'pre', 'anchor', 'image',
			          'orderedlist', 'unorderedlist', 'indent', 'outdent',
			          'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull',
			          'table'],
			diffLeft: 0,
			diffTop: -10,
			//firstButtonClass: 'medium-editor-button-first',
			//lastButtonClass: 'medium-editor-button-last',
			firstButtonClass: 'medium-editor-button',
			lastButtonClass: 'medium-editor-button',
			standardizeSelectionStart: false,

			static: true,
			relativeContainer: document.getElementById('wiki_edit_toolbar'),
			/* options which only apply when static is true */
			align: 'center',
			sticky: true,
			updateOnEmptySelection: false
		},

		paste: {
			forcePlainText: false,
			cleanPastedHTML: true,
			cleanReplacements: [],
			cleanAttrs: ['class', 'style', 'dir'],
			cleanTags: ['meta']
		},

		extensions: { 
			table: new MediumEditorTable()
		}
	});

<?php if ($mode == 'update'): ?>
	wiki_text_editor.setContent (wiki_original_text, 0);
<?php endif; ?>

	$("#wiki_edit_save_button").button().click (function() {
		var e = wiki_text_editor.serialize();

		if (work_in_progress) return;

		if (!!window.FormData)
		{
			// FormData is supported
			work_in_progress = true;
			show_in_progress_message ('Saving in progress. Please wait...', 'Saving...');

			wiki_new_name = $('#wiki_edit_name').val();

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

			form_data.append ('wiki_doctype', 'H');
			form_data.append ('wiki_name', wiki_new_name);
			form_data.append ('wiki_original_name', wiki_original_name);
			form_data.append ('wiki_text', e.wiki_edit_text_editor.value);

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
						wiki_original_text = e.wiki_edit_text_editor.value;
						update_original_file_name_array ();
						show_in_progress_message (null, null);
						if (name_changed)
						{
							// reload the whole page if the name has changed.
							$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/wiki/updatex/{$project->id}/"; ?>' + codepot_string_to_hex(wiki_new_name)));
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
		var ed = wiki_text_editor.serialize();
		if (wiki_original_text != ed.wiki_edit_text_editor.value) 
		{
			return 'Do you want to discard changes?';
		}
		// return null;  // this line caused firefox to show the default message.
	});

	$(window).resize(resize_editor);
	resize_editor ();
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

<div class="title-band" id="wiki_edit_title_band">
	<div class="title"><input type="text" name="wiki_name" value="<?php print addslashes($wiki->name); ?>" maxlength="80" size="40" id="wiki_edit_name" placeholder="<?php print $this->lang->line('Name'); ?>" /></div>

	<div class="actions">
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
	<div id='wiki_edit_files_body'>
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

<div id="wiki_edit_toolbar">
</div>

<div id="wiki_edit_result" class="result">
	<div id='wiki_edit_text_editor'></div>
</div> <!-- wiki_edit_result -->


<div id='wiki_edit_alert'></div>

</div> <!-- wiki_edit_mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</body>

</html>
