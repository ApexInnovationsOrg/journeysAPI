<?php namespace app\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class journey_results extends Eloquent {
 	public $timestamps = false;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'journey_results';

	protected $primaryKey = 'ID';
	protected $fillable = array('JourneyTreeID','UserID','Score','QuestionsAsked','AnswersGiven','JourneyStarted','JourneyCompleted');

}