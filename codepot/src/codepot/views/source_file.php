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

<div class="sidebar" id="project_source_file_sidebar">
<div class="box">
<div class="boxtitle"><?=$this->lang->line('File')?></div>
<ul>
<li><?=$this->lang->line('Revision')?>: <?=$file['created_rev']?></li>
<li><?=$this->lang->line('Author')?>: <?=htmlspecialchars($file['last_author'])?></li>
<li><?=$this->lang->line('Size')?>: <?=$file['size']?></li>
<li><?=$this->lang->line('Last updated on')?>: <?=$file['time']?></li>
</ul>
</div>

</div> <!-- project_source_file_sidebar -->


<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_source_file_mainarea">

<div class="title" id="project_source_file_title">
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
$xpar = '/source/file/' . $project->id . '/' . $par;
if ($revision != SVN_REVISION_HEAD) $xpar .= '/' . $revision;
print anchor ($xpar, htmlspecialchars($file['name']));
?>
</div> <!-- project_source_file_mainarea_title -->


<div class="menu" id="project_source_file_mainarea_menu">
<?php
$par = $folder . '/' . $file['name'];
$par = $this->converter->AsciiTohex ($par);
$xpar = 'source/blame/' . $project->id . '/' . $par;
if ($revision != SVN_REVISION_HEAD) $xpar .= '/' . $revision;
print anchor ($xpar, $this->lang->line('Blame'));
print ' | ';
print anchor ('source/history/file/' . $project->id . '/' . $par, $this->lang->line('History'));
?>
</div> <!-- project_source_file_mainarea_menu -->

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

