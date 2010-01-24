<?php

class WikiModel extends Model
{
	function WikiModel ()
	{
		parent::Model ();
		$this->load->database ();
	}

	function get ($userid, $project, $name)
	{
		$this->db->trans_start ();
		$this->db->where ('projectid', $project->id);
		$this->db->where ('name', $name);
		$query = $this->db->get ('wiki');
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;

		$result = $query->result ();
		return empty($result)? NULL: $result[0];
	}

	function getAll ($userid, $project)
	{
		$this->db->trans_start ();
		$this->db->where ('projectid', $project->id);
		$query = $this->db->get ('wiki');
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;
		return $query->result ();
	}

	function create ($userid, $wiki)
	{
		// TODO: check if userid can do this..
		$this->db->trans_start ();
		$this->db->set ('projectid', $wiki->projectid);
		$this->db->set ('name', $wiki->name);
		$this->db->set ('text', $wiki->text);
		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('createdby', $userid);
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('wiki');
		$this->db->trans_complete ();
                return $this->db->trans_status();
	}

	function update ($userid, $wiki)
	{
		// TODO: check if userid can do this..
		$this->db->trans_start ();
		$this->db->where ('projectid', $wiki->projectid);
		$this->db->where ('name', $wiki->name);
		$this->db->set ('text', $wiki->text);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);
		$this->db->update ('wiki');
		$this->db->trans_complete ();
                return $this->db->trans_status();
	}

	function delete ($userid, $wiki)
	{
		// TODO: check if userid can do this..
		$this->db->trans_start ();
		$this->db->where ('projectid', $wiki->projectid);
		$this->db->where ('name', $wiki->name);
		$this->db->delete ('wiki');
		$this->db->trans_complete ();
                return $this->db->trans_status();
	}

}

?>
