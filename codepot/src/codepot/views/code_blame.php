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

<div class="content" id="project_code_blame_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,
		'site' => NULL,
		'pageid' => 'code',
		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_code_blame_mainarea">

<div class="title" id="project_code_blame_mainarea_title">
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
</div> <!-- project_code_blame_mainarea_title -->

<div class="menu" id="project_code_blame_mainarea_menu">
<?php
$xpar = $this->converter->AsciiToHex ($headpath);

if ($file['created_rev'] != $file['head_rev'])
{
	print anchor ("code/blame/{$project->id}/${xpar}", $this->lang->line('Head revision'));
	print ' | ';
}

print anchor ("code/file/{$project->id}/${xpar}{$revreq}", $this->lang->line('Details'));
print ' | ';
print anchor ("code/diff/{$project->id}/{$xpar}{$revreq}", $this->lang->line('Difference'));
print ' | ';
print anchor ("code/history/{$project->id}/{$xpar}", $this->lang->line('History'));

?>
</div> <!-- project_code_blame_mainarea_menu -->

<div class="infostrip" id="project_code_blame_mainarea_infostrip">
	<?=anchor ("code/file/{$project->id}/${xpar}/{$file['prev_rev']}", '<<')?> 
	<?=$this->lang->line('Revision')?>: <?=$file['created_rev']?> 
	<?=anchor ("code/file/{$project->id}/${xpar}/{$file['next_rev']}", '>>')?> |
	<?=$this->lang->line('Author')?>: <?=htmlspecialchars($file['last_author'])?> |
	<?=$this->lang->line('Size')?>: <?=$file['size']?> |
	<?=$this->lang->line('Last updated on')?>: <?=$file['time']?>
</div>

<div id="project_code_blame_mainarea_result">

<?php 
	$fileext = substr(strrchr($file['name'], '.'), 1);
	if ($fileext == "") $fileext = "html"
?>

<pre class="prettyprint lang-<?=$fileext?>" id="project_code_blame_mainarea_result_pre">
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
	
			$xpar = $this->converter->AsciiTohex ($headpath);
			$rev_padded = anchor ("code/blame/{$project->id}/{$xpar}/{$rev}", $rev_padded);
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
	
		print "<span class='nocode'>{$rev_padded}</span>";
		print "<span class='nocode' title='{$author}'>{$author_padded}</span>";
		print "<span class='nocode'>{$lineno_padded}</span>";
		print htmlspecialchars ($line['line']);
		print "\n";
	}
?>
</pre>

<div id="project_code_blame_mainarea_result_info">
<script language='javascript'>
function toggle_logmsg()
{
	var x = document.getElementById ('project_code_blame_mainarea_result_info_logmsg');	
	if (x) x.style.visibility = (x.style.visibility == 'visible')? 'hidden': 'visible';
	return false;
}
</script>

<div class="title">
<a href='#' onClick='toggle_logmsg()'><?= $this->lang->line('Message') ?></a>
</div>
<pre id="project_code_blame_mainarea_result_info_logmsg" style="visibility: visible">
<?= $file['logmsg'] ?>
</pre>
</div> <!-- project_code_blame_mainarea_result_info -->

</div> <!-- project_code_blame_mainarea_result -->

</div> <!-- project_code_blame_mainarea -->

<!---------------------------------------------------------------------------->


<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_code_blame_content -->

</body>

</html>

