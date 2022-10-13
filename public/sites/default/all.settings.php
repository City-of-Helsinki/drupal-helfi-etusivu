<?php

/**
 * @file
 * Contains site specific overrides.
 */

// Elasticsearch settings.
if (getenv('ELASTICSEARCH_URL')) {
  $config['elasticsearch_connector.cluster.news']['url'] = getenv('ELASTICSEARCH_URL');

  if (getenv('ELASTIC_USER') && getenv('ELASTIC_PASSWORD')) {
    $config['elasticsearch_connector.cluster.news']['options']['use_authentication'] = '1';
    $config['elasticsearch_connector.cluster.news']['options']['authentication_type'] = 'Basic';
    $config['elasticsearch_connector.cluster.news']['options']['username'] = getenv('ELASTIC_USER');
    $config['elasticsearch_connector.cluster.news']['options']['password'] = getenv('ELASTIC_PASSWORD');
  }
}

// Elastic proxy URL.
$config['elastic_proxy.settings']['elastic_proxy_url'] = getenv('ELASTIC_PROXY_URL');
