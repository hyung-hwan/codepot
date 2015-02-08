<?php

class ProjectModel extends Model
{
	function ProjectModel ()
	{
		parent::Model ();
		$this->load->database ();
	}

	function get ($id)
	{
		$this->db->trans_start ();

		$this->db->where ('id', (string)$id);
		$query = $this->db->get ('project');
		//if ($query === FALSE) 
		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$result = $query->result ();
		if (empty($result))
		{
			$this->db->trans_complete ();
			if ($this->db->trans_status() === FALSE) return FALSE;
			return NULL;
		}

		$this->db->select ('userid');
		$this->db->where ('projectid', (string)$id); 
		$this->db->order_by ('priority', 'asc');
		$query2 = $this->db->get ('project_membership');

		/*
		$members = '';
		foreach ($query2->result() as $a)
		{
			if ($members !== '') $members .= ',';
			$members .= $a->userid;
		}
		*/
		$members = array ();
		foreach ($query2->result() as $a) array_push ($members, $a->userid);
		$result[0]->members = $members;

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $result[0];
	}

	function getNumEntries ($userid, $search)
	{
		$this->db->trans_start ();

		$this->db->select ('count(id) as count');
		$this->db->order_by ('name', 'asc');
		if ($search->id != '') $this->db->like ('id', $search->id);
		if ($search->name != '') $this->db->like ('name', $search->name);
		if ($search->summary != '') $this->db->like ('summary', $search->summary);

		$query = $this->db->get ('project');
		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$result = $query->result();
		
		$num = empty($result)? 0: $result[0]->count;

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $num;
	}

	function getEntries ($userid, $offset, $limit, $search)
	{
		$this->db->trans_start ();
		$this->db->order_by ('name', 'asc');
		if ($search->id != '') $this->db->like ('id', $search->id);
		if ($search->name != '') $this->db->like ('name', $search->name);
		if ($search->summary != '') $this->db->like ('summary', $search->summary);
		$query = $this->db->get ('project', $limit, $offset);
		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;
		return $query->result ();
	}


