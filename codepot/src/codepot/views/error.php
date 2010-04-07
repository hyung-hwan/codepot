<html>

<?php if (!isset($title)) $title = $this->lang->line('Error'); ?>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><?=$title?></title>
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
<?= htmlspecialchars($title) ?>
</div>
<?= htmlspecialchars($message) ?>
</div>

<?php $this->load->view ('footer'); ?>

</div> <!-- project_error_content -->

</body>

</html>

