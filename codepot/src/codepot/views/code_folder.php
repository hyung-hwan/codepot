<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/code.css')?>" />

<script type="text/javascript" src="<?=base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/jquery-ui.css')?>" />

<?php
	$file_count = count($file['content']);
?>

<script type="text/javascript">
<?php if ($file_count > 0): ?>
$(function () {
	<?php
	if ($login['settings'] != NULL && $login['settings']->code_hide_details == 'Y')
		print '$("#code_folder_mainarea_result_info").hide();';
	?>

	if ($("#code_folder_mainarea_result_info").is(":visible"))
		btn_label = "<?=$this->lang->line('Hide details')?>";
	else
		btn_label = "<?=$this->lang->line('Show details')?>";
	
	btn = $("#code_folder_mainarea_details_button").button({"label": btn_label}).click (function () {
		
		if ($("#code_folder_mainarea_result_info").is(":visible"))
		{
			$("#code_folder_mainarea_result_info").hide("blind",{},200);
			$("#code_folder_mainarea_details_button").button(
				"option", "label", "<?=$this->lang->line('Show details')?>");
		}
		else
		{
			$("#code_folder_mainarea_result_info").show("blind",{},200);
			$("#code_folder_mainarea_details_button").button(
				"option", "label", "<?=$this->lang->line('Hide details')?>");
		}
	});
});
<?php endif; ?>
</script>

<title><?=htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="code_folder_content">

<!-- ================================================================== -->

<?php $this->load->view ('taskbar'); ?>

<!-- ================================================================== -->

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

<!-- ================================================================== -->

<div class="mainarea" id="code_folder_mainarea">

<div class="title">
<?php
	if ($revision <= 0)
	{
		$revreq = '';
		$revreqroot = '';
	}
	else
	{
		$revreq = "/{$revision}";
		$revreqroot = '/' . $this->converter->AsciiToHex('.') . $revreq;
	}

	// print the main anchor for the root folder. 
	// let the anchor text be the project name.
	print anchor (
		"code/file/{$project->id}{$revreqroot}", 
		htmlspecialchars($project->name));

	// explode non-root folder parts to anchors
	$exps = explode ('/', $headpath);
	$expsize = count($exps);
	$par = '';
	for ($i = 1; $i < $expsize; $i++)
	{
		print '/';
		$par .= '/' . $exps[$i];
		$xpar = $this->converter->AsciiToHex ($par);
		print anchor (
			"code/file/{$project->id}/{$xpar}{$revreq}",
			htmlspecialchars($exps[$i]));
	}

	if ($headpath != $file['fullpath'])
	{
		print ' - ';
		print htmlspecialchars ($file['fullpath']);
	}
?>
</div>


<div class="infostrip" id="code_folder_mainarea_infostrip">
	
	<?php if (CODEPOT_SIGNIN_FOR_CODE_SEARCH === FALSE || (isset($login['id']) && $login['id'] != '')): ?>
	<?=form_open("code/search/{$project->id}/", 'id="code_folder_search_form"')?>
	<?=form_hidden('search_folder', set_value('search_folder', $file['fullpath']), 'id="code_folder_search_folder"')?>
	<?=form_hidden('search_revision', set_value('search_revision', $revision), 'id="code_folder_search_revision"')?>
	<?=form_input('search_pattern', set_value('search_pattern', ''), 'id="code_folder_search_pattern"')?>
	<?=form_submit('search_submit', $this->lang->line('Search'), 'id="code_folder_search_submit"')?>
	| 
	<?php endif; ?>

	<?=$this->lang->line('Revision')?>: <?=$file['created_rev']?> 
	<?php if ($file_count > 0): ?>
	| 
	<a id="code_folder_mainarea_details_button" href='#'><?=$this->lang->line('Details')?></a>
	<?php endif; ?>

	<?=form_close()?>
</div>

<div id="code_folder_mainarea_result">

