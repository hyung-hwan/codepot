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

<div class="mainarea" id="project_source_blame_mainarea">

<div class="title" id="project_source_blame_mainarea_title">
<?php
if ($revision <= 0)
{
	$revreq = '';
	$revreqroot = '';
}
else
{
	//$revreq = ($file['created_rev'] == $file['head_rev'])? '': "/{$file['created_rev']}";
	//$revreqroot = ($revreq == '')? '': ('/' . $this->converter->AsciiToHex ('.') . $revreq);
	$revreq = "/{$revision}";
	$revreqroot = '/' . $this->converter->AsciiToHex ('.') . $revreq;
}

print anchor (
	"/source/folder/{$project->id}{$revreqroot}",
	htmlspecialchars($project->name));

if ($folder != '')
{
	$exps = explode ('/', $folder);
	$expsize = count($exps);
	$par = '';
	for ($i = 1; $i < $expsize; $i++)
	{
		print '/';

		$par .= '/' . $exps[$i];
		$hexpar = $this->converter->AsciiToHex ($par);
		print anchor (
			"source/folder/{$project->id}/{$hexpar}{$revreq}",
			htmlspecialchars($exps[$i]));
	}
}

print '/';

$par = $this->converter->AsciiTohex ("{$folder}/{$file['name']}");
print anchor (
	"/source/blame/{$project->id}/{$par}{$revreq}",
	htmlspecialchars($file['name']));
?>
</div> <!-- project_source_blame_mainarea_title -->

<div class="menu" id="project_source_blame_mainarea_menu">
<?php
$par = $this->converter->AsciiToHex ("{$folder}/{$file['name']}");


if ($file['created_rev'] != $file['head_rev'])
{
	print anchor ("source/file/{$project->id}/${par}", $this->lang->line('Head revision'));
	print ' | ';
}

print anchor ("source/file/{$project->id}/${par}{$revreq}", $this->lang->line('Details'));
print ' | ';
print anchor ("source/diff/{$project->id}/{$par}{$revreq}", $this->lang->line('Difference'));
print ' | ';
print anchor ("source/history/file/{$project->id}/{$par}", $this->lang->line('History'));

?>
</div> <!-- project_source_blame_mainarea_menu -->

<div class="infostrip">
<?=anchor ("source/file/{$project->id}/${par}/{$file['prev_rev']}", '<<')?> 
<?=$this->lang->line('Revision')?>: <?=$file['created_rev']?> 
<?=anchor ("source/file/{$project->id}/${par}/{$file['next_rev']}", '>>')?> |
<?=$this->lang->line('Author')?>: <?=htmlspecialchars($file['last_author'])?> |
<?=$this->lang->line('Size')?>: <?=$file['size']?> |
<?=$this->lang->line('Last updated on')?>: <?=$file['time']?>
</div>

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

