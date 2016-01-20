<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/code.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/prettify/prettify.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-ada.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-basic.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-css.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lisp.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lua.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-pascal.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-sql.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-vb.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<?php
$enstyle_anchor_text = '<i class="fa fa-magic"></i> ' . $this->lang->line('Enstyle');
$destyle_anchor_text = '<i class="fa fa-times"></i> ' . $this->lang->line('Destyle');

$hex_headpath = $this->converter->AsciiToHex ($headpath);
if ($revision <= 0)
{
	$revreq = '';
	$revreqroot = '';

	$history_path = "/code/history/{$project->id}/{$hex_headpath}";
}
else
{
	$revreq = "/{$file['created_rev']}";
	$revreqroot = '/' . $this->converter->AsciiToHex ('.') . $revreq;

	if ($hex_headpath == '') $revtrailer = $revreqroot;
	else $revtrailer = "/{$hex_headpath}{$revreq}";
	$history_path = "/code/history/{$project->id}{$revtrailer}";
}
?>

<script type="text/javascript">
var showing_raw_code = false;

function showRawCode()
{
	if (showing_raw_code)
	{
		$("#code_file_style_button").button("option", "label", '<?php print $destyle_anchor_text; ?>');
		$("#code_file_result_code").removeClass("prettyprinted");
		prettyPrint();
	}
	else
	{
		$("#code_file_style_button").button("option", "label", '<?php print $enstyle_anchor_text; ?>');
		$("#code_file_result_code").html($("#code_file_result_raw").html());
	}

	showing_raw_code = !showing_raw_code;
}

