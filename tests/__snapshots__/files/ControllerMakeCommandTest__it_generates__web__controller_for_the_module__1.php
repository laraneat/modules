<?php

namespace Modules\Author\UI\WEB\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

class WebAuthorController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
