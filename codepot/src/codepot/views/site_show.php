<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/site.css')?>" />

<script type="text/javascript" src="<?=base_url_make('/js/creole.js')?>"></script>

<script type="text/javascript">
function render_wiki()
{
	creole_render_wiki (
		"site_show_mainarea_wiki_text",
		"site_show_mainarea_wiki",
		"<?=site_url()?>/site/wiki/",
		"<?=site_url()?>/site/image/"
	);
}
</script>

<?php
?>
<title><?=htmlspecialchars($site->name)?> (<?=$site->id?>)</title>
</head>

<body onload="render_wiki()">

<div class="content" id="site_show_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php

if ($login['sysadmin?'])
{
	$ctxmenuitems = array (
		//array ("site/create", $this->lang->line('New')),
		array ("site/update/{$site->id}", $this->lang->line('Edit')),
		array ("site/delete/{$site->id}", $this->lang->line('Delete'))
	);
}
else $ctxmenuitems = array ();

$this->load->view (
        'projectbar',
        array (
		'banner' => $this->lang->line('Administration'),

		'page' => array (
			'type' => 'site',
			'id' => 'catalog',
			'site' => $site,
                ),

                'ctxmenuitems' => $ctxmenuitems
        )
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="site_show_mainarea">

<div class="title">
<?=htmlspecialchars($site->name)?> (<?=htmlspecialchars($site->id)?>)
</div>

<div id="site_show_mainarea_wiki">
<pre id="site_show_mainarea_wiki_text" style="visibility: hidden">
<?php print htmlspecialchars($site->text); ?>
</pre>
</div> <!-- site_show_mainarea_text -->

<!----------------------------------------------------------->

</div> <!-- site_show_mainarea -->

<?php $this->load->view ('footer'); ?>

</div> <!-- site_show_content -->

</body>
</html>
