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

<div class="content" id="project_source_file_content">

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

<div class="mainarea" id="project_source_file_mainarea">

<div class="title" id="project_source_file_mainarea_title">
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
        "/source/file/{$project->id}/{$par}{$revreq}",
        htmlspecialchars($file['name']));
?>
</div> <!-- project_source_file_mainarea_title -->

<div class="menu" id="project_source_file_mainarea_menu">
<?php
$par = $this->converter->AsciiToHex ("{$folder}/{$file['name']}");

if ($file['created_rev'] != $file['head_rev']) 
{
	print anchor ("source/file/{$project->id}/${par}", $this->lang->line('Head revision'));
	print ' | ';
}

print anchor ("source/blame/{$project->id}/${par}{$revreq}", $this->lang->line('Blame'));
print ' | ';
print anchor ("source/diff/{$project->id}/{$par}{$revreq}", $this->lang->line('Difference'));
print ' | ';
print anchor ("source/history/file/{$project->id}/{$par}", $this->lang->line('History'));
?>
</div> <!-- project_source_file_mainarea_menu -->

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
	// print htmlspecialchars($file['content']);

	$pos = 0; $lineno = 0; $len = strlen($file['content']);
	while ($pos < $len)
	{
		$lineno_padded = str_pad (++$lineno, 6, ' ', STR_PAD_LEFT);
		$npos = strpos ($file['content'], "\n", $pos);
		if ($npos === FALSE)
		{
			print '<span class="nocode">' . $lineno_padded . ' </span> ';
			print substr ($file['content'], $pos, $len - $pos);
			print "\n";
			break;
		}

		print '<span class="nocode">' . $lineno_padded . ' </span> ';
		print htmlspecialchars (substr ($file['content'], $pos, $npos - $pos));
		print "\n";

		$pos = $npos + 1;
	}
?>
</pre>

</div> <!-- project_source_file_mainarea -->


<!---------------------------------------------------------------------------->


<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_source_file_content -->

</body>

</html>

