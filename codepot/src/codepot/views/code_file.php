<html>

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

<script type="text/javascript">
$(function () {
	<?php
	if ($login['settings'] != NULL && $login['settings']->code_hide_metadata == 'Y')
		print '$("#code_file_mainarea_result_info").hide();';
	?>

	if ($("#code_file_mainarea_result_info").is(":visible"))
		btn_label = "<?php print $this->lang->line('Hide metadata')?>";
	else
		btn_label = "<?php print $this->lang->line('Show metadata')?>";

	btn = $("#code_file_mainarea_metadata_button").button({"label": btn_label}).click (function () {
		if ($("#code_file_mainarea_result_info").is(":visible"))
		{
			$("#code_file_mainarea_result_info").hide("blind",{},200);
			$("#code_file_mainarea_metadata_button").button(
				"option", "label", "<?php print $this->lang->line('Show metadata')?>");
		}
		else
		{
			$("#code_file_mainarea_result_info").show("blind",{},200);
			$("#code_file_mainarea_metadata_button").button(
				"option", "label", "<?php print $this->lang->line('Hide metadata')?>");
		}
	});

	// for code rendering
	$("#code_file_mainarea_result_raw").html ($("#code_file_mainarea_result_code").html())
	prettyPrint ();
});

var showing_raw_code = false;

function showRawCode()
{
	if (showing_raw_code)
	{
		
		$("#code_file_style_anchor").text("<?php print $this->lang->line('Destyle')?>");
		$("#code_file_mainarea_result_code").removeClass("prettyprinted");
		prettyPrint();
	}
	else
	{
		$("#code_file_style_anchor").text("<?php print $this->lang->line('Enstyle')?>");
		$("#code_file_mainarea_result_code").html($("#code_file_mainarea_result_raw").html());
	}

	showing_raw_code = !showing_raw_code;
}

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

<div class="title" id="code_file_mainarea_title">
<?php
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
</div> <!-- code_file_mainarea_title -->

<div class="menu" id="code_file_mainarea_menu">
<?php
	$xpar = $this->converter->AsciiToHex ($headpath);

	$blame_anchor_text = '<i class="fa fa-bomb"></i> ' . $this->lang->line('Blame');
	$history_anchor_text = '<i class="fa fa-history"></i> ' . $this->lang->line('History');
	$download_anchor_text = '<i class="fa fa-download"></i> ' . $this->lang->line('Download');

	if ($file['created_rev'] != $file['head_rev']) 
	{
		print anchor (
			"code/file/{$project->id}/${xpar}",
			$this->lang->line('Head revision'));
		print ' | ';
	}

	print anchor ("code/blame/{$project->id}/${xpar}{$revreq}", $blame_anchor_text);
	print ' | ';
	print anchor ("code/diff/{$project->id}/{$xpar}{$revreq}", $this->lang->line('Difference'));
	print ' | ';
	print anchor ("code/fulldiff/{$project->id}/{$xpar}{$revreq}", $this->lang->line('Full Difference'));
	print ' | ';

	if ($revision > 0)
	{
		if ($xpar == '') $revtrailer = $revreqroot;
		else $revtrailer = "/{$xpar}{$revreq}";
		print anchor ("code/history/{$project->id}{$revtrailer}", $history_anchor_text);
	}
	else
	{
		print anchor ("code/history/{$project->id}/{$xpar}", $history_anchor_text);
	}

	print ' | ';
	print anchor ("code/fetch/{$project->id}/${xpar}{$revreq}", $download_anchor_text);

	print ' | ';
	print anchor (
		"code/file/{$project->id}/${xpar}{$revreq}",
		$this->lang->line('Destyle'), 
		array('id'      => 'code_file_style_anchor', 
		      'onClick' => 'showRawCode(); return false;')
	);
?>
</div> <!-- code_file_mainarea_menu -->

<div class="infostrip" id="code_file_mainarea_infostrip">

	<?php 
		print anchor ("code/file/{$project->id}/${xpar}/{$file['prev_rev']}", '<i class="fa fa-arrow-circle-left"></i>');
		print ' ';

		// anchor to the revision history at the root directory
		print anchor (
			"code/revision/{$project->id}/!/{$file['created_rev']}",
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
		print anchor ("code/file/{$project->id}/${xpar}/{$file['next_rev']}", '<i class="fa fa-arrow-circle-right"></i>');

		print ' | ';
		printf ('%s: %s', $this->lang->line('Size'), $file['size']);
	?>
	<a id="code_file_mainarea_metadata_button" href='#'><?php print $this->lang->line('Metadata')?></a>
</div>

<div style="display:none">
<pre id="code_file_mainarea_result_raw">
</pre>
</div>

<div class="result" id="code_file_mainarea_result">

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

<pre class="prettyprint <?php print $prettyprint_linenums?> <?php print $prettyprint_lang?>" id="code_file_mainarea_result_code">
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

	if (!$is_image_stream) print htmlspecialchars($file['content']); 
?>
</pre>

<div id="code_file_mainarea_result_info" class="infobox">
<div class="title"><?php print  $this->lang->line('CODE_COMMIT') ?></div>
<ul>
<li><?php printf ($this->lang->line('CODE_MSG_COMMITTED_BY_ON'), $file['last_author'], $file['time']); ?></li>
</ul>

<div class="title"><?php print  $this->lang->line('Message') ?></div>
<pre id="code_file_mainarea_result_info_logmsg" class="pre-wrapped">
<?php print  htmlspecialchars ($file['logmsg']) ?>
</pre>

<?php
if (array_key_exists('properties', $file) && count($file['properties']) > 0)
{
	print '<div class="title">';
	print $this->lang->line('CODE_PROPERTIES');
	print '</div>';

	print '<ul id="code_file_mainarea_result_info_property_list">';
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
</pre>

<div class="title">LOC</div>
<?php
	/* TODO: show this if it's enabled in the user settings  */
	$graph_url = codepot_merge_path (site_url(), "/code/graph/cloc-file/{$project->id}/{$xpar}{$revreq}");
	print "<img src='{$graph_url}' id='code_file_mainarea_result_info_locgraph' />";
?>

</div> <!-- code_file_mainarea_result_info -->


</div> <!-- code_file_mainarea_result -->

</div> <!-- code_file_mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  code_file_content -->

</body>

</html>

