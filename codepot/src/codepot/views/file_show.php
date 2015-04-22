<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/file.css')?>" />
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

<script type="text/javascript">
function render_wiki()
{
	creole_render_wiki (
		"file_show_mainarea_wiki_text", 
		"file_show_mainarea_wiki", 
		"<?php print site_url()?>/wiki/show/<?php print $project->id?>/",
		"<?php print site_url()?>/wiki/attachment0/<?php print $project->id?>/"
	);

	prettyPrint ();
}

$(function () {
	if ($("#file_show_mainarea_result_info").is(":visible"))
		btn_label = "<?php print $this->lang->line('Hide metadata')?>";
	else
		btn_label = "<?php print $this->lang->line('Show metadata')?>";


	btn = $("#file_show_mainarea_metadata_button").button({"label": btn_label}).click (function () {
		
		if ($("#file_show_mainarea_result_info").is(":visible"))
		{
			$("#file_show_mainarea_result_info").hide("blind",{},200);
			$("#file_show_mainarea_metadata_button").button(
				"option", "label", "<?php print $this->lang->line('Show metadata')?>");
		}
		else
		{
			$("#file_show_mainarea_result_info").show("blind",{},200);
			$("#file_show_mainarea_metadata_button").button(
				"option", "label", "<?php print $this->lang->line('Hide metadata')?>");
		}
	});

	render_wiki ();
});

</script>

<title><?php print htmlspecialchars($project->name)?> - <?php print htmlspecialchars($file->name)?></title>
</head>

<body>

<div class="content" id="file_show_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$hexname = $this->converter->AsciiToHex ($file->name);
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,

		'page' => array (
			'type' => 'project',
			'id' => 'file',
			'project' => $project,
		),

		'ctxmenuitems' => array (
			array ("file/create/{$project->id}", $this->lang->line('New')),
			array ("file/update/{$project->id}/{$hexname}", $this->lang->line('Edit')),
			array ("file/delete/{$project->id}/{$hexname}", $this->lang->line('Delete'))
		)
	)
);
?>

<!---------------------------------------------------------------------------->


<div class="mainarea" id="file_show_mainarea">
<div class="title"><?php print htmlspecialchars($file->name)?></div>

<div class="infostrip" id="wiki_show_mainarea_infostrip">
	<?php 
		$download_anchor_text = '<i class="fa fa-download"></i> ' . $this->lang->line('Download');
		print  anchor ("file/get/{$project->id}/". $this->converter->AsciiToHex($file->name), $download_anchor_text);
	?>
	| <a id="file_show_mainarea_metadata_button" href='#'><?php print $this->lang->line('Metadata')?></a>
</div>

<div id="file_show_mainarea_result">


<div class="result" id="file_show_mainarea_wiki">
<pre id="file_show_mainarea_wiki_text" style="visibility: hidden">
<?php print htmlspecialchars($file->description); ?>
</pre>
</div> <!-- file_show_mainarea_wiki -->



<div id="file_show_mainarea_result_info" class="infobox">

	<ul>
	<li><?php print $this->lang->line('Created on')?> <?php print  $file->createdon ?></li>
	<li><?php print $this->lang->line('Created by')?> <?php print  $file->createdby ?></li>
	<li><?php print $this->lang->line('Last updated on')?> <?php print  $file->updatedon ?></li>
	<li><?php print $this->lang->line('Last updated by')?> <?php print  $file->updatedby ?></li>
	</ul>

	<div class="title"><?php print $this->lang->line('MD5')?></div>
	<ul>
	<li><?php print  $file->md5sum ?></li>
	</ul>

</div> <!-- file_show_mainarea_result_info -->



</div> <!-- file_show_mainarea_result -->

</div> <!-- file_show_mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  file_show_content -->

</body>

</html>

