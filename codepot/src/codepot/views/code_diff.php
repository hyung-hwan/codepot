<html>

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

<script type="text/javascript">
$(function() {
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

<div class="title" id="code_diff_mainarea_title">
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
</div> <!-- code_diff_mainarea_title -->

<div class="menu" id="code_diff_mainarea_menu">
<?php
	$history_anchor_text = '<i class="fa fa-history"></i> ' . $this->lang->line('History');
	$diff_anchor_text = '<i class="fa fa-server"></i> ' . $this->lang->line('Difference');
	$fulldiff_anchor_text = '<i class="fa fa-tasks"></i> ' . $this->lang->line('Full Difference');
	$blame_anchor_text = '<i class="fa fa-bomb"></i> ' . $this->lang->line('Blame');

	$xpar = $this->converter->AsciiTohex ($headpath);
	print anchor (
		"code/file/{$project->id}/{$xpar}{$revreq}",
		$this->lang->line('Details'));
	print ' | ';
	print anchor ("code/blame/{$project->id}/{$xpar}{$revreq}", $blame_anchor_text);
		
	print ' | ';

	if (!$fullview)
	{
		print anchor ("code/fulldiff/{$project->id}/{$xpar}{$revreq}", $fulldiff_anchor_text);
	}
	else
	{
		print anchor ("code/diff/{$project->id}/{$xpar}{$revreq}", $diff_anchor_text);
	}

	print ' | ';

	if ($revision1 > 0)
	{
		if ($xpar == '') $revtrailer = $revreqroot;
		else $revtrailer = "/{$xpar}{$revreq}";
		print anchor ("code/history/{$project->id}{$revtrailer}", $history_anchor_text);
	}
	else
	{
		print anchor ("code/history/{$project->id}/{$xpar}", $history_anchor_text);
	}
?>
</div> <!-- code_diff_mainarea_menu -->

<?php 
	$fileext = substr(strrchr($file['name'], '.'), 1);
	if ($fileext == "") $fileext = "html"
?>

<div class="result" id="code_diff_mainarea_result">
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

/*
function format_diff ($a, $b, $css_class)
{
	if ($b == '') return htmlspecialchars($a);

	// TODO: word by word comparison to be less position dependent 
	$cc = '';
	$diffstart = -1;
	$alen = strlen($a);
	$blen = strlen($b);

	for ($i = 0; $i < $alen && $i < $blen; $i++)
	{
		if ($a[$i] == $b[$i])
		{
			if ($diffstart >= 0)
			{
				$cc .= sprintf ('<span class="%s">', $css_class);
				$cc .= htmlspecialchars(substr($a, $diffstart, $i - $diffstart));
				$cc .= '</span>';
				$diffstart = -1;
			}
			$cc .= htmlspecialchars($a[$i]);
		}
		else
		{
			if ($diffstart < 0) $diffstart = $i;
		}
	}

	if ($diffstart >= 0)
	{
		$cc .= sprintf ('<span class="%s">', $css_class);
		$cc .= htmlspecialchars(substr($a, $diffstart, $alen - $diffstart));
		$cc .= '</span>';
	}	
	else
	{
		if ($alen > $blen)
		{
			$cc .= sprintf ('<span class="%s">', $css_class);
			$cc .= htmlspecialchars(substr ($a, $blen, $alen - $blen));
			$cc .= '</span>';
		}
	}

	return $cc;
}
*/

//if (!$fullview)
if (FALSE) // don't want to delete code for the original diff view. 
{
	print '<table id="code_diff_mainarea_result_table">';
	/*
	print '<pre>';
	print_r ($file['content']);
	print '</pre>';
	*/

	print '<tr class="heading">';
	print '<th>';
	print ' ';

	$currev = $file['created_rev'];
	$prevrev = $file['against']['prev_rev'];
	$prevanc = "code/diff/{$project->id}/{$xpar}/{$currev}/{$prevrev}";
	print anchor ($prevanc, '<i class="fa fa-arrow-circle-left"></i>');
	print ' ';

	print $this->lang->line('Revision');
	print ' ';
	print $file['against']['created_rev'];

	$currev = $file['created_rev'];
	$nextrev = $file['against']['next_rev'];
	$nextanc = "code/diff/{$project->id}/{$xpar}/{$currev}/{$nextrev}";
	print ' ';
	print anchor ($nextanc, '<i class="fa fa-arrow-circle-right"></i>');

	print '</th>';

	print '<th>';
	print ' ';

	$currev = $file['against']['created_rev'];
	$prevrev = $file['prev_rev'];
	$prevanc = "code/diff/{$project->id}/{$xpar}/{$prevrev}/{$currev}";
	print anchor ($prevanc, '<i class="fa fa-arrow-circle-left"></i>');
	print ' ';

	print $this->lang->line('Revision');
	print ' ';
	print $file['created_rev'];

	$currev = $file['against']['created_rev'];
	$nextrev = $file['next_rev'];
	$nextanc = "code/diff/{$project->id}/{$xpar}/{$nextrev}/{$currev}";
	print ' ';
	print anchor ($nextanc, '<i class="fa fa-arrow-circle-right"></i>');

	print '</th>';
	print '</tr>';

	if ($headpath != $file['fullpath'] ||
	    $headpath != $file['against']['fullpath'])
	{
		print '<tr>';

		print '<th>';
		print anchor (
			"code/file/{$project->id}/{$xpar}/{$file['against']['created_rev']}",
			htmlspecialchars ($file['against']['fullpath']));
		print '</th>';

		print '<th>';
		print anchor (
			"code/file/{$project->id}/{$xpar}/{$file['created_rev']}",
			htmlspecialchars ($file['fullpath']));
		print '</th>';

		print '</tr>';
	}

	if (empty($file['content']))
	{
		print '<tr>';
		print '<td colspan="2">';
		print htmlspecialchars ($this->lang->line('MSG_NO_DIFF'));
		print '</td>';
		print '</tr>';
	}
	else
	{
		foreach ($file['content'] as $x)
		{
			print '<tr class="diff">';

			if (array_key_exists('rev1line', $x)) 
			{
				$diffclass = array_key_exists('rev1diffclass', $x)? $x['rev1diffclass']: 'diff';
				print "<td class='{$diffclass}'>";
				print "<pre class='prettyprint lang-{$fileext}'>";
				if ($x['rev1line'] == '') print '&nbsp;';
				else print htmlspecialchars($x['rev1line']);
				print '</pre>';
				print '</td>';
			}
			else
			{
				print '<td class="diffrow">';
				print $x['rev1lineno'];
				print '</td>';
			}

			if (array_key_exists('rev2line', $x)) 
			{
				$diffclass = array_key_exists('rev2diffclass', $x)? $x['rev2diffclass']: 'diff';
				print "<td class='{$diffclass}'>";
				print "<pre class='prettyprint lang-{$fileext}'>";
				if ($x['rev2line'] == '') print '&nbsp;';
				else print htmlspecialchars($x['rev2line']);
				print '</pre>';
				print '</td>';
			}
			else
			{
				print '<td class="diffrow">';
				print $x['rev2lineno'];
				print '</td>';
			}
	
			print '</tr>';
		}
	}

	print '</table>';
}
else
{
	$http_user_agent = $_SERVER['HTTP_USER_AGENT']; 
	$is_msie = (stristr($http_user_agent, 'MSIE') !== FALSE && 
	            stristr($http_user_agent, 'Opera') === FALSE);
	if (!$is_msie) $is_msie = (preg_match ("/^Mozilla.+\(Windows.+\) like Gecko$/", $http_user_agent) !== FALSE);

	$diff_view = $fullview? 'fulldiff': 'diff';

	print '<div style="width: 100%; overflow: hidden;" id="code_diff_mainarea_result_fullview">';

	//
	// SHOW THE OLD FILE
	//
	print ("<div style='float:left; width: 50%; margin: 0; padding: 0;'>");

	print '<div class="navigator">';

	$currev = $file['created_rev'];
	$prevrev = $file['against']['prev_rev'];
	$prevanc = "code/{$diff_view}/{$project->id}/{$xpar}/{$currev}/{$prevrev}";

	print anchor ($prevanc, '<i class="fa fa-arrow-circle-left"></i>');
	print ' ';

	// show the history details of the previous revision at the root directory
	$revanc = "code/revision/{$project->id}/!/{$file['against']['created_rev']}";
	print anchor ($revanc, ($this->lang->line('Revision') . ' ' . $file['against']['created_rev']));

	$currev = $file['created_rev'];
	$nextrev = $file['against']['next_rev'];
	$nextanc = "code/{$diff_view}/{$project->id}/{$xpar}/{$currev}/{$nextrev}";
	print ' ';
	print anchor ($nextanc, '<i class="fa fa-arrow-circle-right"></i>');
	print "</div>"; // navigator

	//print "<pre class='prettyprint lang-{$fileext}' style='width: 100%;' id='code_diff_mainarea_result_fulldiffold'>";
	print '<pre style="width: 100%;" id="code_diff_mainarea_result_fulldiffold" class="line-numbered">';

	print '<span class="line-number-block">';
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
			if ($actual_line_no > 1) print "<span class='line-number-empty'>&nbsp;</span>";
			$actual_line_no = $x['rev1lineno'];
		}
	}
	print '</span>';

	print '<code class="line-numbered-code prettyprint lang-{$fileext}" id="old-code">';
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
			if ($actual_line_no > 1) print "<span class='line-numbered-code-line-empty'>&nbsp;</span>\n"; // \n is required here unlike in the line-number-block
			$actual_line_no = $x['rev1lineno'];
		}
	}
	print '</code>';
	print '<span class="line-number-clear"></span>';
	print '</pre>';

	print '</div>';

	//
	// SHOW THE NEW FILE
	//
	print ("<div style='float:left; width: 50%; margin: 0; padding: 0;'>");

	print '<div class="navigator">';

	$currev = $file['against']['created_rev'];
	$prevrev = $file['prev_rev'];
	$prevanc = "code/{$diff_view}/{$project->id}/{$xpar}/{$prevrev}/{$currev}";
	print anchor ($prevanc, '<i class="fa fa-arrow-circle-left"></i>');
	print ' ';

	// show the history details of the current revision at the root directory
	$revanc = "code/revision/{$project->id}/!/{$file['created_rev']}";
	print anchor ($revanc, ($this->lang->line('Revision') . ' ' . $file['created_rev']));

	$currev = $file['against']['created_rev'];
	$nextrev = $file['next_rev'];
	$nextanc = "code/{$diff_view}/{$project->id}/{$xpar}/{$nextrev}/{$currev}";
	print ' ';
	print anchor ($nextanc, '<i class="fa fa-arrow-circle-right"></i>');
	print "</div>"; // navigator

	//print "<pre class='prettyprint lang-{$fileext}' style='width: 100%;' id='code_diff_mainarea_result_fulldiffnew'>";
	print '<pre style="width: 100%;" id="code_diff_mainarea_result_fulldiffnew" class="line-numbered">';

	print '<span class="line-number-block">';
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
			if ($actual_line_no > 1) print "<span class='line-number-empty'>&nbsp;</span>";
			$actual_line_no = $x['rev2lineno'];
		}
	}
	print '</span>';
	
	print '<code class="line-numbered-code prettyprint lang-{$fileext}" id="new-code" class="line-numbered-code">';
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
			if ($actual_line_no > 1) print "<span class='line-numbered-code-line-empty'>&nbsp;</span>\n"; // \n is required here unlike in the line number block
			$actual_line_no = $x['rev2lineno'];
		}
	}


	print '</code>';
	print '<span class="line-number-clear"></span>';
	print '</pre>';

	print '</div>';


	print '</div>';
}
?>

</div>

</div> <!-- code_diff_mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- code_diff_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>

</html>

