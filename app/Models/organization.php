<?php namespace app\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class organization extends Eloquent {
 	public $timestamps = false;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'Organizations';

	protected $primaryKey = 'ID';
	protected $fillable = array();

}