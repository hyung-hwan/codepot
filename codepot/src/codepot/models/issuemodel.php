<?php

class IssueModel extends Model
{
	protected $errmsg = '';

	function capture_error ($errno, $errmsg)
	{
		$this->errmsg = $errmsg;
	}

	function getErrorMessage ()
	{
		return $this->errmsg;
	}

	function IssueModel ()
	{
		parent::Model ();
		$this->load->database ();
	}

	function get ($userid, $project, $id)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->where ('projectid', $project->id);
		$this->db->where ('id', $id);
		$query = $this->db->get ('issue');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		$result = $query->result ();
		if (empty($result))
		{
			$this->db->trans_commit ();
			return NULL;
		}

		$this->db->where ('projectid', $project->id);
		$this->db->where ('issueid', $id);
		$query = $this->db->get ('issue_file_list');
		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}
		$files = $query->result();

		$this->db->where ('projectid', $project->id);
		$this->db->where ('id', $id);
		$this->db->order_by ('sno', 'asc');
		$query = $this->db->get ('issue_change');
		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}
		$changes = $query->result();

		$this->db->trans_commit ();

		$result[0]->changes = $changes;
		$result[0]->files = $files;
		return $result[0];
	}

	function getNumEntries ($userid, $project, $search)
	{
		$this->db->trans_start ();

		$this->db->where ('projectid', $project->id);
		if ($search->type != '') $this->db->where ('type', $search->type);
		if ($search->status != '') $this->db->where ('status', $search->status);
		if ($search->priority != '') $this->db->where ('priority', $search->priority);
		if ($search->owner != '') $this->db->like ('owner', $search->owner);
		if ($search->summary != '') $this->db->like ('summary', $search->summary);
		$this->db->select ('count(*) as count');
		$query = $this->db->get ('issue');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_complete ();
			return FALSE;
		}
		
		$result = $query->result();
		
		$num = empty($result)? 0: 
		       isset($result[0]->COUNT)? $result[0]->COUNT: $result[0]->count;

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $num;
	}

	function getEntries ($userid, $offset, $limit, $project, $search)
	{
		$this->db->trans_start ();

		$this->db->where ('projectid', $project->id);
		if ($search->type != '') $this->db->where ('type', $search->type);
		if ($search->status != '') $this->db->where ('status', $search->status);
		if ($search->priority != '') $this->db->where ('priority', $search->priority);
		if ($search->owner != '') $this->db->like ('owner', $search->owner);
		if ($search->summary != '') $this->db->like ('summary', $search->summary);
		$this->db->order_by ('id', 'desc');
		$query = $this->db->get ('issue', $limit, $offset);
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;
		return $query->result ();
	}

	function getAll ($userid, $project)
	{
		$this->db->trans_start ();
		$this->db->where ('projectid', $project->id);
		$this->db->order_by ('id', 'desc');
		$query = $this->db->get ('issue');
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;
		return $query->result ();
	}

	function getMyIssues ($userid, $filter, $hour_limit = 0)
	{
		$this->db->trans_start ();
		if (strlen($userid) > 0) $this->db->where ('owner', $userid);

		if (is_array($filter))
		{
			$this->db->where_in ('status', array_keys($filter));
		}

		if ($hour_limit > 0)
		{
			//$this->db->where ("updatedon >= SYSDATE() - INTERVAL {$hour_limit} HOUR");
			$this->db->where ("updatedon >= CURRENT_TIMESTAMP - INTERVAL '{$hour_limit}' HOUR");
		}

		if (strlen($userid) > 0) 
		{
			$this->db->order_by ('id', 'desc');
		}
		else
		{
			$this->db->order_by ('updatedon', 'desc');
		}

		$query = $this->db->get ('issue');
		$this->db->trans_complete ();


		if ($this->db->trans_status() === FALSE) return FALSE;
		return $query->result ();
	}

	function getFile ($userid, $project, $issueid, $filename)
	{
		$this->db->trans_start ();

		$this->db->select ('filename,encname,md5sum,description,createdon,createdby');
		$this->db->where ('projectid', $project->id);
		$this->db->where ('issueid', $issueid);
		$this->db->where ('filename', $filename);

		$query = $this->db->get ('issue_file_list');
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;
		$result = $query->result ();
		if (empty($result)) return NULL;

		return $result[0];
	}

	function create ($userid, $issue)
	{
		// TODO: check if userid can do this..
		$this->db->trans_start ();

		$this->db->where ('projectid', $issue->projectid);
		$this->db->select ('MAX(id) as maxid');
		$query = $this->db->get ('issue');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$result = $query->result();
		$maxid = (empty($result) || $result[0] == NULL)? 0: $result[0]->maxid;

		$newid = $maxid + 1;

		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('id', $newid);
		$this->db->set ('summary', $issue->summary);
		$this->db->set ('description', $issue->description);
		$this->db->set ('type', $issue->type);
		$this->db->set ('status', $issue->status);
		$this->db->set ('owner', $issue->owner);
		$this->db->set ('priority', $issue->priority);
		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('createdby', $userid);
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('issue');

		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('id', $newid);
		$this->db->set ('sno', 1);
		$this->db->set ('type', $issue->type);
		$this->db->set ('status', $issue->status);
		$this->db->set ('owner', $issue->owner);
		$this->db->set ('comment', '');
		$this->db->set ('priority', $issue->priority);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('issue_change');

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'issue');
		$this->db->set ('action',    'create');
		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $newid);
		$this->db->insert ('log');

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $newid;
	}

	function update_partial ($userid, $issue)
	{
		$this->db->trans_start ();
		$this->db->where ('projectid', $issue->projectid);
		$this->db->where ('id', $issue->id);
		$this->db->set ('summary', $issue->summary);
		$this->db->set ('description', $issue->description);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);
		$this->db->update ('issue');

                $this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'issue');
		$this->db->set ('action',    'update');
		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $issue->id);
		$this->db->insert ('log');

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $issue->id;
	}

	function update ($userid, $issue)
	{
		// TODO: check if userid can do this..
		$this->db->trans_start ();
		$this->db->where ('projectid', $issue->projectid);
		$this->db->where ('id', $issue->id);
		$this->db->set ('summary', $issue->summary);
		$this->db->set ('description', $issue->description);
		$this->db->set ('type', $issue->type);
		$this->db->set ('status', $issue->status);
		$this->db->set ('owner', $issue->owner);
		$this->db->set ('priority', $issue->priority);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);
		$this->db->update ('issue');

		$this->db->where ('projectid', $issue->projectid);
		$this->db->where ('id', $issue->id);
		$this->db->where ('sno', 1);
		$this->db->set ('type', $issue->type);
		$this->db->set ('status', $issue->status);
		$this->db->set ('owner', $issue->owner);
		$this->db->set ('priority', $issue->priority);
		$this->db->set ('comment', '');
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);
		$this->db->update ('issue_change');

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'issue');
		$this->db->set ('action',    'update');
		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $issue->id);
		$this->db->insert ('log');

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $issue->id;
	}

	function change ($userid, $project, $id, $change)
	{
		$this->db->trans_start ();

		$this->db->where ('projectid', $project->id);
		$this->db->where ('id', $id);
		$this->db->select ('MAX(sno) as maxsno');
		$query = $this->db->get ('issue_change');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_complete ();
			return FALSE;
		}
		$result = $query->result();
		$maxsno = (empty($result) || $result[0] == NULL)? 0: $result[0]->maxsno;
		$newsno = $maxsno + 1;

		$this->db->set ('projectid', $project->id);
		$this->db->set ('id', $id);
		$this->db->set ('sno', $newsno);
		$this->db->set ('type', $change->type);
		$this->db->set ('status', $change->status);
		$this->db->set ('owner', $change->owner);
		$this->db->set ('priority', $change->priority);
		$this->db->set ('comment', $change->comment);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('issue_change');

		$this->db->where ('projectid', $project->id);
		$this->db->where ('id', $id);
		$this->db->set ('type', $change->type);
		$this->db->set ('status', $change->status);
		$this->db->set ('owner', $change->owner);
		$this->db->set ('priority', $change->priority);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);
		$this->db->update ('issue');

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'issue');
		$this->db->set ('action',    'change');
		$this->db->set ('projectid', $project->id);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $id);
		$this->db->insert ('log');

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $id;
	}

	function undo_last_change ($userid, $project, $id)
	{
		$this->db->trans_start ();

		$this->db->where ('projectid', $project->id);
		$this->db->where ('id', $id);
		$this->db->select ('MAX(sno) as maxsno');
		$query = $this->db->get ('issue_change');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_complete ();
			return FALSE;
		}
		$result = $query->result();
		if (!empty($result))
		{
			$maxsno = $result[0]->maxsno;
			if ($maxsno > 1)
			{	
				$this->db->where ('projectid', $project->id);
				$this->db->where ('id', $id);
				$this->db->where ('sno', $maxsno);
				$this->db->delete ('issue_change');

				$this->db->where ('projectid', $project->id);
				$this->db->where ('id', $id);
				$this->db->select ('MAX(sno) as maxsno');
				$query = $this->db->get ('issue_change');
				if ($this->db->trans_status() === FALSE) 
				{
					$this->db->trans_complete ();
					return FALSE;
				}
				$result = $query->result();
				if (!empty($result))
				{
					$maxsno = $result[0]->maxsno;
					$this->db->where ('projectid', $project->id);
					$this->db->where ('id', $id);
					$this->db->where ('sno', $maxsno);
					$query = $this->db->get ('issue_change');
					if ($this->db->trans_status() === FALSE) 
					{
						$this->db->trans_complete ();
						return FALSE;
					}
					$result = $query->result();
					if (!empty($result))
					{
						$change = $result[0];
						$this->db->where ('projectid', $project->id);
						$this->db->where ('id', $id);
						$this->db->set ('type', $change->type);
						$this->db->set ('status', $change->status);
						$this->db->set ('owner', $change->owner);
						$this->db->set ('priority', $change->priority);
						$this->db->set ('updatedon', $change->updatedon);
						$this->db->set ('updatedby', $change->updatedby);
						$this->db->update ('issue');
					}
				}
			}
		}

		$this->db->trans_complete ();
                return $this->db->trans_status();
	}

	function delete ($userid, $issue)
	{
		// TODO: check if userid can do this..
		$this->db->trans_start ();

		$this->db->where ('projectid', $issue->projectid);
		$this->db->where ('id', $issue->id);
		$this->db->delete ('issue_change');

		$this->db->where ('projectid', $issue->projectid);
		$this->db->where ('id', $issue->id);
		$this->db->delete ('issue');

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'issue');
		$this->db->set ('action',    'delete');
		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $issue->id);
		$this->db->insert ('log');

		$this->db->trans_complete ();
		return $this->db->trans_status();
	}

	
	private function delete_all_files ($files)
	{
		foreach ($files as $f) @unlink ($f);
	}

	private function _create_issue ($userid, $issue, $attached_files, $uploader)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->where ('projectid', $issue->projectid);
		$this->db->select ('MAX(id) as maxid');
		$query = $this->db->get ('issue');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$result = $query->result();
		$maxid = (empty($result) || $result[0] == NULL)? 0: $result[0]->maxid;

		$newid = $maxid + 1;

		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('id', $newid);
		$this->db->set ('summary', $issue->summary);
		$this->db->set ('description', $issue->description);
		$this->db->set ('type', $issue->type);
		$this->db->set ('status', $issue->status);
		$this->db->set ('owner', $issue->owner);
		$this->db->set ('priority', $issue->priority);
		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('createdby', $userid);
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('issue');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('id', $newid);
		$this->db->set ('sno', 1);
		$this->db->set ('type', $issue->type);
		$this->db->set ('status', $issue->status);
		$this->db->set ('owner', $issue->owner);
		$this->db->set ('comment', '');
		$this->db->set ('priority', $issue->priority);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('issue_change');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$config['allowed_types'] = '*';
		$config['upload_path'] = CODEPOT_ISSUE_FILE_DIR;
		$config['max_size'] = CODEPOT_MAX_UPLOAD_SIZE;
		$config['encrypt_name'] = TRUE;
		$config['overwrite'] = FALSE;
		$config['remove_spaces'] = FALSE;
		$uploader->initialize ($config);

		$ok_files = array();
		$file_count = count($attached_files);
		for ($i = 0; $i < $file_count; $i++)
		{
			$f = $attached_files[$i];
			if (!$uploader->do_upload($f['fid']))
			{
				$this->errmsg = "Failed to upload {$f['name']}";
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}

			$ud = $uploader->data();
			array_push ($ok_files, $ud['full_path']);

			$md5sum = @md5_file ($ud['full_path']);
			if ($md5sum === FALSE)
			{
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}

			$this->db->set ('projectid', $issue->projectid);
			$this->db->set ('issueid', $newid);
			$this->db->set ('filename', $f['name']);
			$this->db->set ('encname', $ud['file_name']);
			$this->db->set ('description', $f['desc']);
			$this->db->set ('md5sum', $md5sum);
			$this->db->insert ('issue_file_list');
			if ($this->db->trans_status() === FALSE)
			{
				$this->errmsg = $this->db->_error_message(); 
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}
		}

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'issue');
		$this->db->set ('action',    'create');
		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $newid);
		$this->db->insert ('log');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			$this->delete_all_files ($ok_files);
			return FALSE;
		}

		$this->db->trans_commit ();
		return $newid;
	}

	function createWithFiles ($userid, $issue, $attached_files, $uploader)
	{
		set_error_handler (array ($this, 'capture_error'));
		$errmsg = '';
		$x = $this->_create_issue ($userid, $issue, $attached_files, $uploader);
		restore_error_handler ();
		return $x;
	}

	function updateSummaryAndDescription ($userid, $issue)
	{
		// TODO: check if userid can do this..
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->trans_start ();
		$this->db->where ('projectid', $issue->projectid);
		$this->db->where ('id', $issue->id);
		$this->db->set ('summary', $issue->summary);
		$this->db->set ('description', $issue->description);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);
		$this->db->update ('issue');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'issue');
		$this->db->set ('action',    'update');
		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $issue->id);
		$this->db->insert ('log');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->trans_commit ();
		return $issue->id;
	}

	private function _delete_issue ($userid, $projectid, $issueid)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->where ('projectid', $projectid);
		$this->db->where ('issueid', $issueid);
		$query = $this->db->get ('issue_file_list');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$result = $query->result ();
		$file_names = array ();
		foreach ($result as $f)
		{
			array_push ($file_names, $f->encname);
		}

		$this->db->where ('projectid', $projectid);
		$this->db->where ('issueid', $issueid);
		$this->db->delete ('issue_file_list');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->where ('projectid', $projectid);
		$this->db->where ('id', $issueid);
		$this->db->delete ('issue_change');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->where ('projectid', $projectid);
		$this->db->where ('id', $issueid);
		$this->db->delete ('issue');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'issue');
		$this->db->set ('action',    'delete');
		$this->db->set ('projectid', $projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $issueid);
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$file_name_count = count($file_names);
		for ($i = 0; $i < $file_name_count; $i++)
		{
			$encname = $file_names[$i];
			$path = CODEPOT_ISSUE_FILE_DIR . '/' . $encname;
			if (@unlink ($path) === FALSE)
			{
				if ($i == 0)
				{
					$this->db->trans_rollback ();
					return FALSE;
				}
				else
				{
					// there is no good way to recover from the error.
					// carry on. some files will get orphaned.
				}
			}
		}

		$this->db->trans_commit ();
		return TRUE;
	}

	function deleteWithFiles ($userid, $projectid, $issueid)
	{
		set_error_handler (array ($this, 'capture_error'));
		$errmsg = '';
		$x = $this->_delete_issue ($userid, $projectid, $issueid);
		restore_error_handler ();
		return $x;
	}


	private function _add_files ($userid, $projectid, $issueid, $add_files, $uploader)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$config['allowed_types'] = '*';
		$config['upload_path'] = CODEPOT_ISSUE_FILE_DIR;
		$config['max_size'] = CODEPOT_MAX_UPLOAD_SIZE;
		$config['encrypt_name'] = TRUE;
		$config['overwrite'] = FALSE;
		$config['remove_spaces'] = FALSE;
		$uploader->initialize ($config);

		$ok_files = array();
		$file_count = count($add_files);
		for ($i = 0; $i < $file_count; $i++)
		{
			$f = $add_files[$i];
			if (!$uploader->do_upload($f['fid']))
			{
				$this->errmsg = "Failed to upload {$f['name']}";
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}

			$ud = $uploader->data();
			array_push ($ok_files, $ud['full_path']);

			$md5sum = @md5_file ($ud['full_path']);
			if ($md5sum === FALSE)
			{
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}

			$this->db->set ('projectid', $projectid);
			$this->db->set ('issueid', $issueid);
			$this->db->set ('filename', $f['name']);
			$this->db->set ('encname', $ud['file_name']);

			$this->db->set ('md5sum', $md5sum);
			$this->db->set ('description', $f['desc']);
			$this->db->insert ('issue_file_list');
			if ($this->db->trans_status() === FALSE)
			{
				$this->errmsg = $this->db->_error_message(); 
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}
		}

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'issue');
		$this->db->set ('action',    'update');
		$this->db->set ('projectid', $projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $issueid);
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			$this->delete_all_files ($ok_files);
			return FALSE;
		}

		$this->db->trans_commit ();
		return TRUE;
	}

	function addFiles ($userid, $projectid, $issueid, $add_files, $uploader)
	{
		set_error_handler (array ($this, 'capture_error'));
		$errmsg = '';
		$x = $this->_add_files ($userid, $projectid, $issueid, $add_files, $uploader);
		restore_error_handler ();
		return $x;
	}

	private function _edit_files ($userid, $projectid, $issueid, $edit_files)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$kill_files = array();
		$file_count = count($edit_files);
		for ($i = 0; $i < $file_count; $i++)
		{
			$f = $edit_files[$i];

			if (array_key_exists('kill', $f))
			{
				$this->db->where ('projectid', $projectid);
				$this->db->where ('issueid', $issueid);
				$this->db->where ('filename', $f['name']);
				$this->db->select ('encname');
				$query = $this->db->get('issue_file_list');
				if ($this->db->trans_status() === FALSE)
				{
					$this->errmsg = $this->db->_error_message(); 
					$this->db->trans_rollback ();
					return FALSE;
				}

				$result = $query->result ();
				if (empty($result)) 
				{
					$this->errmsg = "no such file - {$f['name']}";
					$this->db->trans_rollback ();
					return FALSE;
				}

				array_push ($kill_files, CODEPOT_ISSUE_FILE_DIR . '/' . $result[0]->encname);

				$this->db->where ('projectid', $projectid);
				$this->db->where ('issueid', $issueid);
				$this->db->where ('filename', $f['name']);
				$query = $this->db->delete('issue_file_list');
				if ($this->db->trans_status() === FALSE)
				{
					$this->errmsg = $this->db->_error_message(); 
					$this->db->trans_rollback ();
					return FALSE;
				}
			}
			else if (array_key_exists('desc', $f))
			{
				$this->db->where ('projectid', $projectid);
				$this->db->where ('issueid', $issueid);
				$this->db->where ('filename', $f['name']);
				$this->db->set ('description', $f['desc']);
				$this->db->update ('issue_file_list');
				if ($this->db->trans_status() === FALSE)
				{
					$this->errmsg = $this->db->_error_message(); 
					$this->db->trans_rollback ();
					return FALSE;
				}
			}
		}

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'issue');
		$this->db->set ('action',    'update');
		$this->db->set ('projectid', $projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $issueid);
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->delete_all_files ($kill_files);
		$this->db->trans_commit ();
		return TRUE;
	}

	function editFiles ($userid, $projectid, $issueid, $edit_files)
	{
		set_error_handler (array ($this, 'capture_error'));
		$errmsg = '';
		$x = $this->_edit_files ($userid, $projectid, $issueid, $edit_files);
		restore_error_handler ();
		return $x;
	}

	function isIssueCreatedBy ($projectid, $issueid, $userid)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->where ('projectid', $projectod);
		$this->db->where ('id', $issueid);
		$query = $this->db->get ('issue');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		$result = $query->result ();
		if (empty($result))
		{
			$this->db->trans_commit ();
			return FALSE;
		}

		$this->db->trans_commit ();

		$issue = &$result[0];
		return ($issue->created_by == $userid);
	}
}

?>
