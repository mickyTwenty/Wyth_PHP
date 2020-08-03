<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

class FAQ extends Model
{
    use Eloquence;

    protected $table    = 'faqs';
    protected $fillable = ['type', 'title', 'content'];

    protected $searchableColumns = ['title'];
}
