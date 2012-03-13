<?php

namespace Amiss\Demo;

abstract class Object
{
	public function __get($key)
	{
		throw new \Exception(sprintf("Property '%s' not defined", $key));
	}

	/**
	 * Prevents a field from being set if it is not defined in the class.
	 */
	public function __set($key, $value)
	{
		throw new \Exception(sprintf("Property '%s' not defined", $key));
	}

	/**
	 * Prevents a field from being tested for existence if it is not defined in the class.
	 */
	public function __isset($key)
	{
		throw new \Exception(sprintf("Property '%s' not defined", $key));
	}

	/**
	 * Prevents a field from being unset if it is not defined in the class. 
	 */
	public function __unset($key)
	{
		throw new \Exception(sprintf("Property '%s' not defined", $key));
	}
}

class Artist extends Object
{
	public $artistId;
	public $artistTypeId;
	public $name;
	public $slug;
	public $bio;
	
	/**
	 * @var Amiss\Demo\ArtistType
	 */
	public $artistType;
}

class ArtistType extends Object
{
	public $artistTypeId;
	public $type;
	public $slug;
	
	/**
	 * @var Amiss\Demo\Artist[]
	 */
	public $artists = array();
}

class Event extends Object
{
	public $eventId;
	public $name;
	public $slug;
	public $dateStart;
	public $dateEnd;
	public $venueId;
	
	/**
	 * @var Amiss\Demo\EventArtist[]
	 */
	public $eventArtists;
	
	/**
	 * @var Amiss\Demo\Venue
	 */
	public $venue;
}

class EventArtist extends Artist
{
	public $eventId;
	public $artistId;
	public $priority;
	public $sequence;
	public $eventArtistName;
	
	/**
	 * @var Amiss\Demo\Event
	 */
	public $event;
	
	/**
	 * @var Amiss\Demo\Artist
	 */
	public $artist;
}

class Venue extends Object
{
	public $venueId;
	public $venueName;
	public $venueSlug;
	public $venueAddress;
	public $venueShortAddress;
	public $venueLatitude;
	public $venueLongitude;
}
