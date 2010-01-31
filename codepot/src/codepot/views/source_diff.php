<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<script type="text/javascript" src="<?=base_url()?>/js/prettify/prettify.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/prettify/lang-css.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/prettify/lang-lisp.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/prettify/lang-lua.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/prettify/lang-sql.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/prettify/lang-vb.js"></script>
<title><?=htmlspecialchars($project->name)?></title>
</head>

<body onload="prettyPrint()">

<div class="content" id="project_source_diff_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'pageid' => 'source',
		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_source_diff_mainarea">

<div class="title" id="project_source_diff_mainarea_title">
<?php
$xpar = '/source/folder/' . $project->id;
print anchor ($xpar, htmlspecialchars($project->name));
if ($folder != '')
{
	$exps = explode ('/', $folder);
	$expsize = count($exps);
	$par = '';
	for ($i = 1; $i < $expsize; $i++)
	{
		$par .= '/' . $exps[$i];
		$hexpar = $this->converter->AsciiToHex ($par);
		print '/';
		$xpar = 'source/folder/' . $project->id . '/' . $hexpar;
		if ($revision1 != SVN_REVISION_HEAD) $xpar .= '/' . $revision1;
		print anchor ($xpar, htmlspecialchars($exps[$i]));
	}
}
$par = $folder . '/' . $file['name'];
$par = $this->converter->AsciiTohex ($par);
print '/';
$xpar = '/source/file/' . $project->id . '/' . $par;
if ($revision1 != SVN_REVISION_HEAD) $xpar .= '/' . $revision1;
print anchor ($xpar, htmlspecialchars($file['name']));
?>
</div> <!-- project_source_diff_mainarea_title -->


<div class="menu" id="project_source_diff_mainarea_menu">
<?php
$par = $folder . '/' . $file['name'];
$par = $this->converter->AsciiTohex ($par);

$xdetails = "source/file/{$project->id}/{$par}";
$xblame = "source/blame/{$project->id}/{$par}";
if ($revision1 != SVN_REVISION_HEAD) 
{
	$xdetails .= "/{$revision1}";
	$xblame .= "/{$revision1}";
}

print anchor ($xdetails, $this->lang->line('Details'));
print ' | ';
print anchor ($xblame, $this->lang->line('Blame'));
print ' | ';
print anchor ("source/history/file/{$project->id}/{$par}", $this->lang->line('History'));
?>
</div> <!-- project_source_diff_mainarea_menu -->

<div class="infostrip" id="project_source_diff_mainarea_infostrip">
<?=$this->lang->line('Revision')?>: <?=$file['created_rev']?> |
<?=$this->lang->line('Author')?>: <?=htmlspecialchars($file['last_author'])?> |
<?=$this->lang->line('Size')?>: <?=$file['size']?> |
<?=$this->lang->line('Last updated on')?>: <?=$file['time']?>
</div>


<?php 
$fileext = substr(strrchr($file['name'], '.'), 1);
if ($fileext == "") $fileext = "html"
?>

<div id="project_source_diff_mainarea_result">
<table id="project_source_diff_mainarea_result_table">
<?php

	/*
	print '<pre>';	
	print_r ($file['content']);
	print '</pre>';
	*/

	print '<tr class="heading">';
	print '<th>';

	$currev = $file['created_rev'];
	$prevrev = $file['against']['prev_rev'];
	$prevanc = "source/diff/{$project->id}/{$par}/{$currev}/{$prevrev}";
	print anchor ($prevanc, '<<');
	print '&nbsp;&nbsp;&nbsp;';

	print $this->lang->line('Revision');
	print ' ';
	print $file['against']['created_rev'];

	$currev = $file['created_rev'];
	$nextrev = $file['against']['next_rev'];
	$nextanc = "source/diff/{$project->id}/{$par}/{$currev}/{$nextrev}";
	print '&nbsp;&nbsp;&nbsp;';
	print anchor ($nextanc, '>>');

	print '</th>';

	print '<th>';

	$currev = $file['against']['created_rev'];
	$prevrev = $file['prev_rev'];
	$prevanc = "source/diff/{$project->id}/{$par}/{$prevrev}/{$currev}";
	print anchor ($prevanc, '<<');
	print '&nbsp;&nbsp;&nbsp;';

	print $this->lang->line('Revision');
	print ' ';
	print $file['created_rev'];

	$currev = $file['against']['created_rev'];
	$nextrev = $file['next_rev'];
	$nextanc = "source/diff/{$project->id}/{$par}/{$nextrev}/{$currev}";
	print '&nbsp;&nbsp;&nbsp;';
	print anchor ($nextanc, '>>');

	print '</th>';
	print '</tr>';

	if (empty($file['content']))
	{
		print '<tr>';
		print '<td colspan=2>';
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
				print htmlspecialchars($x['rev1line']);
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
				print htmlspecialchars($x['rev2line']);
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
?>
</table>
</div>

</div> <!-- project_source_diff_mainarea -->


<!---------------------------------------------------------------------------->


<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_source_diff_content -->

</body>

</html>

