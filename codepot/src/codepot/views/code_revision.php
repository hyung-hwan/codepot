<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/code.css')?>" />

<script type="text/javascript" src="<?=base_url_make('/js/creole.js')?>"></script>

<script type="text/javascript" src="<?=base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/jquery-ui.css')?>" />

<script type="text/javascript">

<?php $review_count = count($reviews); ?>
<?php $is_loggedin = ($login['id'] != ''); ?>
<?php $can_edit = ($login['id'] == $file['history']['author']); ?>

<?php if ($can_edit): ?>
$(function() {
	$("#code_revision_edit_div").dialog (
		{
			title: '<?=$this->lang->line('Edit')?>',
			width: 'auto',
			height: 'auto',
			resizable: false,
			autoOpen: false,
			modal: true,
			buttons: {
				'<?=$this->lang->line('OK')?>': function () {
					$('#code_revision_edit_logmsg_form').submit ();
					$(this).dialog('close');
				},
				'<?=$this->lang->line('Cancel')?>': function () {
					$(this).dialog('close');
				}
			},
			close: function() { }
		}
	);

	$("#code_revision_edit_logmsg_button").button().click (
		function () {
			$("#code_revision_edit_div").dialog('open');
			return false;
		}
	);
});
<?php endif; ?>

<?php if ($is_loggedin): ?>
$(function() {
	$("#code_revision_new_review_comment_div").dialog (
		{
			title: '<?=$this->lang->line('Comment')?>',
			width: 'auto',
			height: 'auto',
			resizable: false,
			autoOpen: false,
			modal: true,
			buttons: {
				'<?=$this->lang->line('OK')?>': function () {
					$('#code_revision_new_review_comment_form').submit ();
					$(this).dialog('close');
				},
				'<?=$this->lang->line('Cancel')?>': function () {
					$(this).dialog('close');
				}
			},
			close: function() { }
		}
	);

	$("#code_revision_new_review_comment_button").button().click (
		function () {
			$("#code_revision_new_review_comment_div").dialog('open');
			return false;
		}
	);

	<?php
	for ($i = $review_count; $i > 0; $i--)
	{
		$rc = $reviews[$i - 1];
		if ($login['id'] == $rc->updatedby)
		{
			$edit_title = $this->lang->line('Comment') . " {$i}";
			$label_ok = $this->lang->line('OK');
			$label_cancel = $this->lang->line('Cancel');
			print ("

				$('#code_revision_edit_review_comment_div_{$i}').dialog (
					{
						title: '{$edit_title}',
						width: 'auto',
						height: 'auto',
						resizable: false,
						autoOpen: false,
						modal: true,
						buttons: {
							'{$label_ok}': function () {
								// dynamically add a comment number to edit
								var hidden_comment_no = $('<input>').attr('type', 'hidden').attr('name', 'edit_review_comment_no').val('{$i}');
								$('#code_revision_edit_review_comment_form_{$i}').append(hidden_comment_no).submit ();
								$(this).dialog('close');
							},
							'{$label_cancel}': function () {
								$(this).dialog('close');
							}
						},
						close: function() { }
					}
				);
			
				$('#code_revision_edit_review_comment_button_{$i}').button().click (
					function () {
						$('#code_revision_edit_review_comment_div_{$i}').dialog('open');
						return false;
					}
				)
			");
		}
	}
	?>
});
<?php endif; ?>

<?php if (strlen($popup_error_message) > 0): ?>
$(function() {
	$("#code_revision_popup_error_div").dialog( { 
		title: '<?=$this->lang->line('Error')?>',
		width: 'auto',
		height: 'auto',
		modal: true,
		autoOpen: true,
		buttons: {
			"<?=$this->lang->line('OK')?>": function() {
				$( this ).dialog( "close" );
			}
		}
	});
});
<?php endif; ?>

</script>


<script type="text/javascript">
function render_wiki()
{
	creole_render_wiki (
		"code_revision_mainarea_result_msg_text", 
		"code_revision_mainarea_result_msg", 
		"<?=site_url()?>/wiki/show/<?=$project->id?>/",
		""
	);

	<?php 
	print "for (i = 0; i < $review_count; i++) {\n";
	?>

	creole_render_wiki (
		"code_revision_mainarea_review_comment_text_" + (i + 1) , 
		"code_revision_mainarea_review_comment_" + (i + 1), 
		"<?=site_url()?>/wiki/show/<?=$project->id?>/",
		""
	);

	<?php
	print "}\n";
	?>
}
</script>

<title><?=htmlspecialchars($project->name)?></title>
</head>

<body onload="render_wiki()">

<div class="content" id="code_revision_content">

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

<div class="mainarea" id="code_revision_mainarea">

<?php
$history = $file['history'];
?>

<div class="title" id="code_revision_mainarea_title">
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
		"code/revision/{$project->id}{$revreqroot}",
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
			"code/revision/{$project->id}/{$xpar}{$revreq}",
			htmlspecialchars($exps[$i]));
	}

	if ($headpath != $file['fullpath'])
	{
		print ' - ';
		print htmlspecialchars($file['fullpath']);
	}
