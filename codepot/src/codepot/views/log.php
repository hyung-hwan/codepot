<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/log.css')?>" />
     
<?php if ($login['sysadmin?'] && isset($site)): ?>

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

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
	$pageid = ''; 
	$pageobj = $project; 
	$banner = NULL;
}
else if (isset($site)) 
{ 
	if ($login['sysadmin?'])
	{
		$pagetype = 'site';
		$pageid = 'log';
		$pageobj = $site; 
		$banner = NULL;
	}
	else
	{
		$pagetype = '';
		$pageid = '';
		$pageobj = NULL;
		$banner = $site->name;
	}
}
else if (isset($user)) 
{ 
	$pagetype = 'user';
	$pageid = ''; 
	$pageobj = $user; 
	$banner = NULL;
}
else 
{ 
	$pagetype = '';
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
			$pagetype => $pageobj	
		),

                'ctxmenuitems' => array ()
        )
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="log_mainarea">

<div class="title" id="log_mainarea_title">
<?php print  anchor ("site/log", $this->lang->line ('Change log')) ?>
</div>

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

	<div class="infostrip">
	<a id="log_mainarea_purge" href="#"><?php print  $this->lang->line('Purge') ?></a>
	</div>

<?php endif; ?>

<div id="log_mainarea_result">

<table id="log_mainarea_result_table">
<?php 
	$curdate = '';
	$xdot = $this->converter->AsciiToHex ('.');

	$rowclasses = array ('odd', 'even');
	$rowcount = 0;

	$numcols = 4;
	if (isset($project) && $project != NULL) $numcols--;

	foreach ($log_entries as $log)
	{
		if ($log['type'] == 'code')
		{
			$code = $log['message'];

			//$date = substr($code['time'], 0, 10);
			//$time = substr($code['time'], 11, 5);
			$date = date ('Y-m-d', strtotime($log['createdon']));
			$time = date ('h:i', strtotime($log['createdon']));
		}
		else
		{
			$date = date ('Y-m-d', strtotime($log['createdon']));
			$time = date ('h:i', strtotime($log['createdon']));
		}

		if ($curdate != $date)
		{
			print "<tr class='break'><td colspan='{$numcols}' class='break'>&nbsp;</td></tr>";
			print "<tr class='head'><td colspan='{$numcols}' class='date'>$date</td></tr>";
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
			if ($log['action'] == 'revpropchange')
			{
				$fmt = $this->lang->line ('MSG_LOG_REVPROP_CHANGE_BY');
				//print htmlspecialchars (sprintf($fmt, $code['propname'], $code['author']));
				printf (
					htmlspecialchars ($fmt),
					htmlspecialchars ($code['propname']),
					anchor ("/site/userlog/{$code['author']}", htmlspecialchars ($code['author'])));
				//$code['action']
			}
			else
			{
				$fmt = $this->lang->line (
					'MSG_LOG_'.strtoupper($log['action']).'_BY');

				//print htmlspecialchars (sprintf($fmt, $code['author']));
				printf (
					htmlspecialchars ($fmt),
					anchor ("/site/userlog/{$code['author']}", htmlspecialchars ($code['author'])));
			}
			print '</span>';

			if ($log['action'] != 'revpropchange')
			{
				print '<pre class="message">';
				print htmlspecialchars ($code['message']);
				print '</pre>';
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

			if ($uri != '')
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

			//print htmlspecialchars (sprintf($fmt, $log['userid']));
			printf (
				htmlspecialchars ($fmt),
				anchor ("/site/userlog/{$log['userid']}", htmlspecialchars ($log['userid'])));
			print '</span>';
		}

		print '</td>';
		print '</tr>';
	}
?>
<tr class='foot'>
<td colspan='<?php print $numcols?>' class='pages'><?php print  $page_links ?></td>
</tr>
</table>

</div> <!-- log_mainarea_result -->

</div> <!-- log_mainarea -->

<?php $this->load->view ('footer'); ?>

</div> <!-- log_content -->

</body>
</html>
