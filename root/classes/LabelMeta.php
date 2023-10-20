<?php
namespace eGamings\WLC;

class LabelMeta
{
    /** @var string Metric namespace */
    private $namespace = '';

    /** @var string Metric description */
    private $description = '';

    /** @var string Metric type */
    private $type = '';

    public function __construct(string $namespace, string $description, string $type)
    {
        $this->namespace = $namespace;
        $this->description = $description;
        $this->type = $type;
    }

     /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

     /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

     /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
