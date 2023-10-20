<?php

namespace eGamings\WLC;

use eGamings\WLC\System;
use eGamings\WLC\Logger;

// @codeCoverageIgnoreStart
class PrometheusKey
{
    /** @var string Contains metric type (counter or histogram). */
    private $metric_type;

    /** @var string Contains metric label (in the form of {param="value",...} */
    private $metric_label = "";

    /** @var string Contains description. */
    private $description = "";

    /** @var string Contains metric name, that goes in front of the label (metric_name{}) */
    private $metric_name = "";

    /** @var string The metric to be used as a key value to save in redis */
    private $internal_metric = "";

    /** @var string The metric to expose. Can be extended with additional params which are absent from the internal_metric */
    private $external_metric = "";

    /** @var array To store all the necessary metrics to process them on demand later */
    private $metric_labels = [];

    /** @var array To store all the necessary metrics to process them on demand later */
    private $additional_labels = [];

    private $prefix = 'wlc_';
    private $namespace = '';

    public function __construct(string $type, string $metric_name, string $description)
    {
        $this->metric_type = $type;
        $this->metric_name = $metric_name;
        $this->description = $description;
        $this->namespace = $this->prefix . $metric_name;
        $this->additional_labels = [
            'instance' => Logger::getInstanceName(),
        ];
        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function store(): bool
    {
        $redis = System::redis();

        if (!$redis) {
            return false;
        }

        switch ($this->metric_type) {
            case PrometheusMetricTypes::COUNTER:
                return (bool)!$redis->incr($this->getInternalValue());
            case PrometheusMetricTypes::HISTOGRAM:
                return true;
            default:
                return false;
        }
    }
    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->metric_type;
    }

    /**
     * @return string
     */
    public function getAllKeysValue(): string
    {
        $label_output = new LabelOutput(new LabelMeta($this->namespace, $this->description, $this->getType()));
        return $label_output->getOutput();
    }

    /**
     * @return array
     */
    public function getKeyValue(): string
    {
        $redis = System::redis();
        return
            "#HELP {$this->namespace} {$this->description}" . "\n" .
            "#TYPE {$this->namespace} {$this->getType()}" . "\n" .
            $this->getExternalValue() . " " . (int)$redis->get($this->getInternalValue()) . "\n\n";
    }

    /**
     * @param string $label_name
     * @param string $label_value
     * @return PrometheusKey
     */
    private function setLabel(string $label_name, string $label_value)
    {
        $this->metric_labels[$label_name] = $label_value;
        ksort($this->metric_labels);
        $this->generate();
        return $this;
    }

    private function generate(): void
    {
        $strings = [];
        foreach ($this->metric_labels as $key => $value) {
            $strings[] = "${key}=\"${value}\"";
        }
        $internal_labels = implode(',', $strings);
        $this->internal_metric = "{$this->namespace}{{$internal_labels}}";


        foreach ($this->additional_labels as $key => $value) {
            $strings[] = "${key}=\"${value}\"";
        }
        $external_labels = implode(',', $strings);
        $this->external_metric = "{$this->namespace}{{$external_labels}}";
    }

    /**
     * @param string $label_value
     * @return PrometheusKey
     */
    public function l_app(string $label_value)
    {
        return $this->setLabel('app', $label_value);
    }

    /**
     * @param string $label_value
     * @return PrometheusKey
     */
    public function l_type(string $label_value)
    {
        return $this->setLabel('type', $label_value);
    }

    /**
     * @param string $label_value
     * @return PrometheusKey
     */
    public function l_domain(string $label_value)
    {
        return $this->setLabel('domain', $label_value);
    }

    /**
     * @param string $label_value
     * @return PrometheusKey
     */
    public function l_instance(string $label_value)
    {
        return $this->setLabel('instance', $label_value);
    }

    /**
     * @param string $label_value
     * @return PrometheusKey
     */
    public function l_hostname(string $label_value)
    {
        return $this->setLabel('hostname', $label_value);
    }

    /**
     * @return string
     */
    public function getInternalValue(): string
    {
        return $this->internal_metric;
    }

    public function getExternalValue(): string {
        return $this->external_metric;
    }
}
// @codeCoverageIgnoreEnd
