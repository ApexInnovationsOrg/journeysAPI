<?php namespace app\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class employee extends Eloquent {
 	public $timestamps = false;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'Employees';

	protected $primaryKey = 'ID';
	protected $fillable = array('FirstName','LastName','Email');

}