<?php

// require_once($amissPath.'/../doc/demo/ar.php');

namespace
{
	$cache = get_note_cache('apc', !isset($_GET['nocache']));
	$mapper = new Amiss\Mapper\Note($cache);
	$mapper->objectNamespace = 'Amiss\Example\ActiveNote';
	$manager = new Amiss\Manager(new Amiss\Connector('sqlite::memory:'), $mapper);
	$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite'));
	$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sqlite'));
	
	Amiss\Active\Record::setManager($manager);
}

namespace Amiss\Example\ActiveNote
{
	/**
	 * @table artist
	 */
	class ArtistRecord extends \Amiss\Active\Record
	{
		/**
		 * @primary
		 * @type autoinc
		 */
		public $artistId;
		
		/**
		 * @field
		 */
		public $artistTypeId;
		
		/**
		 * @field
		 */
		public $name;
		
		/**
		 * @field
		 */
		public $slug;
		
		/**
		 * @field
		 */
		public $bio;
		
		/**
		 * @has one ArtistTypeRecord artistTypeId
		 */
		private $type;
		
		public function getType()
		{
			if ($this->type === null) {
				$this->type = $this->getRelated('type');
			}
			return $this->type;
		}
	}
	
	/**
	 * @table artist_type
	 */
	class ArtistTypeRecord extends \Amiss\Active\Record
	{
		/**
		 * @primary
		 * @type autoinc
		 */
		public $artistTypeId;
		
		/**
		 * @field
		 */
		public $type;
		
		/**
		 * @field
		 */
		public $slug;
		
		/**
		 * @has many ArtistRecord
		 * @var Amiss\Demo\ArtistRecord[]
		 */
		private $artists = array();
		
		public function getArtists()
		{
			if ($this->artists === null) {
				$this->artists = $this->getRelated('artists');
			}
			return $this->artists;
		}
	}
}
