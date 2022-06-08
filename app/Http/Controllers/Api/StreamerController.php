<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use App\Services\ConvertCsvToApiService;

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
    public function index(Request $request, string $csv_name)
    {
        # Create a new instance of the Instant API class.
        $api = new ConvertCsvToApiService();

        # Intercept the requested URL and use the parameters within it to determine what data to respond with.
        $api->parse_query($request->query(), $csv_name);

        # Gather the requested data from its CSV source, converting it into JSON, XML, or HTML.
        $api->parse();

        # Send the JSON to the browser.
        return $api->output();
    }

    public function netflix(Request $request)
    {
        $csv_name = $this->getCsvNameFromPath($request);
        // dd($csv_name);
        // set query string, pass into index, then pass that into parseQuery of index function
        return $this->index($request, $csv_name);
    }

    public function hulu(Request $request)
    {
        $csv_name = $this->getCsvNameFromPath($request);

        // set query string, pass into index, then pass that into parseQuery of index function
        return $this->index($request, $csv_name);
    }

    public function disney(Request $request)
    {
        $csv_name = $this->getCsvNameFromPath($request);

        // set query string, pass into index, then pass that into parseQuery of index function
        return $this->index($request, $csv_name);
    }

    public function amazon(Request $request)
    {
        $csv_name = $this->getCsvNameFromPath($request);

        // set query string, pass into index, then pass that into parseQuery of index function
        return $this->index($request, $csv_name);
    }

    public function getCsvNameFromPath($request)
    {
        return str_replace('/api/', '', $request->getPathInfo());
    }
}
