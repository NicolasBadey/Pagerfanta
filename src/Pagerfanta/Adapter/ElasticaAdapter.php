<?php

/*
 * This file is part of the Pagerfanta package.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pagerfanta\Adapter;

use Pagerfanta\Exception\InvalidArgumentException;
use Elastica\SearchableInterface;
use Elastica\Query;

/**
 * ElasticaAdapter.
 *
 * @author Nicolas Badey <nicolasbadey@gmail.com>
 */
class ElasticaAdapter implements AdapterInterface
{

    /**
     * @var SearchableInterface the object to search in
     */
    private $searchable;

    /**
     * @var Query the query to search
     */
    private $query;

    /**
     * @var integer the number of hits
     */
    private $totalHits;


    /**
     * Constructor
     *
     * @param SearchableInterface $searchable the object to search in
     * @param Query $query the query to search
     */
    public function __construct(SearchableInterface $searchable, Query $query)
    {
        $this->searchable = $searchable;
        $this->query      = $query;
    }


    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        if ( ! isset($this->totalHits)) {
            $this->totalHits = $this->searchable->search($this->query)->getTotalHits();
        }

        return $this->query->hasParam('size')
            ? min($this->totalHits, (integer) $this->query->getParam('size'))
            : $this->totalHits;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $offset = (integer) $offset;
        $length = (integer) $length;
        $size = $this->query->hasParam('size')
            ? (integer) $this->query->getParam('size')
            : null;

        if ($size && $size < $offset + $length) {
            $length = $size - $offset;
        }

        if ($length < 1) {
            throw new InvalidArgumentException('$length must be greater than zero');
        }

        $query = clone $this->query;
        $query->setFrom($offset);
        $query->setSize($length);

        $resultSet = $this->searchable->search($query);
        $this->totalHits = $resultSet->getTotalHits();
        $this->facets = $resultSet->getFacets();

        return $resultSet;
    }
}
