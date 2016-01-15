<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

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

<title><?php 
	printf ('%s - %s', htmlspecialchars($project->name), htmlspecialchars($wiki->name));
?></title>


<?php
$hex_wikiname = $this->converter->AsciiToHex ($wiki->name);

if ($wiki->doctype == 'H')
{
	$is_html = TRUE;
	$update_command = 'updatex';
}
else
{
	$is_html = FALSE;
	$update_command = 'update';
}
?>

<script type="text/javascript">
function show_alert (outputMsg, titleMsg) 
{
	$('#wiki_show_alert').html(outputMsg).dialog({
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

function showdown_render_wiki (inputid, outputid)
{
	var sd = new showdown.Converter ({
		omitExtraWLInCodeBlocks: false,
		noHeaderId: true,
		prefixHeaderId: false,
		parseImgDimensions: true,
		headerLevelStart: 1,
		simplifiedAutoLink: false,
		literalMidWordUnderscores: false,
		strikethrough: true,
		tables: true,
		tablesHeaderId: false,
		ghCodeBlocks: true,
		tasklists: true
	});

	function decodeEntities(str)
	{
		return str.replace(/&amp;/g, '&').
				replace(/&lt;/g, '<').
				replace(/&gt;/g, '>').
				replace(/&quot;/g, '"');
	}

	var input = document.getElementById(inputid);
	var output = document.getElementById(outputid);

	output.innerHTML = sd.makeHtml(decodeEntities(input.innerHTML));
}

function render_wiki()
{
	var column_count = '<?php print  $wiki->columns ?>';
	var x_column_count = parseInt (column_count);
	if (isNaN(x_column_count) || x_column_count < 1) x_column_count = 1;
	else if (x_column_count > 9) x_column_count = 9; // sync this max value with wiki_edit. TODO: put this into codepot.ini

	if (x_column_count > 1)
	{
		column_count = x_column_count.toString();
		$("#wiki_show_wiki").css ({
			"-moz-column-count":    column_count,
			"-webkit-column-count": column_count,
			"column-count":         column_count
		});
	}

<?php if ($wiki->doctype == 'M'): ?>
	showdown_render_wiki ("wiki_show_wiki_text", "wiki_show_wiki");
<?php else: ?>
	creole_render_wiki (
		"wiki_show_wiki_text", 
		"wiki_show_wiki", 
		"<?php print site_url()?>/wiki/show/<?php print $project->id?>/",
		"<?php print site_url()?>/wiki/attachment/<?php print $project->id?>/<?php print $hex_wikiname?>/"
	);
<?php endif; ?>

	prettyPrint ();
}

var work_in_progress = false;

$(function () {
	$('#wiki_show_metadata').accordion({
		collapsible: true,
		heightStyle: "content"
	});

<?php if (isset($login['id']) && $login['id'] != ''): ?>
	$('#wiki_show_delete_form').dialog (
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

						var f = $('#wiki_show_delete_confirm');
						if (f != null && f.is(':checked')) form_data.append ('wiki_delete_confirm', 'Y');

						$('#wiki_show_delete_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/wiki/xhr_delete/{$project->id}/{$hex_wikiname}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#wiki_show_delete_form').dialog('enable');
								$('#wiki_show_delete_form').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/wiki/home/{$project->id}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								work_in_progress = false;
								$('#wiki_show_delete_form').dialog('enable');
								$('#wiki_show_delete_form').dialog('close');
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
					$('#wiki_show_delete_form').dialog('close');
				}
			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);

	$("#wiki_show_edit_button").button().click (
		function () { 
			$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/wiki/{$update_command}/{$project->id}/{$hex_wikiname}"; ?>'));
			return false;
		}
	);
	$('#wiki_show_delete_button').button().click (
		function () { 
			$('#wiki_show_delete_form').dialog('open'); 
			return false; // prevent the default behavior
		}
	);
<?php endif; ?>

<?php if ($is_html): ?>
	/* nothing */
<?php else: ?>
	render_wiki ();
<?php endif; ?>
});
</script>

</head>

<body>

<div class="content" id="wiki_show_content">

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


<div class="mainarea" id="wiki_show_mainarea">

<div class="codepot-title-band" id="wiki_show_title_band">
	<div class="title"><?php print htmlspecialchars($wiki->name)?></div>

	<div class="actions">
		<?php if (isset($login['id']) && $login['id'] != ''): ?>
		<a id="wiki_show_edit_button" href='#'><?php print $this->lang->line('Edit')?></a>
		<a id="wiki_show_delete_button" href='#'><?php print $this->lang->line('Delete')?></a>
		<?php endif; ?>
	</div>

	<div style='clear: both'></div>
</div>

<div id='wiki_show_metadata' class='collapsible-box'>
	<div id='wiki_show_metadata_header' class='collapsible-box-header'><?php print $this->lang->line('Metadata')?></div>
	<div id='wiki_show_metadata_body'>

		<ul id='wiki_show_metadata_list'>
		<li><?php print $this->lang->line('Created on')?> <?php print codepot_dbdatetodispdate($wiki->createdon); ?></li>
		<li><?php print $this->lang->line('Created by')?> <?php print htmlspecialchars($wiki->createdby); ?></li>
		<li><?php print $this->lang->line('Last updated on')?> <?php print codepot_dbdatetodispdate($wiki->updatedon); ?></li>
		<li><?php print $this->lang->line('Last updated by')?> <?php print htmlspecialchars($wiki->updatedby); ?></li>
		</ul>

		<ul id='wiki_show_file_list'>
		<?php
			foreach ($wiki->attachments as $att)
			{
				$hexattname = $this->converter->AsciiToHex ($att->name);
				print '<li>';
				print anchor (
					"wiki/attachment/{$project->id}/{$hex_wikiname}/{$hexattname}", 
					htmlspecialchars($att->name)
				);
				print '</li>';
			}
		?>
		</ul>

		<div style='clear: both;'></div>
	</div>
</div>

<div id="wiki_show_result" class="codepot-relative-container-view">
	<?php
	print '<div id="wiki_show_wiki" class="codepot-styled-text-view">'; 
	if ($is_html)
	{
		print $wiki->text;
	}
	else
	{
		print '<pre id="wiki_show_wiki_text" style="visibility: hidden">';
		print htmlspecialchars($wiki->text);
		print '</pre>';
	}
	print '</div>';
	?>
</div> <!-- wiki_show_result -->


<?php if (isset($login['id']) && $login['id'] != ''): ?>
<div id='wiki_show_delete_form'>
	<input type='checkbox' id='wiki_show_delete_confirm' />
	<?php print $this->lang->line('MSG_SURE_TO_DELETE_THIS') . ' - ' . htmlspecialchars($wiki->name); ?>
</div>
<?php endif; ?>

<div id='wiki_show_alert'></div>

</div> <!-- wiki_show_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  wiki_show_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>

</html>

