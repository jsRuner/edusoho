<?php

namespace Topxia\Service\Quiz\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Quiz\Dao\TestItemDao;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Connection;

class TestItemDaoImpl extends BaseDao implements TestItemDao
{
    protected $table = 'test_item';

    public function getItem($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }

    public function addItem($item)
    {
        $item = $this->getConnection()->insert($this->table, $item);
        if ($item <= 0) {
            throw $this->createDaoException('Insert item error.');
        }
        return $this->getItem($this->getConnection()->lastInsertId());
    }

    //`testId`,`seq`,`questionId`,`questionType`,`score`
    public function addItems(array $items)
    {
        if(empty($items)){ 
            return array(); 
        }
        $items = implode(',',$items);
        $sql ="INSERT INTO {$this->table} (`testId`,`seq`,`questionId`,`questionType`,`score`) VALUES  {$items} ";
        return $this->getConnection()->executeUpdate($sql);
    }

    public function updateItem($id, $fields)
    {
        $this->getConnection()->update($this->table, $fields, array('id' => $id));
        return $this->getItem($id);
    }

    public function deleteItem($id)
    {
        return $this->getConnection()->delete($this->table, array('id' => $id));
    } 

    public function deleteItemsByParentId($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE parentId = ?";
        return $this->getConnection()->executeUpdate($sql, array($id));
    }

    public function searchItemCount($conditions)
    {
        $builder = $this->_createSearchQueryBuilder($conditions)
             ->select('COUNT(id)');

        return $builder->execute()->fetchColumn(0);
    }

    public function searchItem($conditions, $orderBy, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $builder = $this->_createSearchQueryBuilder($conditions)
            ->select('*')
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->orderBy($orderBy[0], $orderBy[1]);

        return $builder->execute()->fetchAll() ? : array();
    }

    public function findItemByIds(array $ids)
    {
        if(empty($ids)){ 
            return array(); 
        }
        $marks = str_repeat('?,', count($ids) - 1) . '?';
        $sql ="SELECT * FROM {$this->table} WHERE id IN ({$marks});";
        return $this->getConnection()->fetchAll($sql, $ids);
    }

    public function deleteItemByIds(array $ids)
    {
        if(empty($ids)){ 
            return array(); 
        }
        $marks = str_repeat('?,', count($ids) - 1) . '?';
        $sql ="DELETE FROM {$this->table} WHERE id IN ({$marks});";
        return $this->getConnection()->executeUpdate($sql, $ids);
    }

    public function getItemsCountByTestId($testId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE testId = ? ";
        return $this->getConnection()->fetchColumn($sql, array($testId));
    }

    private function _createSearchQueryBuilder($conditions)
    {
        $builder = $this->createDynamicQueryBuilder($conditions)
            ->from($this->table, 'item')
            ->andWhere('questionType = :questionType')
            ->andWhere('parentId = :parentId')
            ->andWhere('targetId = :targetId')
            ->andWhere('targetType = :targetType');

        if(empty($conditions['parentId'])){
            $builder->andStaticWhere(" `parentId` = '0' ");
            
        }   

        if (isset($conditions['target']) && empty($conditions['parentId']) ) {
            $target = array();
            foreach ($conditions['target'] as $targetType => $targetIds) {
                if (is_array($targetIds)) {
                    foreach ($targetIds as $key => $targetId) {
                        $targetIds[$key] = (int) $targetId;
                    }
                    $targetIds = join(' , ', $targetIds);
                } else {
                    $targetIds = (int) $targetIds;
                }
                $target[] = " targetType ='".$targetType."' and targetId in (".$targetIds.")"  ;
            }
            if (!empty($target)) {
                $target = join(' or ', $target);
                $builder->andStaticWhere(" ($target) ");
            }
        }
      
        return $builder;
    }

}