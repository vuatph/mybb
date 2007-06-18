<?php
/**
 * MyBB 1.2
 * Copyright � 2007 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybboard.net
 * License: http://www.mybboard.net/license.php
 *
 * $Id$
 */

class FeedGenerator
{
	/**
	 * The type of feed to generate.
	 *
	 * @var string
	 */
	var $feed_format = 'rss2.0';

	/**
	 * The XML to output.
	 *
	 * @var string
	 */
	var $xml = "";

	/**
	 * Array of all of the items
	 *
	 * @var array
	 */
	var $items = array();

	/**
	 * Array of the channel information.
	 *
	 * @var array
	 */
	var $channel = array();

	/**
	 * Set the type of feed to be used.
	 *
	 * @param string The feed type.
	 */
	function set_feed_format($feed_format)
	{
		if($feed_format == 'atom1.0')
		{
			$this->feed_format = 'atom1.0';
		}
		else
		{
			$this->feed_format = 'rss2.0';
		}
	}

	/**
	 * Sets the channel information for the RSS feed.
	 *
	 * @param array The channel information
	 */
	function set_channel($channel)
	{
		$this->channel = $channel;
	}

	/**
	 * Adds an item to the RSS feed.
	 *
	 * @param array The item.
	 */
	function add_item($item)
	{
		$this->items[] = $item;
	}


