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

	$("#wiki_edit_save_button").button().click (function() {
		// TODO:
		var e = wiki_text_editor.serialize();
		alert (e.wiki_edit_text_editor.value);
		//console.log ("%o", wiki_text_editor);
		//console.log ("%o", e);
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
	<div class="title"><input type="text" name="wiki_name" value="" maxlength="80" size="40" id="wiki_edit_name" placeholder="<?php print $this->lang->line('Name'); ?>" /></div>

	<div class="actions">
		<?php if (isset($login['id']) && $login['id'] != ''): ?>
		<a id="wiki_edit_save_button" href='#'><?php print $this->lang->line('Save')?></a>
		<?php endif; ?>
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

	<input type="hidden" name="wiki_projectid" value="<?php print addslashes($wiki->projectid); ?>"  id="wiki_edit_projectid" />
	<?php if ($mode == 'update'): ?>
	<input type="hidden" name="wiki_original_name" value="<?php print addslashes($wiki->name); ?>"  id="wiki_edit_original_name" />
	<?php endif; ?>

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
