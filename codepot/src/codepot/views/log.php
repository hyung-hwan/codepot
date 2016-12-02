<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/log.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<?php if ($login['sysadmin?'] && isset($site)): ?>

<script type="text/javascript">
$(function () {
	$("#purge_confirm").dialog({
		resizable: false,
		autoOpen: false,
		height:140,
		modal: true,
		buttons: {
			'<?php print $this->lang->line('OK')?>': function() {
				$(this).dialog('close');
				$('#purge_log').val ('yes');
				$('#purge_form').submit();
			},
			'<?php print $this->lang->line('Cancel')?>': function() {
				$(this).dialog('close');
				$('#purge_log').val ('no');
			}
		}
	});
        $("#log_mainarea_purge").button().click(
		function () {
			$('#purge_confirm').dialog('open');
		}
	);
});
</script>

<?php endif; ?>

<?php
	$caption = $this->lang->line('Home');
	if ($login['id'] != '') $caption .= "({$login['id']})";
?>

<title><?php print htmlspecialchars($caption)?></title>
</head>

<body>

<div class="content" id="log_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php

if (isset($project)) 
{ 
	$pagetype = 'project';
	$pageobjname = 'project';
	$pageid = ''; 
	$pageobj = $project; 
	$banner = NULL;
}
else if (isset($site)) 
{ 
	if ($login['sysadmin?'])
	{
		$pagetype = 'site';
		$pageobjname = 'site';
		$pageid = 'log';
		$pageobj = $site; 
		$banner = NULL;
	}
	else
	{
		$pagetype = '';
		$pageobjname = '';
		$pageid = '';
		$pageobj = NULL;
		$banner = $site->name;
	}
}
else if (isset($user)) 
{ 
	$pagetype = 'user';
	$pageobjname = 'user';
	$pageid = ''; 
	$pageobj = $user; 
	$banner = NULL;

	if ($user->id != $login['id']) $pagetype = 'user-other';
}
else 
{ 
	$pagetype = '';
	$pageobjname = '';
	$pageid = '';
	$pageobj = NULL; 
	$banner = NULL;
}

