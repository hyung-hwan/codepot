<?php

class IssueModel extends Model
{
	function IssueModel ()
	{
		parent::Model ();
		$this->load->database ();
	}

	function get ($userid, $project, $id)
	{
		$this->db->trans_start ();
		$this->db->where ('projectid', $project->id);
		$this->db->where ('id', $id);
		$query = $this->db->get ('issue');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$result = $query->result ();
		if (empty($result))
		{
			$this->db->trans_complete ();
			return NULL;
		}

		$this->db->where ('projectid', $project->id);
		$this->db->where ('id', $id);
		$this->db->order_by ('sno', 'asc');
		$query = $this->db->get ('issue_change');

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		$changes = $query->result();

		$result[0]->changes = $changes;
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
		$this->db->select ('count(id) as count');
		$query = $this->db->get ('issue');
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
			$this->db->where ("updatedon >= SYSDATE() - INTERVAL {$hour_limit} HOUR");
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

}

?>
