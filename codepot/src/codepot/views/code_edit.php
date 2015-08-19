<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/code.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/ace/ace.js')?>"></script>

<?php
$enstyle_anchor_text = '<i class="fa fa-magic"></i> ' . $this->lang->line('Enstyle');
$destyle_anchor_text = '<i class="fa fa-times"></i> ' . $this->lang->line('Destyle');

if ($revision <= 0)
{
	$revreq = '';
	$revreqroot = '';
}
else
{
	$revreq = "/{$file['created_rev']}";
	$revreqroot = '/' . $this->converter->AsciiToHex ('.') . $revreq;
}

$hex_headpath = $this->converter->AsciiToHex($headpath);
?>

<script type="text/javascript">
var base_return_anchor = codepot_merge_path('<?php print site_url() ?>', '<?php print "/code/${caller}/{$project->id}/{$hex_headpath}" ?>');

function resize_editor()
{
	var infostrip = $("#code_edit_mainarea_infostrip");
	var code = $("#code_edit_mainarea_result_code");
	var footer = $("#codepot_footer");

	var ioff = infostrip.offset();
	var foff = footer.offset();

	ioff.top += infostrip.outerHeight() + 5;
	code.offset (ioff);
	code.innerHeight (foff.top - ioff.top - 5);
	code.innerWidth (infostrip.innerWidth());
}