$this->load->view (
	'projectbar',
	array (
		'banner' => $banner,

		'page' => array (
			'type' => $pagetype,
			'id' => $pageid,
			$pageobjname => $pageobj
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="log_mainarea">

<div class="codepot-title-band" id="log_title_band">

	<div class="title">
	<?php 
		print anchor ("site/log", $this->lang->line ('Change log'));
		if ($pagetype == 'project' && $target_userid != '') printf ('(%s)', htmlspecialchars ($target_userid));
	?>
	</div>

	<div class="actions">
	<?php if ($login['sysadmin?'] && isset($site)): ?>
		<?php print form_open("site/log", 'id="purge_form"')?>
			<input type='hidden' name='purge_log' id='purge_log' value='' />
		<?php print form_close()?>

		<div id="purge_confirm" title="<?php print  $this->lang->line('Purge') ?>">
		<p>
			<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>
			Are you sure?
		</p>
		</div>

		<a id="log_mainarea_purge" href="#"><?php print  $this->lang->line('Purge') ?></a>
	<?php endif; ?>
	</div>

	<div style="clear: both;"></div>
</div>



<div id="log_mainarea_result" class="codepot-relative-container-view">

<table id="log_mainarea_result_table" class="codepot-full-width-table codepot-spacious-table">
<?php 
	$curdate = '';
	$xdot = $this->converter->AsciiToHex ('.');

	$rowclasses = array ('odd', 'even');
	$rowcount = 0;

	$numcols = 4;
	if (isset($project) && $project != NULL) $numcols--;

	foreach ($log_entries as $log)
	{
		if ($log['type'] == 'code') $code = $log['message'];

		if (CODEPOT_DATABASE_STORE_GMT)
			$createdon = $log['createdon'] . ' +0000';
		else
			$createdon = $log['createdon'];

		$tzoff = strftime ('%z', strtotime($createdon));
		$date = strftime ('%Y-%m-%d', strtotime($createdon));
		$time = strftime ('%H:%M:%S', strtotime($createdon));

		if ($curdate != $date)
		{
			print "<tr class='break'><td colspan='{$numcols}' class='break'>&nbsp;</td></tr>";
			print "<tr class='header'><td colspan='{$numcols}' class='header'>$date ($tzoff)</td></tr>";
			$curdate = $date;
			$rowcount = 0;
		}

		print "<tr class='{$rowclasses[$rowcount%2]}'>";
		$rowcount++;
		print '<td class="time">' . $time . '</td>';

		if (!isset($project) || $project == NULL)
		{
			print '<td class="projectid">';
			print anchor ("project/home/{$log['projectid']}", $log['projectid']);
			print '</td>';
		}

		if ($log['type'] == 'code')
		{
			print '<td class="object">';
			print anchor (
				"code/revision/{$log['projectid']}/{$xdot}/{$code['rev']}", 
				"r{$code['rev']}");
			print '</td>';

			print '<td class="details">';
			print '<span class="description">';

			$xauthor = $this->converter->AsciiToHex ($code['author']);
			if ($log['action'] == 'revpropchange')
			{
				$fmt = $this->lang->line ('MSG_LOG_REVPROP_CHANGE_BY');

				if ($pagetype == 'project')
				{
					printf (
						htmlspecialchars ($fmt),
						htmlspecialchars ($code['propname']),
						anchor ("/project/log/{$log['projectid']}/{$xauthor}", htmlspecialchars ($code['author'])));
				}
				else
				{
					printf (
						htmlspecialchars ($fmt),
						htmlspecialchars ($code['propname']),
						anchor ("/user/log/{$xauthor}", htmlspecialchars ($code['author'])));
				}
				
			}
			else
			{
				$fmt = $this->lang->line (
					'MSG_LOG_'.strtoupper($log['action']).'_BY');

				if ($pagetype == 'project')
				{
					printf (
						htmlspecialchars ($fmt),
						anchor ("/project/log/{$log['projectid']}/{$xauthor}", htmlspecialchars ($code['author'])));
				}
				else
				{
					printf (
						htmlspecialchars ($fmt),
						anchor ("/user/log/{$xauthor}", htmlspecialchars ($code['author'])));
				}
			}
			print '</span>';

			if ($log['action'] != 'revpropchange')
			{
				print '<div class="codepot-plain-text-view"><pre>';
				print htmlspecialchars ($code['message']);
				print '</pre></div>';
			}
		}
		else
		{
			print '<td class="object">';

			$uri = '';
			if ($log['type'] == 'project')
			{
				$uri = "/project/home/{$log['projectid']}";
				$trimmed = preg_replace("/(.{10}).+/u", "$1…", $log['message']);
			}
			else if ($log['type'] == 'wiki')
			{
				$hex = $this->converter->AsciiToHex ($log['message']);
				$uri = "/wiki/show_r/{$log['projectid']}/{$hex}";
				$trimmed = preg_replace("/(.{10}).+/u", "$1…", $log['message']);
			}
			else if ($log['type'] == 'file')
			{
				$hex = $this->converter->AsciiToHex ($log['message']);
				$uri = "/file/show/{$log['projectid']}/{$hex}";
				$trimmed = preg_replace("/(.{10}).+/u", "$1…", $log['message']);
			}
			else if ($log['type'] == 'issue')
			{
				$hex = $this->converter->AsciiToHex ($log['message']);
				$uri = "/issue/show/{$log['projectid']}/{$hex}";
				$trimmed = $this->lang->line('Issue') . " {$log['message']}";
			}

			if ($uri != '' && $trimmed != '')
			{
				print anchor (
					$uri,
					htmlspecialchars ($trimmed),
					'title="'.htmlspecialchars ($log['message']).'"');
			}
			else print htmlspecialchars ($trimmed);
			print '</td>';

			print '<td class="details">';
			print '<span class="description">';
			$fmt = $this->lang->line (
				'MSG_LOG_'.strtoupper($log['action']).'_BY');

			$xuserid = $this->converter->AsciiToHex($log['userid']);
			if ($pagetype == 'project')
				printf ($fmt, anchor ("/project/log/{$log['projectid']}/{$xuserid}", htmlspecialchars($log['userid'])));
			else
				printf ($fmt, anchor ("/user/log/{$xuserid}", htmlspecialchars($log['userid'])));
			print '</span>';
		}

		print '</td>';
		print '</tr>';
	}
?>
</table>

<div id="log_mainarea_result_pages">
<?php print $page_links; ?>
</div>

</div> <!-- log_mainarea_result -->

</div> <!-- log_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- log_content -->

<?php $this->load->view ('footer'); ?>


</body>
</html>
