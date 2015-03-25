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

		$this->db->select ('name,encname,createdon,createdby');
		$this->db->where ('projectid', $project->id);
		$this->db->where ('wikiname', $name);
		$this->db->order_by ('name', 'ASC');
		$query2 = $this->db->get ('wiki_attachment');

		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$this->db->trans_complete ();

		$wikis = $result[0];
		$wikis->attachments = $query2->result();

		return $wikis;
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

	function getAttachment ($userid, $project, $wikiname, $name)
	{
		$this->db->trans_start ();

		$this->db->select ('name,encname,createdon,createdby');
		$this->db->where ('projectid', $project->id);
		$this->db->where ('wikiname', $wikiname);
		$this->db->where ('name', $name);

		$query = $this->db->get ('wiki_attachment');
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;
		$result = $query->result ();
		if (empty($result)) return NULL;

		return $result[0];
	}

	function getAttachments ($userid, $project, $wikiname)
	{
		$this->db->trans_start ();

		$this->db->select ('name,encname,createdon,createdby');
		$this->db->where ('projectid', $project->id);
		$this->db->where ('wikiname', $wikiname);

		$query = $this->db->get ('wiki_attachment');
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;
		$result = $query->result ();

		return $result;
	}

	function create ($userid, $wiki)
	{
		// TODO: check if userid can do this..
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$now = date('Y-m-d H:i:s');

		$this->db->set ('projectid', $wiki->projectid);
		$this->db->set ('name', $wiki->name);
		$this->db->set ('text', $wiki->text);
		$this->db->set ('createdon', $now);
		$this->db->set ('updatedon', $now);
		$this->db->set ('createdby', $userid);
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('wiki');

		foreach ($wiki->delete_attachments as $att)
		{
			$this->db->where ('projectid', $wiki->projectid);
			$this->db->where ('wikiname', $wiki->name);
			$this->db->where ('name', $att->name);
			$this->db->where ('encname', $att->encname);
			$this->db->delete ('wiki_attachment');

			if ($this->db->trans_status() === FALSE)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}

			if ($this->db->affected_rows() <= 0)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}
		}

		foreach ($wiki->new_attachments as $att)
		{
			$this->db->set ('projectid', $wiki->projectid);
			$this->db->set ('wikiname', $wiki->name);
			$this->db->set ('name', $att['name']);
			$this->db->set ('encname', $att['encname']);
			$this->db->set ('createdon', $now);
			$this->db->set ('createdby', $userid);
			$this->db->insert ('wiki_attachment');
		}

		$this->db->set ('createdon', $now);
		$this->db->set ('type',      'wiki');
		$this->db->set ('action',    'create');
		$this->db->set ('projectid', $wiki->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $wiki->name);
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->trans_commit ();
		return TRUE;
	}

	function update ($userid, $wiki, $new_wiki_name = NULL)
	{
		// TODO: check if userid can do this..
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$now = date('Y-m-d H:i:s');

		if (!is_null($new_wiki_name) && $wiki->name != $new_wiki_name)
		{
			// there is a change in name.
			// rename the wiki document and its attachments

			// check if the new name exists.
			$this->db->where ('projectid', $wiki->projectid);
			$this->db->where ('name', $new_wiki_name);
			$query = $this->db->get ('wiki');
			if ($this->db->trans_status() === FALSE) 
			{
				$this->db->trans_rollback ();
				return FALSE;
			}

			$result = $query->result ();
			if (!empty($result))
			{
				// the new name exists in the table.
				$this->db->trans_rollback ();
				return FALSE;
			}

			$this->db->where ('projectid', $wiki->projectid);
			$this->db->where ('name', $wiki->name);
			$this->db->set ('name', $new_wiki_name);
			$this->db->set ('updatedon', $now);
			$this->db->set ('updatedby', $userid);
			$this->db->update ('wiki');

			// attachment renaming isn't needed because the
			// database table has a proper trigger set.
			//$this->db->where ('projectid', $wiki->projectid);
			//$this->db->where ('wikiname', $wiki->name);
			//$this->db->set ('wikiname', $new_wiki_name);
			//$this->db->update ('wiki_attachment');

			$effective_wiki_name = $new_wiki_name;
		}
		else
		{
			$effective_wiki_name = $wiki->name;
		}

		$this->db->where ('projectid', $wiki->projectid);
		$this->db->where ('name', $effective_wiki_name);
		$this->db->set ('text', $wiki->text);
		$this->db->set ('updatedon', $now);
		$this->db->set ('updatedby', $userid);
		$this->db->update ('wiki');

		foreach ($wiki->delete_attachments as $att)
		{
			$this->db->where ('projectid', $wiki->projectid);
			$this->db->where ('wikiname', $effective_wiki_name);
			$this->db->where ('name', $att->name);
			$this->db->where ('encname', $att->encname);
			$this->db->delete ('wiki_attachment');

			if ($this->db->trans_status() === FALSE)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}

			if ($this->db->affected_rows() <= 0)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}
		}

		foreach ($wiki->new_attachments as $att)
		{
			$this->db->set ('projectid', $wiki->projectid);
			$this->db->set ('wikiname', $effective_wiki_name);
			$this->db->set ('name', $att['name']);
			$this->db->set ('encname', $att['encname']);
			$this->db->set ('createdon', $now);
			$this->db->set ('createdby', $userid);
			$this->db->insert ('wiki_attachment');
		}

		// TODO: put rename message
		//$this->db->set ('createdon', $now);
		//$this->db->set ('type',      'wiki');
		//$this->db->set ('action',    'rename');
		//$this->db->set ('projectid', $wiki->projectid);
		//$this->db->set ('userid',    $userid);
		//$this->db->set ('message',   $effective_wiki_name);
		//$this->db->insert ('log');

		$this->db->set ('createdon', $now);
		$this->db->set ('type',      'wiki');
		$this->db->set ('action',    'update');
		$this->db->set ('projectid', $wiki->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $effective_wiki_name);
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->trans_commit ();
		return TRUE;
	}

	function delete ($userid, $wiki)
	{
		// TODO: check if userid can do this..
		$this->db->trans_start ();

		$this->db->where ('projectid', $wiki->projectid);
		$this->db->where ('wikiname', $wiki->name);
		$this->db->delete ('wiki_attachment');

		$this->db->where ('projectid', $wiki->projectid);
		$this->db->where ('name', $wiki->name);
		$this->db->delete ('wiki');

		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'wiki');
		$this->db->set ('action',    'delete');
		$this->db->set ('projectid', $wiki->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $wiki->name);

		$this->db->insert ('log');
		$this->db->trans_complete ();
		return $this->db->trans_status();
	}

}

?>
