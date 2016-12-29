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
$(function () {

	$('#code_blame_metadata').accordion({
		collapsible: true,
		heightStyle: "content"
	});

	$("#code_blame_mainarea_loc_info").hide();
	btn = $("#code_blame_mainarea_loc_button").button().click (function () {
		if ($("#code_blame_mainarea_loc_info").is(":visible"))
		{
			$("#code_blame_mainarea_loc_info").hide("blind",{},200);
		}
		else
		{
			$("#code_blame_mainarea_loc_info").show("blind",{},200);
		}

		return false; // prevent the default behavior
	});

	$("#code_blame_mainarea_edit_button").button();

	<?php if ($file['created_rev'] != $file['head_rev']): ?>
		$("#code_blame_headrev_button").button().click (function() {
			$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/blame/{$project->id}/${hex_headpath}"; ?>'));
			return false;
		});
	<?php endif; ?>

	$("#code_blame_detail_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/file/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});
	$("#code_blame_diff_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/diff/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});

	$("#code_blame_fulldiff_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/fulldiff/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});

	$("#code_blame_history_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print $history_path; ?>'));
		return false;
	});
	$("#code_blame_download_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/fetch/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});
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

<div class="content" id="code_blame_content">

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

<div class="mainarea" id="code_blame_mainarea">

<div class="codepot-title-band" id="code_blame_title_band">

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
			if ($i == $expsize - 1)
			{
				print anchor (
					"code/blame/{$project->id}/{$xpar}{$revreq}",
					htmlspecialchars($exps[$i]));
			}
			else
			{
				print anchor (
					"code/file/{$project->id}/{$xpar}{$revreq}",
					htmlspecialchars($exps[$i]));
			}
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
		print anchor ("code/blame/{$project->id}/{$hex_headpath}/{$file['prev_rev']}", '<i class="fa fa-arrow-circle-left"></i>');
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
		print anchor ("code/blame/{$project->id}/{$hex_headpath}/{$file['next_rev']}", '<i class="fa fa-arrow-circle-right"></i>');

		print ' | ';
		printf ('%s: %s', $this->lang->line('Size'), $file['size']);

		if ((isset($login['id']) && $login['id'] != ''))
		{
			print ' ';
			print anchor ("code/bledit/{$project->id}/{$hex_headpath}{$revreq}", $this->lang->line('Edit'), 'id="code_blame_mainarea_edit_button"');
		}

		print anchor ("#", "LOC", "id=code_blame_mainarea_loc_button");
		?>

	</div>
	<div style="clear: both;"></div>
</div>

<div id='code_blame_metadata' class='collapsible-box'>
	<div id='code_blame_metadata_header' class='collapsible-box-header'>
	<?php
		print '<div class="metadata-committer">';
		$user_icon_url = codepot_merge_path (site_url(), '/user/icon/' . $this->converter->AsciiToHex($file['last_author']));
		print "<img src='{$user_icon_url}' class='metadata-committer-icon' />";
		print htmlspecialchars ($file['last_author']);
		print '</div>';

		print '<div class="metadata-menu">';

		$detail_anchor_text = $this->lang->line('Details');
		$history_anchor_text = '<i class="fa fa-history"></i> ' . $this->lang->line('History');
		$download_anchor_text = '<i class="fa fa-download"></i> ' . $this->lang->line('Download');
		$diff_anchor_text = '<i class="fa fa-server"></i> ' . $this->lang->line('Difference');
		$fulldiff_anchor_text = '<i class="fa fa-tasks"></i> ' . $this->lang->line('Full Difference');

		if ($file['created_rev'] != $file['head_rev']) 
		{
			$head_revision_text = '<i class="fa fa-exclamation-triangle" style="color:#CC2222"></i> ' . $this->lang->line('Head');
			print anchor ('#', $head_revision_text, 'id="code_blame_headrev_button"');
		}

		print anchor ('#', $detail_anchor_text, 'id="code_blame_detail_button"');
		print anchor ('#', $diff_anchor_text, 'id="code_blame_diff_button"');
		print anchor ('#', $fulldiff_anchor_text, 'id="code_blame_fulldiff_button"');
		print anchor ('#', $history_anchor_text, 'id="code_blame_history_button"');
		print anchor ('#', $download_anchor_text, 'id="code_blame_download_button"');
		print '</div>';

		print '<div class="metadata-commit-date">';
		printf ('[%s] ', $file['created_rev']);
		print codepot_unixtimetodispdate ($file['time_t']);
		print '</div>';
	?>
		<div style='clear: both'></div>
	</div>

	<div id='code_blame_metadata_body' class='codepot-metadata-collapsible-body'>
		<div class='codepot-plain-text-view'>
			<pre><?php print htmlspecialchars ($file['logmsg']); ?></pre>
		</div>

		<?php
		if (array_key_exists('properties', $file) && count($file['properties']) > 0)
		{
			print '<ul id="code_blame_property_list">';
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

<div id="code_blame_result" class="codepot-relative-container-view codepot-styled-code-view" >

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

	<?php
	print '<pre id="code_blame_result_code_container" class="codepot-line-numbered">';
	// when producing codepot-line-numbered code, make sure to produce proper
	// line terminators.
	//
	// the <code> </code> block requires \n after every line.
	// while the <span></span> block to contain revision numbers and authors
	// doesn't require \n. It is because the css file sets the codepot-line-number span
	// to display: block.
	//
	// If you have new lines between <span></span> and <code></code>, there will
	// be some positioning problems as thouse new lines are rendered at the top
	// of the actual code.
	//
	$content = &$file['content'];
	$len = count($content);

	print '<span class="codepot-line-number-block" id="code_blame_result_code_revision">';
	$rev = '';
	for ($i = 0; $i < $len; $i++)
	{
		$line = &$content[$i];

		if ($line['rev'] != $rev) 
		{
			$rev = $line['rev'];
			//$rev_to_show = str_pad ($rev, 6, ' ', STR_PAD_LEFT);
			$rev_to_show = $rev;

			$xpar = $this->converter->AsciiTohex ($headpath);
			$rev_to_show = anchor ("code/blame/{$project->id}/{$hex_headpath}/{$rev}", $rev_to_show);
		}
		else
		{
			//$rev_to_show = str_pad (' ', 6, ' ', STR_PAD_LEFT);
			$rev_to_show = ' ';
		}

		print "<span>{$rev_to_show}</span>";
	}
	print '</span>';

	print '<span class="codepot-line-number-block" id="code_blame_result_code_author">';
	$rev = '';
	$author = '';
	for ($i = 0; $i < $len; $i++)
	{
		$line = &$content[$i];

		if ($line['author'] != $author || $line['rev'] != $rev) 
		{
			$author = $line['author'];
			//$author_to_show = str_pad ($author, 9, ' ', STR_PAD_RIGHT);
			//$author_to_show = substr($author_to_show, 0, 9);
			//$author_to_show = $author;

			$author_home_url = codepot_merge_path (site_url(), '/user/home/' . $this->converter->AsciiToHex($author));
			$author_to_show = "<a href='{$author_home_url}'>" . htmlspecialchars($author) . "</a>";
		}
		else
		{
			//$author_to_show = str_pad (' ', 9, ' ', STR_PAD_RIGHT);
			$author_to_show = ' ';
		}

		$rev = $line['rev'];
		print "<span>{$author_to_show}</span>";
	}
	print '</span>';

	printf ('<code class="codepot-line-numbered-code prettyprint %s %s" id="code_blame_result_code">', $prettyprint_linenums, $prettyprint_lang);

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


	for ($i = 0; $i < $len; $i++)
	{
		$line = &$content[$i];

		if ($charset == '')
		{
			print htmlspecialchars ($line['line']);
		}
		else
		{
			// ignore iconv error 
			print htmlspecialchars (@iconv($charset, 'UTF-8//IGNORE', $line['line']));
		}
		print "\n";
	}

	print '</code>';
	print '</pre>';
	?>

	<div id="code_blame_mainarea_loc_info" class="codepot-infobox">
		<div class="title">LOC</div>
		<?php
		/* TODO: show this if it's enabled in the user settings  */
		$graph_url = codepot_merge_path (site_url(), "/code/graph/cloc-file/{$project->id}/{$hex_headpath}{$revreq}");
		print "<img src='{$graph_url}' id='code_blame_mainarea_loc_info_locgraph' />";
		?>
	</div> <!-- code_blame_mainarea_loc_info -->

</div> <!-- code_blame_result -->

</div> <!-- code_blame_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  code_blame_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</body>

</html>

