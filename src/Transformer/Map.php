<?php
namespace Giift\Etl\Transformer;

/**
 * Transforms the data for storing it in proper format or structure for querying and analysis purpose
 * this class extends Node class
 *
 * @author giift
 */
class Map extends \Giift\Etl\Node
{
    /**
     * array of mapping to transform data
     *
     * @var array
     */
    private $map = array();

    /**
     * constructor
     *
     * @param array $config Fields array from config.
     */
    public function __construct(array $config = array())
    {
        parent::__construct($config);
        $this->map = (isset($config['map']) && is_array($config['map'])) ? $config['map'] : array();
    }

    /**
     * send data next step
     *
     * @see \Etl\Node::processRecord()
     * @param array $record Single array from single line of csv.
     * @return string
     */
    public function processRecord(array $record)
    {
        $map_count = count($this->map);
        $data_count = count($record);
        $data = array();
        if ($data_count === $map_count && $data_count > 0) {
            $data = array_combine($this->map, $record);
        }

        return $this->sendNextStep($data);
    }
}
