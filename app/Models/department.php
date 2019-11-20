<?php namespace app\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class department extends Eloquent {
 	public $timestamps = false;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'Departments';

	protected $primaryKey = 'ID';
	protected $fillable = array();

	public function Organization(){
		
		return \app\Models\organization::find($this->OrganizationID);
	}
}