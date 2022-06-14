<?php

namespace App\Console\Commands;

use DateTime;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\Http;

class GenerateDocumentation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:generate-docs {api_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will help generate the API documentation structure needed for APILayer and RapidAPI using OpenAPI 3.0 spec.';

    /**
    * Create a new command instance.
    *
    * @return void
    */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $data = $this->getCsvData();
        $headers = $this->getCsvHeaders($data);
        $responses = [];

        foreach ($headers as $key => $value) {
            $responses[$value] = $this->ask('What data type is this parameter? (leave blank to input a string)', $value);

            if ($responses[$value] == $value) {
                $responses[$value] = 'string';
            }

            if ($responses[$value] == 'array') {
                $should_explode = $this->ask('Do the values from the CSV for this field need to be exploded into separate values?', 'y or n');

                if ($should_explode == 'y') {
                    $responses[$value] = 'exploded-array';
                } else {
                    $response[$value] = 'array';
                }
            }
        }

        $unique_values = $this->getUniqueValuesForArrayParams($responses, $data);
        $params = $this->buildParamsArray($unique_values);

        $this->generateDocumentation($params);

        return 0;
    }

    public function getCsvData()
    {
        $response = HTTP::get('http://api-aggregate.localhost/api/' . $this->argument('api_name'));
        // $last_page = $json['last_page'];
        // TODO: while loop with last page to get all the data
        $json = $response->json();
        $data = $json['data'];


        return $data;
    }

    public function getCsvHeaders($data)
    {
        $first_row = $data[0];
        $headers = [];

        foreach ($first_row as $key => $value) {
            array_push($headers, $key);
        }

        return $headers;
    }

    public function getUniqueValuesForArrayParams(array $responses, array $data)
    {
        $unique_values = [];

        foreach ($responses as $key => $response) {
            if ($response == 'exploded-array') {
                $unique_collection = collect($data)->pluck($key);
                $unique_array = [];
                foreach ($unique_collection as $unique) {
                    $unique = explode(', ', $unique);
                    foreach ($unique as $exploded) {
                        array_push($unique_array, $exploded);
                    }
                }

                $unique_values[$key] = collect($unique_array)->unique();
            } elseif ($response == 'array') {
                $unique_values[$key] = collect($data)->pluck($key)->unique();
            } elseif ($response == 'string') {
                $unique_values[$key] = $response;
            }
        }

        return $unique_values;
    }

    public function buildParamsArray($unique_values)
    {
        $params = [];

        foreach ($unique_values as $key => $value) {
            if (is_object($value)) {
                $value = array_values($value->toArray());

                array_push($params, [
                    'name' => $key,
                    'in' => 'query',
                    'description' => '',
                    'style' => 'pipeDelimited',
                    'explode' => true,
                    'schema' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                            'enum' => $value,
                        ],
                    ],
                ]);
            } else {
                array_push($params, [
                'name' => $key,
                'in' => 'query',
                'description' => '',
                'schema' => [
                    'type' => 'string',
                ],
            ]);
            }
        }

        return $params;
    }

    public function generateDocumentation(array $params)
    {
        $path = '/' . $this->argument('api_name');

        $documentation = [
            'openapi' => '3.0.1',
            'info' => [
                'title' => '',
                'description' => '',
                'version' => '1.0.0',
            ],
            'servers' => [
                ['url' => 'https://apiaggregate.com/api'],
            ],
            'paths' => [
                $path => [
                    'get' => [
                        'tags' =>
                            [$this->argument('api_name')],
                        'summary' => 'test',
                        'parameters' => $params,
                        'responses' => [
                            200 => [
                                'description' => 'Success',
                            ]
                        ]
                    ],
                ],
            ],
        ];

        $yaml = Yaml::dump($documentation, 50, 2);

        file_put_contents('./public/docs/test2.yaml', $yaml);
    }
}
