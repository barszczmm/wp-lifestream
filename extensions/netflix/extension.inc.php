<?php
class Lifestream_NetflixFeed extends Lifestream_Feed
{
	const ID			= 'netflix';
	const NAME			= 'Netflix';
	const URL			= 'http://www.netflix.com/';
	const DESCRIPTION	= 'You can find your feed URL by logging into your Netflix account and clicking on RSS at the very bottom of the page.';

	function __toString()
	{
		return $this->get_option('user_id');
	}

	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
			'user_id' => array($this->lifestream->__('User ID:'), null, '', ''),
			'show_queue' => array($this->lifestream->__('Include queued videos in this feed.'), true, true, false),
			'show_reviews' => array($this->lifestream->__('Include reviewed videos in this feed.'), true, true, false),
		);
	}
	
	function get_url() {
		$urls = array();
		if ($this->get_option('show_queue'))
		{
			$urls[] = array('http://rss.netflix.com/QueueRSS?id='.$this->get_option('user_id').'queue');
			$urls[] = array('http://rss.netflix.com/QueueEDRSS?id='.$this->get_option('user_id').'queue');
		}
		if ($this->get_option('show_reviews'))
		{
			$urls[] = array('http://rss.netflix.com/ReviewsRSS?id='.$this->get_option('user_id').'review');
		}
		return $urls;
	}
	
	function save_options()
	{
		if (preg_match('/id=([A-Z0-9]+)/i', $this->get_option('url'), $match))
		{
			$this->update_option('user_id', $match[1]);
		}
		else
		{
			throw new Lifestream_Error("Invalid feed URL.");
		}
		parent::save_options();
	}
	
	function get_label_class($key)
	{
		if ($key == 'review') $cls = 'Lifestream_ReviewVideoLabel';
		elseif ($key == 'queue') $cls = 'Lifestream_QueueVideoLabel';
		return $cls;
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		if ($data['title'] == 'Your Queue is empty.') return;
		if ($key == 'queue')
		{
			$data['title'] = substr($data['title'], 5);
		}
		return $data;
	}
}
$lifestream->register_feed('Lifestream_NetflixFeed');
?>