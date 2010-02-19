<div class="projectbar">

<?php
function show_projectbar ($con, $site, $project, $pageid, $ctxmenuitems)
{
	print "<div class='title'>";

	if (isset($project)) print $project->id;
	else if (isset($site) && $site->name != '') print htmlspecialchars($site->name);
	else print htmlspecialchars(CODEPOT_DEFAULT_BANNER);

	print "</div>";

	print '<div class="ctxmenu">';
	if ($ctxmenuitems !== NULL && count($ctxmenuitems) > 0)
	{
		foreach ($ctxmenuitems as $item)
		{
        		print anchor ($item[0], $item[1]);
		}
	}
	else print '&nbsp;';
	print '</div>';

	print '<div class="fixedmenu">';

	if (isset($project))
	{
		$menuitems = array (
			array ("project/home/{$project->id}", $con->lang->line('Overview')),
			array ("wiki/home/{$project->id}", $con->lang->line('Wiki')),
			array ("source/home/{$project->id}", $con->lang->line('Source')),
			array ("file/home/{$project->id}", $con->lang->line('Files'))
		);

		$langcodes = array (
			"english"    => "en",
			"indonesian" => "id",
			"korean"     => "ko"
		);

		$lang = $langcodes[CODEPOT_LANG];
		$websvn = base_url() . "websvn/listing.php?langchoice={$lang}&repname={$project->id}";
		foreach ($menuitems as $item)
		{
			$menuid = substr ($item[0], 0, strpos($item[0], '/'));
			$extra = ($menuid == $pageid)? 'class="selected"': '';
			$menulink = $item[0];

			if ($menuid == 'source')
			{
				if (CODEPOT_ENABLE_WEBSVN === TRUE ||
				    !function_exists('svn_ls')) $menulink = $websvn;
			}

			print anchor ($menulink, $item[1], $extra);
		}
	}
	else print '&nbsp;';

	print '</div>';
}

show_projectbar ($this, $site, $project, $pageid, $ctxmenuitems);
?>

</div>
