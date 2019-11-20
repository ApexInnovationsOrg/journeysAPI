<?php namespace app\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class journey_content extends Eloquent {
 	public $timestamps = false;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'journey_content';

	protected $primaryKey = 'ID';
	protected $fillable = array('NodeID','Content');

}