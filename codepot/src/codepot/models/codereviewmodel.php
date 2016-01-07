<?php

class CodeReviewModel extends Model
{
	protected $errmsg = '';

	function getErrorMessage ()
	{
		return $this->errmsg;
	}

	function CodeReviewModel ()
	{
		parent::Model ();
		$this->load->database ();
	}

	function getReviews ($projectid, $revision)
	{
		$this->db->trans_begin ();

		$this->db->where ('projectid', (string)$projectid);
		$this->db->where ('rev', $revision);
		$query = $this->db->get ('code_review');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$result = $query->result ();
		$this->db->trans_commit ();
		return $result;
	}

	function insertReview ($projectid, $revision, $userid, $comment)
	{
		// TODO: check if userid can do this..
		$this->db->trans_begin ();

		$this->db->where ('projectid', $projectid);
		$this->db->where ('rev', $revision);
		$this->db->select ('MAX(sno) as maxsno');
		$query = $this->db->get ('code_review');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$result = $query->result();
		$maxsno = (empty($result) || $result[0] == NULL)? 0: $result[0]->maxsno;

		$newsno = $maxsno + 1;

		$this->db->set ('projectid', $projectid);
		$this->db->set ('rev', $revision);
		$this->db->set ('sno', $newsno);
		$this->db->set ('comment', $comment);
		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('createdby', $userid);
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('code_review');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}
		/*$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'code_review');
		$this->db->set ('action',    'insert');
		$this->db->set ('projectid', $projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   "$rev,$sno");
		$this->db->insert ('log');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}*/

		$this->db->trans_commit ();
		return $newsno;
	}

	function updateReview ($projectid, $revision, $sno, $userid, $comment, $strict = FALSE)
	{
		// TODO: check if userid can do this..
		$this->db->trans_begin ();

		$this->db->where ('projectid', $projectid);
		$this->db->where ('rev', $revision);
		$this->db->where ('sno', $sno);
		if ($strict) $this->db->where ('updatedby', $userid);
		$this->db->set ('comment', $comment);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);
		$this->db->update ('code_review');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		/*$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'code_review');
		$this->db->set ('action',    'insert');
		$this->db->set ('projectid', $projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   "$rev,$sno");
		$this->db->insert ('log');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}*/

		$this->db->trans_commit ();
		return TRUE;
	}

	function deleteReview ($projectid, $revision, $sno, $userid)
	{
		// TODO: check if userid can do this..
		$this->db->trans_begin();

		$this->db->where ('projectid', $projectid);
		$this->db->where ('rev', $revision);
		$this->db->where ('sno', $sno);
		$this->db->delete ('code_review');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		/*$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->set ('type',      'issue');
		$this->db->set ('action',    'delete');
		$this->db->set ('projectid', $projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   "$rev,$sno");
		$this->db->insert ('log');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		} */

		$this->db->trans_commit ();
		return TRUE;
	}
}

?>
