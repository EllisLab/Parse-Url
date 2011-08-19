<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
Copyright (C) 2005 - 2011 EllisLab, Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
ELLISLAB, INC. BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Except as contained in this notice, the name of EllisLab, Inc. shall not be
used in advertising or otherwise to promote the sale, use or other dealings
in this Software without prior written authorization from EllisLab, Inc.
*/

$plugin_info = array(
						'pi_name'			=> 'Parse URL',
						'pi_version'		=> '1.2.1',
						'pi_author'			=> 'Paul Burdick',
						'pi_author_url'		=> 'http://www.expressionengine.com/',
						'pi_description'	=> 'Parses URL in a string and returns only certain parts',
						'pi_usage'			=> Parse_url::usage()
					);

/**
 * Parse_url Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			ExpressionEngine Dev Team
 * @copyright		Copyright (c) 2005 - 2011, EllisLab, Inc.
 * @link			http://expressionengine.com/downloads/details/parse_urls/
 */

class Parse_url {

    var $return_data;

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */
    function Parse_url($str = '')
    {
        $EE =& get_instance();

        if ($str == '')
        {
        	$str = $EE->TMPL->tagdata;
        }

        // ---------------------------------------
        //  Plugin Parameters
        // ---------------------------------------
        
		$autod = $EE->TMPL->fetch_param('find_uris', 'yes');
		$parts = $EE->TMPL->fetch_param('parts', 'scheme|host|path');
		$omit  = $EE->TMPL->fetch_param('omit', '');
		
		$parts = trim($parts);
		$omit  = explode('|', $omit);
		
		if (substr($parts, 0, 3) == 'not')
        {
	    	$not	  = explode('|', trim(substr($parts, 3)));
        	$possible = array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment');
        	
        	$parts = implode('|', array_diff($possible, $not));
        }

        // ---------------------------------------
        //  No auto-discovery? Just one big URL
        // ---------------------------------------
		
		if ($autod == 'no')
		{
			$str = trim($str);
			$str = $this->_parse_uri($str, $parts, $omit);
			$this->return_data = $str;
			return;
		}
		
        // ---------------------------------------
        //  Protect Any URLs in HTML
        // ---------------------------------------
        		
		$easy = array();
        
        if (preg_match_all("/<.*?>/i", $str, $matches))
        {
			$EE->load->helper('string');
			$hash = unique_marker('pi_parse_url');
			
        	for ($i = 0, $s = count($matches['0']); $i < $s; $i++)
        	{
        		$easy[$hash.'_'.$i] = $matches['0'][$i];
        		
        		$str = str_replace($matches['0'][$i], $hash.'_'.$i, $str);
        	}
        }
        
        // ---------------------------------------
        //  Find Any URLs Not in HTML
        // ---------------------------------------
        
        if (preg_match_all("#(http(s?)://|www\.*)([a-z0-9@%_.~\#\/\-\?&=]+)[^\s\)\<]+#i", $str, $matches))
        {
        	for ($i = 0, $s = count($matches['0']); $i < $s; $i++)
        	{
				$uri = $this->_parse_uri($matches[0][$i], $parts, $omit);
				
        		$str = str_replace($matches[0][$i], $uri, $str);
        	}
        }
        
        // ---------------------------------------
        //  Replace Protected HTML
        // ---------------------------------------
        
        if (count($easy) > 0)
        {
        	foreach($easy as $key => $value)
        	{
        		$str = str_replace($key, $value, $str);
        	}
        }
                
 		$this->return_data = $str;
    }

	// --------------------------------------------------------------------
    
	/**
	 * Parsing function
	 *
	 * @access	private
	 * @return	string	requested parts
	 */
	private function _parse_uri($uri_text, $parts, $omit)
	{
		$parsed		= parse_url($uri_text);  // Faster than preg_match, I hear
		$possible	= array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment');
    	
		foreach ($possible as $k)
		{
			if ( ! isset($parsed[$k]) OR ! stristr($parts, $k))
			{
				$parsed[$k] = '';
			}
		}
		
		$uri  = $parsed['scheme'] ? $parsed['scheme'].'://'	: '';
		$uri .= $parsed['user'] ? $parsed['user'].($parsed['pass'] ? ':'.$parsed['pass'] : '').'@' : '';
		$uri .= $parsed['host'];
		$uri .= $parsed['port'] ? ':'.$parsed['port'] : '';
		$uri .= $parsed['path'];
		$uri .= $parsed['query'] ? '?'.$parsed['query'] : '';
		$uri .= $parsed['fragment']	? '#'.$parsed['fragment'] : '';

		// remove omitted stuff
		
		if (count($omit) > 0)
		{
			foreach($omit as $remove)
			{
				$uri = str_replace($remove, '', $uri);
			}
		}
		
		return $uri;
	}

	// --------------------------------------------------------------------

	/**
	 * Plugin Usage
	 *
	 * @access	public
	 * @return	string	plugin usage text
	 */
	function usage()
	{
		ob_start(); 
		?>
		Wrap anything you want to be processed between the tag pairs.

		{exp:parse_url total="100"}

		text you want processed

		{/exp:parse_url}

		PARAMETERS:

		The "parts" parameter lets you specify what parts of the URL to keep:

		scheme - e.g. http
		host
		port
		user
		pass
		path
		query - after the question mark ?
		fragment - after the hashmark #

		Include multiple ones like so:  parts="scheme|host|path|query|fragment"

		--------

		The 'omit' parameter lets you remove a certain string from the URLs.  Separate multiple strings with a bar (|).
		
		--------
		
		The 'find_uris' parameter lets you control auto-discovery of URLs. If set to "no" it will treat the entire input as a URL. [default: yes]

		***************************
		Version 1.1.1
		***************************
		Fixed a bug where the auto linking was interfering with this plugin's processing of URLs.

		Version 1.2
		***************************
		Updated plugin to be 2.0 compatible

		Version 1.2.1
		***************************
		Added an find_uris parameter to control auto-discovery, which breaks some complex URLs.

		<?php
		$buffer = ob_get_contents();
	
		ob_end_clean(); 

		return $buffer;
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file pi.parse_url.php */
/* Location: ./system/expressionengine/third_party/parse_url/pi.parse_url.php */