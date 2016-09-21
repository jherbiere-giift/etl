<?php
namespace Giift\Etl;

/**
 * Class Node
 *
 * @author giift
 */
abstract class Node
{
    /**
     * Chain of ETL
     *
     * @var object
     */
    private $outputs_ = array();

    /**
     * id of the output class
     * @var unknown
     */
    private $outputs_ids_ = array();

    //2 level array, first level is node class, second level is node type, and value is class full name
    private static $nodesCatalog_ = array(
        'extractor' => array(
            'csv' => '\\Giift\\Etl\\Extractor\\Csv'
        ),
        'loader' => array(
            'debug' => '\\Giift\\Etl\\Loader\\Debug'
        ),
        'transformer' => array(
            'add_field' => '\\Giift\\Etl\\Transformer\\AddField',
            'conditional_if' => '\\Giift\\Etl\\Transformer\\ConditionalIf',
            'filter' => '\\Giift\\Etl\\Transformer\\Filter',
            'map' => '\\Giift\\Etl\\Transformer\\Map',
            'replace' => '\\Giift\\Etl\\Transformer\\Replace'
        )
    );

    /**
     * Constructor
     * @param object $config
     */
    public function __construct(array $config = array())
    {
        $this->outputs_ids_ = (isset($config['output']) && is_array($config['output'])) ? $config['output'] : array();
    }

    /**
     * Build a node from its class, its type and its config
     * @return \Giift\Etl\Node
     */
    public static function forge($class, $type, array $config = array())
    {
        if (isset(self::$nodesCatalog_[$class]) && isset(self::$nodesCatalog_[$class][$type])) {
            $class = self::$nodesCatalog_[$class][$type];
            return new $class($config);
        }
        return null;
    }

    /**
     * Function process record
     *
     * @param array $record
     */
    abstract public function processRecord(array $record);

    /**
     * Add node to output
     *
     * @param object $node
     */
    public function addOutput(\Giift\Etl\Node &$node)
    {
        $this->outputs_[] = $node;
    }

    /**
     * @return array
     */
    public function getOutputs()
    {
        return $this->outputs_;
    }

    /**
     * Send record to next step
     *
     * @param array $record
     */
    public function sendNextStep(array $record, $node_id = null)
    {
        $res = '';
        if (!is_null($node_id)) {
            try {
                $res .= $this->outputs_[$node_id]->processRecord($record);
            } catch (\Exception $e) {
                //log to gerror
                $log_gerror = new \Model_Gerror();
                $log_gerror->type = 'ETL';
                $log_gerror->file = __FILE__;
                $log_gerror->line = __LINE__;
                $log_gerror->str = 'ETL: Node error.';
                $log_gerror->set_info(
                    'message',
                    array(
                    'outputs'=>print_r($record, true),
                    'Exception'=>$e->getMessage()
                    )
                );
                $log_gerror->save();

                $res .= $e->getMessage();
            }
        } else {
            foreach ($this->outputs_ as $output) {
                try {
                    $res .= $output->processRecord($record);
                } catch (Exception $e) {
                    //log to gerror
                    $log_gerror = new \Model_Gerror();
                    $log_gerror->type = 'ETL';
                    $log_gerror->file = __FILE__;
                    $log_gerror->line = __LINE__;
                    $log_gerror->str = 'ETL: Node error.';
                    $log_gerror->set_info(
                        'message',
                        array('outputs'=>print_r($output, true), 'Exception'=>$e->getMessage())
                    );
                    $log_gerror->save();

                    $res .= $e->getMessage();
                }
            }
        }

        return $res;
    }

    /**
     * link output together
     * @param object $chain
     */
    public function link(\Giift\Etl\Chain &$chain)
    {
        if (empty($this->outputs_)) {
            foreach ($this->outputs_ids_ as $id) {
                $output = $chain->getNode($id);
                if (!is_null($output)) {
                    $this->addOutput($output);
                    $output->link($chain);
                }
            }
        }
    }
}
