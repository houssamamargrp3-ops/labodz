<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalysisRule extends Model
{
    use HasFactory;

    protected $fillable = ['analysis_id', 'question_id', 'disallowed_option_id', 'action'];

    public function analyse()
    {
        return $this->belongsTo(Analyse::class, 'analysis_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function option()
    {
        return $this->belongsTo(Option::class, 'disallowed_option_id');
    }
}
