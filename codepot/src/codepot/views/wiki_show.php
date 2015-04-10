<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/wiki.css')?>" />

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
$(function () {
	if ($("#wiki_show_mainarea_result_info").is(":visible"))
		btn_label = "<?php print $this->lang->line('Hide details')?>";
	else
		btn_label = "<?php print $this->lang->line('Show details')?>";
	

	btn = $("#wiki_show_mainarea_details_button").button({"label": btn_label}).click (function () {
		
		if ($("#wiki_show_mainarea_result_info").is(":visible"))
		{
			$("#wiki_show_mainarea_result_info").hide("blind",{},200);
			$("#wiki_show_mainarea_details_button").button(
				"option", "label", "<?php print $this->lang->line('Show details')?>");
		}
		else
		{
			$("#wiki_show_mainarea_result_info").show("blind",{},200);
			$("#wiki_show_mainarea_details_button").button(
				"option", "label", "<?php print $this->lang->line('Hide details')?>");
		}
	});
});

function render_wiki()
{
	var column_count = '<?php print  $wiki->columns ?>';
	var x_column_count = parseInt (column_count);
	if (isNaN(x_column_count) || x_column_count < 1) x_column_count = 1;
	else if (x_column_count > 9) x_column_count = 9; // sync this max value with wiki_edit. TODO: put this into codepot.ini

	column_count = x_column_count.toString();

	$("#wiki_show_mainarea_wiki").css ({
		"-moz-column-count":    column_count,
		"-webkit-column-count": column_count,
		"column-count":         column_count
	});

	creole_render_wiki (
		"wiki_show_mainarea_wiki_text", 
		"wiki_show_mainarea_wiki", 
		"<?php print site_url()?>/wiki/show/<?php print $project->id?>/",
		"<?php print site_url()?>/wiki/attachment/<?php print $project->id?>/<?php print $hexname?>/"
	);

	prettyPrint ();
}
</script>

<body onload="render_wiki()">

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
			array ("wiki/create/{$project->id}", $this->lang->line('New')),
			array ("wiki/update/{$project->id}/{$hexname}", $this->lang->line('Edit')),
			array ("wiki/delete/{$project->id}/{$hexname}", $this->lang->line('Delete'))
		)
	)
);
?>

<!---------------------------------------------------------------------------->


<div class="mainarea" id="wiki_show_mainarea">

<div class="title"><?php print htmlspecialchars($wiki->name)?></div>

<div class="infostrip" id="wiki_show_mainarea_infostrip">
	<a id="wiki_show_mainarea_details_button" href='#'><?php print $this->lang->line('Details')?></a>
</div>




<div id="wiki_show_mainarea_result">


<div class="result" id="wiki_show_mainarea_wiki">
<pre id="wiki_show_mainarea_wiki_text" style="visibility: hidden">
<?php print htmlspecialchars($wiki->text); ?>
</pre>
</div> <!-- wiki_show_mainarea_wiki -->


<div id="wiki_show_mainarea_result_info"> 

<ul>
<li><?php print $this->lang->line('Created on')?> <?php print  $wiki->createdon ?></li>
<li><?php print $this->lang->line('Created by')?> <?php print  $wiki->createdby ?></li>
<li><?php print $this->lang->line('Last updated on')?> <?php print  $wiki->updatedon ?></li>
<li><?php print $this->lang->line('Last updated by')?> <?php print  $wiki->updatedby ?></li>
</ul>

<?php if (!empty($wiki->attachments)): ?>
	<div class="title"><?php print  $this->lang->line('WIKI_ATTACHMENTS') ?></div>
	<ul>
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
<?php endif; ?>

</div> <!-- wiki_show_mainarea_result_info -->

</div> <!-- wiki_show_mainarea_result -->

</div> <!-- wiki_show_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  wiki_show_content -->

</body>

</html>