	/**
	 * Generate and echo XML for the feed.
	 *
	 */
	function generate_feed()
	{
		global $parser;
		// First, add the feed metadata.
		switch($this->feed_format)
		{
			// Ouput an Atom 1.0 formatted feed.
			case "atom1.0":
				$this->channel['date'] = gmdate("Y-m-d\TH:i:s\Z", $this->channel['date']);
				$this->xml .= "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
				$this->xml .= "<feed xmlns=\"http://www.w3.org/2005/Atom\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n";
				$this->xml .= "\t<title type=\"html\"><![CDATA[".htmlspecialchars_uni($this->channel['title'])."]]></title>\n";
				$this->xml .= "\t<subtitle type=\"html\"><![CDATA[".htmlspecialchars_uni($this->channel['description'])."]]></subtitle>\n";
				$this->xml .= "\t<link rel=\"self\" href=\"{$this->channel['link']}syndication.php\"/>\n";
				$this->xml .= "\t<id>{$this->channel['link']}</id>\n";
				$this->xml .= "\t<link rel=\"alternate\" type=\"text/html\" href=\"{$this->channel['link']}\"/>\n";
				$this->xml .= "\t<updated>{$this->channel['date']}</updated>\n";
				$this->xml .= "\t<generator uri=\"http://mybboard.net\">MyBB</generator>\n";
				break;
			// The default is the RSS 2.0 format.
			default:
				$this->channel['date'] = date("D, d M Y H:i:s O", $this->channel['date']);
				$this->xml .= "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
				$this->xml .= "<rss version=\"2.0\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n";
				$this->xml .= "\t<channel>\n";
				$this->xml .= "\t\t<title><![CDATA[".htmlspecialchars_uni($this->channel['title'])."]]></title>\n";
				$this->xml .= "\t\t<link>{$this->channel['link']}</link>\n";
				$this->xml .= "\t\t<description><![CDATA[".htmlspecialchars_uni($this->channel['description'])."]]></description>\n";
				$this->xml .= "\t\t<pubDate>{$this->channel['date']}</pubDate>\n";
				$this->xml .= "\t\t<generator>MyBB</generator>\n";
		}

		// Now loop through all of the items and add them to the feed XML.
		foreach($this->items as $item)
		{
			$parser_options = array();
			$parser_options['allow_html'] = $item['allowhtml'];
			$parser_options['allow_mycode'] = $item['allowmycode'];
			$parser_options['allow_smilies'] = $item['allowsmilies'];
			$parser_options['allow_imgcode'] = $item['allowimgcode'];
			$parser_options['me_username'] = $item['username'];
			if($item['smilieoff'] == "yes")
			{
				$parser_options['allow_smilies'] = "no";
			}

			if(empty($item['date']))
			{
				$item['date'] = TIME_NOW;
			}
			switch($this->feed_format)
			{
				// Output Atom 1.0 format feed.
				case "atom1.0":
					$item['date'] = date("Y-m-d\TH:i:s\Z", $item['date']);
					$this->xml .= "\t<entry xmlns=\"http://www.w3.org/2005/Atom\">\n";
					if(!empty($item['author']))
					{
						$this->xml .= "\t\t<author>\n";
						$this->xml .= "\t\t\t<name>".htmlspecialchars_uni($item['author'])."</name>\n";
						$this->xml .= "\t\t</author>\n";
					}
					$this->xml .= "\t\t<published>{$item['date']}</published>\n";
					if(empty($item['updated']))
					{
						$item['updated'] = $item['date'];
					}
					else
					{
						$item['updated'] = date("Y-m-d\TH:i:s\Z", $item['updated']);;
					}
					$this->xml .= "\t\t<updated>{$item['updated']}</updated>\n";
					$this->xml .= "\t\t<link rel=\"alternate\" type=\"text/html\" href=\"{$item['link']}\" />\n";
					$this->xml .= "\t\t<id>{$item['link']}</id>\n";
					$this->xml .= "\t\t<title type=\"html\" xml:space=\"preserve\"><![CDATA[".htmlspecialchars_uni($item['title'])."]]></title>\n";
					$this->xml .= "\t\t<content type=\"html\" xml:space=\"preserve\" xml:base=\"{$item['link']}\"><![CDATA[".$parser->parse_message($item['description'], $parser_options)."]]></content>\n";
					$this->xml .= "\t\t<draft xmlns=\"http://purl.org/atom-blog/ns#\">false</draft>\n";
					$this->xml .= "\t</entry>\n";
					break;

				// The default is the RSS 2.0 format.
				default:
					$item['date'] = date("D, d M Y H:i:s O", $item['date']);
					$item['description'] = $parser->parse_message($item['description'], $parser_options);
					$this->xml .= "\t\t<item>\n";
					$this->xml .= "\t\t\t<title><![CDATA[".htmlspecialchars_uni($item['title'])."]]></title>\n";
					$this->xml .= "\t\t\t<link>{$item['link']}</link>\n";
					$this->xml .= "\t\t\t<pubDate>{$item['date']}</pubDate>\n";
					if(!empty($item['author']))
					{
						$this->xml .= "\t\t\t<dc:creator>".htmlspecialchars_uni($item['author'])."</dc:creator>\n";
					}
					$this->xml .= "\t\t\t<guid isPermaLink=\"false\">{$item['link']}</guid>\n";
					$this->xml .= "\t\t\t<description><![CDATA[{$item['description']}]]></description>\n";
					$this->xml .= "\t\t\t<content:encoded><![CDATA[{$item['description']}]]></content:encoded>\n";
					$this->xml .= "\t\t</item>\n";
					break;
			}
		}

		// Now, neatly end the feed XML.
		switch($this->feed_format)
		{
			case "atom1.0":
				$this->xml .= "</feed>";
				break;
			default:
				$this->xml .= "\t</channel>\n";
				$this->xml .= "</rss>";
		}
	}

	/**
	* Output the feed XML.
	*/
	function output_feed()
	{
		// Send an appropriate header to the browser.
		switch($this->feed_format)
		{
			case "atom1.0":
				header("Content-Type: application/atom+xml; charset=\"utf-8\"");
				break;
			default:
				header("Content-Type: text/xml; charset=\"utf-8\"");
		}

		// Output the feed XML. If the feed hasn't been generated, do so.
		if(!empty($this->xml))
		{
			echo $this->xml;
		}
		else
		{
			$this->generate_feed();
			echo $this->xml;
		}
	}
}
?>