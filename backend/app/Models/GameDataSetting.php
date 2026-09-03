<?php

namespace App\Models;

use Database\Factories\GameDataSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameDataSetting extends Model
{
    /** @use HasFactory<GameDataSettingFactory> */
    use HasFactory;

    protected $fillable = ['client_version', 'server_version'];
}