function show_alert (outputMsg, titleMsg) 
{
	$("#code_edit_mainarea_alert").html(outputMsg).dialog({
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

var ace_modes = null;
var editor_changed = false;
var save_button = null;
var return_button = null;
var saving_in_progress = false;

function set_editor_changed (changed)
{
	if (editor_changed != changed)
	{
		editor_changed = changed;
		if (changed)
		{
			$(window).on ("beforeunload", function () {
				return 'Do you want to discard changes?';
			});
		}
		else
		{
			$(window).off ("beforeunload");
		}
	}
}

$(function () {
	save_button = $("#code_edit_mainarea_save_button").button();
	return_button = $("#code_edit_mainarea_return_button").button();

	var mode_menu = $("#code_edit_mainarea_mode");

	ace_modes = codepot_get_ace_modes();
	var detected_mode = null;
	var text_mode = null;
	var text_opt = null;
	for (var i in ace_modes)
	{
		var mode = ace_modes[i];

		var opt = $("<option></option>").val(mode.mode).text(mode.caption);

		if (!text_mode && mode.caption == 'Text') 
		{
			text_mode = mode;
			text_opt = opt;
		}
		if (mode.supportsFile("<?php print addslashes($file['name']); ?>"))
		{
			if (!detected_mode) 
			{
				opt.attr('selected', 'selected');
				detected_mode = mode;
			}
		}

		
		mode_menu.append(opt);
	}

	if (!detected_mode && text_mode) 
	{
		text_opt.attr('selected', 'selected');
		detected_mode = text_mode;
	}

	var editor = ace.edit("code_edit_mainarea_result_code");
	//editor.setTheme("ace/theme/chrome");
	if (detected_mode) editor.getSession().setMode (detected_mode.mode);
	editor.getSession().setUseSoftTabs(false);
	editor.setShowInvisibles(true);
	editor.setBehavioursEnabled(false);

	set_editor_changed (false);
	save_button.button ("disable");
	editor.on ("change", function (e) {
		set_editor_changed (true);
		save_button.button ("enable");
	});

	mode_menu.change (function() {
		editor.getSession().setMode ($(this).val());
	});

	$('#code_edit_mainarea_save_form').dialog ({
		title: '<?php print $this->lang->line('Save')?>',
		autoOpen: false,
		modal: true,
		width: '60%',
		buttons: { 
			'<?php print $this->lang->line('OK')?>': function () { 
				if (saving_in_progress) return;

				var save_message = $("#code_edit_mainarea_save_message").val();
				if (save_message == '') return false;

				editor.setReadOnly (true);
				save_button.button ("disable");
				saving_in_progress = true;
				$.ajax({
					method: "POST",
					dataType: "json",
					url: codepot_merge_path('<?php print site_url() ?>',  '<?php print "/code/enjson_save/{$project->id}/{$hex_headpath}"; ?>'),
					data: { "message": save_message, "text": editor.getValue() },

					success: function(json, textStatus, jqXHR) { 
						saving_in_progress = false;
						$('#code_edit_mainarea_save_form').dialog('enable'); 
						$('#code_edit_mainarea_save_form').dialog('close'); 
						if (json.status == "ok")
						{
							set_editor_changed (false);
							save_button.button ("disable");
							// once the existing document is saved, arrange to return 
							// to the the head revision regardless of the original revision.
							return_button.attr ('href', base_return_anchor);
							show_alert ('Saved', "<?php print $this->lang->line('Success')?>");
						}
						else
						{
							show_alert ('<pre>' + codepot_htmlspecialchars(json.status) + '</pre>', "<?php print $this->lang->line('Error')?>");
							save_button.button ("enable");
						}
						editor.setReadOnly (false);
					},

					error: function(jqXHR, textStatus, errorThrown) { 
						saving_in_progress = false;
						$('#code_edit_mainarea_save_form').dialog('enable'); 
						$('#code_edit_mainarea_save_form').dialog('close'); 
						show_alert ('Not saved - ' + errorThrown, "<?php print $this->lang->line('Error')?>");
						editor.setReadOnly (false);
						save_button.button ("enable");
					}
				});

				$('#code_edit_mainarea_save_form').dialog('disable'); 
			},

			'<?php print $this->lang->line('Cancel')?>': function () { 
				if (saving_in_progress) return;
				$(this).dialog('close'); 
			}
		},

		beforeClose: function() { 
			// if saving is in progress, prevent dialog closing
			return !saving_in_progress;
		}
	}); 


	save_button.click (function() {
		if (editor_changed) $("#code_edit_mainarea_save_form").dialog('open');
		return false;
	});


	$(window).resize(resize_editor);
	resize_editor ();
});
</script>

<title><?php 
	if ($headpath == '')
		printf ('%s', htmlspecialchars($project->name));
	else
		printf ('%s - %s', htmlspecialchars($project->name), htmlspecialchars($headpath));
?></title>
</head>

<body>

<div class="content" id="code_edit_content">

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
			'id' => 'code',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="code_edit_mainarea">

<div class="title" id="code_edit_mainarea_title">
<?php
	print anchor (
		"code/${caller}/{$project->id}{$revreqroot}",
		htmlspecialchars($project->name));

	$exps = explode ('/', $headpath);
	$expsize = count($exps);
	$par = '';
	for ($i = 1; $i < $expsize; $i++)
	{
		$par .= '/' . $exps[$i];
		$xpar = $this->converter->AsciiToHex ($par);

		print '/';
		print anchor (
			"code/${caller}/{$project->id}/{$xpar}{$revreq}",
			htmlspecialchars($exps[$i]));
	}

	if ($headpath != $file['fullpath'])
	{
		print ' - ';
		print htmlspecialchars($file['fullpath']);
	}
?>
</div> <!-- code_edit_mainarea_title -->

<div class="infostrip" id="code_edit_mainarea_infostrip">

	<?php 
		/* Saving file work on the head only. so the links here don't include the given revision anymore */
		print '<select id="code_edit_mainarea_mode"></select>';
		print ' ';
		print anchor ("code/${caller}/{$project->id}/{$hex_headpath}", $this->lang->line('Save'), 'id="code_edit_mainarea_save_button"');
		print ' ';
		print anchor ("code/${caller}/{$project->id}/{$hex_headpath}{$revreq}", $this->lang->line('Return'), 'id="code_edit_mainarea_return_button"');
	?>

</div>

<div class="result" id="code_edit_mainarea_result">

<?php 
/*
$fileext = substr(strrchr($file['name'], '.'), 1);
if ($fileext == 'adb' || $fileext == 'ads') $fileext = 'ada';
else if ($fileext == 'pas') $fileext = 'pascal';
else if ($fileext == 'bas') $fileext = 'basic';
*/
?>

<div id="code_edit_mainarea_result_code"><?php 
/*
	$is_octet_stream = FALSE;
	if (array_key_exists('properties', $file) && count($file['properties']) > 0)
	{
		foreach ($file['properties'] as $pn => $pv)
		{
			if ($pn == 'svn:mime-type' && $pv == 'application/octet-stream')
			{
				$is_octet_stream = TRUE;
				break;
			}
		}
	}

	$is_image_stream = FALSE;
	if ($is_octet_stream || 
	    in_array (strtolower($fileext), array ('png', 'jpg', 'gif', 'tif', 'bmp', 'ico')))
	{
		$img = @imagecreatefromstring ($file['content']);
		if ($img !== FALSE)
		{
			@imagedestroy ($img);
			print ('<img src="data:image;base64,' . base64_encode ($file['content']) . '" alt="[image]" />');
			$is_image_stream = TRUE;
		}
	}

	if (!$is_image_stream)*/ print htmlspecialchars($file['content']); 
?></div>

</div> <!-- code_edit_mainarea_result -->

<div id="code_edit_mainarea_save_form">
	<div>
		<?php print $this->lang->line('Message'); ?>
	</div>
	<div>
		<textarea id='code_edit_mainarea_save_message' rows=10 cols=60 style="width: 100%;"></textarea>
	</div>
</div>

<div id="code_edit_mainarea_alert">
</div>

</div> <!-- code_edit_mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  code_edit_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>

</html>

