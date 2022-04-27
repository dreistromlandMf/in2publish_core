<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling;

use In2code\In2publishCore\Component\TcaHandling\Demand\DemandService;
use In2code\In2publishCore\Component\TcaHandling\Query\QueryService;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordTree;

use function array_diff;
use function array_keys;
use function array_merge;
use function implode;
use function In2code\In2publishCore\merge_records;
use function preg_match_all;

class DerServiceUmbenennen
{
    protected QueryService $queryService;
    protected ConfigContainer $configContainer;
    protected DemandService $demandService;
    protected RecordFactory $recordFactory;
    protected RecordIndex $recordIndex;

    public function injectQueryService(QueryService $queryService): void
    {
        $this->queryService = $queryService;
    }

    public function injectConfigContainer(ConfigContainer $configContainer): void
    {
        $this->configContainer = $configContainer;
    }

    public function injectDemandService(DemandService $demandService): void
    {
        $this->demandService = $demandService;
    }

    public function injectRecordFactory(RecordFactory $recordFactory): void
    {
        $this->recordFactory = $recordFactory;
    }

    public function injectRecordIndex(RecordIndex $recordIndex): void
    {
        $this->recordIndex = $recordIndex;
    }

    public function buildRecordTree(string $table, int $id): RecordTree
    {
        $recordTree = new RecordTree();

        $records = $this->findRequestedRecordWithTranslations($table, $id, $recordTree);

        $this->findPagesRecursively($records);

        $records = $this->findAllRecordsOnPages();

        $this->findRecordsByTca($records);

        $this->recordIndex->connectTranslations();

        return $recordTree;
    }

    private function findRequestedRecordWithTranslations(string $table, int $id, RecordTree $recordTree): array
    {
        if ('pages' === $table && 0 === $id) {
            $pageTreeRootRecord = $this->recordFactory->createPageTreeRootRecord();
            $recordTree->addChild($pageTreeRootRecord);
            $return = [];
            $return['pages'][0] = $pageTreeRootRecord;
            return $return;
        }
        $demand = [];
        $demand['select'][$table]['']['uid'][$id] = $recordTree;

        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;
        if (null !== $transOrigPointerField) {
            $demand['select'][$table][''][$transOrigPointerField][$id] = $recordTree;
        }

        return $this->queryService->resolveDemand($demand);
    }

    /**
     * @param array<string, array<int|string, Record>> $records
     */
    private function findPagesRecursively(array $records): void
    {
        $currentRecursion = 0;
        $recursionLimit = 5;

        while ($recursionLimit > $currentRecursion++ && !empty($records)) {
            $demand = [];
            foreach ($records['pages'] ?? [] as $record) {
                $demand['select']['pages']['']['pid'][$record->getId()] = $record;
            }
            $records = $this->queryService->resolveDemand($demand);
        }
    }

    public function findAllRecordsOnPages(): array
    {
        $pages = $this->recordIndex->getRecordByClassification('pages');
        if (empty($pages)) {
            return [];
        }
        $demand = [];

        $nonExcludedTables = $this->getAllTablesWhichAreNotExcluded();

        foreach ($nonExcludedTables as $table) {
            foreach ($pages as $page) {
                $demand['select'][$table]['']['pid'][$page->getId()] = $page;
            }
        }
        $records = $this->queryService->resolveDemand($demand);
        merge_records($records, ['pages' => $pages]);
        return $records;
    }

    /**
     * @param array<string, array<int|string, Record>> $records
     */
    public function findRecordsByTca(array $records): void
    {
        $currentRecursion = 0;
        $recursionLimit = 7;

        while ($recursionLimit > $currentRecursion++ && !empty($records)) {
            $demand = $this->demandService->buildDemandForRecords($records);

            $records = $this->queryService->resolveDemand($demand);
        }
    }

    /**
     * @return array<string>
     */
    public function getAllTablesWhichAreNotExcluded(): array
    {
        // This array contains regular expressions which every table name has to be tested against.
        $excludeRelatedTables = $this->configContainer->get('excludeRelatedTables');

        // Compose a RegEx which matches all excluded tables.
        $regex = '/,(' . implode('|', array_merge(['pages'], $excludeRelatedTables)) . '),/iU';

        // Combine all existing tables into a single string, where each table is delimited by ",,", so preg_match will
        // match two consecutive table names when searching for ",table1, OR ,table2," in ",table1,,table2,".
        // Otherwise, the leading comma of the first table will be consumed by the expression, and it will not match the
        // second table.
        $tables = array_keys($GLOBALS['TCA']);
        $tablesString = ',' . implode(',,', $tables) . ',';
        $matches = [];

        // $matches[1] contains all table names which match all the expressions from excludeRelatedTables.
        preg_match_all($regex, $tablesString, $matches);

        // Remove all excluded tables from the list of existing tables.
        return array_diff($tables, $matches[1]);
    }
}
