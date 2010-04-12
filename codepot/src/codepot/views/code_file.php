<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/code.css" />
<script type="text/javascript" src="<?=base_url()?>/js/prettify/prettify.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/prettify/lang-css.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/prettify/lang-lisp.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/prettify/lang-lua.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/prettify/lang-sql.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/prettify/lang-vb.js"></script>

<script type="text/javascript" src="<?=base_url()?>/js/jquery.min.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/jquery-ui.css" />

<script type="text/javascript">
$(function () {
	if ($("#code_file_mainarea_result_info").is(":visible"))
		btn_label = "<?=$this->lang->line('CODE_HIDE_DETAILS')?>";
	else
		btn_label = "<?=$this->lang->line('CODE_SHOW_DETAILS')?>";
	
	btn = $("#code_file_mainarea_details_button").button({"label": btn_label}).click (function () {
		
		if ($("#code_file_mainarea_result_info").is(":visible"))
		{
			$("#code_file_mainarea_result_info").hide();
			$("#code_file_mainarea_details_button").button(
				"option", "label", "<?=$this->lang->line('CODE_SHOW_DETAILS')?>");
		}
		else
		{
			$("#code_file_mainarea_result_info").show();
			$("#code_file_mainarea_details_button").button(
				"option", "label", "<?=$this->lang->line('CODE_HIDE_DETAILS')?>");
		}
	});
});
</script>

<title><?=htmlspecialchars($project->name)?></title>
</head>

<body onload="prettyPrint()">

<div class="content" id="code_file_content">

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

<div class="mainarea" id="code_file_mainarea">

<div class="title" id="code_file_mainarea_title">
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
</div> <!-- code_file_mainarea_title -->

<div class="menu" id="code_file_mainarea_menu">
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
</div> <!-- code_file_mainarea_menu -->

<div class="infostrip" id="code_file_mainarea_infostrip">
	<?=anchor ("code/file/{$project->id}/${xpar}/{$file['prev_rev']}", '<<')?> 
	<?=$this->lang->line('Revision')?>: <?=$file['created_rev']?> 
	<?=anchor ("code/file/{$project->id}/${xpar}/{$file['next_rev']}", '>>')?> |
	<?=$this->lang->line('Size')?>: <?=$file['size']?> |
	<a id="code_file_mainarea_details_button" href='#'><?=$this->lang->line('Details')?></a>
</div>

<div id="code_file_mainarea_result">

<?php 
$fileext = substr(strrchr($file['name'], '.'), 1);
if ($fileext == '') $fileext = "html"
?>

<pre class="prettyprint lang-<?=$fileext?>" id="code_file_mainarea_result_pre">
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


<div id="code_file_mainarea_result_info">
<div class="title"><?= $this->lang->line('CODE_COMMIT') ?></div>
<?php printf ($this->lang->line('CODE_MSG_COMMITTED_BY_ON'), $file['last_author'], $file['time']); ?>

<div class="title"><?= $this->lang->line('Message') ?></div>
<pre id="code_file_mainarea_result_info_logmsg">
<?= $file['logmsg'] ?>
</pre>

<?php
if (array_key_exists('properties', $file) && count($file['properties']) > 0)
{
	print '<div class="title">';
	print $this->lang->line('CODE_PROPERTIES');
	print '</div>';

	print '<ul id="code_file_mainarea_result_info_property_list">';
	foreach ($file['properties'] as $pn => $pv)
	{
		print '<li>';
		print htmlspecialchars($pn);
		print ' - ';
		print htmlspecialchars($pv);
		print '</li>';
	}
	print '</ul>';
}
?>
</pre>
</div> <!-- code_file_mainarea_result_info -->

</div> <!-- code_file_mainarea_result -->

</div> <!-- code_file_mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  code_file_content -->

</body>

</html>

