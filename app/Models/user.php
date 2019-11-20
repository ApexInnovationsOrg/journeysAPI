<?php namespace app\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class user extends Eloquent {
 	public $timestamps = false;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'Users';

	protected $primaryKey = 'ID';
	protected $fillable = array('Login','FirstName','LastName');

	public function Department(){
		
		return \app\Models\department::find($this->DepartmentID);
	}

}