<?php namespace DreamFactory\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    //******************************************************************************
    //* Traits
    //******************************************************************************

    use DispatchesJobs, ValidatesRequests;
}
