<?php

namespace Modules\Author\UI\API\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Laraneat\Modules\Concerns\WithJsonResponseHelpers;

class ApiAuthorController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, WithJsonResponseHelpers;
}
