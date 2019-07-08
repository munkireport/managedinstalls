<?php

use munkireport\models\MRModel as Eloquent;

class Managedinstalls_model extends Eloquent
{
    protected $table = 'managedinstalls';

    protected $fillable = [
      'serial_number',
      'name',
      'display_name',
      'version',
      'size',
      'installed',
      'status',
      'type',
    ];

    public $timestamps = false;

}