?>
</div>

<div class="menu" id="code_revision_mainarea_menu">
<?php
	$xpar = $this->converter->AsciiToHex(($headpath == '')? '.': $headpath);
	if ($revision > 0 && $revision < $next_revision)
	{
		print anchor ("code/revision/{$project->id}/{$xpar}", $this->lang->line('Head revision'));
		print ' | ';
	}

	if ($revision > 0)
	{
		if ($xpar == '') $revtrailer = $revreqroot;
		else $revtrailer = "/{$xpar}{$revreq}";
		print anchor (
			"code/history/{$project->id}{$revtrailer}",
			$this->lang->line('History'));
	}
	else
	{
		print anchor ("code/history/{$project->id}/{$xpar}", $this->lang->line('History'));
	}
?>
</div> <!-- code_revision_mainarea_menu -->

<div class="infostrip" id="code_revision_mainarea_infostrip">
	<?=anchor ("code/revision/{$project->id}/${xpar}/{$prev_revision}", '<<')?> 
	<?=$this->lang->line('Revision')?>: <?=$history['rev']?> 
	<?=anchor ("code/revision/{$project->id}/${xpar}/{$next_revision}", '>>')?> | 
	<?=$this->lang->line('Committer')?>: <?=htmlspecialchars($history['author'])?> | 
	<?=$this->lang->line('Last updated on')?>: <?=date('r', strtotime($history['date']))?>
</div>


<div id="code_revision_mainarea_result">

<div class="title"><?=$this->lang->line('Message')?>&nbsp;
<?php if ($can_edit): ?>
	<span class='anchor'>
		<?=anchor ("#", $this->lang->line('Edit'),
		           array ('id' => 'code_revision_edit_logmsg_button'));
		?>
	</span>
<?php endif; ?>
</div>

<div id="code_revision_mainarea_result_msg">
<pre id="code_revision_mainarea_result_msg_text" style="visibility: hidden;">
<?php print htmlspecialchars($history['msg']); ?>
</pre>
</div>

<div class="title"><?=$this->lang->line('Files')?></div>
<table id="code_revision_mainarea_result_table">
<?php 
	/*
	print '<tr class="heading">';
	print '<th>' .  $this->lang->line('Path') . '</th>';
	print '<th></th>';
	print '</tr>';
	*/
	
	$rowclasses = array ('odd', 'even');
	$rowcount = 0;
	foreach ($history['paths'] as $p)
	{
		$rowclass = $rowclasses[++$rowcount % 2];
		print "<tr class='{$rowclass}'>";

		$xpar = $this->converter->AsciiToHex ($p['path']);

		print "<td class='{$p['action']}'>";
		print anchor ("code/file/{$project->id}/{$xpar}/{$history['rev']}", htmlspecialchars($p['path']));
		print '</td>';

		print '<td>';
		//print anchor ("code/blame/{$project->id}/{$xpar}/{$history['rev']}", $this->lang->line('Blame'));
		//print ' ';
		print anchor ("code/diff/{$project->id}/{$xpar}/{$history['rev']}", $this->lang->line('Difference'));
		print '</td>';

		print '<td>';
		print anchor ("code/fulldiff/{$project->id}/{$xpar}/{$history['rev']}", $this->lang->line('Full Difference'));
		print '</td>';

		print '</tr>';
	}
