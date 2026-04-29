<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothAccountRepository.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAccountRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth accounts
 */

namespace APP\plugins\generic\thoth\classes\repositories;

use ThothApi\GraphQL\Client;
use ThothApi\GraphQL\Request;

class ThothAccountRepository
{
    private const ME_WITH_PUBLISHERS_QUERY = <<<GQL
        query {
            me {
                publisherContexts {
                    publisher {
                        publisherId
                        publisherName
                    }
                    permissions {
                        publisherAdmin
                        workLifecycle
                        cdnWrite
                    }
                }
            }
        }
    GQL;

    protected $thothClient;
    protected array $httpConfig;
    protected string $token;

    public function __construct($thothClient, array $httpConfig = [], string $token = '')
    {
        $this->thothClient = $thothClient;
        $this->httpConfig = $httpConfig;
        $this->token = $token;
    }

    public function getLinkedPublishers()
    {
        $publisherContexts = $this->token
            ? $this->getPublisherContextsWithPublishers()
            : $this->thothClient->me()->getPublisherContexts() ?? [];

        return array_values(array_map(
            fn (array $publisherContext) => $publisherContext['publisher'],
            array_filter($publisherContexts, fn (array $publisherContext) => !empty($publisherContext['publisher']))
        ));
    }

    protected function getPublisherContextsWithPublishers(): array
    {
        $httpConfig = $this->httpConfig ?: ['base_uri' => Client::THOTH_BASE_URI];
        $response = (new Request($httpConfig))->runQuery(self::ME_WITH_PUBLISHERS_QUERY, null, $this->token);

        return $response->getData()['me']['publisherContexts'] ?? [];
    }
}
