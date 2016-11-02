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

<script type="text/javascript">

function show_alert (outputMsg, titleMsg) 
{
	$('#file_home_alert').html(outputMsg).dialog({
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

function preview_new_description(input_text)
{
	creole_render_wiki_with_input_text (
		input_text,
		"file_home_new_description_preview", 
		"<?php print site_url()?>/wiki/show/<?php print $project->id?>/",
		"<?php print site_url()?>/wiki/attachment0/<?php print $project->id?>/",
		true // raw
	);

	prettyPrint ();
}

var import_in_progress = false;
var populated_file_obj = [];
var populated_file_max = 0;

function populate_selected_files ()
{
	var file_desc = {};
	for (var n = 0; n < populated_file_max; n++)
	{
		var f = populated_file_obj[n];
		if (f != null)
		{
			var d = $('#file_home_new_file_desc_' + n);
			if (d != null) file_desc[f.name] = d.val();
		}
	}

	$('#file_home_new_file_table').empty();
	populated_file_obj = [];

	var f = $('#file_home_new_files').get(0);
	var f_no = 0;
	for (var n = 0; n < f.files.length; n++)
	{
		if (f.files[n] != null) 
		{
			var desc = file_desc[f.files[n].name];
			if (desc == null) desc = '';

			$('#file_home_new_file_table').append (
				codepot_sprintf (
					'<tr id="file_home_new_file_row_%d"><td><a href="#" id="file_home_new_file_cancel_%d" onClick="cancel_out_new_file(%d); return false;"><i class="fa fa-trash"></i></a></td><td>%s</td><td><input type="text" id="file_home_new_file_desc_%d" size="40" value="%s" /></td></tr>', 
					f_no, f_no, f_no, codepot_htmlspecialchars(f.files[n].name), f_no, codepot_addslashes(desc)
				)
			);

			populated_file_obj[f_no] = f.files[n];
			f_no++;
		}
	}

	populated_file_max = f_no;
}

function cancel_out_new_file (no)
{
	$('#file_home_new_file_row_' + no).remove ();
	populated_file_obj[no] = null;
}

$(function () { 

<?php if (isset($login['id']) && $login['id'] != ''): ?>

	$('#file_home_new_files').change (function () {
		populate_selected_files ();
	});

	$("#file_home_new_description_tabs").tabs ();
	$("#file_home_new_description_tabs").bind ('tabsshow', function (event, ui) {
		if (ui.index == 2) preview_new_description ($("#file_home_new_description").val());
	});

	$('#file_home_new_form_div').dialog (
		{
			title: '<?php print $this->lang->line('New');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {

				'<?php print $this->lang->line('OK')?>': function () {
					if (import_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						import_in_progress = true;

						var form_data = new FormData();

						var f_no = 0;
						for (var i = 0; i <= populated_file_max; i++)
						{

							var f = populated_file_obj[i];
							if (f != null)
							{
								form_data.append ('file_new_file_' + f_no, f);

								var d = $('#file_home_new_file_desc_' + i);
								if (d != null) form_data.append('file_new_file_desc_' + f_no, d.val());

								f_no++;
							}
						}

						form_data.append ('file_new_file_count', f_no);
						form_data.append ('file_new_tag', $('#file_home_new_tag').val());
						form_data.append ('file_new_name', $('#file_home_new_name').val());
						form_data.append ('file_new_description', $('#file_home_new_description').val());

						$('#file_home_new_form_div').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/file/xhr_import/{$project->id}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								import_in_progress = false;
								$('#file_home_new_form_div').dialog('enable');
								$('#file_home_new_form_div').dialog('close');
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
								import_in_progress = false;
								$('#file_home_new_form_div').dialog('enable');
								$('#file_home_new_form_div').dialog('close');
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
					if (import_in_progress) return;
					$('#file_home_new_form_div').dialog('close');
				}
			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !import_in_progress;
			}
		}
	);

	$("#file_home_new_button").button().click (
		function () { 
			$('#file_home_new_form_div').dialog('open'); 
			return false; // prevent the default behavior
		}
	);
<?php endif; ?>

});

</script>


<title><?php print htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="file_home_content">

<!-- ============================================================ -->

<?php $this->load->view ('taskbar'); ?>

<!-- ============================================================ -->

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

<!-- ============================================================ -->

<div class="mainarea" id="file_home_mainarea">

<div class="codepot-title-band" id="file_home_title_band">
	<div class="title"><?php print $this->lang->line('Files')?></div>

	<div class="actions">
	<?php 
	$total_file_count = 0;
	foreach ($files as $f) $total_file_count += count($f->file_list);
	printf ($this->lang->line('FILE_FMT_TOTAL_X_FILES'), $total_file_count); 
	?> 

	<?php if (isset($login['id']) && $login['id'] != ''): ?>
	<a id="file_home_new_button" href='#'><?php print $this->lang->line('New')?></a>
	<?php endif; ?>
	</div>

	<div style='clear: both'></div>
</div>

<div id="file_home_result" class="codepot-relative-container-view">
	<?php
	if (empty($files))
	{
		print htmlspecialchars($this->lang->line('FILE_MSG_NO_FILES_AVAILABLE'));
	}
	else
	{
		function comp_tag ($a, $b)
		{
			//$x = explode ('.', $a);
			//$y = explode ('.', $b);
			$x = explode ('.', str_replace('-', '.', $a));
			$y = explode ('.', str_replace('-', '.', $b));
			$cx = count($x);
			$cy = count($y);
			$min = min($cx, $cy);

			for ($i = 0; $i < $min; $i++)
			{
				if (is_numeric($x[$i]) && is_numeric($y[$i]))
				{
					$q = (int)$x[$i] - (int)$y[$i];
				}
				else
				{
					$q = strcmp($x[$i], $y[$i]);
				}
				if ($q != 0) return $q;
			}

			return ($cx > $cy)? 1: (($cx < $cy)? -1: 0);
		}

		function comp_files ($a, $b)
		{
			//$cmp = version_compare ($b->tag, $a->tag);
			$cmp = comp_tag ($b->tag, $a->tag); // descending
			if ($cmp == 0)
			{
				$cmp = comp_tag ($a->name, $b->name); // ascending
				if ($cmp == 0)
				{
					$cmp = strcmp ($a->filename, $b->filename);
				}
			}
			return $cmp;
		}

		usort ($files, 'comp_files');

		print '<table id="file_home_result_table" class="codepot-fit-width-table codepot-spacious-table">';
		print '<tr class="heading">';
		print '<th>' . $this->lang->line('Tag') . '</th>';
		print '<th>' . $this->lang->line('Name') . '</th>';
		print '<th>' . $this->lang->line('File') . '</th>';
		print '<th>' . $this->lang->line('Summary') . '</th>';
		print '<th>' . $this->lang->line('MD5') . '</th>';
		print '</tr>';
		
		$oldtag = '';
		$rownum = 0;
		$rowclasses = array ('odd', 'even');
		foreach ($files as $file) 
		{
			$hexname = $this->converter->AsciiToHex ($file->name);
			$rowclass = $rowclasses[$rownum++ % 2];

			$file_list_count = count($file->file_list);

			if ($file_list_count <= 0)
			{
				print "<tr class='{$rowclass}'>";

				print '<td>';
				if ($file->tag != $oldtag)
				{
					print htmlspecialchars($file->tag);
					$oldtag = $file->tag;
				}
				print '</td>';

				print '<td>';
				print anchor ("file/show/{$project->id}/{$hexname}", htmlspecialchars($file->name));
				print '</td>';

				print '<td></td>';
				print '<td></td>';
				print '<td></td>';

				print '</tr>';
			}
			else
			{
				for ($i = 0; $i < $file_list_count; $i++)
				{
					print "<tr class='{$rowclass}'>";

					$f = $file->file_list[$i];
					$xname = $this->converter->AsciiToHex ($f->filename);

					print '<td>';
					if ($i == 0 && $file->tag != $oldtag)
					{
						print htmlspecialchars($file->tag);
						$oldtag = $file->tag;
					}
					print '</td>';

					print '<td>';
					if ($i == 0) print anchor ("file/show/{$project->id}/{$hexname}", htmlspecialchars($file->name));
					print '</td>';

					print '<td>';
					print anchor ("file/get/{$project->id}/{$xname}", htmlspecialchars($f->filename));
					print '</td>';

					print '<td>';
					print htmlspecialchars($f->description);
					print '</td>';

					print '<td><tt>';
					print $f->md5sum;
					print '</tt></td>';

					print '</tr>';
				}
			}
		}
		print '</table>';
	}
	?>
</div> <!-- file_home_result -->

<?php if (isset($login['id']) && $login['id'] != ''): ?>
<div id='file_home_new_form_div'>
	<div style='line-height: 2em;'><?php print $this->lang->line('Tag'); ?>: <input type='text' id='file_home_new_tag' name='file_home_new_tag' /></div>
	<div style='line-height: 2em;'><?php print $this->lang->line('Name'); ?>: <input type='text' id='file_home_new_name' name='file_home_new_name' size='50'/></div>

	<div id='file_home_new_description_tabs' style='width:100%;'>
		<ul>
			<li><a href='#file_home_new_file_input'><?php print $this->lang->line('Files'); ?></a></li>
			<li><a href='#file_home_new_description_input'><?php print $this->lang->line('Description'); ?></a></li>
			<li><a href='#file_home_new_description_preview'><?php print $this->lang->line('Preview'); ?></a></li>
		</ul>

		<div id='file_home_new_file_input'>
			<input type='file' id='file_home_new_files' name='file_home_new_files' multiple='' autocomplete='off' style='color: transparent;' />
			<table id='file_home_new_file_table'></table>
		</div>
		<div id='file_home_new_description_input'>
			<textarea type='textarea' id='file_home_new_description' name='file_home_new_description' rows=10 cols=80 style='width:100%;'></textarea>
		</div>
		<div id='file_home_new_description_preview' class='codepot-styled-text-preview'>
		</div>
	</div>
</div>

<?php endif; ?>

<div id='file_home_alert'></div>

</div> <!-- file_home_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- file_home_content -->

<!-- ============================================================ -->

<?php $this->load->view ('footer'); ?>

<!-- ============================================================ -->


</body>
</html>
