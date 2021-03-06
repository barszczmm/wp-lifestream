<?php
class Lifestream_NokiaSportsTracker extends Lifestream_Extension
{
	const ID			= 'nokiasportstracker';
	const NAME			= 'Nokia Sports Tracker';
	const URL			= 'http://sportstracker.nokia.com/';
	const DESCRIPTION	= '';
	const LABEL			= 'Lifestream_ExercisesLabel';
	const AUTHOR		= 'Maciej "barszcz" Marczewski <maciej@marczewski.net.pl>';

	function __toString()
	{
		return $this->get_option('username');
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_user_profile_url()
	{
		return 'http://sportstracker.nokia.com/nts/user/profile.do?u='.$this->get_option('username');
	}
	
	function get_url()
	{
		return 'http://sportstracker.nokia.com/nts/json/specificlatest.do?user_id='.$this->get_option('user_id');
	}
	
	function save_options($validate=true) 
	{
		$profile_url = $this->get_user_profile_url();
		$data = $this->lifestream->file_get_contents($profile_url);
		if (preg_match('<input type="hidden" id="user_id" name="user_id" value="(\d+)" />', $data, $matches))
		{
			$this->add_option('user_id', $matches[1]);
		}
		else
		{
			throw new Lifestream_Error("Error getting User ID from profile page source: ".$this->get_user_profile_url()."\n(if you don't see your profile page under this link then you entered wrong username)");
		}
		
		$url = $this->get_url();
		
		if ($validate)
		{
			$data = $this->lifestream->file_get_contents($url);
			$json = json_decode($data);
			# json_last_error function is available in PHP 5.3.0 or newer
			if (version_compare(PHP_VERSION, '5.3', '>='))
			{
				if (json_last_error() != JSON_ERROR_NONE)
				{
					$sample = substr($data, 0, 150);
					throw new Lifestream_FeedFetchError("Error fetching JSON format from ".$url."\n(Received: ".$sample.")");
				}
			}
			else
			{
				if (!$data || ($data && ($json === NULL)))
				{
					$sample = substr($data, 0, 150);
					throw new Lifestream_FeedFetchError("Error fetching JSON format from ".$url."\n(Received: ".$sample.")");
				}
			}
		}
		parent::save_options();
	}
	
	function fetch($urls=null, $initial=false)
	{
		if (!$urls) $urls = $this->get_url();
		if (!is_array($urls)) $urls = array($urls);
		$items = array();
		foreach ($urls as $url_data)
		{
			if (is_array($url_data))
			{
				// url, key
				list($url, $key) = $url_data;
			}
			else
			{
				$url = $url_data;
				$key = '';
			}
			$data = $this->lifestream->file_get_contents($url);
			$json = json_decode($data);
			# json_last_error function is available in PHP 5.3.0 or newer
			if (version_compare(PHP_VERSION, '5.3', '>='))
			{
				if (json_last_error() != JSON_ERROR_NONE)
				{
					$sample = substr($data, 0, 150);
					throw new Lifestream_FeedFetchError("Error fetching JSON format from ".$url."\n(Received: ".$sample.")");
				}
			}
			else
			{
				if (!$data || ($data && ($json === NULL)))
				{
					$sample = substr($data, 0, 150);
					throw new Lifestream_FeedFetchError("Error fetching JSON format from ".$url."\n(Received: ".$sample.")");
				}
			}
			foreach ($json as $row)
			{
				$rows =& $this->yield_many($row, $url, $key);
				foreach ($rows as $row)
				{
					if (!$row) continue;
					if (!$row['key']) $row['key'] = $key;
					if (count($row)) $items[] = $row;
				}
			}
		}
		return $items;
	}

	function yield($row)
	{
		$date = strptime($row->startUTC, '%d.%m.%y %H:%M');
		$timestamp = mktime($date['tm_hour'], $date['tm_min'], 0, 1+$date['tm_mon'], $date['tm_mday'], 1900+$date['tm_year']);
		return array(
			'date'         =>  $timestamp,
			'link'         =>  'http://sportstracker.nokia.com/nts/workoutdetail/index.do?id='.$row->id,
			'title'        =>  $row->activity->name.' ('.round(($row->distance)/1000, 1).' km in '.preg_replace('/ \d{1,2} s/', '', $row->duration).')',
		);
	}
}
# PHP older than 5.2.0 had no JSON extension bundled by default
if (version_compare(PHP_VERSION, '5.2', '>='))
{
	$lifestream->register_feed('Lifestream_NokiaSportsTracker');
}
?>