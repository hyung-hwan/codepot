<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/code.css')?>" />
<script type="text/javascript" src="<?=base_url_make('/js/prettify/prettify.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-css.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-lisp.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-lua.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-sql.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-vb.js')?>"></script>

<title><?=htmlspecialchars($project->name)?></title>
</head>

<body onload="prettyPrint()">

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
	$xpar = $this->converter->AsciiTohex ($headpath);
	print anchor (
		"code/file/{$project->id}/{$xpar}{$revreq}",
		$this->lang->line('Details'));
	print ' | ';
	print anchor (
		"code/blame/{$project->id}/{$xpar}{$revreq}",
		$this->lang->line('Blame'));
	print ' | ';

	if (!$fullview)
	{
		print anchor (
			"code/fulldiff/{$project->id}/{$xpar}{$revreq}",
			$this->lang->line('Full Difference'));
	}
	else
	{
		print anchor (
			"code/diff/{$project->id}/{$xpar}{$revreq}",
			$this->lang->line('Difference'));
	}

	print ' | ';


	if ($revision1 > 0)
	{
		if ($xpar == '') $revtrailer = $revreqroot;
		else $revtrailer = "/{$xpar}{$revreq}";
		print anchor (
			"code/history/{$project->id}{$revtrailer}",
			$this->lang->line('History'));
	}
	else
	{
		print anchor (
			"code/history/{$project->id}/{$xpar}",
			$this->lang->line('History'));
	}
?>
</div> <!-- code_diff_mainarea_menu -->

<?php 
	$fileext = substr(strrchr($file['name'], '.'), 1);
	if ($fileext == "") $fileext = "html"
?>

<div id="code_diff_mainarea_result">
<?php
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

	$currev = $file['created_rev'];
	$prevrev = $file['against']['prev_rev'];
	$prevanc = "code/diff/{$project->id}/{$xpar}/{$currev}/{$prevrev}";
	print anchor ($prevanc, '<<');
	print '&nbsp;&nbsp;&nbsp;';

	print $this->lang->line('Revision');
	print ' ';
	print $file['against']['created_rev'];

	$currev = $file['created_rev'];
	$nextrev = $file['against']['next_rev'];
	$nextanc = "code/diff/{$project->id}/{$xpar}/{$currev}/{$nextrev}";
	print '&nbsp;&nbsp;&nbsp;';
	print anchor ($nextanc, '>>');

	print '</th>';

	print '<th>';

	$currev = $file['against']['created_rev'];
	$prevrev = $file['prev_rev'];
	$prevanc = "code/diff/{$project->id}/{$xpar}/{$prevrev}/{$currev}";
	print anchor ($prevanc, '<<');
	print '&nbsp;&nbsp;&nbsp;';

	print $this->lang->line('Revision');
	print ' ';
	print $file['created_rev'];

	$currev = $file['against']['created_rev'];
	$nextrev = $file['next_rev'];
	$nextanc = "code/diff/{$project->id}/{$xpar}/{$nextrev}/{$currev}";
	print '&nbsp;&nbsp;&nbsp;';
	print anchor ($nextanc, '>>');

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
	$is_msie = (stristr($http_user_agent, 'MSIE') != FALSE && 
	            stristr($http_user_agent, 'Opera') == FALSE);
	if (!$is_msie)
	{
		$is_msie = (preg_match ("/^Mozilla.+\(Windows.+\) like Gecko$/", $http_user_agent) != FALSE);
	}

	print '<div style="width: 100%; overflow: hidden;" id="code_diff_mainarea_result_fullview">';

	if (empty($file['content']))
	{
		print htmlspecialchars ($this->lang->line('MSG_NO_DIFF'));
	}
	else
	{
		print ("<div style='float:left; width: 49%;'>");

		print "<div class='navigator'>";
		$currev = $file['created_rev'];
		$prevrev = $file['against']['prev_rev'];
		$prevanc = "code/fulldiff/{$project->id}/{$xpar}/{$currev}/{$prevrev}";
		print anchor ($prevanc, '<<');
		print '&nbsp;&nbsp;&nbsp;';
	
		print $this->lang->line('Revision');
		print ' ';
		print $file['against']['created_rev'];
	
		$currev = $file['created_rev'];
		$nextrev = $file['against']['next_rev'];
		$nextanc = "code/fulldiff/{$project->id}/{$xpar}/{$currev}/{$nextrev}";
		print '&nbsp;&nbsp;&nbsp;';
		print anchor ($nextanc, '>>');
		print "</div>";

		print "<pre class='prettyprint lang-{$fileext}' style='width: 100%' id='code_diff_mainarea_result_fulldiffold'>";

		foreach ($file['content'] as $x)
		{
			if (array_key_exists('rev2line', $x)) 
			{
				$diffclass = array_key_exists('rev1diffclass', $x)? $x['rev1diffclass']: 'diff';
				print "<span class='{$diffclass}'>";

				if ($diffclass == 'diffchanged')
					$xline = format_diff ($x['rev1line'], $x['rev2line'], 'diffchangedold');
				else 
					$xline = htmlspecialchars($x['rev1line']);

				if ($is_msie && $xline == '') $xline = '&nbsp;';
				print $xline;
				print "</span>\n";
			}
			else
			{
				print "<span class='diffrow'> ";
				print $x['rev1lineno'];
				print " </span>\n";
			}
		}
		printf ("</div>");
		print '</pre>';


		print ("<div style='float:left; width: 49%;'>");

		print "<div class='navigator'>";
		$currev = $file['against']['created_rev'];
		$prevrev = $file['prev_rev'];
		$prevanc = "code/fulldiff/{$project->id}/{$xpar}/{$prevrev}/{$currev}";
		print anchor ($prevanc, '<<');
		print '&nbsp;&nbsp;&nbsp;';

		print $this->lang->line('Revision');
		print ' ';
		print $file['created_rev'];

		$currev = $file['against']['created_rev'];
		$nextrev = $file['next_rev'];
		$nextanc = "code/fulldiff/{$project->id}/{$xpar}/{$nextrev}/{$currev}";
		print '&nbsp;&nbsp;&nbsp;';
		print anchor ($nextanc, '>>');
		print "</div>";

		print "<pre class='prettyprint lang-{$fileext}' style='width: 100%' id='code_diff_mainarea_result_fulldiffnew'>";
		foreach ($file['content'] as $x)
		{
			if (array_key_exists('rev2line', $x)) 
			{
				$diffclass = array_key_exists('rev2diffclass', $x)? $x['rev2diffclass']: 'diff';

				print "<span class='{$diffclass}'>";

				if ($diffclass == 'diffchanged')
					$xline = format_diff ($x['rev2line'], $x['rev1line'], 'diffchangednew');
				else 
					$xline = htmlspecialchars($x['rev2line']);

				if ($is_msie && $xline == '') $xline = '&nbsp;';
				print $xline;
				print "</span>\n";
			}
			else
			{
				print "<span class='diffrow'> ";
				print $x['rev2lineno'];
				print " </span>\n";
			}
		}

		print '</pre>';
		printf ("</div>");
	}
	print '</div>';
}
?>

</div>

</div> <!-- code_diff_mainarea -->


<!---------------------------------------------------------------------------->


<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!-- code_diff_content -->

</body>

</html>