?>
</table>



<div class="title"><?=$this->lang->line('Comment')?>&nbsp;
<?php if ($is_loggedin): ?>
<span class='anchor'>
	<?=anchor ("#", $this->lang->line('New'),
	           array ('id' => 'code_revision_new_review_comment_button'));
	?>
</span>
<?php endif; ?>
</div>

<div id="code_revision_mainarea_review_comment">
<?php
	for ($i = $review_count; $i > 0; $i--)
	{
		$rc = $reviews[$i - 1];
		print "<div id='code_revision_mainarea_review_comment_title_{$i}' class='review_comment_title'>\n";
		printf (" <span class='review_comment_title_no'>%d</span>", $rc->sno);
		printf (" <span class='review_comment_title_updatedby'>%s</span>", $rc->updatedby);
		printf (" <span class='review_comment_title_updatedon'>%s</span>", $rc->updatedon);
		
		if ($login['id'] == $rc->updatedby)
		{
			print '&nbsp;';
			print anchor (
				"#", $this->lang->line('Edit'), 
				array ('id' => 'code_revision_edit_review_comment_button_' . $i)
			);
		}

		print ("</div>\n");

		print "<div id='code_revision_mainarea_review_comment_{$i}' class='review_comment_text'>\n";
		print "<pre id='code_revision_mainarea_review_comment_text_{$i}' style='visibility: hidden'>\n";

		print $rc->comment;

		print "</pre>\n";
		print "</div>\n";
	}

?>
</div> <!-- code_revision_mainarea_review_comment -->



</div> <!-- code_revision_mainarea_result -->

</div> <!-- code_revision_mainarea -->


<!---------------------------------------------------------------------------->


<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!-- code_revision_content -->

<?php if ($can_edit): ?>
<div id="code_revision_edit_div">
	<?=form_open("code/revision/{$project->id}${revreqroot}", 'id="code_revision_edit_logmsg_form"')?>
		<?=
			form_textarea (
				array ('name' => 'edit_log_message', 
				       'value' => $history['msg'], 'rows'=> 10, 'cols' => 70,
				       'id' => 'code_revision_edit_log_message')
			)

		?>
	<?=form_close()?>
</div>
<?php endif; ?> <!-- $can_edit -->

<?php if ($is_loggedin): ?>
<div id="code_revision_new_review_comment_div">
<?php
	print form_open("code/revision/{$project->id}${revreqroot}", 'id="code_revision_new_review_comment_form"');

	print form_error('new_review_comment'); 
	print '<br />';

	print form_textarea (
		array ('name' => 'new_review_comment', 
		       'value' => set_value('new_review_comment', ''), 
		       'rows'=> 20, 'cols' => 90,
		       'id' => 'code_revision_new_review_comment')
	);
	print form_close();

	for ($i = $review_count; $i > 0; $i--)
	{
		$rc = $reviews[$i - 1];

		if ($login['id'] == $rc->updatedby)
		{
			print "<div id='code_revision_edit_review_comment_div_{$i}'>\n";
			print form_open("code/revision/{$project->id}${revreqroot}", "id='code_revision_edit_review_comment_form_{$i}'");
			print form_error("edit_review_comment_{$i}");
			print '<br />';
			print form_textarea (
				array ('name' => "edit_review_comment_{$i}",
				       'value' => $rc->comment, 'rows'=> 20, 'cols' => 90,
				       'id' => "code_revision_edit_review_comment_{$i}")
			);
			print form_close();
			print "</div>\n";
		}
	}
?>
</div>
<?php endif; ?> <!-- $is_loggedin -->

<?php if (strlen($popup_error_message) > 0): ?>
<div id="code_revision_popup_error_div">
<?=$popup_error_message?>
</div>
<?php endif; ?>


</body>

</html>

