<html>

<?php if (!isset($title)) $title = $this->lang->line('Error'); ?>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/project.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<title><?php print $title?></title>
</head>

<body>

<div class="content" id="project_error_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
if (isset($project)) { $pagetype = 'project';  $pageobj = $project; }
else if (isset($site)) { $pagetype = 'site'; $pageobj = $site; }
else if (isset($user)) { $pagetype = 'user'; $pageobj = $user; }
else { $pagetype = ''; $pageobj = NULL; }

$this->load->view (
        'projectbar',
        array (
		'banner' => NULL,
		
		'page' => array (
			'type' => $pagetype,
			'id' => '',
			$pagetype => $pageobj
		),

                'ctxmenuitems' => array ()
        )
);
?>

<!---------------------------------------------------------------------------->
<div class="mainarea" id="project_error_mainarea">
<div class="title" id="project_error_title">
<?php print  htmlspecialchars($title) ?>
</div>
<?php print  htmlspecialchars($message) ?>
</div>

<?php $this->load->view ('footer'); ?>

</div> <!-- project_error_content -->

</body>

</html>

