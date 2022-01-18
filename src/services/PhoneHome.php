<?php

namespace sitemill\powertools\services;

use Craft;
use craft\base\PluginInterface;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use GuzzleHttp\Client;
use sitemill\powertools\jobs\PhoneHomeJob;
use yii\base\Module;

class PhoneHome
{
    private static $_cacheKey;
    private static $_siteUrl;
    private static $_notion;
    private static $_database;

    /**
     * If we haven't checked within a day, push a new job to the queue
     */
    public static function makeCall()
    {
        if (Craft::$app->cache->get(self::$_cacheKey) !== false || self::_doesQueueJobExist()) {
            return;
        }
        Craft::$app->queue->push(new PhoneHomeJob());
    }

    /**
     * Create or update existing record in Notion
     */
    public static function makeRequest()
    {
        $key = getenv('NOTION_API_KEY');
        self::$_database = getenv('NOTION_DATABASE');

        // Missing environment values, do nothing
        if ($key === false || self::$_database === false) {
            return;
        }

        self::$_siteUrl = UrlHelper::siteUrl('/');
        self::$_notion = new Client([
            'base_uri' => 'https://api.notion.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $key,
                'Notion-Version' => '2021-08-16'
            ]
        ]);

        $existingRecord = self::_existingRecord();

        try {
            if ($existingRecord === null) {
                $request = self::$_notion->request('POST', 'pages', [
                    'json' => [
                        'parent' => [
                            'database_id' => self::$_database,
                        ],
                        'properties' => self::_appInfo()
                    ]
                ]);
            } else {
              $request = self::$_notion->request('PATCH', 'pages/' . $existingRecord, [
                    'json' => [
                        'properties' => self::_appInfo()
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Craft::error(
                'Error phoning home',
                __METHOD__
            );
        }

        $oneDayDuration = 60 * 60 * 24;
        Craft::$app->cache->set(self::$_cacheKey, true, $oneDayDuration);
    }


    /**
     * Check if existing Phoning home job is already in the queue
     *
     * @return boolean
     */
    private static function _doesQueueJobExist(): bool
    {
        // Potentially using the redis queue, so property doesn't exist
        if (!Craft::$app->queue->hasProperty('jobInfo')) {
            return false;
        }

        return in_array('Phoning home', array_column(Craft::$app->queue->jobInfo, 'description'), true);
    }

    private static function _existingRecord(): ?string
    {
        $request = self::$_notion->request('POST', 'databases/' . self::$_database . '/query', [
            'json' => [
                'filter' => [
                    'property' => 'Url',
                    'text' => [
                        'equals' => self::$_siteUrl
                    ]
                ]
            ]
        ]);
        $queryResponse = json_decode($request->getBody());
        return $queryResponse->results[0]->id ?? null;
    }

    private static function _appInfo(): array
    {
        return [
            'Site Name' => [
                'title' => [
                    [
                        'text' => [
                            'content' => Craft::$app->getSystemName()
                        ]
                    ]
                ]
            ],
            'Url' => [
                'url' => self::$_siteUrl
            ],
            'Craft Edition' => [
                'select' => [
                    "name" => App::editionName(Craft::$app->getEdition())
                ]
            ],
            'Craft Version' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => Craft::$app->getVersion()
                        ]
                    ]
                ]
            ],
            'PHP Version' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => App::phpVersion()
                        ]
                    ]
                ]
            ],
            'DB Version' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => self::_dbDriver()
                        ]
                    ]
                ]
            ],
            'Plugins' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => self::_plugins()
                        ]
                    ]
                ]
            ],
            'Modules' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => self::_modules()
                        ]
                    ]
                ]
            ]
        ];
    }

    private static function _plugins(): string
    {
        $plugins = Craft::$app->plugins->getAllPlugins();

        return implode(PHP_EOL, array_map(function($plugin) {
            return "{$plugin->name} ({$plugin->developer}): {$plugin->version}";
        }, $plugins));
    }

    private static function _dbDriver(): string
    {
        $db = Craft::$app->getDb();

        if ($db->getIsMysql()) {
            $driverName = 'MySQL';
        } else {
            $driverName = 'PostgreSQL';
        }

        return $driverName . ' ' . App::normalizeVersion($db->getSchema()->getServerVersion());
    }

    private static function _modules(): string
    {
        $modules = [];

        foreach (Craft::$app->getModules() as $id => $module) {
            if ($module instanceof PluginInterface) {
                continue;
            }

            if ($module instanceof Module) {
                $modules[$id] = get_class($module);
            } else if (is_string($module)) {
                $modules[$id] = $module;
            } else if (is_array($module) && isset($module['class'])) {
                $modules[$id] = $module['class'];
            }
        }

        return implode(PHP_EOL, $modules);
    }


}