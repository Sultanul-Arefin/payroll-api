<?php

namespace Modules\StaffObjective\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\StaffObjective\Database\factories\StaffObjectiveFactory;

class StaffObjective extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['user_id', 'review_by', 'review_date', 'meeting_details', 'review_notes', 'official_review_notes', 'review_document'];

    public function ReviewDocument(): Attribute
    {
        return Attribute::make(
            get: fn($value) => ($value !=null ? asset('storage').'/'.$value : null )
        );
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    //who is the person review staff objective request
    public function reviewBy()
    {
        return $this->belongsTo(User::class, 'review_by', 'id');
    }

}