$(function () {

	$('#code_file_metadata').accordion({
		collapsible: true,
                heightStyle: "content"
	});

	$("#code_file_mainarea_loc_info").hide();
	btn = $("#code_file_mainarea_loc_button").button().click (function () {
		if ($("#code_file_mainarea_loc_info").is(":visible"))
		{
			$("#code_file_mainarea_loc_info").hide("blind",{},200);
		}
		else
		{
			$("#code_file_mainarea_loc_info").show("blind",{},200);
		}

		return false; // prevent the default behavior
	});

	$("#code_file_mainarea_edit_button").button();

	<?php if ($file['created_rev'] != $file['head_rev']): ?>
		$("#code_file_headrev_button").button().click (function() {
			$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/file/{$project->id}/${hex_headpath}"; ?>'));
			return false;
		});
	<?php endif; ?>

	$("#code_file_blame_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/blame/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});
	$("#code_file_diff_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/diff/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});

	$("#code_file_fulldiff_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/fulldiff/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});

	$("#code_file_history_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print $history_path; ?>'));
		return false;
	});
	$("#code_file_download_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/fetch/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});

	$("#code_file_style_button").button({"label": '<?php print $destyle_anchor_text; ?>'}).click (function () {
		showRawCode();
		return false;
	});

	// for code rendering
	$("#code_file_result_raw").html ($("#code_file_result_code").html())
	prettyPrint ();
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

<div class="content" id="code_file_content">

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

<div class="mainarea" id="code_file_mainarea">

<div class="codepot-title-band" id="code_file_title_band">

	<div class="title">
		<?php
		print anchor (
			"code/file/{$project->id}{$revreqroot}",
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
				"code/file/{$project->id}/{$xpar}{$revreq}",
				htmlspecialchars($exps[$i]));
		}

		if ($headpath != $file['fullpath'])
		{
			print ' - ';
			print htmlspecialchars($file['fullpath']);
		}
		?>
	</div>

	<div class="actions">
		<?php 
		print anchor ("code/file/{$project->id}/${hex_headpath}/{$file['prev_rev']}", '<i class="fa fa-arrow-circle-left"></i>');
		print ' ';

		// anchor to the revision history at the root directory
		print anchor (
			//"code/revision/{$project->id}/!/{$file['created_rev']}",
			"code/revision/{$project->id}/${hex_headpath}/{$file['created_rev']}",
			sprintf("%s %s", $this->lang->line('Revision'), $file['created_rev'])
		);

		if (!empty($file['created_tag']))
		{
			print ' ';
			print ('<span class="left_arrow_indicator">');
			print htmlspecialchars($file['created_tag']);
			print ('</span>');
		}

		print ' ';
		print anchor ("code/file/{$project->id}/${hex_headpath}/{$file['next_rev']}", '<i class="fa fa-arrow-circle-right"></i>');

		print ' | ';
		printf ('%s: %s', $this->lang->line('Size'), $file['size']);


		if ((isset($login['id']) && $login['id'] != ''))
		{
			print ' ';
			print anchor ("code/edit/{$project->id}/{$hex_headpath}{$revreq}", $this->lang->line('Edit'), 'id="code_file_mainarea_edit_button"');
		}
		print anchor ("#", "LOC", "id=code_file_mainarea_loc_button");
		?>
	</div>

	<div style="clear: both;"></div>
</div>

<div id='code_file_metadata' class='collapsible-box'>
	<div id='code_file_metadata_header' class='collapsible-box-header'>
		<?php
		print '<div class="metadata-committer">';
		$user_icon_url = codepot_merge_path (site_url(), '/user/icon/' . $this->converter->AsciiToHex($file['last_author']));
		print "<img src='{$user_icon_url}' class='metadata-committer-icon' />";
		print htmlspecialchars ($file['last_author']);
		print '</div>';

		print '<div class="metadata-menu">';

		$blame_anchor_text = '<i class="fa fa-bomb"></i> ' . $this->lang->line('Blame');
		$history_anchor_text = '<i class="fa fa-history"></i> ' . $this->lang->line('History');
		$download_anchor_text = '<i class="fa fa-download"></i> ' . $this->lang->line('Download');
		$diff_anchor_text = '<i class="fa fa-server"></i> ' . $this->lang->line('Difference');
		$fulldiff_anchor_text = '<i class="fa fa-tasks"></i> ' . $this->lang->line('Full Difference');

		if ($file['created_rev'] != $file['head_rev']) 
		{
			$head_revision_text = '<i class="fa fa-exclamation-triangle" style="color:#CC2222"></i> ' . $this->lang->line('Head');
			print anchor ('#', $head_revision_text, 'id="code_file_headrev_button"');
		}

		print anchor ('#', $blame_anchor_text, 'id="code_file_blame_button"');
		print anchor ('#', $diff_anchor_text, 'id="code_file_diff_button"');
		print anchor ('#', $fulldiff_anchor_text, 'id="code_file_fulldiff_button"');
		print anchor ('#', $history_anchor_text, 'id="code_file_history_button"');
		//print anchor ('', $download_anchor_text, 'id="code_file_download_button"');
		print anchor ("code/fetch/{$project->id}/${hex_headpath}{$revreq}", $download_anchor_text, 'id="code_file_download_button"');
		print anchor ('#', $this->lang->line('Enstyle'), 'id="code_file_style_button"');
		print '</div>';

		print '<div class="metadata-commit-date">';
		printf ('[%s] ', $file['created_rev']);
		print strftime ('%Y-%m-%d %H:%M:%S %z', $file['time_t']);
		print '</div>';
		?>
		<div style='clear: both'></div>
	</div>

	<div id='code_file_metadata_body'>
		<div class='codepot-plain-text-view'>
			<pre><?php print htmlspecialchars ($file['logmsg']); ?></pre>
		</div>

		<?php
		if (array_key_exists('properties', $file) && count($file['properties']) > 0)
		{
			print '<ul id="code_file_property_list">';
			foreach ($file['properties'] as $pn => $pv)
			{
				print '<li>';
				print htmlspecialchars($pn);
				if ($pv != '')
				{
					print ' - ';
					print htmlspecialchars($pv);
				}
				print '</li>';
			}
			print '</ul>';
		}
		?>
	</div>
</div>

<div style="display:none">
<pre id="code_file_result_raw">
</pre>
</div>

<div id="code_file_result" class="codepot-relative-container-view codepot-styled-code-view" >

<?php 
$fileext = substr(strrchr($file['name'], '.'), 1);
if ($fileext == 'adb' || $fileext == 'ads') $fileext = 'ada';
else if ($fileext == 'pas') $fileext = 'pascal';
else if ($fileext == 'bas') $fileext = 'basic';

$prettyprint_lang = ($fileext != '')?  "lang-$fileext": '';

$prettyprint_linenums = 'linenums';
if ($login['settings'] != NULL &&
    $login['settings']->code_hide_line_num == 'Y') $prettyprint_linenums = '';
?>

<pre class="prettyprint <?php print $prettyprint_linenums?> <?php print $prettyprint_lang?>" id="code_file_result_code">
<?php 
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

	if (!$is_image_stream) 
	{
		$charset = '';
		if (array_key_exists('properties', $file) && count($file['properties']) > 0)
		{
			$p = &$file['properties'];
			if (array_key_exists('svn:mime-type', $p))
			{
				if (@preg_match('|\s*[\w/+]+;\s*charset=(\S+)|i', $p['svn:mime-type'], $matches)) 
				{
					$charset = $matches[1];
				}
			}
		}

		if ($charset == '')
		{
			if (property_exists($project, 'codecharset') && strlen($project->codecharset))
				$charset = $project->codecharset;
		}

		if ($charset == '')
		{
			print htmlspecialchars($file['content']); 
		}
		else
		{
			// ignore iconv error
			print htmlspecialchars(@iconv ($charset, 'UTF-8//IGNORE', $file['content'])); 
		}
	}
?>
</pre>

<div id="code_file_mainarea_loc_info" class="codepot-infobox">
	<div class="title">LOC</div>
	<?php
		/* TODO: show this if it's enabled in the user settings  */
		$graph_url = codepot_merge_path (site_url(), "/code/graph/cloc-file/{$project->id}/{$hex_headpath}{$revreq}");
		print "<img src='{$graph_url}' id='code_file_mainarea_loc_info_locgraph' />";
	?>
</div> <!-- code_file_mainarea_loc_info -->

</div> <!-- code_file_result -->

</div> <!-- code_file_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  code_file_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>

</html>

