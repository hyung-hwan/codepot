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
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;

		$result = $query->result ();
		return empty($result)? NULL: $result[0];
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
		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('summary', $issue->summary);
		$this->db->set ('type', $issue->type);
		$this->db->set ('status', $issue->status);
		$this->db->set ('description', $issue->description);
		$this->db->set ('assignedto', $issue->assignedto);
		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('createdby', $userid);
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('issue');

                $this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'issue');
		$this->db->set ('action',    'create');
		$this->db->set ('projectid', $issue->projectid);
		$this->db->set ('userid',    $userid);
		//$this->db->set ('message',   'LAST_INSERT_ID()');
		$this->db->set ('message',   'CONVERT(LAST_INSERT_ID(),CHAR)');
                $this->db->insert ('log');

		$this->db->trans_complete ();
                return $this->db->trans_status();
	}

	function update ($userid, $issue)
	{
		// TODO: check if userid can do this..
		$this->db->trans_start ();
		$this->db->where ('projectid', $issue->projectid);
		$this->db->where ('id', $issue->id);
		$this->db->set ('summary', $issue->summary);
		$this->db->set ('type', $issue->type);
		$this->db->set ('status', $issue->status);
		$this->db->set ('description', $issue->description);
		$this->db->set ('assignedto', $issue->assignedto);
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
                return $this->db->trans_status();
	}

	function delete ($userid, $issue)
	{
		// TODO: check if userid can do this..
		$this->db->trans_start ();
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
