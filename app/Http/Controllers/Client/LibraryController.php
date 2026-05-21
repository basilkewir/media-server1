<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(): View
    {
        return view('client.library');
    }
}
