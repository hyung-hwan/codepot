<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/code.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

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
$hex_headpath = $this->converter->AsciiTohex ($headpath);

if ($revision1 <= 0)
{
	$revreq = '';
	$revreqroot = '';

	$dualrevreq = '';
	$dualrevreqroot = '';

	$history_path = "/code/history/{$project->id}/{$hex_headpath}";
}
else
{
	$revreq = "/{$file['created_rev']}";
	$revreqroot = '/' . $this->converter->AsciiToHex ('.') . $revreq;

	$dualrevreq = "/{$file['created_rev']}/{$file['against']['created_rev']}";
	$dualrevreqroot = '/' . $this->converter->AsciiToHex ('.') . $dualrevreq;

	if ($hex_headpath == '') $revtrailer = $revreqroot;
	else $revtrailer = "/{$hex_headpath}{$revreq}";
	$history_path = "/code/history/{$project->id}{$revtrailer}";
}

$revreq_against = "/{$file['against']['created_rev']}";
$revreqroot_against = '/' . $this->converter->AsciiToHex ('.') . $revreq_against;
if ($hex_headpath == '') $revtrailer_against = $revreqroot_against;
else $revtrailer_against = "/{$hex_headpath}{$revreq_against}";
$history_against_path = "/code/history/{$project->id}{$revtrailer_against}";

$head_revision_text = '<i class="fa fa-exclamation-triangle" style="color:#CC2222"></i> ' . $this->lang->line('Head');
$history_anchor_text = '<i class="fa fa-history"></i> ' . $this->lang->line('History');

$blame_anchor_text = '<i class="fa fa-bomb"></i> ' . $this->lang->line('Blame');
$download_anchor_text = '<i class="fa fa-download"></i> ' . $this->lang->line('Download');

if ($fullview)
{
	$diff_view = 'fulldiff';
	$altdiff_view = 'diff';

	$diff_anchor_text = '<i class="fa fa-tasks"></i> ' . $this->lang->line('Full Difference');
	$altdiff_anchor_text = '<i class="fa fa-server"></i> ' . $this->lang->line('Difference');
}
else
{
	$diff_view = 'diff';
	$altdiff_view = 'fulldiff';

	$diff_anchor_text = '<i class="fa fa-server"></i> ' . $this->lang->line('Difference');
	$altdiff_anchor_text = '<i class="fa fa-tasks"></i> ' . $this->lang->line('Full Difference');
}
?>

<script type="text/javascript">
$(function() {
	$('#code_diff_metadata').accordion({
		collapsible: true,
		heightStyle: "content"
	});

	$('#code_diff_metadata_against').accordion({
		collapsible: true,
		heightStyle: "content"
	});


	<?php if ($file['created_rev'] != $file['head_rev']): ?>
		$("#code_diff_headrev_button").button().click (function() {
			$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/{$diff_view}/{$project->id}/${hex_headpath}"; ?>'));
			return false;
		});
	<?php endif; ?>

	$("#code_diff_detail_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/file/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});
	$("#code_diff_blame_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/blame/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});
	$("#code_diff_diff_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/{$altdiff_view}/{$project->id}/${hex_headpath}{$dualrevreq}"; ?>'));
		return false;
	});
	$("#code_diff_history_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print $history_path; ?>'));
		return false;
	});
	$("#code_diff_download_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/fetch/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});


	$("#code_diff_detail_against_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/file/{$project->id}/${hex_headpath}{$revreq_against}"; ?>'));
		return false;
	});
	$("#code_diff_blame_against_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/blame/{$project->id}/${hex_headpath}{$revreq_against}"; ?>'));
		return false;
	});
	$("#code_diff_diff_against_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/{$altdiff_view}/{$project->id}/${hex_headpath}{$dualrevreq}"; ?>'));
		return false;
	});
	$("#code_diff_history_against_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print $history_against_path; ?>'));
		return false;
	});
	$("#code_diff_download_against_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/fetch/{$project->id}/${hex_headpath}{$revreq_against}"; ?>'));
		return false;
	});

	prettyPrint();
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

<div class="content" id="code_diff_content">

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

<div class="mainarea" id="code_diff_mainarea">

<div class="codepot-title-band" id="code_diff_title_band">

	<div class="title">
	<?php
		if ($revision1 <= 0)
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
			$par .= "/{$exps[$i]}";

			$xpar = $this->converter->AsciiToHex ($par);
			$xpar = "code/file/{$project->id}/{$xpar}{$revreq}";

			print '/';
			print anchor ($xpar, htmlspecialchars($exps[$i]));
		}
	?>
	</div>

	<div class="actions"></div>
	<div style="clear: both;"></div>
