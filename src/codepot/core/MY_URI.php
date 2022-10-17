<?php

class MY_URI extends CI_URI 
{
	// CodeIgniter changed the behavior such that it doesn't url-decodes the 
	// arguments to invoked calls.
	//
	// core/CodeIgniter.php called $RTR->_set_routing()
	// Router::_set_routing() ->  URI::_explode_segments -> URI::__filter_uri
	//
	// Create MY_URI and implement _filter_uri here to override the default behavior
	//
	
	function _filter_uri ($uri)
	{
		return rawurldecode(parent::_filter_uri($uri));
	}
}

