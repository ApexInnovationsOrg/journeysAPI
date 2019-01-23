<?php namespace app\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class ContentElements extends Eloquent {
 	public $timestamps = false;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'ace_ContentElements';


	protected $fillable = array('MoreContent','Content','ContentType','PageID');

}