</div>

<div id='code_diff_metadata_container'>
	<div id='code_diff_metadata_against' class='collapsible-box'>
		<div id='code_diff_metadata_against_header' class='collapsible-box-header'>
			<?php
			print '<div class="metadata-committer">';
			$user_icon_url = codepot_merge_path (site_url(), '/user/icon/' . $this->converter->AsciiToHex($file['against']['last_author']));
			print "<img src='{$user_icon_url}' class='metadata-committer-icon' />";
			print htmlspecialchars ($file['against']['last_author']);
			print '</div>';

			print '<div class="metadata-menu">';
			print anchor ("#", $this->lang->line('Details'), 'id="code_diff_detail_against_button"');
			print anchor ("#", $blame_anchor_text, 'id="code_diff_blame_against_button"');
			print anchor ("#", $altdiff_anchor_text, 'id="code_diff_diff_against_button"');
			print anchor ("#", $history_anchor_text, 'id="code_diff_history_against_button"');
			print anchor ("#", $download_anchor_text, 'id="code_diff_download_against_button"');
			print '</div>';

			print '<div class="metadata-commit-date">';
			printf ('[%s] ', $file['against']['created_rev']);
			print strftime ('%Y-%m-%d %H:%M:%S %z', $file['against']['time_t']);
			print '</div>'
			?>

			<div style='clear: both'></div>
		</div>

		<div id='code_diff_metadata_against_body' class='codepot-metadata-collapsible-body'>
			<div class='codepot-plain-text-view'>
				<pre><?php print htmlspecialchars ($file['against']['logmsg']); ?></pre>
			</div>
		</div>
	</div>

	<div id='code_diff_metadata' class='collapsible-box'>
		<div id='code_diff_metadata_header' class='collapsible-box-header'>
			<?php
			print '<div class="metadata-committer">';
			$user_icon_url = codepot_merge_path (site_url(), '/user/icon/' . $this->converter->AsciiToHex($file['last_author']));
			print "<img src='{$user_icon_url}' class='metadata-committer-icon' />";
			print htmlspecialchars ($file['last_author']);
			print '</div>';

			print '<div class="metadata-menu">';
			if ($file['created_rev'] != $file['head_rev']) 
			{
				print anchor ('#', $head_revision_text, 'id="code_diff_headrev_button"');
			}

			print anchor ("#", $this->lang->line('Details'), 'id="code_diff_detail_button"');
			print anchor ("#", $blame_anchor_text, 'id="code_diff_blame_button"');
			print anchor ("#", $altdiff_anchor_text, 'id="code_diff_diff_button"');
			print anchor ("#", $history_anchor_text, 'id="code_diff_history_button"');
			print anchor ("#", $download_anchor_text, 'id="code_diff_download_button"');
			print '</div>';

			print '<div class="metadata-commit-date">';
			printf ('[%s] ', $file['created_rev']);
			print strftime ('%Y-%m-%d %H:%M:%S %z', $file['time_t']);
			print '</div>'
			?>

			<div style='clear: both'></div>
		</div>

		<div id='code_diff_metadata_body' class='codepot-metadata-collapsible-body'>
			<div class='codepot-plain-text-view'>
				<pre><?php print htmlspecialchars ($file['logmsg']); ?></pre>
			</div>
		</div>
	</div>

	<div style='clear: both;'></div>
</div>

<?php 
	$fileext = substr(strrchr($file['name'], '.'), 1);
	if ($fileext == "") $fileext = "html"
?>