<?php
	function comp_files ($a, $b)	
	{
		if ($a['type'] == $b['type'])
		{
			return strcasecmp ($a['name'], $b['name']);
		}	

		return ($a['type'] == 'dir')? -1: 1;
	}

	if ($file_count <= 0)
	{
		 print $this->lang->line('MSG_NO_CODE_AVAIL');
	}
	else 
	{
		print '<div class="menu" id="code_folder_mainarea_menu">';
		$xpar = $this->converter->AsciiTohex ($headpath);
		if ($revision > 0 && $revision < $next_revision)
		{
			print anchor ("code/file/{$project->id}/{$xpar}", $this->lang->line('Head revision'));
			print ' | ';
		}

		if ($revision > 0)
		{
			if ($xpar == '') $revtrailer = $revreqroot;
			else $revtrailer = "/{$xpar}{$revreq}";
			print anchor ("code/history/{$project->id}{$revtrailer}", $this->lang->line('History'));
		}
		else
			print anchor ("code/history/{$project->id}/{$xpar}", $this->lang->line('History'));

		print '</div>';

		usort ($file['content'], 'comp_files');

		print '<table id="code_folder_mainarea_result_table">';
		print '<tr class="heading">';
		print '<th>' . $this->lang->line('Name') . '</th>';
		print '<th>' . $this->lang->line('Revision') . '</th>';
		print '<th>' . $this->lang->line('Size') . '</th>';
		print '<th>' . $this->lang->line('Committer') . '</th>';
		print '<th>' . $this->lang->line('Date') . '</th>';
		print '<th>' . $this->lang->line('Blame') . '</th>';
		print '<th>' . $this->lang->line('Difference') . '</th>';
		print '<th>' . $this->lang->line('Full Difference') . '</th>';
		print '</tr>';

		$rowclasses = array ('even', 'odd');
		$rownum = 0;
		foreach ($file['content'] as $f)
		{
			//$fullpath = $headpath . '/' . $f['name'];
			$fullpath = $file['fullpath'] . '/' . $f['name'];

			$rowclass = $rowclasses[++$rownum % 2];
			if ($f['type'] === 'dir')
			{
				// directory 
				$hexpath = $this->converter->AsciiToHex($fullpath);
       		         	print "<tr class='{$rowclass}'>";
				print '<td>';
				print anchor (
					"code/file/{$project->id}/{$hexpath}{$revreq}",
					htmlspecialchars($f['name']));
				print '</td>';
				print '<td>';
				print $f['created_rev'];
				print '</td>';
				print '<td></td>';
				print '<td>';
				print htmlspecialchars($f['last_author']);
				print '</td>';
				print '<td><code>';
				//print date('r', $f['time_t']);
				print date('Y-m-d', $f['time_t']);
				print '</code></td>';
				print '<td></td>';
				print '<td></td>';
				print '</tr>';
			}
			else
			{
				// file
				$hexpath = $this->converter->AsciiToHex($fullpath);
       		         	print "<tr class='{$rowclass}'>";
				print '<td>';
				print anchor (
					"code/file/{$project->id}/{$hexpath}{$revreq}",
					htmlspecialchars($f['name']));
				print '</td>';
				print '<td>';
				print $f['created_rev'];
				print '</td>';
				print '<td>';
				print $f['size'];
				print '</td>';
				print '<td>';
				print htmlspecialchars($f['last_author']);
				print '</td>';
				print '<td><code>';
				//print date('r', $f['time_t']);
				print date('Y-m-d', $f['time_t']);
				print '</code></td>';

				print '<td>';
				print anchor (
					"code/blame/{$project->id}/{$hexpath}{$revreq}",
					$this->lang->line('Blame'));
				print '</td>';
				print '<td>';
				print anchor (
					"code/diff/{$project->id}/{$hexpath}{$revreq}",
					$this->lang->line('Difference'));
				print '</td>';
				print '<td>';
				print anchor (
					"code/fulldiff/{$project->id}/{$hexpath}{$revreq}",
					$this->lang->line('Full Difference'));
				print '</td>';
				print '</tr>';
			}
		}
		print '</table>';

		print '<div id="code_folder_mainarea_result_info">';

		print '<div class="title">';
		print $this->lang->line('CODE_COMMIT');
		print '</div>';
		printf ($this->lang->line('CODE_MSG_COMMITTED_BY'), $file['last_author']);

		print '<div class="title">';
		print $this->lang->line('Message');
		print '</div>';
		print '<pre id="code_folder_mainarea_result_info_logmsg">';
		print htmlspecialchars ($file['logmsg']);
		print '</pre>';

		if (array_key_exists('properties', $file) && count($file['properties']) > 0)
		{
			print '<div class="title">';
			print $this->lang->line('CODE_PROPERTIES');
			print '</div>';

			print '<ul id="code_folder_mainarea_result_info_property_list">';
			foreach ($file['properties'] as $pn => $pv)
			{
				print '<li>';
				print htmlspecialchars($pn);
				if ($pv != '')
				{
					print ' - ';
					print htmlspecialchars($pv);
				}
				print '</li>';
			}
			print '</ul>';
		}

		print '</div>';
	}
?>

</div> <!-- code_folder_mainarea_result -->

</div> <!-- code_folder_mainarea -->


<!-- ================================================================== -->

<?php $this->load->view ('footer'); ?>

<!-- ================================================================== -->

</div> <!--  code_folder_content -->

</body>

</html>

