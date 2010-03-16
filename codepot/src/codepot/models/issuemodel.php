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
		$this->db->order_by ('sno', 'desc');
		$query = $this->db->get ('issue_change');

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		$changes = $query->result();

		$result[0]->changes = $changes;
		return $result[0];
	}

	function getAll ($userid, $project)
	{
		$this->db->trans_start ();
		$this->db->where ('projectid', $project->id);
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

		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('id', $issue->id);
		$this->db->set ('sno', 1);
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

	function delete ($userid, $issue)
	{
		// TODO: check if userid can do this..
		$this->db->trans_start ();
		$this->db->where ('projectid', $issue->projectid);
		$this->db->where ('id', $issue->id);
		$this->db->delete ('issue');

		$this->db->where ('projectid', $issue->projectid);
		$this->db->where ('id', $issue->id);
		$this->db->delete ('issue_change');

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
