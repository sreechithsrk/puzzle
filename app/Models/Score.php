<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'total_score', 'string', 'remaining_string'];

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id');
    }

    public function getWordsAttribute()
    {
        $wordSubmissions = WordSubmissions::whereStudentId($this->student_id)->pluck('word')->unique();

        return $wordSubmissions->implode(', ');
    }
}
