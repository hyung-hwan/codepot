<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/code.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/prettify/prettify.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-css.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lisp.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lua.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-sql.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-vb.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jqueryui-editable.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jqueryui-editable.css')?>" />

<script type="text/javascript">
$(function() {
	$('#code_search_submit').button().click (function (e) {
		if ($.trim($("#code_search_string").val()) != "")
		{
			$('#code_search_submit').button ('disable');
			$('#code_search_string_icon').addClass ('fa-cog fa-spin');
			$('#code_search_form').submit ();
		}
		return false; // prevent the default behavior
	});

	/*
	$('#code_search_form').submit (function(e) {
		if ($.trim($("#code_search_string").val()) === "")
		{
			// prevent submission when the search string is empty.
			e.preventDefault();
		}
	});*/
	
	$('#code_search_invertedly').button();
	$('#code_search_case_insensitively').button();
	$('#code_search_recursively').button();
	$('#code_search_in_name').button();
	$('#code_search_is_regex').button();
	$('.code_search_option').tooltip({
		position: {
			my: "left top",
			at: "right-5 bottom-5",
			collision: "none"
		}
	});

	$('#code_search_wildcard').text($('input[name=search_wildcard_pattern]').val());

	$('#code_search_wildcard').editable({
		type: 'text',
		title: '<?php print $this->lang->line('CODE_SEARCH_WILDCARD') ?>',
		placement: 'bottom',
		success: function(response, newValue) {
			$('input[name=search_wildcard_pattern]').val(newValue);
		}
	});

	prettyPrint();
});
</script>

<?php
	$file_count = count($file['content']);
?>

<title><?php 
	if ($headpath == '')
		printf ('%s', htmlspecialchars($project->name));
	else
		printf ('%s-%s', htmlspecialchars($project->name), htmlspecialchars($headpath));
?></title>
</head>

<body>

<div class="content" id="code_search_content">

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

<div class="mainarea" id="code_search_mainarea">

<div class="codepot-title-band" id="code_search_title_band">

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
			htmlspecialchars($project->id));

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

	<div class="actions">
		<?php
		print form_open("code/search/{$project->id}/", 'id="code_search_form"');
		print form_hidden ('search_folder', set_value('search_folder', $file['fullpath']));
		print form_hidden ('search_revision', set_value('search_revision', $revision));
		
		print '<i id="code_search_string_icon" class="fa"></i> ';
		print form_input(array(
			'name' => 'search_string', 
			'value' => set_value('search_string', ''), 
			'id' =>'code_search_string',
			'placeholder' => $this->lang->line('CODE_SEARCH_STRING')
		));
		print ' ';

		print form_checkbox(array(
			'name'    => 'search_invertedly', 
			'id'      => 'code_search_invertedly',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => ($invertedly == 'Y')/*,
			'title'   => $this->lang->line('CODE_SEARCH_INVERTEDLY')*/
		));
		print form_label('v', 'code_search_invertedly',
			array('class' => 'code_search_option',
			      'id' => 'code_search_invertedly_label',
			      'title' => $this->lang->line('CODE_SEARCH_INVERTEDLY'))
		);

		print form_checkbox(array(
			'name'    => 'search_case_insensitively', 
			'id'      => 'code_search_case_insensitively',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => ($case_insensitively == 'Y')/*,
			'title'   => $this->lang->line('CODE_SEARCH_CASE_INSENSITIVELY')*/
		));
		print form_label('i', 'code_search_case_insensitively', 
			array('class' => 'code_search_option',
			      'id' => 'code_search_case_insensitively_label',
			      'title' => $this->lang->line('CODE_SEARCH_CASE_INSENSITIVELY'))
		);

		print form_checkbox(array(
			'name'    => 'search_recursively', 
			'id'      => 'code_search_recursively',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => ($recursively == 'Y')/*,
			'title'   => $this->lang->line('CODE_SEARCH_RECURSIVELY')*/
		));
		print form_label('r', 'code_search_recursively', 
			array('class' => 'code_search_option',
			      'id' => 'code_search_recursively_label',
			      'title' => $this->lang->line('CODE_SEARCH_RECURSIVELY'))
		);

		print form_checkbox(array(
			'name'    => 'search_in_name', 
			'id'      => 'code_search_in_name',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => ($in_name == 'Y')/*,
			'title'   => $this->lang->line('CODE_SEARCH_IN_NAME')*/
		));
		print form_label('n', 'code_search_in_name', 
			array('class' => 'code_search_option',
			      'id' => 'code_search_in_name_label',
			      'title' => $this->lang->line('CODE_SEARCH_IN_NAME'))
		);

		print form_checkbox(array(
			'name'    => 'search_is_regex', 
			'id'      => 'code_search_is_regex',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => ($is_regex == 'Y')/*,
			'title'   => $this->lang->line('CODE_SEARCH_IS_REGEX')*/
		));
		print form_label('x', 'code_search_is_regex', 
			array('class' => 'code_search_option',
			      'id' => 'code_search_is_regex_label',
			      'title'   => $this->lang->line('CODE_SEARCH_IS_REGEX'))
		);

		print '<a id="code_search_wildcard" href="#"></a>';
		print form_hidden('search_wildcard_pattern', set_value('search_wildcard_pattern', $wildcard_pattern));

		print ' ';
		printf ('<a id="code_search_submit" href="#">%s</a>', $this->lang->line('Search'));
		//print form_submit ('search_submit', $this->lang->line('Search'), 'id="code_search_submit"');
		print ' | ';
		print anchor (
			"code/revision/{$project->id}/!/{$file['created_rev']}",
			sprintf("%s %s", $this->lang->line('Revision'), $file['created_rev'])
		);

		print form_close();
		?>
	</div>

	<div style="clear: both;"></div>
