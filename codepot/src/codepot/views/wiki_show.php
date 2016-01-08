<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/wiki.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/creole.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/prettify/prettify.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-css.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lisp.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lua.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-sql.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-vb.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<title><?php 
	printf ('%s - %s', htmlspecialchars($project->name), htmlspecialchars($wiki->name));
?></title>
</head>

<?php
$hexname = $this->converter->AsciiToHex ($wiki->name);
?>

<script type="text/javascript">
function render_wiki()
{
	var column_count = '<?php print  $wiki->columns ?>';
	var x_column_count = parseInt (column_count);
	if (isNaN(x_column_count) || x_column_count < 1) x_column_count = 1;
	else if (x_column_count > 9) x_column_count = 9; // sync this max value with wiki_edit. TODO: put this into codepot.ini

	if (x_column_count > 1)
	{
		column_count = x_column_count.toString();
		$("#wiki_show_mainarea_wiki").css ({
			"-moz-column-count":    column_count,
			"-webkit-column-count": column_count,
			"column-count":         column_count
		});
	}

	creole_render_wiki (
		"wiki_show_mainarea_wiki_text", 
		"wiki_show_mainarea_wiki", 
		"<?php print site_url()?>/wiki/show/<?php print $project->id?>/",
		"<?php print site_url()?>/wiki/attachment/<?php print $project->id?>/<?php print $hexname?>/"
	);

	prettyPrint ();
}

$(function () {
	$('#wiki_show_mainarea_metadata').accordion({
		collapsible: true
	});

	render_wiki ();
});
</script>

<body>

<div class="content" id="wiki_show_content">

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

		'ctxmenuitems' => array (
			array ("wiki/create/{$project->id}", '<i class="fa fa-plus"></i> ' . $this->lang->line('New')),
			array ("wiki/update/{$project->id}/{$hexname}", '<i class="fa fa-edit"></i> ' .$this->lang->line('Edit')),
			array ("wiki/delete/{$project->id}/{$hexname}", '<i class="fa fa-trash"></i> ' .$this->lang->line('Delete'))
		)
	)
);
?>

<!---------------------------------------------------------------------------->


<div class="mainarea" id="wiki_show_mainarea">

<div class="title-band" id="wiki_show_mainarea_title_band">
	<div class="title"><?php print htmlspecialchars($wiki->name)?></div>

	<div class="actions">
	</div>

	<div style='clear: both'></div>
</div>

<div id="wiki_show_mainarea_result" class="result">

<div id='wiki_show_mainarea_metadata' class='collapsible-box'>
	<div id='wiki_show_mainarea_metadata_header' class='collapsible-box-header'><?php print $this->lang->line('Metadata')?></div>
	<div id='wiki_show_mainarea_metadata_body'>

		<div id='wiki_show_mainarea_metadata_list_div'>
			<ul id='wiki_show_mainarea_metadata_list'>
			<li><?php print $this->lang->line('Created on')?> <?php print codepot_dbdatetodispdate($wiki->createdon); ?></li>
			<li><?php print $this->lang->line('Created by')?> <?php print htmlspecialchars($wiki->createdby); ?></li>
			<li><?php print $this->lang->line('Last updated on')?> <?php print codepot_dbdatetodispdate($wiki->updatedon); ?></li>
			<li><?php print $this->lang->line('Last updated by')?> <?php print htmlspecialchars($wiki->updatedby); ?></li>
			</ul>
		</div>

		<div id='wiki_show_mainarea_attachment_list_div'>
			<ul id='wiki_show_mainarea_attachment_list'>
			<?php
				foreach ($wiki->attachments as $att)
				{
					$hexattname = $this->converter->AsciiToHex ($att->name);
					print '<li>';
					print anchor (
						"wiki/attachment/{$project->id}/{$hexname}/{$hexattname}", 
						htmlspecialchars($att->name)
					);
					print '</li>';
				}
			?>
			</ul>
		</div>

		<div style='clear: both;'></div>
	</div>
</div>


<div class="result" id="wiki_show_mainarea_wiki">
<pre id="wiki_show_mainarea_wiki_text" style="visibility: hidden">
<?php print htmlspecialchars($wiki->text); ?>
</pre>
</div> <!-- wiki_show_mainarea_wiki -->

</div> <!-- wiki_show_mainarea_result -->

</div> <!-- wiki_show_mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  wiki_show_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>

</html>