	function create ($userid, $project, $api_base_url)
	{
		// TODO: check if userid can do this..

		$this->db->trans_begin ();

		$this->db->set ('id', $project->id);
		$this->db->set ('name', $project->name);
		$this->db->set ('summary', $project->summary);
		$this->db->set ('description', $project->description);
		$this->db->set ('commitable', $project->commitable);
		$this->db->set ('public', $project->public);
		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('createdby', $userid);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('project');

		$this->db->where ('projectid', $project->id);
		$this->db->delete ('project_membership');

		//$members = preg_split ('/[[:space:],]+/', $project->members);
		$members = $project->members;
		$member_count = count ($members);
		$members = array_unique ($members);
		$priority = 0;
		for ($i = 0; $i < $member_count; $i++)
		{
			if (!array_key_exists($i, $members)) continue;

			$m = $members[$i];
			if ($m == '') continue;

			$this->db->set ('projectid', $project->id);
			$this->db->set ('userid', $m);
			$this->db->set ('priority', ++$priority);
			$this->db->insert ('project_membership');
		}

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'project');
		$this->db->set ('action',    'create');
		$this->db->set ('projectid', $project->id);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $project->name);
		$this->db->insert ('log');

		if ($priority <= 0 || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}
		else
		{
			$url = parse_url(base_url());
			$api = $api_base_url . $url['path'] . '/' . CODEPOT_INDEX_PAGE . '/api';

			$cfgdir = CODEPOT_CFG_DIR;
			$repodir = CODEPOT_SVNREPO_DIR;

			// create repository
			if (@svn_repos_create ("{$repodir}/{$project->id}") === FALSE)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}

			$hooks = array (
				"pre-commit",
				"start-commit",
				"post-commit",
				"pre-revprop-change",
				"post-revprop-change"
			);

			foreach ($hooks as $hook)
			{
				// copy hook scripts to the top repository directory
				// overwriting existing scripts are ok as they are 
				// just updated to the latest scripts anyway.
				$contents = @file_get_contents("{$cfgdir}/${hook}");
				if ($contents === FALSE)
				{
					$this->deleteDirectory ("{$repodir}/{$project->id}");
					$this->db->trans_rollback ();
					return FALSE;
				}

				if (@file_put_contents (
					"{$repodir}/${hook}",
					str_replace('%API%', $api, $contents)) === FALSE)
				{
					$this->deleteDirectory ("{$repodir}/{$project->id}");
					$this->db->trans_rollback ();
					return FALSE;
				}

				// install the hook script to the new project repository
				if (@chmod ("{$repodir}/{$hook}", 0755) === FALSE ||
				    @symlink ("../../{$hook}", "{$repodir}/{$project->id}/hooks/${hook}") === FALSE)
				{
					$this->deleteDirectory ("{$repodir}/{$project->id}");
					$this->db->trans_rollback ();
					return FALSE;
				}
			}

			$this->db->trans_commit ();
			return TRUE;
		}
	}

	function update ($userid, $project)
	{
		// TODO: check if userid can do this..

		$this->db->trans_begin ();

		$this->db->where ('id', $project->id);
		$this->db->set ('name', $project->name);
		$this->db->set ('summary', $project->summary);
		$this->db->set ('description', $project->description);
		$this->db->set ('commitable', $project->commitable);
		$this->db->set ('public', $project->public);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);
		$this->db->update ('project');

		$this->db->where ('projectid', $project->id);
		$this->db->delete ('project_membership');

		//$members = preg_split ('/[[:space:],]+/', $project->members);
		$members = $project->members;
		$member_count = count ($members);
		$members = array_unique ($members);
		$priority = 0;

		for ($i = 0; $i < $member_count; $i++)
		{
			if (!array_key_exists($i, $members)) continue;

			$m = $members[$i];
			if ($m == '') continue;

			$this->db->set ('projectid', $project->id);
			$this->db->set ('userid', $m);
			$this->db->set ('priority', ++$priority);
			$this->db->insert ('project_membership');
		}

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'project');
		$this->db->set ('action',    'update');
		$this->db->set ('projectid', $project->id);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $project->name);
		$this->db->insert ('log');

		if ($priority <= 0 || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}
		else
		{
			$this->db->trans_commit ();
			return TRUE;
		}
	}

	function delete ($userid, $project, $force = FALSE)
	{
		// TODO: check if userid can do this..
		$this->db->trans_begin ();

		if ($force)
		{
			$this->db->where ('projectid', $project->id);
			$query = $this->db->get ('wiki_attachment');
			if ($this->db->trans_status() === FALSE)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}
			$wikiatts = $query->result ();

			$this->db->where ('projectid', $project->id);
			$this->db->delete ('wiki_attachment');

			$this->db->where ('projectid', $project->id);
			$this->db->delete ('wiki');

			$this->db->where ('projectid', $project->id);
			$this->db->delete ('issue_change');

			$this->db->where ('projectid', $project->id);
			$this->db->delete ('issue');

			$this->db->where ('projectid', $project->id);
			$this->db->delete ('code_review');

			$this->db->where ('projectid', $project->id);
			$query = $this->db->get ('file');
			if ($this->db->trans_status() === FALSE)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}

			$files = $query->result ();

			$this->db->where ('projectid', $project->id);
			$this->db->delete ('file');
		}

		$this->db->where ('id', $project->id);
		$this->db->delete ('project');

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'project');
		$this->db->set ('action',    'delete');
		$this->db->set ('projectid', $project->id);
                $this->db->set ('userid',    $userid);
                $this->db->set ('message',   $project->name);
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}
		else
		{
			$repodir = CODEPOT_SVNREPO_DIR;
			if ($this->deleteDirectory ("{$repodir}/{$project->id}") === FALSE)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}
			else
			{
				if ($force)
				{
					if (count($files) > 0)
					{
						// no way to roll back file delete.
						// so deletion is done here.
						$this->_delete_files (CODEPOT_FILE_DIR, $files);
					}

					if (count($wikiatts) > 0)
					{
						// no way to roll back attachment delete.
						// so deletion is done here.
						$this->_delete_files (CODEPOT_ATTACHMENT_DIR, $wikiatts);
					}
				}

				$this->db->trans_commit ();
				return TRUE;
			}
		}
	}

	function getMyProjects ($userid)
	{
		$this->db->trans_start ();

		$this->db->select ('project.*');
		$this->db->where ('project_membership.userid', (string)$userid);
		$this->db->join ('project_membership', 'project_membership.projectid = project.id');

		$query = $this->db->get ('project');

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;
		return $query->result ();
	}

	function getOtherProjects ($userid)
	{
		$this->db->trans_start ();

		$this->db->select ('project.*');
		$this->db->where ('project_membership.userid !=', (string)$userid);
		$this->db->join ('project_membership', 'project_membership.projectid = project.id');
		$this->db->distinct ();
		$query = $this->db->get ('project');

		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;
		return $query->result ();
	}

	function getLatestProjects ($userid, $limit)
	{
		$this->db->trans_start ();

		$this->db->limit ($limit);
		$this->db->order_by ('createdon', 'desc');	
		$query = $this->db->get ('project');

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;
		return $query->result ();
	}

	function _scandir ($dir)
	{
		$files = array ();

		$dh  = opendir($dir);
		while (false !== ($filename = readdir($dh)))
		{
			$files[] = $filename;
		}
		closedir ($dh);

		return $files;
	}

	function deleteDirectory($dir) 
	{
		if (is_link($dir)) return @unlink($dir);
		if (!file_exists($dir)) return TRUE;
		if (!is_dir($dir)) return @unlink($dir);

		foreach ($this->_scandir($dir) as $item) 
		{
			if ($item == '.' || $item == '..') continue;
			if ($this->deleteDirectory($dir . "/" . $item) === FALSE) 
			{
				chmod($dir . "/" . $item, 0777);
				if ($this->deleteDirectory($dir . "/" . $item) === FALSE) 
					return FALSE;
			};
		}

		return rmdir($dir);
	}

	function projectHasMember ($projectid, $userid)
	{
		$this->db->trans_start ();
		$this->db->where ('userid', $userid);
		$this->db->where ('projectid', $projectid);
		$count = $this->db->count_all_results ('project_membership');
		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;
		return ($count == 1)? TRUE: FALSE;
	}

	function projectIsOwnedBy ($projectid, $userid)
	{
		$this->db->trans_start ();
		$this->db->where ('userid', $userid);
		$this->db->where ('projectid', $projectid);
		$this->db->where ('priority', 1);
		$count = $this->db->count_all_results ('project_membership');
		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;
		return ($count == 1)? TRUE: FALSE;
	}

	function projectIsCommitable ($projectid)
	{
		$this->db->trans_start ();
		$this->db->where ('id', $projectid);
		$this->db->where ('commitable', 'Y');
		$count = $this->db->count_all_results ('project');
		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;
		return ($count == 1)? TRUE: FALSE;
	}

	function projectIsPublic ($projectid)
	{
		$this->db->trans_start ();
		$this->db->where ('id', $projectid);
		$this->db->where ('public', 'Y');
		$count = $this->db->count_all_results ('project');
		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;
		return ($count == 1)? TRUE: FALSE;
	}

	function _delete_files ($basedir, $files)
	{
		foreach ($files as $file)
			@unlink ($basedir . "/{$file->encname}");
	}

	function getUserIcons ($users)
	{
		$this->db->trans_start ();

		$this->db->select ('userid,icon_name');
		$this->db->where_in ('userid', $users);
		$this->db->where ('icon_name IS NOT NULL', null);

		$query = $this->db->get ('user_settings');
		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$out = array();
		$result = $query->result();
		
		if (!empty($result))
		{
			foreach ($result as $t) $out[$t->userid] = $t->icon_name;
		}

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $out;
	}
}

?>