<div id="code_diff_result" class="codepot-relative-container-view codepot-styled-code-view">
	<?php
	function format_diff2 ($a, $b, $css_class)
	{
		$ms = codepot_find_matching_sequences ($a, $b);
		$ms_count = count($ms);

		$k = 0;
		$cc = ''; 

		if ($css_class == 'diffchangedold')
		{
			for ($i = 0; $i < $ms_count; $i++)
			{
				list($mp1, $mp2, $ml) = $ms[$i];
				if ($mp1 > $k)
				{
					$cc .= sprintf ('<span class="%s">', $css_class);
					$cc .= htmlspecialchars(substr($a, $k, $mp1 - $k));
					$cc .= '</span>';
				}
				$cc .= htmlspecialchars(substr($a, $mp1, $ml));
				$k = $mp1 + $ml;
			}
			if ($k < strlen($a)) 
			{
				$cc .= sprintf ('<span class="%s">', $css_class);
				$cc .= htmlspecialchars(substr($a, $k));
				$cc .= '</span>';
			}
		}
		else
		{
			for ($i = 0; $i < $ms_count; $i++)
			{
				list($mp1, $mp2, $ml) = $ms[$i];
				if ($mp2 > $k)
				{
					$cc .= sprintf ('<span class="%s">', $css_class);
					$cc .= htmlspecialchars(substr($b, $k, $mp2 - $k));
					$cc .= '</span>';
				}
				$cc .= htmlspecialchars(substr($b, $mp2, $ml));
				$k = $mp2 + $ml;
			}
			if ($k < strlen($b)) 
			{
				$cc .= sprintf ('<span class="%s">', $css_class);
				$cc .= htmlspecialchars(substr($b, $k));
				$cc .= '</span>';
			}
		}


		return $cc;
	}

	$http_user_agent = $_SERVER['HTTP_USER_AGENT']; 
	$is_msie = (stristr($http_user_agent, 'MSIE') !== FALSE && 
			  stristr($http_user_agent, 'Opera') === FALSE);
	if (!$is_msie) $is_msie = (preg_match ("/^Mozilla.+\(Windows.+\) like Gecko$/", $http_user_agent) !== FALSE);

	print '<div id="code_diff_full_code_view">';

	//
	// SHOW THE OLD FILE
	//
	print ('<div id="code_diff_old_code_view">');

	print '<div class="navigator">';

	$currev = $file['created_rev'];
	$prevrev = $file['against']['prev_rev'];
	$prevanc = "code/{$diff_view}/{$project->id}/{$hex_headpath}/{$currev}/{$prevrev}";

	print anchor ($prevanc, '<i class="fa fa-arrow-circle-left"></i>');
	print ' ';

	// show the history details of the previous revision at the root directory
	$revanc = "code/revision/{$project->id}/!/{$file['against']['created_rev']}";
	$codeanc = "code/file/{$project->id}/{$hex_headpath}/{$file['against']['created_rev']}";
	print anchor ($revanc, $this->lang->line('Revision'));
	print ' ';
	print anchor ($codeanc, $file['against']['created_rev']);

	$currev = $file['created_rev'];
	$nextrev = $file['against']['next_rev'];
	$nextanc = "code/{$diff_view}/{$project->id}/{$hex_headpath}/{$currev}/{$nextrev}";
	print ' ';
	print anchor ($nextanc, '<i class="fa fa-arrow-circle-right"></i>');
	print "</div>"; // navigator

	print '<pre id="code_diff_old_code" class="codepot-line-numbered">';

	print '<span class="codepot-line-number-block">';
	$actual_line_no = 1;
	foreach ($file['content'] as $x)
	{
		if (array_key_exists('rev2line', $x)) 
		{
			// on the old file, there can be unchanged, changed, and deleted lines.
			// however, it should consider added lines on the new file side.
			if (array_key_exists('rev2diffclass', $x) && $x['rev2diffclass'] == 'diffadded')
			{
				// the line number is not display on the old file side
				// as it doesn't have anthing meaningful to show.
				// this line is added because the new file side has something
				// to show as that both the old file view and the new file
				// view can go side by side.
				print "<span> </span>";
			}
			else
			{
				print "<span>$actual_line_no</span>";
				$actual_line_no++;
			}
		}
		else
		{
			if ($actual_line_no > 1) print "<span class='codepot-line-number-empty'>&nbsp;</span>";
			$actual_line_no = $x['rev1lineno'];
		}
	}
	print '</span>';

	print '<code class="codepot-line-numbered-code prettyprint lang-{$fileext}">';
	$actual_line_no = 1;
	foreach ($file['content'] as $x)
	{
		if (array_key_exists('rev2line', $x)) 
		{
			$diffclass = array_key_exists('rev1diffclass', $x)? $x['rev1diffclass']: 'diff';
			print "<span class='{$diffclass}'>";

			if ($diffclass == 'diffchanged')
			{
				//$xline = format_diff ($x['rev1line'], $x['rev2line'], 'diffchangedold');
				$xline = format_diff2 ($x['rev1line'], $x['rev2line'], 'diffchangedold');
			}
			else 
			{
				$xline = htmlspecialchars($x['rev1line']);
			}

			if ($is_msie && $xline == '') $xline = '&nbsp;';
			print $xline;
			print "</span>\n";

			// on the old file, there can be unchanged, changed, and deleted lines.
			$actual_line_no++;
		}
		else
		{
			// this is the line number that tells which line the upcoming 
			// block of difference begins. set $actual_line_no to this line number.
			//print "<span class='diffrow'> ";
			//print $x['rev1lineno'];
			//print " </span>\n";
			if ($actual_line_no > 1) print "<span class='codepot-line-numbered-code-line-empty'>&nbsp;</span>\n"; // \n is required here unlike in the codepot-line-number-block
			$actual_line_no = $x['rev1lineno'];
		}
	}
	print '</code>';
	print '<span class="codepot-line-number-clear"></span>';
	print '</pre>';

	print '</div>';

	//
	// SHOW THE NEW FILE
	//
	print ('<div id="code_diff_new_code_view">');

	print '<div class="navigator">';

	$currev = $file['against']['created_rev'];
	$prevrev = $file['prev_rev'];
	$prevanc = "code/{$diff_view}/{$project->id}/{$hex_headpath}/{$prevrev}/{$currev}";
	print anchor ($prevanc, '<i class="fa fa-arrow-circle-left"></i>');
	print ' ';

	// show the history details of the current revision at the root directory
	$revanc = "code/revision/{$project->id}/!/{$file['created_rev']}";
	$codeanc = "code/file/{$project->id}/{$hex_headpath}/{$file['created_rev']}";
	print anchor ($revanc, $this->lang->line('Revision'));
	print ' ';
	print anchor ($codeanc, $file['created_rev']);

	$currev = $file['against']['created_rev'];
	$nextrev = $file['next_rev'];
	$nextanc = "code/{$diff_view}/{$project->id}/{$hex_headpath}/{$nextrev}/{$currev}";
	print ' ';
	print anchor ($nextanc, '<i class="fa fa-arrow-circle-right"></i>');
	print "</div>"; // navigator

	print '<pre id="code_diff_new_code" class="codepot-line-numbered">';

	print '<span class="codepot-line-number-block">';
	$actual_line_no = 1;
	foreach ($file['content'] as $x)
	{
		// on the old file, there can be unchanged, changed, and added lines.
		// however, the new file side must consider deleted lines on the old file side.
		if (array_key_exists('rev2line', $x)) 
		{
			if (array_key_exists('rev1diffclass', $x) && $x['rev1diffclass'] == 'diffdeleted')
			{
				/* corresponding line on the old file has been deleted */
				print "<span> </span>";
			}
			else
			{
				print "<span>$actual_line_no</span>";
				$actual_line_no++;
			}
		}
		else
		{
			if ($actual_line_no > 1) print "<span class='codepot-line-number-empty'>&nbsp;</span>";
			$actual_line_no = $x['rev2lineno'];
		}
	}
	print '</span>';

	print '<code class="codepot-line-numbered-code prettyprint lang-{$fileext}" class="codepot-line-numbered-code">';
	$actual_line_no = 1;
	foreach ($file['content'] as $x)
	{
		if (array_key_exists('rev2line', $x)) 
		{
			$diffclass = array_key_exists('rev2diffclass', $x)? $x['rev2diffclass']: 'diff';

			print "<span class='{$diffclass}'>";

			if ($diffclass == 'diffchanged')
			{
				//$xline = format_diff ($x['rev2line'], $x['rev1line'], 'diffchangednew');
				$xline = format_diff2 ($x['rev1line'], $x['rev2line'], 'diffchangednew');
			}
			else 
			{
				$xline = htmlspecialchars($x['rev2line']);
			}

			if ($is_msie && $xline == '') $xline = '&nbsp;';
			print $xline;
			print "</span>\n";

			if (array_key_exists('rev1diffclass', $x) && $x['rev1diffclass'] == 'diffdeleted')
			{
				/* corresponding line on the old file has been deleted */
			}
			else
			{
				$actual_line_no++;
			}
		}
		else
		{
			//print "<span class='diffrow'> ";
			//print $x['rev2lineno'];
			//print " </span>\n";
			if ($actual_line_no > 1) print "<span class='codepot-line-numbered-code-line-empty'>&nbsp;</span>\n"; // \n is required here unlike in the line number block
			$actual_line_no = $x['rev2lineno'];
		}
	}


	print '</code>';
	print '<span class="codepot-line-number-clear"></span>';
	print '</pre>';

	print '</div>';


	print '</div>';

	?>

</div>

</div> <!-- code_diff_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- code_diff_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>

</html>

