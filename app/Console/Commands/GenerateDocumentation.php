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

        $responses = $this->promptForDataTypes($headers);
        $strict_params = $this->promptForStrictParams($headers);

        $unique_values = $this->getUniqueValuesForArrayParams($responses, $data);
        $params = $this->buildParamsArray($unique_values, $strict_params);

        $this->generateDocumentation($params);

        return 0;
    }

    public function promptForStrictParams($headers)
    {
        $strict_fields = $this->choice('Which params should allow strict filtering? '. PHP_EOL .' For example, querying `TV` with a strict filter would not return records with a value of `TV Shows`.', $headers, null, null, true);

        return $strict_fields;
    }

    public function promptForDataTypes($headers)
    {
        $responses = [];

        foreach ($headers as $key => $value) {
            $responses[$value] = $this->choice('What data type is this parameter ('.$value.')? '.PHP_EOL.' a: array '.PHP_EOL.' i: integer '.PHP_EOL.' s: string '.PHP_EOL.' n: leave field off docs', ['a', 'i', 's', 'n']);

            if ($responses[$value] == 'a') {
                $responses[$value] = 'array';
            }

            if ($responses[$value] == 'i') {
                $responses[$value] = 'integer';
            }


            if ($responses[$value] == 's') {
                $responses[$value] = 'string';
            }

            if ($responses[$value] == 'array') {
                $should_explode = $this->ask('Is this a comma separated list that needs to be exploded into individual options?', 'y or n');

                if ($should_explode == 'y') {
                    $responses[$value] = 'exploded-array';
                } else {
                    $response[$value] = 'array';
                }
            }
        }

        return $responses;
    }

    public function getCsvData()
    {
        $domain = 'https://apiaggregate.co/';

        if (env('APP_ENV') == 'local') {
            $domain = 'http://api-aggregate.localhost';
        }

        $response = HTTP::get($domain . '/api/' . $this->argument('api_name'));
        $json = $response->json();

        if (!isset($json['last_page'])) {
            $this->error('Could not retrieve CSV data to generate documentation.');
            return;
        }

        $last_page = $json['last_page'];

        $index = 1;
        $data = [];

        while ($index <= $last_page) {
            $response = HTTP::get($domain . '/api/' . $this->argument('api_name') . '?page=' . $index);

            $json = $response->json();

            if (isset($json) && isset($json['data'])) {
                $data = array_merge($data, $json['data']);
            }

            $index += 1;
        }

        return $data;
    }

    public function getCsvHeaders($data)
    {
        $headers = [];

        foreach ($data as $key => $value) {
            foreach ($value as $k => $v) {
                array_push($headers, $k);
            }
        }

        $headers = collect($headers)->unique()->toArray();

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
            } elseif ($response == 'string' || $response == 'integer') {
                $unique_values[$key] = $response;
            } elseif ($response == 'n') {
                continue;
            }
        }

        return $unique_values;
    }

    public function buildParamsArray($unique_values, $strict_fields)
    {
        $params = [];
        $unique_values = array_merge($unique_values, ['strict_fields' => $strict_fields]);

        foreach ($unique_values as $key => $value) {
            if (is_object($value) || is_array($value)) {
                if (is_object($value)) {
                    $value = array_values($value->toArray());
                }

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
                    'type' => $value,
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
                        'summary' => '',
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

        file_put_contents('./public/docs/'. $this->argument('api_name') . '-test.yaml', $yaml);
    }
}
