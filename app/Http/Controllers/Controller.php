<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Habilita $this->authorize(...) en los controladores para apoyarse en las Policies.
    use AuthorizesRequests;
}
