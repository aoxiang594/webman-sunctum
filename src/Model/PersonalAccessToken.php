<?php

namespace Aoxiang\WebmanSunctum\Model;

use support\Model;

class PersonalAccessToken extends Model
{
    protected $guarded = [];
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'personal_access_tokens';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

//    /**
//     * Indicates if the model should be timestamped.
//     *
//     * @var bool
//     */
//    public $timestamps = false;
}