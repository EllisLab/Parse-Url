<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
Copyright (C) 2005 - 2021 Packet Tide, LLC

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
PACKET TIDE, LLC BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Except as contained in this notice, the name of Packet Tide, LLC shall not be
used in advertising or otherwise to promote the sale, use or other dealings
in this Software without prior written authorization from Packet Tide, LLC.
*/


/**
 * Parse_url Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			Packet Tide
 * @copyright		Copyright (C) 2005 - 2021 Packet Tide, LLC
 * @link			https://github.com/EllisLab/Parse-Url
 */
class Parse_url {

    public $return_data;

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct($str = '')
    {

        if ($str == '')
        {
        	$str = ee()->TMPL->tagdata;
        }

        // ---------------------------------------
        //  Plugin Parameters
        // ---------------------------------------

		$autod = ee()->TMPL->fetch_param('find_uris', 'yes');
		$parts = ee()->TMPL->fetch_param('parts', 'scheme|host|path');
		$omit  = ee()->TMPL->fetch_param('omit', '');

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
			ee()->load->helper('string');
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
}
