<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ConvertCsvToApi;

class ApiController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $api = new ConvertCsvToApi($request);

        $api->parse_query($request->query(), str_replace('/api/', '', $request->getPathInfo()));

        $api->parse();

        return $api->output();
    }
}
