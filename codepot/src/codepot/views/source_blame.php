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

<div class="content" id="project_source_blame_content">

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

<div class="sidebar" id="project_source_blame_sidebar">

<div class="box">
<div class="boxtitle"><?=$this->lang->line('File')?></div>
<ul>
<li><?=$this->lang->line('Revision')?>: <?=$file['created_rev']?></li>
<li><?=$this->lang->line('Author')?>: <?=htmlspecialchars($file['last_author'])?></li>
<li><?=$this->lang->line('Size')?>: <?=$file['size']?></li>
<li><?=$this->lang->line('Last updated on')?>: <?=$file['time']?></li>
</ul>
</div>

</div> <!-- project_source_blame_sidebar -->


<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_source_blame_mainarea">

<div class="title" id="project_source_blame_mainarea_title">
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
		if ($revision != SVN_REVISION_HEAD) $xpar .= '/' . $revision;
		print anchor ($xpar, htmlspecialchars($exps[$i]));
	}
}
$par = $folder . '/' . $file['name'];
$par = $this->converter->AsciiTohex ($par);
print '/';
$xpar = '/source/blame/' . $project->id . '/' . $par;
if ($revision != SVN_REVISION_HEAD) $xpar .= '/' . $revision;
print anchor ($xpar, htmlspecialchars($file['name']));
?>
</div> <!-- project_source_blame_mainarea_title -->

<div class="menu" id="project_source_blame_mainarea_menu">
<?php
$par = $folder . '/' . $file['name'];
$par = $this->converter->AsciiTohex ($par);
$xpar = 'source/file/' . $project->id . '/' . $par;
if ($revision != SVN_REVISION_HEAD) $xpar .= '/' . $revision;
print anchor ($xpar, $this->lang->line('Details'));
print ' | ';
print anchor ('source/history/file/' . $project->id . '/' . $par, $this->lang->line('History'));
?>
</div> <!-- project_source_blame_mainarea_menu -->

<?php 
$fileext = substr(strrchr($file['name'], '.'), 1);
if ($fileext == "") $fileext = "html"
?>
<pre class="prettyprint lang-<?=$fileext?>">
<?php

$content = $file['content'];
$len = count($content);
$rev = '';
$author = '';

for ($i = 0; $i < $len; $i++)
{
	$line = $content[$i];
	$lineno_padded = str_pad ($line['line_no'], 6, ' ', STR_PAD_LEFT);

	if ($line['rev'] != $rev) 
	{
		$rev = $line['rev'];
		$rev_padded = str_pad ($rev, 6, ' ', STR_PAD_LEFT);

		$par = $folder . '/' . $file['name'];
		$par = $this->converter->AsciiTohex ($par);
		$rev_padded = anchor ('/source/blame/' . $project->id . '/' . $par . '/' . $rev, $rev_padded);
	}
	else
	{
		$rev_padded = str_pad (' ', 6, ' ', STR_PAD_LEFT);
	}

	if ($line['author'] != $author) 
	{
		$author = $line['author'];
		$author_padded = str_pad ($author, 8, ' ', STR_PAD_RIGHT);
		$author_padded = substr($author_padded, 0, 8);
	}
	else
	{
		$author_padded = str_pad (' ', 8, ' ', STR_PAD_RIGHT);
	}

	print '<span class="nocode">' . $rev_padded . ' </span> ';
	print '<span class="nocode">' . $author_padded . ' </span> ';
	print '<span class="nocode">' . $lineno_padded . ' </span> ';
	print htmlspecialchars ($line['line']);
	print "\n";
}
?>
</pre>

</div> <!-- project_source_blame_mainarea -->


<!---------------------------------------------------------------------------->


<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_source_blame_content -->

</body>

</html>

