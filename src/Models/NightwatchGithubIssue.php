<?php

namespace Andremellow\NightwatchGithub\Models;

use Illuminate\Database\Eloquent\Model;

class NightwatchGithubIssue extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return (string) config('nightwatch-github.table', parent::getTable());
    }
}
