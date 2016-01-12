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
	var titleband = $("#wiki_edit_title_band");
	var editor = $("#wiki_edit_text_editor");
	var attachment = $("#wiki_edit_attachment");
	var footer = $("#codepot_footer");

	editor.height(0); // to prevent from continuous growing. it seems to affect footer placement when not set to 0.

	var ioff = titleband.offset();
	var foff = footer.offset();

	ioff.top += titleband.outerHeight() + 5 + attachment.outerHeight() + 10;

	editor.offset (ioff);
	//editor.innerHeight (foff.top - ioff.top - 5);
	editor.height (foff.top - ioff.top - 5);
	editor.innerWidth (titleband.innerWidth());
}

var new_attachment_no = 0;
var wiki_text_editor = null;
var work_in_progress = false;
var wiki_original_name = '<?php print addslashes($wiki->name); ?>';
var wiki_new_name = '';

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
	$('#wiki_edit_more_new_attachment').button().click (
		function () {
			var html = [
				'<li><input type="file" name="wiki_new_attachment_',
				++new_attachment_no,
				'" /></li>'
			].join("");
			$('#wiki_edit_new_attachment_list').append (html);
			resize_editor();
			return false;
		}
	);

	wiki_text_editor = new MediumEditor('#wiki_edit_text_editor', {
		autoLink: true,
		imageDragging: true,
		buttonLabels: 'fontawesome',

		toolbar: {
			allowMultiParagraphSelection: true,
			buttons: ['bold', 'italic', 'underline', 'strikethrough', 
			          'anchor', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 
			          'subscript', 'superscript', 'quote', 'pre', 
			          'orderedlist', 'unorderedlist', 'indent', 'outdent',
			          'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull',
			          'removeFormat', 'table'],
			diffLeft: 0,
			diffTop: -10,
			firstButtonClass: 'medium-editor-button-first',
			lastButtonClass: 'medium-editor-button-last',
			standardizeSelectionStart: false,

			static: false,
			relativeContainer: null,
			/* options which only apply when static is true */
			align: 'center',
			sticky: false,
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
	wiki_text_editor.setContent ('<?php print addslashes($wiki->text); ?>', 0);
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

			/*
			var f_no = 0;
			for (var i = 0; i <= populated_file_max; i++)
			{

				var f = populated_file_obj[i];
				if (f != null)
				{
					form_data.append ('wiki_file_' + f_no, f);

					var d = $('#wiki_edit_file_desc_' + i);
					if (d != null) form_data.append('wiki_file_desc_' + f_no, d.val());

					f_no++;
				}
			}

			form_data.append ('wiki_file_count', f_no);*/

			form_data.append ('wiki_type', 'H');
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
					show_in_progress_message (null, null);
					if (data == 'ok') 
					{
						wiki_original_name = wiki_new_name;
						// TODO: reload contents?
					}
					else
					{
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

<div id='wiki_edit_attachment'>
	<?php if (!empty($wiki->attachments)): ?>
	<?php print form_label($this->lang->line('WIKI_ATTACHMENTS').': ', 'wiki_edit_attachment_list')?> 

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
	<?php endif; ?>


	<?php print form_label($this->lang->line('WIKI_NEW_ATTACHMENTS').': ', 'wiki_edit_new_attachment_list')?> 
	<a href='#' id='wiki_edit_more_new_attachment'>
		<?php print $this->lang->line('WIKI_MORE_NEW_ATTACHMENTS')?>
	</a>

	<ul id='wiki_edit_new_attachment_list'>
	<li>	
		<input type='file' name='wiki_new_attachment_0' />
		<!--<input type='checkbox' name='wiki_delete_attachment[]' value='delete'/>Delete-->
	</li>
	</ul>
</div>

<div id="wiki_edit_result">
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
