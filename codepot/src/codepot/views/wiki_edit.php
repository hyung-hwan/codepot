<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/wiki.css" />

<script type="text/javascript" src="<?=base_url()?>/js/jquery.min.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/jquery-ui.css" />

<script type="text/javascript">
var new_attachment_no = 0;
$(function () {
	$('#wiki_edit_more_new_attachment').button().click (
		function () {
			var html = [
				'<li><input type="file" name="wiki_new_attachment_',
				++new_attachment_no,
				'" /></li>'
			].join("");
			$('#wiki_edit_new_attachment_list').append (html);
			return false;
		}
	);
});
</script>

<title><?=htmlspecialchars($wiki->name)?></title>
</head>

<body>

<div class="content">

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
			'id' => 'wiki',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="wiki_edit_mainarea">

<?php if ($message != "") print '<div id="wiki_edit_message" class="form_message">'.htmlspecialchars($message).'</div>'; ?>

<?=form_open_multipart("wiki/{$mode}/{$project->id}/".$this->converter->AsciiToHex($wiki->name))?>
	<?=form_fieldset()?>
		<div>
			<div>
				<?=form_label($this->lang->line('Name').': ', 'wiki_name')?>
				<?=form_error('wiki_name');?>
			</div>
			<div>
				<?php 
					$extra = ($mode == 'update')? 'readonly="readonly"': ''; 
					$extra .= 'maxlength="80" size="40" id="wiki_edit_mainarea_name"';
				?>
				<?=form_input('wiki_name', set_value('wiki_name', $wiki->name), $extra)?>
			</div>
		</div>

		<div>
			<div>
				<?=form_label($this->lang->line('Text').': ', 'wiki_text')?>
				<?=form_error('wiki_text');?>
			</div>
			<div>
				<?php
					$xdata = array (
						'name' => 'wiki_text',
						'value' => set_value ('wiki_text', $wiki->text),
						'id' => 'wiki_edit_mainarea_text',
						'rows' => 20,
						'cols' => 80
					);
					print form_textarea ($xdata);
				?>
			</div>
		</div>

		<?php if (!empty($wiki->attachments)): ?>
		<div>
			<div>
				<?=form_label($this->lang->line('WIKI_ATTACHMENTS').': ', 'wiki_edit_attachment_list')?> 
				<?=form_error('wiki_attachment_list');?>
			</div>

			<ul id='wiki_edit_attachment_list'>
			<?php
				foreach ($wiki->attachments as $att)
				{
					$hexattname = 
						$this->converter->AsciiToHex($att->name) . 
						'@' .
						$this->converter->AsciiToHex($att->encname);
					$escattname = htmlspecialchars($att->name);

					print '<li>';
					print "<input type='checkbox' name='wiki_delete_attachment[]' value='{$hexattname}' title='Check to delete {$escattname}'/>";
					print $escattname;
					print '</li>';
				}
			?>
			</ul>

		</div>
		<?php endif; ?>

		<div>
			<div>
				<?=form_label($this->lang->line('WIKI_NEW_ATTACHMENTS').': ', 'wiki_edit_new_attachment_list')?> 
				<a href='#' id='wiki_edit_more_new_attachment'>
					<?=$this->lang->line('WIKI_MORE_NEW_ATTACHMENTS')?>
				</a>
				<?=form_error('wiki_edit_new_attachment_list');?>
			</div>

			<ul id='wiki_edit_new_attachment_list'>
			<li>	
				<input type='file' name='wiki_new_attachment_0' />
				<!--<input type='checkbox' name='wiki_delete_attachment[]' value='delete'/>Delete-->
			</li>
			</ul>

		</div>
		
		<div>
			<?=form_hidden('wiki_projectid', set_value('wiki_projectid', $wiki->projectid))?>
		</div>

		<div>
			<?php $caption = ($mode == 'update')? $this->lang->line('Update'): $this->lang->line('Create'); ?>
			<?=form_submit('wiki', $caption)?>
		</div>

	<?=form_fieldset_close()?>
<?=form_close();?>

</div> <!-- wiki_edit_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>

</body>

</html>