</div>

<div id="code_search_result" class="codepot-relative-container-view">

<?php
// this searching part should have been placed in SubversionModel.
function search_and_show ($controller, $project, $path, $revision, $pattern, $invertedly, $case_insensitively, $is_regex, $recurse, $in_name, $wildcard)
{
	//$file = $controller->subversion->getFile ($project->id, $path, $revision);
	//if ($file['type'] == 'file') return;

	$dirarray = array ($path);

	while (count($dirarray) > 0)
	{
		$path = array_shift ($dirarray);
		$file = $controller->subversion->getFile ($project->id, $path, $revision);
		if ($file === FALSE || $file['type'] == 'file') continue;

		// search in a directory.
		$file_list = $file['content'];
		foreach ($file_list as $f)
		{
			$fullpath = $file['fullpath'] . '/' . $f['name'];
			$file2 = $controller->subversion->getFile ($project->id, $fullpath, $revision, $in_name);
			if ($file2 !== FALSE)
			{
				if ($file2['type'] == 'file')
				{
					if (!empty($wildcard) &&
					    fnmatch($wildcard, $file2['name'], FNM_PATHNAME | FNM_PERIOD) === FALSE) continue;

					if ($in_name)
					{
						$lines = array ($file2['name']);
					}
					else
					{
						$lines = explode("\n", $file2['content']);
					}

					$xpattern = $is_regex? $pattern: preg_quote ($pattern, '/');
					$xflags = $invertedly? PREG_GREP_INVERT: 0;
					$matchlines = $case_insensitively?
						@preg_grep ("/{$xpattern}/i", $lines, $xflags):
						@preg_grep ("/{$xpattern}/", $lines, $xflags);

					if ($matchlines === FALSE)
					{
						print $controller->lang->line('CODE_BAD_SEARCH_STRING'); 
						return;
					}
					else if (count($matchlines) > 0)
					{
						$hexpath = $controller->converter->AsciiToHex($fullpath);
						if ($revision <= 0)
						{
							$revreq = '';
							$revreqroot = '';
						}
						else
						{
							$revreq = "/{$file2['created_rev']}";
							$revreqroot = '/' . $controller->converter->AsciiToHex ('.') . $revreq;
						}

						print '<div class="codepot-styled-text-view">';

						print '<div class="title">';
						print anchor (
							"code/file/{$project->id}/{$hexpath}{$revreq}",
							htmlspecialchars($fullpath));
						print '</div>';

						if ($in_name)
						{
							print '<pre class="prettyprint">';
							print htmlspecialchars($file2['name']);
							print '</pre>';
						}
						else
						{
							$fileext = substr(strrchr($file2['name'], '.'), 1);
							if ($fileext == "") $fileext = "html";

							print '<pre class="codepot-line-numbered">';
							print '<span class="codepot-line-number-block">';
							foreach ($matchlines as $linenum => $line)
							{
								print "<span>{$linenum}</span>";
							}
							print '</span>';
							print "<code class='codepot-line-numbered-code prettyprint lang-{$fileext}'>";
							foreach ($matchlines as $linenum => $line)
							{
								print htmlspecialchars($line);
								print "\n";
							}
							print '</code>';
							print '<span class="codepot-line-number-clear"></span>';
							print '</pre>';
						}

						print '</div>';
					}
				
				}
				else
				{
					if ($recurse && count($file2['content']) > 0)
					{
						array_push ($dirarray, $fullpath);
					}
				}
			}
		}
	}
}

// the repository search can take very long.
// change the execution time limit to run this script forever if it's allowed.
if (CODEPOT_ALLOW_SET_TIME_LIMIT) set_time_limit (0);

// TODO: prevent recursion to subdirectories depending on input
search_and_show ($this, $project, $file['fullpath'], $revision, $pattern, $invertedly, $case_insensitively, $is_regex, $recursively, $in_name, $wildcard_pattern);
?>

</div> <!-- code_search_result -->

</div> <!-- code_search_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  code_search_content -->

<!-- ================================================================== -->

<?php $this->load->view ('footer'); ?>

<!-- ================================================================== -->


</body>

</html>

