<?php

class SiteModel extends Model
{
	function __construct ()
	{
		parent::__construct ();
		$this->load->database ();
	}

	function getDefault ()
	{
		$site = new stdClass();
		$site->id = CODEPOT_DEFAULT_SITE_LANGUAGE;
		$site->name = CODEPOT_DEFAULT_SITE_NAME;
		$site->summary = '';
		$site->text = '';
		$site->updatedby = '';
		$site->createdby = '';
		$site->updatedon = 0;
		$site->createdon = 0;
		return $site;
	}

	function get ($id)
	{
		$this->db->trans_start ();

		$this->db->where ('id', (string)$id);
		$query = $this->db->get ('site');

		//if ($query === FALSE)
		if ($this->db->trans_status() == FALSE)
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

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $result[0];
	}

	function getAll ($userid)
	{
		$this->db->trans_start ();
		$query = $this->db->get ('site');
		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;
		return $query->result ();
	}

	function create ($userid, $site)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->set ('id', $site->id);
		$this->db->set ('name', $site->name);
		$this->db->set ('summary', $site->summary);
		$this->db->set ('text', $site->text);
		$this->db->set ('createdon', codepot_nowtodbdate());
		$this->db->set ('createdby', $userid);
		$this->db->set ('updatedon', codepot_nowtodbdate());
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('site');

		if ($this->db->trans_status() === FALSE)
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

	function update ($userid, $site)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->where ('id', $site->id);
		$this->db->set ('name', $site->name);
		$this->db->set ('summary', $site->summary);
		$this->db->set ('text', $site->text);
		$this->db->set ('updatedon', codepot_nowtodbdate());
		$this->db->set ('updatedby', $userid);
		$this->db->update ('site');

		if ($this->db->trans_status() === FALSE)
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

	function delete ($userid, $site)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->where ('id', $site->id);
		$this->db->delete ('site');

		if ($this->db->trans_status() === FALSE)
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
}

?>
