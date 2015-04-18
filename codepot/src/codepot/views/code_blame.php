<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/code.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/prettify/prettify.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-ada.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-basic.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-css.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lisp.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lua.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-pascal.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-sql.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-vb.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />


<script type="text/javascript">
$(function () {
	<?php
	if ($login['settings'] != NULL && $login['settings']->code_hide_metadata == 'Y')
		print '$("#code_blame_mainarea_result_info").hide();';
	?>

	if ($("#code_blame_mainarea_result_info").is(":visible"))
		btn_label = "<?php print $this->lang->line('Hide metadata')?>";
	else
		btn_label = "<?php print $this->lang->line('Show metadata')?>";
	
	btn = $("#code_blame_mainarea_metadata_button").button({"label": btn_label}).click (function () {
		
		if ($("#code_blame_mainarea_result_info").is(":visible"))
		{
			$("#code_blame_mainarea_result_info").hide("blind",{},200);
			$("#code_blame_mainarea_metadata_button").button(
				"option", "label", "<?php print $this->lang->line('Show metadata')?>");
		}
		else
		{
			$("#code_blame_mainarea_result_info").show("blind",{},200);
			$("#code_blame_mainarea_metadata_button").button(
				"option", "label", "<?php print $this->lang->line('Hide metadata')?>");
		}
	});

	prettyPrint ();
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

<div class="content" id="code_blame_content">

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

<div class="mainarea" id="code_blame_mainarea">

<div class="title" id="code_blame_mainarea_title">
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
</div> <!-- code_blame_mainarea_title -->

<div class="menu" id="code_blame_mainarea_menu">
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

if ($revision > 0)
{
	if ($xpar == '') $revtrailer = $revreqroot;
	else $revtrailer = "/{$xpar}{$revreq}";
	print anchor ("code/history/{$project->id}{$revtrailer}", $this->lang->line('History'));
}
else
{
	print anchor ("code/history/{$project->id}/{$xpar}", $this->lang->line('History'));
}

print ' | ';
print anchor ("code/fetch/{$project->id}/${xpar}{$revreq}", $this->lang->line('Download'));

?>
</div> <!-- code_blame_mainarea_menu -->

<div class="infostrip" id="code_blame_mainarea_infostrip">
	<?php 
		print anchor ("code/blame/{$project->id}/${xpar}/{$file['prev_rev']}", '<<');
		print ' ';

		// anchor to the revision history at the root directory
		print anchor (
			"code/revision/{$project->id}/!/{$file['created_rev']}",
			sprintf("%s %s", $this->lang->line('Revision'), $file['created_rev'])
		);

                if (!empty($file['created_tag']))
                {
			print ' ';
			print ('<span class="left_arrow_indicator">');
			print htmlspecialchars($file['created_tag']);
			print ('</span>');
                }
		print ' ';
		print anchor ("code/blame/{$project->id}/${xpar}/{$file['next_rev']}", '>>');

		print ' | ';
		printf ('%s: %s', $this->lang->line('Size'), $file['size']);
	?>
	<a id="code_blame_mainarea_metadata_button" href='#'><?php print $this->lang->line('Metadata')?></a>
</div>

<div class="result" id="code_blame_mainarea_result">

<?php 
$fileext = substr(strrchr($file['name'], '.'), 1);
if ($fileext == 'adb' || $fileext == 'ads') $fileext = 'ada';
else if ($fileext == 'pas') $fileext = 'pascal';
else if ($fileext == 'bas') $fileext = 'basic';

$prettyprint_lang = ($fileext != '')?  "lang-$fileext": '';

$prettyprint_linenums = 'linenums';
if ($login['settings'] != NULL &&
    $login['settings']->code_hide_line_num == 'Y') $prettyprint_linenums = '';
?>

<pre class="prettyprint <?php print $prettyprint_linenums?> <?php print $prettyprint_lang?>" id="code_blame_mainarea_result_pre">
<?php

	$content = $file['content'];
	$len = count($content);
	$rev = '';
	$author = '';

	for ($i = 0; $i < $len; $i++)
	{
		$line = $content[$i];
	
		if ($line['author'] != $author || $line['rev'] != $rev) 
		{
			$author = $line['author'];
			$author_padded = str_pad ($author, 9, ' ', STR_PAD_RIGHT);
			$author_padded = substr($author_padded, 0, 9);
		}
		else
		{
			$author_padded = str_pad (' ', 9, ' ', STR_PAD_RIGHT);
		}

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
	
		print "<span class='nocode'>{$rev_padded}</span> ";
		print "<span class='nocode' title='{$author}'>{$author_padded}</span> ";

		print htmlspecialchars ($line['line']);
		print "\n";
	}
?>
</pre>

<div id="code_blame_mainarea_result_info" class="infobox">
<div class="title"><?php print  $this->lang->line('CODE_COMMIT') ?></div>
<ul>
<li><?php printf ($this->lang->line('CODE_MSG_COMMITTED_BY_ON'), $file['last_author'], $file['time']); ?></li>
</ul>


<div class="title"><?php print  $this->lang->line('Message') ?></div>
<pre id="code_blame_mainarea_result_info_logmsg">
<?php print  $file['logmsg'] ?>
</pre>

<?php
if (array_key_exists('properties', $file) && count($file['properties']) > 0)
{
	print '<div class="title">';
	print $this->lang->line('CODE_PROPERTIES');
	print '</div>';

	print '<ul id="code_blame_mainarea_result_info_property_list">';
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
</div> <!-- code_blame_mainarea_result_info -->

</div> <!-- code_blame_mainarea_result -->

</div> <!-- code_blame_mainarea -->

<!---------------------------------------------------------------------------->


<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  code_blame_content -->

</body>

</html>

