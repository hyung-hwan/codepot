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

<div class="content" id="project_code_file_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'site' => NULL,
		'pageid' => 'code',
		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_code_file_mainarea">

<div class="title" id="project_code_file_mainarea_title">
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
</div> <!-- project_code_file_mainarea_title -->

<div class="menu" id="project_code_file_mainarea_menu">
<?php
	$xpar = $this->converter->AsciiToHex ($headpath);

	if ($file['created_rev'] != $file['head_rev']) 
	{
		print anchor (
			"code/file/{$project->id}/${xpar}",
			$this->lang->line('Head revision'));
		print ' | ';
	}

	print anchor (
		"code/blame/{$project->id}/${xpar}{$revreq}",
		$this->lang->line('Blame'));
	print ' | ';
	print anchor (
		"code/diff/{$project->id}/{$xpar}{$revreq}",
		$this->lang->line('Difference'));
	print ' | ';
	print anchor (
		"code/history/{$project->id}/{$xpar}", 
		$this->lang->line('History'));
?>
</div> <!-- project_code_file_mainarea_menu -->

<div class="infostrip" id="project_code_file_mainarea_infostrip">
	<?=anchor ("code/file/{$project->id}/${xpar}/{$file['prev_rev']}", '<<')?> 
	<?=$this->lang->line('Revision')?>: <?=$file['created_rev']?> 
	<?=anchor ("code/file/{$project->id}/${xpar}/{$file['next_rev']}", '>>')?> |
	<?=$this->lang->line('Author')?>: <?=htmlspecialchars($file['last_author'])?> |
	<?=$this->lang->line('Size')?>: <?=$file['size']?> |
	<?=$this->lang->line('Last updated on')?>: <?=$file['time']?> 
</div>

<div id="project_code_file_mainarea_result">

<?php 
$fileext = substr(strrchr($file['name'], '.'), 1);
if ($fileext == '') $fileext = "html"
?>

<pre class="prettyprint lang-<?=$fileext?>" id="project_code_file_mainarea_result_pre">
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


<div id="project_code_file_mainarea_result_info">
<script language='javascript'>
function toggle_logmsg()
{
	var x = document.getElementById ('project_code_file_mainarea_result_info_logmsg');	
	if (x) x.style.visibility = (x.style.visibility == 'visible')? 'hidden': 'visible';
	return false;
}
</script>

<div class="title">
<a href='#' onClick='toggle_logmsg()'><?= $this->lang->line('Message') ?></a>
</div>
<pre id="project_code_file_mainarea_result_info_logmsg" style="visibility: visible">
<?= $file['logmsg'] ?>
</pre>
</div> <!-- project_code_file_mainarea_result_info -->

</div> <!-- project_code_file_mainarea_result -->

</div> <!-- project_code_file_mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_code_file_content -->

</body>

</html>

