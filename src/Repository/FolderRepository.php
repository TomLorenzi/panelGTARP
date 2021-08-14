<?php

namespace App\Repository;

use App\Entity\Folder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Folder|null find($id, $lockMode = null, $lockVersion = null)
 * @method Folder|null findOneBy(array $criteria, array $orderBy = null)
 * @method Folder[]    findAll()
 * @method Folder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FolderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Folder::class);
    }

    /**
     * @param array|null $filters
     * @return array $result
     */
    public function getListFolders($filters = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('folder')
            ->from($this->_entityName, 'folder');

        $qb->andWhere('folder.state = 1');

        $columnSort = 'folder.id';
        $directionSort = 'asc';
        if(isset($filters['order'])) {
            $columnSort = $filters['order'][0]['column'];
            if(is_numeric($filters['order'][0]['column'])) {
                $orderColumn = $filters['order'][0]['column'];
                $columnName = $filters['columns'][$orderColumn]['data'];
                $columnSort = 'folder.' . $columnName;
            }
            $directionSort = $filters['order'][0]['dir'];
        }

        $qb->groupBy('folder.id');
        $qb->orderBy($columnSort, $directionSort);

        if (isset($filters['start']) && $filters['start'] !== '') {
            $qb->setFirstResult($filters['start']);
        }

        if(isset($filters['length'])) {
            $qb->setMaxResults($filters['length']);
        }

        $result = $qb->getQuery()->getResult();

        $serializedResult = [];

        foreach($result as $folder) {
            $folderArray = array(
                'id' => $folder->getId(),
                'date' => $folder->getInserted()
            );
            $serializedResult[] = $folderArray;
        }

        $totalFolders = $this->countCraftsFiltered();

        $serializedList = array(
            'draw' => $filters['draw'],
            'recordsTotal' => $totalFolders,
            'recordsFiltered' => $totalFolders,
            'data' => $serializedResult
        );

        $serializedIndexList = array();
        foreach($serializedResult as $folder) {
            $serializedIndexList[] = $folder;
        }
        $serializedList['data'] = $serializedIndexList;

        return $serializedList;
    }

    /**
     * @return array $result
     */
    public function countCraftsFiltered()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('count(folder.id)')
            ->from($this->_entityName, 'folder');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
