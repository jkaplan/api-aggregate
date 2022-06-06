<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\StreamerService;
use App\Http\Controllers\Controller;

class StreamerController extends Controller
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
        # Create a new instance of the Instant API class.
        $api = new StreamerService();

        # Intercept the requested URL and use the parameters within it to determine what data to respond with.
        $api->parse_query();

        # Gather the requested data from its CSV source, converting it into JSON, XML, or HTML.
        $api->parse();

        # Send the JSON to the browser.
        return $api->output();
    }
}
